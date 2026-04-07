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
use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use App\Models\GlobalSetting;
use App\Models\OrderSchedule;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\ProductType;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\SelectedOrderSchedule;
use App\Models\OrderTempControl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\TransitMixer;

class ScheduleData
{
    public $user_id;
    public $reschedule_minutes;
    public $assigned_plant;
    public $min_delivery_time;
    public $assigned_pumps_per_order;
    public $quantity;
    public $failure_reason;
    public $pump_busy_slots;

    public $original_bps; // ✅ snapshot before order trips begin
    public $original_tms; //    public $truck_busy_slots;
    public $plant_busy_slots;
    public $pump_loading_time;
    public $assign_pump_slot;
    public $interval;
    public $trip;
    public $order_interval;
    public $order_start_time;
    public $transit_mixers;
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

    public $dual_plant_mode;       // bool  — true = alternate two plants
    public $dual_plant_primary;    // string|null — plant name locked for odd trips
    public $dual_plant_secondary;  // string|null — plant name locked for even trips

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

            $shift_end = Carbon::parse($shift_end)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME);
            $this->clearPreviousSchedules($company, $user_id, $shift_start, $shift_end);
            $shift_start = Carbon::parse($shift_start)->subDays(2)->format(ConstantHelper::SQL_DATE_TIME);

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
                'pump_busy_slots'     => [],
                'truck_busy_slots'    => [],
                'plant_busy_slots'    => [],
                'dual_plant_mode'      => false,
                'dual_plant_primary'   => null,
                'dual_plant_secondary' => null,
            ]);
            $this->calculateAndStoreLpi($scheduleData);
            $this->generateSchedule($scheduleData);
            $this->optimizeSchedules($scheduleData);
            $this->reassignMixersAfterStore($scheduleData);
            $this->reassignPumpsAfterOptimize($scheduleData);
            self::updateQcFromPreviousSlot();
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
        $globalStart     = microtime(true);
        $maxTotalSeconds = 540;

        try {
            $this->initializeVariables($scheduleData);
            $allOrders = $this->fetchOrders($scheduleData);
            $majorOrders    = $allOrders->filter(fn($o) => !$o->flexibility && $o->quantity >= 20);
            Log::info("Pass 1 — major orders: " . $majorOrders->count());
            foreach ($allOrders as $key => $order) {
                if ((microtime(true) - $globalStart) > $maxTotalSeconds) break;
                $this->scheduleOrder($scheduleData, $order, $key);
            }
        } catch (\Exception $ex) {
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }

    private function processOrder($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $orderKey)
    {
        $locations      = $this->adjustLocations($order, $scheduleData->bps_availability);
        $countLocations = count($locations);
        $counter        = 0;
        $originalBpsAvailability = $scheduleData->bps_availability;
        $originalTmsAvailability = $scheduleData->tms_availability;

        foreach ($locations as $location) {
            if ($order->location != $location) {
                $counter++;
                if ($counter < $countLocations) {
                    continue;
                } else {
                    $nearestBatchingPlant = CustomerProjectSiteHelper::assignNewBatchingPlant($order, $locations);
                    $location = $nearestBatchingPlant->location;
                }
            }
            $tmsAvailability = $this->transitMixerHelper->getTrucksLocationAvailability($scheduleData->tms_availability, $location);
            if (!$tmsAvailability) {
                continue;
            }
            $generatedScheduleData->location = $location;
            $generatedScheduleData->original_bps  = $originalBpsAvailability; // ✅ store in state
            $generatedScheduleData->original_tms  = $originalTmsAvailability;
            $scheduleData = clone $generatedScheduleData;
            $scheduleData->order_start    = Carbon::parse($order->delivery_date);
            $scheduleData->delivery_time  = Carbon::parse($order->delivery_date);
            $scheduleData->min_delivery_time = Carbon::parse($order->delivery_date);
            $scheduleData->order_no       = $order->order_no;
            $scheduleData->location       = $location;
            $scheduleData->early_trip     = $scheduleData->late_trip = $scheduleData->order_start;
            $this->resetOrderVariables($scheduleData, $order, $order->interval);
            $this->processTrips($order, $scheduleData, $generatedScheduleData, $location, $orderKey);
            if ($scheduleData->is_completed) {
                break;
            }
        }
    }

    private function determineDualPlantMode(ScheduleData &$scheduleData, $order): void
    {
        $scheduleData->dual_plant_mode      = false;
        $scheduleData->dual_plant_primary   = null;
        $scheduleData->dual_plant_secondary = null;
        $productType      = ProductType::where('type', '=', $order->mix_code)->first();
        $orderTempControl = OrderTempControl::where('order_id', $order->og_order_id)->first();

        if ($productType) {
            $tempLoadingTime = 0;
            if ($orderTempControl) {
                $tempLoadingTime = $productType->temperature_creation_time;
            }
            $scheduleData->loading_time = $productType->batching_creation_time + $tempLoadingTime;
        }

        $deliveryDate = Carbon::parse($scheduleData->delivery_time);
        $scheduleData->return_time  = $order->return_to_plant;
        $scheduleData->travel_time  = $order->travel_to_site;
        $loadingTime  = (int) $scheduleData->loading_time;
        $pouringTime  = (int) $order->pouring_time;

        $plantsAtLocation = collect($scheduleData->bps_availability)
            ->unique('plant_name')
            ->values();



        $singlePlantCapacity = $plantsAtLocation->max('plant_capacity') ?? 0;
        $requiredRate        = 0.0;

        if ($singlePlantCapacity > 0 && ($order->interval ?? 0) > 0) {
            $optimalTruckCap = 8;
            $requiredRate = ($loadingTime / $optimalTruckCap) * $order->quantity; // m³/hr
        }

        $rateExceedsCapacity = $singlePlantCapacity > 0 && $requiredRate > $singlePlantCapacity;

        $intervalMins = (int) ($order->interval    ?? 0);

        $isCritical = ($pouringTime + $intervalMins) < $loadingTime;



        $scheduleData->dual_plant_mode = $rateExceedsCapacity || $isCritical;

        $reason = match (true) {
            $rateExceedsCapacity && $isCritical => 'both',
            $rateExceedsCapacity                => 'rate',
            $isCritical                         => 'structure',
            default                             => null,
        };

        try {
            DB::table('selected_orders')
                ->where('order_no', $order->order_no)
                ->where('group_company_id', $scheduleData->company)
                ->update([
                    'dual_plant_mode'      => $scheduleData->dual_plant_mode,
                    'dual_plant_primary'   => null, // filled by assignBatchingPlantDual on trip 1
                    'dual_plant_secondary' => null, // filled by assignBatchingPlantDual on trip 2
                    'dual_plant_reason'    => $reason,
                    'is_critical' => $isCritical,
                ]);
        } catch (\Throwable $e) {
            Log::warning("[DUAL_PLANT] Could not persist dual_plant fields — run migration. " . $e->getMessage());
        }

        Log::info(
            "[DUAL_PLANT] Order {$order->order_no} — "
                . "dual_plant_mode="    . ($scheduleData->dual_plant_mode ? 'YES' : 'NO') . " | "
                . "required_rate={$requiredRate} m³/hr | "
                . "single_plant_capacity={$singlePlantCapacity} m³/hr | "
                . "rate_exceeds="       . ($rateExceedsCapacity ? 'YES' : 'NO') . " | "
                . "is_critical_struct=" . ($isCritical ? 'YES' : 'NO') . " | "
                . " (pouring={$pouringTime} + interval={$intervalMins} >= loading={$loadingTime}) | "
                . "reason={$reason}"
        );
    }

    private function processTrips($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $location, $orderKey)
    {
        $quantity  = $order->quantity;
        $trip      = 1;
        $scheduleData->trip      = 1;
        $scheduleData->phase_seq = 1;
        $scheduleData->current_interval = $order->interval;
        $minDeliveryTime = Carbon::parse($order->delivery_date);
        $shiftDurationMinutes = Carbon::parse($scheduleData->shift_start)
            ->diffInMinutes(Carbon::parse($scheduleData->shift_end));
        $maxIterations  = ($shiftDurationMinutes * 2) + ($order->interval * 10) + 500;
        $iterationCount = 0;

        while ($quantity > 0) {

            $iterationCount++;
            if ($iterationCount > $maxIterations) {
                Log::warning("[LOOP_GUARD] Max iterations ({$maxIterations}) reached for order {$order->order_no}. Force stopping.");
                $scheduleData->failure_reason = "Schedule timed out for order {$order->order_no} — no valid slot found within shift window.";
                break;
            }

            if ($scheduleData->phase == 1) {
                if ($scheduleData->late_trip->lt($scheduleData->delivery_time)) {
                    $scheduleData->late_trip = $scheduleData->delivery_time->copy();
                }
            } else {
                if ($scheduleData->early_trip->gt($scheduleData->delivery_time)) {
                    $scheduleData->early_trip = $scheduleData->delivery_time->copy();
                }
            }

            if ($scheduleData->loading_start->gt($scheduleData->shift_end)) {
                $scheduleData->shift_end_exit = 2;
                if (!$scheduleData->failure_reason) {
                    $shiftEndFormatted = Carbon::parse($scheduleData->shift_end)->format('Y-m-d H:i');
                    $scheduleData->failure_reason = "Order {$order->order_no} could not be scheduled — "
                        . "required loading start exceeds shift end ({$shiftEndFormatted}). "
                        . "Try reducing quantity";
                }
                break;
            }
            if ($scheduleData->loading_start->lt($scheduleData->shift_start)) {
                $scheduleData->shift_end_exit = 2;
                if (!$scheduleData->failure_reason) {
                    $shiftStartFormatted = Carbon::parse($scheduleData->shift_start)->format('Y-m-d H:i');
                    $scheduleData->failure_reason = "Order {$order->order_no} could not be scheduled — "
                        . "required loading start falls before shift start ({$shiftStartFormatted}). "
                        . "Mark the order as non-flexible or move the delivery time later.";
                }
                break;
            }

            // if ($scheduleData->pouring_start->lt($minDeliveryTime)) {
            //     $shift = $scheduleData->pouring_start->diffInMinutes($minDeliveryTime, false);
            //     $scheduleData->loading_start  = $scheduleData->loading_start->copy()->addMinutes($shift);
            //     $scheduleData->loading_end    = $scheduleData->loading_end->copy()->addMinutes($shift);
            //     $scheduleData->qc_start       = $scheduleData->qc_start->copy()->addMinutes($shift);
            //     $scheduleData->qc_end         = $scheduleData->qc_end->copy()->addMinutes($shift);
            //     $scheduleData->travel_start   = $scheduleData->travel_start->copy()->addMinutes($shift);
            //     $scheduleData->travel_end     = $scheduleData->travel_end->copy()->addMinutes($shift);
            //     $scheduleData->insp_start     = $scheduleData->insp_start->copy()->addMinutes($shift);
            //     $scheduleData->insp_end       = $scheduleData->insp_end->copy()->addMinutes($shift);
            //     $scheduleData->pouring_start  = $minDeliveryTime->copy();
            //     $scheduleData->pouring_end    = $minDeliveryTime->copy()->addMinutes($scheduleData->pouring_time);
            //     $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
            //     $scheduleData->cleaning_end   = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
            //     $scheduleData->return_start   = $scheduleData->cleaning_end->copy()->addMinute();
            //     $scheduleData->return_end     = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
            //     $scheduleData->delivery_time  = $minDeliveryTime->copy();
            //     $scheduleData->order_start    = $minDeliveryTime->copy();
            // }

            Log::info("--TRIP--{$trip}--LS-{$scheduleData->loading_start}--DT--{$scheduleData->delivery_time}--CI--{$scheduleData->current_interval}--ITER--{$iterationCount}");

            $this->assignResources($order, $scheduleData, $location, $trip, $quantity);

            if ($this->allResourcesAssigned($scheduleData)) {
                //Log::info("All Resources Assigned: trip=$trip order={$order->order_no} qty=$quantity iter=$iterationCount");
                $scheduleData->batching_qty = min($scheduleData->transit_mixer['data']['truck_capacity'], $quantity);
                $scheduleData->next_qty     = $quantity - $scheduleData->batching_qty;
                $scheduleData->phase_seq++;
                $this->finalizeTrip($order, $scheduleData, $location, $trip, $quantity, $orderKey);
                $quantity -= $scheduleData->batching_qty;
                $trip++;
                $scheduleData->trip = $trip;
                $scheduleData->current_interval = $order->interval === 0 ? 1 : $order->interval;
            } else {

                //Log::info("Resource Not Found: trip=$trip order={$order->order_no} CI={$scheduleData->current_interval} iter=$iterationCount");

                if ($trip === 1 || $scheduleData->current_interval === 1) {
                    $nextDeliveryTime = $scheduleData->order_start->copy()->addMinutes(1);
                    Log::info("delivery time change for order " . $order->order_no . " " . $nextDeliveryTime);

                    $shiftEndExit = $scheduleData->shift_end_exit;
                    $phase        = $scheduleData->phase;
                    $lastResponse = $scheduleData->lastResponse;
                    $scheduleData = clone $generatedScheduleData;
                    $scheduleData->order_start    = $nextDeliveryTime;
                    $scheduleData->delivery_time  = $nextDeliveryTime;
                    $scheduleData->early_trip     = $nextDeliveryTime;
                    $scheduleData->late_trip      = $nextDeliveryTime;
                    $scheduleData->order_no       = $order->order_no;
                    $scheduleData->phase          = $phase;
                    $scheduleData->shift_end_exit = $shiftEndExit;
                    $scheduleData->lastResponse   = $lastResponse;
                    $this->resetOrderVariables($scheduleData, $order, $order->interval);
                    $scheduleData->current_interval = $order->interval;
                    $quantity = $order->quantity;
                    $trip     = 1;
                    $scheduleData->trip      = 1;
                    $scheduleData->phase_seq = 1;
                    $this->updateSchedule($scheduleData, $order);
                } else {
                    $lastPouringEnd = null;
                    if ($scheduleData->current_interval > 1) {
                        $scheduleData->current_interval--;
                        if (!empty($scheduleData->schedules)) {
                            $lastPouringEnd = Carbon::parse(
                                collect($scheduleData->schedules)
                                    ->sortByDesc('trip')
                                    ->first()['pouring_end']
                            );

                            if ($scheduleData->next_delivery_time->lte($lastPouringEnd)) {
                                $scheduleData->next_delivery_time = $lastPouringEnd->copy()->addMinute();
                                $scheduleData->current_interval = 1;
                            }
                        }
                        $this->generateNextSlot($scheduleData, $order, 8, $scheduleData->current_interval, $lastPouringEnd);
                        continue;
                    }


                    $this->setLastTripResponse($scheduleData);
                    if ($scheduleData->shift_end_exit == 0) {
                        $scheduleData->phase = 1;
                    }

                    $scheduleData->delivery_time = $scheduleData->delivery_time->copy()->addMinutes(1);

                    $quantity = $order->quantity;
                    $trip     = 1;
                    $scheduleData->trip = 1;
                    $scheduleData->current_interval = $order->interval;
                }
            }

            if ($quantity <= 0) {
                $scheduleData->is_completed = 1;
                break;
            }
        }
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
            "site_id",
            "location",
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
            "customer_id"
        )
            ->with('customer_company')
            ->where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->whereBetween("delivery_date", [$scheduleData->shift_start, $scheduleData->shift_end])

            ->whereNull("start_time")
            ->where("selected", true)
            ->orderBy('lpi_score', 'DESC')
            ->orderBy('priority', 'ASC')
            // ->orderBy('site_id', 'ASC')
            ->get();
    }

    private function resetOrderVariables(ScheduleData &$scheduleData, $order, $interval, $truckQty = 8)
    {
        $scheduleData->assigned_pump   = [];
        $scheduleData->schedules       = [];
        $scheduleData->selected_order_pump_schedules = [];
        $scheduleData->is_completed    = false;
        $scheduleData->order_no        = $order->order_no;
        $scheduleData->order_interval  = $order->interval === 0 ? 1 : $order->interval;
        $scheduleData->current_interval = $scheduleData->order_interval;
        $scheduleData->delivered_quantity = 0;
        $scheduleData->assigned_plant  = null;
        $scheduleData->phase_seq       = 0;

        $scheduleData->dual_plant_primary   = null;
        $scheduleData->dual_plant_secondary = null;
        $scheduleData->assigned_plants = [];
        $scheduleData->assigned_tms    = [];
        if (!empty($scheduleData->original_bps)) {
            $scheduleData->bps_availability = $scheduleData->original_bps;
        }
        if (!empty($scheduleData->original_tms)) {
            $scheduleData->tms_availability = $scheduleData->original_tms;
        }


        $productType      = ProductType::where('type', '=', $order->mix_code)->first();
        $orderTempControl = OrderTempControl::where('order_id', $order->og_order_id)->first();

        if ($productType) {
            $tempLoadingTime = 0;
            if ($orderTempControl) {
                $tempLoadingTime = $productType->temperature_creation_time;
            }
            $scheduleData->loading_time = $productType->batching_creation_time + $tempLoadingTime;
        }

        $deliveryDate = Carbon::parse($scheduleData->delivery_time);
        $scheduleData->return_time  = $order->return_to_plant;
        $scheduleData->travel_time  = $order->travel_to_site;
        $loadingTime  = $scheduleData->loading_time;
        $pouringTime  = $order->pouring_time;

        if ($order->quantity < $truckQty) {
            $loadingTime = round(($loadingTime / $truckQty) * $order->quantity, 0);
            $pouringTime = round(($pouringTime / $truckQty) * $order->quantity, 0);
        }

        $total_time = $loadingTime + $scheduleData->qc_time + $scheduleData->travel_time + $scheduleData->insp_time + 4;
        $scheduleData->loading_time  = $loadingTime;
        $scheduleData->total_time    = $total_time;
        $scheduleData->loading_start = $deliveryDate->copy()->subMinutes($total_time);
        $scheduleData->loading_end   = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
        $scheduleData->qc_start      = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end        = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start  = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end    = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start    = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end      = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_time  = $pouringTime;
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end   = $scheduleData->pouring_start->copy()->addMinutes($pouringTime);
        $scheduleData->pump_qty  = $order->pump_qty;
        $scheduleData->pump_cap  = $order->pump;
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end   = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start   = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end     = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($interval);
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

    private function setLastTripResponse(ScheduleData &$scheduleData)
    {
        if (!isset($scheduleData->lastResponse)) {
            $scheduleData->lastResponse = [
                'last_trip' => $scheduleData->trip,
                'data'      => clone $scheduleData,
            ];
        } elseif ($scheduleData->lastResponse && $scheduleData->lastResponse['last_trip'] < $scheduleData->trip) {
            $scheduleData->lastResponse = [
                'last_trip' => $scheduleData->trip,
                'data'      => clone $scheduleData,
            ];
        }
    }

    private function updateSchedule(ScheduleData &$scheduleData, &$order)
    {
        $order->delivered_quantity    = 0;
        $scheduleData->delivered_quantity = 0;
        $scheduleData->loading_start  = $scheduleData->delivery_time->copy()->subMinutes($scheduleData->total_time);
        $scheduleData->loading_end    = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
        $scheduleData->qc_start       = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end         = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start   = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end     = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start     = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end       = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_start  = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end    = $scheduleData->pouring_start->copy()->addMinutes($order->pouring_time);
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end   = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start   = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end     = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($order->interval);
    }

    private function assignResources($order, ScheduleData &$scheduleData, $location, $trip, $quantity)
    {
        $this->assignBatchingPlant($scheduleData, $location, $trip, $order);
        $this->assignTransitMixer($scheduleData, $location, $trip, $quantity);
    }

    private function assignBatchingPlant(ScheduleData &$scheduleData, $location, $trip, $order)
    {
        if ($scheduleData->dual_plant_mode) {
            $scheduleData->assigned_plant = null;
        }

        $scheduleData->batching_plant = BatchingPlantHelper::getAvailableBatchingPlants(
            $scheduleData->bps_availability,
            $location,
            $scheduleData->loading_start,
            $scheduleData->loading_end,
            $scheduleData->restriction_start,
            $scheduleData->restriction_end,
            $scheduleData->assigned_plants,
            $scheduleData->assigned_plant ?? null,
        );

        if (isset($scheduleData->batching_plant['data']['plant_name'])) {
            $scheduleData->assigned_plant      = $scheduleData->batching_plant['data']['plant_name'];
            $scheduleData->plant_busy_slots[]  = [
                'start'    => $scheduleData->loading_start->copy(),
                'end'      => $scheduleData->loading_end->copy(),
                'plant_id' => $scheduleData->batching_plant['data']['plant_name'],
                'order_no' => $scheduleData->order_no,
            ];
            //Log::info("Batching Plant Assigned (single): {$trip}--{$scheduleData->batching_plant['data']['plant_name']} From: {$scheduleData->loading_start} To: {$scheduleData->loading_end}");
        } else {
            //Log::info("Batching Plant Not found (single): {$trip} From: {$scheduleData->loading_start} To: {$scheduleData->loading_end}");
        }
    }



    /**
     * Warn when the required concrete rate exceeds the combined output of both
     * locked plants. Does NOT push back any times — that is the dispatcher's
     * decision (per client spec Rule 5: Controlled Override).
     */
    private function checkCombinedPlantCapacity(ScheduleData &$scheduleData, $order, string $location): void
    {
        if (($order->interval ?? 0) <= 0 || $scheduleData->truck_capacity <= 0) {
            return;
        }

        $requiredRate = ($scheduleData->truck_capacity * 60.0) / $order->interval; // m³/hr

        $combinedCapacity = collect($scheduleData->bps_availability)
            ->filter(
                fn($p) => ($p['location'] ?? '') === $location &&
                    in_array($p['plant_name'], [
                        $scheduleData->dual_plant_primary,
                        $scheduleData->dual_plant_secondary,
                    ])
            )
            ->unique('plant_name')
            ->sum('plant_capacity');

        if ($combinedCapacity > 0 && $requiredRate > $combinedCapacity) {
            $warning = "[CAPACITY_GUARD] Order {$order->order_no} — "
                . "required rate {$requiredRate} m³/hr exceeds combined plant capacity {$combinedCapacity} m³/hr "
                . "({$scheduleData->dual_plant_primary} + {$scheduleData->dual_plant_secondary}). "
                . "Consider reducing the concrete delivery rate or requesting PM override.";

            //Log::warning($warning);

            $existing = $scheduleData->failure_reason ?? '';
            if (strpos($existing, 'CAPACITY_GUARD') === false) {
                $scheduleData->failure_reason = trim($existing . ' ' . $warning);
            }
        }
    }

    private function assignTransitMixer(ScheduleData &$scheduleData, $location, $trip, $quantity)
    {
        if (isset($scheduleData->batching_plant['data']['plant_name'])) {
            $truck_cap = self::getOptimalTruckCapacity($scheduleData->tms_availability, $quantity);
            //Log::info("Truck Capacity" . $truck_cap . "Quantity" . $quantity);
            $scheduleData->transit_mixer = TransitMixerHelper::getAvailableTrucks(
                $scheduleData->tms_availability,
                $truck_cap,
                $scheduleData->loading_start,
                $scheduleData->return_end,
                $scheduleData->shift_end,
                $scheduleData->restriction_start,
                $scheduleData->restriction_start,
                $location,
                $trip,
                $scheduleData->assigned_tms
            );
            if (isset($scheduleData->transit_mixer['data']['truck_name'])) {
                $scheduleData->truck_busy_slots[] = [
                    'start'    => $scheduleData->loading_start->copy(),
                    'end'      => $scheduleData->return_end->copy()->subSeconds(),
                    'truck_id' => $scheduleData->transit_mixer['data']['truck_name'],
                    'order_no' => $scheduleData->order_no,
                    'cap'      => $scheduleData->transit_mixer['data']['truck_capacity'],
                ];
            } else {
                $reason = 'Transit Mixer Not Found for Order' . $scheduleData->order_no;
                if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                    BatchingPlantAvailability::create([
                        'group_company_id' => $scheduleData->company,
                        'location'         => $scheduleData->location,
                        'plant_name'       => $scheduleData->batching_plant['data']['plant_name'],
                        'plant_capacity'   => 0,
                        'free_from'        => $scheduleData->loading_start,
                        'free_upto'        => $scheduleData->loading_start,
                        'user_id'          => $scheduleData->user_id,
                        'reason'           => $reason,
                    ]);
                }
                //Log::info("Transit Mixer Not Found for Order: " . $trip);
            }
        }
    }

    private function allResourcesAssigned(ScheduleData &$scheduleData)
    {
        if (!$scheduleData->batching_plant) return false;
        if (!$scheduleData->transit_mixer)  return false;
        return true;
    }

    private function finalizeTrip($order, ScheduleData &$scheduleData, $location, $trip, $quantity, $orderKey)
    {
        $scheduleData->schedules[] = $this->createScheduleEntry($order, $scheduleData, $location, $trip);
        $this->updateResourceAvailability($scheduleData, $order, $location);
    }

    /**
     * Detect loading slot overlaps across all trips for this order.
     *
     * In single-plant mode every trip hits the same plant, so loading windows
     * must not overlap. In dual-plant mode each plant only handles every other
     * trip, so the effective gap per plant is 2×interval — this method checks
     * each plant's trips independently.
     *
     * Called before storeSchedules() so the dispatcher sees the warning
     * immediately rather than only in the conflict report after the fact.
     *
     * @return array  Empty = no overlaps. Each entry has:
     *   plant, trip_a, trip_b, overlap_minutes, current_interval, min_interval, fix
     */
    private function detectLoadingOverlaps(ScheduleData $scheduleData, $order): array
    {
        $overlaps = [];

        $tripsByPlant = collect($scheduleData->schedules)
            ->groupBy('batching_plant');

        foreach ($tripsByPlant as $plantName => $trips) {
            $sorted = $trips
                ->sortBy(fn($t) => Carbon::parse($t['loading_start'])->timestamp)
                ->values();

            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev         = $sorted[$i - 1];
                $curr         = $sorted[$i];
                $prevEnd      = Carbon::parse($prev['loading_end']);
                $currStart    = Carbon::parse($curr['loading_start']);

                if ($currStart->lt($prevEnd)) {
                    $overlapMin      = $currStart->diffInMinutes($prevEnd);
                    $minNeeded       = $this->calculateMinInterval($scheduleData, $order);

                    $overlaps[] = [
                        'plant'            => $plantName,
                        'trip_a'           => $prev['trip'],
                        'trip_b'           => $curr['trip'],
                        'overlap_minutes'  => $overlapMin,
                        'current_interval' => $order->interval,
                        'min_interval'     => $minNeeded,
                        'fix'              => "Increase interval from {$order->interval} to at least "
                            . "{$minNeeded} min"
                            . ($scheduleData->dual_plant_mode
                                ? ' (already dual-plant — check plant capacity)'
                                : ', or enable dual-plant mode for this order'),
                    ];
                }
            }
        }

        return $overlaps;
    }

    private function storeSchedules($order, ScheduleData &$scheduleData)
    {
        $overlaps = $this->detectLoadingOverlaps($scheduleData, $order);
        if (!empty($overlaps)) {
            foreach ($overlaps as $ov) {
                Log::warning(
                    "[LOADING_OVERLAP] Order {$order->order_no} "
                        . "plant={$ov['plant']} trips {$ov['trip_a']}+{$ov['trip_b']} "
                        . "overlap by {$ov['overlap_minutes']} min. Fix: {$ov['fix']}"
                );
            }
            $worst      = collect($overlaps)->sortByDesc('overlap_minutes')->first();
            $overlapMsg = "Loading overlap {$worst['overlap_minutes']} min on plant "
                . "{$worst['plant']} (trips {$worst['trip_a']}/{$worst['trip_b']}). "
                . "{$worst['fix']}.";
            $scheduleData->failure_reason = trim(
                ($scheduleData->failure_reason ?? '') . ' ' . $overlapMsg
            );
        }

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

        $totalOrdered   = (int) $order->quantity;
        $totalDelivered = (int) $scheduleData->delivered_quantity;

        if ($totalDelivered < $totalOrdered) {
            $undelivered  = $totalOrdered - $totalDelivered;
            $minInterval  = $this->calculateMinInterval($scheduleData, $order);
            $overlapWarning = '';
            if ($order->interval < $minInterval) {
                $overlapWarning = " WARNING: Current interval ({$order->interval} min) is less than "
                    . "minimum required ({$minInterval} min) — batching plant loading slots are overlapping. "
                    . "Set interval to at least {$minInterval} min to avoid plant conflicts.";
            }
            $failureReason = $scheduleData->failure_reason . " " . $overlapWarning;
            //Log::warning("[PARTIAL_DELIVERY] " . $failureReason);
            DB::table('selected_orders')->where('id', $order->id)->update(['failure_reason' => $failureReason]);
        }

        if ($order->pump) {
            DB::table("selected_order_pump_schedules")
                ->insert(array_values($scheduleData->selected_order_pump_schedules));
        }

        $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
        $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()
            ->diffInMinutes(Carbon::parse($order_deviation->start_time), false);

        DB::table("selected_orders")->where("id", $order->id)->update(['deviation' => $order_deviation]);
    }

    private function updateResourceAvailability(ScheduleData &$scheduleData, $order, $location)
    {
        $order->delivered_quantity        += $scheduleData->batching_qty;
        $scheduleData->delivered_quantity += $scheduleData->batching_qty;

        $truck      = $scheduleData->transit_mixer['data'];
        $truckIndex = $scheduleData->transit_mixer['index'];

        $scheduleData->tms_availability[$truckIndex]['free_upto'] = $scheduleData->loading_start->copy()->addSeconds()->format('Y-m-d H:i:s');
        $scheduleData->tms_availability[$truckIndex]['location']  = $location;
        if (
            isset($scheduleData->tms_availability[$truckIndex]['free_from']) &&
            $scheduleData->tms_availability[$truckIndex]['free_upto'] <= $scheduleData->tms_availability[$truckIndex]['free_from']
        ) {
            unset($scheduleData->tms_availability[$truckIndex]);
        }
        $scheduleData->tms_availability[] = [
            'truck_name'     => $truck['truck_name'],
            'truck_capacity' => $truck['truck_capacity'],
            'loading_time'   => $scheduleData->loading_time,
            'free_from'      => $scheduleData->return_end->subSeconds()->format('Y-m-d H:i:s'),
            'free_upto'      => $truck['free_upto'],
            'location'       => $location,
        ];

        $plant      = $scheduleData->batching_plant['data'];
        $plantIndex = $scheduleData->batching_plant['index'];

        $scheduleData->bps_availability[$plantIndex]['free_upto'] = $scheduleData->loading_start->copy()->addSeconds();
        if (
            isset($scheduleData->bps_availability[$plantIndex]['free_from']) &&
            $scheduleData->bps_availability[$plantIndex]['free_upto'] <= $scheduleData->bps_availability[$plantIndex]['free_from']
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
        if (!isset($scheduleData->early_trip) || $scheduleData->early_trip->gt($scheduleData->pouring_start)) {
            $scheduleData->early_trip = $scheduleData->pouring_start;
        }
        if (!isset($scheduleData->late_trip) || $scheduleData->late_trip->lt($scheduleData->pouring_end)) {
            $scheduleData->late_trip = $scheduleData->pouring_end;
        }
        if (!empty($scheduleData->schedules)) {
            $lastPouringEnd = Carbon::parse(
                collect($scheduleData->schedules)
                    ->sortByDesc('trip')
                    ->first()['pouring_end']
            );

            if ($scheduleData->next_delivery_time->lte($lastPouringEnd)) {
                $scheduleData->next_delivery_time = $lastPouringEnd->copy()->addMinute();
                $scheduleData->current_interval = 1;
            }
        }

        $this->generateNextSlot($scheduleData, $order, 8, $order->interval, $lastPouringEnd);
    }

    private function generateNextSlot(ScheduleData &$scheduleData, $order, $truckQty = 8, $interval = null, $lastPour = null)
    {
        // ── Interval must never exceed the order's agreed interval ───────────
        $maxInterval  = (int) ($order->interval ?: 1);
        $interval     = min((int) ($interval ?? $maxInterval), $maxInterval);

        $scheduleData->delivery_time = $scheduleData->next_delivery_time;
        $loadingTime  = $scheduleData->loading_time;
        $pouringTime  = $order->pouring_time;

        if ($truckQty == 11) {
            $loadingTime = round(($loadingTime / 8) * 11);
            $pouringTime = round(($pouringTime / 8) * 11);
        }

        if (isset($scheduleData->next_qty) && $scheduleData->next_qty < $truckQty) {
            $loadingTime = round((($loadingTime / $truckQty) * $scheduleData->next_qty), 0);
            $pouringTime = round((($pouringTime / $truckQty) * $scheduleData->next_qty), 0);
        }

        $scheduleData->loading_time   = $loadingTime;
        $scheduleData->pouring_time   = $pouringTime;

        $total_time = $loadingTime + $scheduleData->qc_time + $scheduleData->travel_time + $scheduleData->insp_time + 4;
        $scheduleData->loading_start  = $scheduleData->delivery_time->copy()->subMinutes($total_time);
        $scheduleData->loading_end    = $scheduleData->loading_start->copy()->addMinutes($loadingTime);
        $scheduleData->qc_start       = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end         = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start   = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end     = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start     = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end       = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_start  = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end    = $scheduleData->pouring_start->copy()->addMinutes($pouringTime);
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end   = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start   = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end     = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $lastPour->copy()->addMinutes($interval);

        Log::info("[NEXT_SLOT] next_delivery={$scheduleData->next_delivery_time->format('H:i')} "
            . "interval={$interval} max={$maxInterval} base={$lastPour->format('H:i')}");
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

    public function optimizeSchedules(ScheduleData $scheduleData)
    {
        DB::transaction(function () use ($scheduleData) {
            $records = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                ->where("user_id", $scheduleData->user_id)
                ->where('schedule_date', $scheduleData->schedule_date)
                ->orderBy('loading_start')
                ->get();
            foreach ($records as $row) {
                $originalStart = Carbon::parse($row->loading_start);
                /* ---------- previous plant job ---------- */
                $prevPlant = $records
                    ->where('batching_plant', $row->batching_plant)
                    ->where('id', '!=', $row->id)
                    ->filter(function ($r) use ($row) {
                        return Carbon::parse($r->loading_start)
                            ->lt(Carbon::parse($row->loading_start));
                    })
                    ->sortByDesc('loading_start')
                    ->first();
                $trip = (int) $row->trip;


                $flexibility = (int) $row->order->flexibility;





                $plantGap = $prevPlant
                    ? Carbon::parse($prevPlant->loading_end)
                    ->diffInMinutes(Carbon::parse($row->loading_start)) - 1
                    : PHP_INT_MAX;



                $requestedDelivery = Carbon::parse($row->order->delivery_date);
                $actualStartTime = Carbon::parse($row->order->start_time);

                $gap = PHP_INT_MAX;
                if ($trip === 1) {
                    if ($row->order->delivery_date === $row->order->start_time) {
                        $gap = PHP_INT_MAX;
                    } else {
                        $gap =  $requestedDelivery->diffInMinutes($actualStartTime, false);
                        $gap = $gap < 0 ? PHP_INT_MAX : $gap;
                    }
                }



                if ($plantGap === PHP_INT_MAX && $gap === PHP_INT_MAX)
                    continue;

                if (!$flexibility && $trip === 1) {
                    if ($gap !== PHP_INT_MAX) {
                        if ($plantGap === PHP_INT_MAX)
                            $earlyMinutes = $gap;
                        else
                            $earlyMinutes = min($gap, $plantGap);
                    }
                    else{
                        $earlyMinutes = 0;
                    }

                } else {
                    $earlyMinutes = $plantGap;
                }


                if ($earlyMinutes <= 1 || $earlyMinutes === PHP_INT_MAX)
                    continue;




                $newStart = Carbon::parse($row->loading_start)->subMinutes($earlyMinutes);
                if (!$newStart) {
                    continue;
                }
                if ($newStart->gte($originalStart)) {
                    continue;
                }
                $prevTrip = $records
                    ->where('trip', $row->trip - 1)
                    ->where('order_id', $row->order_id)
                    ->first();
                $total_time = $row->loading_time
                    + $row->qc_time
                    + $row->travel_time
                    + $row->insp_time + 4;

                $newPouringStart = $newStart->copy()->addMinutes($total_time);
                if (isset($prevTrip)) {
                    $prevPouringEnd = Carbon::parse($prevTrip->pouring_end);
                    if ($newPouringStart->lt($prevPouringEnd)) {
                        $newPouringStart = $prevPouringEnd->addMinute();
                    }
                }
                $newStart = $newPouringStart->copy()->subMinutes($total_time);
                /* ---------- recompute timings ---------- */
                $loadingEnd = $newStart->copy()->addMinutes($row->loading_time);
                $qcStart = $loadingEnd->copy()->addMinute();
                $qcEnd = $qcStart->copy()->addMinutes($row->qc_time);
                $travelStart = $qcEnd->copy()->addMinute();
                $travelEnd = $travelStart->copy()->addMinutes($row->travel_time);
                $inspStart = $travelEnd->copy()->addMinute();
                $inspEnd = $inspStart->copy()->addMinutes($row->insp_time);
                $pouringStart = $inspEnd->copy()->addMinute();
                $pouringEnd = $pouringStart->copy()->addMinutes($row->pouring_time);

                $cleanStart = $pouringEnd->copy()->addMinute();
                $cleanEnd = $cleanStart->copy()->addMinutes($row->cleaning_time);

                $returnStart = $cleanEnd->copy()->addMinute();
                $returnEnd = $returnStart->copy()->addMinutes($row->return_time);

                $row->loading_start = $newStart;
                $row->loading_end = $loadingEnd;
                $row->qc_start = $qcStart;
                $row->qc_end = $qcEnd;
                $row->travel_start = $travelStart;
                $row->travel_end = $travelEnd;
                $row->insp_start = $inspStart;
                $row->insp_end = $inspEnd;
                $row->pouring_start = $pouringStart;
                $row->pouring_end = $pouringEnd;
                $row->cleaning_start = $cleanStart;
                $row->cleaning_end = $cleanEnd;
                $row->return_start = $returnStart;
                $row->return_end = $returnEnd;


                $row->save();

                /* ---------- update in-memory record ---------- */
                $records = $records->map(function ($r) use ($row) {
                    return $r->id == $row->id ? $row : $r;
                });
            }
            $rows = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                ->where("user_id", $scheduleData->user_id)
                ->where('schedule_date', $scheduleData->schedule_date)
                ->select(
                    'order_id',
                    'order_no',
                    DB::raw('MIN(pouring_start) as min_pour'),
                    DB::raw('MAX(pouring_end) as max_pour'),
                    DB::raw('MIN(loading_start) as min_load')
                )
                ->groupBy('order_id', 'order_no')
                ->get();

            foreach ($rows as $row) {
                DB::table('selected_orders')
                    ->where('id', $row->order_id)
                    ->update([
                        'start_time' => $row->min_pour,
                        'end_time' => $row->max_pour,
                    ]);
            }
        });
    }
    private function reassignPumpsAfterOptimize(ScheduleData $scheduleData): void
    {
        try {

            $orders = SelectedOrder::where('group_company_id', $scheduleData->company)
                ->where('user_id', $scheduleData->user_id)
                ->where('selected', true)
                ->whereNotNull('pump')
                ->where('pump_qty', '>', 0)
                ->orderBy('start_time', 'asc')
                ->get();

            if ($orders->isEmpty()) {
                return;
            }

            SelectedOrderPumpSchedule::where('group_company_id', $scheduleData->company)
                ->where('user_id', $scheduleData->user_id)
                ->where('schedule_date', $scheduleData->schedule_date)
                ->delete();

            $scheduleData->pump_busy_slots = [];
            $scheduleData->assigned_pumps = [];

            foreach ($orders as $order) {

                $dbSchedules = SelectedOrderSchedule::where('group_company_id', $scheduleData->company)
                    ->where('user_id', $scheduleData->user_id)
                    ->where('order_no', $order->order_no)
                    ->orderBy('trip', 'asc')
                    ->get();

                if ($dbSchedules->isEmpty()) {
                    //Log::info("[PUMP_REASSIGN] No trips found for order {$order->order_no}, skipping.");
                    continue;
                }

                $scheduleData->schedules = $dbSchedules->map(function ($row) {
                    return [
                        'order_id' => $row->order_id,
                        'order_no' => $row->order_no,
                        'trip' => $row->trip,
                        'batching_qty' => $row->batching_qty,
                        'loading_start' => $row->loading_start,
                        'loading_end' => $row->loading_end,
                        'loading_time' => $row->loading_time,
                        'qc_time' => $row->qc_time,
                        'qc_start' => $row->qc_start,
                        'qc_end' => $row->qc_end,
                        'travel_time' => $row->travel_time,
                        'travel_start' => $row->travel_start,
                        'travel_end' => $row->travel_end,
                        'insp_time' => $row->insp_time,
                        'insp_start' => $row->insp_start,
                        'insp_end' => $row->insp_end,
                        'pouring_time' => $row->pouring_time,
                        'pouring_start' => $row->pouring_start,
                        'pouring_end' => $row->pouring_end,
                        'cleaning_time' => $row->cleaning_time,
                        'cleaning_start' => $row->cleaning_start,
                        'cleaning_end' => $row->cleaning_end,
                        'return_time' => $row->return_time,
                        'return_start' => $row->return_start,
                        'return_end' => $row->return_end,
                        'delivery_start' => $row->loading_start,
                    ];
                })->toArray();

                $scheduleData->selected_order_pump_schedules = [];
                $scheduleData->assigned_pump = [];
                $scheduleData->pouring_pump = null;
                $scheduleData->order_no = $order->order_no;
                $scheduleData->location = $order->location;

                $ok = $this->assignPump($order, $scheduleData, $order->location);

                if ($ok && !empty($scheduleData->selected_order_pump_schedules)) {
                    DB::table('selected_order_pump_schedules')
                        ->insert(array_values($scheduleData->selected_order_pump_schedules));
                    //Log::info("[PUMP_REASSIGN] Order {$order->order_no} — pump reassigned successfully.");
                } else {
                    //Log::warning("[PUMP_REASSIGN] Order {$order->order_no} — pump reassignment failed.");
                    DB::table('selected_orders')
                        ->where('id', $order->id)
                        ->update(['failure_reason' => $scheduleData->failure_reason ?? 'Pump reassignment failed after optimization.']);
                }
            }
        } catch (Exception $e) {
            //Log::error("[PUMP_REASSIGN] Error: " . $e->getMessage());
        }
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
            $siteToSite = PumpHelper::getOverlapPumps(
                $scheduleData,
                $scheduleData->pumps_availability,
                $order->id,
                $groupPourStart,
                $groupPumpEndTime,
                $cleanEnd,
                $requirements[$p],
                $groupPourStart,
            );

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
                            'free_from' => $groupPourStart,
                            'free_upto' => $groupPourStart,
                            'user_id' => $scheduleData->user_id,
                            'reason' => $reason,
                        ]);
                    }
                }

                //Log::warning("Pump not found for order {$order->order_no} pumpSeq {$pumpSeq}");
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
            //Log::info("assign pump start time " . $start->copy()->format('Y-m-d H:i:s'));
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
            if ($waiting) {
                //Log::info("Update Current Slot Waiting " . $waiting);
                $inspStart = $inspStart->copy()->subMinutes($waiting);
                $inspEnd = $inspEnd->copy()->subMinutes($waiting);
                $installStart = $installStart->copy()->subMinutes($waiting);
                $installEnd = $installEnd->copy()->subMinutes($waiting);
            }
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
                'interval' => $order->interval,
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
    public static function getDistance($origin, $destination)
    {
        if ($origin === $destination) {
            return 0;
        }
        $origin = CustomerProjectSite::find($origin);
        $destination = CustomerProjectSite::find($destination);
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
    public static function getOptimalTruckCapacity($trucks, $requiredQty)
    {
        $capacities = array_unique(array_column($trucks, 'truck_capacity'));
        sort($capacities);
        $maxCapacity = max($capacities);
        if ($requiredQty >= $maxCapacity) {
            return $maxCapacity;
        }
        if (in_array($requiredQty, $capacities)) {
            return $requiredQty;
        }
        $limit = $requiredQty * 1.25;
        foreach ($capacities as $cap) {
            if ($cap >= $requiredQty && $cap <= $limit) {
                return $cap;
            }
        }
        foreach ($capacities as $cap) {
            if ($cap > $requiredQty) {
                return $cap;
            }
        }
        return $maxCapacity;
    }
    public function reassignMixersAfterStore(ScheduleData $scheduleData): void
    {
        DB::transaction(function () use ($scheduleData) {

            $mixers = collect($scheduleData->tms_availability);

            if ($mixers->isEmpty()) {
                return;
            }

            $rows = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                ->where("user_id", $scheduleData->user_id)
                ->where("schedule_date", $scheduleData->schedule_date)
                ->lockForUpdate()
                ->orderBy('loading_start', 'asc')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $busyIntervals = [];
            foreach ($mixers as $mixer) {
                $busyIntervals[$mixer['truck_name']] = [];
            }

            foreach ($rows as $row) {
                $start = Carbon::parse($row->loading_start);
                $end = Carbon::parse($row->return_end);

                $bestTruck = null;
                $bestGap = PHP_INT_MAX; // prefer truck with SMALLEST gap (tightest fit)

                foreach ($mixers as $mixer) {

                    if ($mixer['truck_capacity'] != $row->capacity) {
                        continue;
                    }

                    $truck = $mixer['truck_name'];
                    $intervals = $busyIntervals[$truck] ?? [];

                    $conflict = false;
                    $lastEnd = null;

                    foreach ($intervals as $iv) {
                        if ($start->lt($iv['end']) && $end->gt($iv['start'])) {
                            $conflict = true;
                            break;
                        }
                        if (!$lastEnd || $iv['end']->gt($lastEnd)) {
                            $lastEnd = $iv['end'];
                        }
                    }

                    if ($conflict) {
                        continue;
                    }

                    $gap = $lastEnd ? $lastEnd->diffInMinutes($start, false) : PHP_INT_MAX;

                    if ($gap < 0) {
                        continue;
                    }

                    if ($bestTruck === null || $gap < $bestGap) {
                        $bestTruck = $truck;
                        $bestGap = $gap;
                    }
                }

                if (!$bestTruck) {
                    //Log::warning("No free mixer found for order {$row->order_no} trip {$row->trip}, keeping {$row->transit_mixer}");
                    if (!isset($busyIntervals[$row->transit_mixer])) {
                        $busyIntervals[$row->transit_mixer] = [];
                    }
                    $busyIntervals[$row->transit_mixer][] = [
                        'start' => $start,
                        'end' => $end,
                    ];
                    continue;
                }

                $row->transit_mixer = $bestTruck;
                $row->save();

                $busyIntervals[$bestTruck][] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }
        });
    }
    private function scheduleOrder($scheduleData, $order, $orderKey, $strict = false)
    {
        $orderStartTime = microtime(true);
        $maxOrderSeconds = 60;

        Log::info("Processing Order: " . $order->order_no);

        $orderSchedule = clone $scheduleData;
        $orderSchedule->is_completed = false;
        $orderSchedule->delivered_quantity = 0;

        $this->processOrder($order, $orderSchedule, $scheduleData, $orderKey);

        $elapsed = microtime(true) - $orderStartTime;
        if ($elapsed > $maxOrderSeconds) {
            //Log::warning("[TIME_GUARD] Order {$order->order_no} took {$elapsed}s — exceeded {$maxOrderSeconds}s limit.");
        }

        if (isset($orderSchedule->lastResponse) && $orderSchedule->lastResponse['last_trip'] > $orderSchedule->trip) {
            $orderSchedule = clone $orderSchedule->lastResponse['data'];
        }

        if (empty($orderSchedule->schedules)) {
            if (!$orderSchedule->failure_reason) {
                $orderSchedule->failure_reason = "Unable to schedule (unknown reason)";
            }
            DB::table('selected_orders')
                ->where('id', $order->id)
                ->update(['failure_reason' => $orderSchedule->failure_reason]);
        }

        $this->storeSchedules($order, $orderSchedule);
        $scheduleData->tms_availability = $orderSchedule->tms_availability;
        $scheduleData->pumps_availability = $orderSchedule->pumps_availability;
        $scheduleData->pump_busy_slots = $orderSchedule->pump_busy_slots;
        $scheduleData->truck_busy_slots = $orderSchedule->truck_busy_slots;
        $scheduleData->plant_busy_slots = $orderSchedule->plant_busy_slots;
        $scheduleData->bps_availability = $orderSchedule->bps_availability;
        $scheduleData->assigned_pumps = $orderSchedule->assigned_pumps;
        $scheduleData->assigned_plants = $orderSchedule->assigned_plants;
        $scheduleData->assigned_tms = $orderSchedule->assigned_tms;
        $scheduleData->original_bps = null;
        $scheduleData->original_tms = null;
        $scheduleData->failure_reason = null;
        return true;
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

    private function syncPumpSchedulesAfterOptimize(ScheduleData $scheduleData): void
    {
        $orderTimes = SelectedOrderSchedule::where('group_company_id', $scheduleData->company)
            ->where('user_id', $scheduleData->user_id)
            ->where('schedule_date', $scheduleData->schedule_date)
            ->select(
                'order_id',
                'order_no',
                DB::raw('MIN(pouring_start) as new_pour_start'),
                DB::raw('MAX(pouring_end) as new_pour_end'),
                DB::raw('MIN(loading_start) as new_loading_start')
            )
            ->groupBy('order_id', 'order_no')
            ->get();


        foreach ($orderTimes as $orderTime) {
            $pumpSchedules = SelectedOrderPumpSchedule::where('group_company_id', $scheduleData->company)
                ->where('user_id', $scheduleData->user_id)
                ->where('order_no', $orderTime->order_no)
                ->get();

            if ($pumpSchedules->isEmpty()) {
                continue;
            }

            $newPourStart = Carbon::parse($orderTime->new_pour_start);
            $newPourEnd = Carbon::parse($orderTime->new_pour_end);

            foreach ($pumpSchedules as $ps) {
                $oldPourStart = Carbon::parse($ps->pouring_start);

                $shiftMinutes = $oldPourStart->diffInMinutes($newPourStart, false);

                $newQcStart = Carbon::parse($ps->qc_start)->addMinutes($shiftMinutes);
                $newQcEnd = Carbon::parse($ps->qc_end)->addMinutes($shiftMinutes);
                $newTravelStart = Carbon::parse($ps->travel_start)->addMinutes($shiftMinutes);
                $newTravelEnd = Carbon::parse($ps->travel_end)->addMinutes($shiftMinutes);
                $newInspStart = Carbon::parse($ps->insp_start)->addMinutes($shiftMinutes);
                $newInspEnd = Carbon::parse($ps->insp_end)->addMinutes($shiftMinutes);
                $newInstallStart = Carbon::parse($ps->install_start)->addMinutes($shiftMinutes);
                $newInstallEnd = Carbon::parse($ps->install_end)->addMinutes($shiftMinutes);
                $newWaitingStart = Carbon::parse($ps->waiting_start)->addMinutes($shiftMinutes);
                $newWaitingEnd = $newPourStart->copy()->subMinute();
                $newWaitingTime = max(0, $newWaitingStart->diffInMinutes($newWaitingEnd));
                $pourTime = $newPourStart->copy()->diffInMinutes($newPourEnd);

                $newCleanStart = $newPourEnd->copy()->addMinute();
                $newCleanEnd = $newCleanStart->copy()->addMinutes((int) $ps->cleaning_time);
                $newReturnStart = $ps->return_time > 0 ? $newCleanEnd->copy()->addMinute() : $newCleanEnd->copy();
                $newReturnEnd = $ps->return_time > 0 ? $newReturnStart->copy()->addMinutes($ps->return_time) : $newCleanEnd->copy();

                $ps->update([
                    'qc_start' => $newQcStart,
                    'qc_end' => $newQcEnd,
                    'travel_start' => $newTravelStart,
                    'travel_end' => $newTravelEnd,
                    'insp_start' => $newInspStart,
                    'insp_end' => $newInspEnd,
                    'install_start' => $newInstallStart,
                    'install_end' => $newInstallEnd,
                    'waiting_start' => $newWaitingStart,
                    'waiting_end' => $newWaitingEnd,
                    'waiting_time' => $newWaitingTime,
                    'pouring_start' => $newPourStart,
                    'pouring_end' => $newPourEnd,
                    'pouring_time' => $pourTime,
                    'cleaning_start' => $newCleanStart,
                    'cleaning_end' => $newCleanEnd,
                    'return_start' => $newReturnStart,
                    'return_end' => $newReturnEnd,
                ]);

                Log::info("[PUMP_SYNC] Order {$orderTime->order_no} pump {$ps->pump} shifted by {$shiftMinutes} min. "
                    . "New pouring: {$newPourStart->format('H:i')} → {$newPourEnd->format('H:i')}");
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

            $this->determineDualPlantMode($scheduleData, $order);

            $structRef = isset($order->structural_reference_id)
                ? $structuralRefs->get($order->structural_reference_id)
                : null;

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

            if ((int) ($order->customer_company->tier) <= 10)
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
    private function calculateMinInterval(ScheduleData $scheduleData, $order): int
    {
        $loadingTime = $scheduleData->loading_time;
        $pouringTime = $order->pouring_time;

        // If quantity < truck capacity, times are already scaled down
        if ($order->quantity < 8) {
            $loadingTime = round(($loadingTime / 8) * $order->quantity, 0);
            $pouringTime = round(($pouringTime / 8) * $order->quantity, 0);
        }

        // minimum interval so loading slots don't overlap on batching plant
        $minInterval = max(1, (int) ceil($loadingTime - $pouringTime));

        Log::info("[MIN_INTERVAL] Order {$order->order_no} — "
            . "loading_time={$loadingTime} pouring_time={$pouringTime} "
            . "min_interval={$minInterval} current_interval={$order->interval}");

        return $minInterval;
    }
}
