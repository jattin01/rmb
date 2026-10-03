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
    // ── Trial (feasibility-probe) mode ───────────────────────────────────────
    // The incremental scheduler re-runs the whole placement dozens of times per
    // order while searching for a working start-delay. During those TRIAL passes
    // we only need to know whether each order delivered fully / failed — not the
    // full schedule. When trial_mode is true the scheduler:
    //   • skips the bulky selected_order_schedules / pump-schedule inserts and
    //     the BatchingPlantAvailability output rows (and their deletes),
    //   • persists ONLY the lightweight selected_orders summary that
    //     detectPartialOrders()/detectRejectedOrders() read,
    //   • skips the standby-pump pass.
    // The final, committed pass runs with trial_mode = false and writes
    // everything for real. This removes the per-probe DB row churn that made
    // large runs (50+ orders) slow.
    public $trial_mode = false;
    // ── Per-run trip cache ───────────────────────────────────────────────────
    // generateAllOrderTrips() builds each order's trips keyed by
    // "order_id:effectivePumps". The trip layout only depends on the order's own
    // fields, the run-constant stage times, and the lane count — and the lane
    // count (effectivePumps) is 1 for every non-pump / single-pump order and only
    // varies for MULTI-pump orders when the open-plant count changes. So when a
    // plant opens mid-run and trips are regenerated, only the multi-pump orders
    // whose lane count actually changed are rebuilt; everything else is reused.
    // Scoped to one schedule run (ScheduleData is created fresh per run).
    public $trip_cache = [];
    // ── Plant-opening control (req_plants driven) ────────────────────────────
    // opened_plant_count : how many DISTINCT plants are currently open and being
    //                      scheduled on (starts at max(req_plants) across orders).
    // max_plant_count    : total distinct plants in the full fleet (hard ceiling).
    // divide_mode        : true when the whole run's ordered qty > 500. Controls
    //                      WHEN we open an extra plant — on a PARTIAL (divide)
    //                      vs on a hard REJECT (consolidate, qty <= 500).
    // req_plants_max     : max req_plants across all orders (for logging/audit).
    public $opened_plant_count;
    public $max_plant_count;
    public $divide_mode;
    public $req_plants_max;
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
    const INCR_DELAY_PROBE_BASE_MINS = 1; // first geometric probe step (then doubles)
    const INCR_DELAY_MAX_PROBES    = 12;  // hard cap on probe schedule passes
    const INCR_DELAY_MAX_REFINE    = 8;   // hard cap on binary-refine schedule passes
    // Grace tolerance (minutes) on the per-order delay-limit check. The hard
    // limit is expected_duration + max_delay. A trip whose elapsed span exceeds
    // that limit by no more than this grace is still accepted at its current
    // (earlier) start instead of being flagged a partial. Without it a near-miss
    // — e.g. a single-pump order whose OWN duration lands ~1 min over its limit
    // at its ORIGINAL delivery time — is treated as a partial and pushed into the
    // delay search, which then postpones it by a large amount even though
    // delaying cannot shrink the order's structural duration (the start just
    // moves later). Keep this small: it only forgives marginal overruns, it does
    // NOT widen max_delay for genuinely late orders. Tune to taste.
    const DELAY_LIMIT_GRACE_MINS   = 0;
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

    // Highest progress % written for the current run (monotonic; never goes back).
    private int $lastProgress = 0;

    public function __construct()
    {
        ini_set('max_execution_time', '-1');
        $this->pumpHelper           = new PumpHelper();
        $this->transitMixerHelper   = new TransitMixerHelper();
        $this->batchingPlantHelper  = new BatchingPlantHelper();
        $this->restrictionHelper    = new TransitMixerRestrictionHelper();
    }

    /**
     * Dedicated single-file logger for the scheduler. Every Log call in this
     * class writes to storage/logs/scheduling.log, which initializeSchedule()
     * truncates at the start of each run — so the file always holds exactly the
     * latest run and nothing older. Static so it works from the static helper
     * methods too; cached so the stream is opened once per run.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    private static $schedLogger = null;

    /**
     * Plant pinning. Once a candidate is COMMITTED, the plant it was accepted
     * on is remembered here and re-used on every later pass instead of
     * re-running predictBestPlant() — which would otherwise be free to move an
     * already-accepted order onto a different plant each time a new candidate
     * is tested.
     *
     * @var array<string,string> order_no => plant_name
     */
    private $pinnedPlant = [];

    /** Plant chosen per order during the CURRENT pass. @var array<string,string> */
    private $passPlantChoice = [];

    private static function schedLog()
    {
        if (self::$schedLogger === null) {
            self::$schedLogger = Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/scheduling.log'),
            ]);
        }
        return self::$schedLogger;
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
            // Reset the dedicated scheduling log for this run. Truncating it
            // (instead of deleting the shared laravel.log) keeps the rest of the
            // app's logging intact and leaves only the latest run's lines behind.
            self::$schedLogger = null; // force a fresh handle for this run
            $this->lastProgress = 0;
            $shift_end = Carbon::parse($shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME);
            $this->clearPreviousSchedules($company, $user_id, $schedule_date);
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

            // ── Distinct-plant accounting for the full fleet ─────────────────
            // bps_availability can contain several free-window rows for the SAME
            // plant, so the fleet size is the count of DISTINCT plant names, not
            // the raw array length.
            $distinctPlantNames = array_values(array_unique(array_column($bpsFull, 'plant_name')));
            $maxPlantCount      = count($distinctPlantNames);

            // divide_mode: a "big" run (> 500 m³ total). It changes WHEN we open
            // an extra plant later — on a PARTIAL order (divide) rather than on a
            // hard REJECT (consolidate, <= 500).
            $hasCriticalOrder = SelectedOrder::where("group_company_id", $company)
                ->where("user_id", $user_id)
                ->whereBetween("delivery_date", [$shift_start, $shift_end])
                ->whereNull("start_time")
                ->where("selected", true)
                ->where("is_critical", true)
                ->exists();

            $divideMode = ($totalOrdersQty > self::PLANT_DIVIDE_QTY_THRESHOLD)
                || $hasCriticalOrder;



            // ── Diagnostics: what does the scheduler actually see? ───────────
            // Plants with no location_shift row, not serving the order location,
            // or not selected for this run won't appear here and so can't be
            // opened — check those first if the fleet looks smaller than expected.
            $plantSummary = collect($bpsFull)
                ->map(fn($p) => ($p['plant_name'] ?? '?') . '@' . ($p['location'] ?? '?'))
                ->implode(', ');
            self::schedLog()->info("[PLANT_SETUP] total_qty={$totalOrdersQty} "
                . "distinct_plants={$maxPlantCount} [{$plantSummary}] "
                . "selected_plant_ids=[" . implode(',', $batching_plant_ids) . "] "
                . "divide_mode=" . ($divideMode ? 'YES (>500 → open on PARTIAL)' : 'NO (<=500 → open on REJECT)'));

            // Build on the FULL fleet first; bps_availability is resized down to
            // the req_plants-derived opened set right after Phase 1 below.
            $scheduleData = new ScheduleData([
                'user_id'           => $user_id,
                'company'           => $company,
                'schedule_date'     => $schedule_date,
                'sch_adj_from'      => 0,
                'sch_adj_to'        => 1440,
                'tms_availability'  => $tmsAvailability,
                'pumps_availability' => $this->pumpHelper->getPumpsAvailability($company, $schedule_date, $pump_ids),
                'bps_availability'  => $bpsFull,
                'bps_availability_full' => $bpsFull,
                'consolidated_single_plant' => false,
                'opened_plant_count' => $maxPlantCount,
                'max_plant_count'    => $maxPlantCount,
                'divide_mode'        => $divideMode,
                'req_plants_max'     => 1,
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

            // LPI (lpi_score) is computed and persisted at order-selection time
            // by OrderController; the scheduler reads the stored value below.

            // ── Phase 1: read max req_plants across orders ───────────────────
            // req_plants is persisted at order-selection time by OrderController;
            // here we only read the max to size how many plants to open.
            $maxReqPlants =  $totalOrdersQty > self::PLANT_DIVIDE_QTY_THRESHOLD ? $this->getMaxReqPlants($scheduleData) : 1;

            // ── Phase 2: open max(req_plants) plants to start with ────────────
            // Capped at the number of distinct plants actually available. The
            // remaining plants stay closed and are opened one at a time later,
            // on a REJECT (<=500) or a PARTIAL (>500), inside the incremental
            // scheduler.
            $openedPlantCount = max(1, min($maxReqPlants, $maxPlantCount));
            $scheduleData->req_plants_max     = $maxReqPlants;
            $scheduleData->opened_plant_count = $openedPlantCount;
            $scheduleData->bps_availability   = $this->firstNDistinctPlants($bpsFull, $openedPlantCount);
            // "consolidated" now just means: not every plant is open yet, so the
            // scheduler may still open more.
            $scheduleData->consolidated_single_plant = ($openedPlantCount < $maxPlantCount);

            self::schedLog()->info("[PLANT_SETUP] req_plants_max={$maxReqPlants} → opening {$openedPlantCount} "
                . "of {$maxPlantCount} plant(s) to start ("
                . ($openedPlantCount < $maxPlantCount ? "can open more on "
                    . ($divideMode ? "PARTIAL" : "REJECT") : "full fleet already open")
                . ")");

            // ── Pump usage & pump-aware durations ────────────────────────────
            // Basis for "usable pumps":
            //   • <=500 (consolidate): the plants currently OPENED (opened_plant_count).
            //     Recomputed whenever a plant opens during scheduling.
            //   • >500  (divide): ALL available plants (maxPlantCount).
            // Persisted so fetchOrders() (inside generateSchedule) reads the updates.
            $pumpPlantBasis = $divideMode ? $maxPlantCount : $openedPlantCount;
            self::schedLog()->info("[PUMP_DUR] usable-pump basis = "
                . ($divideMode ? "all plants ({$maxPlantCount})" : "opened plants ({$openedPlantCount})"));
            $this->recalcPumpDurations($scheduleData, $pumpPlantBasis);

            $this->generateSchedule($scheduleData);
            //$conflicts = ScheduleService::validateAllResourceConflicts($scheduleData);
            //self::schedLog()->info('After optimise Schedule Conflicts:', $conflicts);
            //$this->checkScheduleTimes($scheduleData);
        } catch (\Exception $e) {
            if (!$scheduleData->is_completed && !$scheduleData->failure_reason) {
                $scheduleData->failure_reason = "Unable to schedule within constraints";
            }
            self::schedLog()->error('Schedule Initialization Error: ' . $e->getTraceAsString());
        }
    }

    /**
     * Best-effort progress reporting for the front-end progress bar.
     * Writes a 1–99% value onto the active (processing) schedule_runs row for
     * this company + user + date. Never decreases, and 100% is left for the
     * job to set once the run is fully marked completed. Failures here must
     * never break scheduling, so everything is wrapped in a try/catch.
     */
    private function reportProgress(ScheduleData $scheduleData, int $done, int $total): void
    {
        if ($total < 1) {
            return;
        }

        $percent = (int) floor(($done / $total) * 100);
        $percent = max(1, min(99, $percent)); // 100 is set by the job on completion

        if ($percent <= $this->lastProgress) {
            return; // monotonic — never move the bar backwards
        }
        $this->lastProgress = $percent;

        try {
            DB::table('schedule_runs')
                ->where('group_company_id', $scheduleData->company)
                ->where('user_id', $scheduleData->user_id)
                ->where('schedule_date', $scheduleData->schedule_date)
                ->where('status', 'processing')
                ->update([
                    'progress'   => $percent,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            self::schedLog()->warning('[PROGRESS] update failed: ' . $e->getMessage());
        }
    }

    private function clearPreviousSchedules($company, $user_id, $delivery_date, bool $trial = false): void
    {
        $orderIds = SelectedOrder::where('group_company_id', $company)
            ->whereDate('delivery_date', $delivery_date)
            ->where('user_id', $user_id)
            ->pluck('id');

        if (!$trial) {
            SelectedOrderSchedule::where('group_company_id', $company)
                ->where('user_id', $user_id)
                ->whereIn('order_id', $orderIds)
                ->delete();

            SelectedOrderPumpSchedule::where('group_company_id', $company)
                ->where('user_id', $user_id)
                ->whereIn('order_id', $orderIds)
                ->delete();

            BatchingPlantAvailability::where('group_company_id', $company)
                ->where('user_id', $user_id)
                // ->whereIn('order_id', $orderIds) // only if this table has order_id
                ->delete();
        }

        SelectedOrder::where('group_company_id', $company)
            ->whereDate('delivery_date', $delivery_date)
            ->where('user_id', $user_id)
            ->update([
                'start_time' => null,
                'end_time' => null,
                'deviation' => null,
                'delivered_quantity' => 0,
                'location' => null,
                'failure_reason' => null,
                'standby_pump' => 0,
            ]);
    }
    public function generateSchedule(ScheduleData &$scheduleData)
    {
        try {
            $this->initializeVariables($scheduleData);
            $allOrders = $this->fetchOrders($scheduleData);
            $this->getLocations($allOrders, $scheduleData);

            self::schedLog()->info("[INCR_SCHED] ── Step 0: feasibility filter on " . $allOrders->count() . " orders ──");
            if ($allOrders->isEmpty()) {
                self::schedLog()->warning("[INCR_SCHED] No orders passed the feasibility filter — nothing to schedule.");
                return;
            }

            // Highest LPI first — these get admitted (and protected) before lower ones.
            $allOrders = $allOrders->sortByDesc('lpi_score')->values();

            // Snapshot clean pools ONCE. bps_availability here is the consolidated
            // (single-plant) set for small runs, or the full fleet otherwise.
            $pristine = $this->snapshotPools($scheduleData);

            // Generate trips ONCE for every order; each pass just filters to the set under test.
            $allTrips = $this->generateAllOrderTrips($scheduleData, $allOrders);
            self::schedLog()->info("[INCR_SCHED] generated " . count($allTrips) . " trip(s) total");

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
                    self::schedLog()->warning("[INCR_SCHED] could not save failure_reason for order_id={$orderId}: " . $e->getMessage());
                }
            }
        } catch (\Exception $ex) {
            self::schedLog()->error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }
    /**
     * Incremental scheduler with plant-expansion RESTART-FROM-FRESH.
     *
     * The run starts on max(req_plants) plants (Phase 2). If, while plants are
     * still closed, a candidate triggers the expand condition, scheduleCandidate-
     * AtDelay() opens ONE more plant and reports expanded=true. The expand
     * condition depends on run size:
     *   • divide_mode (> 500 m³): a PARTIAL order opens another plant.
     *   • else        (<= 500)  : a hard REJECT opens another plant.
     *
     * The moment a plant opens, every decision made on the narrower fleet is
     * stale, so this wrapper DISCARDS the in-progress pass — committed set,
     * committed delays, rejections — and re-runs the WHOLE incremental schedule
     * from scratch on the now-wider fleet. Every order is re-evaluated from its
     * ORIGINAL time (no carried-over delays); the delay search re-derives any
     * delays needed on the wider fleet.
     *
     * Each expansion bumps opened_plant_count by 1 (on both $scheduleData and
     * $pristine) and is bounded by max_plant_count, so this loop runs at most
     * (max_plant_count − initial_opened + 1) times. Once every plant is open the
     * expand trigger is skipped and the normal delay-retry / reject path takes
     * over. Trip generation is plant-independent, so $allTrips is reused as-is;
     * "fresh" means the scheduling decisions restart, not the trip timetable.
     *
     * @return array{committed:\Illuminate\Support\Collection, rejected:array}
     */
    private function runIncrementalSchedule(ScheduleData &$scheduleData, $allOrders, array $allTrips, array $pristine): array
    {
        $committed       = collect();   // orders that schedule cleanly together so far
        $rejected        = [];          // order_id => reason
        $committedDelays = [];          // order_no => accepted delay in minutes
        $this->pinnedPlant = [];        // fresh run: nothing pinned yet

        $candidatesList = $allOrders->values();
        $candidateIdx   = 0;

        // NOTE: unlike a full restart, a mid-pass plant expansion here does
        // NOT reset $committed / $committedDelays and does NOT go back to
        // the top of the candidate list. It only rewinds $candidateIdx back
        // to the SAME candidate that triggered the expansion (which then
        // gets retried from delay 0 / its original time on the wider
        // fleet), via `continue` without incrementing $candidateIdx. Every
        // already-committed candidate and its accepted delay is kept as-is.
        while ($candidateIdx < $candidatesList->count()) {
            $candidate = $candidatesList[$candidateIdx];

            // Progress = orders resolved so far (committed + rejected) / total.
            // Best-effort; drives the front-end progress bar.
            $this->reportProgress(
                $scheduleData,
                $committed->count() + count($rejected),
                $candidatesList->count()
            );

            // Test set = everything accepted so far + this candidate.
            $testOrders   = $committed->concat([$candidate])->values();
            $testOrderNos = $testOrders->pluck('order_no')->all();

            // Ceiling on how far we may postpone this candidate's START (first
            // trip). Postponing the start is allowed up to the global 8-hour
            // ceiling (INCR_MAX_DELAY_MINUTES) REGARDLESS of the order's
            // max_delay — max_delay only bounds the POUR SPAN once pouring
            // begins (enforced per-trip by the delay-limit check in
            // scheduleTripsChronologically: pouring_end − first_pouring_start ≤
            // expected_duration + max_delay). So a late start is fine as long
            // as the pour itself still completes within tolerance.
            $maxDelay = self::INCR_MAX_DELAY_MINUTES;

            self::schedLog()->info("[INCR_SCHED] testing candidate {$candidate->order_no} "
                . "(LPI {$candidate->lpi_score}) with committed=["
                . $committed->pluck('order_no')->implode(',') . "]");

            // ── Attempt 0: no delay ──────────────────────────────────────
            $res = $this->scheduleCandidateAtDelay(
                $scheduleData,
                $pristine,
                $allTrips,
                $testOrders,
                $testOrderNos,
                $candidate,
                $committedDelays,
                0
            );
            if ($res['expanded']) {
                [$allOrders, $allTrips, $candidatesList, $candidateIdx, $committed] =
                    $this->rebuildAfterPlantExpansion($scheduleData, $allOrders, $allTrips, $candidate, $committed);
                self::schedLog()->info("[PLANT_EXPAND] retrying {$candidate->order_no} on the wider fleet — "
                    . $committed->count() . " already-committed order(s) kept as-is (no full reset).");
                continue;
            }
            $scope = "on the available plant(s)";

            if ($res['candidateRejected']) {
                $rejected[$candidate->id] = $res['candidateReason']
                    ?? "This order could not be scheduled because all available plants are at full capacity.";
                self::schedLog()->info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — appeared in rejectedOrders {$scope} "
                    . "— a delay cannot help; committed set unchanged");
                $candidateIdx++;
                continue;
            }
            if (empty($res['partials'])) {
                $committed = $testOrders;
                $this->pinCommittedPlants($committed, $this->passPlantChoice);
                self::schedLog()->info("[INCR_SCHED] ✓ {$candidate->order_no} accepted "
                    . "(committed now " . $committed->count() . " order(s))");
                $candidateIdx++;
                continue;
            }

            // ── Predict a working delay: geometric probe, then binary refine.
            self::schedLog()->info("[INCR_DELAY] candidate {$candidate->order_no} caused "
                . count($res['partials']) . " partial order(s) {$scope} at +0 min — "
                . "predicting a working delay (search up to +{$maxDelay} min).");

            $probes     = 0;
            $lastFail   = 0;
            $okDelay    = null;
            $rejectedAt = null;
            $expanded   = false;
            // Plant choices from the last CLEAN pass — the refine loop may end
            // on a dirty pass, so snapshot at each clean point rather than
            // trusting whatever the final pass happened to leave behind.
            $cleanPlantChoice = [];
            $step       = max(1, self::INCR_DELAY_PROBE_BASE_MINS);
            $delay      = $step;
            // Remember the candidate's OWN recorded reason (e.g. a pump
            // message) from the trial passes, so a final reject reports the
            // real cause instead of the generic "all plants at full capacity".
            $candidateReasonSeen = $this->candidatePartialReason($res['partials'], $candidate);

            // PROBE
            while ($delay <= $maxDelay && $probes < self::INCR_DELAY_MAX_PROBES) {
                $probes++;
                $r = $this->scheduleCandidateAtDelay(
                    $scheduleData,
                    $pristine,
                    $allTrips,
                    $testOrders,
                    $testOrderNos,
                    $candidate,
                    $committedDelays,
                    $delay
                );
                if ($r['expanded']) {
                    $expanded = true;
                    break;
                }
                if ($r['candidateRejected']) {
                    self::schedLog()->info("[INCR_DELAY] probe +{$delay} min → candidate hard-rejected; "
                        . "further delay cannot help. Stopping probe.");
                    $rejectedAt = $r;
                    break;
                }
                if (empty($r['partials'])) {
                    $okDelay = $delay;
                    $cleanPlantChoice = $this->passPlantChoice;
                    self::schedLog()->info("[INCR_DELAY] probe +{$delay} min → clean (0 partials); "
                        . "bracketed a working delay, refining toward the minimum.");
                    break;
                }
                self::schedLog()->info("[INCR_DELAY] probe +{$delay} min → still "
                    . count($r['partials']) . " partial(s); growing step.");
                $seenReason = $this->candidatePartialReason($r['partials'], $candidate);
                if ($seenReason !== null) { $candidateReasonSeen = $seenReason; }
                $lastFail = $delay;
                $delay   += $step;
                $step    *= 2;
            }
            if ($expanded) {
                [$allOrders, $allTrips, $candidatesList, $candidateIdx, $committed] =
                    $this->rebuildAfterPlantExpansion($scheduleData, $allOrders, $allTrips, $candidate, $committed);
                self::schedLog()->info("[PLANT_EXPAND] retrying {$candidate->order_no} on the wider fleet "
                    . "(opened mid probe) — " . $committed->count() . " already-committed order(s) kept as-is.");
                continue;
            }

            // Geometric growth may have jumped past the cap without testing it.
            if (
                $okDelay === null && $rejectedAt === null
                && $lastFail < $maxDelay && $probes < self::INCR_DELAY_MAX_PROBES
            ) {
                $probes++;
                $r = $this->scheduleCandidateAtDelay(
                    $scheduleData,
                    $pristine,
                    $allTrips,
                    $testOrders,
                    $testOrderNos,
                    $candidate,
                    $committedDelays,
                    $maxDelay
                );
                if ($r['expanded']) {
                    $expanded = true;
                } elseif ($r['candidateRejected']) {
                    $rejectedAt = $r;
                } elseif (empty($r['partials'])) {
                    $okDelay = $maxDelay;
                    $cleanPlantChoice = $this->passPlantChoice;
                    self::schedLog()->info("[INCR_DELAY] probe +{$maxDelay} min (cap) → clean (0 partials).");
                } else {
                    $lastFail = $maxDelay;
                    $seenReason = $this->candidatePartialReason($r['partials'], $candidate);
                    if ($seenReason !== null) { $candidateReasonSeen = $seenReason; }
                }
            }
            if ($expanded) {
                [$allOrders, $allTrips, $candidatesList, $candidateIdx, $committed] =
                    $this->rebuildAfterPlantExpansion($scheduleData, $allOrders, $allTrips, $candidate, $committed);
                self::schedLog()->info("[PLANT_EXPAND] retrying {$candidate->order_no} on the wider fleet "
                    . "(opened at cap probe) — " . $committed->count() . " already-committed order(s) kept as-is.");
                continue;
            }

            if ($rejectedAt !== null) {
                $rejected[$candidate->id] = $rejectedAt['candidateReason']
                    ?? "This order could not be scheduled because all available plants are at full capacity.";
                self::schedLog()->info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — appeared in rejectedOrders "
                    . "while probing delays ({$probes} probe(s)); committed set unchanged");
                $candidateIdx++;
                continue;
            }
            if ($okDelay === null) {
                // Never committed — drop any expansion pin so it cannot leak.
                unset($this->pinnedPlant[$candidate->order_no]);
                $rejected[$candidate->id] = $candidateReasonSeen
                    ?: "This order could not be scheduled because all available plants are at full capacity.";
                self::schedLog()->info("[INCR_SCHED] ✗ {$candidate->order_no} rejected — no delay up to "
                    . "+{$maxDelay} min cleared the partials ({$probes} probe(s)); committed set unchanged");
                $candidateIdx++;
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
                    $scheduleData,
                    $pristine,
                    $allTrips,
                    $testOrders,
                    $testOrderNos,
                    $candidate,
                    $committedDelays,
                    $mid
                );
                if ($r['expanded']) {
                    $expanded = true;
                    break;
                }
                if (!$r['candidateRejected'] && empty($r['partials'])) {
                    $best = $mid;
                    $hi   = $mid;
                    $cleanPlantChoice = $this->passPlantChoice;
                    self::schedLog()->info("[INCR_DELAY] refine +{$mid} min → clean; trying smaller.");
                } else {
                    $lo = $mid;
                    self::schedLog()->info("[INCR_DELAY] refine +{$mid} min → not clean; trying larger.");
                }
            }
            if ($expanded) {
                [$allOrders, $allTrips, $candidatesList, $candidateIdx, $committed] =
                    $this->rebuildAfterPlantExpansion($scheduleData, $allOrders, $allTrips, $candidate, $committed);
                self::schedLog()->info("[PLANT_EXPAND] retrying {$candidate->order_no} on the wider fleet "
                    . "(opened mid refine) — " . $committed->count() . " already-committed order(s) kept as-is.");
                continue;
            }

            $committed = $testOrders;
            $this->pinCommittedPlants($committed, $cleanPlantChoice);
            $committedDelays[$candidate->order_no] = $best;
            self::schedLog()->info("[INCR_SCHED] ✓ {$candidate->order_no} accepted with predicted +{$best} min delay "
                . "(" . ($probes + $refine + 1) . " scheduling pass(es); +1-min stepping would have taken up "
                . "to " . ($best + 1) . "); committed now " . $committed->count() . " order(s)");
            $candidateIdx++;
        }

        // ── Final canonical pass: rebuild DB with ONLY the committed set. ──
        // trial_mode OFF — this pass writes the real schedule rows.
        $scheduleData->trial_mode = false;
        $this->restorePools($scheduleData, $pristine);
        $this->clearPreviousSchedules(
            $scheduleData->company,
            $scheduleData->user_id,
            $scheduleData->schedule_date
        );
        if ($committed->isNotEmpty()) {
            $committedNos   = $committed->pluck('order_no')->all();
            $committedTrips = $this->buildTripsWithDelays($allTrips, $committedNos, $committedDelays);
            self::schedLog()->info("[INCR_SCHED] final pass scheduling " . $committed->count() . " committed order(s)"
                . (!empty($committedDelays)
                    ? " (delays applied: " . $this->describeDelays($committedDelays, $committedNos) . ")"
                    : ""));
            $this->scheduleTripsChronologically($scheduleData, $committedTrips, $committed);
        }

        return ['committed' => $committed, 'rejected' => $rejected];
    }

    /**
     * After a plant opens mid-pass (scheduleCandidateAtDelay() returned
     * expanded=true), rebuild whatever per-fleet-size data depends on the
     * opened-plant count — WITHOUT discarding progress. Unlike the old
     * full-restart behavior, $committed and its accepted delays are kept
     * exactly as-is; only $candidateIdx is rewound back to the SAME
     * candidate that triggered the expansion, so the caller's `continue`
     * retries just that one candidate (from delay 0 / its original time)
     * on the wider fleet.
     *
     * @return array{0:\Illuminate\Support\Collection,1:array,2:\Illuminate\Support\Collection,3:int,4:\Illuminate\Support\Collection}
     *         [allOrders, allTrips, candidatesList, candidateIdx, committed]
     */
    private function rebuildAfterPlantExpansion(
        ScheduleData &$scheduleData,
        $allOrders,
        array $allTrips,
        $candidate,
        $committed
    ): array {
        // In consolidate mode (<=500) usable pumps are sized off the OPENED
        // plants, so opening one changes used_pump/standby_pump and the
        // pump-aware expected_duration/max_delay. Recompute, persist, then
        // reload the orders + regenerate trips so we keep using fresh values.
        // In divide mode (>500) usable pumps are sized off ALL plants
        // already, so nothing changes — skip.
        if (!$scheduleData->divide_mode) {
            $this->recalcPumpDurations($scheduleData, (int) $scheduleData->opened_plant_count);
            $allOrders = $this->reloadOrders($scheduleData);
            $allTrips  = $this->generateAllOrderTrips($scheduleData, $allOrders);
            self::schedLog()->info("[PUMP_DUR] recomputed usable pumps for "
                . "{$scheduleData->opened_plant_count} opened plant(s); "
                . count($allTrips) . " trip(s) regenerated.");

            // Already-committed orders may now carry fresh per-order fields
            // (expected_duration/max_delay) from the reload — swap each one
            // for its refreshed counterpart instead of keeping the stale copy.
            // NOTE: they are NOT re-run through the scheduler on the wider
            // fleet — only the candidate that triggered the expansion is.
            $committed = $committed->map(function ($order) use ($allOrders) {
                return $allOrders->firstWhere('order_no', $order->order_no) ?? $order;
            });
        }

        $candidatesList = $allOrders->values();
        $candidateIdx   = $candidatesList->search(function ($o) use ($candidate) {
            return $o->order_no === $candidate->order_no;
        });
        if ($candidateIdx === false) {
            // Shouldn't happen — the candidate that triggered the expansion
            // must still be in the reloaded list — but fail safe instead of
            // crashing the whole pass.
            self::schedLog()->warning("[PLANT_EXPAND] {$candidate->order_no} not found after reload; "
                . "resuming from the top of the candidate list instead.");
            $candidateIdx = 0;
        }

        return [$allOrders, $allTrips, $candidatesList, $candidateIdx, $committed];
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
     * expanded=true. The caller (runIncrementalSchedule) then retries just the
     * SAME candidate on the wider fleet via rebuildAfterPlantExpansion() —
     * already-committed candidates and their accepted delays are kept as-is,
     * so this method does NOT reschedule after expanding.
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
        // Feasibility probe: persist only the order summary, skip bulky rows.
        $scheduleData->trial_mode = true;
        $this->clearPreviousSchedules(
            $scheduleData->company,
            $scheduleData->user_id,
            $scheduleData->schedule_date,
            true
        );
        $this->scheduleTripsChronologically($scheduleData, $testTrips, $testOrders);
        $partials       = $this->detectPartialOrders($scheduleData, $testOrders);
        $rejectedOrders = $this->detectRejectedOrders($scheduleData, $testOrders);

        // ── Plant expansion (open ONE more plant at a time) ──────────────────
        // Trigger depends on the run size:
        //   • divide_mode (> 500): open a plant whenever a PARTIAL is detected.
        //   • else      (<= 500): open a plant whenever a hard REJECT is detected.
        // Only fires while plants remain closed (opened_plant_count < max). When
        // every plant is already open the trigger is skipped, so the normal
        // delay-retry / reject path in the caller takes over as the fallback.
        // NOTE: distinct PLANT NAMES, not array length — bps_availability
        // fragments into many free-window rows for the SAME plant.
        $expanded = false;
        $expandTrigger = $scheduleData->divide_mode ? !empty($partials) : !empty($rejectedOrders);
        $triggerLabel  = $scheduleData->divide_mode ? 'partial' : 'hard rejection';
        $hasRoomToOpen = (int) $scheduleData->opened_plant_count < (int) $scheduleData->max_plant_count;

        if ($expandTrigger && $scheduleData->consolidated_single_plant && $hasRoomToOpen) {
            $from = (int) $scheduleData->opened_plant_count;
            $to   = $from + 1;

            self::schedLog()->info("[PLANT_EXPAND] candidate {$candidate->order_no} hit a {$triggerLabel} "
                . "with {$from} plant(s) open — opening one more ({$from} → {$to} of "
                . "{$scheduleData->max_plant_count}); the run will restart fresh on the wider fleet.");

            $widened = $this->firstNDistinctPlants($scheduleData->bps_availability_full, $to);

            // ── Send the candidate to the plant that was just opened FOR it ───
            // The expansion happens precisely because this candidate did not fit
            // on the plant(s) already open. Without a pin, predictBestPlant()
            // re-scores on the retry and sends it straight back to the crowded
            // plant — the new plant stays empty and the expansion achieves nothing.
            // It picks the crowded plant because existingLoad only counts trips
            // ALREADY placed in this pass: committed orders that pour later in the
            // day are invisible, so a plant holding several commitments still
            // scores as empty and wins the tie on array order.
            $plantsBefore = array_values(array_unique(array_column($scheduleData->bps_availability, 'plant_name')));
            $plantsAfter  = array_values(array_unique(array_column($widened, 'plant_name')));
            $justOpened   = array_values(array_diff($plantsAfter, $plantsBefore));
            if (!empty($justOpened)) {
                $this->pinnedPlant[$candidate->order_no] = $justOpened[0];
                self::schedLog()->info("[PLANT_PIN] candidate {$candidate->order_no} pinned to the newly "
                    . "opened plant {$justOpened[0]} — the expansion was opened for this candidate, so it "
                    . "must not fall back onto the crowded plant(s) [" . implode(', ', $plantsBefore) . "].");
            }

            // Apply to BOTH the live state and the baseline so the wider fleet
            // persists across the caller's restart-from-fresh.
            $scheduleData->opened_plant_count        = $to;
            $scheduleData->bps_availability           = $widened;
            $scheduleData->consolidated_single_plant  = ($to < (int) $scheduleData->max_plant_count);
            $pristine['bps_availability']             = $widened;
            $expanded = true;
            // Do NOT reschedule here — the caller restarts the whole pass.
        } elseif ($expandTrigger && !$hasRoomToOpen) {
            self::schedLog()->warning("[PLANT_EXPAND] candidate {$candidate->order_no} hit a {$triggerLabel}, but all "
                . "{$scheduleData->max_plant_count} distinct plant(s) are already open — cannot open more. "
                . "Falling back to delay-retry / reject. Check plant selection / location / location_shift rows.");
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
            $aStart = Carbon::parse($a['loading_start']);
            $bStart = Carbon::parse($b['loading_start']);

            // 1. Sort by loading_start
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }

            // 2. Sort by trip
            if (($a['trip'] ?? PHP_INT_MAX) !== ($b['trip'] ?? PHP_INT_MAX)) {
                return ($a['trip'] ?? PHP_INT_MAX) <=> ($b['trip'] ?? PHP_INT_MAX);
            }

            // 3. Sort by LPI score (higher first)
            return ($b['order_lpi_score'] ?? 0) <=> ($a['order_lpi_score'] ?? 0);
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
            ->get(['id', 'order_no', 'quantity', 'delivered_quantity', 'lpi_score', 'failure_reason']);
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
                    'id'              => $row->id,
                    'order_no'        => $row->order_no,
                    'delivered'       => $delivered,
                    'quantity'        => $ordered,
                    'lpi_score'       => (float) ($row->lpi_score ?? 0),
                    // Real, specific reason persisted by scheduleTripsChronologically()
                    // (e.g. "Delay limit exceeded ..." / "exceeds maximum acceptable
                    // delay ..."). Kept for diagnostics/logging even though it's no
                    // longer used to short-circuit the delay search — a max_delay-type
                    // partial IS often fixable by delaying the candidate (it usually
                    // means the trip had to wait on a busy plant/pump, and shifting
                    // the candidate's start can land it in a free slot instead).
                    'failure_reason'  => trim((string) ($row->failure_reason ?? '')),
                    'reason'          => "Order rejected — partial scheduling not allowed. "
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
                self::schedLog()->warning("Could not save rejection reason for order ID {$orderId}: " . $e->getMessage());
            }
        }
        self::schedLog()->info("Feasibility check complete: " . $feasible->count() . " orders accepted, " . count($rejected) . " rejected out of " . $allOrders->count() . " total.");
        return $feasible;
    }
    private function generateAllOrderTrips(ScheduleData $scheduleData, $allOrders): array
    {
        $plantCount = $this->selectedPlantCount($scheduleData);
        $allTrips   = [];
        $orderIndex = 0;
        foreach ($allOrders as $order) {
            $effectivePumps = $this->effectivePumpsForOrder($order, $plantCount);

            // Cap by the number of PHYSICAL pumps that actually exist and match
            // this order's required capacity/type. Trips can only overlap their
            // pours if there are real pumps to serve them simultaneously — so if
            // only ONE matching pump is available, the order pours SEQUENTIALLY
            // (single lane, no trips on the same time) regardless of pump_qty.
            if ((bool) $order->pump) {
                $availPumps  = $this->availablePumpCountForOrder($order, $scheduleData);
                $cappedPumps = max(1, min($effectivePumps, $availPumps));
                if ($cappedPumps !== $effectivePumps) {
                    self::schedLog()->info("[PUMP_PARALLEL] order {$order->order_no}: pump_qty="
                        . (int) ($order->pump_qty ?? 0) . " wanted {$effectivePumps} lane(s) but only "
                        . "{$availPumps} matching pump(s) available → using {$cappedPumps} "
                        . ($cappedPumps === 1 ? "(sequential, no overlapping trips)" : "lane(s)"));
                    $effectivePumps = $cappedPumps;
                }
            }

            if ((bool) $order->pump && $effectivePumps > 1) {
                self::schedLog()->info("[PUMP_PARALLEL] order {$order->order_no} pump_qty="
                    . (int) ($order->pump_qty ?? 0) . " selected_plants={$plantCount} "
                    . "→ effectivePumps={$effectivePumps} (trips run {$effectivePumps}-at-a-time in waves)");
            }
            // Reuse this order's trips when its lane count hasn't changed since a
            // previous (re)generation. Non-pump / single-pump orders always key
            // on effectivePumps=1, so they are built once per run; multi-pump
            // orders rebuild only when a plant change alters their lane count.
            $tripCacheKey = $order->id . ':' . $effectivePumps;
            if (isset($scheduleData->trip_cache[$tripCacheKey])) {
                $orderTrips = $scheduleData->trip_cache[$tripCacheKey];
                $orderIndex++; // keep order_sequence advancing as in the build path
            } else {
                $orderTrips = $this->buildOrderTrips($order, $effectivePumps, $scheduleData, $orderIndex);
                $scheduleData->trip_cache[$tripCacheKey] = $orderTrips;
            }
            foreach ($orderTrips as $t) {
                $allTrips[] = $t;
            }
        }
        usort($allTrips, function ($a, $b) {
            $aStart = Carbon::parse($a['loading_start']);
            $bStart = Carbon::parse($b['loading_start']);

            // 1. Sort by loading_start
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }

            // 2. Sort by trip
            if (($a['trip'] ?? PHP_INT_MAX) !== ($b['trip'] ?? PHP_INT_MAX)) {
                return ($a['trip'] ?? PHP_INT_MAX) <=> ($b['trip'] ?? PHP_INT_MAX);
            }

            // 3. Sort by LPI score (higher first)
            return ($b['order_lpi_score'] ?? 0) <=> ($a['order_lpi_score'] ?? 0);
        });
        return $allTrips;
    }

    /**
     * Number of distinct plants currently selected/available for scheduling.
     * bps_availability may hold several free-window rows per plant, so this
     * counts DISTINCT plant_name values, not the array length.
     */
    private function selectedPlantCount(ScheduleData $scheduleData): int
    {
        $names = array_filter(array_column($scheduleData->bps_availability ?? [], 'plant_name'));
        return max(1, count(array_unique($names)));
    }

    /**
     * Effective pump lanes for an order, driven by used_pump — the pumps the
     * order ACTUALLY uses once plant capacity is taken into account (standby_pump
     * pumps are excluded). Non-pump / single-pump orders pour on a single lane.
     * $plantCount is accepted for call-site compatibility; the plant cap is
     * already baked into used_pump by recalcPumpDurations.
     */
    private function effectivePumpsForOrder($order, int $plantCount): int
    {
        if (!($order->pump ?? false)) {
            return 1;
        }
        return max(1, (int) ($order->used_pump ?? $order->pump_qty ?? 1));
    }

    /**
     * Count of distinct PHYSICAL pumps in pumps_availability that match the
     * order's required capacity + type (across all its order_pumps rows). Used to
     * cap the lanes so trips only overlap when real pumps exist to serve them.
     */
    private function availablePumpCountForOrder($order, ScheduleData $scheduleData): int
    {
        if (!($order->pump ?? false)) {
            return 1;
        }
        $reqs = [];
        foreach (($order->order_pumps ?? []) as $op) {
            $reqs[] = ['capacity' => (float) $op->pump_size, 'type' => $op->type];
        }
        if (empty($reqs)) {
            return 1;
        }
        $matchingIds = [];
        foreach (($scheduleData->pumps_availability ?? []) as $s) {
            foreach ($reqs as $r) {
                if (
                    (float) ($s['pump_capacity'] ?? -1) === (float) $r['capacity']
                    && ($s['pump_type'] ?? null) === $r['type']
                ) {
                    if (isset($s['pump_id'])) {
                        $matchingIds[$s['pump_id']] = true;
                    }
                    break;
                }
            }
        }
        return max(1, count($matchingIds));
    }

    private function buildOrderTrips($order, int $effectivePumps, ScheduleData $scheduleData, int &$orderIndex): array
    {
        $effectivePumps = max(1, $effectivePumps);
        $truckCapacity = self::DEFAULT_TRUCK_CAPACITY;
        $loadingTime   = $order->loading_time   ?? ConstantHelper::LOADING_TIME;
        $pouringTime   = $order->pouring_time   ?? 0;
        $travelTime    = $order->travel_to_site ?? 0;
        $returnTime    = $order->return_to_plant ?? 0;
        $interval      = $order->pouring_time;
        $qcTime        = $scheduleData->qc_time;
        $inspTime      = $scheduleData->insp_time;
        $cleaningTime  = $scheduleData->cleaning_time;
        $location      = $order->location;
        $remainingQty  = $order->quantity;
        $totalTrips    = (int) ceil($order->quantity / max(1, $truckCapacity));

        // ── Per-trip tolerance (for plant clash resolution) ───────────
        $tolerancePercent  = (float) ($order->tolerance ?? 10);
        $tripToleranceMins = (int) ceil($interval * ($tolerancePercent / 100));

        // ── Overlap mode (long pour) ─────────────────────────────────────────
        // When the pour is long relative to loading (pouring_time >= 2×loading)
        // and the order has >1 pump lane, ONE plant keeps loading fresh trucks
        // every ~loading_time while earlier trucks are still pouring, so several
        // pours run CONCURRENTLY off a single plant (each on its own pump). In
        // this mode the E trucks of a wave load BACK-TO-BACK (every loading_time:
        // 8:00, 8:05, 8:10 for 3 pumps), one per pump; the NEXT wave reuses those
        // pumps exactly pouring_time after the wave started (8:20 = 8:00 + 20),
        // so each pump is busy continuously and the plant idles only the leftover
        // of the cycle. (The old parallel layout loaded all E at the SAME instant
        // and needed E plants.) For short pours we keep the parallel waves.
        $overlapMode = ($pouringTime > 0 && $loadingTime > 0
            && $pouringTime >= 2 * $loadingTime && $effectivePumps > 1);

        // Wave grid anchor = trip-1 loading start, computed once from a FULL
        // truck load so a partial last trip can't drift the grid. For
        // $effectivePumps == 1 the wave index equals the trip index, so
        // loadingStart reproduces the old sequential value exactly.
        $anchorPreMins = $loadingTime + $qcTime + $travelTime + $inspTime + 4;
        $waveAnchor    = Carbon::parse($order->delivery_date)->subMinutes($anchorPreMins);

        // Per-lane previous pouring_end → chains pump waiting within a lane.
        $lanePrevPouringEnd = array_fill(0, $effectivePumps, null);

        $trips = [];
        for ($trip = 1; $trip <= $totalTrips; $trip++) {
            $i    = $trip - 1;
            $wave = intdiv($i, $effectivePumps);
            $lane = $i % $effectivePumps;

            $batchQty        = min($truckCapacity, $remainingQty);
            $tripLoadingTime = $loadingTime;
            $tripPouringTime = $pouringTime;
            if ($batchQty < self::DEFAULT_TRUCK_CAPACITY) {
                $tripLoadingTime = (int) round(($loadingTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
                $tripPouringTime = (int) round(($pouringTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
            }

            // Overlap mode: within a wave the E trucks load back-to-back
            // (lane × loading_time → 0, +5, +10…), and waves are pouring_time
            // apart so each pump is reused exactly when its previous pour ends.
            // Otherwise parallel waves: all lanes of a wave load together.
            //
            // IMPORTANT: this grid point is where loading would END for a FULL
            // (8 m³) truck — it must stay FIXED regardless of this trip's actual
            // batch size, because downstream stages (qc/travel/insp/pouring) are
            // anchored off loadingEnd, and pouring_start must equal the order's
            // delivery_date for trip 1 (and stay grid-aligned for later trips)
            // no matter how small the batch is. Only loadingStart moves — a
            // partial (<8 m³) batch needs LESS loading time, so it can START
            // loading later and still FINISH loading at the same anchor point,
            // instead of finishing early and dragging the whole downstream
            // chain earlier than intended (which used to cause a small,
            // spurious postponement for orders/last-trips under 8 m³).
            $loadingEnd   = $overlapMode
                ? $waveAnchor->copy()->addMinutes($wave * $pouringTime + $lane * $loadingTime + $loadingTime)
                : $waveAnchor->copy()->addMinutes($wave * $interval + $loadingTime);
            $loadingStart = $loadingEnd->copy()->subMinutes($tripLoadingTime);
            $qcStart      = $loadingEnd->copy()->addMinute();
            $qcEnd        = $qcStart->copy()->addMinutes($qcTime);
            $travelStart  = $qcEnd->copy()->addMinute();
            $travelEnd    = $travelStart->copy()->addMinutes($travelTime);
            $inspStart    = $travelEnd->copy()->addMinute();
            $inspEnd      = $inspStart->copy()->addMinutes($inspTime);

            // ── Waiting step for pump orders, wave 2+ on the SAME lane ────────
            $waitingStart = null;
            $waitingEnd   = null;
            $waitingTime  = 0;
            $prevPE = $lanePrevPouringEnd[$lane] ?? null;
            $pouringStart = $inspEnd->copy()->addMinute();
            $pouringEnd    = $pouringStart->copy()->addMinutes($tripPouringTime);
            $cleaningStart = $pouringEnd->copy()->addMinute();
            $cleaningEnd   = $cleaningStart->copy()->addMinutes($cleaningTime);
            $returnStart   = $cleaningEnd->copy()->addMinute();
            $returnEnd     = $returnStart->copy()->addMinutes($returnTime);

            $lanePrevPouringEnd[$lane] = $pouringEnd->copy();

            $trips[] = [
                'order_sequence' => $orderIndex,
                'order_quantity' => $order->quantity,
                'order_id'       => $order->id,
                'order_no'       => $order->order_no,
                'order_lpi_score' => $order->lpi_score,
                'order_priority' => $order->priority,
                'order_pump'     => (bool) $order->pump,
                'trip'           => $trip,
                'total_trips'    => $totalTrips,
                'wave'           => $wave,
                'lane'           => $lane,
                'effective_pumps' => $effectivePumps,
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
                'plant'=>null,
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
        return $trips;
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

            // 1. Sort by loading_start
            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }

            // // 2. Sort by trip
            // if (($a['trip'] ?? PHP_INT_MAX) !== ($b['trip'] ?? PHP_INT_MAX)) {
            //     return ($a['trip'] ?? PHP_INT_MAX) <=> ($b['trip'] ?? PHP_INT_MAX);
            // }

            // 3. Sort by LPI score (higher first)
            return ($b['order_lpi_score'] ?? 0) <=> ($a['order_lpi_score'] ?? 0);
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
        $this->passPlantChoice = [];   // plant choices made during THIS pass
        $trialAborted          = false; // trial probe stopped at the first partial
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
            // self::schedLog()->info("[PUMP_PRIORITY] reordered " . count($loserTrips)
            //     . " trip(s) of " . count($pumpLosers) . " losing pump order(s) ["
            //     . implode(',', $pumpLosers) . "] to run after their winners");
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
            // ── Trial-pass fast exit ────────────────────────
            // A feasibility probe only needs CLEAN vs NOT CLEAN. Once any order
            // has failed and cannot complete, this pass is already NOT clean and
            // no remaining trip can change that verdict — so stop here instead of
            // scheduling the rest. clearPreviousSchedules() resets
            // delivered_quantity to 0 at the start of every pass, so every order
            // left untested still reports delivered < ordered and is picked up as
            // a partial by detectPartialOrders() — the verdict is unchanged.
            // The final canonical pass (trial_mode = false) never takes this exit:
            // it must schedule everything it can.
            if ($scheduleData->trial_mode && !$trialAborted) {
                foreach ($orderFailed as $failedNo => $_failReason) {
                    if (empty($orderCompleted[$failedNo])) {
                        $trialAborted = true;
                        self::schedLog()->info("[TRIAL_FASTEXIT] order {$failedNo} cannot complete "
                            . "— probe is already not clean; skipping the remaining trips.");
                        break;
                    }
                }
                if ($trialAborted) {
                    break;
                }
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
                // tripData is already incremental-shifted, so measure how late trip 1
                // already is relative to the order's target time.
                $deliveryDate    = Carbon::parse($order->delivery_date);
                $trip1Start      = Carbon::parse($tripData['pouring_start']); // or loading_start — whichever your 480 is measured against
                $alreadyLate     = max(0, (int) $deliveryDate->diffInMinutes($trip1Start, false));

                $hardLimit       = 480; // or (int) ($order->max_delay ?? 480) if you want per-order
                $maxRetryMinutes = max(0, $hardLimit - $alreadyLate);
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
                $scheduleData->assigned_plant = $bestPlantPerOrderTrip[$orderNo] ?? null;
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
                            $this->appendFailureReason(
                                $scheduleData,
                                "Order {$order->order_no} trip {$currentTrip['trip']}: pump busy, "
                                    . "delayed {$delay_time} min"
                                    . (isset($delay['pump_id']) ? " (pump #{$delay['pump_id']})" : "") . "."
                            );
                            if ($retryOffset + $delay_time > $maxRetryMinutes) {
                                $orderFailed[$orderNo] = "Pump unavailable. With  in Max allowed delay time: " .
                                    Carbon::parse($order->delivery_date)->addMinutes($maxRetryMinutes)->format('h:i A');
                                break;
                            }
                            $reason = "Pump not found for order {$order->order_no}";
                            $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                            if (!$scheduleData->trial_mode && isset($scheduleData->batching_plant['data']['plant_name'])) {
                                BatchingPlantAvailability::create([
                                    'group_company_id' => $scheduleData->company,
                                    'location' => $scheduleData->location,
                                    'plant_name' => $scheduleData->batching_plant['data']['plant_name'],
                                    'plant_capacity' => 0,
                                    'free_from' => $scheduleData->loading_start->copy()->format('Y-m-d H:i:s'),
                                    'free_upto' => $scheduleData->loading_start->copy()->addMinutes($delay_time)->format('Y-m-d H:i:s'),
                                    'user_id' => $scheduleData->user_id,
                                    'reason' => $reason,
                                ]);
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
                    if (!$scheduleData->trial_mode && isset($scheduleData->batching_plant['data']['plant_name'])) {
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
                if ($scheduleData->trip === 1 && $order->base_interval >= $order->loading_time && $order->req_plants === 1) {
                    // An order that is already COMMITTED keeps the plant it was
                    // accepted on; only a not-yet-committed order is predicted.
                    $plant = $this->pinnedPlantFor($scheduleData, $orderNo)
                        ?? $this->predictBestPlant($scheduleData, $order, $location);
                    $scheduleData->assigned_plants = [$plant];
                    $scheduleData->assigned_plant = $plant;
                    $bestPlantPerOrderTrip[$orderNo] = $plant;
                    $this->passPlantChoice[$orderNo] = $plant;
                }
                $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                if (!isset($scheduleData->batching_plant['data']['plant_name'])) {
                    $nextPlantFree = $this->nextFreeMinutes($scheduleData->bps_availability, $currentTrip['loading_start']);
                    $retryOffset += max(1, $nextPlantFree);
                    continue;
                }
                $plantName = $scheduleData->batching_plant['data']['plant_name'];

                if (!in_array($plantName, $scheduleData->assigned_plants ?? [])) {
                    $scheduleData->assigned_plants[] = $plantName;
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
                    // Effective limit = hard limit + small grace, so a marginal
                    // overrun (e.g. a single-pump order ~1 min over its own limit
                    // at its ORIGINAL time) is NOT flagged partial and shoved into
                    // the delay search — which would only postpone it, never
                    // shorten it. See DELAY_LIMIT_GRACE_MINS.
                    $effectiveLimit   = $delayLimit + self::DELAY_LIMIT_GRACE_MINS;

                    // self::schedLog()->info("[DELAY_LIMIT_CHECK] order={$orderNo} trip={$currentTrip['trip']} "
                    //     . "first_pouring_start={$firstPS->format('H:i')} "
                    //     . "current_pouring_end={$currentPE->format('H:i')} "
                    //     . "elapsed={$elapsedMins}min "
                    //     . "limit={$delayLimit}min(+{self::DELAY_LIMIT_GRACE_MINS} grace) (expected={$expectedDuration}+max_delay={$order->max_delay}) "
                    //     . ($elapsedMins > $effectiveLimit ? "⛔ EXCEEDED" : "✓ OK"));

                    if ($elapsedMins > $effectiveLimit) {
                        $orderFailed[$orderNo] = "Delay limit exceeded at trip {$currentTrip['trip']}. "
                            . "Elapsed: {$elapsedMins} min (first pouring {$firstPS->format('H:i')} → "
                            . "current pouring end {$currentPE->format('H:i')}). "
                            . "Limit: {$delayLimit} min (+" . self::DELAY_LIMIT_GRACE_MINS . " grace) "
                            . "(expected {$expectedDuration} + max_delay {$order->max_delay}).";
                        self::schedLog()->info("[DELAY_LIMIT_CHECK] order={$orderNo} trip={$currentTrip['trip']} "
                            . "first_pouring_start={$firstPS->format('H:i')} "
                            . "current_pouring_end={$currentPE->format('H:i')} "
                            . "elapsed={$elapsedMins}min "
                            . "limit={$delayLimit}min(+" . self::DELAY_LIMIT_GRACE_MINS . " grace) (expected={$expectedDuration}+max_delay={$order->max_delay}) "
                            . "on plant {$plantName} "
                            . ($elapsedMins > $effectiveLimit ? "⛔ EXCEEDED" : "✓ OK"));
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

                    self::schedLog()->warning("[MAXRETRY_EXCEEDED] order={$orderNo} trip={$tripData['trip']}/{$totalTrips} "
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

                    self::schedLog()->warning("[MAXRETRY_EXCEEDED] order={$orderNo} trip={$tripData['trip']}/{$totalTrips} "
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

            // self::schedLog()->info("[DELAY_CHECK] order={$orderNo} "
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
                    self::schedLog()->warning("[SUPPLY_RATE] order={$orderNo} qty={$order->quantity} "
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

        // Standby-required orders are collected here and assigned their standby
        // pumps in a SECOND pass, sorted by LPI (highest first), so high-LPI
        // orders get first pick of the limited free pumps.
        $standbyCandidates = [];

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
                    self::schedLog()->warning("[PARTIAL_BLOCKED] Could not persist rejection for order={$orderNo}: " . $e->getMessage());
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
                // Standby pumps are assigned in a later LPI-sorted pass (after
                // all active pumps are placed and stored), so higher-LPI orders
                // claim the remaining free pumps first. Just record the candidate.
                if ($order->standby_pump_required) {
                    $standbyCandidates[$orderNo] = $order;
                }
            }
            $this->storeSchedules($order, $scheduleData);
        }

        // ── Standby pump pass (LPI priority) ──────────────────────────────
        // All active pumps are now placed and stored. Reserve standby pumps for
        // standby-required orders, HIGHEST-LPI FIRST, so the most important
        // orders claim the remaining free pumps before lower-LPI ones do.
        if (!$scheduleData->trial_mode && !empty($standbyCandidates)) {
            uasort($standbyCandidates, function ($a, $b) {
                return ((float) ($b->lpi_score ?? 0)) <=> ((float) ($a->lpi_score ?? 0));
            });
            foreach ($standbyCandidates as $standbyOrder) {
                $this->assignStandbyPump($standbyOrder, $scheduleData);
            }
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
        // Anchor point: tripData['loading_start']/loading_time were computed
        // upstream (estimateOrderWindow / trip generation) by back-calculating
        // insp+qc+travel+loading_time (+buffer) from the order's target
        // delivery/pour time. That anchor (loadingEnd = loadingStart +
        // full-capacity loading_time) must stay fixed regardless of batch
        // size — ONLY the loading duration itself scales with quantity. So
        // both full and partial (<8 m³) truckloads shift loadingStart by the
        // same delta (scaledLoading − base loading_time), which keeps
        // loadingEnd pinned to the anchor. Previously the <8 branch shifted
        // by the FULL base loading_time instead of the delta, pulling the
        // whole downstream chain (qc/travel/insp/pouring/cleaning) earlier
        // than the anchor and causing spurious resource-slot conflicts that
        // showed up as small candidate-delay postponements for orders whose
        // (last) trip was under 8 m³.
        $extraLoadingTime = $scaledLoading - $tripData['loading_time'];
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
            "lpi_score",
            "is_critical",
            "req_plants",
            "used_pump",
            "standby_pump",
            "standby_pump_required"
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
        if ($scheduleData->trial_mode) {
            // ── Feasibility probe: persist ONLY the selected_orders summary ──
            // Compute the same min/max aggregates the DB version reads back, but
            // do it in PHP from the in-memory rows — no bulk inserts, no
            // read-back SELECT. This is what detectPartialOrders() /
            // detectRejectedOrders() consume.
            $minPour = $maxPour = null;
            foreach (($scheduleData->schedules ?? []) as $row) {
                $ps = isset($row['pouring_start']) ? Carbon::parse($row['pouring_start']) : null;
                $pe = isset($row['pouring_end'])   ? Carbon::parse($row['pouring_end'])   : null;
                if ($ps && (!$minPour || $ps->lt($minPour))) {
                    $minPour = $ps;
                }
                if ($pe && (!$maxPour || $pe->gt($maxPour))) {
                    $maxPour = $pe;
                }
            }
            $minPourStr = $minPour ? $minPour->format('Y-m-d H:i:s') : null;
            $maxPourStr = $maxPour ? $maxPour->format('Y-m-d H:i:s') : null;

            $actualDuration   = ($minPour && $maxPour) ? (int) $minPour->diffInMinutes($maxPour) : 0;
            $expectedDuration = (int) ($order->expected_duration ?? $actualDuration);
            $actualDelay      = max(0, $actualDuration - $expectedDuration);
            $deviation        = $minPour
                ? Carbon::parse($order->delivery_date)->diffInMinutes($minPour, false)
                : null;

            DB::table('selected_orders')->where('id', $order->id)->update([
                'start_time'         => $minPourStr,
                'end_time'           => $maxPourStr,
                'delivered_quantity' => $scheduleData->delivered_quantity,
                'location'           => $scheduleData->location,
                'deviation'          => $deviation,
                'actual_delay'       => $actualDelay,
                'expected_duration'  => $expectedDuration,
                'failure_reason'     => $scheduleData->failure_reason ?: null,
            ]);
            return;
        }

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

        // self::schedLog()->info("[STORED] order={$order->order_no} "
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
        $pumpsRequired = max(1, (int) ($order->used_pump ?? $order->pump_qty));
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
            // All pumps work in PARALLEL: every pump shares the same pour window
            // (the full order window), so each pump's start_time is identical.
            // Pumps differ only in how much they pour (batching_qty / trip count),
            // not in WHEN they start or end.
            $groupPourStart = Carbon::parse($trips[0]['pouring_start']);
            $groupPourEnd = Carbon::parse($trips[$lastIndex]['pouring_end']);
            $groupPumpEndTime = Carbon::parse($trips[$lastIndex]['return_end']);
            $cleanEnd = Carbon::parse($trips[$lastIndex]['cleaning_end']);
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
                    if ($plant && !$scheduleData->trial_mode) {
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
                // Active (pouring) pump. Standby pumps are appended later by
                // assignStandbyPump() with is_standby = true. Every row in
                // selected_order_pump_schedules MUST carry this key so the bulk
                // insert in storeSchedules() keeps a consistent column set.
                'is_standby' => false,
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

    /**
     * Standby Pump Assignment.
     *
     * Runs AFTER an order's active pumps have been scheduled (assignPump). When
     * the order has Standby Pump Required = Yes it reserves ONE additional pump
     * as a dedicated standby for this order. The standby pump must:
     *   1. Be a real pump matching one of the order's required capacity/type.
     *   2. Not be assigned to any schedule (never used in this run — i.e. it has
     *      no entry in pump_busy_slots).
     *   3. Be available during the order's pour window.
     *   4. Not already be one of this order's active pumps.
     *
     * The chosen pump is recorded as an extra selected_order_pump_schedule row
     * (is_standby = true) shadowing the order's pour window, and is reserved in
     * the busy-slot / assigned-pump pools so nothing else can grab it.
     *
     * Best-effort: if no free pump is found the order keeps its normal schedule
     * and no standby is added.
     */
    private function assignStandbyPump($order, ScheduleData &$scheduleData): void
    {
        // A standby pump is only added when the order requires MORE THAN ONE
        // pump. Single-pump orders never get a standby.
        $pumpQty = (int) ($order->pump_qty ?? 0);
        if ($pumpQty <= 1) {
            self::schedLog()->info("[STANDBY] order {$order->order_no}: only one pump required — no standby pump added.");
            return;
        }

        // Active (pouring) pump rows for this order — already persisted by
        // storeSchedules(). This runs in a post-scheduling pass, so we read them
        // back from the DB rather than from in-memory state.
        $activeRows = DB::table('selected_order_pump_schedules')
            ->where('group_company_id', $scheduleData->company)
            ->where('user_id', $scheduleData->user_id)
            ->where('order_no', $order->order_no)
            ->where(function ($q) {
                $q->where('is_standby', false)->orWhereNull('is_standby');
            })
            ->orderBy('qc_start')
            ->get();
        if ($activeRows->isEmpty()) {
            self::schedLog()->info("[STANDBY] order {$order->order_no}: no active pump rows — skipping standby.");
            return;
        }

        // standby count = req_pump − used_pump (the active lanes already placed).
        $activeCount   = $activeRows->count();
        $standbyNeeded = $pumpQty - $activeCount;
        if ($standbyNeeded <= 0) {
            self::schedLog()->info("[STANDBY] order {$order->order_no}: used_pump ({$activeCount}) "
                . "covers req_pump ({$pumpQty}) — no standby pump needed.");
            return;
        }

        // Required capacity/type combinations for this order.
        $requirements = [];
        foreach (($order->order_pumps ?? []) as $op) {
            $requirements[] = [
                'capacity' => (float) $op->pump_size,
                'type'     => $op->type,
            ];
        }
        if (empty($requirements)) {
            self::schedLog()->info("[STANDBY] order {$order->order_no}: no pump requirements — skipping standby.");
            return;
        }

        // Pumps already serving THIS order (cannot also be its standby).
        $orderActivePumpNames = $activeRows->pluck('pump')->filter()->unique()->values()->all();

        // Pump IDs already assigned to ANY schedule in this run. pump_busy_slots
        // accumulates across the whole run (active lanes + standby reservations
        // already made earlier in this pass), so a standby must avoid all of them.
        $usedPumpIds = [];
        foreach (($scheduleData->pump_busy_slots ?? []) as $slot) {
            if (isset($slot['pump_id'])) {
                $usedPumpIds[$slot['pump_id']] = true;
            }
        }

        // Order pour window (widest across its active pumps). Standby rows shadow
        // it, and the template carries every column the schedule view needs.
        $template = (array) $activeRows->first();
        unset($template['id'], $template['created_at'], $template['updated_at']);
        $windowStart = Carbon::parse($template['qc_start']);
        $windowEnd   = Carbon::parse($template['return_end']);
        foreach ($activeRows as $row) {
            $s = Carbon::parse($row->qc_start);
            $e = Carbon::parse($row->return_end);
            if ($s->lt($windowStart)) {
                $windowStart = $s;
            }
            if ($e->gt($windowEnd)) {
                $windowEnd = $e;
            }
        }

        $rowsToInsert  = [];
        $reservedCount = 0;
        $chosenPumpIds = [];
        for ($n = 0; $n < $standbyNeeded; $n++) {
            // Walk the inventory and pick a free pump that matches a requirement,
            // is unused, hasn't already been picked as a standby, and is
            // available across the order window.
            $chosen = null;
            foreach (($scheduleData->pumps_availability ?? []) as $slot) {
                $pumpId = $slot['pump_id'] ?? null;
                if ($pumpId === null) {
                    continue;
                }
                if (isset($usedPumpIds[$pumpId])) {        // already on a schedule
                    continue;
                }
                if (isset($chosenPumpIds[$pumpId])) {      // already a standby this order
                    continue;
                }
                $matches = false;
                foreach ($requirements as $req) {
                    if (
                        (float) ($slot['pump_capacity'] ?? -1) === (float) $req['capacity']
                        && ($slot['pump_type'] ?? null) === $req['type']
                    ) {
                        $matches = true;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }
                if (isset($slot['free_from'], $slot['free_upto'])) {
                    $freeFrom = Carbon::parse($slot['free_from']);
                    $freeUpto = Carbon::parse($slot['free_upto']);
                    if ($freeFrom->gt($windowStart) || $freeUpto->lt($windowEnd)) {
                        continue;
                    }
                }
                $pump = Pump::find($pumpId);
                if (!$pump) {
                    continue;
                }
                if (in_array($pump->pump_name, $orderActivePumpNames, true)) {
                    continue;
                }
                $chosen = ['slot' => $slot, 'pump' => $pump, 'pump_id' => $pumpId];
                break;
            }

            if ($chosen === null) {
                self::schedLog()->info("[STANDBY] order {$order->order_no}: only {$reservedCount} of "
                    . "{$standbyNeeded} standby pump(s) could be reserved — no more free pumps.");
                break;
            }

            $standbyPump   = $chosen['pump'];
            $standbyPumpId = $chosen['pump_id'];
            $chosenPumpIds[$standbyPumpId] = true;
            $usedPumpIds[$standbyPumpId]   = true;

            // Standby schedule row: shadows the order window, flagged is_standby.
            // trip = 0 marks it as a non-pouring (reserve) lane.
            $standbyRow                 = $template;
            $standbyRow['pump']         = $standbyPump->pump_name;
            $standbyRow['trip']         = 0;
            $standbyRow['batching_qty'] = 0;
            $standbyRow['is_standby']   = 1;
            $rowsToInsert[] = $standbyRow;

            // Reserve it in-memory so the next standby pick (this or a lower-LPI
            // order later in the pass) can't reuse the same pump.
            $scheduleData->pump_busy_slots[] = [
                'start'      => $windowStart->copy(),
                'end'        => $windowEnd->copy(),
                'pump_id'    => $standbyPumpId,
                'type'       => $standbyPump->type,
                'capacity'   => $standbyPump->pump_capacity,
                'location'   => $order->site_id,
                'order_no'   => $order->order_no,
                'is_standby' => true,
            ];
            if (!in_array($standbyPump->pump_name, $scheduleData->assigned_pumps, true)) {
                $scheduleData->assigned_pumps[] = $standbyPump->pump_name;
            }

            $reservedCount++;
            self::schedLog()->info("[STANDBY] order {$order->order_no} (LPI " . ($order->lpi_score ?? 0) . "): "
                . "reserved pump {$standbyPump->pump_name} (id={$standbyPumpId}) as STANDBY "
                . "[{$windowStart->format('H:i')}–{$windowEnd->format('H:i')}].");
        }

        if (!empty($rowsToInsert)) {
            DB::table('selected_order_pump_schedules')->insert($rowsToInsert);
        }

        // standby_pump (the COUNT = req_pump − used_pump) is persisted by
        // recalcPumpDurations(); not written here.
        self::schedLog()->info("[STANDBY] order {$order->order_no} (LPI " . ($order->lpi_score ?? 0) . "): "
            . "req_pump={$pumpQty} used_pump={$activeCount} standby_pump={$standbyNeeded} "
            . "reserved={$reservedCount}.");
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
                        self::schedLog()->error('Schedule time error detected', [
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

    /**
     * Phase 1 — read the MAX req_plants across this run's orders, used by
     * Phase 2 to size how many plants to open initially.
     *
     * req_plants is computed and persisted once, at order-selection time, by
     * OrderController::calculateReqPlants(). The scheduler does NOT recompute or
     * re-save it here — it just reads the stored column. If an order has no
     * stored value yet (e.g. it predates the column being populated), it's
     * treated as 1 and a warning is logged so it can be re-selected; the
     * scheduler still has its own plant-opening fallback (open another plant on
     * a reject/partial), so a too-low value self-corrects at run time.
     */
    private function getMaxReqPlants(ScheduleData $scheduleData): int
    {
        $rows = SelectedOrder::where('group_company_id', $scheduleData->company)
            ->where('user_id', $scheduleData->user_id)
            ->whereBetween('delivery_date', [$scheduleData->shift_start, $scheduleData->shift_end])
            ->where('selected', true)
            ->get(['order_no', 'req_plants']);

        $maxReqPlants = 1;
        $missing = [];

        foreach ($rows as $row) {
            $reqPlants = (int) ($row->req_plants ?? 0);
            if ($reqPlants < 1) {
                $missing[] = $row->order_no;
                $reqPlants = 1;
            }
            $maxReqPlants = max($maxReqPlants, $reqPlants);
        }

        if (!empty($missing)) {
            self::schedLog()->warning("[REQ_PLANTS] No stored req_plants for order(s) [" . implode(',', $missing) . "] "
                . "— treated as 1. Re-run order selection so OrderController persists them.");
        }

        self::schedLog()->info("[REQ_PLANTS] max req_plants across run = {$maxReqPlants} (read from stored values)");
        return $maxReqPlants;
    }

    /**
     * Return the bps_availability rows belonging to the first $n DISTINCT plants
     * (by plant_name, in their existing order). Used to open a bounded subset of
     * the fleet: a plant can have several free-window rows, so we filter by name
     * rather than slicing the raw array.
     */
    private function firstNDistinctPlants(array $bps, int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        // Group distinct plant names by location, preserving first-seen order
        // for both the locations and the plants within each location. This keeps
        // the result deterministic and monotonic (opening N+1 plants always
        // includes the same N), which the incremental "open one more" logic relies on.
        $byLocation    = [];   // location => [plant_name, ...]
        $locationOrder = [];   // locations in first-seen order
        $seenPlant     = [];
        foreach ($bps as $p) {
            $name = $p['plant_name'] ?? null;
            if ($name === null || isset($seenPlant[$name])) {
                continue;
            }
            $seenPlant[$name] = true;
            $loc = $p['location'] ?? '__none__';
            if (!isset($byLocation[$loc])) {
                $byLocation[$loc] = [];
                $locationOrder[]  = $loc;
            }
            $byLocation[$loc][] = $name;
        }

        // Round-robin across locations so the opened set SPANS locations instead
        // of draining one location first (which starved the second location and
        // meant its plants were never available to predictBestPlant()).
        $allowed   = [];
        $exhausted = false;
        while (count($allowed) < $n && !$exhausted) {
            $exhausted = true;
            foreach ($locationOrder as $loc) {
                if (!empty($byLocation[$loc])) {
                    $allowed[] = array_shift($byLocation[$loc]);
                    $exhausted = false;
                    if (count($allowed) >= $n) {
                        break;
                    }
                }
            }
        }

        return array_values(array_filter(
            $bps,
            fn($p) => in_array($p['plant_name'] ?? null, $allowed, true)
        ));
    }

    /**
     * Pump usage and pump-aware durations, computed once the run's plant
     * availability ($availablePlants = distinct plants in the fleet) is known.
     *
     * For each order it persists:
     *   • used_pump    = pumps actually runnable given the plants:
     *                    pumps_per_plant = floor(pouring_time / loading_time) (min 1)
     *                    used_pump = min(pump_qty, availablePlants * pumps_per_plant)
     *                    (single/no-pump orders: used_pump = pump_qty)
     *   • standby_pump = pump_qty - used_pump
     *
     * For MULTI-pump orders (used_pump > 1) it also recomputes and persists
     * expected_duration & max_delay, because parallel pumps shorten the pour
     * (effective pouring = pouring_time / used_pump). These overwrite the base
     * values OrderController stored (which assume a single pump). Done before
     * generateSchedule() so the refreshed fetchOrders() picks them up.
     *
     * NOTE: the duration/max_delay formulas mirror OrderController's
     * calculateExpectedDuration()/setMaxDelay(); they live here too only because
     * the parallel-pump variant needs plant availability, which OrderController
     * doesn't have. Keep the two in sync if the base formula changes.
     */
    private function recalcPumpDurations(ScheduleData $scheduleData, int $availablePlants): void
    {
        $orders = SelectedOrder::where('group_company_id', $scheduleData->company)
            ->where('user_id', $scheduleData->user_id)
            ->whereBetween('delivery_date', [$scheduleData->shift_start, $scheduleData->shift_end])
            ->where('selected', true)
            ->get();

        foreach ($orders as $order) {
            $isPump  = (bool) ($order->pump ?? false);
            $pumpQty = (int) ($order->pump_qty ?? 0);

            if (!$isPump || $pumpQty <= 1) {
                $usedPump    = max(0, $pumpQty);
                $standbyPump = 0;
            } else {
                $pouringTime   = max(1, (int) ($order->pouring_time ?? 0));
                $loadingTime   = max(1, (int) ($order->loading_time ?? 0));
                $pumpsPerPlant = max(1, (int) floor($pouringTime / $loadingTime));
                $capacity      = max(1, $availablePlants) * $pumpsPerPlant;
                $usedPump      = max(1, min($pumpQty, $capacity));
                $standbyPump   = max(0, $pumpQty - $usedPump);
            }

            // standby_pump = req_pump − used_pump (the pumps requested but not
            // actively used; these are the order's standby pumps).
            $update = [
                'used_pump'    => $usedPump,
                'standby_pump' => $standbyPump,
            ];

            if ($isPump && $usedPump > 1) {
                $expected = $this->computeExpectedDuration($order, $usedPump);
                $maxDelay = $this->computeMaxDelay($expected, (bool) $order->flexibility);
                $update['expected_duration'] = $expected;
                $update['max_delay']         = $maxDelay;
                self::schedLog()->info("[PUMP_DUR] Order {$order->order_no}: plants={$availablePlants} "
                    . "pump_qty={$pumpQty} used_pump={$usedPump} standby={$standbyPump} "
                    . "→ expected_duration={$expected} max_delay={$maxDelay}");
            } else {
                self::schedLog()->info("[PUMP_DUR] Order {$order->order_no}: used_pump={$usedPump} "
                    . "standby={$standbyPump} (no duration change)");
            }

            try {
                DB::table('selected_orders')->where('id', $order->id)->update($update);
            } catch (\Throwable $e) {
                self::schedLog()->warning("[PUMP_DUR] Could not save pump usage for order {$order->order_no} "
                    . "— add used_pump/standby_pump columns via migration. " . $e->getMessage());
            }
        }
    }

    /**
     * Parallel-pump expected_duration. Mirrors OrderController::calculateExpected-
     * Duration but divides the pouring across used_pump pumps. For used_pump <= 1
     * it returns the same value as the base formula.
     */
    private function computeExpectedDuration($order, int $usedPump): int
    {
        $batchSize   = 8;
        $quantity    = (float) ($order->quantity ?? 0);
        $pouringTime = max(1, (int) ($order->pouring_time ?? 0));
        $hasPump     = (bool) $order->pump;
        $usedPump    = max(1, $usedPump);
        $numTrips    = max(1, (int) ceil($quantity / $batchSize));

        // Parallel pumps → effective per-batch pouring divided across used_pump.
        $effPouring = ($hasPump && $usedPump > 1)
            ? max(1, (int) ceil($pouringTime / $usedPump))
            : $pouringTime;

        // base_interval is pouring-driven; recompute the trip gap from the
        // effective pouring (leaves the stored base_interval column untouched).
        $customerInterval = (int) ($order->interval ?? 0);
        $interval = ($hasPump && $usedPump > 1)
            ? max($effPouring, $customerInterval)
            : (int) ($order->base_interval ?? 0);

        $lastTripQty     = $quantity - ($batchSize * ($numTrips - 1));
        $lastPouringTime = ($lastTripQty < $batchSize)
            ? max(1, (int) round(($effPouring / $batchSize) * $lastTripQty + 1))
            : $effPouring;

        if ($numTrips <= 1) {
            return $effPouring;
        }
        return (($numTrips - 1) * $interval) + $lastPouringTime;
    }

    /**
     * max_delay from expected_duration. Mirrors OrderController::setMaxDelay:
     * base = ceil(expected_duration / 3); flexible floors it at 60, non-flexible
     * caps it at 60.
     */
    private function computeMaxDelay(int $expectedDuration, bool $isFlexible): int
    {
        $maxDelayMins = max(1, (int) ceil($expectedDuration / 3));
        return $isFlexible
            ? max($maxDelayMins, 60)
            : min($maxDelayMins, 60);
    }

    /**
     * Re-fetch the run's orders (with locations resolved and LPI-sorted), exactly
     * as generateSchedule() prepares them. Used after a plant opens so the next
     * incremental pass sees the freshly-recomputed expected_duration / max_delay /
     * used_pump from the DB.
     */
    private function reloadOrders(ScheduleData $scheduleData)
    {
        $orders = $this->fetchOrders($scheduleData);
        $this->getLocations($orders, $scheduleData);
        return $orders->sortByDesc('lpi_score')->values();
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
        // self::schedLog()->info("[PUMP_PRIORITY] reservePumpsByPriority ENTERED v3 (orders="
        //     . $allOrders->count() . ", trips=" . count($sortedTrips) . ")");
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
                // self::schedLog()->info("[PUMP_PRIORITY]   order {$orderNo} pump=yes but order_pumps "
                //     . "is empty — skipped (cannot match a pump without capacity/type)");
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
            // self::schedLog()->info("[PUMP_PRIORITY] only " . count($pumpOrders)
            //     . " pump order(s) with matchable requirements — no contention to resolve");
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
        // self::schedLog()->info("[PUMP_PRIORITY] pass start — " . count($pumpOrders) . " pump order(s); "
        //     . "available pumps: [{$inventory}]");
        foreach ($pumpOrders as $po) {
            $reqStr = collect($po['requirements'])
                ->map(fn($r) => $r['capacity'] . '/' . $r['type'])
                ->implode(' + ');
            // self::schedLog()->info("[PUMP_PRIORITY]   order {$po['order_no']} LPI={$po['lpi']} "
            //     . "window=[{$po['start']->format('H:i')}–{$po['end']->format('H:i')}] "
            //     . "needs=[{$reqStr}]");
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
                    // self::schedLog()->info("[PUMP_PRIORITY]   ✗ order {$po['order_no']} (LPI {$po['lpi']}) "
                    //     . "could NOT reserve {$req['capacity']}/{$req['type']} — "
                    //     . ($matched === 0
                    //         ? "no pump of this capacity/type exists (so pump path is a no-op for it)"
                    //         : "all {$matched} matching pump(s) busy in its window → scheduled after winner"));
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

                // self::schedLog()->info("[PUMP_PRIORITY] reserved pump {$chosenPumpId} for order "
                //     . "{$po['order_no']} (LPI {$po['lpi']}) "
                //     . "[{$po['start']->format('H:i')}–{$po['end']->format('H:i')}]");
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
            self::schedLog()->warning("[PUMP_RESCHEDULE] No matching pump free within shift end {$shiftEnd->format('H:i')}");
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
    private function predictBestPlant(
        ScheduleData $scheduleData,
        $order,
        string $location
    ): ?string {
        // Pre-filter candidates (no collect in loop)
        $candidates = [];
        $seen = [];

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
            $locationBonus = (((string) ($plantLocation[$plantName] ?? '')) === (string) $location) ? 500 : 0;
            $score = ($tripsOk * 1000) - $totalDelay - ($existingLoad * 2) + $locationBonus;
            //$score = ($tripsOk * 1000) - $totalDelay - ($existingLoad * 2);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPlant = $plantName;
            }
        }
        return $bestPlant;
    }
    private function scorePlantCandidates(
        ScheduleData $scheduleData,
        $order,
        string $location,
        array $candidates
    ): ?string {
        if (empty($candidates)) return null;

        $totalTrips      = (int) ceil($order->quantity / max(1, $scheduleData->truck_capacity));
        $loadingDuration = $scheduleData->loading_time;
        $intervalMinutes = max(1, (int) ($order->base_interval ?? $order->interval));

        // Pre-index availability slots by plant name (timestamps for fast comparison)
        $plantSlots = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'];
            if (!in_array($pn, $candidates, true)) {
                continue;
            }
            $plantSlots[$pn][] = [
                'from_ts' => ($bp['free_from'] instanceof Carbon) ? $bp['free_from']->timestamp : Carbon::parse($bp['free_from'])->timestamp,
                'upto_ts' => ($bp['free_upto'] instanceof Carbon) ? $bp['free_upto']->timestamp : Carbon::parse($bp['free_upto'])->timestamp,
            ];
        }

        // Map plant_name -> its location, so the location bonus below can be
        // computed PER CANDIDATE (previously it wrongly read bps_availability[0]).
        $plantLocation = [];
        foreach ($scheduleData->bps_availability as $bp) {
            $pn = $bp['plant_name'] ?? null;
            if ($pn !== null && !isset($plantLocation[$pn])) {
                $plantLocation[$pn] = $bp['location'] ?? null;
            }
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

            // A plant that couldn't fit even the FIRST trip isn't actually
            // "free" for this order — skip it so it can never win purely by
            // being first-evaluated (bestScore starts at PHP_INT_MIN).
            if ($tripsOk === 0) continue;

            $existingLoad  = $plantLoad[$plantName] ?? 0;
            $locationBonus = (((string) ($plantLocation[$plantName] ?? '')) === (string) $location) ? 500 : 0;
            $score = ($tripsOk * 1000) - $totalDelay - ($existingLoad * 2) + $locationBonus;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPlant = $plantName;
            }
        }
        return $bestPlant;
    }
    private function predictBestPlant2(
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

        // ── Earliest-free among opened plants ─────────────────────────────
        // All candidates are already-OPENED plants (bps_availability only ever
        // holds the currently-opened fleet). Among the opened plants that are
        // FREE for this order — i.e. can accommodate ALL of its trips — pick the
        // one that becomes free EARLIEST: the plant whose trip-1 loading can
        // start at the smallest timestamp. Plant order is only the tie-breaker
        // when two plants can open the order at the exact same time. If no plant
        // can take the whole order, fall back to the plant with the best partial
        // fit (most trips, then earliest start), and finally the first candidate.
        $bestFullPlant   = null;   // earliest-free plant that fits the WHOLE order
        $bestFullStartTs = null;   // its trip-1 loading start (smaller = earlier)
        $fallbackPlant   = null;
        $fallbackTrips   = -1;
        $fallbackStartTs = null;

        foreach ($candidates as $plantName) {
            $slots = $plantSlots[$plantName] ?? [];
            if (empty($slots)) continue;

            $tripsOk        = 0;
            $firstTripStart = null; // earliest ts this plant can OPEN this order
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
                            if ($t === 1) {
                                $firstTripStart = $startTs;
                            }
                            $tripsOk++;
                            $loadingStartTs = $startTs + $intervalSecs;
                            $slotFound = true;
                            break 2;
                        }
                    }
                }
                if (!$slotFound) break;
            }

            // FREE = this plant can take the WHOLE order. Among all such plants
            // keep the one whose trip-1 loading starts earliest.
            if ($tripsOk >= min($totalTrips, 50) && $firstTripStart !== null) {
                if ($bestFullStartTs === null || $firstTripStart < $bestFullStartTs) {
                    $bestFullStartTs = $firstTripStart;
                    $bestFullPlant   = $plantName;
                }
                continue;
            }

            // Not free — track the best partial fit (most trips, then earliest
            // start) as a fallback in case no plant can take the whole order.
            if (
                $tripsOk > $fallbackTrips
                || ($tripsOk === $fallbackTrips
                    && $firstTripStart !== null
                    && ($fallbackStartTs === null || $firstTripStart < $fallbackStartTs))
            ) {
                $fallbackTrips   = $tripsOk;
                $fallbackPlant   = $plantName;
                $fallbackStartTs = $firstTripStart;
            }
        }

        // Prefer the earliest-free plant that can take the whole order; otherwise
        // the best-partial plant; finally the first candidate.
        return $bestFullPlant ?? $fallbackPlant ?? $candidates[0];
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
                self::schedLog()->info("Order {$order->order_no} (LPI: {$order->lpi_score}) rejected — no available plant can accommodate this order.");
            }
        }
        foreach ($plantBuckets as $pn => $b) {
            self::schedLog()->info("[PLANT_SIM] {$pn}: orders=[" . implode(',', $b['orders']) . "] "
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
    /**
     * The plant an order was committed on, if it is still present in the
     * currently open fleet. Returns null when the order has no pin yet (i.e.
     * it is not committed) or its pinned plant is not open on this pass — the
     * caller then falls back to predictBestPlant().
     */
    private function pinnedPlantFor(ScheduleData $scheduleData, $orderNo): ?string
    {
        $pinned = $this->pinnedPlant[$orderNo] ?? null;
        if ($pinned === null) {
            return null;
        }
        $open = array_column($scheduleData->bps_availability ?? [], 'plant_name');
        if (!in_array($pinned, $open, true)) {
            self::schedLog()->warning("[PLANT_PIN] order {$orderNo}: pinned plant {$pinned} is not "
                . "in the open fleet on this pass — re-predicting.");
            return null;
        }
        return $pinned;
    }

    /**
     * Promote the plant choices of a just-accepted pass into the pinned map,
     * for the committed orders only. From here on those orders re-use the
     * plant they were accepted on instead of being re-predicted.
     */
    private function pinCommittedPlants($committed, array $passChoice): void
    {
        $pins = [];
        foreach ($committed as $o) {
            $no = $o->order_no;
            if (isset($passChoice[$no]) && ($this->pinnedPlant[$no] ?? null) !== $passChoice[$no]) {
                $this->pinnedPlant[$no] = $passChoice[$no];
                $pins[] = "{$no}→{$passChoice[$no]}";
            }
        }
        if (!empty($pins)) {
            self::schedLog()->info("[PLANT_PIN] pinned to committed plant(s): " . implode(', ', $pins));
        }
    }

    /**
     * Pull a specific order's own recorded failure_reason out of a partials
     * array (as returned by detectPartialOrders via scheduleCandidateAtDelay).
     * Returns null when the order isn't present or carries no reason. Lets a
     * reject report the REAL cause (e.g. a pump-availability message) instead
     * of the generic "all plants at full capacity" fallback.
     */
    private function candidatePartialReason(array $partials, $candidate): ?string
    {
        foreach ($partials as $p) {
            $matches = (isset($p['id']) && (int) $p['id'] === (int) $candidate->id)
                || (isset($p['order_no']) && $p['order_no'] === $candidate->order_no);
            if ($matches) {
                $reason = trim((string) ($p['failure_reason'] ?? ''));
                return $reason !== '' ? $reason : null;
            }
        }
        return null;
    }
    /**
     * Append a note to $scheduleData->failure_reason without overwriting an
     * existing reason. Uses " | " as separator and skips a note that's already
     * present, so repeated retry passes don't stack identical lines.
     */
    private function appendFailureReason(ScheduleData $scheduleData, string $message): void
    {
        $existing = trim((string) ($scheduleData->failure_reason ?? ''));
        if ($existing === '') {
            $scheduleData->failure_reason = $message;
            return;
        }
        if (str_contains($existing, $message)) {
            return; // already recorded
        }
        $scheduleData->failure_reason = $existing . ' | ' . $message;
    }
   
}