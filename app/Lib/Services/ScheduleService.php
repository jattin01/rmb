<?php
namespace App\Lib\Services;
use App\Helpers\ConstantHelper;
use App\Helpers\V2\BatchingPlantHelper;
use App\Helpers\V2\PumpHelper;
use App\Helpers\V2\TransitMixerHelper;
use App\Helpers\V2\TransitMixerRestrictionHelper;
use App\Helpers\CustomerProjectSiteHelper;
use App\Models\BatchingPlantAvailability;
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
use Illuminate\Support\Facades\DB;
use App\Models\TransitMixer;
class ScheduleData
{
    public $user_id;
    public $assigned_pumps_per_order;
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
                'transit_mixers' => $transit_mixer_ids
            ]);
            $scheduleData->transit_mixers = $transit_mixer_ids;
            $this->generateSchedule($scheduleData);
        } catch (\Exception $e) {
            //Log::error('Schedule Initialization Error: ' . $e->getMessage());
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
            Log::info("Total Orders: " . count($orders));
            foreach ($orders as $orderKey => $order) {
                $scheduleData->interval = 1;
                //Log::info("Processing Order: " . $order->order_no);
                $orderSchedule = clone $scheduleData;
                $orderSchedule->is_completed = false;
                $orderSchedule->delivered_quantity = 0;
                $this->processOrder($order, $orderSchedule, $scheduleData, $orderKey);
                if (isset($orderSchedule->lastResponse) && $orderSchedule->lastResponse['last_trip'] > $orderSchedule->trip) {
                    $orderSchedule = clone $orderSchedule->lastResponse['data'];
                }
                $this->storeSchedules($order, $orderSchedule);
                $scheduleData->tms_availability = $orderSchedule->tms_availability;
                $scheduleData->pumps_availability = $orderSchedule->pumps_availability;
                $scheduleData->bps_availability = $orderSchedule->bps_availability;
                $scheduleData->assigned_pumps = $orderSchedule->assigned_pumps;
                $scheduleData->assign_pump_slot = [];
                $scheduleData->assigned_plants = $orderSchedule->assigned_plants;
                $scheduleData->assigned_tms = $orderSchedule->assigned_tms;

            }
            $this->optimizeSchedules($scheduleData);

        } catch (\Exception $ex) {
            //Log::error('Error in generateSchedule: ' . $ex->getMessage());
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
            Log::info("\nOrder No: {$order->order_no}");
            Log::info("Interval Step / Adjustment: " . $scheduleData->interval);

            $scheduleData->order_start = Carbon::parse($order->delivery_date);
            $scheduleData->delivery_time = Carbon::parse($order->delivery_date);
            $scheduleData->order_no = $order->order_no;
            $scheduleData->location = $location;
            $scheduleData->early_trip = $scheduleData->late_trip = $scheduleData->order_start;
            $this->resetOrderVariables($scheduleData, $order);
            $this->processTrips($order, $scheduleData, $generatedScheduleData, $location, $orderKey);
            if ($scheduleData->is_completed) {
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
        )
            ->where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->whereBetween("delivery_date", [$scheduleData->shift_start, $scheduleData->shift_end])
            ->whereNull("start_time")
            ->where("selected", true)
            ->orderBy('priority', 'ASC')
            ->orderBy('quantity', 'DESC')
            ->get();
    }
    private function resetOrderVariables(ScheduleData &$scheduleData, $order, $truckQty = 8)
    {
        $scheduleData->assigned_pump = [];
        $scheduleData->assign_pump_slot = [];
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
    private function processTrips($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $location, $orderKey)
    {
        $quantity = $order->quantity;
        $trip = 1;
        $scheduleData->trip = 1;
        $scheduleData->phase_seq = 1;
        while ($quantity > 0) {
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
                break;
            }
            if ($scheduleData->loading_start->lt($scheduleData->shift_start)) {
                $scheduleData->shift_end_exit = 2;
                break;
            }
            //Log::info("--TRIP--" . $trip . "--LS -" . $scheduleData->loading_start .
            // "--LE--" . $scheduleData->loading_end .
            // "--DT--" . $scheduleData->delivery_time);
            $this->assignResources($order, $scheduleData, $location, $trip);
            if ($this->allResourcesAssigned($scheduleData)) {
                //Log::info("All Resources Assigned for Trip:  $trip -- order($orderKey)-" . $order->order_no . '--qty--' . $quantity . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start);
                $scheduleData->batching_qty = min($scheduleData->transit_mixer['data']['truck_capacity'], $quantity);
                $scheduleData->next_qty = $quantity - $scheduleData->batching_qty;
                $scheduleData->phase_seq++;
                $this->finalizeTrip($order, $scheduleData, $location, $trip, $quantity, $orderKey);
                $quantity -= $scheduleData->batching_qty;
                $trip++;
                $scheduleData->trip = $trip;
                $scheduleData->current_interval = 1;
            } else {
                //Log::info("Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit);
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
                    $trip = 1;
                    $scheduleData->trip = 1;
                    $scheduleData->phase_seq = 1;
                    $this->updateSchedule($scheduleData, $order);
                }
                if ($scheduleData->trip > 1) {
                    //Log::info(" if trip GT 1 Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit . '-CI-' . $scheduleData->current_interval);
                    if ($order->flexibility == 1 && $scheduleData->phase == 1) {
                        //Log::info(" if trip flexible GT 1 Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit . '-CI-' . $scheduleData->current_interval);
                        $scheduleData->phase = 2;
                        $scheduleData->phase_seq = 1;
                        $pouring_interval = $scheduleData->current_interval + $scheduleData->pouring_interval;
                        if ($order->pump_qty > 1) {
                            $pouring_interval++;
                            ;
                        } else if ($order->multi_pouring > 1) {
                            $pouring_interval++;
                        }
                        $scheduleData->next_delivery_time = $scheduleData->early_trip->copy()->subMinutes($pouring_interval);
                        $this->generateNextSlot($scheduleData, $order);
                        continue;
                    } else {
                        //Log::info(" if trip not flexible GT 1 Resource Not Found: " . $trip . '-- order-' . $order->order_no . ' -phase-' . $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-' . $scheduleData->shift_end_exit . '-CI-' . $scheduleData->current_interval);
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
                            $allotedPumpsQty = count($scheduleData->assigned_pumps);
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
                        $trip = 1;
                        $scheduleData->trip = 1;
                    }
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
    private function updateSchedule(ScheduleData &$scheduleData, &$order)
    {
        $scheduleData->assigned_pumps_per_order = 1;
        $order->delivered_quantity = 0;
        $scheduleData->delivered_quantity = 0;
        if ($scheduleData->phase == 1) {
            if ($scheduleData->interval > 0) {
                $scheduleData->interval = -$scheduleData->interval;
            } else {
                $scheduleData->interval = abs($scheduleData->interval) + 1;
            }
            $scheduleData->delivery_time = Carbon::parse($order->delivery_date)
                ->addMinutes($scheduleData->interval);
        } else {
            $scheduleData->delivery_time = Carbon::parse($scheduleData->delivery_time)->copy()->subMinutes();
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
        if ($trip <= $scheduleData->pump_qty)
            $this->assignPump($order, $scheduleData, $location, $trip);
    }
    private function assignBatchingPlant(ScheduleData &$scheduleData, $location, $trip)
    {
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
            $scheduleData->order_no
        );
        if (isset($scheduleData->batching_plant['data']['plant_name'])) {
            //Log::info("Batching Plant Assigned: " . $trip . "--" . $scheduleData->batching_plant['data']['plant_name']);
        } else {
            //Log::info("Batching Plant Not Found for Order: " . $trip);
        }
    }
    private function assignTransitMixer(ScheduleData &$scheduleData, $location, $trip)
    {
        $scheduleData->transit_mixer = TransitMixerHelper::getAvailableTrucks(
            $scheduleData->tms_availability,
            $scheduleData->truck_capacity,
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
            //Log::info("Transit Mixer Assigned: " . $trip . "--" . $scheduleData->transit_mixer['data']['truck_name']);
        } else {
            $reason = 'Transit Mixer Not Found for Order' . $scheduleData->order_no;
            if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                BatchingPlantAvailability::create(['group_company_id' => $scheduleData->company, 'location' => $scheduleData->location, 'plant_name' => $scheduleData->batching_plant['data']['plant_name'], 'plant_capacity' => 0, 'free_from' => $scheduleData->loading_start, 'free_upto' => $scheduleData->loading_start, 'user_id' => $scheduleData->user_id, 'reason' => $reason]);
            }
            //Log::info("Transit Mixer Not Found for Order: " . $trip);
        }
    }
    private function assignPump($order, ScheduleData &$scheduleData, $location, $trip)
    {

        if ($order->pump) {
            $scheduleData->pouring_pump = PumpHelper::getAvailablePumps(
                $scheduleData,
                $scheduleData->pumps_availability,
                $order->id,
                $scheduleData->company,
                $scheduleData->pump_loading_time,
                $scheduleData->return_end,
                $order->pump,
                $trip,
                $scheduleData->selected_order_pump_schedules,
                $scheduleData->shift_end,
                $order->pump_qty,
                $location,
                $scheduleData->assigned_pump,
                $scheduleData->assigned_pumps,
            );
            $scheduleData->assigned_pumps_per_order++;
            if (isset($scheduleData->pouring_pump['pump']['pump_name'])) {
                Log::info("Pump Assigned: " . $trip . "--" . $scheduleData->pouring_pump['pump']['pump_name']);

            } else {
                $reason = 'Pump Not Found for Order ' . $scheduleData->order_no;
                if (isset($scheduleData->batching_plant['data']['plant_name'])) {
                    BatchingPlantAvailability::create(['group_company_id' => $scheduleData->company, 'location' => $scheduleData->location, 'plant_name' => $scheduleData->batching_plant['data']['plant_name'], 'plant_capacity' => 0, 'free_from' => $scheduleData->loading_start, 'free_upto' => $scheduleData->loading_start, 'user_id' => $scheduleData->user_id, 'reason' => $reason]);
                }
                //Log::info("Pump Not Found for Order: " . $trip);
            }
        }
    }
    private function allResourcesAssigned(ScheduleData &$scheduleData)
    {
        if (!$scheduleData->batching_plant)
            return false;
        if (!$scheduleData->transit_mixer)
            return false;
        if (($scheduleData->pump_qty && $scheduleData->pump_qty > 0) && empty($scheduleData->pouring_pump))
            return false;
        return true;
    }
    private function finalizeTrip($order, ScheduleData &$scheduleData, $location, $trip, $quantity, $orderKey)
    {
        $scheduleData->schedules[] = $this->createScheduleEntry($order, $scheduleData, $location, $trip);
        if ($order->pump) {
            $this->generatePumpSchedule($scheduleData, $order);
        }
        $this->updateResourceAvailability($scheduleData, $order, $location);
    }
    private function generatePumpSchedule(ScheduleData &$scheduleData, $order)
    {
        if (!isset($scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']])) {
            $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']] = array(
                'order_id' => $order->id,
                'user_id' => $scheduleData->user_id,
                'pump' => $scheduleData->pouring_pump['pump']['pump_name'],
                'mix_code' => $order->mix_code,
                'cust_product_id' => $order->customer_product_id,
                'trip' => 1,
                'batching_qty' => $scheduleData->batching_qty,
                'qc_start' => $scheduleData->qc_start,
                'qc_time' => $scheduleData->qc_time,
                'qc_end' => $scheduleData->qc_end,
                'travel_time' => $scheduleData->travel_time,
                'travel_start' => $scheduleData->travel_start,
                'travel_end' => $scheduleData->travel_end,
                'insp_time' => $scheduleData->insp_time,
                'insp_start' => $scheduleData->insp_start,
                'insp_end' => $scheduleData->insp_end,
                'cleaning_time' => $scheduleData->cleaning_time,
                'delivery_start' => $scheduleData->delivery_time,
                'group_company_id' => $scheduleData->company,
                'schedule_date' => $scheduleData->schedule_date,
                'order_no' => $scheduleData->order_no,
                'location' => $scheduleData->location,
                'pouring_time' => $scheduleData->pouring_time,
                'pouring_start' => $scheduleData->pouring_start,
                'pouring_end' => $scheduleData->pouring_end,
                'cleaning_start' => $scheduleData->cleaning_start,
                'cleaning_end' => $scheduleData->cleaning_end,
                'return_time' => $scheduleData->return_time,
                'return_start' => $scheduleData->return_start,
                'return_end' => $scheduleData->return_end
            );
        } else {
            $selectedPump = $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']];
            $selectedPump['trip']++;
            $selectedPump['batching_qty'] += $scheduleData->batching_qty;
            $selectedPump['delivery_start'] = $scheduleData->delivery_time->copy()->lt($selectedPump['delivery_start']) ? $scheduleData->delivery_time : $selectedPump['delivery_start'];
            $selectedPump['travel_start'] = $scheduleData->travel_start->copy()->lt($selectedPump['travel_start']) ? $scheduleData->travel_start : $selectedPump['travel_start'];
            $selectedPump['travel_end'] = $scheduleData->travel_end->copy()->lt($selectedPump['travel_end']) ? $scheduleData->travel_end : $selectedPump['travel_end'];
            $selectedPump['qc_start'] = $scheduleData->qc_start->copy()->lt($selectedPump['qc_start']) ? $scheduleData->qc_start : $selectedPump['qc_start'];
            $selectedPump['qc_end'] = $scheduleData->qc_end->copy()->lt($selectedPump['qc_end']) ? $scheduleData->qc_end : $selectedPump['qc_end'];
            $selectedPump['insp_start'] = $scheduleData->insp_start->copy()->lt($selectedPump['insp_start']) ? $scheduleData->insp_start : $selectedPump['insp_start'];
            $selectedPump['insp_end'] = $scheduleData->insp_end->copy()->lt($selectedPump['insp_end']) ? $scheduleData->insp_end : $selectedPump['insp_end'];
            $selectedPump['pouring_start'] = $scheduleData->pouring_start->copy()->lt($selectedPump['pouring_start']) ? $scheduleData->pouring_start : $selectedPump['pouring_start'];
            $selectedPump['pouring_end'] = $scheduleData->pouring_end->copy()->gt($selectedPump['pouring_end']) ? $scheduleData->pouring_end : $selectedPump['pouring_end'];
            $selectedPump['pouring_time'] = Carbon::parse($selectedPump['pouring_end'])
                ->diffInMinutes(Carbon::parse($selectedPump['pouring_start']));
            $selectedPump['cleaning_start'] = $scheduleData->cleaning_start->copy()->gt($selectedPump['cleaning_start']) ? $scheduleData->cleaning_start : $selectedPump['cleaning_start'];
            $selectedPump['cleaning_end'] = $scheduleData->cleaning_end->copy()->gt($selectedPump['cleaning_end']) ? $scheduleData->cleaning_end : $selectedPump['cleaning_end'];
            $selectedPump['return_start'] = $scheduleData->return_start->copy()->gt($selectedPump['return_start']) ? $scheduleData->return_start : $selectedPump['return_start'];
            $selectedPump['return_end'] = $scheduleData->return_end->copy()->gt($selectedPump['return_end']) ? $scheduleData->return_end : $selectedPump['return_end'];
            $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']] = $selectedPump;
        }
    }
    private function updatePumpSchedule(ScheduleData &$scheduleData, $selectedPump)
    {

        $scheduleDataIndex = count($scheduleData->schedules) - 1;
        $count = 0;
        foreach ($selectedPump as $i => $pump) {
            $pump_name = Pump::where('pump_name', $selectedPump[$i]['pump'])
                ->first();
            $installMinutes = $pump_name && $pump_name->installation_time ? $pump_name->installation_time : 10;

            $install_end = Carbon::parse($scheduleData->min_loading_start)->subMinutes(1);
            $install_time = $pump_name && $pump_name->installation_time ? $pump_name->installation_time : 10;
            $install_start = Carbon::parse($install_end)->subMinutes($install_time);
            $insp_end = Carbon::parse($install_start)->subMinutes(1);
            $insp_start = Carbon::parse($insp_end)->subMinutes($selectedPump[$i]['insp_time']);
            $travel_end = Carbon::parse($insp_start)->subMinutes(1);
            $travel_start = Carbon::parse($travel_end)->subMinutes($selectedPump[$i]['travel_time']);
            $qc_end = Carbon::parse($travel_start)->subMinutes(1);
            $qc_start = Carbon::parse($qc_end)->subMinutes($selectedPump[$i]['qc_time']);
            $waiting_start = Carbon::parse($install_end)->addMinutes(1);
            $pouring_start = Carbon::parse($scheduleData->schedules[$count]['pouring_start']);
            $waiting_end = Carbon::parse($pouring_start)->subMinutes(1);
            $waiting_time = Carbon::parse($waiting_start)->diffInMinutes(Carbon::parse($waiting_end));
            $pouring_end = $scheduleData->schedules[$scheduleDataIndex]['pouring_end'];
            $pouring_time = $pouring_start->diffInMinutes($pouring_end);

            $clean_start = $pouring_end->copy()->addMinute();
            $clean_end = $clean_start->copy()->addMinutes($selectedPump[$i]['cleaning_time']);
            $return_start = $clean_end->copy()->addMinute();
            $return_end = $return_start->copy()->addMinutes($selectedPump[$i]['return_time']);


            $selectedPump[$i]['qc_start'] = $qc_start;
            $selectedPump[$i]['qc_end'] = $qc_end;
            $selectedPump[$i]['travel_start'] = $travel_start;
            $selectedPump[$i]['travel_end'] = $travel_end;
            $selectedPump[$i]['pouring_start'] = $pouring_start;
            $selectedPump[$i]['pouring_end'] = $pouring_end;
            $selectedPump[$i]['pouring_time'] = $pouring_time;



            $selectedPump[$i]['cleaning_start'] = $clean_start;
            $selectedPump[$i]['cleaning_end'] = $clean_end;
            $selectedPump[$i]['return_start'] = $return_start;
            $selectedPump[$i]['return_end'] = $return_end;




            $selectedPump[$i]['insp_start'] = $insp_start;
            $selectedPump[$i]['insp_end'] = $insp_end;
            $selectedPump[$i]['install_start'] = $install_start;
            $selectedPump[$i]['install_end'] = $install_end;
            $selectedPump[$i]['install_time'] = $installMinutes;
            $selectedPump[$i]['waiting_start'] = $waiting_start;
            $selectedPump[$i]['waiting_time'] = $waiting_time;
            $selectedPump[$i]['pouring_time'] = $pouring_time;
            $selectedPump[$i]['waiting_end'] = $waiting_end;
            $count++;
            $scheduleDataIndex--;
        }
        $scheduleData->selected_order_pump_schedules = $selectedPump;
    }
    private function storeSchedules($order, ScheduleData &$scheduleData)
    {
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
            $this->updatePumpSchedule($scheduleData, $scheduleData->selected_order_pump_schedules);
            DB::table("selected_order_pump_schedules")->insert($scheduleData->selected_order_pump_schedules);
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
        $scheduleData->tms_availability[$truckIndex]['free_upto'] = $scheduleData->loading_start->copy()->addSeconds()->format('Y-m-d H:i:s');
        $scheduleData->tms_availability[$truckIndex]['location'] = $location;
        if (
            isset($scheduleData->tms_availability[$truckIndex]['free_from']) &&
            $scheduleData->tms_availability[$truckIndex]['free_upto'] <= $scheduleData->tms_availability[$truckIndex]['free_from']
        ) {
            unset($scheduleData->tms_availability[$truckIndex]);
        }
        $scheduleData->tms_availability[] = array(
            'truck_name' => $truck['truck_name'],
            'truck_capacity' => $truck['truck_capacity'],
            'loading_time' => $scheduleData->loading_time,
            'free_from' => $scheduleData->return_end->subSeconds()->format('Y-m-d H:i:s'),
            'free_upto' => $truck['free_upto'],
            'location' => $location,
        );
        if ($order->pump) {
            $pump = $scheduleData->pouring_pump['pump'];
            $pumpIndex = $scheduleData->pouring_pump['index'];
            $release_current_pump = true;
            $installTime = $pump['installation_time'] ?? 10;
            $qcTime = $scheduleData->qc_time;
            $inspTime = $scheduleData->insp_time;
            $travelTime = $scheduleData->travel_time;

            $totalTime = $installTime + $qcTime + $inspTime + $travelTime + 4;
            $pump_loading_time = Carbon::parse($scheduleData->pump_loading_time);
            $start_time = $pump_loading_time->copy()->subMinutes($totalTime);

            $scheduleData->pumps_availability[$pumpIndex]['free_upto'] = $start_time->copy()->addSeconds();
            $scheduleData->pumps_availability[$pumpIndex]['location'] = $location;
            if (
                isset($scheduleData->pumps_availability[$pumpIndex]['free_from']) &&
                $scheduleData->pumps_availability[$pumpIndex]['free_upto'] <= $scheduleData->pumps_availability[$pumpIndex]['free_from']
            ) {
                unset($scheduleData->pumps_availability[$pumpIndex]);
            }
            $scheduleData->pumps_availability[] = array(
                'pump_name' => $pump['pump_name'],
                'pump_capacity' => $pump['pump_capacity'],
                'free_from' => $scheduleData->return_end->copy()->subSeconds(),
                'free_upto' => $pump['free_upto'],
                'location' => $location,
                'order_id' => $release_current_pump ? null : $order->id . '-' . (($scheduleData->trip) + $order->pump_qty),
                'order_id_wo_trip' => $release_current_pump ? null : $order->id
            );
            if (!isset($scheduleData->assigned_pump[$pump['pump_capacity']])) {
                $scheduleData->assigned_pump[$pump['pump_capacity']] = array();
            }
            $scheduleData->assigned_pump[$pump['pump_capacity']][] = $pump['pump_name'];

            if (!in_array($pump['pump_name'], $scheduleData->assigned_pumps)) {
                $scheduleData->assigned_pumps[] = $pump['pump_name'];
            }
        }
        $plant = $scheduleData->batching_plant['data'];
        $plantIndex = $scheduleData->batching_plant['index'];
        $scheduleData->bps_availability[$plantIndex]['free_upto'] = $scheduleData->loading_start->copy()->addSeconds();
        if (
            isset($scheduleData->bps_availability[$plantIndex]['free_from']) &&
            $scheduleData->bps_availability[$plantIndex]['free_upto'] <= $scheduleData->bps_availability[$plantIndex]['free_from']
        ) {
            unset($scheduleData->bps_availability[$plantIndex]);
        }
        $scheduleData->bps_availability[] = array(
            'plant_name' => $plant['plant_name'],
            'plant_capacity' => $plant['plant_capacity'],
            'free_from' => $scheduleData->loading_end->copy()->subSeconds(),
            'free_upto' => $plant['free_upto'],
            'location' => $location,
        );
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
                $scheduleDate = Carbon::parse($scheduleData->schedule_date);

                // 1️⃣ Get all transit mixers for this company
                $allMixers = DB::table('transit_mixers')
                    ->join('group_companies', function ($join) use ($scheduleData) {
                        $join->on('group_companies.id', '=', 'transit_mixers.group_company_id');
                    })
                    ->select(
                        'transit_mixers.id',
                        'transit_mixers.truck_name',
                        'transit_mixers.truck_capacity',
                        'transit_mixers.loading_time',
                        'group_companies.working_hrs_s',
                        'group_companies.working_hrs_e'
                    )
                    ->where('group_companies.id', $scheduleData->company)
                    ->where('transit_mixers.status', ConstantHelper::ACTIVE)
                    ->whereIn('transit_mixers.id', (array) $scheduleData->transit_mixers)
                    ->get();
                // 2️⃣ Build mixer availability array
                // mixer_id => array of ['start' => Carbon, 'end' => Carbon]
                $mixerAvailability = [];


                // Existing schedules
                $existingSchedules = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                    ->where("user_id", $scheduleData->user_id)
                    ->where('schedule_date', $scheduleData->schedule_date)
                    ->get();

                // Mark existing schedules as busy
                foreach ($existingSchedules as $schedule) {
                    if ($schedule->transit_mixer) {
                        $mixer = $allMixers->firstWhere('truck_name', $schedule->transit_mixer);
                        if ($mixer) {
                            $mixerAvailability[$mixer->id][$schedule->id] = [
                                'start' => Carbon::parse($schedule->loading_start),
                                'end' => Carbon::parse($schedule->return_end),
                            ];
                        }
                    }
                }
                foreach ($allMixers as $mixer) {
                    if (!isset($mixerAvailability[$mixer->id]))
                        $mixerAvailability[$mixer->id][0] = [
                            'start' => $scheduleDate->copy()->startOfDay(),
                            'end' => $scheduleDate->copy()->startOfDay(),
                        ];

                }


                // 3️⃣ Get orders grouped by plant
                $records = SelectedOrderSchedule::where("group_company_id", $scheduleData->company)
                    ->where("user_id", $scheduleData->user_id)
                    ->where('schedule_date', $scheduleData->schedule_date)
                    ->orderBy('batching_plant')
                    ->orderBy('loading_start')
                    ->get()
                    ->groupBy('batching_plant');



                // 4️⃣ Optimize each order
                foreach ($records as $plant => $plantRecords) {
                    $previous = null;


                    foreach ($plantRecords as $row) {

                        $loadingStart = Carbon::parse($row->loading_start);
                        $loadingEnd = Carbon::parse($row->loading_end);
                        $pouringStart = Carbon::parse($row->pouring_start);
                        // dd($row->mixer);

                        // Shift based on previous schedule
                        if ($previous) {
                            $prevLoadingEnd = Carbon::parse($previous->loading_end);
                            $gapInMinutes = $prevLoadingEnd->diffInMinutes($loadingStart, false);
                            if ($gapInMinutes > 1) {
                                $interval = $row->order->interval;
                                if ($gapInMinutes > $interval) {
                                    $bufferMinutes = $row->loading_time + $row->qc_time + $row->travel_time + $row->insp_time + 5 + $interval;
                                    $loadingStart = $pouringStart->copy()->subMinutes($bufferMinutes);
                                } else {
                                    $loadingStart = $prevLoadingEnd->copy()->addMinute();
                                }
                                $loadingEnd = $loadingStart->copy()->addMinutes($row->loading_time);
                            }
                        }

                        // QC
                        $qcStart = $loadingEnd->copy()->addMinute();
                        $qcEnd = $qcStart->copy()->addMinutes($row->qc_time);

                        // Travel
                        $travelStart = $qcEnd->copy()->addMinute();
                        $travelEnd = $travelStart->copy()->addMinutes($row->travel_time);

                        // Inspection
                        $inspStart = $travelEnd->copy()->addMinute();
                        $inspEnd = $inspStart->copy()->addMinutes($row->insp_time);

                        // Waiting until pouring
                        $waitingStart = $inspEnd->copy()->addMinute();
                        $waitingEnd = $pouringStart->copy()->subMinute();
                        if ($waitingEnd->lt($waitingStart)) {
                            $waitingEnd = $waitingStart;
                        }
                        $waitingTime = $waitingStart->diffInMinutes($waitingEnd);
                        $row->loading_start = $loadingStart;
                        $row->loading_end = $loadingEnd;
                        $row->qc_start = $qcStart;
                        $row->qc_end = $qcEnd;
                        $row->travel_start = $travelStart;
                        $row->travel_end = $travelEnd;
                        $row->insp_start = $inspStart;
                        $row->insp_end = $inspEnd;
                        $row->waiting_start = $waitingStart;
                        $row->waiting_end = $waitingEnd;
                        $row->waiting_time = $waitingTime;




                        // 4️⃣ Assign mixer considering availability and capacity
                        $row->transit_mixer = $this->assignAvailableMixer(
                            $allMixers,
                            $mixerAvailability,
                            $loadingStart,
                            Carbon::parse($row->return_end),
                            (int) $row->mixer->truck_capacity,
                            $row->transit_mixer,
                            $row->id
                        );
                        if ($row->transit_mixer == null)
                            continue;


                        // 5️⃣ Save updated values




                        $row->save();
                        $previous = $row;
                    }
                }

            } catch (\Exception $e) {
                Log::error("Schedule optimization failed: " . $e->getMessage());
                throw $e; // rollback transaction
            }

        });
    }

    /**
     * Assign a free transit mixer considering availability and capacity
     */
    protected function assignAvailableMixer(
        $allMixers,
        &$mixerAvailability,
        Carbon $loadingStart,
        Carbon $travelEnd,
        $orderQuantity,
        $currentMixerName = null,
        $scheduleId = null
    ) {
        $assignedMixerId = null;

        // 1️⃣ Try current mixer first
        if ($currentMixerName) {
            $currentMixer = $allMixers->firstWhere('truck_name', $currentMixerName);
            // if ($currentMixer && $currentMixer->truck_capacity >= $orderQuantity) {
            $busyIntervals = $mixerAvailability[$currentMixer->id] ?? [];
            $isFree = true;

            foreach ($busyIntervals as $schedId => $interval) {
                if ($schedId == $scheduleId)
                    continue; // ignore current schedule
                if ($loadingStart->lt($interval['end']) && $travelEnd->gt($interval['start'])) {
                    $isFree = false;
                    break;
                }
            }

            if ($isFree)
                $assignedMixerId = $currentMixer->id;
            //}
        }

        // 2️⃣ Otherwise, find any free mixer with enough capacity
        if (!$assignedMixerId) {
            foreach ($allMixers as $mixer) {
                // if ($mixer->truck_capacity < $orderQuantity)
                //     continue;

                $busyIntervals = $mixerAvailability[$mixer->id] ?? [];
                $isFree = true;

                foreach ($busyIntervals as $schedId => $interval) {
                    if ($schedId == $scheduleId)
                        continue;
                    if ($loadingStart->lt($interval['end']) && $travelEnd->gt($interval['start'])) {
                        $isFree = false;
                        break;
                    }
                }

                if ($isFree) {
                    $assignedMixerId = $mixer->id;
                    break;
                }
            }
        }

        // 3️⃣ Update availability
        if ($assignedMixerId) {
            // Remove from old mixer if changed
            if ($currentMixerName && $currentMixerName != $allMixers->firstWhere('id', $assignedMixerId)->truck_name) {
                $oldMixer = $allMixers->firstWhere('truck_name', $currentMixerName);
                if ($oldMixer && isset($mixerAvailability[$oldMixer->id][$scheduleId])) {
                    unset($mixerAvailability[$oldMixer->id][$scheduleId]);
                }
            }

            // Assign/update interval for this schedule
            $mixerAvailability[$assignedMixerId][$scheduleId] = [
                'start' => $loadingStart->copy(),
                'end' => $travelEnd->copy(),
            ];

            return $allMixers->firstWhere('id', $assignedMixerId)->truck_name;
        }

        Log::warning("No available transit mixer with enough capacity for {$loadingStart} - {$travelEnd}");
        return null;
    }


}