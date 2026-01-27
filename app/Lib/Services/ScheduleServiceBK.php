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
    public $assingedPlants ;
    public $assingedTms;
    public $assingedPump;
    public $assingedPumps;


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

         $this->pumpHelper = new PumpHelper();
         $this->transitMixerHelper = new TransitMixerHelper();
         $this->batchingPlantHelper = new BatchingPlantHelper();
         $this->restrictionHelper = new TransitMixerRestrictionHelper();
    }

    public function initializeSchedule( int $user_id, string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $schedule_preference, string $shift_start, string $shift_end, int $interval_deviation)
    {

        try {
            $this->clearPreviousSchedules($company, $user_id, $shift_start, $shift_end);

            $pumpsAvailability = $this->pumpHelper->getPumpsAvailability($company, $schedule_date, $pump_ids);
            $tmsAvailability = $this->transitMixerHelper->getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);
            $minOrderStartTime = $this->batchingPlantHelper->getMinOrderScheduleTimeCopy($company, $user_id, $shift_start, $shift_end, $schedule_date);
            $bpsAvailability = $this->batchingPlantHelper->getBatchingPlantAvailabilityCopy($company, $schedule_date, $batching_plant_ids, $minOrderStartTime);

            $restrictions = $this->restrictionHelper->getRestrictions($company, $schedule_date, $shift_start);

            $scheduleLoop = [1440];
            $scheduledOrders = [];

             // Extract truck capacities
            $truckCapacities = array_unique(array_column($tmsAvailability, 'truck_capacity'));
            $minTruckCapacity = min($truckCapacities); // Use the minimum truck capacity

            // $pumpCapacities = array_unique(array_column($pumpsAvailability, 'truck_capacity'));
            // $minPumpCapacity = min($pumpCapacities); // Use the minimum truck capacity

            

            foreach ($scheduleLoop as $loopKey => $loopTime) {

                $scheduleData = new ScheduleData([
                    'user_id' => $user_id,
                    'company' => $company,
                    'schedule_date' => $schedule_date,
                    'sch_adj_from' => 0,
                    'sch_adj_to' => $loopTime,
                    'tms_availability' => $tmsAvailability,
                    'pumps_availability' => $pumpsAvailability,
                    'bps_availability' => $bpsAvailability,
                    'schedule_preference' => $schedule_preference,
                    'shift_start' => $shift_start,
                    'shift_end' => $shift_end,
                    'restriction_start' => $restrictions['restriction_start'],
                    'restriction_end' => $restrictions['restriction_end'],
                    'min_order_start_time' => $minOrderStartTime,
                    'interval_deviation' => $interval_deviation,
                    'generateLog' => $loopKey === 3,
                    'execute' => $loopKey === 3,
                    'truck_capacity' => $minTruckCapacity, // Pass truck capacity


                ]);
                
                $modifiedOrders = $this->generateSchedule($scheduleData);

                // $bpsAvailability = $this->batchingPlantHelper->generateOrUpdateAvailability($user_id, $schedule_date, $company, $minOrderStartTime, $shift_end);

                // if (!$this->hasPendingOrders($company, $user_id, $shift_start, $shift_end)) {
                //     break;
                // }
            }
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
            ->update(['start_time' => null, 'end_time' => null, 'deviation' => null]);
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


    public function generateSchedule(ScheduleData $scheduleData)
    {
        try {
            // Initialize required variables
            $this->initializeVariables($scheduleData);

            // Fetch orders to be scheduled
            $orders = $this->fetchOrders($scheduleData);
            // Process each order
            foreach ($orders as $orderKey => $order) {
                $this->processOrder($order, $scheduleData, $orderKey);
            }

            // Filter and return scheduled orders
            return $this->filterScheduledOrders($this->ordersCopy);
        } catch (\Exception $ex) {
            dd($ex);
            Log::error('Error in generateSchedule: ' . $ex->getMessage());
            throw $ex;
        }
    }

    private function initializeVariables(ScheduleData $scheduleData)
    {
        $this->qcTime = GlobalSetting::where('group_company_id', $scheduleData->company)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;
        $this->inspTime = GlobalSetting::where('group_company_id', $scheduleData->company)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;
        $this->cleaningTime = GlobalSetting::where('group_company_id', $scheduleData->company)->value('chute_cleaning_site') ?? ConstantHelper::CLEANING_TIME;

        $this->transitMixerAvailability = $scheduleData->tms_availability;
        $this->pumpAvailability = $scheduleData->pumps_availability;
        $this->batchingPlantAvailability = $scheduleData->bps_availability;
       
        $this->loading_time = ConstantHelper::LOADING_TIME;
       
        $this->ordersCopy = [];
        $this->schedules = [];
        $this->selectedOrderPumpSchedules = [];

        $this->travel_start = null;
        $this->travel_end = null;
        $this->loading_start = null;
        $this->loading_end = null;
        $this->qc_start = null;
        $this->qc_end = null;
        $this->insp_start = null;
        $this->insp_end = null;
        $this->pouring_start = null;
        $this->pouring_end = null;
        $this->cleaning_start = null;
        $this->cleaning_end = null;
        $this->return_start = null;
        $this->return_end = null;
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

    private function processOrder($order, ScheduleData $scheduleData, $orderKey)
    {
        $this->resetOrderVariables($order);

        // Adjust locations for batching plants
        $locations = $this->adjustLocations($order, $this->batchingPlantAvailability);
        foreach ($locations as $location) {
            $this->processLocation($order, $scheduleData, $location, $orderKey);
        }
    }

    private function resetOrderVariables($order)
    {
        $this->pouringPump = null;
        $this->transitMixer = null;
        $this->batchingPlant = null;

        $this->pouringPumpIndex = null;
        $this->transitMixerIndex = null;
        $this->batchingPlantIndex = null;

        $this->pumpIds = [];
        $this->batchingQty = 0;
        $this->shiftEndExit = 0;
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

    private function processLocation($order, ScheduleData $scheduleData, $location, $orderKey)
    {
        $plantAvailability = BatchingPlantHelper::getMinAvailTimeCopy(
            $this->batchingPlantAvailability,
            ConstantHelper::LOADING_TIME,
            $this->batchingPlant,
            $this->batchingPlantIndex,
            $scheduleData->restriction_start,
            $scheduleData->restriction_end
        );

        $deliveryTime = $this->calculateDeliveryTime($order, $scheduleData, $plantAvailability);

        $this->adjustScheduleBasedOnAvailability($order, $scheduleData, $location, $deliveryTime, $orderKey);
    }

    private function calculateDeliveryTime($order, ScheduleData $scheduleData, $plantAvailability)
    {
        return $order->delivery_date;
        return $scheduleData->schedule_preference !== ConstantHelper::CUSTOMER_TIMELINE_PREF
            ? $plantAvailability
            : $order->delivery_date;
    }

    private function adjustScheduleBasedOnAvailability($order, ScheduleData $scheduleData, $location, $deliveryTime, $orderKey)
    {
        $adjustmentTime = $scheduleData->sch_adj_from ?? 0;
        $deliveryDateN = Carbon::parse($deliveryTime)->addMinutes($adjustmentTime);
        $deliveryDateP = Carbon::parse($deliveryTime)->subMinutes($adjustmentTime);

        $availabilityCounter = 0;

        while ($availabilityCounter <= 215) {
            $availabilityCounter++;

            foreach (ConstantHelper::TO_FROM_LOOP as $val) {
                $this->resetResources();

                $deliveryDate = $val === 1 ? $deliveryDateN : $deliveryDateP;

                if ($this->checkRestrictions($deliveryDate, $scheduleData)) {
                    continue;
                }

                $this->processTrips($order, $scheduleData, $location, $deliveryDate, $orderKey);

                if ($this->shiftEndExit === 1) {
                    break;
                }
            }

            if ($this->shiftEndExit === 1) {
                break;
            }

            $adjustmentTime++;
            $deliveryDateN = Carbon::parse($deliveryTime)->addMinutes($adjustmentTime);
            $deliveryDateP = Carbon::parse($deliveryTime)->subMinutes($adjustmentTime);
        }

        // dd( $this->schedules);
    }

    private function resetResources()
    {
        $this->transitMixer = null;
        $this->pouringPump = null;

        $this->pouringPumpIndex = null;
        $this->transitMixerIndex = null;

        $this->batchingPlant = null;
        $this->batchingPlantIndex = null;

        $this->pumpIds = [];
    }

    private function checkRestrictions($deliveryDate, ScheduleData $scheduleData)
    {
        return isset($scheduleData->restriction_start) && isset($scheduleData->restriction_end) &&
            $deliveryDate->between($scheduleData->restriction_start, $scheduleData->restriction_end);
    }

    private function processTrips($order, ScheduleData $scheduleData, $location, $deliveryDate, $orderKey)
    {
        $quantity = $order->quantity;
        $trip = 1;

        $scheduleData->assingedPlants = array();
        $scheduleData->assingedTms = array();
        $scheduleData->assingedPumps = array();

        // dd($order);
        while ($quantity > 0) {
            $this->assignResources($order, $scheduleData, $location, $deliveryDate, $trip);

            if ($this->allResourcesAssigned($order)) {

                // if($trip == 2) {
                //     dd ('found 2');
                // }

                 // Calculate return time
                $return_time = $order->return_to_plant;

                // Calculate loading times
                $travel_time = $order->travel_to_site;
                $total_time = $this->loading_time + $this->qcTime + $travel_time + $this->inspTime +4;

                
                $this->loading_start = $deliveryDate->copy()->subMinutes($total_time);
                $this->loading_end = $this->loading_start->copy()->addMinutes($this->loading_time);
              
                // Calculate QC times
                $this->qc_start = $this->loading_end->copy()->addMinute();
                $this->qc_end = $this->qc_start->copy()->addMinutes($this->qcTime);

                // Calculate travel times
                $this->travel_start = $this->qc_end->copy()->addMinute();
                $this->travel_end = $this->travel_start->copy()->addMinutes($travel_time);

                // Calculate inspection times
                $this->insp_start = $this->travel_end->copy()->addMinute();
                $this->insp_end = $this->insp_start->copy()->addMinutes($this->inspTime);

                // Calculate pouring times
                $this->pouring_start = $this->insp_end->copy()->addMinute();
                $this->pouring_end = $this->pouring_start->copy()->addMinutes($order->pouring_time);


                // next delivery date
                $deliveryDate = $this->pouring_end->copy()->addMinutes($order->interval);

                // next loading date
                $deliveryDate = $this->deliveryDate->copy()->subMinutes($total_time);

                // Calculate cleaning times
                $this->cleaning_start = $this->pouring_end->copy()->addMinute();
                $this->cleaning_end = $this->cleaning_start->copy()->addMinutes($this->cleaningTime);

                // Calculate return times
                $this->return_start = $this->cleaning_end->copy()->addMinute();
                $this->return_end = $this->return_start->copy()->addMinutes($return_time);


                $truck = $this->transitMixer['data'];
                $this->transitMixerIndex = $this->transitMixer['index'];

                // Calculate the next available time for the truck
                $nextAvailableTime =$this->return_end->copy()->addMinute();
            
                
                // Update Transit Mixer Availability
                $this->transitMixerAvailability[$this->transitMixerIndex]['free_upto'] = $this->loading_start->copy()->subMinute()->format('Y-m-d H:i:s');
                $this->transitMixerAvailability[$this->transitMixerIndex]['location'] = $location;

        
                if (isset($this->transitMixerAvailability[$this->transitMixerIndex]['free_from']) &&
                    $this->transitMixerAvailability[$this->transitMixerIndex]['free_upto'] <= $this->transitMixerAvailability[$this->transitMixerIndex]['free_from']) {
                    unset($this->transitMixerAvailability[$this->transitMixerIndex]);
                }
              
                // dd($truck);
                $this->transitMixerAvailability[] = array(
                    'truck_name' => $truck['truck_name'],
                    'truck_capacity' => $truck['truck_capacity'],
                    'loading_time' => $this->loading_time,
                    'free_from' => $nextAvailableTime->addMinute()->format('Y-m-d H:i:s'),
                    'free_upto' => $truck['free_upto'],
                    'location' => $location,
                );

                // if($trip == 3) {
                //     dd($this->transitMixerAvailability);
                // }


                if ($order->pump) {
                    $pump = $this->pouringPump['pump'];

                    $release_current_pump = false;
                    $current_remaining_qty = $quantity - $this->batchingQty;
                    $reamining_pump_trips = ceil($current_remaining_qty / $scheduleData->truck_capacity);
                    $reamining_pump_trips = $reamining_pump_trips / $order->pump_qty;
                    if ($reamining_pump_trips < 1)
                    {
                        $release_current_pump = true;
                    }

                    $this->pouringPumpIndex = $this->pouringPump['index'];
                    $this->pumpAvailability[$this->pouringPumpIndex]['free_upto'] = $this->pouring_start->copy()->subMinute();
                    $this->pumpAvailability[$this->pouringPumpIndex]['location'] = $location;
    
                    if (isset($this->pumpAvailability[$this->pouringPumpIndex]['free_from']) &&
                        $this->pumpAvailability[$this->pouringPumpIndex]['free_upto'] <= $this->pumpAvailability[$this->pouringPumpIndex]['free_from']) {
                        unset($this->pumpAvailability[$this->pouringPumpIndex]);
                    }
    
                    $this->pumpAvailability[] = array(
                        'pump_name' => $pump['pump_name'],
                        'pump_capacity' => $pump['pump_capacity'],
                        'free_from' => $this->pouring_end->copy()->addMinute(),
                        'free_upto' => $pump['free_upto'],
                        'location' => $location,
                        'order_id' => $release_current_pump ? null : $order->id . '-' . (($trip) + $order->pump_qty) ,
                        'order_id_wo_trip' => $release_current_pump ? null : $order->id
                    );

                    if(!$scheduleData->assingedPump) {
                        $scheduleData->assingedPump = $pump['pump_name'];
                    }

                    if(! in_array($pump['pump_name'], $scheduleData->assingedPumps)) {
                        $scheduleData->assingedPumps[] = $pump['pump_name'];
                    }

                }
    
                // Update Batching Plant Availability

                $plant = $this->batchingPlant['data'];
                $this->batchingPlantIndex = $this->batchingPlant['index'];
                $this->batchingPlantAvailability[$this->batchingPlantIndex]['free_upto'] = $this->loading_start->copy()->subMinute();
    
                if (isset($this->batchingPlantAvailability[$this->batchingPlantIndex]['free_from']) &&
                    $this->batchingPlantAvailability[$this->batchingPlantIndex]['free_upto'] <= $this->batchingPlantAvailability[$this->batchingPlantIndex]['free_from']) {
                    unset($this->batchingPlantAvailability[$this->batchingPlantIndex]);
                }
    
                $this->batchingPlantAvailability[] = array(
                    'plant_name' => $plant['plant_name'],
                    'plant_capacity' => $plant['plant_capacity'],
                    'free_from' => $this->loading_end->copy()->addMinute(),
                    'free_upto' => $plant['free_upto'],
                    'location' => $location,
                );


                if(! in_array($plant['plant_name'], $scheduleData->assingedPlants)) {
                    $scheduleData->assingedPlants[] = $plant['plant_name'];
                }
                if(! in_array( $truck['truck_name'], $scheduleData->assingedTms)) {
                    $scheduleData->assingedTms[] = $truck['truck_name'];
                }

                // $scheduleData->assingedPlants[] = $plant['plant_name'];
                // $scheduleData->assingedTms[] = $truck['truck_name'];
    
                // Finalize the trip

                // dd($this->transitMixerAvailability, $this->pumpAvailability, $this->batchingPlantAvailability);

                
                $this->finalizeTrip($order, $scheduleData, $location, $trip, $quantity, $orderKey);
                $quantity -= $this->batchingQty;
                $trip++;
            } else {
                // Log::info("TIPS". json_encode($this->schedules));
                // dd('herer not found', $trip, $quantity, $this->schedules);
                break;
            }
        }

        // dd($this->transitMixerAvailability);

        if($quantity <= 0) {
            $this->storeOrderSchedule($order, $scheduleData, $location);
        }
    }

    private function assignResources($order, ScheduleData $scheduleData, $location, $deliveryDate, $trip)
    {
        $this->assignBatchingPlant($scheduleData, $location, $deliveryDate, $trip);
        $this->assignTransitMixer($scheduleData, $location, $deliveryDate, $order->return_to_plant, $trip);
        $this->assignPump($order, $scheduleData, $location, $deliveryDate, $trip);
    }

    private function assignBatchingPlant(ScheduleData $scheduleData, $location, $deliveryDate, $trip)
    {
        $this->batchingPlant = BatchingPlantHelper::getAvailableBatchingPlants(
            $this->batchingPlantAvailability,
            $scheduleData->company,
            $location,
            $deliveryDate,
            $deliveryDate->copy()->addMinutes(ConstantHelper::LOADING_TIME),
            $scheduleData->restriction_start,
            $scheduleData->restriction_end,
            $trip,
            $scheduleData->assingedPlants
        );
    }

    private function storeOrderSchedule($order, $scheduleData, $location) {
        $user_id = $scheduleData->user_id;

        // dd(json_decode( json_encode ($this->selectedOrderPumpSchedules)));
        DB::table("selected_order_schedules")->insert($this->schedules);
        DB::table("selected_order_pump_schedules")->insert($this->selectedOrderPumpSchedules);
        $update_order = DB::table('selected_orders as A')->where('id', $order->id)
            ->update(['start_time' => DB::table('selected_order_schedules as B')
            ->select(DB::raw('MIN(pouring_start) AS min_pour'))
            ->where('group_company_id', $scheduleData->company)->where('user_id', $user_id)->where('order_no', $order->order_no)
            ->first()->min_pour,

        'end_time' => DB::table('selected_order_schedules as B')
            ->select(DB::raw('MAX(pouring_end) AS max_pour'))
            ->where('group_company_id',  $scheduleData->company)->where('user_id', $user_id)->where('order_no', $order->order_no)
            ->first()->max_pour, 'location' => $location, ]);
        //Reset schedules
        $schedules = [];
        $selected_order_pump_schedules = [];
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

    private function assignTransitMixer(ScheduleData $scheduleData, $location, $deliveryDate, $returnTime, $trip)
    {
        $this->transitMixer = TransitMixerHelper::getAvailableTrucks(
            $this->transitMixerAvailability,          // $trucks
            $scheduleData->truck_capacity,            // $truck_cap (Assuming truck capacity is part of ScheduleData or can be derived)
            $deliveryDate,                            // $loading_start
            $deliveryDate->copy()->addMinutes($returnTime), // $return_end
            $scheduleData->shift_end,                 // $location_end_time
            $scheduleData->restriction_start,         // $restriction_start
            $scheduleData->restriction_end,           // $restriction_end
            $location      ,                           // $location
            $trip,
            $scheduleData->assingedTms
        );
        // dd($this->transitMixer);
    }

    private function assignPump($order, ScheduleData $scheduleData, $location, $deliveryDate, $trip)
    {
        if ($order->pump) {
            $this->pouringPump = PumpHelper::getAvailablePumps(
                $this->pumpAvailability,                // $pumps
                $order->id,                             // $order_id
                $scheduleData->company,                 // $company
                $deliveryDate,                          // $pump_start_time
                $deliveryDate->copy()->addMinutes($order->interval), // $pump_end_time
                $order->pump,                           // $pump_cap
                $trip,                                  // $trip
                $this->selectedOrderPumpSchedules,      // $selected_order_pump_schedules
                $scheduleData->shift_end,               // $location_end_time
                $order->pump_qty,                       // $pump_qty
                $location   ,                            // $location
                $scheduleData->assingedPump,
                $scheduleData->assingedPumps
            );
        }
    }

    private function allResourcesAssigned($order)
    {

        Log::info('Checking allResourcesAssigned', [
            'batchingPlant' => $this->batchingPlant,
            'transitMixer' => $this->transitMixer,
            'pouringPump' => $this->pouringPump,
            'orderPump' => $order->pump,
        ]);
        // dd($this->batchingPlant, $this->transitMixer, $this->pouringPump);
        return isset($this->batchingPlant, $this->transitMixer) &&
            (!isset($order->pump) || isset($this->pouringPump));
    }

    private function finalizeTrip($order, ScheduleData $scheduleData, $location, $trip, $quantity, $orderKey)
    {
        $this->batchingQty = min($this->transitMixer['data']['truck_capacity'], $quantity);

        $this->schedules[] = $this->createScheduleEntry($order, $scheduleData, $location, $trip);

        if ($order->pump) {

            $pump_update = PumpHelper::searchAndUpdateArray( $this->selectedOrderPumpSchedules, [
                'group_company_id' => $scheduleData->company,
                'schedule_date' => $scheduleData->schedule_date, 
                'order_no' => $order->order_no, 
                'pump' => $this->pouringPump['pump']['pump_name'], 
                'location' => $location
            ], 
            [
                'pouring_time' => [
                    'value' => $order->pouring_time,
                ], 
                'pouring_end' => $this->pouring_end, 
                'cleaning_start' => $this->cleaning_start, 
                'cleaning_end' => $this->cleaning_end,
                'return_time' => $order->return_to_plant,
                'return_start' => $this->return_start,
                'return_end' => $this->return_end
            ]);

            if ($pump_update['match'] === false)
            {
                $this->selectedOrderPumpSchedules[] = $this->createPumpScheduleEntry($order, $scheduleData, $location, $trip);
        
            }
            else {
                // dd($pump_update);
                $this->selectedOrderPumpSchedules = $pump_update['data'];
            }
        }

        // dd($this->schedules, $this->selectedOrderPumpSchedules);
        $this->updateResourceAvailability($scheduleData, $location);
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
            "batching_plant" => $this->batchingPlant['data']['plant_name'] ?? null,
            "transit_mixer" => $this->transitMixer['data']['truck_name'] ?? null,
            "batching_qty" => $this->batchingQty,
            "loading_time" => $this->loading_time,
            "loading_start" => $this->loading_start,
            "loading_end" => $this->loading_end,
            "qc_time" => $this->qcTime,
            "qc_start" => $this->qc_start,
            "qc_end" => $this->qc_end,
            "travel_time" => $order->travel_to_site,
            "travel_start" => $this->travel_start,
            "travel_end" => $this->travel_end,
            "insp_time" => $this->inspTime,
            "insp_start" => $this->insp_start,
            "insp_end" => $this->insp_end,
            "pouring_time" => $order->pouring_time,
            "pouring_start" => $this->pouring_start,
            "pouring_end" => $this->pouring_end,
            "cleaning_time" => $this->cleaningTime,
            "cleaning_start" => $this->cleaning_start,
            "cleaning_end" => $this->cleaning_end,
            "return_time" => $order->return_to_plant,
            "return_start" => $this->return_start,
            "return_end" => $this->return_end,
            "delivery_start" => $this->loading_start,
            "deviation" => abs(Carbon::parse($order->delivery_date)->diffInMinutes($this->pouring_start, false)),
        ];
    }

    private function createPumpScheduleEntry($order, ScheduleData $scheduleData, $location, $trip)
    {
        return [
            
            'pump' => $this->pouringPump['pump']['pump_name'],
            'batching_qty' => $this->batchingQty,
            "order_id" => $order->id,
            "group_company_id" => $scheduleData->company,
            "user_id" => $scheduleData->user_id,
            "schedule_date" => $scheduleData->schedule_date,
            "order_no" => $order->order_no,
            "location" => $location,
            "trip" => $trip,
            "mix_code" => $order->mix_code,
            "qc_time" => $this->qcTime,
            "qc_start" => $this->qc_start,
            "qc_end" => $this->qc_end,
            "travel_time" => $order->travel_to_site,
            "travel_start" => $this->travel_start,
            "travel_end" => $this->travel_end,
            "insp_time" => $this->inspTime,
            "insp_start" => $this->insp_start,
            "insp_end" => $this->insp_end,
            "pouring_time" => $order->pouring_time,
            "pouring_start" => $this->pouring_start,
            "pouring_end" => $this->pouring_end,
            "cleaning_time" => $this->cleaningTime,
            "cleaning_start" => $this->cleaning_start,
            "cleaning_end" => $this->cleaning_end,
            "return_time" => $order->return_to_plant,
            "return_start" => $this->return_start,
            "return_end" => $this->return_end,
            "delivery_start" => $this->loading_start,
        ];

    }

    private function updateResourceAvailability(ScheduleData $scheduleData, $location)
    {
        // Update batching plant, transit mixer, and pump availability
    }

    private function filterScheduledOrders($ordersCopy)
    {
        return array_filter($ordersCopy, function ($order) {
            return isset($order['is_scheduled']) && $order['is_scheduled'] === true;
        });
    }

    public  function generateScheduleOld(int $user_id, string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty, string $schedule_preference, string $shift_start, string $shift_end, $restriction_start, $restriction_end, string $min_order_start_time, int $interval_deviation, bool $generateLog, bool $execute = false)
    {

        try
        {

            $qc_time = GlobalSetting::where('group_company_id', $company)->value('batching_quality_inspection') ?? ConstantHelper::QC_TIME;

            $insp_time = GlobalSetting::where('group_company_id', $company)->value('site_quality_inspection') ?? ConstantHelper::INSP_TIME;

            $cleaning_time = GlobalSetting::where('group_company_id', $company)->value('chute_cleaning_site') ?? ConstantHelper::CLEANING_TIME;

            $batching_qty = 0;
            //Order and Pump Schedules
            $selected_order_pump_schedules = [];
            $schedules = [];
            //Availabilities data
            $transit_mixer_availability = $tms_availabilty;
            $pump_availability = $pumps_availabilty;
            $batching_plant_availability = $bps_availabilty;
            //Copies for rollback
            $transit_mixer_availability_copy = $tms_availabilty;
            $pump_availability_copy = $pumps_availabilty;
            $batching_plant_availability_copy = $bps_availabilty;
            //Shift timings calculation
            $location_start_time = $shift_start;
            $location_end_time = $shift_end;
            //Restrictions
            $restriction_start_parsed = $restriction_start;
            $restriction_end_parsed = $restriction_end;
            if (isset($restriction_start) && isset($restriction_end))
            {
                $restriction_start_parsed = Carbon::parse($restriction_start);
                $restriction_end_parsed = Carbon::parse($restriction_end);
            }
            //Orders
            $orders = SelectedOrder::select("group_company_id", "id", 'og_order_id', "order_no", "customer", "project", "site", "location", "mix_code", "quantity", "delivery_date", "interval", "interval_deviation", "pump", "pouring_time", "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)->where("user_id", $user_id)->whereBetween("delivery_date", [$location_start_time, $location_end_time])->whereNull("start_time")
                ->where("selected", true)
                ->orderBy('quantity','DESC')
                ->orderBy('priority','ASC')
                ->get();
// dd($orders);
            $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));
            $min_truck_cap = min($truck_capacities);
            $locations = array_unique(array_column($batching_plant_availability, 'location'));

            //Initialize variables, Resources
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $pouring_pump_index = null;
            $transit_mixer_index = null;
            $batching_plant_index = null;
            $pump_ids = [];

            $loading_time = 0;
            $pouring_end_prev = "";
            $pouring_start_prev = "";
            $trip_reset_time = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = "";
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

            $first_trip_qc_start = "";
            $qc_start = "";
            $qc_end = "";

            $travel_start = "";
            $travel_end = "";

            $insp_start = "";
            $insp_end = "";

            $pouring_start = "";
            $pouring_end = "";

            $cleaning_start = "";
            $cleaning_end = "";

            $return_start = "";
            $return_end = "";
            $return_time = "";

            $pump_start_time = "";
            $pump_end_time = "";

            $deviation = 0;
            $location = "";
            $sch_adj_time = 0;

            $bpScheduleGap = [];

            $shift_end_exit = 0;

            $orders_copy = $orders->toArray();

            foreach ($orders as $orderKey => $order)
            { // Order loop start
                // dd($order);
                Log::info('start ----------------------------------------------------------- ');
                // Log::info('this is orderLoop1: ' . $order->order_no);
                // dd('an');
                $pouring_time = $order->interval;
                // Assign large values between interval and pouring time
                if ($order->pouring_time > $order->interval)
                {
                    $pouring_time = $order->pouring_time;
                }
                //Divide pouring time acc to pump qty
                $pouring_interval = 0;
                if ($order->pump_qty > 1)
                {
                    $pouring_interval = round(($pouring_time / $order->pump_qty) , 0);
                }

                //Reset variables
                $pouring_pump = null;
                $transit_mixer = null;
                $batching_plant = null;

                $pouring_pump_index = null;
                $transit_mixer_index = null;
                $batching_plant_index = null;

                $pump_ids = [];

                $loading_time = 0;
                $pouring_end_prev = "";
                $pouring_start_prev = "";
                $trip_reset_time = "";

                $delivery_date = $order->delivery_date;

                $deviation = 0;
                $sch_adj_time = 0;

                //Get Locations availability
                $index = array_search($order->location, $locations);
                if ($index !== false && $index > 0)
                {
                    unset($locations[$index]);
                    array_unshift($locations, $order->location);
                }
                //Locations Loop

                foreach ($locations as $loc)
                {
                    // Log::info('this is locationLoop: ' . $order->order_no . "=>loc:" . $loc);
                    $location = $loc;
                    //Check for first available plant time
                    $plant_availability = BatchingPlantHelper::getMinAvailTimeCopy($batching_plant_availability_copy, ConstantHelper::LOADING_TIME, $batching_plant, $batching_plant_index, $restriction_start_parsed, $restriction_end_parsed);

                    //Start the delivery acc to avl time
                    // $delivery_time = $schedule_preference !== ConstantHelper::CUSTOMER_TIMELINE_PREF ?
                    //     (isset($order->priority) && $order->priority < ConstantHelper::DEFAULT_PRIORITY ? $order->delivery_date : $plant_availability)
                    //     : $delivery_date;
                    $delivery_time = $delivery_date;

                    $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()
                        ->addMinutes($sch_adj_time);

                    $delivery_date_p = Carbon::parse($delivery_time)->copy()
                        ->subMinutes($sch_adj_time);

                    $avl = 0;
                    $avlCounter = 0;
                    $restriction_flag = Carbon::parse(ConstantHelper::DEFAULT_DATE_TIME);
                    //Schedule adjustment based on availability LOOP
                    while ($avl == 0 && $avlCounter <= 215)
                    {
                        $avlCounter++;
                        // echo "AAAAAAA".$avlCounter;
                        // Log::info('avl while: order-' . $order->order_no . 'avl' . $avl);
                        //Forward backward adjustment loop
                        foreach (ConstantHelper::TO_FROM_LOOP as $val)
                        {
                            Log::info('tofromLOOP: order-' . $order->order_no . 'val' . $val);
                            if (isset($restriction_start_parsed) && isset($restriction_end_parsed))
                            {
                                if ($val == 1)
                                {
                                    if (Carbon::parse($delivery_date_n)->between($restriction_start_parsed, $restriction_end_parsed))
                                    {
                                        continue;
                                    }
                                }
                                else
                                {
                                    if (Carbon::parse($delivery_date_p)->between($restriction_start_parsed, $restriction_end_parsed))
                                    {
                                        break;
                                    }
                                }
                            }
                            //Reset resources
                            $transit_mixer = null;
                            $pouring_pump = null;

                            $pouring_pump_index = null;
                            $transit_mixer_index = null;

                            if ($sch_adj_from != 0)
                            {
                                $batching_plant = null;
                                $batching_plant_index = null;
                            }

                            $pump_ids = [];
                            $qty = $order->quantity;
                            $trip = 1;
                            if ($execute)
                            {
                                DB::beginTransaction();
                            }
                            $tl = 0;
                            $qtyCounter = 0;
                            //Trips Loop
                            while ($qty > 0 && $qtyCounter <= 210)
                            {
                                $tl++;
                                // dd('a');
                                $qtyCounter++;
                                // echo "qqqqq:" .$qtyCounter;
                                Log::info('QTY while: order-' . $order->order_no . '-qty-' . $qty);
                                //Truck Loop
                                $tc = 0;
                                // dd($truck_capacities);
                                foreach ($truck_capacities as $truck_capacity)
                                {
                                    // dd('aaa');
                                    $transit_mixer = null;
                                    $transit_mixer_index = null;

                                    // $loading_time = ConstantHelper::LOADING_TIME;
                                    $newCurrentOrder = SelectedOrder::find($order->id); //Need to optimize
                                    if (isset($newCurrentOrder))
                                    {
                                        $newLoadingTime = $newCurrentOrder->customer_product ?->product ?->product_type ?->batching_creation_time ?? ConstantHelper::LOADING_TIME;
                                        // dd($newLoadingTime);
                                    }
                                    $loading_time = isset($newLoadingTime) ? $newLoadingTime : ConstantHelper::LOADING_TIME;
                                    //First Trip
                                    if ($trip == 1)
                                    {
                                        if ($sch_adj_time == 0)
                                        {
                                            $delivery_date = $delivery_time;
                                        }
                                        else
                                        {
                                            if ($val == 1)
                                            {
                                                $delivery_date = $delivery_date_n;
                                            }
                                            else
                                            {
                                                $delivery_date = $delivery_date_p;
                                            }
                                        }
                                        Log::info('TRIP 1 D_date :'.$delivery_date.'--val:'.$val);

                                        //Subsequent Trips
                                        
                                    }
                                    else
                                    {
                                        // $delivery_dateA = $pouring_interval > 0 ? $pouring_start_prev->copy()
                                        //     ->addMinutes($pouring_interval) : $pouring_end_prev->copy()
                                        //     ->subMinutes($pouring_interval)->addMinute();

                                        if($val == 1) {
                                            
                                            $delivery_date =  $pouring_interval > 0 ?  $pouring_start_prev->copy()
                                                ->addMinutes($pouring_interval) :   $pouring_end_prev->copy()
                                                ->addMinutes();
                                        } else {
                                            $delivery_date = $pouring_interval > 0 ? $pouring_start_prev->copy()
                                                ->subMinutes($pouring_interval)->addMinute() : $pouring_end_prev->copy()
                                                ->subMinutes();
                                        }

                                        Log::info('TRIP '.$trip.' D_date :'.$delivery_date.'--val:'.$val.'-PI-'.$pouring_interval.'-dda-'.$pouring_end_prev);
                                        
                                    }
                                    $delivery_date = Carbon::parse($delivery_date);

                                    //Restriction check
                                    if (isset($restriction_start_parsed) && isset($restriction_end_parsed) && ($schedule_preference == ConstantHelper::CUSTOMER_TIMELINE_PREF || $schedule_preference == ConstantHelper::LARGEST_JOB_FIRST_PREF))
                                    {
                                        if ($delivery_date->gte($restriction_start_parsed) && $delivery_date->lte($restriction_end_parsed))
                                        {
                                            if ($restriction_flag->notEqualTo($restriction_start_parsed))
                                            {
                                                $delivery_time = $restriction_end_parsed->copy()
                                                    ->addMinute();
                                                $sch_adj_time = - 1;
                                                $avl = 0;
                                                $restriction_flag = $restriction_start_parsed;
                                                // Log::info('truck: restriction flag-' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                                break;
                                            }
                                        }
                                    }

                                    //Time calculation for activities
                                    $travel_time = $order->travel_to_site;
                                    $total_time = ((int)$loading_time) + $qc_time + ((int)$travel_time) + $insp_time;
                                    // dd($loading_time,$qc_time,$travel_time,$insp_time);
                                    $loading_start = $delivery_date->copy()
                                        ->subMinutes($total_time);
                                    $loading_end = $loading_start->copy()
                                        ->addMinutes($loading_time)->subMinute();

                                    $qc_start = $loading_end->copy()
                                        ->addMinute();
                                    $qc_end = $qc_start->copy()
                                        ->addMinutes($qc_time)->subMinute();
                                        // dd($order);
// dd([$total_time,$loading_start,$loading_end ,$qc_start,$qc_end]);

                                    if ($trip == 1)
                                    {
                                        $first_trip_qc_start = $qc_start;
                                    }

                                    $travel_start = $qc_end->copy()
                                        ->addMinute();
                                    $travel_end = $travel_start->copy()
                                        ->addMinutes($travel_time)->subMinute();
                                    $insp_start = $travel_end->copy()
                                        ->addMinute();
                                    $insp_end = $insp_start->copy()
                                        ->addMinutes($insp_time)->subMinute();


                                    $pouring_start = $insp_end->copy()
                                        ->addMinute();
                                    $pouring_end = $pouring_start->copy()
                                        ->addMinutes($pouring_time)->subMinute();
                                    $cleaning_start = $pouring_end->copy()
                                        ->addMinute();
                                    $cleaning_end = $cleaning_start->copy()
                                        ->addMinutes($cleaning_time)->subMinute();


                                    $return_time = $order->return_to_plant;
                                    $return_start = $cleaning_end->copy()
                                        ->addMinute();
                                    $return_end = $return_start->copy()
                                        ->addMinutes($return_time)->subMinute();

                                    $deviation = ($pouring_start->copy())
                                        ->diffInMinutes($order->delivery_date);

                                    $shift_end_exit = 0;
                                    if (Carbon::parse($loading_start)->gt(Carbon::Parse($location_end_time)) && $trip > 1)
                                    {
                                        $shift_end_exit = 1;
                                        // Log::info('truck: shift end exit-' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                        break;
                                    }
                                    // dd($loading_start);
                                    if($avlCounter > 100){

                                        // dd($avlCounter,$batching_plant_availability_copy);
                                    }
                                    // Log::info("calling bps available helper here :::::::::::");
                                    $plant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $location_end_time, $batching_plant);
                                    // dd($plant);
                                    if (isset($plant))
                                    {
                                        // echo 'batch tl'.$tl;
                                        // dd('plant avl:',$plant );
                                        $batching_plant = $plant['data'];
                                        $batching_plant_index = $plant['index'];
                                        Log::info('truck: batching_plant FOUND-' . $order->order_no . '-qty-' . $qty.'avlCtr:'.$avlCounter.'loading_start:'.$loading_start.'loading_end:'.$loading_end);
                                        // Log::info('BATCHING AVL:'.json_encode($batching_plant_availability_copy));
                                    }
                                    else
                                    {
                                        $batching_plant = $plant;
                                        Log::info('truck: batching_plant NOT FOUND-' . $order->order_no . '-qty-' . $qty .'avlCtr:'.$avlCounter.'loading_start:'.$loading_start.'loading_end:'.$loading_end);
                                        // Log::info('BATCHING NOT AVL:'.json_encode($batching_plant_availability_copy));

                                        // dd($batching_plant_availability_copy,$loading_start,$loading_end);
                                        // Log::info('truck: batching_plant else- break' . $order->order_no . '-qty-' . $qty . '--tc--' . $truck_capacity.'->tl--'.$tl++.'tc>>'.$tc++);
                                        break;
                                    }

                                    // if (!isset($batching_plant)) {
                                    //     $bplant = null;
                                    //     $bplant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_start, $restriction_start, $location_end_time, $restriction_end);
                                    //     if (!isset($bplant)) {
                                    //         break;
                                    //     }
                                    // } else {
                                    //     $bplant = ['data' => $batching_plant, 'index' => $batching_plant_index];
                                    // }
                                    if (isset($order->pump))
                                    {
                                        Log::info(json_encode('if pump required: order-' . $order->order_no));
                                        $release_current_pump = false;
                                        $current_remaining_qty = $qty - $truck_capacity;
                                        $reamining_pump_trips = ceil($current_remaining_qty / $min_truck_cap);
                                        $reamining_pump_trips = $reamining_pump_trips / $order->pump_qty;
                                        if ($reamining_pump_trips < 1)
                                        {
                                            $release_current_pump = true;
                                        }
                                        $lastTripAll = $qty - min([$qty, $truck_capacity]) <= 0;
                                        // Get Pump Start and End Time
                                        $lastTrip = $qty - min([$qty, $truck_capacity]) <= 0;
                                        $pumpTrip = $trip;
                                        // if ($trip > 1) {
                                        //     $temp_pump_ids = $pump_ids;
                                        //     if (count($temp_pump_ids) < $order->pump_qty) {
                                        //         $pumpTrip = 1;
                                        //     }
                                        // }
                                        // dd($pump_availability_copy);
                                        $pump_timings = PumpHelper::getPumpStartAndEndTime($qc_start, $pouring_end, $pouring_start, $return_end, $cleaning_end, $release_current_pump, $pumpTrip);
                                        $pump_start_time = $pump_timings['pump_start'];
                                        $pump_end_time = $pump_timings['pump_end'];

                                        $pump = PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, $trip, $selected_order_pump_schedules, $location_end_time, $order->pump_qty, $location);
                                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, $trip, $selected_order_pump_schedules, $location_end_time, $order->pump_qty);

                                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pump_availability_copy, $order->id, $company, $pump_start_time, $pump_end_time, $order->pump, null, $selected_order_pump_schedules, $location_end_time, $order->pump_qty);

                                        // dd($pump);
                                        if (isset($pump))
                                        {
                                            // echo 'pump tl'.$tl;
                                            Log::info(json_encode('pump Avl if: order-' . $order->order_no . '-pump-' . $pump['pump']['pump_name']));
                                            $pouring_pump = $pump['pump'];
                                            $pouring_pump_index = $pump['index'];
                                        }
                                        else
                                        {
                                            Log::info(json_encode('pump Avl else: order-' . $order->order_no . '-pump-' . $pump));
                                            $pouring_pump = $pump;
                                            // dd($pump_availability_copy);
                                            break ;
                                        }

                                    }

                                    //Assign current Truck capacity and check AVL
                                    $truck_cap = (int)$truck_capacity;

                                    $truck = TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location);
                                    $truck = isset($truck) ? $truck : TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end);
                                    if (isset($truck))
                                    {
                                        // echo 'truck tl'.$tl;
                                        Log::info('truck avl');
                                        $transit_mixer = $truck['data'];
                                        $transit_mixer_index = $truck['index'];
                                    }
                                    else
                                    {
                                        Log::info('truck NOT avl');

                                        $transit_mixer = $truck;
                                    }
                                    if (!isset($transit_mixer))
                                    {
                                        continue;
                                    }
                                    //All resources assigned
                                    if (((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant))
                                    {
                                        // dd('inside');
                                        //Assign pouring end starting next trip
                                        Log::info('All resources Found order-' . $order->order_no);
                                        // dd('all');
                                        $actual_interval_deviation = isset($order->interval_deviation) ? $order->interval_deviation : $interval_deviation;
                                        $max_deviation = round($pouring_time * $actual_interval_deviation / 100, 0);

                                        // $pouring_end_prev = $pouring_end->copy()->addMinutes($pouring_time);
                                        $pouring_end_prev = $pouring_end->copy()
                                            ->addMinutes($max_deviation);
                                        // $pouring_end_prev = $pouring_end -> copy() -> addMinutes(1);
                                        // $pouring_end_prev = $pouring_end;
                                        $pouring_start_prev = $pouring_start;
                                        // $trip_reset_time = $pouring_end;
                                        // $trip_reset_time = $loading_start -> copy() -> addMinutes($total_time + $loading_time);
                                        $trip_reset_time = $loading_start->copy()
                                            ->addMinutes($total_time + $pouring_interval > 0 ? 0 : $loading_time);
                                        //Update Pump AVL
                                        if (isset($order->pump))
                                        {
                                            $pump_availability_copy[$pouring_pump_index]['free_upto'] = Carbon::parse($pump_start_time)->copy()
                                                ->subMinute();
					    $pump_availability_copy[$pouring_pump_index]['location'] = $location;
					    if($pump_availability_copy[$pouring_pump_index]['free_upto']  <= $pump_availability_copy[$pouring_pump_index]['free_from'] ) {
                                                unset($pump_availability_copy[$pouring_pump_index]);
                                            }
                                            $pump_availability_copy[] = array(
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => Carbon::parse($pump_end_time)->copy()
                                                    ->addMinute() ,
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location,
                                                'order_id' => $release_current_pump ? null : $order->id . '-' . (($trip) + $order->pump_qty) ,
                                                'order_id_wo_trip' => $release_current_pump ? null : $order->id
                                            );
                                            if ($lastTripAll)
                                            {
                                                foreach ($pump_availability_copy as & $pAvl)
                                                {
                                                    if (Carbon::parse($pAvl['free_upto'])->gte(Carbon::parse($pAvl['free_from'])))
                                                    {
                                                        $innerOrder = explode('-', $pAvl['order_id']);
                                                        if (count($innerOrder) > 0)
                                                        {
                                                            $order_id = $innerOrder[0];
                                                            if ($order_id == $order->id)
                                                            {
                                                                $pAvl['order_id'] = null;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        //Update Transit Mixer AVL
                                        $transit_mixer_availability_copy[$transit_mixer_index]['free_upto'] = $loading_start->copy()
                                            ->subMinute();
					$transit_mixer_availability_copy[$transit_mixer_index]['location'] = $location;
					 if($transit_mixer_availability_copy[$transit_mixer_index]['free_upto'] <= $transit_mixer_availability_copy[$transit_mixer_index]['free_from']) {
                                            unset($transit_mixer_availability_copy[$transit_mixer_index]);
                                        }
                                        $transit_mixer_availability_copy[] = array(
                                            'truck_name' => $transit_mixer['truck_name'],
                                            'truck_capacity' => $truck_capacity,
                                            'loading_time' => $loading_time,
                                            'free_from' => $return_end->copy()
                                                ->addMinute() ,
                                            'free_upto' => $transit_mixer['free_upto'],
                                            'location' => $location,
                                        );
                                        //Update Batching Plant AVL
                                        Log::info('updating batching plant availabilities');


                                        $batching_plant_availability_copy[$batching_plant_index]['free_upto'] = $loading_start->copy()
                                            ->subMinute();

                                        if($batching_plant_availability_copy[$batching_plant_index]['free_upto'] <= $batching_plant_availability_copy[$batching_plant_index]['free_from']) {
                                            unset($batching_plant_availability_copy[$batching_plant_index]);
                                        }

                                        $batching_plant_availability_copy[] = array(
                                            'location' => $location,
                                            'plant_name' => $batching_plant['plant_name'],
                                            'plant_capacity' => $batching_plant['plant_capacity'],
                                            'free_from' => $loading_end->copy()
                                                ->addMinute() ,
                                            'free_upto' => $batching_plant['free_upto'],
                                        );

                                        $batching_qty = min([$truck_capacity, $qty]);
                                        // dd($batching_qty);



                                        break ;
                                    }

                                } //End Truck Loop
                                // dd('loop truck end');
                                // dd('aa');

                                //Trip adjustiment
                                if (((!isset($transit_mixer)) || (!isset($batching_plant)) || (!isset($pouring_pump) && isset($order->pump))) && $shift_end_exit === 0)
                                {
                                    Log::info('trip adjustment condition if: order-' . $order->order_no);
                                    // dd('trip adjustment');
                                    if (isset($batching_plant) && $generateLog)
                                    {
                                        $reason = ConstantHelper::TRIP_GAP;
                                        if (!isset($transit_mixer) && (!isset($pouring_pump) && isset($order->pump)))
                                        {
                                            $reason = ConstantHelper::TM_AND_PUMP_NOT_AVL;
                                        }
                                        else if (!isset($transit_mixer))
                                        {
                                            $reason = ConstantHelper::TM_NOT_AVL;
                                        }
                                        else if ((!isset($pouring_pump) && isset($order->pump)))
                                        {
                                            $reason = ConstantHelper::PUMP_NOT_AVL;
                                        }
                                        $b_flag = false;
                                        foreach ($bpScheduleGap as $gap)
                                        {
                                            // Log::info('bpScheduleGap: order-' . $order->order_no . '--gap--' . $gap['free_from'] . 'loadingStart' . $loading_start);
                                            if (Carbon::Parse($loading_start)->eq(Carbon::parse($gap['free_from'])))
                                            {
                                                $b_flag = true;
                                                break;
                                            }
                                        }
                                        if ($b_flag == false && $reason !== ConstantHelper::TRIP_GAP)
                                        {

                                            // // //         if ($execute)
                                            // // //         {
                                            // // //             $bpScheduleGap[] = array(
                                            // // //             'group_company_id' => $company,
                                            // // //             'location' => $location,
                                            // // //             'plant_name' => $batching_plant['plant_name'],
                                            // // //             'plant_capacity' => $batching_plant['plant_capacity'],
                                            // // //             'free_from' => $loading_start,
                                            // // //             'free_upto' => $loading_start,
                                            // // //             'user_id' => $user_id,
                                            // // //             'reason' => $reason
                                            // // //         );
                                            // // //         }
                                            // // //         else
                                            // // //         {
                                            BatchingPlantAvailability::create(['group_company_id' => $company, 'location' => $location, 'plant_name' => $batching_plant['plant_name'], 'plant_capacity' => 0, 'free_from' => $loading_start, 'free_upto' => $loading_start, 'user_id' => $user_id, 'reason' => $reason]);
                                            // // //         }
                                            
                                        }
                                    }

                                    if ($trip > 1)
                                    {

                                        // Log::info('trip condtion gr 1: order-' . $order->order_no);

                                        // if ((Carbon::parse($pouring_end_prev) -> diffInMinutes(Carbon::parse($trip_reset_time))) > $pouring_time )
                                        // if ((Carbon::parse($pouring_end_prev)->diffInMinutes(Carbon::parse($trip_reset_time))) <= 0) {
                                        if (Carbon::parse($pouring_end_prev)->lt(Carbon::parse($trip_reset_time)))
                                        {
                                            // Log::info('trip condtion continue if: order-' . $order->order_no);
                                            break;
                                        }
                                        else
                                        {
                                            // Log::info('trip condtion continue else: order-' . $order->order_no);
                                            // $pouring_end_prev = Carbon::parse($pouring_end_prev) -> copy() -> addMinute();
                                            $pouring_end_prev = Carbon::parse($pouring_end_prev)->copy()
                                                ->subMinute();
                                            $pouring_start_prev = Carbon::parse($pouring_start_prev)->copy()
                                                ->addMinute();
                                            continue;
                                        }
                                    }
                                    else
                                    {
                                        // Log::info('trip condtion else: order-' . $order->order_no);
                                        break;
                                    }
                                }
                                else
                                { //Trip fulfilled
                                    // dd('trip full');
                                    if ($trip == 1)
                                    {


                                        if ($shift_end_exit === 1)
                                        {
                                            // dd($shift_end_exit);
                                            break;
                                        }

                                        $schedules[] = array(
                                            "order_id" => $order->id,
                                            'group_company_id' => $company,
                                            'user_id' => $user_id,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                                            'location' => $location,
                                            'trip' => $trip,
                                            'mix_code' => $order->mix_code,
                                            'batching_plant' => $batching_plant['plant_name'],
                                            'transit_mixer' => $transit_mixer['truck_name'],
                                            'batching_qty' => $batching_qty,
                                            'loading_time' => $loading_time,
                                            'loading_start' => $loading_start,
                                            'loading_end' => $loading_end,
                                            'qc_time' => $qc_time,
                                            'qc_start' => $qc_start,
                                            'qc_end' => $qc_end,
                                            'travel_time' => $travel_time,
                                            'travel_start' => $travel_start,
                                            'travel_end' => $travel_end,
                                            'insp_time' => $insp_time,
                                            'insp_start' => $insp_start,
                                            'insp_end' => $insp_end,
                                            'pouring_time' => $pouring_time,
                                            'pouring_start' => $pouring_start,
                                            'pouring_end' => $pouring_end,
                                            'cleaning_time' => $cleaning_time,
                                            'cleaning_start' => $cleaning_start,
                                            'cleaning_end' => $cleaning_end,
                                            'return_time' => $return_time,
                                            'return_start' => $return_start,
                                            'return_end' => $return_end,
                                            'delivery_start' => $delivery_date,
                                            'deviation' => $deviation
                                        );
                                        // dd($schedules);
                                        if (isset($order->pump))
                                        {

                                            $pump_update = CommonHelper::searchAndUpdateArray($selected_order_pump_schedules, ['group_company_id' => $company, 'schedule_date' => $schedule_date, 'order_no' => $order->order_no, 'pump' => $pouring_pump['pump_name'], 'location' => $location], ['pouring_time' => ['value' => $pouring_end], 'pouring_end' => $pouring_end, 'cleaning_start' => $cleaning_start, 'cleaning_end' => $cleaning_end,
                                            // 'return_time' => $return_time,
                                            // 'return_start' => $return_start,
                                            // 'return_end' => $return_end
                                            ]);
                                            if ($pump_update['match'] === false)
                                            {
                                                $selected_order_pump_schedules[] = array(
                                                    'order_id' => $order->id,
                                                    'user_id' => $user_id,
                                                    'group_company_id' => $company,
                                                    'schedule_date' => $schedule_date,
                                                    'order_no' => $order->order_no,
                                                    'pump' => $pouring_pump['pump_name'],
                                                    'location' => $location,
                                                    'trip' => $trip,
                                                    'mix_code' => $order->mix_code,
                                                    'batching_qty' => $batching_qty,
                                                    'qc_time' => $qc_time,
                                                    'qc_start' => $qc_start,
                                                    'qc_end' => $qc_end,
                                                    'travel_time' => $travel_time,
                                                    'travel_start' => $travel_start,
                                                    'travel_end' => $travel_end,
                                                    'insp_time' => $insp_time,
                                                    'insp_start' => $insp_start,
                                                    'insp_end' => $insp_end,
                                                    'pouring_time' => $pouring_time,
                                                    'pouring_start' => $pouring_start,
                                                    'pouring_end' => $pouring_end,
                                                    'cleaning_time' => $cleaning_time,
                                                    'cleaning_start' => $cleaning_start,
                                                    'cleaning_end' => $cleaning_end,
                                                    'return_time' => 0,
                                                    'return_start' => null,
                                                    'return_end' => null,
                                                    'delivery_start' => $pouring_start
                                                );
                                            }
                                            else
                                            {
                                                $selected_order_pump_schedules = $pump_update['data'];
                                            }
                                            if (!in_array($pouring_pump['pump_name'], $pump_ids))
                                            {
                                                $pump_ids[] = $pouring_pump['pump_name'];
                                            }

                                        }

                                    }
                                    // echo "t:".$trip;
                                    // if($trip == 3){

                                    // // dd($selected_order_pump_schedules);
                                    // }

                                    //Next trip
                                    $qty = $qty - $batching_qty;

                                    $trip += 1;
                                    // Log::info('after schedule: order-' . $order->order_no . 'qty' . $qty . 'trip' . $trip . 'batching_qty' . $batching_qty);

                                }
                            } //End Loop Trips
                            // dd($schedules);
                            // if($selected_order_pump_schedules){
                            //     dd($selected_order_pump_schedules);
                            // }
                            $pump_ids = [];
                            //All resources fulfilled

                            if ((((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant)) || $shift_end_exit === 1)
                            // if ((count($schedules) && $order->pump && count($selected_order_pump_schedules)) || (!$order->pump  && count($schedules)))
                            {

                                Log::info('abcd order-' . $order->order_no);
                                // dd('all set');
                                if ($execute)
                                {
                                    // Log::info('abcd executed order-' . $order->order_no);
                                    DB::table("selected_order_schedules")
                                        ->insert($schedules);
                                    DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
                                    $update_order = DB::table('selected_orders as A')->where('id', $order->id)
                                        ->update(['start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)->where('user_id', $user_id)->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)->where('user_id', $user_id)->where('order_no', $order->order_no)
                                        ->first()->max_pour, 'location' => $location, ]);
                                    $order_deviation = DB::table("selected_orders")->where("id", $order->id)
                                        ->first();
                                    $order_deviation = Carbon::parse($order_deviation->delivery_date)
                                        ->copy()
                                        ->diffInMinutes(Carbon::parse($order_deviation->start_time) , false);
                                    DB::table("selected_orders")
                                        ->where("id", $order->id)
                                        ->update(['deviation' => $order_deviation]);
                                }
                                $current_order_deviation = 0;
                                $current_order_max_deviation = 0;
                                //Reset schedules
                                    $order_start_time = $loading_start;
                                if ($schedules && count($schedules) > 0)
                                {
                                    // Log::info('abcd scedule count-' . $order->order_no);
                                    $order_start_time = $schedules[0]['loading_start'];
                                    $current_order_deviation = abs(Carbon::parse($order->delivery_date)
                                        ->copy()
                                        ->diffInMinutes(Carbon::parse($schedules[0]['pouring_start']) , false));
                                    $current_order_max_deviation = self::getOrderMaxPossibleDeviation($current_order_deviation);
                                }
                                $orders_copy[$orderKey]['next_loading'] = $order_start_time;
                                $orders_copy[$orderKey]['order_start_time'] = $order_start_time;
                                $orders_copy[$orderKey]['is_scheduled'] = true;
                                $orders_copy[$orderKey]['current_deviation'] = $current_order_deviation;
                                $orders_copy[$orderKey]['current_max_deviation'] = $current_order_max_deviation;
                                $schedules = [];
                                $selected_order_pump_schedules = [];
                                //Update Deviation and AVL
                                $transit_mixer_availability = $transit_mixer_availability_copy;
                                $pump_availability = $pump_availability_copy;
                                $batching_plant_availability = $batching_plant_availability_copy;

                                //COMMIT AND BREAK
                                if ($execute)
                                {
                                    DB::commit();
                                }
                                $avl = 1;
                                break;
                            }
                            else
                            { // All resources not fulfilled (ROLLBACK/ RESET)
                                // Log::info('abcd all resources not fullfilled-' . $order->order_no);
                                $schedules = [];
                                $selected_order_pump_schedules = [];
                                $transit_mixer_availability_copy = $transit_mixer_availability;
                                $pump_availability_copy = $pump_availability;
                                $batching_plant_availability_copy = $batching_plant_availability;
                                $pump_ids = [];

                                if ($execute)
                                {
                                    DB::rollBack();
                                }
                                if ($sch_adj_time <= 0)
                                {
                                    $avl = 0;
                                    break;
                                }
                            }
                        } //End Forward backward adjustment loop
                        //Order adjustment
                        $sch_adj_time += 1;
                        $delivery_date_n = Carbon::parse($delivery_time)->copy()
                            ->addMinutes($sch_adj_time);
                        $delivery_date_p = Carbon::parse($delivery_time)->copy()
                            ->subMinutes($sch_adj_time);
                            Log::info('DT: '.$delivery_time.'SCHADJ: '.$sch_adj_time.'DDN: '.$delivery_date_n.'DDP'.$delivery_date_p);

                        //Shift crossed or day crossed
                        if ($delivery_date_p->copy()
                            ->lt(Carbon::parse($delivery_time)->copy()
                            ->subMinutes($sch_adj_to)) && $delivery_date_n->copy()
                            ->gt(Carbon::parse($delivery_time)->copy()
                            ->addMinutes($sch_adj_to)))
                        {
                            $avl = 1;
                            // Log::info('abcd avl update 1-' . $order->order_no . '--avl--' . $avl);
                        }
                        if ($delivery_date_p->copy()
                            ->lt($location_start_time) && $delivery_date_n->copy()
                            ->gt($location_end_time))
                        {
                            // Log::info('abcd avl update 2-' . $order->order_no);
                            if ((($delivery_date_p->copy()
                                ->subMinutes($total_time))->lt($location_start_time)) && (($delivery_date_n->copy()
                                ->subMinutes($total_time))->gt($location_end_time)))
                            {

                                $avl = 1;
                                // Log::info('abcd avl update 2-' . $order->order_no . '--avl--' . $avl);

                            }
                        }
                        if ($avl == 1)
                        {
                            // Log::info('abcd break update 1-' . $order->order_no . '--avl--' . $avl);
                            break;
                        }
                    } //Schedule adjustment based on availability LOOP END
                    if ((isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null))) || $shift_end_exit === 1)
                    {

                        // Log::info('abcd break update 2-' . $order->order_no);
                        break;
                    }
                } //Location loops end
                Log::info('end ----------------------------------------------------------- '.$avlCounter.'--'.$qtyCounter);
            } //Orders Loop End
            // foreach (array_chunk($bpScheduleGap, 5000) as $gap) {
            //     DB::table('batching_plant_availability')->insert($gap);
            // }
            $orders_copy = array_filter($orders_copy, function ($ord)
            {
                if (isset($ord['is_scheduled']) && $ord['is_scheduled'] == true)
                {
                    return true;
                }
                else
                {
                    return false;
                };
            });
            // dd($orders_copy);
            return $orders_copy;
        }
        catch(\Exception $ex)
        {
            dd($ex);
        }
    }


}