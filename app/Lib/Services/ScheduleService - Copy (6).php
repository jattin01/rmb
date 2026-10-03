<?php

namespace App\Lib\Services;

use Exception;
use Illuminate\Support\Facades\File;
use App\Helpers\ConstantHelper;
use App\Helpers\V2\BatchingPlantHelper;
use App\Helpers\V2\PumpHelper;
use App\Helpers\V2\TransitMixerHelper;
use App\Helpers\V2\TransitMixerRestrictionHelper;
use App\Helpers\CustomerProjectSiteHelper;
use App\Models\BatchingPlantAvailability;
use App\Models\CustomerProjectSite;
use App\Models\GlobalSetting;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\SelectedOrderSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ScheduleData
{
    public $user_id;
    public $reschedule_minutes;
    public $max_delay_minutes;
    public $needs_progressive_delay;
    public $deviation;
    public $assigned_plant;
    public $min_delivery_time;
    public $assigned_pumps_per_order;
    public $tolerance_percent;
    public $interval_step;
    public $interval_up;
    public $quantity;
    public $failure_reason;
    public $pump_busy_slots;
    public $pump_busy_slots_unset;
    public $truck_busy_slots;
    public $original_bps;
    public $original_tms;
    public $plant_busy_slots;
    public $pump_loading_time;
    public $assign_pump_slot;
    public $interval;
    public $trip;
    public $order_interval;
    public $order_start_time;
    public $transit_mixers;
    public $pump_ids;
    public $batching_plant_ids;
    public $min_loading_start;
    public $order_end_time;
    public $company;
    public $schedule_date;
    public $delivered_quantity;
    public $sch_adj_from;
    public $order_start;
    public $early_trip;
    public $late_trip;
    public $phase;
    public $current_interval;
    public $phase_seq;
    public $pouring_time;
    public $pouring_interval;
    public $pump_qty;
    public $pump_cap;
    public $batching_qty;
    public $next_qty;
    public $trip_time;
    public $sch_adj_to;
    public $tms_availability;
    public $pumps_availability;
    public $bps_availability;
    public $bps_availability_full;
    public $consolidated_single_plant;
    public $schedule_preference;
    public $shift_start;
    public $shift_end;
    public $restriction_start;
    public $restriction_end;
    public $min_order_start_time;
    public $interval_deviation;
    public $generateLog;
    public $execute;
    public $truck_capacity;
    public $order_no;
    public $location;
    public $next_delivery_time;
    public $lastResponse;
    public $next_loading_time;
    public $qc_time;
    public $insp_time;
    public $cleaning_time;
    public $loading_time;
    public $orders_copy;
    public $schedules;
    public $selected_order_pump_schedules;
    public $travel_start;
    public $travel_end;
    public $loading_start;
    public $loading_end;
    public $qc_start;
    public $qc_end;
    public $insp_start;
    public $insp_end;
    public $pouring_start;
    public $pouring_end;
    public $cleaning_start;
    public $cleaning_end;
    public $return_start;
    public $return_end;
    public $install_end;
    public $install_start;
    public $waiting_start;
    public $waiting_end;
    public $install_time;
    public $waiting_time;
    public $delivery_time;
    public $return_time;
    public $travel_time;
    public $total_time;
    public $expected_total_duration;
    public $shift_end_exit;
    public $is_completed;
    public $transit_mixer;
    public $batching_plant;
    public $pouring_pump;
    public $assigned_pump;
    public $assigned_pumps;
    public $assigned_plants;
    public $assigned_tms;
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
class ScheduleService
{
    const DEFAULT_TRUCK_CAPACITY = 8;
    // When the total ordered quantity across the whole run exceeds this,
    // workload is DIVIDED across plants. At or below it, orders are
    // CONSOLIDATED onto the plant that already carries the most load.
    const PLANT_DIVIDE_QTY_THRESHOLD = 500;
    // Small tail added to a pump order's idealised reservation window. With the
    // loser-reordering in scheduleTripsChronologically, a losing pump order now
    // keys off the winner's REAL pump release (the reservation end is corrected
    // to the actual return_end before the loser runs), so this no longer sets
    // the loser's start. It only widens pre-pass conflict detection slightly so
    // a marginally-overlapping order is correctly treated as a loser (i.e.
    // scheduled after the winner) rather than colliding. Keep it small.
    const PUMP_SLIP_BUFFER_MINS = 2;
    // Incremental-schedule delay retry cap. When a candidate order causes a
    // partial (but is NOT itself hard-rejected), runIncrementalSchedule() nudges
    // the candidate forward by +1 minute and retries. This is the upper bound on
    // those +1-min retries per candidate, guarding against infinite loops. The
    // effective cap is min(this, candidate->max_delay) when the order declares a
    // max_delay, so we never push an order past its own allowed delay window.
    // ── Delay-prediction search tuning ──────────────────────────────────────
    // When a candidate causes partials (but is not hard-rejected), the
    // incremental scheduler searches for the smallest start-delay (minutes) that
    // schedules it cleanly, instead of stepping +1 min at a time. It first
    // PROBES in geometric jumps to bracket a working delay, then BINARY-SEARCHES
    // down to the minimum. Every tested delay is actually scheduled and only
    // accepted if it yields zero partials.
    const INCR_MAX_DELAY_MINUTES   = 480; // absolute ceiling on candidate start-delay
    const INCR_DELAY_PROBE_BASE_MINS = 5; // first geometric probe step (then doubles)
    const INCR_DELAY_MAX_PROBES    = 12;  // hard cap on probe schedule passes
    const INCR_DELAY_MAX_REFINE    = 8;   // hard cap on binary-refine schedule passes
    // Failure-reason fragments that mark a HARD rejection — one a +1-minute
    // candidate delay genuinely cannot fix, because it is a resource denial /
    // structural infeasibility for the order itself rather than a timing
    // conflict. Matched case-insensitively as substrings of failure_reason.
    //
    // Everything ELSE the scheduler writes (delay-limit exceeded, supply-rate
    // below minimum, "partial scheduling is not allowed", generic
    // could-not-schedule-within-constraints) is a TIMING/partial condition: it
    // stays a partial and feeds the delay-retry loop. Do NOT add "delay limit"
    // here — the pump-denial string itself contains the words "delay limit
    // tolerance", and delay-limit *exceeded* is precisely the retryable case.
    const HARD_REJECT_REASON_MARKERS = [
        'No available pump found',
    ];
    protected $pumpHelper;
    protected $transitMixerHelper;
    protected $batchingPlantHelper;
    protected $restrictionHelper;
    public function __construct()
    {
        ini_set('max_execution_time', '-1');
        $this->pumpHelper           = new PumpHelper();
        $this->transitMixerHelper   = new TransitMixerHelper();
        $this->batchingPlantHelper  = new BatchingPlantHelper();
        $this->restrictionHelper    = new TransitMixerRestrictionHelper();
    }
    public function initializeSchedule(
        int    $user_id,
        string $company,
        string $schedule_date,
        array  $transit_mixer_ids,
        array  $pump_ids,
        array  $batching_plant_ids,
        string $schedule_preference,
        string $shift_start,
        string $shift_end,
        int    $interval_deviation
    ) {
        try {
            File::delete(storage_path('logs/laravel.log'));
            $shift_end = Carbon::parse($shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME);
            $this->clearPreviousSchedules($company, $user_id, $shift_start, $shift_end);
            $shift_start = Carbon::parse($shift_start)->subDay()->format(ConstantHelper::SQL_DATE_TIME);
            $tmsAvailability = $this->transitMixerHelper->getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);

            // Sum of quantity across all selected orders for this run.
            // Drives single-plant consolidation: when this total is <= 500,
            // we start with ONE plant and only open the rest of the fleet if
            // that single plant can't place everything (see generateSchedule).
            // (Mirrors the fetchOrders() filters so the totals stay in sync.)
            $totalOrdersQty = (int) SelectedOrder::where("group_company_id", $company)
                ->where("user_id", $user_id)
                ->whereBetween("delivery_date", [$shift_start, $shift_end])
                ->whereNull("start_time")
                ->where("selected", true)
                ->sum("quantity");

            // Full plant fleet (every selected plant).
            $bpsFull = $this->batchingPlantHelper->getBatchingPlantAvailabilityCopy(
                $company,
                $schedule_date,
                $batching_plant_ids,
                $this->batchingPlantHelper->getMinOrderScheduleTimeCopy($company, $user_id, $shift_start, $shift_end, $schedule_date)
            );

            // Consolidate to a single plant for small runs (<= 500). The full
            // list is kept so generateSchedule() can fall back to all plants
            // when the single plant overflows the delay limit.
            $consolidatedSinglePlant = (
                $totalOrdersQty > 0
                && $totalOrdersQty <= self::PLANT_DIVIDE_QTY_THRESHOLD
                && count($bpsFull) > 1
            );
            $bpsAvailability = $consolidatedSinglePlant ? [$bpsFull[0]] : $bpsFull;

            // ── Diagnostics: what does the scheduler actually see? ───────────
            // If this shows 1 plant, the run physically cannot split across two,
            // and small orders will share that single plant (and may overflow
            // the delay limit). Check that the 2nd plant is selected for this
            // run, serves the order's location, and has a location_shift row
            // (getBatchingPlantAvailabilityCopy needs it to return the plant).
            $plantSummary = collect($bpsFull)
                ->map(fn($p) => ($p['plant_name'] ?? '?') . '@' . ($p['location'] ?? '?'))
                ->implode(', ');
            Log::info("[PLANT_SETUP] total_qty={$totalOrdersQty} "
                . "plants_found=" . count($bpsFull) . " [{$plantSummary}] "
                . "selected_plant_ids=[" . implode(',', $batching_plant_ids) . "] "
                . "consolidated_single_plant=" . ($consolidatedSinglePlant ? 'YES (start on 1, expand if needed)' : 'NO (use all plants)'));

            $scheduleData = new ScheduleData([
                'user_id'           => $user_id,
                'company'           => $company,
                'schedule_date'     => $schedule_date,
                'sch_adj_from'      => 0,
                'sch_adj_to'        => 1440,
                'tms_availability'  => $tmsAvailability,
                'pumps_availability' => $this->pumpHelper->getPumpsAvailability($company, $schedule_date, $pump_ids),
                'bps_availability'  => $bpsAvailability,
                'bps_availability_full' => $bpsFull,
                'consolidated_single_plant' => $consolidatedSinglePlant,
                'schedule_preference' => $schedule_preference,
                'shift_start'         => $shift_start,
                'shift_end'           => $shift_end,
                'restriction_start'   => $this->restrictionHelper->getRestrictions($company, $schedule_date, $shift_start)['restriction_start'],
                'restriction_end'     => $this->restrictionHelper->getRestrictions($company, $schedule_date, $shift_start)['restriction_end'],
                'interval_deviation'  => $interval_deviation,
                'generateLog'         => false,
                'execute'             => false,
                'truck_capacity'      => max(array_unique(array_column($tmsAvailability, 'truck_capacity'))),
                'assigned_plants'     => [],
                'assigned_tms'        => [],
                'assigned_pumps'      => [],
                'orders_copy'         => [],
                'schedules'           => [],
                'selected_order_pump_schedules' => [],
                'transit_mixers'      => $transit_mixer_ids,
                'pump_ids'            => $pump_ids,
                'batching_plant_ids'  => $batching_plant_ids,
                'pump_busy_slots'     => [],
                'truck_busy_slots'    => [],
                'plant_busy_slots'    => [],
                'pump_busy_slots_unset'     => [],
            ]);
            $this->calculateAndStoreLpi($scheduleData);
            $this->generateSchedule($scheduleData);
            $conflicts = ScheduleService::validateAllResourceConflicts($scheduleData);
            Log::info('After optimise Schedule Conflicts:', $conflicts);
            $this->checkScheduleTimes($scheduleData);
        } catch (\Exception $e) {
            if (!$scheduleData->is_completed && !$scheduleData->failure_reason) {
                $scheduleData->failure_reason = "Unable to schedule within constraints";
            }
            Log::error('Schedule Initialization Error: ' . $e->getTraceAsString());
        }
    }
    private function clearPreviousSchedules($company, $user_id, $shift_start, $shift_end): void
    {
        SelectedOrderSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        SelectedOrderPumpSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        BatchingPlantAvailability::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        SelectedOrder::where("group_company_id", $company)
            ->whereBetween("delivery_date", [$shift_start, $shift_end])
            ->where("user_id", $user_id)
            ->update(['start_time' => null, 'end_time' => null, 'deviation' => null, 'delivered_quantity' => 0, 'location' => null, 'failure_reason' => null]);
    }
    public function generateSchedule(ScheduleData &$scheduleData)
    {
        try {
            $this->initializeVariables($scheduleData);
            $allOrders = $this->fetchOrders($scheduleData);
            $this->getLocations($allOrders, $scheduleData);

            Log::info("[INCR_SCHED] ── Step 0: feasibility filter on " . $allOrders->count() . " orders ──");
            if ($allOrders->isEmpty()) {
                Log::warning("[INCR_SCHED] No orders passed the feasibility filter — nothing to schedule.");
                return;
            }

            // Highest LPI first — these get admitted (and protected) before lower ones.
            $allOrders = $allOrders->sortByDesc('lpi_score')->values();

            // Snapshot clean pools ONCE. bps_availability here is the consolidated
            // (single-plant) set for small runs, or the full fleet otherwise.
            $pristine = $this->snapshotPools($scheduleData);

            // Generate trips ONCE for every order; each pass just filters to the set under test.
            $allTrips = $this->generateAllOrderTrips($scheduleData, $allOrders);
            Log::info("[INCR_SCHED] generated " . count($allTrips) . " trip(s) total");

            // Schedule the orders. When the run started consolidated onto ONE plant
            // and a candidate can't fit, runIncrementalSchedule() opens up the full
            // fleet mid-loop and retries that candidate instead of removing it.
            $result    = $this->runIncrementalSchedule($scheduleData, $allOrders, $allTrips, $pristine);
            $committed = $result['committed'];
            $rejected  = $result['rejected'];

            // Persist rejection reasons (final clear wiped them, so write last).
            foreach ($rejected as $orderId => $reason) {
                try {
                    DB::table('selected_orders')
                        ->where('id', $orderId)
                        ->update([
                            'failure_reason'     => $reason,
                            'delivered_quantity' => 0,
                            'start_time'         => null,
                            'end_time'           => null,
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("[INCR_SCHED] could not save failure_reason for order_id={$orderId}: " . $e->getMessage());
                }
            }
        } catch (\Exception $ex) {
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }
    /**
     * Incremental scheduler with plant-expansion RESTART-FROM-FRESH.
     *
     * The run starts consolidated on a single plant. If, while still on that one
     * plant, a candidate produces a HARD rejection (detectRejectedOrders) and a
     * second distinct plant exists, scheduleCandidateAtDelay() opens the full
     * fleet (flips consolidated_single_plant=false) and reports expanded=true.
     *
     * The moment that happens, every decision made under the single-plant
     * assumption is stale, so this wrapper DISCARDS the in-progress pass —
     * committed set, committed delays, rejections — and re-runs the WHOLE
     * incremental schedule from scratch on the now-full fleet. Every order is
     * re-evaluated from its ORIGINAL time (no carried-over single-plant delays);
     * the delay search re-derives any delays needed on the two-plant fleet.
     *
     * Expansion flips consolidated_single_plant to false permanently (on both
     * $scheduleData and $pristine), so the restarted pass can never expand again
     * → this loop runs at most twice. Trip generation is plant-independent, so
     * $allTrips is reused as-is; "fresh" means the scheduling decisions restart,
     * not the trip timetable.
     *
     * @return array{committed:\Illuminate\Support\Collection, rejected:array}
     */
    private function runIncrementalSchedule(ScheduleData &$scheduleData, $allOrders, array $allTrips, array $pristine): array
    {
        while (true) {
            $committed = collect();   // orders that schedule cleanly together so far
            $rejected  = [];          // order_id => reason

            // order_no => accepted delay in minutes. Reset on every (re)start so a
            // restart on the full fleet carries NO single-plant delays.
            $committedDelays = [];

            // Set true the instant any attempt opens the full fleet, so we abandon
            // this pass and restart everything fresh on the expanded fleet.
            $restart = false;

            foreach ($allOrders as $candidate) {

                // Test set = everything accepted so far + this candidate.
                $testOrders   = $committed->concat([$candidate])->values();
                $testOrderNos = $testOrders->pluck('order_no')->all();

                // Effective ceiling on how far we may delay this candidate's start.
                $candidateMaxDelay = (int) ($candidate->max_delay ?? 0);
                $maxDelay = ($candidateMaxDelay > 0)
                    ? min(self::INCR_MAX_DELAY_MINUTES, $candidateMaxDelay)
                    : self::INCR_MAX_DELAY_MINUTES;

                Log::info("[INCR_SCHED] testing candidate {$candidate->order_no} "
                    . "(LPI {$candidate->lpi_score}) with committed=["
                    . $committed->pluck('order_no')->implode(',') . "]");

                // ── Attempt 0: no delay ──────────────────────────────────────
                $res   = $this->scheduleCandidateAtDelay(
                    $scheduleData, $pristine, $allTrips, $testOrders, $testOrderNos,
                    $candidate, $committedDelays, 0
                );
                if ($res['expanded']) { $restart = true; break; }
                $scope = $res['expanded'] ? "even after expanding to all plants" : "on the available plant(s)";

                if ($res['candidateRejected']) {
                    $rejected[$candidate->id] = $res['candidateReason']
                        ?? "This order could not be scheduled because all available plants are at full capacity.";
                    Log::info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — appeared in rejectedOrders {$scope} "
                        . "— a delay cannot help; committed set unchanged");
                    continue;
                }
                if (empty($res['partials'])) {
                    $committed = $testOrders;
                    Log::info("[INCR_SCHED] ✓ {$candidate->order_no} accepted "
                        . "(committed now " . $committed->count() . " order(s))");
                    continue;
                }

                // ── Predict a working delay: geometric probe, then binary refine.
                Log::info("[INCR_DELAY] candidate {$candidate->order_no} caused "
                    . count($res['partials']) . " partial order(s) {$scope} at +0 min — "
                    . "predicting a working delay (search up to +{$maxDelay} min).");

                $probes     = 0;
                $lastFail   = 0;
                $okDelay    = null;
                $rejectedAt = null;
                $step       = max(1, self::INCR_DELAY_PROBE_BASE_MINS);
                $delay      = $step;

                // PROBE
                while ($delay <= 480 && $probes < self::INCR_DELAY_MAX_PROBES) {
                    $probes++;
                    $r = $this->scheduleCandidateAtDelay(
                        $scheduleData, $pristine, $allTrips, $testOrders, $testOrderNos,
                        $candidate, $committedDelays, $delay
                    );
                    if ($r['expanded']) { $restart = true; break; }
                    if ($r['candidateRejected']) {
                        Log::info("[INCR_DELAY] probe +{$delay} min → candidate hard-rejected; "
                            . "further delay cannot help. Stopping probe.");
                        $rejectedAt = $r;
                        break;
                    }
                    if (empty($r['partials'])) {
                        $okDelay = $delay;
                        Log::info("[INCR_DELAY] probe +{$delay} min → clean (0 partials); "
                            . "bracketed a working delay, refining toward the minimum.");
                        break;
                    }
                    Log::info("[INCR_DELAY] probe +{$delay} min → still "
                        . count($r['partials']) . " partial(s); growing step.");
                    $lastFail = $delay;
                    $delay   += $step;
                    $step    *= 2;
                }
                if ($restart) break;

                // Geometric growth may have jumped past the cap without testing it.
                if ($okDelay === null && $rejectedAt === null
                    && $lastFail < $maxDelay && $probes < self::INCR_DELAY_MAX_PROBES) {
                    $probes++;
                    $r = $this->scheduleCandidateAtDelay(
                        $scheduleData, $pristine, $allTrips, $testOrders, $testOrderNos,
                        $candidate, $committedDelays, $maxDelay
                    );
                    if ($r['expanded']) {
                        $restart = true;
                    } elseif ($r['candidateRejected']) {
                        $rejectedAt = $r;
                    } elseif (empty($r['partials'])) {
                        $okDelay = $maxDelay;
                        Log::info("[INCR_DELAY] probe +{$maxDelay} min (cap) → clean (0 partials).");
                    } else {
                        $lastFail = $maxDelay;
                    }
                }
                if ($restart) break;

                if ($rejectedAt !== null) {
                    $rejected[$candidate->id] = $rejectedAt['candidateReason']
                        ?? "This order could not be scheduled because all available plants are at full capacity.";
                    Log::info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — appeared in rejectedOrders "
                        . "while probing delays ({$probes} probe(s)); committed set unchanged");
                    continue;
                }
                if ($okDelay === null) {
                    $rejected[$candidate->id] =
                        "This order could not be scheduled because all available plants are at full capacity.";
                    Log::info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — no delay up to "
                        . "+{$maxDelay} min cleared the partials ({$probes} probe(s)); committed set unchanged");
                    continue;
                }

                // REFINE
                $lo     = $lastFail;
                $hi     = $okDelay;
                $best   = $okDelay;
                $refine = 0;
                while (($hi - $lo) > 1 && $refine < self::INCR_DELAY_MAX_REFINE) {
                    $refine++;
                    $mid = intdiv($lo + $hi, 2);
                    $r = $this->scheduleCandidateAtDelay(
                        $scheduleData, $pristine, $allTrips, $testOrders, $testOrderNos,
                        $candidate, $committedDelays, $mid
                    );
                    if ($r['expanded']) { $restart = true; break; }
                    if (!$r['candidateRejected'] && empty($r['partials'])) {
                        $best = $mid;
                        $hi   = $mid;
                        Log::info("[INCR_DELAY] refine +{$mid} min → clean; trying smaller.");
                    } else {
                        $lo = $mid;
                        Log::info("[INCR_DELAY] refine +{$mid} min → not clean; trying larger.");
                    }
                }
                if ($restart) break;

                $committed = $testOrders;
                $committedDelays[$candidate->order_no] = $best;
                Log::info("[INCR_SCHED] ✓ {$candidate->order_no} accepted with predicted +{$best} min delay "
                    . "(" . ($probes + $refine + 1) . " scheduling pass(es); +1-min stepping would have taken up "
                    . "to " . ($best + 1) . "); committed now " . $committed->count() . " order(s)");
            }

            // Plant 2 opened mid-pass → throw away this pass and start over fresh
            // on the full fleet. Nothing has been committed to the DB yet for this
            // attempt that the next pass won't clear.
            if ($restart) {
                Log::info("[PLANT_EXPAND] full fleet opened mid-pass — discarding the single-plant "
                    . "decisions and re-scheduling ALL orders from scratch on the full fleet "
                    . "(fresh start: original times, no carried-over delays).");
                continue;
            }

            // ── Final canonical pass: rebuild DB with ONLY the committed set. ──
            $this->restorePools($scheduleData, $pristine);
            $this->clearPreviousSchedules(
                $scheduleData->company,
                $scheduleData->user_id,
                $scheduleData->shift_start,
                $scheduleData->shift_end
            );
            if ($committed->isNotEmpty()) {
                $committedNos   = $committed->pluck('order_no')->all();
                $committedTrips = $this->buildTripsWithDelays($allTrips, $committedNos, $committedDelays);
                Log::info("[INCR_SCHED] final pass scheduling " . $committed->count() . " committed order(s)"
                    . (!empty($committedDelays)
                        ? " (delays applied: " . $this->describeDelays($committedDelays, $committedNos) . ")"
                        : ""));
                $this->scheduleTripsChronologically($scheduleData, $committedTrips, $committed);
            }

            return ['committed' => $committed, 'rejected' => $rejected];
        }
    }

    /**
     * Run ONE scheduling attempt for a candidate at a given start-delay and
     * report what happened. Committed orders carry their already-accepted
     * delays; only the candidate is shifted by $delayMinutes. Restores pools,
     * clears previous schedules, runs the chronological scheduler, then detects
     * partials and hard rejections.
     *
     * Single-plant → full-fleet expansion: if a HARD rejection remains while
     * consolidated on one plant and a second distinct plant exists, it opens the
     * full fleet (mutating $scheduleData and $pristine by reference) and returns
     * expanded=true. The caller (runIncrementalSchedule) then restarts the whole
     * pass from scratch, so this method does NOT reschedule after expanding.
     *
     * @return array{partials:array, rejectedOrders:array, candidateRejected:bool, candidateReason:?string, expanded:bool}
     */
    private function scheduleCandidateAtDelay(
        ScheduleData &$scheduleData,
        array &$pristine,
        array $allTrips,
        $testOrders,
        array $testOrderNos,
        $candidate,
        array $committedDelays,
        int $delayMinutes
    ): array {
        $delaysByOrderNo = $committedDelays;
        $delaysByOrderNo[$candidate->order_no] = $delayMinutes;
        $testTrips = $this->buildTripsWithDelays($allTrips, $testOrderNos, $delaysByOrderNo);

        $this->restorePools($scheduleData, $pristine);
        $this->clearPreviousSchedules(
            $scheduleData->company,
            $scheduleData->user_id,
            $scheduleData->shift_start,
            $scheduleData->shift_end
        );
        $this->scheduleTripsChronologically($scheduleData, $testTrips, $testOrders);
        $partials       = $this->detectPartialOrders($scheduleData, $testOrders);
        $rejectedOrders = $this->detectRejectedOrders($scheduleData, $testOrders);

        // ── Plant expansion (single-plant mode only) ─────────────────────────
        // Trigger on a HARD rejection. NOTE: count distinct PLANT NAMES, not
        // array length — bps_availability fragments into many free-window slots
        // for the SAME plant during scheduling, so a raw count() would mislead.
        $expanded = false;
        if (!empty($rejectedOrders) && $scheduleData->consolidated_single_plant) {
            $distinctCurrent = count(array_unique(array_column($scheduleData->bps_availability ?? [], 'plant_name')));
            $distinctFull    = count(array_unique(array_column($scheduleData->bps_availability_full ?? [], 'plant_name')));

            if ($distinctFull > $distinctCurrent) {
                Log::info("[PLANT_EXPAND] candidate {$candidate->order_no} hit a hard rejection on the single "
                    . "plant — opening the full fleet ({$distinctCurrent} → {$distinctFull} plant(s)); the run "
                    . "will restart fresh on all plants.");

                // Open the full fleet on BOTH the live state and the baseline so
                // the change persists across the restart.
                $scheduleData->bps_availability          = $scheduleData->bps_availability_full;
                $scheduleData->consolidated_single_plant = false;
                $pristine['bps_availability']            = $scheduleData->bps_availability_full;
                $expanded = true;
                // Do NOT reschedule here — the caller restarts the whole pass.
            } else {
                Log::warning("[PLANT_EXPAND] candidate {$candidate->order_no} hit a hard rejection, but only "
                    . "{$distinctFull} distinct plant(s) are available for this run — cannot split. "
                    . "Check plant selection / location / location_shift rows.");
            }
        }

        $candidateRejected = false;
        $candidateReason   = null;
        foreach ($rejectedOrders as $r) {
            if ($r['id'] === $candidate->id) {
                $candidateRejected = true;
                $candidateReason   = $r['reason'] ?? null;
                break;
            }
        }

        return [
            'partials'          => $partials,
            'rejectedOrders'    => $rejectedOrders,
            'candidateRejected' => $candidateRejected,
            'candidateReason'   => $candidateReason,
            'expanded'          => $expanded,
        ];
    }

    /**
     * Build the trip list for a set of orders, applying a per-order delay (in
     * minutes) to every trip of each order. Delaying shifts ALL of an order's
     * trips by the same amount, so their relative spacing is preserved. The
     * canonical $allTrips is never mutated: shiftTripBy() rebuilds each
     * timestamp via Carbon::copy(), and trips with zero delay are passed through
     * untouched.
     *
     * @param array  $allTrips         canonical, un-delayed trips for every order
     * @param array  $orderNos         order_no values to include
     * @param array  $delaysByOrderNo  order_no => delay minutes (missing = 0)
     */
    private function buildTripsWithDelays(array $allTrips, array $orderNos, array $delaysByOrderNo): array
    {
        $trips = [];
        foreach ($allTrips as $trip) {
            if (!in_array($trip['order_no'], $orderNos, true)) {
                continue;
            }
            $delay = (int) ($delaysByOrderNo[$trip['order_no']] ?? 0);
            if ($delay > 0) {
                // $trip is a foreach value copy; shiftTripBy reassigns each
                // timestamp via ->copy(), so $allTrips stays pristine.
                $this->shiftTripBy($trip, $delay);
            }
            $trips[] = $trip;
        }
        // Delaying a candidate may move its trips past committed ones; re-sort
        // so the chronological scheduler sees the correct global ordering.
        usort($trips, function ($a, $b) {
            $aStart = $a['loading_start'] instanceof Carbon ? $a['loading_start'] : Carbon::parse($a['loading_start']);
            $bStart = $b['loading_start'] instanceof Carbon ? $b['loading_start'] : Carbon::parse($b['loading_start']);
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }
            return ($a['trip'] ?? 1) <=> ($b['trip'] ?? 1);
        });
        return $trips;
    }

    /** Human-readable "ORD1+2min, ORD2+5min" summary for logging delays. */
    private function describeDelays(array $delaysByOrderNo, array $orderNos): string
    {
        $parts = [];
        foreach ($orderNos as $no) {
            $d = (int) ($delaysByOrderNo[$no] ?? 0);
            if ($d > 0) {
                $parts[] = "{$no}+{$d}min";
            }
        }
        return empty($parts) ? "none" : implode(', ', $parts);
    }
    private function snapshotPools(ScheduleData $scheduleData): array
    {
        return [
            'tms_availability'      => $scheduleData->tms_availability,
            'bps_availability'      => $scheduleData->bps_availability,
            'pumps_availability'    => $scheduleData->pumps_availability,
            'plant_busy_slots'      => $scheduleData->plant_busy_slots,
            'truck_busy_slots'      => $scheduleData->truck_busy_slots,
            'pump_busy_slots'       => $scheduleData->pump_busy_slots,
            'pump_busy_slots_unset' => $scheduleData->pump_busy_slots_unset,
            'assigned_plants'       => $scheduleData->assigned_plants,
            'assigned_tms'          => $scheduleData->assigned_tms,
            'assigned_pumps'        => $scheduleData->assigned_pumps,
            'schedules'             => $scheduleData->schedules,
            'selected_order_pump_schedules' => $scheduleData->selected_order_pump_schedules,
            'orders_copy'           => $scheduleData->orders_copy,
        ];
    }
    private function restorePools(ScheduleData &$scheduleData, array $pristine): void
    {
        $scheduleData->tms_availability      = $pristine['tms_availability'];
        $scheduleData->bps_availability      = $pristine['bps_availability'];
        $scheduleData->pumps_availability    = $pristine['pumps_availability'];
        $scheduleData->plant_busy_slots      = $pristine['plant_busy_slots'];
        $scheduleData->truck_busy_slots      = $pristine['truck_busy_slots'];
        $scheduleData->pump_busy_slots       = $pristine['pump_busy_slots'];
        $scheduleData->pump_busy_slots_unset = $pristine['pump_busy_slots_unset'];
        $scheduleData->assigned_plants       = $pristine['assigned_plants'];
        $scheduleData->assigned_tms          = $pristine['assigned_tms'];
        $scheduleData->assigned_pumps        = $pristine['assigned_pumps'];
        $scheduleData->schedules             = $pristine['schedules'];
        $scheduleData->selected_order_pump_schedules = $pristine['selected_order_pump_schedules'];
        $scheduleData->orders_copy           = $pristine['orders_copy'];
    }
    private function detectPartialOrders(ScheduleData $scheduleData, $allOrders): array
    {
        $partials = [];
        $persistedRows = DB::table('selected_orders')
            ->whereIn('id', $allOrders->pluck('id')->toArray())
            ->get(['id', 'order_no', 'quantity', 'delivered_quantity', 'lpi_score']);
        foreach ($persistedRows as $row) {
            $delivered = (int) ($row->delivered_quantity ?? 0);
            $ordered   = (int) ($row->quantity ?? 0);
            // Flag anything that did NOT fully deliver, including delivered == 0.
            // Previously the guard was `$delivered > 0`, which let a completely
            // unscheduled order (e.g. a pump order that failed assignPump and was
            // reset to delivered=0) slip past as "not a partial" — so the
            // incremental loop logged "✓ accepted" for an order that the final
            // output then showed as "Not Scheduled". `$ordered > 0` keeps rows
            // with no real quantity from being spuriously flagged.
            if ($ordered > 0 && $delivered < $ordered) {
                $partials[] = [
                    'id'        => $row->id,
                    'order_no'  => $row->order_no,
                    'delivered' => $delivered,
                    'quantity'  => $ordered,
                    'lpi_score' => (float) ($row->lpi_score ?? 0),
                    'reason'    => "Order rejected — partial scheduling not allowed. "
                        . "Engine could only deliver {$delivered} of {$ordered} m³ "
                        . "before resources ran out. Order excluded so others can be scheduled cleanly.",
                ];
            }
        }
        return $partials;
    }

    /**
     * Orders the scheduler HARD-rejected on the current pass — failures a
     * +1-minute candidate delay genuinely cannot fix. These are a strict subset
     * of partials: the order carries a failure_reason whose text matches one of
     * self::HARD_REJECT_REASON_MARKERS (e.g. "No available pump found", a real
     * resource denial) AND delivered nothing.
     *
     * TIMING failures are deliberately EXCLUDED here so they remain partials and
     * drive the delay-retry loop instead of triggering an immediate reject:
     *   • "Delay limit exceeded ..."  (DELAY_LIMIT_CHECK)
     *   • "Supply rate dropped ... below 75% minimum"
     *   • "Order could not be fully scheduled — partial scheduling is not allowed"
     *   • "Could not schedule within constraints"
     * All of these are resolvable (or at least worth retrying) by nudging the
     * candidate's start, which is exactly what the incremental loop now does.
     *
     * @return array<int,array{id:int,order_no:mixed,delivered:int,quantity:int,lpi_score:float,reason:string}>
     */
    private function detectRejectedOrders(ScheduleData $scheduleData, $allOrders): array
    {
        $rejectedOrders = [];
        $persistedRows = DB::table('selected_orders')
            ->whereIn('id', $allOrders->pluck('id')->toArray())
            ->get(['id', 'order_no', 'quantity', 'delivered_quantity', 'lpi_score', 'failure_reason', 'start_time']);
        foreach ($persistedRows as $row) {
            $delivered = (int) ($row->delivered_quantity ?? 0);
            $ordered   = (int) ($row->quantity ?? 0);
            $reason    = trim((string) ($row->failure_reason ?? ''));
            if ($ordered <= 0 || $reason === '') {
                continue;
            }
            // Only a HARD, delay-proof reason counts as a rejection. A timing
            // failure (delay limit, supply rate, partial-not-allowed) is left
            // for detectPartialOrders() to surface as a retryable partial.
            $isHardReject = false;
            foreach (self::HARD_REJECT_REASON_MARKERS as $marker) {
                if (stripos($reason, $marker) !== false) {
                    $isHardReject = true;
                    break;
                }
            }
            $notStarted = $delivered <= 0 || empty($row->start_time);
            if ($isHardReject && $notStarted) {
                $rejectedOrders[] = [
                    'id'        => (int) $row->id,
                    'order_no'  => $row->order_no,
                    'delivered' => $delivered,
                    'quantity'  => $ordered,
                    'lpi_score' => (float) ($row->lpi_score ?? 0),
                    'reason'    => $reason,
                ];
            }
        }
        return $rejectedOrders;
    }
    private function filterSchedulableOrders(ScheduleData $scheduleData, $allOrders)
    {
        $shiftStart    = Carbon::parse($scheduleData->shift_start);
        $shiftEnd      = Carbon::parse($scheduleData->shift_end);
        $shiftMinutes  = 4800;
        $totalTrucks   = count($scheduleData->tms_availability ?? []);
        $totalPlants   = count($scheduleData->bps_availability ?? []);
        $totalPumps    = count($scheduleData->pumps_availability ?? []);
        $feasible  = collect();
        $rejected  = [];
        foreach ($allOrders as $order) {
            $feasible->push($order);
        }
        if ($feasible->isNotEmpty() && $totalPlants > 0) {
            $plantCapResult = $this->simulatePlantAssignment($scheduleData, $feasible, $shiftMinutes);
            foreach ($plantCapResult['rejected'] as $orderId => $reason) {
                $rejected[$orderId] = $reason;
            }
            $feasible = $plantCapResult['accepted'];
        }
        foreach ($rejected as $orderId => $reason) {
            try {
                DB::table('selected_orders')
                    ->where('id', $orderId)
                    ->update([
                        'failure_reason' => "This order could not be scheduled because all available plants are at full capacity.",
                    ]);
            } catch (\Throwable $e) {
                Log::warning("Could not save rejection reason for order ID {$orderId}: " . $e->getMessage());
            }
        }
        Log::info("Feasibility check complete: " . $feasible->count() . " orders accepted, " . count($rejected) . " rejected out of " . $allOrders->count() . " total.");
        return $feasible;
    }
    private function generateAllOrderTrips(ScheduleData $scheduleData, $allOrders): array
    {
        $allTrips = [];
        $orderIndex = 0;
        foreach ($allOrders as $order) {
            $truckCapacity = self::DEFAULT_TRUCK_CAPACITY;
            $loadingTime   = $order->loading_time   ?? ConstantHelper::LOADING_TIME;
            $pouringTime   = $order->pouring_time   ?? 0;
            $travelTime    = $order->travel_to_site ?? 0;
            $returnTime    = $order->return_to_plant ?? 0;
            $interval = max($order->pouring_time,$order->loading_time);
            $qcTime        = $scheduleData->qc_time;
            $inspTime      = $scheduleData->insp_time;
            $cleaningTime  = $scheduleData->cleaning_time;
            $location      = $order->location;
            $remainingQty  = $order->quantity;
            $totalTrips    = (int) ceil($order->quantity / max(1, $truckCapacity));
            $previousLoadingStart = null;
            $previousPouringEnd = null;

            // ── Per-trip tolerance (for plant clash resolution) ───────────
            // From Interval_Tolerance doc: tolerance_minutes = base_interval × (tolerance% / 100)
            // This is the max a single trip can be shifted to avoid plant clash
            $tolerancePercent = (float) ($order->tolerance ?? 10);
            $tripToleranceMins = (int) ceil($interval * ($tolerancePercent / 100));

            for ($trip = 1; $trip <= $totalTrips; $trip++) {
                $batchQty        = min($truckCapacity, $remainingQty);
                $tripLoadingTime = $loadingTime;
                $tripPouringTime = $pouringTime;
                if ($batchQty < self::DEFAULT_TRUCK_CAPACITY) {
                    $tripLoadingTime = (int) round(($loadingTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
                    $tripPouringTime = (int) round(($pouringTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
                }
                $totalTimeMinsTrip = $tripLoadingTime + $qcTime + $travelTime + $inspTime + 4;
                $trip1Loading  = Carbon::parse($order->delivery_date)->subMinutes($totalTimeMinsTrip);
                if ($trip === 1) {
                    $loadingStart = $trip1Loading->copy();
                } else {
                    $loadingStart = $previousLoadingStart->copy()->addMinutes($interval);
                }
                $loadingEnd    = $loadingStart->copy()->addMinutes($tripLoadingTime);
                $qcStart       = $loadingEnd->copy()->addMinute();
                $qcEnd         = $qcStart->copy()->addMinutes($qcTime);
                $travelStart   = $qcEnd->copy()->addMinute();
                $travelEnd     = $travelStart->copy()->addMinutes($travelTime);
                $inspStart     = $travelEnd->copy()->addMinute();
                $inspEnd       = $inspStart->copy()->addMinutes($inspTime);

                // ── Waiting step for pump orders trip 2+ ─────────────────
                // Client doc: Waiting Start = Inspection End + 1 min
                //             Waiting End   = Previous Trip Pouring End - buffer
                //             Truck waits until pump becomes free from previous trip
                $waitingStart = null;
                $waitingEnd   = null;
                $waitingTime  = 0;
                if ($order->pump && $trip > 1 && $previousPouringEnd) {
                    $truckReady = $inspEnd->copy()->addMinute();
                    if ($truckReady->lt($previousPouringEnd)) {
                        // Truck arrived before pump is free — add waiting
                        $waitingStart = $truckReady->copy();
                        $waitingEnd   = $previousPouringEnd->copy()->subMinute();
                        $waitingTime  = max(0, (int) $waitingStart->diffInMinutes($waitingEnd));
                        $pouringStart = $previousPouringEnd->copy()->addMinute();
                    } else {
                        // Truck arrived after pump is free — no waiting needed
                        $pouringStart = $truckReady->copy();
                    }
                } else {
                    $pouringStart = $inspEnd->copy()->addMinute();
                }

                $pouringEnd    = $pouringStart->copy()->addMinutes($tripPouringTime);
                $cleaningStart = $pouringEnd->copy()->addMinute();
                $cleaningEnd   = $cleaningStart->copy()->addMinutes($cleaningTime);
                $returnStart   = $cleaningEnd->copy()->addMinute();
                $returnEnd     = $returnStart->copy()->addMinutes($returnTime);
                $previousLoadingStart = $loadingStart->copy();
                $previousPouringEnd = $pouringEnd->copy();
                $allTrips[] = [
                    'order_sequence' => $orderIndex,
                    'order_quantity' => $order->quantity,
                    'order_id'       => $order->id,
                    'order_no'       => $order->order_no,
                    'order_lpi_score' => $order->lpi_score,
                    'order_priority' => $order->priority,
                    'order_pump'     => (bool) $order->pump,
                    'trip'           => $trip,
                    'total_trips'    => $totalTrips,
                    'batching_qty'   => $batchQty,
                    'location'       => $location,
                    'loading_start'  => $loadingStart,
                    'loading_end'    => $loadingEnd,
                    'qc_start'       => $qcStart,
                    'qc_end'         => $qcEnd,
                    'travel_start'   => $travelStart,
                    'travel_end'     => $travelEnd,
                    'insp_start'     => $inspStart,
                    'insp_end'       => $inspEnd,
                    'waiting_start'  => $waitingStart,
                    'waiting_end'    => $waitingEnd,
                    'waiting_time'   => $waitingTime,
                    'pouring_start'  => $pouringStart,
                    'pouring_end'    => $pouringEnd,
                    'cleaning_start' => $cleaningStart,
                    'cleaning_end'   => $cleaningEnd,
                    'return_start'   => $returnStart,
                    'return_end'     => $returnEnd,
                    'loading_time'   => $tripLoadingTime,
                    'pouring_time'   => $tripPouringTime,
                    'qc_time'        => $qcTime,
                    'insp_time'      => $inspTime,
                    'cleaning_time'  => $cleaningTime,
                    'interval'       => $interval,
                    'flexibility'     => $order->flexibility,
                    'max_delay_minutes' => $order->max_delay,
                    'base_loading_time' => $loadingTime,
                    'base_pouring_time' => $pouringTime,
                    'travel_time_mins'  => $travelTime,
                    'return_time_mins'  => $returnTime,
                    'tolerance'         => $order->tolerance,
                    'tolerance_minutes' => $tripToleranceMins,
                    'expected_duration_minutes' => $order->expected_duration
                ];
                $remainingQty -= $batchQty;
                $orderIndex++;
            }
        }
        usort($allTrips, function ($a, $b) {

            $aStart = Carbon::parse($a['loading_start']);
            $bStart = Carbon::parse($b['loading_start']);
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }
            if ($a['trip'] !== $b['trip']) {
                return ($a['trip'] ?? 1) <=> ($b['trip'] ?? 1);
            }



            if ($a['order_lpi_score'] !== $b['order_lpi_score']) {
                return $b['order_lpi_score'] <=> $a['order_lpi_score'];
            }
        });

        return $allTrips;
    }
    private const PLANT_COUNT = 2;

    private function resolvePlantClashes(array $allTrips, int $plantCount = self::PLANT_COUNT): array
    {
        // when each plant next becomes free (its last trip's loading_end)
        $plantFreeAt = array_fill(0, $plantCount, null);

        foreach ($allTrips as &$trip) {
            $loadingStart  = Carbon::parse($trip['loading_start']);
            $toleranceMins = (int) ($trip['tolerance_minutes'] ?? 0);

            // 1) is any plant already free at this loading_start?
            $assignedPlant     = null;
            $earliestFree      = null;
            $earliestFreePlant = null;

            foreach ($plantFreeAt as $p => $freeAt) {
                if ($freeAt === null || $freeAt->lte($loadingStart)) {
                    $assignedPlant = $p;            // free now → no clash
                    break;
                }
                if ($earliestFree === null || $freeAt->lt($earliestFree)) {
                    $earliestFree      = $freeAt->copy();
                    $earliestFreePlant = $p;        // soonest-freeing plant
                }
            }

            // 2) clash: all plants busy → shift this trip later, within tolerance
            if ($assignedPlant === null) {
                $shiftMins     = (int) ceil(abs($loadingStart->diffInMinutes($earliestFree)));
                $appliedShift  = min($shiftMins, $toleranceMins);

                $trip['plant_clash_shift'] = $appliedShift;
                if ($shiftMins > $toleranceMins) {
                    // couldn't fully clear the clash within tolerance — flag it
                    $trip['plant_clash_unresolved'] = true;
                }

                $this->shiftTripBy($trip, $appliedShift);
                $assignedPlant = $earliestFreePlant;
            }

            $trip['plant'] = $assignedPlant + 1; // 1-indexed for display
            $plantFreeAt[$assignedPlant] = Carbon::parse($trip['loading_end']);
        }
        unset($trip);

        // re-sort since some trips moved later
        usort($allTrips, function ($a, $b) {
            $aStart = Carbon::parse($a['loading_start']);
            $bStart = Carbon::parse($b['loading_start']);
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }
            return $b['order_lpi_score'] <=> $a['order_lpi_score'];
        });

        return $allTrips;
    }

    /** Shift every timestamp of a trip forward by N minutes (keeps the chain intact). */
    private function shiftTripBy(array &$trip, int $minutes): void
    {
        if ($minutes <= 0) {
            return;
        }
        $fields = [
            'loading_start',
            'loading_end',
            'qc_start',
            'qc_end',
            'travel_start',
            'travel_end',
            'insp_start',
            'insp_end',
            'waiting_start',
            'waiting_end',
            'pouring_start',
            'pouring_end',
            'cleaning_start',
            'cleaning_end',
            'return_start',
            'return_end',
        ];
        foreach ($fields as $f) {
            if (empty($trip[$f])) {
                continue; // waiting_* may be null
            }
            $trip[$f] = $trip[$f] instanceof Carbon
                ? $trip[$f]->copy()->addMinutes($minutes)
                : Carbon::parse($trip[$f])->addMinutes($minutes);
        }
    }
    private function scheduleTripsChronologically(ScheduleData &$scheduleData, array $sortedTrips, $allOrders): void
    {
        $orderMap       = $allOrders->keyBy('order_no');
        // Total ordered quantity for the whole run — drives the plant
        // distribute-vs-consolidate strategy in predictBestPlant().
        $totalOrdersQty = (int) $allOrders->sum('quantity');
        $orderSchedules = [];
        $orderDelivered = [];
        $orderFailed    = [];
        $orderCumulativeDelay  = [];
        $bestPlantPerOrderTrip = [];
        $pumpAssigned          = [];
        $orderRemainingQty     = [];
        $orderCompleted        = [];
        $orderTripCounter      = [];
        $orderFailSkipLogged   = [];
        $orderFirstPouringStart = [];  // Track 1st trip actual pouring_start per order

        // ── Pump contention by priority (added) ───────────────────────────
        // Pre-claim the limited pumps for pump orders in LPI-descending order
        // BEFORE the chronological loop. The loop's existing trip-1 logic then
        // (a) skips re-checking for any order already holding a reservation
        //     → higher-LPI orders keep their natural loading_start, and
        // (b) delays any order whose pump is busy
        //     → lower-LPI orders are pushed back instead of higher-LPI ones.
        // No change to sorting or the scheduling loop itself.
        $pumpLosers = $this->reservePumpsByPriority($scheduleData, $sortedTrips, $allOrders);

        // ── Process pump-contention losers AFTER their winners ─────────────
        // A losing pump order is parked at the winner's pump-release time. If we
        // process it at its natural (early) position, the winner's LAST trip
        // hasn't run yet, so its reservation still holds the IDEALISED end and
        // the loser must guess the winner's real slip (hence the residual gap).
        // By moving every loser's trips to the end of the processing order, the
        // winner is fully scheduled first, its reservation end is corrected to
        // the REAL return_end, and the loser keys off that exact time — closing
        // the gap to the minimal hand-off regardless of how much the winner
        // slipped. Stable partition preserves relative order within each group.
        if (!empty($pumpLosers)) {
            $loserSet   = array_flip($pumpLosers);
            $winnerTrips = [];
            $loserTrips  = [];
            foreach ($sortedTrips as $t) {
                if (isset($loserSet[$t['order_no']])) {
                    $loserTrips[] = $t;
                } else {
                    $winnerTrips[] = $t;
                }
            }
            $sortedTrips = array_merge($winnerTrips, $loserTrips);
            Log::info("[PUMP_PRIORITY] reordered " . count($loserTrips)
                . " trip(s) of " . count($pumpLosers) . " losing pump order(s) ["
                . implode(',', $pumpLosers) . "] to run after their winners");
        }

        foreach ($sortedTrips as $tripData) {
            $orderNo = $tripData['order_no'];
            $order   = $orderMap[$orderNo] ?? null;
            if (!$order) continue;
            if (!isset($orderRemainingQty[$orderNo])) {
                $orderRemainingQty[$orderNo] = (int) $order->quantity;
            }
            if (!empty($orderCompleted[$orderNo])) {
                continue;
            }
            if (isset($orderFailed[$orderNo])) {
                if (!isset($orderFailSkipLogged[$orderNo])) {
                    $orderFailSkipLogged[$orderNo] = true;
                }
                continue;
            }

            // ── Retry cap per trip ───────────────────────────────────────
            // Trip 1: can shift up to max_delay to find initial slot
            //
            // Trip 2+ FLEXIBLE: use delay-limit budget instead of maxInterval.
            //   Budget = (expected_duration + max_delay) − elapsed so far.
            //   "elapsed" = how far we already are from first pouring_start.
            //   This lets flexible orders shift freely per-trip as long as
            //   the OVERALL order stays within the delay limit.
            //
            // Trip 2+ NON-FLEXIBLE: strict per-trip tolerance from document
            if ($tripData['trip'] === 1) {
                $maxRetryMinutes = 480;
            } else {

                // Delay-limit budget: how many minutes of room remain
                $expectedDuration = (int) ($tripData['expected_duration_minutes'] ?? 0);
                $delayLimit       = $expectedDuration + (int) ($order->max_delay ?? 30);
                $maxRetryMinutes = 45;

                // $firstPS = $orderFirstPouringStart[$orderNo] ?? null;
                // if ($firstPS) {
                //     // Use SHIFTED pouring_end (account for prior cumulative delay)
                //     $priorShift = $orderCumulativeDelay[$orderNo] ?? 0;
                //     $shiftedPE  = Carbon::parse($tripData['pouring_end'])->addMinutes($priorShift);
                //     $elapsedSoFar = (int) $firstPS->diffInMinutes($shiftedPE);
                //     $maxRetryMinutes = max(0, $delayLimit - $elapsedSoFar);
                // }
            }
            $location   = $tripData['location'];
            $totalTrips = $tripData['total_trips'];
            $priorDelay = $orderCumulativeDelay[$orderNo] ?? 0;
            if ($priorDelay > 0) {
                $tripData = $this->shiftTripByMinutes($tripData, $priorDelay, $order, $scheduleData);
            }
            $tripScheduled = false;
            $retryOffset   = 0;

            while ($retryOffset <= $maxRetryMinutes) {
                $currentTrip = ($retryOffset === 0)
                    ? $tripData
                    : $this->shiftTripByMinutes($tripData, $retryOffset, $order, $scheduleData);

                $scheduleData->order_no       = $orderNo;
                $scheduleData->location       = $location;
                $scheduleData->trip           = $currentTrip['trip'];
                //$scheduleData->assigned_plant = $bestPlantPerOrderTrip[$orderNo] ?? null;
                $plant = $bestPlantPerOrderTrip[$orderNo] ?? null;
                $scheduleData->assigned_plants = $plant !== null ? [$plant] : [];
                $scheduleData->qc_time        = $currentTrip['qc_time'];
                $scheduleData->insp_time      = $currentTrip['insp_time'];
                $scheduleData->cleaning_time  = $currentTrip['cleaning_time'];
                $this->applyTripToScheduleData($scheduleData, $currentTrip);
                if ($order->pump && $scheduleData->trip === 1) {
                    $alreadyReserved = false;
                    foreach ($scheduleData->pump_busy_slots_unset as $pbs) {
                        if (($pbs['order_no'] ?? null) === $orderNo) {
                            $alreadyReserved = true;
                            break;
                        }
                    }
                    if (!$alreadyReserved) {
                        $delay      = $this->getNextFreePumpSlot($scheduleData, $order, $scheduleData->pouring_start);
                        $delay_time = $delay['delay_minutes'];
                        if ($delay_time > 0) {
                            $pumpAssigned[$orderNo] = false;
                            if ($retryOffset + $delay_time > $maxRetryMinutes) {
                                $orderFailed[$orderNo] = "Pump unavailable. With  in Max allowed delay time: " .
                                    Carbon::parse($order->delivery_date)->addMinutes($maxRetryMinutes)->format('h:i A');
                                break;
                            }
                            $reason = "Pump not found for order {$order->order_no}";
                            $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                            if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                                // BatchingPlantAvailability::create([
                                //     'group_company_id' => $scheduleData->company,
                                //     'location' => $scheduleData->location,
                                //     'plant_name' => $scheduleData->batching_plant['data']['plant_name'],
                                //     'plant_capacity' => 0,
                                //     'free_from' => $scheduleData->loading_start->copy()->format('Y-m-d H:i:s'),
                                //     'free_upto' => $scheduleData->loading_start->copy()->addMinutes($delay_time)->format('Y-m-d H:i:s'),
                                //     'user_id' => $scheduleData->user_id,
                                //     'reason' => $reason,
                                // ]);
                            }
                            $retryOffset += $delay_time;
                            continue;
                        }
                        $pumpAssigned[$orderNo] = true;
                        $lastTripReturnEnd = collect($sortedTrips)
                            ->filter(fn($t) => $t['order_no'] === $orderNo)
                            ->sortByDesc('return_end')
                            ->first()['return_end'] ?? null;
                        $scheduleData->pump_busy_slots_unset[] = [
                            'start'    => $delay['pump_qc_start'],
                            'end'      => $lastTripReturnEnd
                                ? Carbon::parse($lastTripReturnEnd)->copy()->addMinute()
                                : $delay['pouring_start'],
                            'truck_id' => null,
                            'cap'      => null,
                            'order_no' => $orderNo,
                            'pump_id'  => $delay['pump_id'],
                        ];
                    }
                    $pumpAssigned[$orderNo] = true;
                }
                if ($order->pump && $scheduleData->trip > 1 && empty($pumpAssigned[$orderNo])) {
                    $retryOffset++;
                    continue;
                }
                $this->assignTransitMixer(
                    $scheduleData,
                    $location,
                    $currentTrip['trip'],
                    $currentTrip['batching_qty'],
                    $order
                );
                if (!isset($scheduleData->transit_mixer['data']['truck_name'])) {
                    $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                    $reason = "Mixer not found for order {$order->order_no}";
                    if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                        BatchingPlantAvailability::create([
                            'group_company_id' => $scheduleData->company,
                            'location' => $scheduleData->location,
                            'plant_name' => $scheduleData->batching_plant['data']['plant_name'],
                            'plant_capacity' => 0,
                            'free_from' => $scheduleData->loading_start->copy()->format('Y-m-d H:i:s'),
                            'free_upto' => $scheduleData->loading_start->copy()->format('Y-m-d H:i:s'),
                            'user_id' => $scheduleData->user_id,
                            'reason' => $reason,
                        ]);
                    }
                    $nextTruckFree = $this->nextFreeMinutes($scheduleData->tms_availability, $currentTrip['loading_start']);
                    $retryOffset += max(1, $nextTruckFree);
                    continue;
                }
                // ── Adjust batching_qty to actual truck capacity (times unchanged) ─
                $actualTruckCap    = (int) ($scheduleData->transit_mixer['data']['truck_capacity'] ?? self::DEFAULT_TRUCK_CAPACITY);
                $remainingForOrder = $orderRemainingQty[$orderNo];
                $actualBatchQty    = min($actualTruckCap, max(0, $remainingForOrder));
                $currentTrip['batching_qty'] = $actualBatchQty;
                $scheduleData->batching_qty  = $actualBatchQty;

                if ($remainingForOrder <= 0) {
                    $orderCompleted[$orderNo] = true;
                    $tripScheduled = true;
                    break;
                }
                if ($actualBatchQty <= 0) {
                    $orderCompleted[$orderNo] = true;
                    $tripScheduled = true;
                    break;
                }
                $truckName = $scheduleData->transit_mixer['data']['truck_name'];
                $newLSts   = $scheduleData->loading_start->timestamp;
                $newREts   = $scheduleData->return_end->timestamp;
                $truckConflict = false;
                foreach ($scheduleData->truck_busy_slots as $busy) {
                    if (($busy['truck_id'] ?? null) !== $truckName) continue;
                    $bsTs = ($busy['start'] instanceof Carbon) ? $busy['start']->timestamp : Carbon::parse($busy['start'])->timestamp;
                    $beTs = ($busy['end'] instanceof Carbon) ? $busy['end']->timestamp : Carbon::parse($busy['end'])->timestamp;
                    if ($newREts > $bsTs && $newLSts < $beTs) {
                        $truckConflict = true;
                        break;
                    }
                }
                if ($truckConflict) {
                    $retryOffset++;
                    continue;
                }
                if ($scheduleData->trip === 1 && $order->base_interval >= $order->loading_time) {
                    $plant = $this->predictBestPlant($scheduleData, $order, $location, $totalOrdersQty);
                    $scheduleData->assigned_plants = [$plant];
                    $bestPlantPerOrderTrip[$orderNo] = $plant;
                }
                $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                if (!isset($scheduleData->batching_plant['data']['plant_name'])) {
                    $nextPlantFree = $this->nextFreeMinutes($scheduleData->bps_availability, $currentTrip['loading_start']);
                    $retryOffset += max(1, $nextPlantFree);
                    continue;
                }

                // ── Capture first trip's actual pouring_start ─────────────
                if ($currentTrip['trip'] === 1 && !isset($orderFirstPouringStart[$orderNo])) {
                    $orderFirstPouringStart[$orderNo] = Carbon::parse($scheduleData->pouring_start)->copy();
                }

                // ── Delay-limit check BEFORE committing resources ─────────
                // Check: current pouring_end − first pouring_start must stay
                // within expected_duration + max_delay.
                // If exceeded → reject immediately WITHOUT locking truck/plant.
                if (isset($orderFirstPouringStart[$orderNo])) {
                    $firstPS          = $orderFirstPouringStart[$orderNo];
                    $currentPE        = Carbon::parse($scheduleData->pouring_end);
                    $elapsedMins      = (int) $firstPS->diffInMinutes($currentPE);
                    $expectedDuration = (int) ($tripData['expected_duration_minutes'] ?? 0);
                    $delayLimit       = $expectedDuration + (int) ($order->max_delay ?? 30);

                    // Log::info("[DELAY_LIMIT_CHECK] order={$orderNo} trip={$currentTrip['trip']} "
                    //     . "first_pouring_start={$firstPS->format('H:i')} "
                    //     . "current_pouring_end={$currentPE->format('H:i')} "
                    //     . "elapsed={$elapsedMins}min "
                    //     . "limit={$delayLimit}min (expected={$expectedDuration}+max_delay={$order->max_delay}) "
                    //     . ($elapsedMins > $delayLimit ? "⛔ EXCEEDED" : "✓ OK"));

                    if ($elapsedMins > $delayLimit) {
                        $orderFailed[$orderNo] = "Delay limit exceeded at trip {$currentTrip['trip']}. "
                            . "Elapsed: {$elapsedMins} min (first pouring {$firstPS->format('H:i')} → "
                            . "current pouring end {$currentPE->format('H:i')}). "
                            . "Limit: {$delayLimit} min "
                            . "(expected {$expectedDuration} + max_delay {$order->max_delay}).";
                        Log::info("[DELAY_LIMIT_CHECK] order={$orderNo} trip={$currentTrip['trip']} "
                            . "first_pouring_start={$firstPS->format('H:i')} "
                            . "current_pouring_end={$currentPE->format('H:i')} "
                            . "elapsed={$elapsedMins}min "
                            . "limit={$delayLimit}min (expected={$expectedDuration}+max_delay={$order->max_delay}) "
                            . ($elapsedMins > $delayLimit ? "⛔ EXCEEDED" : "✓ OK"));
                        unset($orderSchedules[$orderNo]);
                        break;
                    }
                }

                // ── Delay check passed — now commit resources ─────────────
                $entry = $this->createScheduleEntry($order, $scheduleData, $location, $currentTrip['trip']);
                $orderSchedules[$orderNo][] = $entry;
                $orderDelivered[$orderNo]   = ($orderDelivered[$orderNo] ?? 0) + $currentTrip['batching_qty'];
                $orderRemainingQty[$orderNo] -= $currentTrip['batching_qty'];
                $isFinalTrip = $orderRemainingQty[$orderNo] <= 0;
                $this->updateResourcePoolsOnly($scheduleData, $order, $location);

                if ($isFinalTrip && $order->pump) {
                    foreach ($scheduleData->pump_busy_slots_unset as &$busySlot) {
                        if ($busySlot['order_no'] === $orderNo) {
                            // Correct the soft reservation to the winner's REAL
                            // pump release. +1 min gives the next (losing) order
                            // a clean hand-off instead of touching the exact
                            // boundary; this is the whole gap the loser sees.
                            $busySlot['end'] = $scheduleData->return_end->copy()->addMinute();
                            break;
                        }
                    }
                    unset($busySlot);
                }
                if ($isFinalTrip) {
                    $orderCompleted[$orderNo] = true;
                }
                if ($retryOffset > 0) {
                    $orderCumulativeDelay[$orderNo] = $priorDelay + $retryOffset;
                }
                $tripScheduled = true;
                break;
            }
            if (!$tripScheduled && !isset($orderFailed[$orderNo])) {

                // ── Common context for the failure log ───────────────────────────
                $attemptedStart = Carbon::parse($tripData['loading_start']);
                $lastTriedStart = $attemptedStart->copy()->addMinutes($retryOffset);

                if ($tripData['trip'] > 1) {
                    // Flexible: delay-limit budget ran out
                    $firstPS = $orderFirstPouringStart[$orderNo] ?? null;
                    $expectedDuration = (int) ($tripData['expected_duration_minutes'] ?? 0);
                    $delayLimitTotal  = $expectedDuration + (int) ($order->max_delay ?? 30);
                    $orderFailed[$orderNo] = "Delay limit exceeded for flexible order. "
                        . "Trip {$tripData['trip']} could not be scheduled within the remaining budget. "
                        . "Total allowed: {$delayLimitTotal} min "
                        . "(expected {$expectedDuration} + max_delay {$order->max_delay})."
                        . ($firstPS ? " First pouring started at {$firstPS->format('H:i')}." : "");

                    Log::warning("[MAXRETRY_EXCEEDED] order={$orderNo} trip={$tripData['trip']}/{$totalTrips} "
                        . "TYPE=trip2+ "
                        . "retryOffset={$retryOffset}min maxRetry={$maxRetryMinutes}min "
                        . "orig_loading_start={$attemptedStart->format('Y-m-d H:i')} "
                        . "last_tried_start={$lastTriedStart->format('Y-m-d H:i')} "
                        . "delay_limit={$delayLimitTotal}min "
                        . "(expected={$expectedDuration}+max_delay=" . (int)($order->max_delay ?? 30) . ") "
                        . ($firstPS ? "first_pouring={$firstPS->format('H:i')} " : "")
                        . "lpi={$order->lpi_score} pump=" . ($order->pump ? 'yes' : 'no'));
                } else {
                    $orderFailed[$orderNo] = "Could not schedule within constraints. "
                        . "Max retry of {$maxRetryMinutes} minutes exhausted.";

                    Log::warning("[MAXRETRY_EXCEEDED] order={$orderNo} trip={$tripData['trip']}/{$totalTrips} "
                        . "TYPE=trip1 "
                        . "retryOffset={$retryOffset}min maxRetry={$maxRetryMinutes}min "
                        . "orig_loading_start={$attemptedStart->format('Y-m-d H:i')} "
                        . "last_tried_start={$lastTriedStart->format('Y-m-d H:i')} "
                        . "location={$location} "
                        . "lpi={$order->lpi_score} pump=" . ($order->pump ? 'yes' : 'no'));
                }
            }
        }

        // ════════════════════════════════════════════════════════════════════
        //  POST-SCHEDULING VALIDATION (client meeting 2026-05-18)
        //
        //  "max_delay" = actual_duration − expected_duration
        //  where duration = first pouring_start → last pouring_end
        //
        //  If delay exceeds max_delay → reject the order.
        //
        //  Also enforce: big order supply rate must not drop below 75%
        //  of the agreed rate when small orders are inserted.
        // ════════════════════════════════════════════════════════════════════
        $orderActualDelay = [];
        foreach ($orderSchedules as $orderNo => $entries) {
            $order = $orderMap[$orderNo] ?? null;
            if (!$order) continue;
            if (isset($orderFailed[$orderNo]) && empty($orderCompleted[$orderNo])) continue;

            // ── Compute actual pouring span from scheduled entries ────────
            $actualPouringStart = null;
            $actualPouringEnd   = null;
            foreach ($entries as $entry) {
                $ps = Carbon::parse($entry['pouring_start']);
                $pe = Carbon::parse($entry['pouring_end']);
                if ($actualPouringStart === null || $ps->lt($actualPouringStart)) {
                    $actualPouringStart = $ps->copy();
                }
                if ($actualPouringEnd === null || $pe->gt($actualPouringEnd)) {
                    $actualPouringEnd = $pe->copy();
                }
            }
            if (!$actualPouringStart || !$actualPouringEnd) continue;

            $actualDurationMins = (int) $actualPouringStart->diffInMinutes($actualPouringEnd);

            // ── Get expected duration from trip data ──────────────────────
            $orderTrips = array_values(array_filter(
                $sortedTrips,
                fn($t) => $t['order_no'] === $orderNo
            ));
            $expectedDuration = !empty($orderTrips)
                ? ($orderTrips[0]['expected_duration_minutes'] ?? $actualDurationMins)
                : $actualDurationMins;

            $delayMins  = max(0, $actualDurationMins - $expectedDuration);
            $maxAllowed = (int) ($order->max_delay ?? 15);

            $orderActualDelay[$orderNo] = [
                'expected'  => $expectedDuration,
                'actual'    => $actualDurationMins,
                'delay'     => $delayMins,
                'max'       => $maxAllowed,
                'exceeded'  => $delayMins > $maxAllowed,
            ];

            // Log::info("[DELAY_CHECK] order={$orderNo} "
            //     . "expected={$expectedDuration}min actual={$actualDurationMins}min "
            //     . "delay={$delayMins}min max_allowed={$maxAllowed}min "
            //     . ($delayMins > $maxAllowed ? "⛔ EXCEEDED" : "✓ OK"));

            // ── Reject if delay exceeds max_delay ────────────────────────
            if ($delayMins > $maxAllowed) {
                $orderFailed[$orderNo] = "Order delay ({$delayMins} min) exceeds maximum "
                    . "acceptable delay ({$maxAllowed} min). "
                    . "Expected duration: {$expectedDuration} min, "
                    . "Actual duration: {$actualDurationMins} min.";
                unset($orderSchedules[$orderNo]);
                continue;
            }

            // ── 75% Supply Rate Check (big orders only) ──────────────────
            // Client: "minimum productivity for big order should not be less
            // than 75 percent" when small orders are inserted
            if ($order->quantity >= 100 && $expectedDuration > 0) {
                $expectedHours = max(0.01, $expectedDuration / 60);
                $actualHours   = max(0.01, $actualDurationMins / 60);
                $expectedRate  = $order->quantity / $expectedHours;  // m³/hr
                $actualRate    = $order->quantity / $actualHours;    // m³/hr
                $ratePercent   = ($actualRate / max(0.01, $expectedRate)) * 100;

                if ($ratePercent < 75) {
                    Log::warning("[SUPPLY_RATE] order={$orderNo} qty={$order->quantity} "
                        . "rate dropped to " . round($ratePercent, 1) . "% "
                        . "(expected=" . round($expectedRate, 1) . " m³/hr, "
                        . "actual=" . round($actualRate, 1) . " m³/hr) — BELOW 75%");

                    $orderFailed[$orderNo] = "Supply rate dropped to " . round($ratePercent, 1) . "% "
                        . "(below 75% minimum). "
                        . "Expected: " . round($expectedRate, 1) . " m³/hr, "
                        . "Actual: " . round($actualRate, 1) . " m³/hr.";
                    unset($orderSchedules[$orderNo]);
                    continue;
                }
            }
        }

        foreach ($orderSchedules as $orderNo => $entries) {
            $order = $orderMap[$orderNo];
            if (isset($orderFailed[$orderNo]) && empty($orderCompleted[$orderNo])) {
                try {
                    DB::table('selected_orders')
                        ->where('id', $order->id)
                        ->update([
                            'failure_reason'     => "Order could not be fully scheduled — "
                                . "partial scheduling is not allowed. "
                                . "{$orderFailed[$orderNo]}",
                            'delivered_quantity' => 0,
                            'start_time'         => null,
                            'end_time'           => null,
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("[PARTIAL_BLOCKED] Could not persist rejection for order={$orderNo}: " . $e->getMessage());
                }
                continue;
            }
            $scheduleData->schedules                     = $entries;
            $scheduleData->selected_order_pump_schedules = [];
            $scheduleData->delivered_quantity            = $orderDelivered[$orderNo] ?? 0;
            $scheduleData->failure_reason                = null;
            $scheduleData->order_no                      = $orderNo;
            if ($order->pump) {
                $pumpAssigned =  $this->assignPump($order, $scheduleData, $order->location);
                if (!$pumpAssigned) {
                    $scheduleData->failure_reason    = "No available pump found with in delay limit tolerance." . $order->tolerance . ",Max Delay limit " . $order->max_delay . "min";
                    $scheduleData->schedules         = [];
                    $scheduleData->delivered_quantity = 0;
                    DB::table('selected_orders')
                        ->where('id', $order->id)
                        ->update([
                            'failure_reason'     => $scheduleData->failure_reason,
                            'delivered_quantity' => 0,
                            'start_time'         => null,
                            'end_time'           => null,
                        ]);
                    continue;
                }
            }
            $this->storeSchedules($order, $scheduleData);
        }
        foreach ($orderFailed as $orderNo => $reason) {
            if (empty($orderSchedules[$orderNo])) {
                $order = $orderMap[$orderNo] ?? null;
                if ($order) {
                    DB::table('selected_orders')
                        ->where('id', $order->id)
                        ->update(['failure_reason' => $reason]);
                }
            }
        }
    }
    private function updateResourcePoolsOnly(ScheduleData &$scheduleData, $order, string $location): void
    {
        $truck      = $scheduleData->transit_mixer['data'];
        $truckIndex = $scheduleData->transit_mixer['index'];
        $plant      = $scheduleData->batching_plant['data'];
        $plantIndex = $scheduleData->batching_plant['index'];
        $scheduleData->tms_availability[$truckIndex]['free_upto'] =
            $scheduleData->loading_start->copy()->addSeconds()->format('Y-m-d H:i:s');
        $scheduleData->tms_availability[$truckIndex]['location'] = $location;
        if (
            isset($scheduleData->tms_availability[$truckIndex]['free_from']) &&
            $scheduleData->tms_availability[$truckIndex]['free_upto']
            <= $scheduleData->tms_availability[$truckIndex]['free_from']
        ) {
            unset($scheduleData->tms_availability[$truckIndex]);
        }
        $scheduleData->tms_availability[] = [
            'truck_name'     => $truck['truck_name'],
            'truck_capacity' => $truck['truck_capacity'],
            'loading_time'   => $scheduleData->loading_time,
            'free_from'      => $scheduleData->return_end->copy()->subSeconds()->format('Y-m-d H:i:s'),
            'free_upto'      => $truck['free_upto'],
            'location'       => $location,
        ];
        $scheduleData->bps_availability[$plantIndex]['free_upto'] =
            $scheduleData->loading_start->copy()->addSeconds();
        if (
            isset($scheduleData->bps_availability[$plantIndex]['free_from']) &&
            $scheduleData->bps_availability[$plantIndex]['free_upto']
            <= $scheduleData->bps_availability[$plantIndex]['free_from']
        ) {
            unset($scheduleData->bps_availability[$plantIndex]);
        }
        $scheduleData->bps_availability[] = [
            'plant_name'     => $plant['plant_name'],
            'plant_capacity' => $plant['plant_capacity'],
            'free_from'      => $scheduleData->loading_end->copy()->subSeconds(),
            'free_upto'      => $plant['free_upto'],
            'location'       => $location,
        ];
        if (!in_array($plant['plant_name'], $scheduleData->assigned_plants)) {
            $scheduleData->assigned_plants[] = $plant['plant_name'];
        }
        if (!in_array($truck['truck_name'], $scheduleData->assigned_tms)) {
            $scheduleData->assigned_tms[] = $truck['truck_name'];
        }
        $scheduleData->plant_busy_slots[] = [
            'start'    => $scheduleData->loading_start->copy(),
            'end'      => $scheduleData->loading_end->copy(),
            'plant_id' => $plant['plant_name'],
            'order_no' => $scheduleData->order_no,
        ];
        $scheduleData->truck_busy_slots[] = [
            'start'    => $scheduleData->loading_start->copy(),
            'end'      => $scheduleData->return_end->copy()->subSeconds(),
            'truck_id' => $truck['truck_name'],
            'order_no' => $scheduleData->order_no,
            'cap'      => $truck['truck_capacity'],
        ];
    }
    private function shiftTripByMinutes(array $tripData, int $minutes, $order, $scheduleData): array
    {
        static $dateKeys = [
            'loading_start',
            'loading_end',
            'qc_start',
            'qc_end',
            'travel_start',
            'travel_end',
            'insp_start',
            'insp_end',
            'waiting_start',
            'waiting_end',
            'pouring_start',
            'pouring_end',
            'cleaning_start',
            'cleaning_end',
            'return_start',
            'return_end',
        ];
        $shifted = $tripData;
        foreach ($dateKeys as $key) {
            if (isset($shifted[$key])) {
                $val = $shifted[$key];
                $shifted[$key] = ($val instanceof Carbon ? $val->copy() : Carbon::parse($val))->addMinutes($minutes);
            }
        }
        return $shifted;
    }
    private function recalculateTripForCapacity(
        array $tripData,
        int $actualCapacity,
        int $baseLoadingTime,
        int $basePouringTime,
        int $remainingQty
    ): array {
        if ($remainingQty <= 0) {
            $updated = $tripData;
            $updated['batching_qty'] = 0;
            return $updated;
        }
        $batchQty = min($actualCapacity, $remainingQty);
        $batchQty = max(1, $batchQty);
        $scaledLoading = max(1, (int) round(($baseLoadingTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty));
        $scaledPouring = max(0, (int) round(($basePouringTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty));
        $qcTime       = (int) ($tripData['qc_time'] ?? 0);
        $inspTime     = (int) ($tripData['insp_time'] ?? 0);
        $cleaningTime = (int) ($tripData['cleaning_time'] ?? 0);
        if ($batchQty < 8)
            $extraLoadingTime = $tripData['loading_time'];
        else
            $extraLoadingTime = ($scaledLoading - $tripData['loading_time']);
        $travelTime = (int) ($tripData['travel_time_mins'] ?? max(0, Carbon::parse($tripData['travel_start'])
            ->diffInMinutes(Carbon::parse($tripData['travel_end']))));
        $returnTime = (int) ($tripData['return_time_mins'] ?? max(0, Carbon::parse($tripData['return_start'])
            ->diffInMinutes(Carbon::parse($tripData['return_end']))));
        $loadingStart  = Carbon::parse($tripData['loading_start'])->copy()->subMinutes($extraLoadingTime);
        $loadingEnd    = $loadingStart->copy()->addMinutes($scaledLoading);
        $qcStart       = $loadingEnd->copy()->addMinute();
        $qcEnd         = $qcStart->copy()->addMinutes($qcTime);
        $travelStart   = $qcEnd->copy()->addMinute();
        $travelEnd     = $travelStart->copy()->addMinutes($travelTime);
        $inspStart     = $travelEnd->copy()->addMinute();
        $inspEnd       = $inspStart->copy()->addMinutes($inspTime);
        $pouringStart  = $inspEnd->copy()->addMinute();
        $pouringEnd    = $pouringStart->copy()->addMinutes($scaledPouring);
        $cleaningStart = $pouringEnd->copy()->addMinute();
        $cleaningEnd   = $cleaningStart->copy()->addMinutes($cleaningTime);
        $returnStart   = $cleaningEnd->copy()->addMinute();
        $returnEnd     = $returnStart->copy()->addMinutes($returnTime);
        $updated = $tripData;
        $updated['batching_qty']   = $batchQty;
        $updated['loading_time']   = $scaledLoading;
        $updated['pouring_time']   = $scaledPouring;
        $updated['loading_start']  = $loadingStart;
        $updated['loading_end']    = $loadingEnd;
        $updated['qc_start']       = $qcStart;
        $updated['qc_end']         = $qcEnd;
        $updated['travel_start']   = $travelStart;
        $updated['travel_end']     = $travelEnd;
        $updated['insp_start']     = $inspStart;
        $updated['insp_end']       = $inspEnd;
        // Reset waiting — will be recalculated during actual scheduling
        $updated['waiting_start']  = null;
        $updated['waiting_end']    = null;
        $updated['waiting_time']   = 0;
        $updated['pouring_start']  = $pouringStart;
        $updated['pouring_end']    = $pouringEnd;
        $updated['cleaning_start'] = $cleaningStart;
        $updated['cleaning_end']   = $cleaningEnd;
        $updated['return_start']   = $returnStart;
        $updated['return_end']     = $returnEnd;
        return $updated;
    }
    private function applyTripToScheduleData(ScheduleData &$scheduleData, array $trip): void
    {
        $scheduleData->loading_time   = $trip['loading_time'];
        $scheduleData->pouring_time   = $trip['pouring_time'];
        $scheduleData->batching_qty   = $trip['batching_qty'];
        $scheduleData->loading_start  = $trip['loading_start'];
        $scheduleData->loading_end    = $trip['loading_end'];
        $scheduleData->qc_start       = $trip['qc_start'];
        $scheduleData->qc_end         = $trip['qc_end'];
        $scheduleData->travel_start   = $trip['travel_start'];
        $scheduleData->travel_end     = $trip['travel_end'];
        $scheduleData->insp_start     = $trip['insp_start'];
        $scheduleData->insp_end       = $trip['insp_end'];
        $scheduleData->waiting_start  = $trip['waiting_start'] ?? null;
        $scheduleData->waiting_end    = $trip['waiting_end'] ?? null;
        $scheduleData->waiting_time   = $trip['waiting_time'] ?? 0;
        $scheduleData->pouring_start  = $trip['pouring_start'];
        $scheduleData->pouring_end    = $trip['pouring_end'];
        $scheduleData->cleaning_start = $trip['cleaning_start'];
        $scheduleData->cleaning_end   = $trip['cleaning_end'];
        $scheduleData->return_start   = $trip['return_start'];
        $scheduleData->return_end     = $trip['return_end'];
    }
    private function initializeVariables(ScheduleData &$scheduleData)
    {
        $scheduleData->assigned_pumps_per_order = 1;
        $scheduleData->phase          = 1;
        $scheduleData->shift_end_exit = 0;
        $scheduleData->early_trip     = null;
        $scheduleData->late_trip      = null;
        $scheduleData->lastResponse   = null;
        $scheduleData->qc_time        = GlobalSetting::where('group_company_id', $scheduleData->company)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;
        $scheduleData->insp_time      = GlobalSetting::where('group_company_id', $scheduleData->company)->value('site_quality_inspection')     ?? ConstantHelper::INSP_TIME;
        $scheduleData->cleaning_time  = GlobalSetting::where('group_company_id', $scheduleData->company)->value('chute_cleaning_site')          ?? ConstantHelper::CLEANING_TIME;
        $scheduleData->loading_time   = ConstantHelper::LOADING_TIME;
    }
    private function fetchOrders(ScheduleData $scheduleData)
    {
        return SelectedOrder::select(
            "group_company_id",
            "id",
            "og_order_id",
            "base_interval",
            "order_no",
            "customer",
            "project",
            "site",
            'item_type',
            "site_id",
            "location",
            'loading_time',
            'tolerance',
            'max_delay',
            'expected_duration',
            "mix_code",
            "quantity",
            "delivery_date",
            "interval",
            "interval_deviation",
            "pump",
            "pouring_time",
            "travel_to_site",
            "return_to_plant",
            "pump_qty",
            "priority",
            "flexibility",
            "multi_pouring",
            "structural_reference_id",
            "customer_id",
            "lpi_score"
        )
            ->with('customer_company')
            ->where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->whereBetween("delivery_date", [$scheduleData->shift_start, $scheduleData->shift_end])
            ->whereNull("start_time")
            ->where("selected", true)
            ->orderBy('priority', 'ASC')
            ->orderBy('lpi_score', 'DESC')
            ->get();
    }
    private function adjustLocations($order, $batchingPlantAvailability)
    {
        $locations = array_unique(array_column($batchingPlantAvailability, 'location'));
        $index     = array_search($order->location, $locations);
        if ($index !== false && $index > 0) {
            unset($locations[$index]);
            array_unshift($locations, $order->location);
        }
        return $locations;
    }
    private function getLocations($orders, $scheduleData)
    {
        foreach ($orders as $order) {
            $location = $order->location;
            if (empty($location)) {
                $locations = $this->adjustLocations($order, $scheduleData->bps_availability);
                $nearestBatchingPlant = CustomerProjectSiteHelper::assignNewBatchingPlant($order, $locations);
                $order->location = $nearestBatchingPlant->location ?? ($locations[0] ?? null);
            }
        }
    }
    private function assignBatchingPlant(ScheduleData &$scheduleData, $location, $trip, $order)
    {
        $scheduleData->batching_plant = BatchingPlantHelper::getAvailableBatchingPlants(
            $scheduleData->bps_availability,
            $location,
            $scheduleData->loading_start,
            $scheduleData->loading_end,
            $scheduleData->restriction_start,
            $scheduleData->restriction_end,
            $scheduleData->assigned_plants,
            $scheduleData->assigned_plant,
        );
        if (isset($scheduleData->batching_plant['data']['plant_name'])) {
        } else {
        }
    }
    private function assignTransitMixer(ScheduleData &$scheduleData, $location, $trip, $quantity, $order)
    {
        $scheduleData->transit_mixer = TransitMixerHelper::getAvailableTrucks(
            $scheduleData->tms_availability,
            null,
            $scheduleData->loading_start,
            $scheduleData->return_end,
            $scheduleData->shift_end,
            $scheduleData->restriction_start,
            $scheduleData->restriction_start,
            $location,
            $trip,
            $scheduleData->assigned_tms,
            $scheduleData,
            $order->loading_time,
        );
        if (isset($scheduleData->transit_mixer['data']['truck_name'])) {
        } else {
        }
    }
    private function storeSchedules($order, ScheduleData &$scheduleData)
    {
        if ($scheduleData->failure_reason) {
            DB::table('selected_orders')->where('id', $order->id)
                ->update(['failure_reason' => $scheduleData->failure_reason]);
        }
        $user_id = $scheduleData->user_id;
        DB::table("selected_order_schedules")->insert($scheduleData->schedules);

        // ── Single query for all min/max values (replaces 5 separate queries) ──
        $agg = DB::table('selected_order_schedules')
            ->select(DB::raw('MIN(pouring_start) AS min_pour, MAX(pouring_end) AS max_pour, MIN(loading_start) AS min_load'))
            ->where('group_company_id', $scheduleData->company)
            ->where('user_id', $user_id)
            ->where('order_no', $order->order_no)
            ->first();

        $scheduleData->order_start_time = $agg->min_pour;
        $scheduleData->order_end_time   = $agg->max_pour;
        $scheduleData->min_loading_start = $agg->min_load;

        // ── Compute deviation and delay in PHP (no extra DB reads) ────────
        $deviation = Carbon::parse($order->delivery_date)
            ->diffInMinutes(Carbon::parse($agg->min_pour), false);

        $actualDuration   = ($agg->min_pour && $agg->max_pour)
            ? (int) Carbon::parse($agg->min_pour)->diffInMinutes(Carbon::parse($agg->max_pour))
            : 0;
        $expectedDuration = (int) ($order->expected_duration ?? $actualDuration);
        $actualDelay      = max(0, $actualDuration - $expectedDuration);

        // ── Single update for all order fields (replaces 3 separate updates) ──
        DB::table('selected_orders')->where('id', $order->id)->update([
            'start_time'         => $agg->min_pour,
            'end_time'           => $agg->max_pour,
            'delivered_quantity'  => $scheduleData->delivered_quantity,
            'location'           => $scheduleData->location,
            'deviation'          => $deviation,
            'actual_delay'       => $actualDelay,
            'expected_duration'  => $expectedDuration,
        ]);

        if ($order->pump) {
            DB::table("selected_order_pump_schedules")
                ->insert(array_values($scheduleData->selected_order_pump_schedules));
        }

        // Log::info("[STORED] order={$order->order_no} "
        //     . "expected={$expectedDuration}min actual={$actualDuration}min "
        //     . "delay={$actualDelay}min");
    }
    private function createScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {
        return [
            "order_id"       => $order->id,
            "group_company_id" => $scheduleData->company,
            "user_id"        => $scheduleData->user_id,
            "schedule_date"  => $scheduleData->schedule_date,
            "order_no"       => $order->order_no,
            "location"       => $location,
            "trip"           => $trip,
            "mix_code"       => $order->mix_code,
            "batching_plant" => $scheduleData->batching_plant['data']['plant_name'] ?? null,
            "transit_mixer"  => $scheduleData->transit_mixer['data']['truck_name'] ?? null,
            'capacity'       => $scheduleData->transit_mixer['data']['truck_capacity'] ?? null,
            "batching_qty"   => $scheduleData->batching_qty,
            "loading_time"   => $scheduleData->loading_time,
            "loading_start"  => $scheduleData->loading_start,
            "loading_end"    => $scheduleData->loading_end,
            "qc_time"        => $scheduleData->qc_time,
            "qc_start"       => $scheduleData->qc_start,
            "qc_end"         => $scheduleData->qc_end,
            "travel_time"    => $order->travel_to_site,
            "travel_start"   => $scheduleData->travel_start,
            "travel_end"     => $scheduleData->travel_end,
            "insp_time"      => $scheduleData->insp_time,
            "insp_start"     => $scheduleData->insp_start,
            "insp_end"       => $scheduleData->insp_end,
            "waiting_time"   => $scheduleData->waiting_time ?? 0,
            "waiting_start"  => $scheduleData->waiting_start ?? null,
            "waiting_end"    => $scheduleData->waiting_end ?? null,
            "pouring_time"   => $scheduleData->pouring_time,
            "pouring_start"  => $scheduleData->pouring_start,
            "pouring_end"    => $scheduleData->pouring_end,
            "cleaning_time"  => $scheduleData->cleaning_time,
            "cleaning_start" => $scheduleData->cleaning_start,
            "cleaning_end"   => $scheduleData->cleaning_end,
            "return_time"    => $order->return_to_plant,
            "return_start"   => $scheduleData->return_start,
            "return_end"     => $scheduleData->return_end,
            "delivery_start" => $scheduleData->loading_start,
            "deviation"      => abs(Carbon::parse($order->delivery_date)->diffInMinutes($scheduleData->pouring_start, false)),
        ];
    }
    private function assignPump($order, ScheduleData &$scheduleData, $location): bool
    {
        $trips = $this->sortTrips($scheduleData);
        $totalQuantity = array_sum(array_column($trips, 'batching_qty'));
        if (!$order->pump || (int) $order->pump_qty <= 0 || $totalQuantity === 0) {
            return true;
        }
        if (empty($trips)) {
            return false;
        }
        $scheduleData->selected_order_pump_schedules = [];
        $scheduleData->assigned_pump = [];
        $scheduleData->pouring_pump = null;
        $totalTrips = count($trips);
        $pumpsRequired = (int) $order->pump_qty;
        $firstOrderTrip = $trips[0];
        $pumpsTrips = array_fill(0, $pumpsRequired, []);
        foreach ($trips as $index => $trip) {
            $pumpIndex = $index % $pumpsRequired;
            $pumpsTrips[$pumpIndex][] = $trip;
        }
        $batchingQuantities = [];
        $batchingTrips = [];
        foreach ($pumpsTrips as $pumpIndex => $pumpTrips) {
            $totalBatchingQty = array_sum(array_column($pumpTrips, 'batching_qty'));
            $batchingQuantities[$pumpIndex] = $totalBatchingQty;
            $numberOfTrips = count($pumpTrips);
            $batchingTrips[$pumpIndex] = $numberOfTrips;
        }
        for ($p = 0; $p < $pumpsRequired; $p++) {
            $first = $trips[0];
            $last = $trips[count($trips) - 1];
            $lastIndex = count($trips) - 1;
            $pumpTrips = $pumpsTrips[$p];
            $batchingQty = $batchingQuantities[$p];
            $tripsCount = $batchingTrips[$p];
            $groupPourStart = Carbon::parse($trips[$p]['pouring_start']);
            $groupPourEnd = Carbon::parse($trips[$lastIndex - $p]['pouring_end']);
            $groupPumpEndTime = Carbon::parse($trips[$lastIndex - $p]['return_end']);
            $cleanEnd = Carbon::parse($trips[$lastIndex - $p]['cleaning_end']);
            $groupPumpLoadingTime = Carbon::parse($first['loading_start']);
            $pumpSeq = $p + 1;
            $preferred = $scheduleData->assigned_pumps;
            $requirements = [];
            foreach ($order->order_pumps as $op) {
                for ($i = 0; $i < (int) $op->qty; $i++) {
                    $requirements[] = ['capacity' => (float) $op->pump_size, 'type' => $op->type];
                }
            }
            $slots = $scheduleData->pump_busy_slots;
            $siteToSite = null;
            $NewPump = PumpHelper::getAvailablePumps(
                $scheduleData,
                $scheduleData->pumps_availability,
                $order->id,
                $scheduleData->company,
                $groupPourStart->copy(),
                $groupPumpEndTime->copy(),
                $order->pump,
                $pumpSeq,
                $scheduleData->selected_order_pump_schedules,
                $scheduleData->shift_end,
                $order->pump_qty,
                $location,
                $scheduleData->assigned_pump,
                $scheduleData->assigned_pumps,
                $requirements[$p],
                $slots,
                $scheduleData->qc_time,
                $scheduleData->insp_time,
                $order->travel_to_site,
            );
            $scheduleData->pouring_pump = $siteToSite === null ? $NewPump : $siteToSite;
            if (!isset($scheduleData->pouring_pump['pump']['pump_name'])) {
                $reason = "Pump "
                    . "no available pump with required capacity ({$requirements[$p]['capacity']} m³) "
                    . "and type ({$requirements[$p]['type']}) found within the shift window.";
                $scheduleData->failure_reason = $reason;
                if (!empty($scheduleData->bps_availability)) {
                    $plant = collect($scheduleData->bps_availability)
                        ->where('location', $scheduleData->location)
                        ->first();
                    if ($plant) {
                        BatchingPlantAvailability::create([
                            'group_company_id' => $scheduleData->company,
                            'location' => $scheduleData->location,
                            'plant_name' => $plant['plant_name'],
                            'plant_capacity' => 0,
                            'free_from' => $groupPumpLoadingTime,
                            'free_upto' => $groupPumpLoadingTime,
                            'user_id' => $scheduleData->user_id,
                            'reason' => $reason,
                        ]);
                    }
                }
                continue;
            }
            $pump = $scheduleData->pouring_pump['pump'];
            $pumpIndex = $scheduleData->pouring_pump['index'];
            $waiting = $scheduleData->pouring_pump['waiting'] ?? 0;
            $pumpName = $pump['pump_name'];
            $pumpId = $pump['pump_id'];
            $installTime = (int) ($pump['installation_time'] ?? 10);
            $qcTime = isset($scheduleData->pouring_pump['qc_time']) ? $scheduleData->pouring_pump['qc_time'] : $scheduleData->qc_time;
            $travelTime = isset($scheduleData->pouring_pump['travel_time']) ? $scheduleData->pouring_pump['travel_time'] : $order->travel_to_site;
            $returnTime = isset($scheduleData->pouring_pump['return_time']) ? $scheduleData->pouring_pump['return_time'] : $order->return_to_plant;
            $waitingTime = $waiting;
            $totalTime = $installTime +
                (int) $qcTime +
                (int) $scheduleData->insp_time +
                (int) $travelTime + (
                    ($installTime > 0 ? 1 : 0) +
                    ($qcTime > 0 ? 1 : 0) +
                    ($travelTime > 0 ? 1 : 0) +
                    ($scheduleData->insp_time > 0 ? 1 : 0))
                + $waiting;
            $start = $groupPourStart->copy()->subMinutes($totalTime);
            $qcStart = $start->copy();
            $qcEnd = $qcTime > 1 ? $qcStart->copy()->addMinutes($qcTime) : $start->copy();
            $travelStart = $qcTime > 1 ? $qcEnd->copy()->addMinute() : $start->copy();
            $travelEnd = $travelTime > 1 ? $travelStart->copy()->addMinutes($travelTime) : $start->copy();
            $inspStart = $travelTime > 1 ? $travelEnd->copy()->addMinute() : $start->copy();
            $inspEnd = $inspStart->copy()->addMinutes($scheduleData->insp_time);
            $installStart = $inspEnd->copy()->addMinute();
            $installEnd = $waitingTime > 1 ? $installStart->copy()->addMinutes($installTime) : $groupPourStart->copy()->subMinute();
            $waitingStart = $waitingTime > 1 ? $installEnd->copy()->addMinute() : null;
            $waitingEnd = $waitingTime > 1 ? $groupPourStart->copy()->subMinute() : null;
            $pouringTime = $groupPourStart->diffInMinutes($groupPourEnd);
            $cleanStart = $groupPourEnd->copy()->addMinute();
            $cleanEnd = $cleanStart->copy()->addMinutes((int) $scheduleData->cleaning_time);
            $returnStart = $returnTime > 0 ? $cleanEnd->copy()->addMinute() : $cleanEnd->copy();
            $returnEnd = $returnTime > 0 ? $returnStart->copy()->addMinutes($returnTime) : $cleanEnd->copy();
            $pump = Pump::find($pumpId);
            $trip = 0;
            $scheduleData->selected_order_pump_schedules[] = [
                'order_id' => $order->id,
                'user_id' => $scheduleData->user_id,
                'pump' => $pumpName,
                'mix_code' => $order->mix_code,
                'cust_product_id' => $order->customer_product_id ?? null,
                'trip' => $tripsCount,
                'batching_qty' => $batchingQty,
                'qc_time' => $qcTime,
                'qc_start' => $qcStart->copy(),
                'qc_end' => $qcEnd->copy(),
                'travel_time' => $travelStart === $travelEnd ? 0 : $travelTime,
                'travel_start' => $travelStart->copy(),
                'travel_end' => $travelEnd->copy(),
                'insp_time' => (int) $scheduleData->insp_time,
                'insp_start' => $inspStart->copy(),
                'insp_end' => $inspEnd->copy(),
                'install_time' => $installTime,
                'install_start' => $installStart->copy(),
                'install_end' => $installEnd->copy(),
                'pouring_start' => $groupPourStart->copy(),
                'waiting_start' => $waitingStart,
                'waiting_end' => $waitingEnd,
                'waiting_time' => $waitingTime,
                'pouring_end' => $groupPourEnd->copy(),
                'pouring_time' => $pouringTime,
                'cleaning_time' => (int) $scheduleData->cleaning_time,
                'cleaning_start' => $cleanStart->copy(),
                'cleaning_end' => $cleanEnd->copy(),
                'return_time' => $returnTime,
                'return_start' => $returnStart->copy(),
                'return_end' => $returnEnd->copy(),
                'delivery_start' => Carbon::parse($first['delivery_start'] ?? $first['loading_start']),
                'group_company_id' => $scheduleData->company,
                'schedule_date' => $scheduleData->schedule_date,
                'order_no' => $scheduleData->order_no,
                'location' => $scheduleData->location,
            ];
            $scheduleData->pump_busy_slots[] = [
                'start' => $qcStart->copy(),
                'end' => $returnEnd->copy(),
                'pump_id' => $pumpId,
                'type' => $pump->type,
                'capacity' => $pump->pump_capacity,
                'location' => $order->site_id,
                'order_no' => $order->order_no,
                'pouring_start' => $groupPourStart->copy(),
                'install_time' => $pump->installation_time,
                'clean_ends' => $cleanEnd->copy(),
                'insp_starts' => $inspStart->copy(),
                'interval' => $order->base_interval ?? $order->interval,
                'waiting' => $scheduleData->pouring_pump['waiting'] ?? 0
            ];
            if (!isset($scheduleData->assigned_pump[$pump['pump_capacity']])) {
                $scheduleData->assigned_pump[$pump['pump_capacity']] = [];
            }
            $scheduleData->assigned_pump[$pump['pump_capacity']][] = $pumpName;
            if (!in_array($pumpName, $scheduleData->assigned_pumps)) {
                $scheduleData->assigned_pumps[] = $pumpName;
            }
        }
        $assignedPumpCount = count($scheduleData->selected_order_pump_schedules);
        if ($assignedPumpCount === 0) {
            return false;
        }
        return true;
    }
    public function sortTrips(ScheduleData $scheduleData): array
    {
        $trips = $scheduleData->schedules ?? [];
        usort($trips, function ($a, $b) {
            $ta = isset($a['trip']) && is_numeric($a['trip']) ? (int) $a['trip'] : PHP_INT_MAX;
            $tb = isset($b['trip']) && is_numeric($b['trip']) ? (int) $b['trip'] : PHP_INT_MAX;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            $pa = isset($a['pouring_start']) ? Carbon::parse($a['pouring_start'])->timestamp : PHP_INT_MAX;
            $pb = isset($b['pouring_start']) ? Carbon::parse($b['pouring_start'])->timestamp : PHP_INT_MAX;
            return $pa <=> $pb;
        });
        return $trips;
    }
    public static function getDistance($origin_id, $destination_id)
    {
        if ($origin_id === $destination_id) {
            return 0;
        }
        $origin = CustomerProjectSite::find($origin_id);
        $destination = CustomerProjectSite::find($destination_id);
        $apiURL = config('app.google_maps_api_base_url') . '/maps/api/distancematrix/json';
        $queryParams = [
            'key' => config('app.google_map_key'),
            'origins' => $origin->latitude . "," . $origin->longitude,
            'destinations' => $destination->latitude . "," . $destination->longitude,
        ];
        $response = Http::timeout(120)->get($apiURL, $queryParams);
        $data = $response->json();
        if (
            isset($data['rows'][0]['elements'][0]['duration']['value'])
            && $data['rows'][0]['elements'][0]['status'] === 'OK'
        ) {
            $seconds = $data['rows'][0]['elements'][0]['duration']['value'];
            $minutes = ceil($seconds / 60);
            return $minutes;
        }
        return 0;
    }
    public static function validateAllResourceConflicts($scheduleData)
    {
        $conflicts = [];
        $truckSchedules = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->where('schedule_date', $scheduleData->schedule_date)
            ->select(
                'transit_mixer',
                'batching_plant',
                'order_no',
                'qc_start',
                'return_end',
                'loading_start',
                'loading_end',
                'trip'
            )
            ->get();
        foreach ($truckSchedules as $row) {
            if ($row->batching_qty > $row->capacity) {
                $conflicts[] = [
                    'type' => 'Batching Quantity Conflict',
                    'resource_id' => $row->transit_mixer,
                    'order_1' => $row->order_no,
                    'order_2' => null,
                    'message' => 'Batching quantity exceeds truck capacity'
                ];
            }
        }
        $groupedTrucks = $truckSchedules->groupBy('transit_mixer');
        foreach ($groupedTrucks as $truckId => $schedules) {
            $sorted = $schedules->sortBy('qc_start')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if (Carbon::parse($curr->qc_start)->lt(Carbon::parse($prev->return_end))) {
                    $conflicts[] = [
                        'type' => 'Transit Mixer Conflict',
                        'resource_id' => $truckId,
                        'order_1' => $prev->order_no . " Trip:" . $prev->trip,
                        'order_2' => $curr->order_no . " Trip:" . $curr->trip,
                    ];
                }
            }
        }
        $groupedPlants = $truckSchedules->groupBy('batching_plant');
        foreach ($groupedPlants as $plantId => $schedules) {
            $sorted = $schedules->sortBy('loading_start')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if (Carbon::parse($curr->loading_start)->lt(Carbon::parse($prev->loading_end))) {
                    $conflicts[] = [
                        'type' => 'Batching Plant Conflict',
                        'resource_id' => $plantId,
                        'order_1' => $prev->order_no . " Trip:" . $prev->trip,
                        'order_2' => $curr->order_no . " Trip:" . $curr->trip,
                    ];
                }
            }
        }
        $pumpSchedules = SelectedOrderPumpSchedule::where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->where('schedule_date', $scheduleData->schedule_date)
            ->select(
                'pump',
                'order_no',
                'qc_start',
                'return_end'
            )
            ->get();
        $groupedPumps = $pumpSchedules->groupBy('pump');
        foreach ($groupedPumps as $pumpId => $schedules) {
            $sorted = $schedules->sortBy('qc_start')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if (Carbon::parse($curr->qc_start)->lt(Carbon::parse($prev->return_end))) {
                    $conflicts[] = [
                        'type' => 'Pump Conflict',
                        'resource_id' => $pumpId,
                        'order_1' => $prev->order_no,
                        'order_2' => $curr->order_no,
                    ];
                }
            }
        }
        return $conflicts;
    }
    public static function updateQcFromPreviousSlot()
    {
        try {
            $slots = SelectedOrderPumpSchedule::where('qc_time', 0)
                ->orderBy('pouring_start')
                ->get();
            foreach ($slots as $slot) {
                $previousSlot = SelectedOrderPumpSchedule::where('pump', $slot->pump)
                    ->where('pouring_start', '<', $slot->pouring_start)
                    ->orderByDesc('pouring_start')
                    ->first();
                if (!$previousSlot) {
                    continue;
                }
                $qcStart = Carbon::parse($previousSlot->return_end)->copy()->addMinute();
                $qcEnd = $qcStart->copy();
                $travelStart = $qcStart->copy();
                $travelEnd = $qcStart->copy();
                $inspStart = $qcStart->copy();
                $inspEnd = $inspStart->copy()->addMinutes($previousSlot->insp_time);
                $installStart = $inspEnd->copy()->addMinute();
                $installEnd = $installStart->copy()->addMinutes($slot->install_time);
                $waitingStart = $installEnd->copy()->addMinute();
                $waitingEnd = Carbon::parse($slot->pouring_start)->subMinute();
                $waitingMinutes = $waitingStart->diffInMinutes($waitingEnd);
                $waitingMinutes = max($waitingMinutes, 0);
                $pourEnd = Carbon::parse($slot->pouring_end);
                $clean_start = $pourEnd->copy()->addMinute();
                $clean_end = $clean_start->copy()->addMinutes($slot->cleaning_time);
                $retun_start = $clean_end->copy()->addMinute();
                $return_end = $retun_start->copy()->addMinutes($slot->return_time);
                $slot->update([
                    'qc_start' => $qcStart->format('Y-m-d H:i:s'),
                    'qc_end' => $qcEnd->format('Y-m-d H:i:s'),
                    'travel_start' => $travelStart->format('Y-m-d H:i:s'),
                    'travel_end' => $travelEnd->format('Y-m-d H:i:s'),
                    'insp_start' => $inspStart->format('Y-m-d H:i:s'),
                    'insp_end' => $inspEnd->format('Y-m-d H:i:s'),
                    'install_start' => $installStart->format('Y-m-d H:i:s'),
                    'install_end' => $installEnd->format('Y-m-d H:i:s'),
                    'waiting_start' => $waitingStart->format('Y-m-d H:i:s'),
                    'waiting_end' => $waitingEnd->format('Y-m-d H:i:s'),
                    'waiting_time' => $waitingMinutes,
                    'cleaning_start' => $clean_start,
                    'cleaning_end' => $clean_end,
                    'return_start' => $retun_start,
                    'return_end' => $return_end
                ]);
            }
        } catch (Exception $e) {
        }
    }
    function checkScheduleTimes($scheduleData)
    {
        $pairs = [
            ['loading_start', 'loading_end'],
            ['qc_start', 'qc_end'],
            ['travel_start', 'travel_end'],
            ['insp_start', 'insp_end'],
            ['waiting_start', 'waiting_end'],
            ['pouring_start', 'pouring_end'],
            ['cleaning_start', 'cleaning_end'],
            ['return_start', 'return_end'],
        ];
        $records = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->where('schedule_date', $scheduleData->schedule_date)
            ->orderBy('loading_start')
            ->get();
        foreach ($records as $row) {
            foreach ($pairs as $pair) {
                $start = $row->{$pair[0]};
                $end = $row->{$pair[1]};
                if ($start && $end) {
                    if (Carbon::parse($start)->gt(Carbon::parse($end))) {
                        Log::error('Schedule time error detected', [
                            'schedule_id' => $row->id,
                            'order_no' => $row->order_no,
                            'schedule_date' => $row->schedule_date,
                            'trip' => $row->trip,
                            'stage' => $pair[0] . ' -> ' . $pair[1],
                            'start_time' => $start,
                            'end_time' => $end
                        ]);
                    }
                }
            }
        }
    }
    private function calculateAndStoreLpi(ScheduleData $scheduleData): void
    {
        $structuralRefs = \App\Models\StructuralReference::whereIn(
            'id',
            SelectedOrder::where('group_company_id', $scheduleData->company)
                ->where('user_id', $scheduleData->user_id)
                ->whereBetween('delivery_date', [$scheduleData->shift_start, $scheduleData->shift_end])
                ->where('selected', true)
                ->whereNotNull('structural_reference_id')
                ->pluck('structural_reference_id')
        )->get()->keyBy('id');

        $orders = SelectedOrder::where('group_company_id', $scheduleData->company)
            ->where('user_id', $scheduleData->user_id)
            ->whereBetween('delivery_date', [$scheduleData->shift_start, $scheduleData->shift_end])
            ->where('selected', true)
            ->get();

        foreach ($orders as $order) {

            $structRef = $structuralRefs[$order->structural_reference_id] ?? null;

            // ════════════════════════════════════════════════════════════════
            //  V — Volume & Complexity (50% weight)
            // ════════════════════════════════════════════════════════════════
            $vQtyBand = match (true) {
                $order->quantity >= 300 => 100,
                $order->quantity >= 150 => 80,
                $order->quantity >= 100 => 60,
                $order->quantity >= 50  => 40,
                default                 => 0,
            };

            $vQtyLabel = match (true) {
                $order->quantity >= 300 => 'BIG (>=300)',
                $order->quantity >= 150 => 'MEDIUM (150-299)',
                $order->quantity >= 100 => 'STANDARD (100-149)',
                $order->quantity >= 50  => 'SMALL (50-99)',
                default                 => 'EXCLUDED (<50)',
            };

            $vPumpBonus = $order->pump ? 10 : 0;
            $v          = min(100, $vQtyBand + $vPumpBonus);
            $vCapped    = ($vQtyBand + $vPumpBonus) > 100;

            // ════════════════════════════════════════════════════════════════
            //  P — Priority & Customer Importance (30% weight)
            // ════════════════════════════════════════════════════════════════
            $priorityRank = (int) ($order->priority ?? 999);

            $pPriorityBonus = match (true) {
                $priorityRank === 1   => 80,
                $priorityRank === 2   => 70,
                $priorityRank === 3   => 60,
                $priorityRank === 4   => 50,
                $priorityRank === 5   => 40,
                $priorityRank <= 10   => 30,
                default               => 0,
            };
            $pPriorityLabel = $priorityRank > 10
                ? "rank=" . $priorityRank . " (>10, no bonus)"
                : "rank=" . $priorityRank;

            $customerTier      = (int) ($order->customer_company->tier ?? 999);
            $pCustomerBonus    = ($customerTier <= 10) ? 20 : 0;
            $pCustomerLabel    = ($customerTier <= 10)
                ? "TOP-{$customerTier} customer"
                : "tier={$customerTier} (>10, no bonus)";

            $pNonFlexBonus = !((int) ($order->flexibility ?? 0)) ? 20 : 0;
            $pNonFlexLabel = $order->flexibility ? 'flexible (no bonus)' : 'NON-FLEXIBLE';

            $pRaw      = $pPriorityBonus + $pCustomerBonus + $pNonFlexBonus;
            $p         = min(100, $pRaw);
            $pCapped   = $pRaw > 100;

            // ════════════════════════════════════════════════════════════════
            //  C — Cycle Fit & Criticality (20% weight)
            // ════════════════════════════════════════════════════════════════
            $cCriticalBonus = $order->is_critical ? 50 : 0;
            $cCriticalLabel = $order->is_critical ? 'CRITICAL (+50)' : 'not critical';

            $travelMins   = (int) ($order->travel_to_site ?? 60);
            $cTravelBonus = max(0, (int) (((max(0, 60 - $travelMins)) / 60) * 30));
            $cTravelLabel = "travel={$travelMins}min";

            $pourTypeName = strtolower($structRef->name ?? $order->item_type ?? '');
            $cPourBonus   = match (true) {
                str_contains($pourTypeName, 'raft')    => 20,
                str_contains($pourTypeName, 'slab')    => 20,
                str_contains($pourTypeName, 'footing') => 15,
                str_contains($pourTypeName, 'wall')    => 10,
                str_contains($pourTypeName, 'column')  => 5,
                default                                => 10,
            };
            $cPourLabel = match (true) {
                str_contains($pourTypeName, 'raft')    => 'raft',
                str_contains($pourTypeName, 'slab')    => 'slab',
                str_contains($pourTypeName, 'footing') => 'footing',
                str_contains($pourTypeName, 'wall')    => 'wall',
                str_contains($pourTypeName, 'column')  => 'column',
                default                                => "unknown('{$pourTypeName}')",
            };

            $cRaw     = $cCriticalBonus + $cTravelBonus + $cPourBonus;
            $c        = min(100, $cRaw);
            $cCapped  = $cRaw > 100;

            // ════════════════════════════════════════════════════════════════
            //  Final LPI
            // ════════════════════════════════════════════════════════════════
            $vWeighted = round(0.50 * $v, 2);
            $pWeighted = round(0.30 * $p, 2);
            $cWeighted = round(0.20 * $c, 2);
            $lpi       = round($vWeighted + $pWeighted + $cWeighted, 2);

            // ════════════════════════════════════════════════════════════════
            //  Detailed per-factor breakdown log
            //  Renders as a multi-line block per order so the dispatcher can
            //  audit exactly how every point was earned.
            // ════════════════════════════════════════════════════════════════
            $breakdown  = "\n";
            $breakdown .= "[LPI_DETAIL] ┌─ Order #{$order->order_no} (qty={$order->quantity} m³) ──────────────────\n";

            $breakdown .= "[LPI_DETAIL] │  V  (Volume & Complexity, 50% weight)\n";
            $breakdown .= "[LPI_DETAIL] │     • Quantity band: {$vQtyLabel} ............... +{$vQtyBand}\n";
            $breakdown .= "[LPI_DETAIL] │     • Pump required: " . ($order->pump ? 'YES' : 'no')
                . str_pad('', 18 - ($order->pump ? 3 : 2), '.') . " +{$vPumpBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     ─ V subtotal:   {$vQtyBand} + {$vPumpBonus} = "
                . ($vQtyBand + $vPumpBonus) . ($vCapped ? " (CAPPED → 100)" : "") . "\n";
            $breakdown .= "[LPI_DETAIL] │     ► V = {$v}/100\n";
            $breakdown .= "[LPI_DETAIL] │\n";

            $breakdown .= "[LPI_DETAIL] │  P  (Priority & Customer Importance, 30% weight)\n";
            $breakdown .= "[LPI_DETAIL] │     • Dispatch priority: {$pPriorityLabel} ........ +{$pPriorityBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     • Customer tier: {$pCustomerLabel} ........ +{$pCustomerBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     • Schedule type: {$pNonFlexLabel} ........ +{$pNonFlexBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     ─ P subtotal:   {$pPriorityBonus} + {$pCustomerBonus} + {$pNonFlexBonus} = {$pRaw}"
                . ($pCapped ? " (CAPPED → 100)" : "") . "\n";
            $breakdown .= "[LPI_DETAIL] │     ► P = {$p}/100\n";
            $breakdown .= "[LPI_DETAIL] │\n";

            $breakdown .= "[LPI_DETAIL] │  C  (Cycle Fit & Criticality, 20% weight)\n";
            $breakdown .= "[LPI_DETAIL] │     • is_critical flag: {$cCriticalLabel} ........ +{$cCriticalBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     • Travel distance: {$cTravelLabel} ........ +{$cTravelBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     • Pour type: {$cPourLabel} ........ +{$cPourBonus}\n";
            $breakdown .= "[LPI_DETAIL] │     ─ C subtotal:   {$cCriticalBonus} + {$cTravelBonus} + {$cPourBonus} = {$cRaw}"
                . ($cCapped ? " (CAPPED → 100)" : "") . "\n";
            $breakdown .= "[LPI_DETAIL] │     ► C = {$c}/100\n";
            $breakdown .= "[LPI_DETAIL] │\n";

            $breakdown .= "[LPI_DETAIL] │  FINAL  LPI = (0.50 × {$v}) + (0.30 × {$p}) + (0.20 × {$c})\n";
            $breakdown .= "[LPI_DETAIL] │             = {$vWeighted} + {$pWeighted} + {$cWeighted}\n";
            $breakdown .= "[LPI_DETAIL] │  ╔══════════════════════════════════════════════╗\n";
            $breakdown .= "[LPI_DETAIL] │  ║  ORDER {$order->order_no}  →  LPI = {$lpi} / 100\n";
            $breakdown .= "[LPI_DETAIL] │  ╚══════════════════════════════════════════════╝\n";
            $breakdown .= "[LPI_DETAIL] └─────────────────────────────────────────────────────";

            Log::info($breakdown);

            // Compact one-liner kept for grep/filter compatibility with old tools
            Log::info("[LPI] Order {$order->order_no} "
                . "qty={$order->quantity} "
                . "pump=" . ($order->pump ? 'yes' : 'no') . " "
                . "priority={$priorityRank} "
                . "tier={$customerTier} "
                . "flexible=" . ($order->flexibility ? 'yes' : 'no') . " "
                . "critical=" . ($order->is_critical ? 'yes' : 'no') . " "
                . "travel={$travelMins}min "
                . "pour_type={$pourTypeName} "
                . "V={$v}({$vQtyBand}+{$vPumpBonus}) "
                . "P={$p}({$pPriorityBonus}+{$pCustomerBonus}+{$pNonFlexBonus}) "
                . "C={$c}({$cCriticalBonus}+{$cTravelBonus}+{$cPourBonus}) "
                . "LPI={$lpi}");

            try {
                DB::table('selected_orders')
                    ->where('id', $order->id)
                    ->update(['lpi_score' => $lpi]);
            } catch (\Throwable $e) {
                Log::warning("[LPI] Could not save lpi_score — run migration first. " . $e->getMessage());
            }
        }
    }
    /**
     * Pre-claim pumps for pump orders in LPI-descending order.
     *
     * Runs once at the start of scheduleTripsChronologically, on the freshly
     * restored pools. For each pump order (highest LPI first) it tries to
     * reserve a matching free pump across that order's occupancy window and
     * writes the reservation into pump_busy_slots_unset (the same soft-reservation
     * timeline the loop already uses), keyed by order_no.
     *
     * Effect on the existing (unchanged) loop:
     *   - A pre-reserved order trips the `alreadyReserved` check on trip 1, so it
     *     skips getNextFreePumpSlot and keeps its natural loading_start.
     *   - An order that could NOT pre-claim a pump (lost the contention to a
     *     higher-LPI order) is left un-reserved, so the loop's normal delay
     *     branch pushes it back until the shared pump frees.
     *
     * When pumps are plentiful or windows don't overlap, every order pre-claims
     * its own pump and nothing is delayed — behaviour is unchanged.
     */
    private function reservePumpsByPriority(ScheduleData &$scheduleData, array $sortedTrips, $allOrders): array
    {
        // Order numbers that could NOT pre-claim a matching pump (lost the
        // contention). Returned so the caller can schedule them AFTER their
        // winners, so their delay is sized against the winner's real release.
        $losers = [];
        Log::info("[PUMP_PRIORITY] reservePumpsByPriority ENTERED v3 (orders="
            . $allOrders->count() . ", trips=" . count($sortedTrips) . ")");
        $orderMap = $allOrders->keyBy('order_no');

        // Build the pump-occupancy window for each pump order from its trips.
        // NOTE: iterate the TRIPS (not $orderMap keys). keyBy() casts numeric
        // order_no keys to int, while trips carry the raw model value (often a
        // string), so a strict comparison against map keys silently matches
        // nothing. Driving off the trips keeps order_no identical on both sides,
        // and $orderMap[$orderNo] array-access auto-casts on lookup.
        $pumpOrders = [];
        $seen       = [];
        foreach ($sortedTrips as $t) {
            $orderNo = $t['order_no'];
            if (isset($seen[$orderNo])) {
                continue;
            }
            $seen[$orderNo] = true;

            $order = $orderMap[$orderNo] ?? null;
            if (!$order || !$order->pump) {
                continue;
            }

            // Window: earliest loading_start → latest return_end.
            // loading_start is a conservative (early) lower bound for the pump
            // hold; the precise end is what determines how long a losing order
            // is delayed, and the loop later corrects it to the real return_end.
            //
            // IMPORTANT: start/end here come from the IDEALISED pre-schedule trip
            // times (generateAllOrderTrips), i.e. before any truck/plant
            // contention. When the run also contains other orders, the winner's
            // real trips can slip later (e.g. ~8 min), so its pump is actually
            // released LATER than this idealised `end`. A losing order delayed
            // only up to this idealised `end` would then be parked just BEFORE
            // the pump is truly free, collide in the final assignPump(), and be
            // rejected. We absorb that slip below by padding `end` with the
            // winner's max_delay (the bounded worst case it can slip and still
            // be a valid schedule).
            $start = null;
            $end   = null;
            foreach ($sortedTrips as $tt) {
                if ($tt['order_no'] !== $orderNo) {
                    continue;
                }
                $ls = Carbon::parse($tt['loading_start']);
                $re = Carbon::parse($tt['return_end']);
                if ($start === null || $ls->lt($start)) {
                    $start = $ls->copy();
                }
                if ($end === null || $re->gt($end)) {
                    $end = $re->copy();
                }
            }
            if ($start === null || $end === null) {
                continue;
            }

            // Required pump units (capacity + type), expanded by qty.
            $requirements = [];
            foreach ($order->order_pumps as $op) {
                for ($i = 0; $i < (int) $op->qty; $i++) {
                    $requirements[] = [
                        'capacity' => (float) $op->pump_size,
                        'type'     => $op->type,
                    ];
                }
            }
            if (empty($requirements)) {
                Log::info("[PUMP_PRIORITY]   order {$orderNo} pump=yes but order_pumps "
                    . "is empty — skipped (cannot match a pump without capacity/type)");
                continue;
            }

            // Slip buffer: the winner may finish a little later than this
            // idealised window once it competes with other orders for
            // trucks/plants. A losing pump order is parked at this window's end
            // (getNextFreePumpSlot sets its qc_start = reserved end), so we add
            // only a SMALL tail — just enough to clear the winner's typical
            // contention slip — instead of the full max_delay, which would
            // strand the loser ~max_delay minutes after the pump is actually
            // free. Tune PUMP_SLIP_BUFFER_MINS if you see either a re-collision
            // (too small) or a needless gap (too large).
            $slipBuffer = 1 + self::PUMP_SLIP_BUFFER_MINS;

            $pumpOrders[] = [
                'order_no'     => $orderNo,
                'lpi'          => (float) ($order->lpi_score ?? 0),
                'start'        => $start,
                'end'          => $end->copy()->addMinutes($slipBuffer),
                'requirements' => $requirements,
            ];
        }

        // Nothing can contend with fewer than two pump orders.
        if (count($pumpOrders) < 2) {
            Log::info("[PUMP_PRIORITY] only " . count($pumpOrders)
                . " pump order(s) with matchable requirements — no contention to resolve");
            return [];
        }

        // Highest LPI claims pumps first; ties keep their natural ordering.
        // Highest LPI claims pumps first; on an LPI tie the order whose first
        // trip loads earliest claims the pump.
        usort($pumpOrders, function ($a, $b) {
            if ($a['lpi'] !== $b['lpi']) {
                return $b['lpi'] <=> $a['lpi'];          // higher LPI first
            }
            return $a['start']->timestamp <=> $b['start']->timestamp;  // earlier loading first
        });

        // ── Diagnostics: what is this pass actually working with? ──────────
        $inventory = collect($scheduleData->pumps_availability)
            ->map(fn($s) => ($s['pump_capacity'] ?? '?') . '/' . ($s['pump_type'] ?? '?')
                . '#' . ($s['pump_id'] ?? '?'))
            ->implode(', ');
        Log::info("[PUMP_PRIORITY] pass start — " . count($pumpOrders) . " pump order(s); "
            . "available pumps: [{$inventory}]");
        foreach ($pumpOrders as $po) {
            $reqStr = collect($po['requirements'])
                ->map(fn($r) => $r['capacity'] . '/' . $r['type'])
                ->implode(' + ');
            Log::info("[PUMP_PRIORITY]   order {$po['order_no']} LPI={$po['lpi']} "
                . "window=[{$po['start']->format('H:i')}–{$po['end']->format('H:i')}] "
                . "needs=[{$reqStr}]");
        }

        foreach ($pumpOrders as $po) {
            foreach ($po['requirements'] as $req) {
                // Physical pumps that satisfy this requirement (capacity + type).
                $candidates = collect($scheduleData->pumps_availability)
                    ->filter(fn($s) =>
                    (float) $s['pump_capacity'] === (float) $req['capacity']
                        && $s['pump_type'] === $req['type'])
                    ->values();

                // Pick the first candidate that is free across this order's
                // window given reservations already placed in this pre-pass.
                $chosenPumpId = null;
                foreach ($candidates as $cand) {
                    $pid      = $cand['pump_id'];
                    $conflict = false;
                    foreach ($scheduleData->pump_busy_slots_unset as $slot) {
                        if (($slot['pump_id'] ?? null) !== $pid) {
                            continue;
                        }
                        $sStart = Carbon::parse($slot['start']);
                        $sEnd   = Carbon::parse($slot['end']);
                        if ($po['start']->lt($sEnd) && $sStart->lt($po['end'])) {
                            $conflict = true;
                            break;
                        }
                    }
                    if (!$conflict) {
                        $chosenPumpId = $pid;
                        break;
                    }
                }

                // No free matching pump → this (lower-LPI) order lost the
                // contention. Leave it un-reserved; the main loop will delay it.
                if ($chosenPumpId === null) {
                    $matched = collect($scheduleData->pumps_availability)
                        ->filter(fn($s) =>
                        (float) $s['pump_capacity'] === (float) $req['capacity']
                            && $s['pump_type'] === $req['type'])
                        ->count();
                    // Only a genuine contention loss (a matching pump exists but
                    // is busy in this window) needs reordering. If no pump of
                    // this capacity/type exists at all, the pump path is a no-op
                    // and reordering would not help.
                    if ($matched > 0) {
                        $losers[$po['order_no']] = true;
                    }
                    Log::info("[PUMP_PRIORITY]   ✗ order {$po['order_no']} (LPI {$po['lpi']}) "
                        . "could NOT reserve {$req['capacity']}/{$req['type']} — "
                        . ($matched === 0
                            ? "no pump of this capacity/type exists (so pump path is a no-op for it)"
                            : "all {$matched} matching pump(s) busy in its window → scheduled after winner"));
                    continue;
                }

                $scheduleData->pump_busy_slots_unset[] = [
                    'start'    => $po['start']->copy(),
                    'end'      => $po['end']->copy(),
                    'truck_id' => null,
                    'cap'      => null,
                    'order_no' => $po['order_no'],
                    'pump_id'  => $chosenPumpId,
                ];

                Log::info("[PUMP_PRIORITY] reserved pump {$chosenPumpId} for order "
                    . "{$po['order_no']} (LPI {$po['lpi']}) "
                    . "[{$po['start']->format('H:i')}–{$po['end']->format('H:i')}]");
            }
        }

        return array_keys($losers);
    }

    private function getNextFreePumpSlot(ScheduleData $scheduleData, $order, Carbon $start_time): array
    {
        $requirements = [];
        foreach ($order->order_pumps as $op) {
            for ($i = 0; $i < (int) $op->qty; $i++) {
                $requirements[] = [
                    'capacity' => (float) $op->pump_size,
                    'type'     => $op->type,
                ];
            }
        }
        $nullResult = ['pump_id' => null, 'pouring_start' => null, 'pump_qc_start' => null, 'delay_minutes' => 0];
        if (empty($requirements)) {
            return $nullResult;
        }
        $availablePumps = collect($scheduleData->pumps_availability)
            ->filter(function ($slot) use ($requirements) {
                foreach ($requirements as $req) {
                    if (
                        (float) $slot['pump_capacity'] === (float) $req['capacity'] &&
                        $slot['pump_type'] === $req['type']
                    ) {
                        return true;
                    }
                }
                return false;
            })
            ->values();
        if ($availablePumps->isEmpty()) {
            return $nullResult;
        }
        $pumpDispatchOffset = ($order->travel_to_site ?? 0)
            + ($scheduleData->insp_time ?? 0)
            + ($scheduleData->qc_time ?? 0)
            + 3;
        $shiftEnd       = Carbon::parse($scheduleData->shift_end);
        $earliestFree   = null;
        $earliestPumpId = null;
        $earliestOffset = null;
        foreach ($availablePumps as $availPump) {
            $pumpId      = $availPump['pump_id'];
            $pump = Pump::find($pumpId);
            $installTime = $availPump['installation_time'] ?? 10;
            $totalOffset = $pumpDispatchOffset + $installTime + 1;
            $pumpQcStart = $start_time->copy()->subMinutes($totalOffset);
            $pumpBusySlots = collect($scheduleData->pump_busy_slots_unset)
                ->filter(fn($slot) => $slot['pump_id'] === $pumpId)
                ->sortBy(fn($s) => Carbon::parse($s['end'])->timestamp)
                ->values();
            if ($pumpBusySlots->isEmpty()) {
                $freeFrom  = Carbon::parse($availPump['free_from']);
                $candidate = $freeFrom->lte($pumpQcStart) ? $start_time->copy() : null;
            } else {
                $latestEnd   = $pumpBusySlots->max(fn($s) => Carbon::parse($s['end'])->timestamp);
                $lastBusyEnd = Carbon::createFromTimestamp($latestEnd);
                if ($lastBusyEnd->lt($pumpQcStart)) {
                    $candidate = $start_time->copy();
                } else {
                    $candidate = $lastBusyEnd->copy()->addMinutes($totalOffset);
                }
            }
            if ($candidate === null) {
                continue;
            }
            if ($candidate->eq($start_time)) {
                return [
                    'pump_id'        => $pumpId,
                    'pouring_start'  => $start_time->copy(),
                    'pump_qc_start'  => $start_time->copy()->subMinutes($totalOffset),
                    'delay_minutes'  => 0,
                ];
            }
            if ($earliestFree === null || $candidate->lt($earliestFree)) {
                $earliestFree   = $candidate;
                $earliestPumpId = $pumpId;
                $earliestOffset = $totalOffset;
            }
        }
        if ($earliestFree === null) {
            Log::warning("[PUMP_RESCHEDULE] No matching pump free within shift end {$shiftEnd->format('H:i')}");
            return $nullResult;
        }
        $delayMinutes = (int) $start_time->diffInMinutes($earliestFree, false);
        return [
            'pump_id'       => $earliestPumpId,
            'pouring_start' => $earliestFree,
            'pump_qc_start' => $earliestFree->copy()->subMinutes($earliestOffset),
            'delay_minutes' => max(0, $delayMinutes),
        ];
    }
    private function predictBestPlantWorkload(
        ScheduleData $scheduleData,
        $order,
        string $location
    ): ?string {
        // Pre-filter candidates (no collect in loop)
        $candidates = [];
        $seen = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'];
            if (($bp['location'] ?? null) === $location && !isset($seen[$pn])) {
                $candidates[] = $pn;
                $seen[$pn] = true;
            }
        }
        if (empty($candidates)) {
            // Fallback: try all plants
            foreach ($scheduleData->bps_availability as $bp) {
                $pn = $bp['plant_name'];
                if (!isset($seen[$pn])) {
                    $candidates[] = $pn;
                    $seen[$pn] = true;
                }
            }
        }
        if (empty($candidates)) return null;

        $totalTrips      = (int) ceil($order->quantity / max(1, $scheduleData->truck_capacity));
        $loadingDuration = $scheduleData->loading_time;
        $intervalMinutes = max(1, (int) ($order->base_interval ?? $order->interval));

        // Pre-index availability slots by plant name (timestamps for fast comparison)
        $plantSlots = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'];
            $plantSlots[$pn][] = [
                'from_ts' => ($bp['free_from'] instanceof Carbon) ? $bp['free_from']->timestamp : Carbon::parse($bp['free_from'])->timestamp,
                'upto_ts' => ($bp['free_upto'] instanceof Carbon) ? $bp['free_upto']->timestamp : Carbon::parse($bp['free_upto'])->timestamp,
            ];
        }

        // Pre-cache restriction timestamps
        $restrictStart = null;
        $restrictEnd = null;
        if ($scheduleData->restriction_start && $scheduleData->restriction_end) {
            $restrictStart = Carbon::parse($scheduleData->restriction_start)->timestamp;
            $restrictEnd   = Carbon::parse($scheduleData->restriction_end)->timestamp;
        }

        // Count existing load per plant for distribution
        $plantLoad = array_fill_keys($candidates, 0);
        foreach ($scheduleData->plant_busy_slots as $slot) {
            $pn = $slot['plant_id'] ?? null;
            if ($pn && isset($plantLoad[$pn])) {
                $s = ($slot['start'] instanceof Carbon) ? $slot['start']->timestamp : Carbon::parse($slot['start'])->timestamp;
                $e = ($slot['end'] instanceof Carbon) ? $slot['end']->timestamp : Carbon::parse($slot['end'])->timestamp;
                $plantLoad[$pn] += (int)(($e - $s) / 60);
            }
        }

        $loadingSecs     = $loadingDuration * 60;
        $intervalSecs    = $intervalMinutes * 60;
        $bestPlant = null;
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $plantName) {
            $slots = $plantSlots[$plantName] ?? [];
            if (empty($slots)) continue;

            $tripsOk    = 0;
            $totalDelay = 0;
            $loadingStartTs = $scheduleData->loading_start->timestamp;

            for ($t = 1; $t <= min($totalTrips, 50); $t++) { // cap at 50 trips for prediction
                $slotFound = false;
                $slotEndTs = $loadingStartTs + $loadingSecs;

                // Try original time first, then jump to next free slot
                for ($delay = 0; $delay <= 120; $delay++) { // cap delay scan at 120 (was 720)
                    $startTs = $loadingStartTs + ($delay * 60);
                    $endTs   = $startTs + $loadingSecs;

                    // Restriction check
                    if ($restrictStart !== null && $startTs < $restrictEnd && $endTs > $restrictStart) {
                        continue;
                    }

                    // Check if any slot covers this window (fast timestamp comparison)
                    foreach ($slots as $s) {
                        if ($s['from_ts'] <= $startTs && $s['upto_ts'] >= $endTs) {
                            $totalDelay += $delay;
                            $tripsOk++;
                            $loadingStartTs = $startTs + $intervalSecs;
                            $slotFound = true;
                            break 2; // break both inner loops
                        }
                    }
                }
                if (!$slotFound) break;
            }

            $existingLoad  = $plantLoad[$plantName] ?? 0;
            $locationBonus = (($scheduleData->bps_availability[0]['location'] ?? '') === $location) ? 500 : 0;
            $score = ($tripsOk * 1000) - $totalDelay - ($existingLoad * 2) + $locationBonus;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPlant = $plantName;
            }
        }
        return $bestPlant;
    }
    private function predictBestPlant(
        ScheduleData $scheduleData,
        $order,
        string $location,
        int $totalOrdersQty = 0
    ): ?string {
        // Pre-filter candidates (no collect in loop)
        $candidates = [];
        $seen = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'];
            if (($bp['location'] ?? null) === $location && !isset($seen[$pn])) {
                $candidates[] = $pn;
                $seen[$pn] = true;
            }
        }
        if (empty($candidates)) {
            // Fallback: try all plants
            foreach ($scheduleData->bps_availability as $bp) {
                $pn = $bp['plant_name'];
                if (!isset($seen[$pn])) {
                    $candidates[] = $pn;
                    $seen[$pn] = true;
                }
            }
        }
        if (empty($candidates)) return null;

        $totalTrips      = (int) ceil($order->quantity / max(1, $scheduleData->truck_capacity));
        $loadingDuration = $scheduleData->loading_time;
        $intervalMinutes = max(1, (int) ($order->base_interval ?? $order->interval));

        // Pre-index availability slots by plant name (timestamps for fast comparison)
        $plantSlots = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'];
            $plantSlots[$pn][] = [
                'from_ts' => ($bp['free_from'] instanceof Carbon) ? $bp['free_from']->timestamp : Carbon::parse($bp['free_from'])->timestamp,
                'upto_ts' => ($bp['free_upto'] instanceof Carbon) ? $bp['free_upto']->timestamp : Carbon::parse($bp['free_upto'])->timestamp,
            ];
        }

        // Pre-cache restriction timestamps
        $restrictStart = null;
        $restrictEnd = null;
        if ($scheduleData->restriction_start && $scheduleData->restriction_end) {
            $restrictStart = Carbon::parse($scheduleData->restriction_start)->timestamp;
            $restrictEnd   = Carbon::parse($scheduleData->restriction_end)->timestamp;
        }

        $loadingSecs     = $loadingDuration * 60;
        $intervalSecs    = $intervalMinutes * 60;

        // ── First-fit by plant order ──────────────────────────────────────
        // Walk the plants in their availability order (plant 1, then plant 2,
        // then plant 3, …) and return the FIRST one that is FREE for this
        // order — i.e. can accommodate ALL of its trips. Only when a plant
        // cannot take the order do we move on to the next. If no plant can take
        // the whole order, fall back to the earliest plant that fits the most
        // trips (and finally to the first candidate).
        $fallbackPlant = null;
        $fallbackTrips = -1;

        foreach ($candidates as $plantName) {
            $slots = $plantSlots[$plantName] ?? [];
            if (empty($slots)) continue;

            $tripsOk        = 0;
            $loadingStartTs = $scheduleData->loading_start->timestamp;

            for ($t = 1; $t <= min($totalTrips, 50); $t++) { // cap at 50 trips for prediction
                $slotFound = false;

                // Try original time first, then jump forward to the next free slot.
                for ($delay = 0; $delay <= 120; $delay++) {
                    $startTs = $loadingStartTs + ($delay * 60);
                    $endTs   = $startTs + $loadingSecs;

                    // Restriction check
                    if ($restrictStart !== null && $startTs < $restrictEnd && $endTs > $restrictStart) {
                        continue;
                    }

                    foreach ($slots as $s) {
                        if ($s['from_ts'] <= $startTs && $s['upto_ts'] >= $endTs) {
                            $tripsOk++;
                            $loadingStartTs = $startTs + $intervalSecs;
                            $slotFound = true;
                            break 2;
                        }
                    }
                }
                if (!$slotFound) break;
            }

            // FREE = this plant can take the WHOLE order. First such plant wins.
            if ($tripsOk >= min($totalTrips, 50)) {
                return $plantName;
            }

            // Not free — keep the earliest plant with the best partial fit as a
            // fallback in case no plant can take the whole order.
            if ($tripsOk > $fallbackTrips) {
                $fallbackTrips = $tripsOk;
                $fallbackPlant = $plantName;
            }
        }

        // No plant could take the whole order → earliest best-partial plant
        // (or simply the first candidate).
        return $fallbackPlant ?? $candidates[0];
    }
    private function nextFreeMinutes(array $pool, $loadingStart): int
    {
        $lsTs = ($loadingStart instanceof Carbon) ? $loadingStart->timestamp : Carbon::parse($loadingStart)->timestamp;
        $smallestGap = null;
        foreach ($pool as $row) {
            if (!isset($row['free_from'])) continue;
            $ffTs = ($row['free_from'] instanceof Carbon) ? $row['free_from']->timestamp : Carbon::parse($row['free_from'])->timestamp;
            if ($ffTs <= $lsTs) return 1;
            $gap = (int)(($ffTs - $lsTs) / 60);
            if ($smallestGap === null || $gap < $smallestGap) {
                $smallestGap = $gap;
            }
        }
        return max(1, $smallestGap ?? 1);
    }

    private function simulatePlantAssignment(ScheduleData $scheduleData, $feasibleOrders, int $shiftMinutes): array
    {
        $plantNames = collect($scheduleData->bps_availability)
            ->pluck('plant_name')
            ->unique()
            ->values()
            ->toArray();
        $plantLocations = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $plantLocations[$bp['plant_name']] = $bp['location'] ?? null;
        }
        $totalPlants = count($plantNames);
        $sorted = $feasibleOrders->sortBy(function ($o) {
            $pumpBoost = ($o->pump ?? false) ? 0 : 1;
            $lpiInv    = 100000 - (float) ($o->lpi_score ?? 0);
            $qtyInv    = 100000 - (int) ($o->quantity ?? 0);
            return sprintf('%d-%010.2f-%010d', $pumpBoost, $lpiInv, $qtyInv);
        })->values();
        $plantBuckets = [];
        $accepted = collect();
        $rejected = [];
        foreach ($sorted as $order) {
            $loadingTime      = (int) ($order->loading_time ?? ConstantHelper::LOADING_TIME);
            $maxInterval      = (int) ($order->base_interval ?? ($order->interval + $order->pouring_time));
            $tolerancePercent = (float) ($order->tolerance ?? 10);
            $toleranceBuffer  = (int) ceil($maxInterval * ($tolerancePercent / 100));
            $effectiveInterval = $maxInterval + $toleranceBuffer;
            $truckCapacity    = self::DEFAULT_TRUCK_CAPACITY;
            $totalTrips       = (int) ceil($order->quantity / max(1, $truckCapacity));
            $totalLoadingMins = $totalTrips * $loadingTime;
            $orderLocation    = $order->location ?? null;
            $orderWindow = $this->estimateOrderWindow($order, $scheduleData, $totalTrips, $loadingTime);
            $assigned   = false;
            $bestBucket = null;
            $bestSlack  = -1;
            foreach ($plantBuckets as $plantName => &$bucket) {
                $sameLocation = ($bucket['location'] === $orderLocation)
                    || empty($orderLocation)
                    || empty($bucket['location']);
                $hasOverlap = false;
                $cycleFits  = true;
                $worstSlack = $effectiveInterval;
                foreach ($bucket['order_windows'] as $idx => $window) {
                    if ($this->windowsOverlap($orderWindow, $window)) {
                        $hasOverlap = true;
                        // Pairwise check: can THIS order + EACH existing order
                        // fit their combined loading within the larger of their intervals?
                        $existingLoading  = $bucket['order_loading_times'][$idx];
                        $existingInterval = $bucket['order_intervals'][$idx];
                        // Use max of both intervals (scheduler can interleave across cycles)
                        // plus tolerance buffer (scheduler can shift within tolerance)
                        $pairInterval = max($effectiveInterval, $existingInterval);
                        $pairLoading  = $loadingTime + $existingLoading;
                        if ($pairLoading > $pairInterval) {
                            $cycleFits = false;
                            break;
                        }
                        $pairSlack = $pairInterval - $pairLoading;
                        $worstSlack = min($worstSlack, $pairSlack);
                    }
                }
                $shiftFits = ($bucket['total_loading'] + $totalLoadingMins <= $shiftMinutes);
                if ($cycleFits && $shiftFits) {
                    $slack = $hasOverlap ? $worstSlack : $effectiveInterval;
                    $score = ($sameLocation ? 100000 : 0) + $slack;
                    if ($score > $bestSlack) {
                        $bestSlack  = $score;
                        $bestBucket = $plantName;
                    }
                }
            }
            unset($bucket);
            if ($bestBucket !== null) {
                $plantBuckets[$bestBucket]['orders'][]            = $order->order_no;
                $plantBuckets[$bestBucket]['order_windows'][]     = $orderWindow;
                $plantBuckets[$bestBucket]['order_loading_times'][] = $loadingTime;
                $plantBuckets[$bestBucket]['order_intervals'][]   = $effectiveInterval;
                $plantBuckets[$bestBucket]['total_loading']      += $totalLoadingMins;
                $plantBuckets[$bestBucket]['cycle_loading']   += $loadingTime;
                $plantBuckets[$bestBucket]['min_max_interval'] = min(
                    $plantBuckets[$bestBucket]['min_max_interval'],
                    $effectiveInterval
                );
                $assigned = true;
            }
            if (!$assigned) {
                $candidatePlants = [];
                foreach ($plantNames as $pn) {
                    if (!isset($plantBuckets[$pn])) {
                        $loc = $plantLocations[$pn] ?? null;
                        $sameLocation = ($loc === $orderLocation) || empty($orderLocation) || empty($loc);
                        $candidatePlants[] = ['name' => $pn, 'same_loc' => $sameLocation];
                    }
                }
                usort($candidatePlants, fn($a, $b) => ($b['same_loc'] <=> $a['same_loc']));
                foreach ($candidatePlants as $cp) {
                    if ($totalLoadingMins <= $shiftMinutes) {
                        $plantBuckets[$cp['name']] = [
                            'orders'             => [$order->order_no],
                            'order_windows'      => [$orderWindow],
                            'order_loading_times' => [$loadingTime],
                            'order_intervals'    => [$effectiveInterval],
                            'cycle_loading'      => $loadingTime,
                            'min_max_interval'   => $effectiveInterval,
                            'total_loading'      => $totalLoadingMins,
                            'location'           => $plantLocations[$cp['name']] ?? null,
                        ];
                        $assigned = true;
                        break;
                    }
                }
            }
            if ($assigned) {
                $accepted->push($order);
            } else {
                $reason = "This order could not be scheduled because all available plants are at full capacity.";
                $rejected[$order->id] = $reason;
                Log::info("Order {$order->order_no} (LPI: {$order->lpi_score}) rejected — no available plant can accommodate this order.");
            }
        }
        foreach ($plantBuckets as $pn => $b) {
            Log::info("[PLANT_SIM] {$pn}: orders=[" . implode(',', $b['orders']) . "] "
                . "cycle_loading={$b['cycle_loading']}min min_max_interval={$b['min_max_interval']}min "
                . "total_loading={$b['total_loading']}min");
        }
        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }
    private function estimateOrderWindow($order, ScheduleData $scheduleData, int $totalTrips, int $loadingTime): array
    {
        $pouringTime  = (int) ($order->pouring_time   ?? 0);
        $travelTime   = (int) ($order->travel_to_site ?? 0);
        $returnTime   = (int) ($order->return_to_plant ?? 0);
        $qcTime       = (int) $scheduleData->qc_time;
        $inspTime     = (int) $scheduleData->insp_time;
        $cleaningTime = (int) $scheduleData->cleaning_time;
        $hasPump      = (bool) ($order->pump ?? false);
        $isFlexible   = (bool) ($order->flexibility ?? false);

        // Use base_interval set by calculateTolerance (pouring-based for pump orders)
        $interval = (int) ($order->base_interval ?? 0);

        // Pre-pouring time: loading + QC + travel + inspection + buffer
        $prePouringTime = $loadingTime + $qcTime + $travelTime + $inspTime + 4;

        // Trip 1 loading start: back-calculate from delivery time
        $trip1LoadingStart = Carbon::parse($order->delivery_date)->subMinutes($prePouringTime);

        if ($hasPump && $totalTrips > 1) {
            // Client doc: schedule based on pouring interval, not loading interval
            // Truck should arrive ~5 min before pump is free (non-flexible)
            // or ~10 min before for flexible/slab orders
            $siteBuffer = $isFlexible ? 5 : 1;

            // Pouring-based offset: each subsequent trip loads so the truck
            // arrives (siteBuffer) minutes before the previous trip finishes pouring
            // Gap between loadings = pouringInterval - siteBuffer (pre-pouring already accounts for travel)
            $loadingGap = max($interval - $siteBuffer, $loadingTime);

            $lastTripOffset = ($totalTrips - 1) * $loadingGap;
        } else {
            // Non-pump or single trip: use base_interval directly
            $lastTripOffset = ($totalTrips > 1)
                ? ($totalTrips - 1) * $interval
                : 0;
        }

        $lastTripLoadingStart = $trip1LoadingStart->copy()->addMinutes($lastTripOffset);

        $fullCycleMins = $loadingTime + $qcTime + $travelTime + $inspTime
            + $pouringTime + $cleaningTime + $returnTime + 6;

        $lastTripReturnEnd = $lastTripLoadingStart->copy()->addMinutes($fullCycleMins);

        return [
            'start' => $trip1LoadingStart,
            'end'   => $lastTripReturnEnd,
        ];
    }
    private function windowsOverlap(array $windowA, array $windowB): bool
    {
        return $windowA['start']->lt($windowB['end'])
            && $windowB['start']->lt($windowA['end']);
    }
}