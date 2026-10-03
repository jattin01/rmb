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
            Log::info("[CHRONO_SCHED] ── Step 0: Pre-engine feasibility filter on " . $allOrders->count() . " orders ──");
            //$allOrders = $this->filterSchedulableOrders($scheduleData, $allOrders);
            if ($allOrders->isEmpty()) {
                Log::warning("[CHRONO_SCHED] No orders passed the feasibility filter — engine has nothing to schedule.");
                return;
            }
            $pristine = $this->snapshotPools($scheduleData);
            $attempt        = 0;
            $maxAttempts    = $allOrders->count() * 10 + 30;
            $partialReasons = [];

            // Generate trips only ONCE
            $allTrips = $this->generateAllOrderTrips($scheduleData, $allOrders);
            Log::info("[INITIAL] generated " . count($allTrips) . " trip(s)");

            while ($attempt < $maxAttempts) {
                $attempt++;
                Log::info("[ATTEMPT {$attempt}] starting schedule pass with " . count($allTrips) . " trip(s) for " . $allOrders->count() . " order(s)");

                $this->restorePools($scheduleData, $pristine);
                $this->clearPreviousSchedules(
                    $scheduleData->company,
                    $scheduleData->user_id,
                    $scheduleData->shift_start,
                    $scheduleData->shift_end
                );

                $this->scheduleTripsChronologically($scheduleData, $allTrips, $allOrders);
                $partials = $this->detectPartialOrders($scheduleData, $allOrders);
                if (empty($partials)) {
                    Log::info("[ATTEMPT {$attempt}] ✓ no partial orders — schedule accepted");
                    break;
                }

                usort($partials, fn($a, $b) => $b['lpi_score'] <=> $a['lpi_score']);
                $highestPartial = $partials[0];
                $partialOrder   = $allOrders->firstWhere('id', $highestPartial['id']);

                if (!$partialOrder) {
                    Log::error("[ATTEMPT {$attempt}] could not find partial order — aborting loop");
                    break;
                }

                $truckCapacity = self::DEFAULT_TRUCK_CAPACITY;
                $loadingTime   = (int) ($partialOrder->loading_time ?? ConstantHelper::LOADING_TIME);
                $totalTrips    = (int) ceil($partialOrder->quantity / max(1, $truckCapacity));
                $partialWindow = $this->estimateOrderWindow($partialOrder, $scheduleData, $totalTrips, $loadingTime);

                Log::info("[ATTEMPT {$attempt}] partial order {$partialOrder->order_no} (LPI: {$partialOrder->lpi_score}) "
                    . "window: " . $partialWindow['start']->format('H:i') . " - " . $partialWindow['end']->format('H:i'));

                $overlapping = collect();
                // foreach ($allOrders as $candidate) {
                //     if ($candidate->id === $partialOrder->id) continue;
                //     if ((float) ($candidate->lpi_score ?? 0) >= (float) $partialOrder->lpi_score) continue;

                //     $candTrips   = (int) ceil($candidate->quantity / max(1, $truckCapacity));
                //     $candLoading = (int) ($candidate->loading_time ?? ConstantHelper::LOADING_TIME);
                //     $candWindow  = $this->estimateOrderWindow($candidate, $scheduleData, $candTrips, $candLoading);

                //     if ($this->windowsOverlap($partialWindow, $candWindow)) {
                //         $overlapping->push($candidate);
                //     }
                // }

                if ($overlapping->isEmpty()) {
                    $victim     = $partialOrder;
                    $victimType = 'partial-self (no lower overlap)';
                } else {
                    $victim = $overlapping->sort(function ($a, $b) {
                        $aPump = (bool) ($a->pump ?? false);
                        $bPump = (bool) ($b->pump ?? false);
                        if ($aPump !== $bPump) {
                            return $aPump ? 1 : -1;
                        }
                        $cmp = ((float) ($a->lpi_score ?? 0)) <=> ((float) ($b->lpi_score ?? 0));
                        if ($cmp !== 0) return $cmp;
                        return ((int) ($a->quantity ?? 0)) <=> ((int) ($b->quantity ?? 0));
                    })->first();
                    $victimType = 'sacrifice (overlapping lower LPI)';
                }

                $reason = "This order could not be scheduled because all available plants are at full capacity.";

                Log::info("[ATTEMPT {$attempt}] removing order {$victim->order_no} "
                    . "(LPI: {$victim->lpi_score}, type: {$victimType})");

                $partialReasons[$victim->id] = $reason;
                $allOrders = $allOrders->reject(fn($o) => $o->id === $victim->id)->values();

                // Just filter out victim's trips — no regeneration
                $victimOrderNo = $victim->order_no;
                $allTrips = array_values(array_filter($allTrips, fn($t) => $t['order_no'] !== $victimOrderNo));

                Log::info("[ATTEMPT {$attempt}] removed trips for order {$victimOrderNo}, remaining trips: " . count($allTrips));

                if ($allOrders->isEmpty()) {
                    $this->restorePools($scheduleData, $pristine);
                    $this->clearPreviousSchedules(
                        $scheduleData->company,
                        $scheduleData->user_id,
                        $scheduleData->shift_start,
                        $scheduleData->shift_end
                    );
                    break;
                }
            }
            foreach ($partialReasons as $orderId => $reason) {
                try {
                    DB::table('selected_orders')
                        ->where('id', $orderId)
                        ->update([
                            'failure_reason'     => $reason,
                            'delivered_quantity'  => 0,
                            'start_time'         => null,
                            'end_time'           => null,
                        ]);
                } catch (\Throwable $e) {
                    Log::warning("[PARTIAL_PERSIST] could not save failure_reason for order_id={$orderId}: " . $e->getMessage());
                }
            }
        } catch (\Exception $ex) {
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
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
            if ($delivered >= 0 && $delivered < $ordered) {
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
            $interval = $order->base_interval;
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
            // $aStart = Carbon::parse($a['loading_start']);
            // $bStart = Carbon::parse($b['loading_start']);
            // if (!$aStart->eq($bStart)) {
            //     return $aStart->lt($bStart) ? -1 : 1;
            // }
            if ($a['trip'] !== $b['trip']) {
                return ($a['trip'] ?? 1) <=> ($b['trip'] ?? 1);
            }

           

            if ($a['order_lpi_score'] !== $b['order_lpi_score']) {
                return $b['order_lpi_score'] <=> $a['order_lpi_score'];
            }
        });
        return $allTrips;
    }
    private function scheduleTripsChronologically(ScheduleData &$scheduleData, array $sortedTrips, $allOrders): void
    {
        $orderMap       = $allOrders->keyBy('order_no');
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
        $orderLastPouringEnd    = [];  // Track last trip pouring_end (for pump waiting)
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
                $maxRetryMinutes = $delayLimit;

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

            // Always apply cumulative delay from previous trips
            if ($priorDelay > 0) {
                $tripData = $this->shiftTripByMinutes($tripData, $priorDelay, $order, $scheduleData);
            }

            // ── Build retry sequence ─────────────────────────────────────
            // Trip 1: only forward (0, +1, +2, ...)
            // Trip 2+: try original(0), then early(-1..-5), then delay(+1..)
            //   Early: shift trip to find free plant/truck, add waiting, DON'T propagate
            //   Delay: shift trip forward, propagates to subsequent trips
            $retrySequence = [0];
            if ($tripData['trip'] > 1) {
                for ($e = -1; $e >= -5; $e--) {
                    $retrySequence[] = $e;
                }
            }
            for ($d = 1; $d <= $maxRetryMinutes; $d++) {
                $retrySequence[] = $d;
            }

            $tripScheduled = false;
            foreach ($retrySequence as $retryOffset) {
                if ($retryOffset === 0) {
                    $currentTrip = $tripData;
                } else {
                    $currentTrip = $this->shiftTripByMinutes($tripData, $retryOffset, $order, $scheduleData);
                }

                $scheduleData->order_no       = $orderNo;
                $scheduleData->location       = $location;
                $scheduleData->trip           = $currentTrip['trip'];
                $scheduleData->assigned_plant = $bestPlantPerOrderTrip[$orderNo] ?? null;
                $scheduleData->qc_time        = $currentTrip['qc_time'];
                $scheduleData->insp_time      = $currentTrip['insp_time'];
                $scheduleData->cleaning_time  = $currentTrip['cleaning_time'];
                $this->applyTripToScheduleData($scheduleData, $currentTrip);
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
                                break;
                            }
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
                            // foreach handles next offset
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
                    // foreach handles next offset
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
                    // foreach handles next offset
                    continue;
                }
                $remainingForOrder = $orderRemainingQty[$orderNo];
                if ($remainingForOrder <= 0) {
                    $orderCompleted[$orderNo] = true;
                    $tripScheduled = true;
                    break;
                }
                $this->applyTripToScheduleData($scheduleData, $currentTrip);
                if ((int) ($currentTrip['batching_qty'] ?? 0) <= 0) {
                    $orderCompleted[$orderNo] = true;
                    $tripScheduled = true;
                    break;
                }
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
                    // foreach handles next offset
                    continue;
                }
                if ($scheduleData->trip === 1 && $order->base_interval >= $order->loading_time) {
                    $plant = $this->predictBestPlant($scheduleData, $order, $location);
                    $scheduleData->assigned_plant = $plant;
                    $bestPlantPerOrderTrip[$orderNo] = $plant;
                }
                $this->assignBatchingPlant($scheduleData, $location, $currentTrip['trip'], $order);
                if (!isset($scheduleData->batching_plant['data']['plant_name'])) {
                    $nextPlantFree = $this->nextFreeMinutes($scheduleData->bps_availability, $currentTrip['loading_start']);
                    // foreach handles next offset
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
                        unset($orderSchedules[$orderNo]);
                        break;
                    }
                }

                // ── Pump waiting (trip 2+): truck waits until prev pour ends ─
                // For pump orders: after insp_end, truck waits at site until
                // the previous trip's pouring finishes. Then starts pouring.
                // For early sends (retryOffset < 0): same logic — truck arrived
                // early, waits for pump. Only THIS trip gets waiting, not all.
                if ($currentTrip['trip'] > 1 && isset($orderLastPouringEnd[$orderNo])) {
                    $prevPouringEnd = $orderLastPouringEnd[$orderNo];
                    $truckArrival   = $scheduleData->insp_end->copy()->addMinute();

                    if ($truckArrival->lt($prevPouringEnd)) {
                        $waitMins = (int) $truckArrival->diffInMinutes($prevPouringEnd);
                        $scheduleData->waiting_start = $truckArrival->copy();
                        $scheduleData->waiting_end   = $prevPouringEnd->copy()->subMinute();
                        $scheduleData->waiting_time  = max(0, $waitMins);

                        // Pouring starts after previous trip finishes
                        $scheduleData->pouring_start = $prevPouringEnd->copy()->addMinute();
                        $scheduleData->pouring_end   = $scheduleData->pouring_start->copy()
                            ->addMinutes($currentTrip['pouring_time']);

                        // Recalculate cleaning & return from new pouring_end
                        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
                        $scheduleData->cleaning_end   = $scheduleData->cleaning_start->copy()
                            ->addMinutes($scheduleData->cleaning_time);
                        $scheduleData->return_start   = $scheduleData->cleaning_end->copy()->addMinute();
                        $scheduleData->return_end     = $scheduleData->return_start->copy()
                            ->addMinutes($currentTrip['return_time_mins'] ?? 0);

                        Log::info("[WAITING] order={$orderNo} trip={$currentTrip['trip']} "
                            . "offset={$retryOffset}min "
                            . "truck_arrival={$truckArrival->format('H:i')} "
                            . "prev_pour_end={$prevPouringEnd->format('H:i')} "
                            . "waiting={$waitMins}min "
                            . "pouring_start={$scheduleData->pouring_start->format('H:i')}");
                    }
                }

                // ── Commit resources ──────────────────────────────────────
                $entry = $this->createScheduleEntry($order, $scheduleData, $location, $currentTrip['trip']);
                $orderSchedules[$orderNo][] = $entry;
                $orderDelivered[$orderNo]   = ($orderDelivered[$orderNo] ?? 0) + $currentTrip['batching_qty'];
                $orderRemainingQty[$orderNo] -= $currentTrip['batching_qty'];
                $isFinalTrip = $orderRemainingQty[$orderNo] <= 0;
                $this->updateResourcePoolsOnly($scheduleData, $order, $location);

                // Track pouring_end for next trip's waiting calculation
                $orderLastPouringEnd[$orderNo] = Carbon::parse($scheduleData->pouring_end)->copy();

                if ($isFinalTrip && $order->pump) {
                    foreach ($scheduleData->pump_busy_slots_unset as &$busySlot) {
                        if ($busySlot['order_no'] === $orderNo) {
                            $busySlot['end'] = $scheduleData->return_end->copy();
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
                } else {
                    $orderFailed[$orderNo] = "Could not schedule within constraints. "
                        . "Max retry of {$maxRetryMinutes} minutes exhausted.";
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
        $dateKeys = [
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
                $shifted[$key] = Carbon::parse($shifted[$key])->addMinutes($minutes);
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

        // ── Persist actual delay (actual_duration − expected_duration) ────
        // This is the client's definition of "delay time" from meeting 2026-05-18
        if ($scheduleData->order_start_time && $scheduleData->order_end_time) {
            $actualDuration   = (int) Carbon::parse($scheduleData->order_start_time)
                ->diffInMinutes(Carbon::parse($scheduleData->order_end_time));
            $expectedDuration = (int) ($order->expected_duration ?? $actualDuration);
            $actualDelay      = max(0, $actualDuration - $expectedDuration);

            DB::table('selected_orders')
                ->where('id', $order->id)
                ->update([
                    'actual_delay'      => $actualDelay,
                    'expected_duration' => $expectedDuration,
                ]);

            Log::info("[STORED_DELAY] order={$order->order_no} "
                . "expected={$expectedDuration}min actual={$actualDuration}min "
                . "delay={$actualDelay}min max_allowed={$order->max_delay}min");
        }
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

            //Log::info($breakdown);

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
    private function predictBestPlant(
        ScheduleData $scheduleData,
        $order,
        string $location
    ): ?string {
        $candidates = collect($scheduleData->bps_availability)
            ->where('location', $location)
            ->pluck('plant_name')
            ->unique()
            ->values()
            ->toArray();
        if (empty($candidates)) {
            return null;
        }
        $totalTrips      = (int) ceil($order->quantity / max(1, $scheduleData->truck_capacity));
        $loadingDuration = $scheduleData->loading_time;
        $intervalMinutes = max(1, (int) ($order->base_interval ?? $order->interval));
        $bestPlant = null;
        $bestScore = PHP_INT_MIN;
        foreach ($candidates as $plantName) {
            $tripsOk    = 0;
            $totalDelay = 0;
            $loadingStart = $scheduleData->loading_start->copy();
            for ($t = 1; $t <= $totalTrips; $t++) {
                $loadingEnd = $loadingStart->copy()->addMinutes($loadingDuration);
                $slotFound = false;
                for ($delay = 0; $delay <= 720; $delay++) {
                    $slotStart = $loadingStart->copy()->addMinutes($delay);
                    $slotEnd   = $slotStart->copy()->addMinutes($loadingDuration);
                    if (
                        $scheduleData->restriction_start && $scheduleData->restriction_end &&
                        $slotStart->lt(Carbon::parse($scheduleData->restriction_end)) &&
                        $slotEnd->gt(Carbon::parse($scheduleData->restriction_start))
                    ) {
                        continue;
                    }
                    $free = collect($scheduleData->bps_availability)
                        ->where('plant_name', $plantName)
                        ->first(function ($row) use ($slotStart, $slotEnd) {
                            return Carbon::parse($row['free_from'])->lte($slotStart)
                                && Carbon::parse($row['free_upto'])->gte($slotEnd);
                        });
                    if ($free) {
                        $totalDelay += $delay;
                        $tripsOk++;
                        $loadingStart = $slotStart->copy()->addMinutes($intervalMinutes);
                        $slotFound = true;
                        break;
                    }
                }
                if (!$slotFound) {
                    break;
                }
            }
            $score = ($tripsOk * 1000) - $totalDelay;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPlant = $plantName;
            }
        }
        return $bestPlant;
    }
    private function nextFreeMinutes(array $pool, $loadingStart): int
    {
        $loadingStart = $loadingStart instanceof Carbon
            ? $loadingStart
            : Carbon::parse($loadingStart);
        $smallestGap = null;
        foreach ($pool as $row) {
            $freeFrom = isset($row['free_from'])
                ? Carbon::parse($row['free_from'])
                : null;
            if ($freeFrom === null) continue;
            if ($freeFrom->lte($loadingStart)) {
                return 1;
            }
            $gap = (int) $loadingStart->diffInMinutes($freeFrom);
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