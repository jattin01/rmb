<?php
namespace App\Lib\Services;

use App\Helpers\ConstantHelper;
use App\Helpers\V2\BatchingPlantHelper;
use App\Helpers\V2\PumpHelper;
use App\Helpers\V2\TransitMixerHelper;
use App\Helpers\V2\TransitMixerRestrictionHelper;
use App\Models\BatchingPlantAvailability;
use App\Models\GlobalSetting;
use App\Models\SelectedOrder;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\SelectedOrderSchedule;
use Carbon\Carbon;
use Log;
use DB;

class ScheduleData
{
    public $user_id;
    public $company;
    public $schedule_date;
    public $sch_adj_from;
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
    
    public $next_delivery_time;
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

        ini_set('max_execution_time', '-1');  // 0 = no limit

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
            ]);

            $this->generateSchedule($scheduleData);
        } catch (\Exception $e) {
            Log::error('Schedule Initialization Error: ' . $e->getMessage());
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
    private function hasPendingOrders($company, $user_id, $shift_start, $shift_end): bool
    {
        return SelectedOrder::where("group_company_id", $company)
            ->where("user_id", $user_id)
            ->whereBetween("delivery_date", [$shift_start, $shift_end])
            ->whereNull("start_time")
            ->where("selected", true)
            ->exists();
    }

    public function generateSchedule(ScheduleData &$scheduleData)
    {

        try {

            $this->initializeVariables($scheduleData);
            
            $orders = $this->fetchOrders($scheduleData);

            Log::info("Total Orders: " . count($orders));

            foreach ($orders as $orderKey => $order) {
                Log::info("Processing Order: " . $order->order_no);

                $orderSchedule = clone $scheduleData;

                $this->processOrder($order, $orderSchedule, $scheduleData, $orderKey);

                if(!$orderSchedule->is_completed) {
                    continue;
                }
                // if(isset($orderSchedule->lastResponse) && $orderSchedule->lastResponse['last_trip'] > $orderSchedule->trip) {
                //     $orderSchedule = clone $orderSchedule->lastResponse['data'];
                // }
            
                $this->storeSchedules($order, $orderSchedule);
              
                $scheduleData->tms_availability = $orderSchedule->tms_availability;
                $scheduleData->pumps_availability = $orderSchedule->pumps_availability;
                $scheduleData->bps_availability  = $orderSchedule->bps_availability;

                // $scheduleData->assigned_pumps = $orderSchedule->assigned_pumps;
                $scheduleData->assigned_plants = $orderSchedule->assigned_plants;
                $scheduleData->assigned_tms = $orderSchedule->assigned_tms;
            }

            //check pending orders which not scheduled
            $orders = $this->fetchOrders($scheduleData);

            Log::info("Total Orders for Reschedule: " . count($orders));


            foreach ($orders as $orderKey => $order) {
                Log::info("Processing Order: " . $order->order_no);

                $orderSchedule = clone $scheduleData;

                $this->processOrder($order, $orderSchedule, $scheduleData, $orderKey);

                if(isset($orderSchedule->lastResponse) && $orderSchedule->lastResponse['last_trip'] > $orderSchedule->trip) {
                    $orderSchedule = clone $orderSchedule->lastResponse['data'];
                }
            
                $this->storeSchedules($order, $orderSchedule);
                
                $scheduleData->tms_availability = $orderSchedule->tms_availability;
                $scheduleData->pumps_availability = $orderSchedule->pumps_availability;
                $scheduleData->bps_availability  = $orderSchedule->bps_availability;

                // $scheduleData->assigned_pumps = $orderSchedule->assigned_pumps;
                $scheduleData->assigned_plants = $orderSchedule->assigned_plants;
                $scheduleData->assigned_tms = $orderSchedule->assigned_tms;
            }

            dd("END");
            // dd($scheduleData, $orderSchedule);

        } catch (\Exception $ex) {

            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            dd($ex);
            throw $ex;
        }
    }


    private function processOrder($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $orderKey)
    {
        // Adjust locations to prioritize the order's location
        $locations = $this->adjustLocations($order, $scheduleData->bps_availability);
        // dd($locations);
        // Iterate through each location to process the order
        foreach ($locations as $location) {

            // Check if tms available for this location
            $tmsAvailability = $this->transitMixerHelper->getTrucksLocationAvailability($scheduleData->tms_availability, $location);

            if(!$tmsAvailability) {
                Log::info("No Truck available for Order: " . $order->order_no . " - Location: " . $location);
                continue;
            }
            // Reset variables for the current order
            $scheduleData = clone $generatedScheduleData;

            $scheduleData->order_start = Carbon::parse($order->delivery_date);
            $scheduleData->delivery_time = Carbon::parse($order->delivery_date);

            $scheduleData->order_no = $order->order_no;


            $this->resetOrderVariables($scheduleData, $order);


            Log::info("Processing Order: " . $order->order_no . " - Location: " . $location);
            $this->processTrips($order, $scheduleData, $generatedScheduleData, $location, $orderKey);

            if ($scheduleData->is_completed) {
                break;
            }
        }

    }
    

    private function initializeVariables(ScheduleData &$scheduleData)
    {
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
            "group_company_id", "id", "order_no", "customer", "project", "site", "location",
            "mix_code", "quantity", "delivery_date", "interval", "interval_deviation", "pump",
            "pouring_time", "travel_to_site", "return_to_plant", "pump_qty", "priority"
        )
            ->where("group_company_id", $scheduleData->company)
            ->where("user_id", $scheduleData->user_id)
            ->whereBetween("delivery_date", [$scheduleData->shift_start, $scheduleData->shift_end])
            ->whereNull("start_time")
            ->where("selected", true)
            ->orderBy('quantity', 'DESC')
            ->orderBy('priority', 'ASC')
            ->get();
    }

    private function resetOrderVariables(ScheduleData &$scheduleData, $order)
    {
        $scheduleData->assigned_pump = null;
        $scheduleData->schedules = [];
        $scheduleData->selected_order_pump_schedules = [];
        $scheduleData->is_completed = false;
        $scheduleData->order_no = $order->order_no;

        $deliveryDate = Carbon::parse($scheduleData->delivery_time);

        $scheduleData->return_time = $order->return_to_plant;
        $scheduleData->travel_time = $order->travel_to_site;
        
        $total_time = $scheduleData->loading_time + $scheduleData->qc_time + $scheduleData->travel_time + $scheduleData->insp_time + 4;
        
        $scheduleData->total_time = $total_time;
                 
        $scheduleData->loading_start = $deliveryDate->copy()->subMinutes($total_time);
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
    
        // Calculate QC times
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);

        // Calculate travel times
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);

        // Calculate inspection times
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);

        // Calculate pouring times
        $scheduleData->pouring_time = $order->pouring_time;
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($order->pouring_time);

        $pouring_interval = $order->interval + $order->pouring_time;
        if ($order->pump_qty > 1)
        {
            $pouring_interval = round(( $pouring_interval / $order->pump_qty) , 0);
        }

        $scheduleData->pump_qty =  $order->pump_qty ;
        $scheduleData->pump_cap =  $order->pump ;

        $scheduleData->pouring_interval = $pouring_interval;

        // Calculate cleaning times
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);

        // Calculate return times
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        
        // next delivery date
        

        if($scheduleData->phase == 2) {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes( $pouring_interval);
            
        }
        else {
            // if($scheduleData->pouring_interval > 0) {
            
            //     $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($scheduleData->pouring_interval);
            // }
            // else {
            // }
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($pouring_interval);
        }

        // next loading date
        $scheduleData->next_loading_time = $scheduleData->next_delivery_time->copy()->subMinutes($total_time);

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


    private function checkRestrictions(ScheduleData $scheduleData)
    {
        return isset($scheduleData->restriction_start) && isset($scheduleData->restriction_end) &&
            Carbon::parse($scheduleData->next_delivery_time)->between($scheduleData->restriction_start, $scheduleData->restriction_end);
    }

    private function processTrips($order, ScheduleData &$scheduleData, ScheduleData &$generatedScheduleData, $location, $orderKey)
    {

        $quantity = $order->quantity;
        $trip = 1;
        $scheduleData->trip = 1;

        while ($quantity > 0) {

            if($scheduleData->loading_start->gt($scheduleData->shift_end)) {
                $scheduleData->shift_end_exit = 1;


                $scheduleData->phase = 2;

                Log::info("shift end limit--" . $scheduleData->loading_start);

                if(isset($scheduleData->early_trip))
                    $scheduleData->next_delivery_time = $scheduleData->early_trip->copy()->subMinutes( $order->pouring_time + $order->interval);

                else {
                    $scheduleData->next_delivery_time = $scheduleData->order_start->copy()->subMinutes( 1 + $order->pouring_time);

                }

                $this->generateNextSlot($scheduleData, $order);
                continue;
            }

            if($scheduleData->loading_start->lt($scheduleData->shift_start)) {
                $scheduleData->shift_end_exit = 2;
                Log::info("shift start limit". $scheduleData->loading_start);
                break;
            }
          
            Log::info("Processing Trip: " . $trip . " for Order: " . $order->order_no . '-Location-'.$location." quantity: " . $quantity );

            Log::info("--TRIP--". $trip ."--LS -". $scheduleData->loading_start . 
                "--LE--". $scheduleData->loading_end . 
                "--DT--". $scheduleData->delivery_time );


                
            $this->assignResources($order, $scheduleData, $location, $trip);
            
            // if($trip == 12) {
            //     dd($scheduleData);
            // }

            // if($order->order_no == '11152' && $location == 'RMB - DXB' && $trip == 7) {
            //     dd($scheduleData->tms_availability, $scheduleData->assigned_tms, $scheduleData->assigned_pumps, $scheduleData->pouring_pump, $scheduleData->batching_plant, $scheduleData->transit_mixer);
            // }

            if ($this->allResourcesAssigned($scheduleData)) {
                Log::info("All Resources Assigned for Trip:  $trip -- order($orderKey)-". $order->order_no . '--qty--'.$quantity. ' -phase-'. $scheduleData->phase . '-LS-' . $scheduleData->loading_start);
                $this->finalizeTrip($order, $scheduleData, $location, $trip, $quantity, $orderKey);
                $quantity -= $scheduleData->truck_capacity;
                $trip++;

                $scheduleData->trip = $trip;
            } else {
                Log::info("Resource Not Found: " . $trip . '-- order-'. $order->order_no . ' -phase-'. $scheduleData->phase . '-LS-' . $scheduleData->loading_start . '-- shift end-'. $scheduleData->shift_end_exit);

                // if($scheduleData->trip == 11)
                //     dd($scheduleData);
                if($scheduleData->trip > 1) {

                    if($scheduleData->phase == 1) {
                        $scheduleData->phase = 2;

                        $scheduleData->next_delivery_time = $scheduleData->early_trip->copy()->subMinutes( $scheduleData->pouring_interval);
                        // dd($scheduleData->early_trip);
                        $this->generateNextSlot($scheduleData, $order);
                        continue;

                    }
                    else {

                        
                        $this->setLastTripResponse($scheduleData);
                        if($scheduleData->shift_end_exit == 0) {
                            $scheduleData->phase = 1;

                        }
                        $scheduleData->delivery_time =  $scheduleData->delivery_time->copy()->subMinutes(1);

                        $quantity = $order->quantity;
                        $trip = 1;
                        $scheduleData->trip = 1;
                        // continue;
                    }

                }
                else {

                    if($scheduleData->phase === 2) {

                        // if(isset($scheduleData->early_trip)) {
                        //     $nextDeliveryTime = $scheduleData->early_trip->copy()->subMinutes(1);
                        // }
                        // else if(isset($scheduleData->trip_time)) {
                        //     $nextDeliveryTime = $scheduleData->order_start->copy()->subMinutes($scheduleData->trip_time);
                        // }
                        // else {
                        // }
                        // $nextDeliveryTime = $scheduleData->order_start->copy()->subMinutes($scheduleData->loading_time);
                        $nextDeliveryTime = $scheduleData->order_start->copy()->subMinutes(1);
                    }
                    else { 

                        // if(isset($scheduleData->late_trip)) {
                        //     $nextDeliveryTime = $scheduleData->late_trip->copy()->addMinutes(1);
                        // }
                        // else if(isset($scheduleData->trip_time)) {
                        //     $nextDeliveryTime = $scheduleData->order_start->copy()->addMinutes($scheduleData->trip_time);
                        // }
                        // else {
                        // }
                        $nextDeliveryTime = $scheduleData->order_start->copy()->addMinutes(1);
                        
                    }

                    Log::info("Phase change - " .$scheduleData->phase .'--' .$nextDeliveryTime);

                    // $ddTime = $scheduleData->delivery_time;

                    $shiftEndExit = $scheduleData->shift_end_exit;
                    $phase = $scheduleData->phase;
                    $earlyTrip = $scheduleData->early_trip;
                    $lateTrip = $scheduleData->late_trip;
                    $lastResponse = $scheduleData->lastResponse;
                    $scheduleData = clone $generatedScheduleData;

                    $scheduleData->order_start = $nextDeliveryTime;
                    $scheduleData->delivery_time = $nextDeliveryTime;

                    $scheduleData->order_no = $order->order_no;
                    $scheduleData->phase = $phase;
                    $scheduleData->shift_end_exit = $shiftEndExit;
                    $scheduleData->early_trip = $earlyTrip;
                    $scheduleData->late_trip = $lateTrip;
                    $scheduleData->lastResponse = $lastResponse;
                    
                    $this->resetOrderVariables($scheduleData, $order);
                    // $ddTime2 = $scheduleData->delivery_time;

                    $quantity = $order->quantity;
                    $trip = 1;
                    $scheduleData->trip = 1;

                    $this->updateSchedule($scheduleData, $order);

                    Log::info("Process Order again - " . $order->order_no . ' -phase-'. $scheduleData->phase . '-LS-' . $scheduleData->loading_start . ' -DT-'. $scheduleData->delivery_time);
                    // $ddTime3 = $scheduleData->delivery_time;
                    // if($order->order_no == '11152' && $scheduleData->delivery_time && $scheduleData->delivery_time->gt($scheduleData->shift_end)) {
                    //     dd( 'fddf', $ddTime, $ddTime2, $ddTime3);
                    // }
                }

                
                
            }
            
            // }
            if($quantity <= 0) {
                $scheduleData->is_completed = 1;
                break;
            }
        }
        // dd('ss', $scheduleData);
       
    }

    private function setLastTripResponse(ScheduleData &$scheduleData) {
        if(!isset($scheduleData->lastResponse)) {
            $scheduleData->lastResponse = array(
                'last_trip' => $scheduleData->trip,
                'data' => clone $scheduleData
            );
        }

        elseif($scheduleData->lastResponse && $scheduleData->lastResponse['last_trip'] < $scheduleData->trip) {
            $scheduleData->lastResponse = array(
                'last_trip' => $scheduleData->trip,
                'data' => clone $scheduleData
            );
        }
    }

    private function updateSchedule(ScheduleData &$scheduleData, &$order)
    {
     
        
        // if($order->order_no == '11300') {
        //     dd($scheduleData);
        // }

        $order->delivered_quantity = 0;

        if($scheduleData->phase == 1) {
            $scheduleData->delivery_time = Carbon::parse($scheduleData->delivery_time)->copy()->addMinutes();

        }
        else {
            $scheduleData->delivery_time = Carbon::parse($scheduleData->delivery_time)->copy()->subMinutes();

        }
                 
        $scheduleData->loading_start = $scheduleData->delivery_time->copy()->subMinutes($scheduleData->total_time);
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);

        // Calculate QC times
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);

        // Calculate travel times
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);

        // Calculate inspection times
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);

        // Calculate pouring times
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($order->pouring_time);

        // Calculate cleaning times
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);

        // Calculate return times
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
        // dd($scheduleData);

        // next delivery date

        if($scheduleData->phase == 1) {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($scheduleData->pouring_interval);
            // if($scheduleData->pouring_interval > 0) {
            // }
            // else {
            //     $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($order->interval);
            // }
        }
        else {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes( $scheduleData->pouring_interval);
        }
        // $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($order->interval);
   
        // next loading date
        $scheduleData->next_loading_time = $scheduleData->next_delivery_time->copy()->subMinutes($scheduleData->total_time);

    }

    private function assignResources($order, ScheduleData &$scheduleData, $location, $trip)
    {
        $this->assignBatchingPlant($scheduleData, $location, $trip);
        $this->assignTransitMixer($scheduleData, $location, $trip);
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


        if(isset($scheduleData->batching_plant['data']['plant_name'])) {
            Log::info("Batching Plant Assigned: " . $trip ."--" . $scheduleData->batching_plant['data']['plant_name']);
        }
        else {
            Log::info("Batching Plant Not Found for Order: " . $trip);
        }
    }// end assignBatchingPlant

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

        if(isset($scheduleData->transit_mixer['data']['truck_name'])) {
            Log::info("Transit Mixer Assigned: " . $trip ."--" . $scheduleData->transit_mixer['data']['truck_name']);
        }
        else {
            Log::info("Transit Mixer Not Found for Order: " . $trip );
        }
    }

    private function assignPump($order, ScheduleData &$scheduleData, $location, $trip)
    {
        if ($order->pump) {
            $scheduleData->pouring_pump = PumpHelper::getAvailablePumps(
                $scheduleData->pumps_availability,
                $order->id,
                $scheduleData->company,
                $scheduleData->pouring_start,
                $scheduleData->pouring_end,
                $order->pump,
                $trip,
                $scheduleData->selected_order_pump_schedules,
                $scheduleData->shift_end,
                $order->pump_qty,
                $location,
                null,
                $scheduleData->assigned_pumps
            );

            if(isset($scheduleData->pouring_pump['pump']['pump_name'])) {
                Log::info("Pump Assigned: " . $trip ."--" . $scheduleData->pouring_pump['pump']['pump_name']);
            }
            else {
                Log::info("Pump Not Found for Order: ". $trip );
            }
        }
    }

    private function allResourcesAssigned(ScheduleData &$scheduleData)
    {

        if(!$scheduleData->batching_plant) return false;
        
        if(!$scheduleData->transit_mixer) return false;

        if(($scheduleData->pump_qty && $scheduleData->pump_qty > 0  ) && empty($scheduleData->pouring_pump)) return false;

        return true;
    }


    private function finalizeTrip($order, ScheduleData &$scheduleData, $location, $trip, $quantity, $orderKey)
    {
        $scheduleData->batching_qty = min($scheduleData->transit_mixer['data']['truck_capacity'], $quantity);

        $scheduleData->schedules[] = $this->createScheduleEntry($order, $scheduleData, $location, $trip);

        // Log::info("Current Schedule Count: " . count($scheduleData->schedules));
        
        // if($order->order_no == '11300' && $trip == 13) {
        //     dd($scheduleData->schedules);
        // }
        
        if ($order->pump) {

            $this->generatePumpSchedule($scheduleData, $order);

            // $pump_update = PumpHelper::searchAndUpdateArray( $scheduleData->selected_order_pump_schedules, [
            //     'group_company_id' => $scheduleData->company,
            //     'schedule_date' => $scheduleData->schedule_date, 
            //     'order_no' => $order->order_no, 
            //     'pump' => $scheduleData->pouring_pump['pump']['pump_name'], 
            //     'location' => $location
            // ], 
            // [
            //     'pouring_time' => [
            //         'value' => $order->pouring_time,
            //     ], 
            //     'pouring_end' => $scheduleData->pouring_end, 
            //     'cleaning_start' => $scheduleData->cleaning_start, 
            //     'cleaning_end' => $scheduleData->cleaning_end,
            //     'return_time' => $order->return_to_plant,
            //     'return_start' => $scheduleData->return_start,
            //     'return_end' => $scheduleData->return_end
            // ]);

            // if ($pump_update['match'] === false)
            // {
            //     $scheduleData->selected_order_pump_schedules[] = $this->createPumpScheduleEntry($order, $scheduleData, $location, $trip);
        
            // }
            // else {
            //     // dd($pump_update);
            //     $scheduleData->selected_order_pump_schedules = $pump_update['data'];
            // }

        }

        $this->updateResourceAvailability($scheduleData, $order, $location);
    }

    private function generatePumpSchedule(ScheduleData &$scheduleData, $order) {



       if(!isset($scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']]) ) {
            $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']] = array(


                'order_id' => $order->id,
                'user_id' => $scheduleData->user_id,
                'pump' => $scheduleData->pouring_pump['pump']['pump_name'],
                'mix_code' => $order->mix_code,
                'cust_product_id' => $order->customer_product_id,
                'trip' =>  1,
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
                'location' => $order->location,
                'pouring_time' => $scheduleData->pouring_time,
                'pouring_start' => $scheduleData->pouring_start,
                'pouring_end' => $scheduleData->pouring_end,
                'cleaning_start' => $scheduleData->cleaning_start,
                'cleaning_end' => $scheduleData->cleaning_end,
                'return_time' => $scheduleData->return_time,
                'return_start' => $scheduleData->return_start,
                'return_end' => $scheduleData->return_end
            );

            // dd($scheduleData->selected_order_pump_schedules);
       }  
       else {

            $selectedPump = $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']];
            $selectedPump['trip'] ++;
            $selectedPump['batching_qty'] += $scheduleData->batching_qty;

            $selectedPump['delivery_start'] = $scheduleData->delivery_time->copy()->lt($selectedPump['delivery_start']) ? $scheduleData->delivery_time : $selectedPump['delivery_start'];

            $selectedPump['travel_start'] = $scheduleData->travel_start->copy()->lt($selectedPump['travel_start']) ? $scheduleData->travel_start : $selectedPump['travel_start'];
            $selectedPump['travel_end'] = $scheduleData->travel_end->copy()->lt($selectedPump['travel_end']) ? $scheduleData->travel_end : $selectedPump['travel_end'];
            $selectedPump['qc_start'] = $scheduleData->qc_start->copy()->lt($selectedPump['qc_start']) ? $scheduleData->qc_start : $selectedPump['qc_start'];
            $selectedPump['qc_end'] = $scheduleData->qc_end->copy()->lt($selectedPump['qc_end']) ? $scheduleData->qc_end : $selectedPump['qc_end'];
            $selectedPump['insp_start'] = $scheduleData->insp_start->copy()->lt($selectedPump['insp_start']) ? $scheduleData->insp_start : $selectedPump['insp_start'];
            $selectedPump['insp_end'] = $scheduleData->insp_end->copy()->lt($selectedPump['insp_end']) ? $scheduleData->insp_end : $selectedPump['insp_end'];
            
            $selectedPump['pouring_time'] += $scheduleData->pouring_time;
            $selectedPump['pouring_start'] = $scheduleData->pouring_start->copy()->lt($selectedPump['pouring_start']) ? $scheduleData->pouring_start : $selectedPump['pouring_start'];
            $selectedPump['pouring_end'] = $scheduleData->pouring_end->copy()->gt($selectedPump['pouring_end']) ? $scheduleData->pouring_end : $selectedPump['pouring_end'];
            $selectedPump['cleaning_start'] = $scheduleData->cleaning_start->copy()->gt($selectedPump['cleaning_start']) ? $scheduleData->cleaning_start : $selectedPump['cleaning_start'];
            $selectedPump['cleaning_end'] = $scheduleData->cleaning_end->copy()->gt($selectedPump['cleaning_end']) ? $scheduleData->cleaning_end : $selectedPump['cleaning_end'];

            $selectedPump['return_start'] = $scheduleData->return_start->copy()->gt($selectedPump['return_start']) ? $scheduleData->return_start : $selectedPump['return_start'];
            $selectedPump['return_end'] = $scheduleData->return_end->copy()->gt($selectedPump['return_end']) ? $scheduleData->return_end : $selectedPump['return_end'];
            $scheduleData->selected_order_pump_schedules[$scheduleData->pouring_pump['pump']['pump_name']] = $selectedPump;

        }


    }

    private function storeSchedules($order, ScheduleData &$scheduleData, $location = 'MUS')
    {
        // DB::table("selected_order_schedules")->insert($scheduleData->schedules);
        // DB::table("selected_order_pump_schedules")->insert($scheduleData->selected_order_pump_schedules);

        $user_id = $scheduleData->user_id;

        // if($order->order_no == '11300') {
        //     dd($scheduleData->schedules);
        // }

        Log::info("Storing Schedules for Order: " . $order->order_no);

        // dd(json_decode( json_encode ($this->selectedOrderPumpSchedules)));
        DB::table("selected_order_schedules")->insert($scheduleData->schedules);

        if($order->pump) {

            // dd($scheduleData->selected_order_pump_schedules);
            DB::table("selected_order_pump_schedules")->insert($scheduleData->selected_order_pump_schedules);
        }

        $update_order = DB::table('selected_orders as A')
            ->where('id', $order->id)
            ->update([
                'start_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                    ->where('group_company_id', $scheduleData->company)
                    ->where('user_id', $user_id)
                    ->where('order_no', $order->order_no)
                    ->first()->min_pour ,

                'end_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                    ->where('group_company_id',  $scheduleData->company)
                    ->where('user_id', $user_id)->where('order_no', $order->order_no)
                    ->first()->max_pour, 
                'delivered_quantity' => $order->delivered_quantity,
                'location' => $location
            ]);

        //Update Deviation and AVL
        $order_deviation = DB::table("selected_orders")->where("id", $order->id)
            ->first();
        $order_deviation = Carbon::parse($order_deviation->delivery_date)
            ->copy()
            ->diffInMinutes(Carbon::parse($order_deviation->start_time) , false);
        DB::table("selected_orders")
            ->where("id", $order->id)
            ->update(['deviation' => $order_deviation]);

    }

    private function updateResourceAvailability(ScheduleData &$scheduleData,$order, $location)
    {
     
        $order->delivered_quantity = ($order->delivered_quantity ?? 0) + $scheduleData->batching_qty;
        $truck = $scheduleData->transit_mixer['data'];
        $truckIndex = $scheduleData->transit_mixer['index'];
        // Update Transit Mixer Availability
        $scheduleData->tms_availability[$truckIndex]['free_upto'] = $scheduleData->loading_start->copy()->subMinute()->format('Y-m-d H:i:s');
        $scheduleData->tms_availability[$truckIndex]['location'] = $location;


        if (isset($scheduleData->tms_availability[$truckIndex]['free_from']) &&
            $scheduleData->tms_availability[$truckIndex]['free_upto'] <= $scheduleData->tms_availability[$truckIndex]['free_from']) {
            unset($scheduleData->tms_availability[$truckIndex]);
        }
        
        // dd($scheduleData);
        $scheduleData->tms_availability[] = array(
            'truck_name' => $truck['truck_name'],
            'truck_capacity' => $truck['truck_capacity'],
            'loading_time' => $scheduleData->loading_time,
            'free_from' => $scheduleData->return_end ->addMinute()->format('Y-m-d H:i:s'),
            'free_upto' => $truck['free_upto'],
            'location' => $location,
        );

        // if($trip == 3) {
        //     dd($this->transitMixerAvailability);
        // }


        if ($order->pump) {
            $pump = $scheduleData->pouring_pump['pump'];
            $pumpIndex = $scheduleData->pouring_pump['index'];

            // $release_current_pump = false;
            // $current_remaining_qty = $quantity - $this->batchingQty;
            // $reamining_pump_trips = ceil($current_remaining_qty / $scheduleData->truck_capacity);
            // $reamining_pump_trips = $reamining_pump_trips / $order->pump_qty;
            // if ($reamining_pump_trips < 1)
            // {
                $release_current_pump = true;
            // }

            $scheduleData->pumps_availability[$pumpIndex]['free_upto'] = $scheduleData->pouring_start->copy()->subMinute();
            $scheduleData->pumps_availability[$pumpIndex]['location'] = $location;

            if (isset($scheduleData->pumps_availability[$pumpIndex]['free_from']) &&
                $scheduleData->pumps_availability[$pumpIndex]['free_upto'] <= $scheduleData->pumps_availability[$pumpIndex]['free_from']) {
                unset($scheduleData->pumps_availability[$pumpIndex]);
            }

            $scheduleData->pumps_availability[] = array(
                'pump_name' => $pump['pump_name'],
                'pump_capacity' => $pump['pump_capacity'],
                'free_from' => $scheduleData->pouring_end->copy()->addMinute(),
                'free_upto' => $pump['free_upto'],
                'location' => $location,
                'order_id' => $release_current_pump ? null : $order->id . '-' . (($scheduleData->trip) + $order->pump_qty) ,
                'order_id_wo_trip' => $release_current_pump ? null : $order->id
            );

            if(!$scheduleData->assigned_pump) {
                $scheduleData->assigned_pump = $pump['pump_name'];
            }

            if(! in_array($pump['pump_name'], $scheduleData->assigned_pumps)) {
                $scheduleData->assigned_pumps[] = $pump['pump_name'];
            }

        }

        // Update Batching Plant Availability
        $plant = $scheduleData->batching_plant['data'];
        $plantIndex = $scheduleData->batching_plant['index'];
        $scheduleData->bps_availability[$plantIndex]['free_upto'] = $scheduleData->loading_start->copy()->subMinute();

        if (isset($scheduleData->bps_availability[$plantIndex]['free_from']) &&
            $scheduleData->bps_availability[$plantIndex]['free_upto'] <= $scheduleData->bps_availability[$plantIndex]['free_from']) {
            unset($scheduleData->bps_availability[$plantIndex]);
        }

        $scheduleData->bps_availability[] = array(
            'plant_name' => $plant['plant_name'],
            'plant_capacity' => $plant['plant_capacity'],
            'free_from' => $scheduleData->loading_end->copy()->addMinute(),
            'free_upto' => $plant['free_upto'],
            'location' => $location,
        );


        if(! in_array($plant['plant_name'], $scheduleData->assigned_plants)) {
            $scheduleData->assigned_plants[] = $plant['plant_name'];
        }
        if(! in_array( $truck['truck_name'], $scheduleData->assigned_tms)) {
            $scheduleData->assigned_tms[] = $truck['truck_name'];
        }

        if(!isset($scheduleData->early_trip)  || ($scheduleData->early_trip->gt($scheduleData->pouring_start))) {
            $scheduleData->early_trip =  $scheduleData->pouring_start;
        }

        if(!isset($scheduleData->late_trip)  || ($scheduleData->late_trip->lt($scheduleData->pouring_end))) {
            $scheduleData->late_trip =  $scheduleData->pouring_end;
        }


        $this->generateNextSlot($scheduleData, $order);

        
    }

    private function generateNextSlot(ScheduleData &$scheduleData,$order) {


        $lastLoadingTime =  $scheduleData->loading_start;
        $scheduleData->delivery_time = $scheduleData->next_delivery_time;
                 
        $scheduleData->loading_start = $scheduleData->delivery_time->copy()->subMinutes($scheduleData->total_time);
        $scheduleData->loading_end = $scheduleData->loading_start->copy()->addMinutes($scheduleData->loading_time);
    
        if(!isset($scheduleData->trip_time)) {

            $scheduleData->trip_time = $scheduleData->loading_start->copy()->diffInMinutes($lastLoadingTime);
            // dd($lastLoadingTime, $scheduleData->loading_start, $scheduleData->trip_time);
        }


        // Calculate QC times
        $scheduleData->qc_start = $scheduleData->loading_end->copy()->addMinute();
        $scheduleData->qc_end = $scheduleData->qc_start->copy()->addMinutes($scheduleData->qc_time);

        // Calculate travel times
        $scheduleData->travel_start = $scheduleData->qc_end->copy()->addMinute();
        $scheduleData->travel_end = $scheduleData->travel_start->copy()->addMinutes($scheduleData->travel_time);

        // Calculate inspection times
        $scheduleData->insp_start = $scheduleData->travel_end->copy()->addMinute();
        $scheduleData->insp_end = $scheduleData->insp_start->copy()->addMinutes($scheduleData->insp_time);

        // Calculate pouring times
        $scheduleData->pouring_start = $scheduleData->insp_end->copy()->addMinute();
        $scheduleData->pouring_end = $scheduleData->pouring_start->copy()->addMinutes($order->pouring_time);

        // Calculate cleaning times
        $scheduleData->cleaning_start = $scheduleData->pouring_end->copy()->addMinute();
        $scheduleData->cleaning_end = $scheduleData->cleaning_start->copy()->addMinutes($scheduleData->cleaning_time);

        // Calculate return times
        $scheduleData->return_start = $scheduleData->cleaning_end->copy()->addMinute();
        $scheduleData->return_end = $scheduleData->return_start->copy()->addMinutes($scheduleData->return_time);
 
        // next delivery date

        if($scheduleData->phase == 2) {
            // if($scheduleData->pouring_interval > 0) {
            //     $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($scheduleData->pouring_interval);
            // }
            // else {
            // }
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->subMinutes($scheduleData->pouring_interval);

            // dd($scheduleData->pouring_start, $scheduleData->next_delivery_time);
            // dd($scheduleData->pouring_start, $scheduleData->pouring_end, $order->interval, $order->puring_time, $scheduleData->next_delivery_time);
        }
        else {
            $scheduleData->next_delivery_time = $scheduleData->pouring_start->copy()->addMinutes($scheduleData->pouring_interval);
            // if($scheduleData->pouring_interval > 0) {
            // }
            // else {
            //     $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($order->interval);
            // }
        }
        // $scheduleData->next_delivery_time = $scheduleData->pouring_end->copy()->addMinutes($order->interval);
   
        // next loading date
        $scheduleData->next_loading_time = $scheduleData->next_delivery_time->copy()->subMinutes($scheduleData->total_time);

        // if($scheduleData->trip == 9 && $order->order_no == '11244') {
        //     dd($scheduleData);
        // }
    }

    
    private function createScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {

        // Log::info("Creating Schedule Entry for Order: " . $order->order_no . ' Trip: ' . $trip . ' Start time: ' . $scheduleData->loading_start);
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
            "pouring_time" => $order->pouring_time,
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

    private function createPumpScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {
        return [
            'pump' => $scheduleData->pouring_pump['pump']['pump_name'],
            'batching_qty' => $scheduleData->batching_qty,
            "order_id" => $order->id,
            "group_company_id" => $scheduleData->company,
            "user_id" => $scheduleData->user_id,
            "schedule_date" => $scheduleData->schedule_date,
            "order_no" => $order->order_no,
            "location" => $location,
            "trip" => $trip,
            "mix_code" => $order->mix_code,
            "qc_time" => $scheduleData->qc_time,
            "qc_start" => $scheduleData->qc_start,
            "qc_end" => $scheduleData->qc_end,
            "travel_time" => $order->travel_to_site,
            "travel_start" => $scheduleData->travel_start,
            "travel_end" => $scheduleData->travel_end,
            "insp_time" => $scheduleData->insp_time,
            "insp_start" => $scheduleData->insp_start,
            "insp_end" => $scheduleData->insp_end,
            "pouring_time" => $order->pouring_time,
            "pouring_start" => $scheduleData->pouring_start,
            "pouring_end" => $scheduleData->pouring_end,
            "cleaning_time" => $scheduleData->cleaning_time,
            "cleaning_start" => $scheduleData->cleaning_start,
            "cleaning_end" => $scheduleData->cleaning_end,
            "return_time" => $order->return_to_plant,
            "return_start" => $scheduleData->return_start,
            "return_end" => $scheduleData->return_end,
            "delivery_start" => $scheduleData->loading_start,
        ];
    }

}