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
    public $expected_total_duration;
    public $max_delay_minutes;
    public $needs_progressive_delay;
    public $deviation;
    public $assigned_plant;
    public $min_delivery_time;
    public $assigned_pumps_per_order;
    public $min_interval;
    public $max_interval;
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
    const DEFAULT_TRUCK_CAPACITY = 8; // Default reference capacity

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

            $scheduleData = new ScheduleData([
                'user_id'           => $user_id,
                'company'           => $company,
                'schedule_date'     => $schedule_date,
                'sch_adj_from'      => 0,
                'sch_adj_to'        => 1440,
                'tms_availability'  => $tmsAvailability,
                'pumps_availability' => $this->pumpHelper->getPumpsAvailability($company, $schedule_date, $pump_ids),
                'bps_availability'  => $this->batchingPlantHelper->getBatchingPlantAvailabilityCopy(
                    $company,
                    $schedule_date,
                    $batching_plant_ids,
                    $this->batchingPlantHelper->getMinOrderScheduleTimeCopy($company, $user_id, $shift_start, $shift_end, $schedule_date)
                ),
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
            //self::updateQcFromPreviousSlot();
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


            Log::info("[CHRONO_SCHED] ── Step 1: Generating all trips for " . $allOrders->count() . " orders ──");

            // ── Step 1: Pre-generate every trip for every order ───────────────────
            $allTrips = $this->generateAllOrderTrips($scheduleData, $allOrders);


            Log::info("[CHRONO_SCHED] ── Step 2: Total trips generated: " . count($allTrips) . " ──");
            $this->scheduleTripsChronologically($scheduleData, $allTrips, $allOrders);
        } catch (\Exception $ex) {
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Pre-generate all theoretical trip slots for every order.
     *
     * For each order we calculate:
     *   trip_1.pouring_start = delivery_date − total_time
     *   trip_N.pouring_start = trip_1.pouring_start + (N-1) × interval
     *
     * Returns a flat array of trip descriptors (one row per trip).
     */
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
            $interval = min($order->max_interval, $order->loading_time);
            $qcTime        = $scheduleData->qc_time;
            $inspTime      = $scheduleData->insp_time;
            $cleaningTime  = $scheduleData->cleaning_time;
            $location      = $order->location;
            $remainingQty  = $order->quantity;
            $totalTrips    = (int) ceil($order->quantity / max(1, $truckCapacity));

            $totalTimeMins = $loadingTime + $qcTime + $travelTime + $inspTime + 4;
            $trip1Loading  = Carbon::parse($order->delivery_date)->subMinutes($totalTimeMins);
            $previousLoadingStart = null;

            for ($trip = 1; $trip <= $totalTrips; $trip++) {
                $batchQty        = min($truckCapacity, $remainingQty);
                $tripLoadingTime = $loadingTime;
                $tripPouringTime = $pouringTime;

                if ($batchQty < self::DEFAULT_TRUCK_CAPACITY) {
                    $tripLoadingTime = (int) round(($loadingTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
                    $tripPouringTime = (int) round(($pouringTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty);
                }

                $interval = min($order->max_interval, $tripLoadingTime);
                // if($order->order_no === "116665")
                //     dd()




                if ($trip === 1) {
                    $loadingStart = $trip1Loading->copy();
                } else if ($interval < $tripLoadingTime) {
                    $loadingStart = $previousLoadingStart->copy()->addMinutes($interval);
                } else {
                    $loadingStart = $previousLoadingEnd->copy();
                }

                $loadingEnd    = $loadingStart->copy()->addMinutes($tripLoadingTime);
                $qcStart       = $loadingEnd->copy()->addMinute();
                $qcEnd         = $qcStart->copy()->addMinutes($qcTime);
                $travelStart   = $qcEnd->copy()->addMinute();
                $travelEnd     = $travelStart->copy()->addMinutes($travelTime);
                $inspStart     = $travelEnd->copy()->addMinute();
                $inspEnd       = $inspStart->copy()->addMinutes($inspTime);
                $pouringStart  = $inspEnd->copy()->addMinute();
                $pouringEnd    = $pouringStart->copy()->addMinutes($tripPouringTime);
                $cleaningStart = $pouringEnd->copy()->addMinute();
                $cleaningEnd   = $cleaningStart->copy()->addMinutes($cleaningTime);
                $returnStart   = $cleaningEnd->copy()->addMinute();
                $returnEnd     = $returnStart->copy()->addMinutes($returnTime);
                $previousLoadingStart = $loadingStart->copy();
                $previousLoadingEnd = $loadingEnd->copy();


                $allTrips[] = [
                    'order_sequence' => $orderIndex,
                    'order_quantity' => $order->quantity, // Track original order quantity for tie-breaking
                    'order_id'       => $order->id,
                    'order_no'       => $order->order_no,
                    'order_lpi_score' => $order->lpi_score,
                    'order_priority' => $order->priority,
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
                    'min_interval'    => $order->min_interval,
                    'max_interval'    => $order->max_interval,
                    'max_delay_minutes' => $order->max_delay,
                    // Base per-8m³ durations, used later to rescale if a bigger/smaller mixer is picked
                    'base_loading_time' => $loadingTime,
                    'base_pouring_time' => $pouringTime,
                    'travel_time_mins'  => $travelTime,
                    'return_time_mins'  => $returnTime,
                    'tolerance'         => $order->tolerance,
                ];

                $remainingQty -= $batchQty;
                $orderIndex++;


                Log::info("[GEN_TRIPS] order={$order->order_no} trip={$trip}/{$totalTrips} "
                    . "qty={$batchQty} loading_start=" . $loadingStart->format('H:i'));
            }
        }
        usort($allTrips, function ($a, $b) {

            // 1. Sort by loading start time (MAIN DRIVER)
            $aStart = Carbon::parse($a['pouring_start']);
            $bStart = Carbon::parse($b['pouring_start']);

            if (!$aStart->eq($bStart)) {
                return $aStart->lt($bStart) ? -1 : 1;
            }

            // 2. Higher priority first
            if ($a['order_priority'] !== $b['order_priority']) {
                return $b['order_priority'] <=> $a['order_priority'];
            }

            if ($a['order_lpi_score'] !== $b['order_lpi_score']) {
                return $b['order_lpi_score'] <=> $a['order_lpi_score'];
            }
        });

        return $allTrips;
    }

    /**
     * Walk through the pre-generated, chronologically-sorted trip list.
     * For each trip: assign a batching plant + transit mixer, build the schedule
     * entry, and update the resource pools — all in LPI Score DESC order so
     * plants and trucks are shared fairly across every order simultaneously.
     */


    private function scheduleTripsChronologically(ScheduleData &$scheduleData, array $sortedTrips, $allOrders): void
    {
        $orderMap       = $allOrders->keyBy('order_no');
        $orderSchedules = [];
        $orderDelivered = [];
        $orderFailed    = [];


        // ── Track cumulative delay per order so subsequent trips shift forward ──
        $orderCumulativeDelay  = [];  // order_no → total minutes delayed so far
        $bestPlantPerOrderTrip = [];  // order_no → best_plant committed on trip 1
        $pumpAssigned          = [];  // order_no → bool (pump reserved?)
        $orderRemainingQty     = [];  // order_no → remaining qty to still deliver
        $orderCompleted        = [];  // order_no → true once all qty delivered (stop scheduling further pre-gen trips)
        $orderTripCounter      = [];  // order_no → sequential trip number actually scheduled (1,2,3...)

        foreach ($sortedTrips as $tripData) {
            $orderNo = $tripData['order_no'];
            $order   = $orderMap[$orderNo] ?? null;
            if (!$order) continue;

            // Initialise remaining qty once
            if (!isset($orderRemainingQty[$orderNo])) {
                $orderRemainingQty[$orderNo] = (int) $order->quantity;
            }

            // Skip further pre-generated trips for orders that are already complete (bigger mixer delivered early)
            if (!empty($orderCompleted[$orderNo])) {
                Log::info("[CAP_SKIP] order={$orderNo} trip={$tripData['trip']} — order already fully delivered, skipping excess pre-gen trip.");
                continue;
            }

            // Skip further trips if this order already failed earlier
            if (isset($orderFailed[$orderNo])) {
                Log::info("[FAIL_SKIP] order={$orderNo} trip={$tripData['trip']} — order already marked failed, skipping.");
                continue;
            }

            $maxRetryMinutes = $tripData['trip'] > 1 ? $tripData['max_interval'] : $tripData['max_delay_minutes'];

            $location        = $tripData['location'];
            $totalTrips      = $tripData['total_trips'];

            // ── Apply any cumulative delay already accumulated for this order ─────
            $priorDelay = $orderCumulativeDelay[$orderNo] ?? 0;
            if ($priorDelay > 0) {
                $tripData = $this->shiftTripByMinutes($tripData, $priorDelay, $order, $scheduleData);
            }

            $retryOffset   = 0;
            $tripScheduled = false;

            while ($retryOffset <= $maxRetryMinutes) {

                $currentTrip = $retryOffset === 0
                    ? $tripData
                    : $this->shiftTripByMinutes($tripData, $retryOffset, $order, $scheduleData);

                if (Carbon::parse($currentTrip['loading_start'])->gt(Carbon::parse($scheduleData->shift_end))) {
                    $orderFailed[$orderNo] = "order={$orderNo} trip={$tripData['trip']} exceeded shift_end after {$retryOffset} min retry.";
                    Log::warning("[RETRY] " . $orderFailed[$orderNo]);
                    break;
                }

                if ($retryOffset === 0 && Carbon::parse($currentTrip['loading_start'])->lt(Carbon::parse($scheduleData->shift_start))) {
                    $orderFailed[$orderNo] = "order={$orderNo} trip={$tripData['trip']} is before shift_start.";
                    Log::warning("[RETRY] " . $orderFailed[$orderNo]);
                    break;
                }

                // ── Seed scheduleData with the cap=8 baseline (will be overwritten after truck pick) ──
                $scheduleData->order_no       = $orderNo;
                $scheduleData->location       = $location;
                $scheduleData->trip           = $currentTrip['trip'];
                $scheduleData->assigned_plant = $bestPlantPerOrderTrip[$orderNo] ?? null;
                $scheduleData->qc_time        = $currentTrip['qc_time'];
                $scheduleData->insp_time      = $currentTrip['insp_time'];
                $scheduleData->cleaning_time  = $currentTrip['cleaning_time'];
                $this->applyTripToScheduleData($scheduleData, $currentTrip);

                // ── STEP 1: PUMP CHECK — nothing consumed yet ─────────────────────
                if ($order->pump && $scheduleData->trip === 1) {

                    $alreadyReserved = collect($scheduleData->pump_busy_slots_unset)
                        ->contains('order_no', $orderNo);

                    if (!$alreadyReserved) {
                        $delay      = $this->getNextFreePumpSlot($scheduleData, $order, $scheduleData->pouring_start);
                        $delay_time = $delay['delay_minutes'];

                        if ($delay_time > 0) {
                            $pumpAssigned[$orderNo] = false;
                            if ($retryOffset + $delay_time > $maxRetryMinutes) {
                                $orderFailed[$orderNo] = "Pump unavailable. With  in Max allowed delay time: " .
                                    Carbon::parse($order->delivery_date)->addMinutes($maxRetryMinutes)->format('h:i A');
                                Log::warning("[PUMP_RETRY_EXCEEDED] " . $orderFailed[$orderNo]);
                                break;
                            }

                            Log::info("[PUMP_JUMP] order={$orderNo} pump busy, jumping +{$delay_time}min");
                            $reason = "Pump not found for order {$order->order_no}";
                            $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);

                            if (isset($scheduleData->batching_plant['data']['plant_name'])) {
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

                        Log::info("[PUMP] Reserved pump_id={$delay['pump_id']} for order={$orderNo} "
                            . "busy from " . $delay['pump_qc_start']->format('H:i')
                            . " to " . Carbon::parse($lastTripReturnEnd)->format('H:i'));
                    }
                    $pumpAssigned[$orderNo] = true;
                }
                if ($order->pump && $scheduleData->trip > 1 && empty($pumpAssigned[$orderNo])) {
                    $retryOffset++;
                    continue;
                }

                // ── STEP 2: TRUCK FIRST — decide the real capacity before anything else ──
                // On trip 1, assigned_plant is null → selector is free to pick any capacity.
                // On later trips, the committed plant is used as the fit constraint inside the helper.
                $this->assignTransitMixer(
                    $scheduleData,
                    $location,
                    $currentTrip['trip'],
                    $currentTrip['batching_qty'],
                    $order
                );

                if (!isset($scheduleData->transit_mixer['data']['truck_name'])) {
                    Log::info("[RETRY] order={$orderNo} trip={$tripData['trip']} "
                        . "no truck at offset +{$retryOffset}min — retrying +1min");
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

                    $retryOffset++;
                    continue;
                }

                $actualCapacity = (int) $scheduleData->transit_mixer['data']['truck_capacity'];
                $remainingForOrder = $orderRemainingQty[$orderNo];

                //if ((int) $actualCapacity > self::DEFAULT_TRUCK_CAPACITY) {
                $recalc = $this->recalculateTripForCapacity(
                    $currentTrip,
                    $actualCapacity,
                    (int) ($currentTrip['base_loading_time'] ?? $order->loading_time),
                    (int) ($currentTrip['base_pouring_time'] ?? $order->pouring_time),
                    $remainingForOrder
                );
                $currentTrip = $recalc;
                //}
                // ── STEP 3: RECALCULATE trip from actual mixer capacity ───────────

                $this->applyTripToScheduleData($scheduleData, $currentTrip);

                // Re-validate truck can fit the (possibly extended) return_end window
                $truckName = $scheduleData->transit_mixer['data']['truck_name'];
                $newLS     = Carbon::parse($scheduleData->loading_start);
                $newRE     = Carbon::parse($scheduleData->return_end);

                $truckConflict = false;
                foreach ($scheduleData->truck_busy_slots as $busy) {
                    if (($busy['truck_id'] ?? null) !== $truckName) continue;
                    $bs = Carbon::parse($busy['start']);
                    $be = Carbon::parse($busy['end']);
                    if ($newRE->gt($bs) && $newLS->lt($be)) {
                        $truckConflict = true;
                        break;
                    }
                }
                if ($truckConflict) {
                    Log::info("[CAP_RECHECK] order={$orderNo} trip={$currentTrip['trip']} "
                        . "truck={$truckName} cap={$actualCapacity} — extended window conflicts, retrying +1min");
                    $retryOffset++;
                    continue;
                }

                Log::info("[CAP_RECALC] order={$orderNo} trip={$currentTrip['trip']} "
                    . "mixer_cap={$actualCapacity} batch_qty={$currentTrip['batching_qty']} "
                    . "loading_time={$currentTrip['loading_time']} pouring_time={$currentTrip['pouring_time']} "
                    . "loading=" . $newLS->format('H:i') . "→" . Carbon::parse($scheduleData->loading_end)->format('H:i') . " "
                    . "return_end=" . $newRE->format('H:i'));
                if ($scheduleData->trip === 1 && $order->max_interval >= $order->loading_time) {
                    $plant = $this->predictBestPlant($scheduleData, $order, $location);
                    $scheduleData->assigned_plant = $plant;
                    $bestPlantPerOrderTrip[$orderNo] = $plant;
                }

                // ── STEP 4: PLANT — using the capacity-aware loading window ───────
                $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);


                if (!isset($scheduleData->batching_plant['data']['plant_name'])) {
                    Log::info("[RETRY] order={$orderNo} trip={$tripData['trip']} "
                        . "no batching plant at offset +{$retryOffset}min — retrying +1min");
                    $retryOffset++;
                    continue;
                }

                // if ($scheduleData->trip === 1 && $order->max_interval >= $order->loading_time) {
                //     $bestPlantPerOrderTrip[$orderNo] = $scheduleData->batching_plant['data']['plant_name'];
                // }

                // ── STEP 5: COMMIT ────────────────────────────────────────────────
                $entry = $this->createScheduleEntry($order, $scheduleData, $location, $currentTrip['trip']);
                $orderSchedules[$orderNo][] = $entry;
                $orderDelivered[$orderNo]   = ($orderDelivered[$orderNo] ?? 0) + $currentTrip['batching_qty'];

                // Deduct by ACTUAL capacity delivered (not a fixed 8)
                $orderRemainingQty[$orderNo] -= $currentTrip['batching_qty'];
                $isFinalTrip = $orderRemainingQty[$orderNo] <= 0;

                $this->updateResourcePoolsOnly($scheduleData, $order, $location);

                // Update pump busy-slot end whenever the order actually finishes
                // (may happen before the pre-generated final trip number if a larger mixer was used)
                if ($isFinalTrip && $order->pump) {
                    foreach ($scheduleData->pump_busy_slots_unset as &$busySlot) {
                        if ($busySlot['order_no'] === $orderNo) {
                            $busySlot['end'] = $scheduleData->return_end->copy();
                            Log::info("[PUMP] Updated busy slot end for order={$orderNo} "
                                . "end=" . $busySlot['end']->format('H:i') . " (actual final trip={$currentTrip['trip']})");
                            break;
                        }
                    }
                    unset($busySlot);
                    $orderCompleted[$orderNo] = true;
                }

                if ($retryOffset > 0) {
                    $orderCumulativeDelay[$orderNo] = $priorDelay + $retryOffset;
                    Log::info("[DELAY_PROPAGATE] order={$orderNo} trip={$currentTrip['trip']} "
                        . "delayed +{$retryOffset}min → cumulative={$orderCumulativeDelay[$orderNo]}min");
                }

                Log::info("[CHRONO_SCHED] ✓ order={$orderNo} trip={$currentTrip['trip']}/{$currentTrip['total_trips']}"
                    . " plant={$scheduleData->batching_plant['data']['plant_name']}"
                    . " truck={$scheduleData->transit_mixer['data']['truck_name']}"
                    . " cap={$actualCapacity}"
                    . " qty={$currentTrip['batching_qty']}"
                    . " remaining={$orderRemainingQty[$orderNo]}"
                    . " loading_start=" . $currentTrip['loading_start']->format('H:i')
                    . ($retryOffset > 0 ? " (delayed +{$retryOffset}min)" : "")
                    . ($isFinalTrip ? " [ORDER_COMPLETE]" : ""));

                $tripScheduled = true;
                break;
            }

            if (!$tripScheduled && !isset($orderFailed[$orderNo])) {
                $minInterval = $tripData['min_interval'] ?? 0;
                $maxInterval = $tripData['max_interval'] ?? 0;
                $tolerancePercent = $order->tolerance ?? 0;
                $delayMinutes = $tripData['max_delay_minutes'] ?? 0;
                $orderFailed[$orderNo] = "order={$orderNo}  "
                    . "could not be scheduled after {$tripData['max_delay_minutes']} min of retries "
                    . "(delay={$delayMinutes}min, min_interval={$minInterval}, max_interval={$maxInterval}, tolerance={$tolerancePercent}%).";
                Log::error("[CHRONO_SCHED] ✗ " . $orderFailed[$orderNo]);
            }
        }

        // ── Persist schedules ─────────────────────────────────────────────────────
        foreach ($orderSchedules as $orderNo => $entries) {
            $order = $orderMap[$orderNo];
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

                    Log::warning("[PUMP_NOT_FOUND] Order {$orderNo} cleared — no pump available.");
                    continue; // skip storeSchedules
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
                    Log::error("[CHRONO_SCHED] Order {$orderNo} fully failed: {$reason}");
                }
            }
        }
    }

    /**
     * Update truck + plant availability pools after a trip is committed.
     * Unlike updateResourceAvailability(), this does NOT call generateNextSlot()
     * because all trips are already pre-generated.
     */
    private function updateResourcePoolsOnly(ScheduleData &$scheduleData, $order, string $location): void
    {
        $truck      = $scheduleData->transit_mixer['data'];
        $truckIndex = $scheduleData->transit_mixer['index'];
        $plant      = $scheduleData->batching_plant['data'];
        $plantIndex = $scheduleData->batching_plant['index'];

        // ── Update truck availability ─────────────────────────────────────────────
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

        // ── Update plant availability ─────────────────────────────────────────────
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

        // ── Track assigned resources ──────────────────────────────────────────────
        if (!in_array($plant['plant_name'], $scheduleData->assigned_plants)) {
            $scheduleData->assigned_plants[] = $plant['plant_name'];
        }
        if (!in_array($truck['truck_name'], $scheduleData->assigned_tms)) {
            $scheduleData->assigned_tms[] = $truck['truck_name'];
        }

        // ── Update busy-slot logs (used for conflict validation) ──────────────────
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

    /**
     * Return a copy of $tripData with every Carbon timestamp shifted forward
     * by $minutes minutes. All scalar fields (qty, durations, etc.) are unchanged.
     */
    private function shiftTripByMinutes(array $tripData, int $minutes, $order, $scheduleData): array
    {
        $dateKeys = [
            'loading_start',
            'loading_end',
            'qc_start',
            'qc_end',
            'travel_start',
            'travel_end',
            'insp_start',
            'insp_end',
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
                $shifted[$key] = Carbon::parse($shifted[$key])->addMinutes($minutes);
            }
        }

        return $shifted;
    }

    /**
     * Rebuild a trip's quantity + time fields based on the ACTUAL assigned mixer capacity.
     *
     * The trip was pre-generated assuming capacity = 8. Once a real mixer is selected
     * (which may be 8 or larger), we:
     *   - set batching_qty = min(actualCapacity, remainingQty)
     *   - scale loading_time / pouring_time proportionally to batching_qty
     *   - recompute every downstream timestamp (qc, travel, insp, pouring, cleaning, return)
     * loading_start stays fixed; only durations and dependent timestamps change.
     */
    private function recalculateTripForCapacity(
        array $tripData,
        int $actualCapacity,
        int $baseLoadingTime,
        int $basePouringTime,
        int $remainingQty
    ): array {
        $batchQty = max(1, min($actualCapacity, $remainingQty));




        // Proportional scale — per-m³ rate from the 8m³ baseline
        $scaledLoading = max(1, (int) round(($baseLoadingTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty));
        $scaledPouring = max(0, (int) round(($basePouringTime / self::DEFAULT_TRUCK_CAPACITY) * $batchQty));

        $qcTime       = (int) ($tripData['qc_time'] ?? 0);
        $inspTime     = (int) ($tripData['insp_time'] ?? 0);
        $cleaningTime = (int) ($tripData['cleaning_time'] ?? 0);


        $extraLoadingTime = ($scaledLoading - $tripData['loading_time']);

        // Travel/return durations carried from pre-gen (they don't scale with capacity)
        $travelTime = (int) ($tripData['travel_time_mins'] ?? max(0, Carbon::parse($tripData['travel_start'])
            ->diffInMinutes(Carbon::parse($tripData['travel_end']))));
        $returnTime = (int) ($tripData['return_time_mins'] ?? max(0, Carbon::parse($tripData['return_start'])
            ->diffInMinutes(Carbon::parse($tripData['return_end']))));



        $loadingStart  = Carbon::parse($tripData['loading_start'])->copy()->subMinutes($extraLoadingTime);
     //   if($tripData['order_no'] === "116665" && $batchQty<8)
       //     dd($loadingStart->format('H:i'), $tripData['loading_start'], $extraLoadingTime, $scaledLoading, $tripData['loading_time']);
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
        $updated['pouring_start']  = $pouringStart;
        $updated['pouring_end']    = $pouringEnd;
        $updated['cleaning_start'] = $cleaningStart;
        $updated['cleaning_end']   = $cleaningEnd;
        $updated['return_start']   = $returnStart;
        $updated['return_end']     = $returnEnd;

        return $updated;
    }

    /**
     * Push recalculated trip fields into $scheduleData so assignBatchingPlant /
     * createScheduleEntry / updateResourcePoolsOnly all see the capacity-aware values.
     */
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
            "order_no",
            "customer",
            "project",
            "site",
            'item_type',
            "site_id",
            "location",
            'loading_time',
            'min_interval',
            'max_interval',
            'tolerance',
            'max_delay',
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
            Log::info("Batching Plant Assigned (single): {$trip}--{$scheduleData->batching_plant['data']['plant_name']} From: {$scheduleData->loading_start} To: {$scheduleData->loading_end}");
        } else {
            Log::info("Batching Plant Not found (single): {$trip} From: {$scheduleData->loading_start} To: {$scheduleData->loading_end}");
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
            // NOTE: do NOT push to truck_busy_slots here. At this point loading/return
            // timestamps may still be based on the cap=8 plan. The caller reruns
            // recalculateTripForCapacity() using the assigned mixer's actual capacity
            // and then updateResourcePoolsOnly() registers the correct capacity-aware slot.
            Log::info("[TRANSIT_MIXER_ASSIGNED] Trip {$trip} Truck={$scheduleData->transit_mixer['data']['truck_name']} Capacity={$scheduleData->transit_mixer['data']['truck_capacity']}");
        } else {
            Log::info("[TRANSIT_MIXER_NOT_FOUND] Trip {$trip} Order {$scheduleData->order_no}");
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

        $scheduleData->order_start_time = DB::table('selected_order_schedules as B')
            ->select(DB::raw('MIN(pouring_start) AS min_pour'))
            ->where('group_company_id', $scheduleData->company)
            ->where('user_id', $user_id)
            ->where('order_no', $order->order_no)
            ->first()->min_pour;

        $scheduleData->order_end_time = DB::table('selected_order_schedules as B')
            ->select(DB::raw('MAX(pouring_end) AS max_pour'))
            ->where('group_company_id', $scheduleData->company)
            ->where('user_id', $user_id)
            ->where('order_no', $order->order_no)
            ->first()->max_pour;

        $scheduleData->min_loading_start = DB::table('selected_order_schedules as B')
            ->select(DB::raw('MIN(loading_start) AS min_load'))
            ->where('group_company_id', $scheduleData->company)
            ->where('user_id', $user_id)
            ->where('order_no', $order->order_no)
            ->first()->min_load;

        DB::table('selected_orders as A')
            ->where('id', $order->id)
            ->update([
                'start_time'          => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                    ->where('group_company_id', $scheduleData->company)
                    ->where('user_id', $user_id)
                    ->where('order_no', $order->order_no)
                    ->first()->min_pour,
                'end_time'            => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                    ->where('group_company_id', $scheduleData->company)
                    ->where('user_id', $user_id)
                    ->where('order_no', $order->order_no)
                    ->first()->max_pour,
                'delivered_quantity'  => $scheduleData->delivered_quantity,
                'location'            => $scheduleData->location,
            ]);

        if ($order->pump) {
            DB::table("selected_order_pump_schedules")
                ->insert(array_values($scheduleData->selected_order_pump_schedules));
        }

        $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
        $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()
            ->diffInMinutes(Carbon::parse($order_deviation->start_time), false);

        DB::table("selected_orders")->where("id", $order->id)->update(['deviation' => $order_deviation]);
    }


    private function createScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {
        // NOTE: loading_time / pouring_time are already scaled to the assigned mixer's
        // actual capacity by recalculateTripForCapacity() in scheduleTripsChronologically.
        // Do NOT re-apply any capacity-based extra here (was causing double-add).

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
        Log::info("Order no " . $order->order_no . " Required pumps " . $pumpsRequired);
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
            // //check site to site travel pump
            // $siteToSite = PumpHelper::getOverlapPumps(
            //     $scheduleData,
            //     $scheduleData->pumps_availability,
            //     $order->id,
            //     $groupPourStart,
            //     $groupPumpEndTime,
            //     $cleanEnd,
            //     $requirements[$p],
            //     $slots,
            //     $order->site_id
            // );


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
            if ($siteToSite === null)
                Log::info("pick pump New order " . $order->order_no);
            else
                Log::info("pick pump Site to Site " . $order->order_no);

            if (!isset($scheduleData->pouring_pump['pump']['pump_name'])) {

                $reason = "Pump #{$pumpSeq} not found for order {$order->order_no} — "
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
                'interval' => $order->interval,
                'waiting' => $scheduleData->pouring_pump['waiting'] ?? 0
            ];
            //dd($scheduleData->pump_busy_slots);
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
            //Log::info("travel site to site minutes " . $minutes);
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
                ->orderBy('pouring_start') // order by pouring_start to make previous slot logic easy
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
                $inspEnd = $inspStart->copy()->addMinutes($previousSlot->insp_time); // default 5 min if 0
                $installStart = $inspEnd->copy()->addMinute();
                $installEnd = $installStart->copy()->addMinutes($slot->install_time); // default 5 min if 0
                $waitingStart = $installEnd->copy()->addMinute();
                $waitingEnd = Carbon::parse($slot->pouring_start)->subMinute();
                $waitingMinutes = $waitingStart->diffInMinutes($waitingEnd);
                $waitingMinutes = max($waitingMinutes, 0); // ensure not negative
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
            //Log::info("Qc update error" . $e->getMessage());
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
        $loadingTime = $scheduleData->loading_time ?? ConstantHelper::LOADING_TIME;
        $qcTime = $scheduleData->qc_time
            ?? GlobalSetting::where('group_company_id', $scheduleData->company)
            ->value('batching_quality_inspection')
            ?? ConstantHelper::QC_TIME;
        $inspTime = $scheduleData->insp_time
            ?? GlobalSetting::where('group_company_id', $scheduleData->company)
            ->value('site_quality_inspection')
            ?? ConstantHelper::INSP_TIME;

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
            $structRef = $order->item_type;

            $v = match (true) {
                $order->quantity >= 200 => 100,
                $order->quantity >= 100 => 80,
                $order->quantity >= 50 => 60,
                $order->quantity >= 20 => 40,
                default => 20,
            };
            if ($order->pump)
                $v = min(100, $v + 10);
            if ($order->pump_qty > 1)
                $v = min(100, $v + 5);

            $availableTrucks = collect($scheduleData->tms_availability)
                ->filter(
                    fn($t) =>
                    Carbon::parse($t['free_from'])->lte(Carbon::parse($order->delivery_date))
                )
                ->count();
            $truckScore = min(40, $availableTrucks * 10);

            $totalTime = $loadingTime + $qcTime + ($order->travel_to_site ?? 20) + $inspTime + 4;

            $shiftStartTime = Carbon::parse($scheduleData->shift_start);
            $leadMinutes = $shiftStartTime->diffInMinutes(Carbon::parse($order->delivery_date), false);

            $timeScore = $leadMinutes >= ($totalTime * 2)
                ? 40
                : max(0, (int) (($leadMinutes / max(1, $totalTime * 2)) * 40));

            $intervalScore = ($order->interval ?? 0) >= 10
                ? 20
                : max(0, (int) ((($order->interval ?? 0) / 10) * 20));

            $p = min(100, $truckScore + $timeScore + $intervalScore);

            $c = 0;

            if ((int) ($order->priority) <= 10)
                $c += 50;

            if (!(int) ($order->flexibility ?? 0))
                $c += 30;

            if ($order->is_critical)
                $c += 20;

            $c = min(100, $c);

            $lpi = round((0.50 * $v) + (0.30 * $p) + (0.20 * $c), 2);

            Log::info("[LPI] Order {$order->order_no} "
                . "structural_ref=" . ($structRef->name ?? 'none') . " "
                . "is_critical=" . ($order->is_critical ?? 0) . " "
                . "V={$v} P={$p} C={$c} LPI={$lpi}");



            try {
                DB::table('selected_orders')
                    ->where('id', $order->id)
                    ->update(['lpi_score' => $lpi]);
            } catch (\Throwable $e) {
                Log::warning("[LPI] Could not save lpi_score — run migration first. " . $e->getMessage());
            }
        }
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
        $earliestOffset = null; // store totalOffset for the winning pump

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

            // Free at start_time = no delay, return immediately
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
            'pump_qc_start' => $earliestFree->copy()->subMinutes($earliestOffset), // ← qc start for delayed pump
            'delay_minutes' => max(0, $delayMinutes),
        ];
    }
    private function predictBestPlant(
        ScheduleData $scheduleData,
        $order,
        string $location
    ): ?string {
        // Candidate plants at this location
        $candidates = collect($scheduleData->bps_availability)
            ->where('location', $location)
            ->pluck('plant_name')
            ->unique()
            ->values()
            ->toArray();

        if (empty($candidates)) {
            Log::info("[PREDICT_PLANT] Order {$order->order_no} — no candidates at location={$location}");
            return null;
        }

        $totalTrips      = (int) ceil($order->quantity / max(1, $scheduleData->truck_capacity));
        $loadingDuration = $scheduleData->loading_time;
        $intervalMinutes = max(1, (int) $order->interval);

        $bestPlant = null;
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $plantName) {
            $tripsOk    = 0;
            $totalDelay = 0;
            // Ideal loading_start for trip 1 (already computed in resetOrderVariables)
            $loadingStart = $scheduleData->loading_start->copy();

            for ($t = 1; $t <= $totalTrips; $t++) {
                $loadingEnd = $loadingStart->copy()->addMinutes($loadingDuration);

                // Slide forward (0 → MAX_INTERVAL) until plant has a free window
                $slotFound = false;
                for ($delay = 0; $delay <= 40; $delay++) {
                    $slotStart = $loadingStart->copy()->addMinutes($delay);
                    $slotEnd   = $slotStart->copy()->addMinutes($loadingDuration);

                    // Check restriction window — skip if loading would land inside it
                    if (
                        $scheduleData->restriction_start && $scheduleData->restriction_end &&
                        $slotStart->lt(Carbon::parse($scheduleData->restriction_end)) &&
                        $slotEnd->gt(Carbon::parse($scheduleData->restriction_start))
                    ) {
                        continue;
                    }

                    // Is the plant free for [slotStart, slotEnd]?
                    $free = collect($scheduleData->bps_availability)
                        ->where('plant_name', $plantName)
                        ->first(function ($row) use ($slotStart, $slotEnd) {
                            return Carbon::parse($row['free_from'])->lte($slotStart)
                                && Carbon::parse($row['free_upto'])->gte($slotEnd);
                        });

                    if ($free) {
                        $totalDelay += $delay;
                        $tripsOk++;
                        // Next trip's ideal start = this actual start + interval
                        $loadingStart = $slotStart->copy()->addMinutes($intervalMinutes);
                        $slotFound = true;
                        break;
                    }
                }

                if (!$slotFound) {
                    break; // plant cannot fit this trip even with full 40-min flex
                }
            }

            $score = ($tripsOk * 1000) - $totalDelay;

            Log::info("[PREDICT_PLANT] Order {$order->order_no} plant={$plantName} "
                . "trips={$tripsOk}/{$totalTrips} delay={$totalDelay} score={$score}");

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPlant = $plantName;
            }
        }

        Log::info("[PREDICT_PLANT] Order {$order->order_no} → SELECTED plant={$bestPlant} score={$bestScore}");
        return $bestPlant;
    }
}
