<?php
namespace App\Lib\Services;
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
    public $assigned_pumps_per_order;
    public $quantity;
    public $failure_reason;
    public $pump_busy_slots;
    public $truck_busy_slots;
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
    public function __construct(
    ) {
        ini_set('max_execution_time', '-1');
        $this->pumpHelper = new PumpHelper();
        $this->transitMixerHelper = new TransitMixerHelper();
        $this->batchingPlantHelper = new BatchingPlantHelper();
        $this->restrictionHelper = new TransitMixerRestrictionHelper();
    }
    public function initializeSchedule(
        int $user_id,
        string $company,
        string $schedule_date,
        array $transit_mixer_ids,
        array $pump_ids,
        array $batching_plant_ids,
        string $schedule_preference,
        string $shift_start,
        string $shift_end,
        int $interval_deviation
    ) {
        try {
            $shift_end = Carbon::parse($shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME);
            $this->clearPreviousSchedules($company, $user_id, $shift_start, $shift_end);
            $shift_start = Carbon::parse($shift_start)->subDay()->format(ConstantHelper::SQL_DATE_TIME);
            $tmsAvailability = $this->transitMixerHelper->getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);
            $scheduleData = new ScheduleData([
                'user_id' => $user_id,
                'company' => $company,
                'schedule_date' => $schedule_date,
                'sch_adj_from' => 0,
                'sch_adj_to' => 1440,
                'tms_availability' => $tmsAvailability,
                'pumps_availability' => $this->pumpHelper->getPumpsAvailability($company, $schedule_date, $pump_ids),
                'bps_availability' => $this->batchingPlantHelper->getBatchingPlantAvailabilityCopy(
                    $company,
                    $schedule_date,
                    $batching_plant_ids,
                    $this->batchingPlantHelper->getMinOrderScheduleTimeCopy($company, $user_id, $shift_start, $shift_end, $schedule_date)
                ),
                'schedule_preference' => $schedule_preference,
                'shift_start' => $shift_start,
                'shift_end' => $shift_end,
                'restriction_start' => $this->restrictionHelper->getRestrictions($company, $schedule_date, $shift_start)['restriction_start'],
                'restriction_end' => $this->restrictionHelper->getRestrictions($company, $schedule_date, $shift_start)['restriction_end'],
                'interval_deviation' => $interval_deviation,
                'generateLog' => false,
                'execute' => false,
                'truck_capacity' => max(array_unique(array_column($tmsAvailability, 'truck_capacity'))),
                'assigned_plants' => [],
                'assigned_tms' => [],
                'assigned_pumps' => [],
                'orders_copy' => [],
                'schedules' => [],
                'selected_order_pump_schedules' => [],
                'transit_mixers' => $transit_mixer_ids,
                'pump_busy_slots' => [],
                'truck_busy_slots' => [],
                'plant_busy_slots' => [],
            ]);
            $this->generateSchedule($scheduleData);
            $conflicts = ScheduleService::validateAllResourceConflicts($scheduleData);
            Log::info('Schedule Conflicts:', $conflicts);
            $this->reassignMixersAfterStore($scheduleData);
            $conflicts = ScheduleService::validateAllResourceConflicts($scheduleData);
            Log::info('Conflict before optimize:', $conflicts);
            $this->optimizeSchedules($scheduleData);
            self::updateQcFromPreviousSlot();
            $conflicts = ScheduleService::validateAllResourceConflicts($scheduleData);
            Log::info('After optmize Schedule Conflicts:', $conflicts);
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
            ->update(['start_time' => null, 'end_time' => null, 'deviation' => null, 'delivered_quantity' => 0, 'location' => null]);
    }
    public function generateSchedule(ScheduleData &$scheduleData)
    {
        try {
            $this->initializeVariables($scheduleData);
            $orders = $this->fetchOrders($scheduleData);
            //Log::info("Total Orders: " . count($orders));
            foreach ($orders as $orderKey => $order) {
                $this->scheduleOrder($scheduleData, $order, $orderKey);

            }
        } catch (\Exception $ex) {
            if (!$scheduleData->is_completed && !$scheduleData->failure_reason) {
                $scheduleData->failure_reason = "Unable to schedule within constraints";
            }
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }
    private function processOrder($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $orderKey)
    {
        $locations = $this->adjustLocations($order, $scheduleData->bps_availability);
        $countLocations = count($locations);
        $counter = 0;
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
            $interval = $scheduleData->interval;
            $scheduleData = clone $generatedScheduleData;
            $scheduleData->interval = $interval;
            //Log::info("\nOrder No: {$order->order_no}");
            //Log::info("Interval Step / Adjustment: " . $scheduleData->interval);
            $scheduleData->order_start = Carbon::parse($order->delivery_date);
            $scheduleData->delivery_time = Carbon::parse($order->delivery_date);
            $scheduleData->order_no = $order->order_no;
            $scheduleData->location = $location;
            $scheduleData->early_trip = $scheduleData->late_trip = $scheduleData->order_start;
            $this->resetOrderVariables($scheduleData, $order);
            $this->processTrips($order, $scheduleData, $generatedScheduleData, $location, $orderKey);
            if ($scheduleData->is_completed) {
                if ($order->pump && (int) $order->pump_qty > 0) {
                    $ok = $this->assignPump($order, $scheduleData, $location);
                    if (!$ok) {
                        $scheduleData->is_completed = false;
                        $scheduleData->schedules = [];
                        $scheduleData->selected_order_pump_schedules = [];
                        $scheduleData->assigned_pump = [];
                        $scheduleData->pouring_pump = null;
                        $scheduleData->failure_reason = "Pump unavailable for required capacity/time";
                        continue;
                    }
                }
                break;
            }
        }
    }
    private function processTrips($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $location, $orderKey)
    {
        $quantity = $order->quantity;
        $scheduleData->quantity = $quantity;
        $trip = 1;
        $scheduleData->trip = 1;
        $scheduleData->phase_seq = 1;
        $productType = ProductType::where('type', $order->mix_code)->first();
        $orderTempControl = OrderTempControl::where('order_id', $order->og_order_id)->first();
        if ($productType) {
            $tempLoadingTime = 0;
            if ($orderTempControl) {
                $tempQuantity = $orderTempControl->quantity;
                $tempLoadingTime = $productType->temperature_creation_time;
            }
            $scheduleData->loading_time = $productType->batching_creation_time + $tempLoadingTime;
        }
        while ($quantity > 0) {
            $scheduleData->quantity = $quantity;
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
            }
            if ($scheduleData->loading_start->lt($scheduleData->shift_start)) {
                $scheduleData->shift_end_exit = 2;
            }
            //Log::info("--TRIP--" . $trip . "--LS -" . $scheduleData->loading_start .      "--LE--" . $scheduleData->loading_end . "--DT--" . $scheduleData->delivery_time);
            $this->assignResources($order, $scheduleData, $location, $trip);
            if ($this->allResourcesAssigned($scheduleData)) {
                //Log::info("All Resources Assigned for Trip:  $trip -- order($orderKey)-" . $order->order_no . '--qty--' . $quantity . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start);
                $scheduleData->batching_qty = min($scheduleData->transit_mixer['data']['truck_capacity'], $quantity);
                $scheduleData->next_qty = $quantity - $scheduleData->batching_qty;
                $scheduleData->phase_seq++;
                $this->finalizeTrip($order, $scheduleData, $location, $trip, $quantity, $orderKey);
                $quantity -= $scheduleData->batching_qty;
                $scheduleData->quantity = $quantity;
                $trip++;
                $scheduleData->trip = $trip;
                $scheduleData->current_interval = 1;
            } else {
                //Log::info("Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit);
                $scheduleData->failure_reason = "Resources unavailable (Plant or Truck) for" . $order->order_no;
                if ($scheduleData->current_interval <= $scheduleData->order_interval) {
                    $scheduleData->current_interval++;
                } else {
                    if ($scheduleData->phase === 2) {
                        $nextDeliveryTime = $scheduleData->order_start->copy()->subMinutes(1);
                    } else {
                        $nextDeliveryTime = $scheduleData->order_start->copy()->addMinutes(1);
                    }
                    $shiftEndExit = $scheduleData->shift_end_exit;
                    $phase = $scheduleData->phase;
                    $earlyTrip = null;
                    $lateTrip = null;
                    $lastResponse = $scheduleData->lastResponse;
                    $interval = $scheduleData->interval;
                    $scheduleData = clone $generatedScheduleData;
                    $scheduleData->interval = $interval;
                    $scheduleData->order_start = $nextDeliveryTime;
                    $scheduleData->delivery_time = $nextDeliveryTime;
                    $earlyTrip = $lateTrip = $nextDeliveryTime;
                    $scheduleData->order_no = $order->order_no;
                    $scheduleData->phase = $phase;
                    $scheduleData->shift_end_exit = $shiftEndExit;
                    $scheduleData->early_trip = $earlyTrip;
                    $scheduleData->late_trip = $lateTrip;
                    $scheduleData->lastResponse = $lastResponse;
                    $this->resetOrderVariables($scheduleData, $order);
                    $quantity = $order->quantity;
                    $scheduleData->quantity = $quantity;
                    $trip = 1;
                    $scheduleData->trip = 1;
                    $scheduleData->phase_seq = 1;
                    $this->updateSchedule($scheduleData, $order);
                }
                if ($scheduleData->trip > 1) {
                    if ($scheduleData->current_interval <= $scheduleData->order_interval) {
                        if ($scheduleData->phase == 1) {
                            $scheduleData->next_delivery_time = $scheduleData->delivery_time->copy()->addMinutes();
                        } else {
                            $scheduleData->next_delivery_time = $scheduleData->delivery_time->copy()->subMinutes($pouring_interval);
                        }
                        $this->generateNextSlot($scheduleData, $order);
                        continue;
                    }
                    if ($trip > 1 && ($scheduleData->pump_qty && $scheduleData->pump_qty > 0) && empty($scheduleData->pouring_pump)) {
                        //Log::info(" if trip not flexible 1 if GT 1 Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit . '-CI-' . $scheduleData->current_interval);
                        $allotedPumpsQty = max($scheduleData->pump_qty, count($scheduleData->assigned_pumps));
                        $pouringTime = round(($order->pouring_time / 8) * $scheduleData->batching_qty);
                        $pouring_interval = $scheduleData->current_interval + $pouringTime;
                        $pouring_interval = round(($pouring_interval / $allotedPumpsQty), 0);
                        if ($scheduleData->phase == 2) {
                            $scheduleData->next_delivery_time = $scheduleData->early_trip->copy()->subMinutes($pouring_interval);
                        } else {
                            $scheduleData->next_delivery_time = $scheduleData->delivery_time->copy()->addMinutes();
                        }
                        $scheduleData->early_trip = $scheduleData->next_delivery_time;
                        $this->generateNextSlot($scheduleData, $order);
                        continue;
                    }
                    $this->setLastTripResponse($scheduleData);
                    if ($scheduleData->shift_end_exit == 0) {
                        $scheduleData->phase = 1;
                    }
                    $scheduleData->delivery_time = $scheduleData->delivery_time->copy()->subMinutes(1);
                    $quantity = $order->quantity;
                    $scheduleData->quantity = $quantity;
                    $trip = 1;
                    $scheduleData->trip = 1;
                } else {
                    if ($scheduleData->phase === 2) {
                        $nextDeliveryTime = $scheduleData->order_start->copy()->subMinutes(1);
                    } else {
                        $nextDeliveryTime = $scheduleData->order_start->copy()->addMinutes(1);
                    }
                    $shiftEndExit = $scheduleData->shift_end_exit;
                    $phase = $scheduleData->phase;
                    $earlyTrip = null;
                    $lateTrip = null;
                    $lastResponse = $scheduleData->lastResponse;
                    $interval = $scheduleData->interval;
                    $scheduleData = clone $generatedScheduleData;
                    $scheduleData->interval = $interval;
                    $scheduleData->order_start = $nextDeliveryTime;
                    $scheduleData->delivery_time = $nextDeliveryTime;
                    $earlyTrip = $lateTrip = $nextDeliveryTime;
                    $scheduleData->order_no = $order->order_no;
                    $scheduleData->phase = $phase;
                    $scheduleData->shift_end_exit = $shiftEndExit;
                    $scheduleData->early_trip = $earlyTrip;
                    $scheduleData->late_trip = $lateTrip;
                    $scheduleData->lastResponse = $lastResponse;
                    $this->resetOrderVariables($scheduleData, $order);
                    $quantity = $order->quantity;
                    $scheduleData->quantity = $quantity;
                    $trip = 1;
                    $scheduleData->trip = 1;
                    $scheduleData->phase_seq = 1;
                    $this->updateSchedule($scheduleData, $order);
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
        $scheduleData->phase = 1;
        $scheduleData->shift_end_exit = 0;
        $scheduleData->early_trip = null;
        $scheduleData->late_trip = null;
        $scheduleData->lastResponse = null;
        $scheduleData->qc_time = GlobalSetting::where('group_company_id', $scheduleData->company)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;
        $scheduleData->insp_time = GlobalSetting::where('group_company_id', $scheduleData->company)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;
        $scheduleData->cleaning_time = GlobalSetting::where('group_company_id', $scheduleData->company)->value('chute_cleaning_site') ?? ConstantHelper::CLEANING_TIME;
        $scheduleData->loading_time = ConstantHelper::LOADING_TIME;
    }
    private function fetchOrders(ScheduleData $scheduleData)
    {
        $orders = SelectedOrder::select(
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
        )
            ->where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->whereBetween("delivery_date", [$scheduleData->shift_start, $scheduleData->shift_end])
            ->whereNull("start_time")
            ->where("selected", true)
            ->orderBy('priority', 'ASC')
            ->orderBy('quantity', 'DESC')
            ->get();
        return $orders;
    }
    private function resetOrderVariables(ScheduleData &$scheduleData, $order, $truckQty = 8)
    {
        $scheduleData->assigned_pump = [];
        $scheduleData->schedules = [];
        $scheduleData->selected_order_pump_schedules = [];
        $scheduleData->is_completed = false;
        $scheduleData->order_no = $order->order_no;
        $scheduleData->order_interval = $order->interval;
        $scheduleData->current_interval = 1;
        $scheduleData->delivered_quantity = 0;
        $scheduleData->phase_seq = 0;
        $productType = ProductType::where('type', '=', $order->mix_code)
            ->first();
        $orderTempControl = OrderTempControl::where('order_id', $order->og_order_id)->first();
        if ($productType) {
            $tempLoadingTime = 0;
            if ($orderTempControl) {
                $tempQuantity = $orderTempControl->quantity;
                $tempLoadingTime = $productType->temperature_creation_time;
            }
            $scheduleData->loading_time = $productType->batching_creation_time + $tempLoadingTime;
        }
        $deliveryDate = Carbon::parse($scheduleData->delivery_time);
        $scheduleData->return_time = $order->return_to_plant;
        $scheduleData->travel_time = $order->travel_to_site;
        $loadingTime = $scheduleData->loading_time;
        $pouringTime = $order->pouring_time;
        if ($order->quantity < $truckQty) {
            $loadingTime = round(($loadingTime / $truckQty) * $order->quantity, 0);
            $pouringTime = round(($pouringTime / $truckQty) * $order->quantity, 0);
        }
        $total_time = $loadingTime + $scheduleData->qc_time + $scheduleData->travel_time + $scheduleData->insp_time + 4;
        $scheduleData->loading_time = $loadingTime;
        $scheduleData->total_time = $total_time;
        $scheduleData->loading_start = $deliveryDate->copy()->subMinutes($total_time);
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_time = $pouringTime;
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($pouringTime);
        $pouring_interval = $scheduleData->current_interval + $pouringTime;
        $scheduleData->order_start_time = $scheduleData->delivery_time;
        $scheduleData->pump_loading_time = $scheduleData->loading_start;
        if ($order->pump_qty > 1) {
            $pouring_interval = round(($pouring_interval / $order->pump_qty), 0);
            if ($scheduleData->phase_seq && $scheduleData->phase_seq % $order->pump_qty == 0) {
                $pouring_interval++;
            }
        } else if ($order->multi_pouring > 1) {
            $pouring_interval = round(($pouring_interval / $order->multi_pouring), 0);
            if ($scheduleData->phase_seq && $scheduleData->phase_seq % $order->multi_pouring == 0) {
                $pouring_interval++;
            }
        }
        $scheduleData->pouring_interval = $pouring_interval;
        $scheduleData->pump_qty = $order->pump_qty;
        $scheduleData->pump_cap = $order->pump;
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($pouring_interval);
        if ($scheduleData->phase == 2) {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes($pouring_interval);
        }
    }
    private function adjustLocations($order, $batchingPlantAvailability)
    {
        $locations = array_unique(array_column($batchingPlantAvailability, 'location'));
        $index = array_search($order->location, $locations);
        if ($index !== false && $index > 0) {
            unset($locations[$index]);
            array_unshift($locations, $order->location);
        }
        return $locations;
    }

    private function setLastTripResponse(ScheduleData &$scheduleData)
    {
        if (!isset($scheduleData->lastResponse)) {
            $scheduleData->lastResponse = array(
                'last_trip' => $scheduleData->trip,
                'data' => clone $scheduleData
            );
        } elseif ($scheduleData->lastResponse && $scheduleData->lastResponse['last_trip'] < $scheduleData->trip) {
            $scheduleData->lastResponse = array(
                'last_trip' => $scheduleData->trip,
                'data' => clone $scheduleData
            );
        }
    }
    private function updateSchedule(ScheduleData &$scheduleData, &$order, $nextDeliveryDate = null)
    {
        $scheduleData->assigned_pumps_per_order = 1;
        $order->delivered_quantity = 0;
        $scheduleData->delivered_quantity = 0;
        if ($order->flexibility == 1) {
            $baseDelivery = Carbon::parse($scheduleData->delivery_time);
            $currentDelivery = $baseDelivery->copy()->addMinutes($scheduleData->interval);
            $earlyMinutes = $baseDelivery->diffInMinutes($currentDelivery, false);
            if ($earlyMinutes <= -720) {
                $scheduleData->interval = abs($scheduleData->interval) + 1;
            } else {
                if ($scheduleData->interval > 0) {
                    $scheduleData->interval = -$scheduleData->interval;
                } else {
                    $scheduleData->interval = abs($scheduleData->interval) + 1;
                }
            }
            $scheduleData->delivery_time = $baseDelivery->copy()
                ->addMinutes($scheduleData->interval);
        } else {
            $scheduleData->interval++;
            $scheduleData->delivery_time = Carbon::parse($scheduleData->delivery_time)->copy()->addMinutes($scheduleData->interval);
        }
        if ($nextDeliveryDate) {
            $scheduleData->delivery_time = Carbon::parse($nextDeliveryDate);
        }
        if (
            $scheduleData->delivery_time->toDateString()
            !== Carbon::parse($scheduleData->schedule_date)->toDateString()
        ) {
            $scheduleData->shift_end_exit = 5;
            $scheduleData->failure_reason = "Exceeded schedule date boundary";
            return;
        }
        $scheduleData->loading_start = $scheduleData->delivery_time->copy()->subMinutes($scheduleData->total_time);
        $scheduleData->order_start_time = $scheduleData->delivery_time;
        $scheduleData->pump_loading_time = $scheduleData->loading_start;
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($order->pouring_time);
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($scheduleData->pouring_interval);
        if ($scheduleData->phase == 2) {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes($scheduleData->pouring_interval);
        }
        $scheduleData->next_loading_time = $scheduleData->next_delivery_time->copy()->subMinutes($scheduleData->total_time);
    }
    private function assignResources($order, ScheduleData &$scheduleData, $location, $trip)
    {
        $this->assignBatchingPlant($scheduleData, $location, $trip);
        $this->assignTransitMixer($scheduleData, $location, $trip);
    }
    private function assignBatchingPlant(ScheduleData &$scheduleData, $location, $trip)
    {
        $slots = $scheduleData->plant_busy_slots;
        $scheduleData->batching_plant = BatchingPlantHelper::getAvailableBatchingPlants(
            $scheduleData->bps_availability,
            $scheduleData->company,
            $location,
            $scheduleData->loading_start,
            $scheduleData->loading_end,
            $scheduleData->restriction_start,
            $scheduleData->restriction_end,
            $trip,
            $scheduleData->assigned_plants,
            $scheduleData->order_no,
            $slots
        );
        if (isset($scheduleData->batching_plant['data']['plant_name'])) {
            $scheduleData->plant_busy_slots[] = [
                'start' => $scheduleData->loading_start->copy(),
                'end' => $scheduleData->loading_end->copy(),
                'plant_id' => $scheduleData->batching_plant['data']['plant_name'],
                'order_no' => $scheduleData->order_no
            ];
            //Log::info("Batching Plant Assigned: " . $trip . "--" . $scheduleData->batching_plant['data']['plant_name'] . "From: " . $scheduleData->loading_start . " To:" . $scheduleData->loading_end);
        } else {
            //Log::info("Batching Plant Not found: " . $trip . "--" . "From: " . $scheduleData->loading_start . " To:" . $scheduleData->loading_end);
        }
    }
    private function assignTransitMixer(ScheduleData &$scheduleData, $location, $trip)
    {
        $slots = $scheduleData->truck_busy_slots;
        $scheduleData->transit_mixer = TransitMixerHelper::getAvailableTrucks(
            $scheduleData->tms_availability,
            null,
            $scheduleData->loading_start,
            $scheduleData->return_end,
            $scheduleData->shift_end,
            $scheduleData->restriction_start,
            $scheduleData->restriction_end,
            $location,
            $trip,
            $scheduleData->assigned_tms,
            $slots,
            $scheduleData->order_no,
            $scheduleData->quantity
        );
        if (isset($scheduleData->transit_mixer['data']['truck_name'])) {
            $scheduleData->truck_busy_slots[] = [
                'start' => $scheduleData->loading_start->copy(),
                'end' => $scheduleData->return_end->copy()->subSeconds(),
                'truck_id' => $scheduleData->transit_mixer['data']['truck_name'],
                'order_no' => $scheduleData->order_no,
                'cap' => $scheduleData->transit_mixer['data']['truck_capacity'],
            ];
        } else {
            $reason = 'Transit Mixer Not Found for Order' . $scheduleData->order_no;
            if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                BatchingPlantAvailability::create(['group_company_id' => $scheduleData->company, 'location' => $scheduleData->location, 'plant_name' => $scheduleData->batching_plant['data']['plant_name'], 'plant_capacity' => 0, 'free_from' => $scheduleData->loading_start, 'free_upto' => $scheduleData->loading_start, 'user_id' => $scheduleData->user_id, 'reason' => $reason]);
            }
            //Log::info("Transit Mixer Not Found for Order: " . $trip);
        }
    }
    private function allResourcesAssigned(ScheduleData &$scheduleData)
    {
        if (!$scheduleData->batching_plant)
            return false;
        if (!$scheduleData->transit_mixer)
            return false;
        return true;
    }
    private function finalizeTrip($order, ScheduleData &$scheduleData, $location, $trip, $quantity, $orderKey)
    {
        $scheduleData->schedules[] = $this->createScheduleEntry($order, $scheduleData, $location, $trip);
        $this->updateResourceAvailability($scheduleData, $order, $location);
    }
    private function storeSchedules($order, ScheduleData &$scheduleData)
    {
        if ($scheduleData->failure_reason && !$scheduleData->is_completed) {
            DB::table('selected_orders')
                ->where('id', $order->id)
                ->update([
                    'failure_reason' => $scheduleData->failure_reason
                ]);
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
            ->where('user_id', $user_id)->where('order_no', $order->order_no)
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
                'start_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                    ->where('group_company_id', $scheduleData->company)
                    ->where('user_id', $user_id)
                    ->where('order_no', $order->order_no)
                    ->first()->min_pour,
                'end_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                    ->where('group_company_id', $scheduleData->company)
                    ->where('user_id', $user_id)->where('order_no', $order->order_no)
                    ->first()->max_pour,
                'delivered_quantity' => $scheduleData->delivered_quantity,
                'location' => $scheduleData->location
            ]);
        if ($order->pump) {
            DB::table("selected_order_pump_schedules")
                ->insert(array_values($scheduleData->selected_order_pump_schedules));
        }
        $order_deviation = DB::table("selected_orders")->where("id", $order->id)
            ->first();
        $order_deviation = Carbon::parse($order_deviation->delivery_date)
            ->copy()
            ->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
        DB::table("selected_orders")
            ->where("id", $order->id)
            ->update(['deviation' => $order_deviation]);
    }
    private function updateResourceAvailability(ScheduleData &$scheduleData, $order, $location)
    {
        $order->delivered_quantity += $scheduleData->batching_qty;
        $scheduleData->delivered_quantity += $scheduleData->batching_qty;
        $truck = $scheduleData->transit_mixer['data'];
        $truckIndex = $scheduleData->transit_mixer['index'];
        $plant = $scheduleData->batching_plant['data'];
        if (!in_array($plant['plant_name'], $scheduleData->assigned_plants)) {
            $scheduleData->assigned_plants[] = $plant['plant_name'];
        }
        if (!in_array($truck['truck_name'], $scheduleData->assigned_tms)) {
            $scheduleData->assigned_tms[] = $truck['truck_name'];
        }
        if (!isset($scheduleData->early_trip) || ($scheduleData->early_trip->gt($scheduleData->pouring_start))) {
            $scheduleData->early_trip = $scheduleData->pouring_start;
        }
        if (!isset($scheduleData->late_trip) || ($scheduleData->late_trip->lt($scheduleData->pouring_end))) {
            $scheduleData->late_trip = $scheduleData->pouring_end;
        }
        $this->generateNextSlot($scheduleData, $order);
    }
    private function generateNextSlot(ScheduleData &$scheduleData, $order, $truckQty = 8, $interval = 1)
    {
        $lastLoadingTime = $scheduleData->loading_start;
        $scheduleData->delivery_time = $scheduleData->next_delivery_time;
        $loadingTime = $scheduleData->loading_time;
        $pouringTime = $order->pouring_time;
        if ($truckQty == 11) {
            $loadingTime = round(($loadingTime / 8) * 11);
            $pouringTime = round(($pouringTime / 8) * 11);
        }
        if (isset($scheduleData->next_qty) && $scheduleData->next_qty < $truckQty) {
            $loadingTime = round((($loadingTime / $truckQty) * $scheduleData->next_qty), 0);
            $pouringTime = round((($pouringTime / $truckQty) * $scheduleData->next_qty), 0);
        }
        $scheduleData->loading_time = $loadingTime;
        $scheduleData->pouring_time = $pouringTime;
        $total_time = $loadingTime + $scheduleData->qc_time + $scheduleData->travel_time + $scheduleData->insp_time + 4;
        $scheduleData->loading_start = $scheduleData->delivery_time->copy()->subMinutes($total_time);
        $pouring_interval = $interval + $pouringTime;
        $scheduleData->pouring_interval = $pouring_interval;
        if ($order->pump_qty > 1) {
            $pouring_interval = round(($pouring_interval / $order->pump_qty), 0);
            $scheduleData->pouring_interval = $pouring_interval;
            if ($scheduleData->phase_seq && ($scheduleData->phase_seq % $order->pump_qty) == 0) {
                $pouring_interval++;
            }
        } else if ($order->multi_pouring > 1) {
            $pouring_interval = round(($pouring_interval / $order->multi_pouring), 0);
            $scheduleData->pouring_interval = $pouring_interval;
            if ($scheduleData->phase_seq && ($scheduleData->phase_seq % $order->multi_pouring) == 0) {
                $pouring_interval++;
            }
        }
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($loadingTime);
        if (!isset($scheduleData->trip_time)) {
            $scheduleData->trip_time = $scheduleData->loading_start->copy()->diffInMinutes($lastLoadingTime);
        }
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($pouringTime);
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($pouring_interval);
        if ($scheduleData->phase == 2) {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes($pouring_interval);
        }
        $scheduleData->next_loading_time = $scheduleData->next_delivery_time->copy()->subMinutes($scheduleData->total_time);
    }
    private function createScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {
        return [
            "order_id" => $order->id,
            "group_company_id" => $scheduleData->company,
            "user_id" => $scheduleData->user_id,
            "schedule_date" => $scheduleData->schedule_date,
            "order_no" => $order->order_no,
            "location" => $location,
            "trip" => $trip,
            "mix_code" => $order->mix_code,
            "batching_plant" => $scheduleData->batching_plant['data']['plant_name'] ?? null,
            "transit_mixer" => $scheduleData->transit_mixer['data']['truck_name'] ?? null,
            'capacity' => $scheduleData->transit_mixer['data']['truck_capacity'] ?? null,
            "batching_qty" => $scheduleData->batching_qty,
            "loading_time" => $scheduleData->loading_time,
            "loading_start" => $scheduleData->loading_start,
            "loading_end" => $scheduleData->loading_end,
            "qc_time" => $scheduleData->qc_time,
            "qc_start" => $scheduleData->qc_start,
            "qc_end" => $scheduleData->qc_end,
            "travel_time" => $order->travel_to_site,
            "travel_start" => $scheduleData->travel_start,
            "travel_end" => $scheduleData->travel_end,
            "insp_time" => $scheduleData->insp_time,
            "insp_start" => $scheduleData->insp_start,
            "insp_end" => $scheduleData->insp_end,
            "pouring_time" => $scheduleData->pouring_time,
            "pouring_start" => $scheduleData->pouring_start,
            "pouring_end" => $scheduleData->pouring_end,
            "cleaning_time" => $scheduleData->cleaning_time,
            "cleaning_start" => $scheduleData->cleaning_start,
            "cleaning_end" => $scheduleData->cleaning_end,
            "return_time" => $order->return_to_plant,
            "return_start" => $scheduleData->return_start,
            "return_end" => $scheduleData->return_end,
            "delivery_start" => $scheduleData->loading_start,
            "deviation" => abs(Carbon::parse($order->delivery_date)->diffInMinutes($scheduleData->pouring_start, false)),
        ];
    }
    public function optimizeSchedules(ScheduleData $scheduleData)
    {
        DB::transaction(function () use ($scheduleData) {
            try {
                $records = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                    ->where("user_id", $scheduleData->user_id)
                    ->where('schedule_date', $scheduleData->schedule_date)
                    ->orderBy('loading_start')
                    ->get();

                $slots = [];
                foreach ($records as $r) {
                    $slots[] = [
                        'id' => $r->id,
                        'order_no' => $r->order_no,
                        'trip' => $r->trip,
                        'loading_start' => $r->loading_start,
                        'loading_end' => $r->loading_end,
                        'return_end' => $r->return_end,
                        'batching_plant' => $r->batching_plant,
                        'pouring_start' => $r->pouring_start,
                        'transit_mixer' => $r->transit_mixer,
                        'pump' => $r->pump_assign ? $r->pump_assign->pump_name : null,
                        'pump_qc_start' => $r->pump_assign ? $r->pump_assign->qc_start : null,
                        'pump_return_end' => $r->pump_assign ? $r->pump_assign->return_end : null
                    ];
                }
                foreach ($records as $index => $row) {
                    $pouringStart = Carbon::parse($row->pouring_start);
                    $recordsByPlant = collect($slots)
                        ->where('batching_plant', $row->batching_plant)
                        ->where('id', '!=', $row->id)
                        ->where('loading_start', '<', $row->loading_start);
                    $recordsByMixer = collect($slots)
                        ->where('transit_mixer', $row->transit_mixer)
                        ->where('id', '!=', $row->id)
                        ->where('loading_start', '<', $row->loading_start);

                    $previousPump = null;
                    if ($row->pump_assign) {
                        $previousPump = collect($slots)
                            ->where("user_id", $scheduleData->user_id)
                            ->where('schedule_date', $scheduleData->schedule_date)
                            ->where('pump', $row->pump_assign->pump_name)
                            ->where('id', '!=', $row->id)
                            ->where('pouring_start', '<', $row->pump_assign->pouring_start)
                            ->sortByDesc('pouring_start')
                            ->first();
                    }
                  

                    $prevPumpEnd = $previousPump
                        ? Carbon::parse($previousPump['pump_return_end'])
                        : null;

                    if ($prevPumpEnd) {
                        Log::info("Previous pump return end time: " . $prevPumpEnd->format('Y-m-d H:i:s'));
                    } else {
                        Log::info("No previous pump found, so no return end time.");
                    }




                    $previousBatching = $recordsByPlant
                        ->sortByDesc('loading_start')
                        ->first();
                    $prevBatchingEnd = $previousBatching
                        ? Carbon::parse($previousBatching['loading_end'])
                        : null;
                    $previousTransit = $recordsByMixer
                        ->sortByDesc('loading_start')
                        ->first();
                    $previousTransitEnd = $previousTransit
                        ? Carbon::parse($previousTransit['return_end'])
                        : null;
                    $earliestStart = collect([$prevBatchingEnd, $previousTransitEnd, $prevPumpEnd])
                        ->filter()
                        ->max();
                    if (!$earliestStart) {
                        continue;
                    }
                    $loadingStart = $earliestStart->copy()->addMinute();
                    $originalLoadingStart = Carbon::parse($row->loading_start);
                    $maxEarlyAllowed = $originalLoadingStart->copy()->subMinutes($row->order->interval);
                    if ($loadingStart->lt($maxEarlyAllowed)) {
                        $loadingStart = $maxEarlyAllowed;
                    }
                    $loadingEnd = $loadingStart->copy()->addMinutes($row->loading_time);
                    $qcStart = $loadingEnd->copy()->addMinute();
                    $qcEnd = $qcStart->copy()->addMinutes($row->qc_time);
                    $travelStart = $qcEnd->copy()->addMinute();
                    $travelEnd = $travelStart->copy()->addMinutes($row->travel_time);
                    $inspStart = $travelEnd->copy()->addMinute();
                    $inspEnd = $inspStart->copy()->addMinutes($row->insp_time);
                    $waitingStart = $inspEnd->copy()->addMinute();
                    $waitingEnd = $pouringStart->copy()->subMinute();
                    $waitingTime = $waitingStart->diffInMinutes($waitingEnd);
                    $slots[$index]['loading_start'] = $loadingStart;
                    $slots[$index]['loading_end'] = $loadingEnd;
                    SelectedOrderSchedule::where('id', $row->id)->update([
                        'loading_start' => $loadingStart,
                        'loading_end' => $loadingEnd,
                        'qc_start' => $qcStart,
                        'qc_end' => $qcEnd,
                        'travel_start' => $travelStart,
                        'travel_end' => $travelEnd,
                        'insp_start' => $inspStart,
                        'insp_end' => $inspEnd,
                        'waiting_start' => $waitingStart,
                        'waiting_end' => $waitingEnd,
                        'waiting_time' => $waitingTime
                    ]);
                    // if ($row->pump_assign) {
                    //     $waitingStartPump = $loadingStart->copy();
                    //     $waitingEndPump = Carbon::parse($row->pump_assign->pouring_start)->subMinute();
                    //     $waitingTimePump = $waitingStartPump->diffInMinutes($waitingEndPump);

                    //     $installEnd = $waitingStartPump->copy()->subMinute();
                    //     $installStart = $installEnd->copy()->subMinutes($row->pump_assign->install_time);

                    //     $inspEndPump = $installStart->copy()->subMinute();
                    //     $inspStartPump = $inspEndPump->copy()->subMinutes($row->pump_assign->insp_time);

                    //     $travelEndPump = $inspStartPump->copy()->subMinute();
                    //     ;
                    //     $travelStartPump = $travelEndPump->copy()->subMinutes($row->pump_assign->travel_time);
                    //     $qcEndPump = $travelStartPump->copy()->subMinute();
                    //     $qcStartPump = $qcEndPump->copy()->subMinutes($row->pump_assign->qc_time);
                    //     SelectedOrderPumpSchedule::where('id', $row->pump_assign->id)->update([
                    //         'qc_start' => $qcStartPump,
                    //         'qc_end' => $qcEndPump,
                    //         'travel_start' => $travelStartPump,
                    //         'travel_end' => $travelEndPump,
                    //         'insp_start' => $inspStartPump,
                    //         'install_start' => $installStart,
                    //         'install_end' => $installEnd,
                    //         'insp_end' => $inspEndPump,
                    //         'waiting_start' => $waitingStartPump,
                    //         'waiting_end' => $waitingEndPump,
                    //         'waiting_time' => $waitingTimePump
                    //     ]);
                    //     $slots[$index]['qc_start_pump'] = $qcStartPump;

                    // }
                }
            } catch (\Exception $e) {
                Log::error("Schedule optimization failed: " . $e->getMessage());
                throw $e;
            }
        });
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
            $installTime = (int) ($pump['installation_time'] ?? 10);
            $siteToSite = null;
            $siteToSite = PumpHelper::getOverlapPumps(
                $scheduleData,
                $scheduleData->pumps_availability,
                $order->id,
                $groupPumpLoadingTime,
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
                $groupPumpLoadingTime->copy(),
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
                $first['qc_time'],
                $first['insp_time'],
                $first['travel_time'],
                $first['loading_time'],
            );

            $scheduleData->pouring_pump = $siteToSite === null ? $NewPump : $siteToSite;
            if ($siteToSite === null)
                Log::info("pick pump New order " . $order->order_no);
            else
                Log::info("pick pump Site to Site " . $order->order_no);
            if (!isset($scheduleData->pouring_pump['pump']['pump_name'])) {
                Log::warning("Pump not found for order {$order->order_no} pumpSeq {$pumpSeq}");
                continue;
            }
            $pump = $scheduleData->pouring_pump['pump'];
            $pumpIndex = $scheduleData->pouring_pump['index'];
            $waiting = $scheduleData->pouring_pump['waiting'] ?? 0;
            $pumpName = $pump['pump_name'];
            $pumpId = $pump['pump_id'];
            $installTime = (int) ($pump['installation_time'] ?? 10);
            $qcTime = isset($scheduleData->pouring_pump['qc_time']) ? $scheduleData->pouring_pump['qc_time'] : $first['qc_time'];
            $travelTime = isset($scheduleData->pouring_pump['travel_time']) ? $scheduleData->pouring_pump['travel_time'] : $first['travel_time'];
            $returnTime = isset($scheduleData->pouring_pump['return_time']) ? $scheduleData->pouring_pump['return_time'] : $first['return_time'];
            $waitingTime = $first['qc_time'] + $first['insp_time'] + $first['travel_time'] + $first['loading_time'] + 4 + $waiting;
            $totalTime = $installTime +
                (int) $qcTime +
                (int) $first['insp_time'] +
                (int) $travelTime + (
                ($installTime > 0 ? 1 : 0) +
                ($qcTime > 0 ? 1 : 0) +
                ($travelTime > 0 ? 1 : 0) +
                ($first['insp_time'] > 0 ? 1 : 0));
            Log::info("Pump Time Calculation", [
                'pump_loading_time' => $groupPumpLoadingTime->format('Y-m-d H:i:s'),
                'install_time' => $installTime,
                'qc_time' => $qcTime,
                'inspection_time' => $first['insp_time'],
                'travel_time' => $travelTime,
                'extra_minutes_for_steps' => (
                    ($installTime > 0 ? 1 : 0) +
                    ($qcTime > 0 ? 1 : 0) +
                    ($travelTime > 0 ? 1 : 0) +
                    ($first['insp_time'] > 0 ? 1 : 0)
                ),
                'total_minutes_subtracted' => $totalTime
            ]);
            $start = $groupPumpLoadingTime->copy()->subMinutes($totalTime);
            Log::info("assign pump start time " . $start->copy()->format('Y-m-d H:i:s'));

            $qcStart = $start->copy();
            $qcEnd = $qcTime !== 0 ? $qcStart->copy()->addMinutes($qcTime) : $start->copy();
            $travelStart = $qcTime !== 0 ? $qcEnd->copy()->addMinute() : $start->copy();
            $travelEnd = $travelTime !== 0 ? $travelStart->copy()->addMinutes($travelTime) : $start->copy();
            $inspStart = $travelTime !== 0 ? $travelEnd->copy()->addMinute() : $start->copy();
            $inspEnd = $inspStart->copy()->addMinutes($scheduleData->insp_time);
            $installStart = $inspEnd->copy()->addMinute();
            $installEnd = $installStart->copy()->addMinutes($installTime);
            $waitingStart = $installEnd->copy()->addMinute();
            $waitingEnd = $waitingStart->copy()->addMinutes($waitingTime);
            $pouringTime = $groupPourStart->diffInMinutes($groupPourEnd);
            $cleanStart = $groupPourEnd->copy()->addMinute();
            $cleanEnd = $cleanStart->copy()->addMinutes((int) $scheduleData->cleaning_time);
            $returnStart = $cleanEnd->copy()->addMinute();
            $returnEnd = $returnStart->copy()->addMinutes($returnTime);
            if ($waiting) {
                Log::info("Update Current Slot Waiting " . $waiting);
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
                'waiting_start' => $waitingStart->copy(),
                'waiting_end' => $waitingEnd->copy(),
                'waiting_time' => $waitingTime,
                'pouring_start' => $groupPourStart->copy(),
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
        $response = Http::get($apiURL, $queryParams);
        $data = $response->json();
        if (
            isset($data['rows'][0]['elements'][0]['duration']['value'])
            && $data['rows'][0]['elements'][0]['status'] === 'OK'
        ) {
            $seconds = $data['rows'][0]['elements'][0]['duration']['value'];
            $minutes = ceil($seconds / 60);
            Log::info("travel site to site minutes " . $minutes);
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
            $allMixers = $scheduleData->tms_availability;
            $rows = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                ->where("user_id", $scheduleData->user_id)
                ->where("schedule_date", $scheduleData->schedule_date)
                ->orderBy('loading_start', 'asc')
                ->orderBy('capacity', 'desc')
                ->get();
            if ($rows->isEmpty() || count($allMixers) === 0) {
                return;
            }
            $lastFreeAt = [];
            $busyIntervals = [];
            foreach ($allMixers as $m) {
                $lastFreeAt[$m['truck_name']] = null;
                $busyIntervals[$m['truck_name']] = [];
            }
            foreach ($rows as $row) {
                $ls = Carbon::parse($row->loading_start);
                $re = Carbon::parse($row->return_end);
                $bestTruck = null;
                $bestGap = null;
                foreach ($allMixers as $mixer) {
                    $truckName = $mixer['truck_name'];
                    $capacity = $mixer['truck_capacity'];
                    if ($capacity != $row->capacity) {
                        continue;
                    }
                    $overlap = false;
                    foreach ($busyIntervals[$truckName] as $iv) {
                        if ($ls->lte($iv['end']) && $re->gte($iv['start'])) {
                            $overlap = true;
                            break;
                        }
                    }
                    if ($overlap)
                        continue;
                    $last = $lastFreeAt[$truckName];
                    $gap = $last ? $last->diffInMinutes($ls) : 0;
                    if ($last && $last->gte($ls))
                        continue;
                    if ($bestTruck === null || $gap > $bestGap || ($gap === $bestGap && strcmp((string) $truckName, (string) $bestTruck) < 0)) {
                        $bestTruck = $truckName;
                        $bestGap = $gap;
                    }
                }
                if (!$bestTruck) {
                    $busyIntervals[$row->transit_mixer][] = ['start' => Carbon::parse($row->loading_start), 'end' => Carbon::parse($row->return_end)];
                    $lastFreeAt[$row->transit_mixer] = Carbon::parse($row->return_end);
                    continue;
                }
                $row->transit_mixer = $bestTruck;
                $row->save();
                $busyIntervals[$bestTruck][] = ['start' => $ls->copy(), 'end' => $re->copy()];
                $lastFreeAt[$bestTruck] = $re->copy();
            }
        });
    }

    private function resourceCheck($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $orderKey)
    {
        $locations = $this->adjustLocations($order, $scheduleData->bps_availability);
        foreach ($locations as $location) {
            $tmsAvailability = $this->transitMixerHelper
                ->getTrucksLocationAvailability($scheduleData->tms_availability, $location);
            if (!$tmsAvailability) {
                continue;
            }
            $scheduleData = clone $generatedScheduleData;
            $scheduleData->location = $location;
            $scheduleData->order_no = $order->order_no;
            $this->resetOrderVariables($scheduleData, $order);
            $quantity = $order->quantity;
            $productType = ProductType::where('type', $order->mix_code)->first();
            if ($productType) {
                $scheduleData->loading_time = $productType->batching_creation_time;
            }
            $trip = 1;
            $scheduleData->trip = 1;
            $resourcesAvailable = true;
            while ($quantity > 0) {
                $this->assignResources($order, $scheduleData, $location, $trip);
                if (!$this->allResourcesAssigned($scheduleData)) {
                    $resourcesAvailable = false;
                    break;
                }
                $truckCapacity = $scheduleData->transit_mixer['data']['truck_capacity'];
                $batchQty = min($truckCapacity, $quantity);
                $quantity -= $batchQty;
                $trip++;
                $scheduleData->trip = $trip;
            }
            if (!$resourcesAvailable) {
                continue;
            }
            if ($order->pump && (int) $order->pump_qty > 0) {
                $pumpOk = $this->assignPump($order, $scheduleData, $location);
                if (!$pumpOk) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }
    private function scheduleOrder($scheduleData, $order, $orderKey, $strict = false)
    {
        $scheduleData->interval = 1;
        //Log::info("Processing Order: " . $order->order_no);
        $orderSchedule = clone $scheduleData;
        $orderSchedule->is_completed = false;
        $orderSchedule->delivered_quantity = 0;
        $isAvaiable = $this->resourceCheck($order, $orderSchedule, $scheduleData, $orderKey);
        if (!$isAvaiable && $strict)
            return false;

        $this->processOrder($order, $orderSchedule, $scheduleData, $orderKey);

        if (isset($orderSchedule->lastResponse) && $orderSchedule->lastResponse['last_trip'] > $orderSchedule->trip) {
            $orderSchedule = clone $orderSchedule->lastResponse['data'];
        }
        if (empty($orderSchedule->schedules)) {
            if (!$orderSchedule->failure_reason) {
                $orderSchedule->failure_reason = "Unable to schedule (unknown reason)";
            }
            DB::table('selected_orders')
                ->where('id', $order->id)
                ->update([
                    'failure_reason' => $orderSchedule->failure_reason
                ]);
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
        $scheduleData->failure_reason = null;
        return true;
    }
    public static function updateQcFromPreviousSlot()
    {
        // Fetch all slots where qc_time is 0
        $slots = SelectedOrderPumpSchedule::where('qc_time', 0)
            ->orderBy('pouring_start') // order by pouring_start to make previous slot logic easy
            ->get();

        foreach ($slots as $slot) {
            // Find the nearest previous slot on the same pump
            $previousSlot = SelectedOrderPumpSchedule::where('pump', $slot->pump)
                ->where('pouring_start', '<', $slot->pouring_start)
                ->orderByDesc('pouring_start')
                ->first();

            if (!$previousSlot) {
                // No previous slot exists for this pump
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
            $waitingEnd = Carbon::parse($slot->waiting_end);

            $waitingMinutes = $waitingStart->diffInMinutes($waitingEnd);
            $waitingMinutes = max($waitingMinutes, 0); // ensure not negative

            // Update current slot
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
            ]);

            Log::info("QC updated for order {$slot->order_no} on pump {$slot->pump}", [
                'previous_return_end' => $previousSlot->return_end,
                'qc_start' => $qcStart->format('Y-m-d H:i:s'),
                'qc_end' => $qcEnd->format('Y-m-d H:i:s'),
            ]);
        }
    }

}