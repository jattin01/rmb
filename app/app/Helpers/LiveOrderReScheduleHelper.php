<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\BatchingPlant;
use App\Models\BatchingPlantAvailability;
use App\Models\GlobalSetting;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\LiveOrderPumpSchedule;
use App\Models\LiveOrderSchedule;
use App\Models\Order;
use App\Models\SelectedOrderSchedule;
use App\Models\SelectedOrder;
use App\Helpers\GroupCompanyHelper;
use App\Models\Pump;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/* 
Purpose :: 
    1.call intialize schedule with liveSchedule orders excluding current trip
    2.set the variables and update liveschedule expected times 
    3.no need of publish order and schedule cron ,use the same code in this helper 
    4.reflect expected time on graph data 
*/
class LiveOrderReScheduleHelper
{


    public static function initializeReSchedule($trip , string $status = null, $currentTime)
    {
        try{
            
            //find live order for current trip
            $liveOrder = LiveOrder::where('id',$trip->order_id)->first();
            $scheduleDate = date('Y-m-d', strtotime($liveOrder->delivery_date));

            $companyId = $liveOrder->group_company_id;
            $user = User::where('user_type','=','Admin')->where('status','=','Active')->first();
           
            if($status == ConstantHelper::BATCHING){
                //fetch remaining schedules for the day 
                $remainingLiveOrderShcedules = LiveOrderSchedule::where('id','!=',$trip->id)
                ->where('schedule_date',$scheduleDate)
                ->whereNull('actual_loading_start');

                $remainingLiveOrderPumpShcedules = LiveOrderPumpSchedule::where('schedule_date',$scheduleDate)
                ->whereNull('actual_travel_time');

                $modified_orders_array = LiveOrder::with("schedule", "pump_schedule")
                ->where('delivery_date','like',$scheduleDate.'%')
                ->withSum(['schedule' => function ($query) {
                        $query->whereNull('actual_loading_start'); 
                    }], 'batching_qty')
                ->get();

            }

            if($status == ConstantHelper::INTERNAL_QC){
                //fetch remaining schedules for the day 
                $remainingLiveOrderShcedules = LiveOrderSchedule::where('id','!=',$trip->id)
                ->whereNull('actual_qc_start');

                $remainingLiveOrderPumpShcedules = LiveOrderPumpSchedule::where('schedule_date',$scheduleDate)
                ->whereNull('actual_travel_time');

                $modified_orders_array = LiveOrder::with("schedule", "pump_schedule")
                ->withSum(['schedule' => function ($query) {
                        $query->whereNull('actual_qc_start'); 
                    }], 'batching_qty')
                ->get();
            }

            if($status == ConstantHelper::ON_SITE_TRAVEL){
                //fetch remaining schedules for the day 
                $remainingLiveOrderShcedules = LiveOrderSchedule::where('id','!=',$trip->id)
                ->whereNull('actual_travel_start');

                $remainingLiveOrderPumpShcedules = LiveOrderPumpSchedule::where('schedule_date',$scheduleDate)
                ->whereNull('actual_travel_time');

                $modified_orders_array = LiveOrder::where('id','>=',$trip->order_id)->with("schedule", "pump_schedule")
                ->withSum(['schedule' => function ($query) {
                        $query->whereNull('actual_loading_start'); // Add condition to filter schedules
                    }], 'batching_qty')
                ->get();
            }

            $remainingQty = $remainingLiveOrderShcedules->sum('batching_qty');
            $modified_orders = $remainingLiveOrderShcedules;
            $schedules = $remainingLiveOrderShcedules->get()->toArray();
            $schedulesP = $remainingLiveOrderPumpShcedules->get()->toArray();

            $scheduled_transit_mixers = $modified_orders->groupBy('transit_mixer')->pluck('transit_mixer')->toArray();
            $transit_mixer_ids = TransitMixer::whereIn('truck_name',$scheduled_transit_mixers)
                ->pluck('id')->toArray();

            $scheduled_batching_plants = $modified_orders->groupBy('batching_plant')->pluck('batching_plant')->toArray();
            $batching_plants_ids = BatchingPlant::whereIn('plant_name',$scheduled_batching_plants)
            ->pluck('id')->toArray();

            $scheduled_pumps = [];

            foreach ($modified_orders_array as $order) {
                
                $pumpScheduled = $order->pump_schedule->pluck('pump')->toArray();
                if(!empty($pumpScheduled)){
                    $scheduled_pumps = array_merge($scheduled_pumps, $pumpScheduled);
                }
            }
            
            $pump_ids = Pump::whereIn('pump_name',$scheduled_pumps)
                                            ->pluck('id')->toArray();
            // $scheduleDate = '2025-01-09';
            $company_shifts = GroupCompanyHelper::getShiftTime($companyId, $scheduleDate);
            $shift_start = $currentTime;
            $shift_end = $company_shifts['end_time'];
            $interval_deviation = 100;
            $modified_orders_array = $modified_orders_array->toArray();
            // dd($modified_orders_array);
            // $newArray = [];
            foreach ($modified_orders_array as $orderKey => &$order) {
                // Initialize variables

                $order_start_time = $currentTime;
                $current_order_deviation = null;
                $current_order_max_deviation = null;
                $remainingBatchingQty = $order['schedule_sum_batching_qty'];
                

                if ($schedules && count($schedules) > 0) {
                    // $order_start_time = date('Y-m-d H:i:s');
                   
                    $current_order_deviation = abs(Carbon::parse($order['delivery_date'])->copy()->diffInMinutes(Carbon::parse($schedules[0]['planned_pouring_start']), false));
                    $current_order_max_deviation = self::getOrderMaxPossibleDeviation($current_order_deviation);
                }

                // Add new keys directly to the $order array
                $order['next_loading'] = $currentTime;
                $order['is_scheduled'] = true;
                $order['current_deviation'] = $current_order_deviation;
                $order['current_max_deviation'] = $current_order_max_deviation;
                // $order['location'] = $findSelectedOrders->location;
                $order['max_interval'] = $order['interval'];
                $order['order_start_time'] = $currentTime;
                $order['remaining_qty'] = $remainingBatchingQty;
                $order['batched_qty'] = $order['delivered_quantity'];
                $order['assigned_batching_plant'] = $order['schedule'][0]['batching_plant'];
                $order['pouring_time'] = $order['schedule'][0]['planned_pouring_time'];
                // $order["id"] = $order['id'];

            }

            $newArray = array(
                'sc' => array(),
                'sp' => array()
            );

            foreach($schedules as $s) {
                 if(!isset($newArray['sc'][$s['order_id']]))
                    $newArray['sc'][$s['order_id']] = array();

                $newArray['sc'][$s['order_id']][] = $s;
            }

            foreach($schedulesP as $p) {
                 if(!isset($newArray['sp'][$p['order_id']]))
                    $newArray['sp'][$p['order_id']] = array();

                $newArray['sp'][$p['order_id']][] = $p;
            }

// dd($newArray);
            // $removeLiveSchedules = $remainingLiveOrderShcedules->update(['deleted_at' => now()]);

            self::initializeSchedule($user->id, $companyId, $scheduleDate,$transit_mixer_ids, $pump_ids ?? [], $batching_plants_ids, $shift_start, $shift_end, $interval_deviation, $modified_orders_array, $remainingQty,$trip, $newArray, $status, $finalIteration = true);

            // CALL PUBLISH API 
            $request = request()->merge([
                'company_group_id' => $trip->group_company_id,
                'schedule_date' => $scheduleDate,
            ]);
           // dd('first method');
            // Call the OrderController's publishOrders method
           // app('App\Http\Controllers\OrderController')->publishOrders($request);

            //CALL SCHEDULE CRON 
           // app('App\Http\Controllers\API\LiveOrderController')->generateLiveSchedule($request);

        }
        Catch(\Exception $ex){
            dd($ex);
        }
            
    }
    
    public static function generateReScheduleNewVersion(int $user_id, string $company, string $schedule_date, array &$tms_availabilty, array &$pumps_availabilty, array $bps_availabilty, string $shift_start, string $shift_end, $restriction_start, $restriction_end, string $min_order_start_time, string $location, int $interval_deviation, array $modified_orders, $remainingQty, $trip, $newArray, $status, bool $finalIteration = false)
    {



        $qc_time = GlobalSetting::where('group_company_id', $company)
            ->value('batching_quality_inspection') ?? 0;

        $insp_time = GlobalSetting::where('group_company_id', $company)
            ->value('site_quality_inspection') ?? 0;

        $cleaning_time = GlobalSetting::where('group_company_id', $company)
            ->value('chute_cleaning_site') ?? 0;

        $batching_qty = $remainingQty;
        //Order and Pump Schedules
        $selected_order_pump_schedules = [];
        $schedules = [];

        $batching_plant_availability = $bps_availabilty;
        $bps_availabilty_old = [];
        //Copies for rollback
        $batching_plant_availability_copy = $bps_availabilty;

        //Shift timings calculation
        $location_start_time = $shift_start;
        $location_end_time = $shift_end;

        //Restrictions
        $restriction_start_parsed = $restriction_start;
        $restriction_end_parsed = $restriction_end;
        if (isset($restriction_start) && isset($restriction_end)) {
            $restriction_start_parsed = Carbon::parse($restriction_start);
            $restriction_end_parsed = Carbon::parse($restriction_end);
        }
        
        $truck_capacities = array_unique(array_column($tms_availabilty, 'truck_capacity'));
        $min_truck_cap = min($truck_capacities);

        //Initialize variables, Resources
        $pouring_pump = null;
        $transit_mixer = null;
        $batching_plant = null;

        $pouring_pump_index = null;
        $transit_mixer_index = null;
        $batching_plant_index = null;
        $pump_ids = [];

        $loading_time = ConstantHelper::LOADING_TIME;
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
        $sch_adj_time = 0;

        $bpScheduleGap = [];
        $plant_availability = $min_order_start_time;

        $ctr = 0;

        $orders = $modified_orders;
       
        $same_interval_count = 1;

        foreach ($orders as &$currOrder) {
           

            if ($currOrder['pump_qty'] > 0) {
                $currOrder['max_interval'] = (int)(ceil($currOrder['max_interval'] / $currOrder['pump_qty']));
            }
            $currOrder['current_trip'] = 0;
            $currOrder['next_loading_timestamp'] = Carbon::parse($currOrder['next_loading'])->timestamp;
            $interval = $currOrder['max_interval'];
            $max_interval = $currOrder['max_interval'] +  (((isset($currOrder['interval_deviation']) ? $currOrder['interval_deviation'] : $interval_deviation) / 100) * $currOrder['max_interval']);
            $currOrder['max_interval'] = round($max_interval, 0);
            $currOrder['previous_pouring_end'] = null;
            if ($currOrder['interval'] == $currOrder['max_interval']) {
                $currOrder['is_interval_same'] = $same_interval_count;
                $same_interval_count += 1;
            } else {
                $currOrder['is_interval_same'] = 0;
            }

            $currOrder['min_start_timestamp'] = min(Carbon::parse($currOrder['next_loading'])->timestamp, Carbon::parse($currOrder['order_start_time'])->timestamp);
            $ogOrder = Order::find($currOrder['og_order_id']);
            
            if (isset($ogOrder)) {
                $totalTempQty = 0;
                foreach ($ogOrder->order_temp_control as $temp) {
                    $totalTempQty += $temp->qty;
                }
                $currOrder['remaining_temp_quantity'] = $totalTempQty;
            } else {
                $currOrder['remaining_temp_quantity'] = 0;
            }
        }

        $orders = collect($orders);
      
        while (Carbon::parse($plant_availability)->lte(Carbon::parse($location_end_time)->copy()->addMinutes($loading_time))) // Batching Plant Loop through end time
        {

            $batching_plant = null;
            $transit_mixer = null;
            $pouring_pump = null;
            $plant_availability = BatchingPlantHelper::getMinAvailTime($batching_plant_availability, $loading_time, $location, $restriction_start_parsed, $restriction_end_parsed);

            if (Carbon::parse($plant_availability)->gte(Carbon::parse($location_end_time))) {
                break;
            }
           
            $remainingOrders = $orders->filter(function ($order) {
           // echo '=>'.$order['remaining_qty'];
                              if ($order['remaining_qty'] > 0) {
                    return true;
                } else {
                    return false;
                }
            });

            //No orders
            if ($remainingOrders->count() == 0) {
                break;
            }

            $orderBatchedFixInt = new Collection();
            $orderBatchedFlexInt = new Collection();
            $newOrdersWithinTime = new Collection();
            $orderBatchedAfterTime = new Collection();
            $newOrdersAfterTime = new Collection();

            $ordersNew = $orders->map(function ($item) {
                return $item;
            });

            $orders = $ordersNew;

            foreach ($orders as $order) {

                if ($order['remaining_qty'] <= 0) {
                    continue;
                }

                if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0) {
                    $orderBatchedFlexInt->push($order);
                }
                else if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0) {
                    $newOrdersWithinTime->push($order);
                } else if (Carbon::parse($order['next_loading'])->gt(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0) {
                    $orderBatchedAfterTime->push($order);
                } else if (Carbon::parse($order['next_loading'])->gt(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0) {
                    $newOrdersAfterTime->push($order);
                }
            }

            $orders = $ordersNew;

            $currentOrder = null;
            if (count($orderBatchedFlexInt) > 0) {

                $orderBatchedFlexInt = $orderBatchedFlexInt->sortBy([
                    ['next_loading_timestamp', 'asc'],
                    ['batched_qty', 'asc'],
                    ['is_interval_same', 'desc']
                ]);

                $currentOrder = $orderBatchedFlexInt->first();
                
            } else if (count($newOrdersWithinTime) > 0) {
                
                $newOrdersWithinTime = $newOrdersWithinTime->sortBy([
                    ['next_loading_timestamp', 'asc'],
                ]);

                $currentOrder = $newOrdersWithinTime->first();
            } else if (count($orderBatchedAfterTime) > 0) {
                
                $orderBatchedAfterTime = $orderBatchedAfterTime->sortBy([
                    ['next_loading_timestamp', 'asc'],
                    ['batched_qty', 'asc']
                ]);
                foreach ($orderBatchedAfterTime as $orderCheck) {

			$orderCheckSchedules = array_filter($schedules, function ($item) use ($orderCheck) {
				
                        return ($item[0]['order_id'] == $orderCheck['id']);
                    });
                    usort($orderCheckSchedules, function ($a, $b) {
                        return $a['expected_loading_end']->lt($b['expected_loading_end']) ? 1 : -1;
                    });
                    if (count($orderCheckSchedules) > 0) {
                        $end_time = $orderCheckSchedules[0][0]['expected_loading_end'];
                        if (Carbon::parse($end_time)->gte(Carbon::parse($plant_availability))) {
                            continue;
                        } else {
                            $currentOrder = $orderCheck;
                            break;
                        }
                    } else {
                        $currentOrder = $orderCheck;
                        break;
                    }
                }
               
                if (!isset($currentOrder) || (isset($currentOrder) && Carbon::parse($currentOrder['next_loading'])->gt(Carbon::Parse($plant_availability)))) {
                    if ($newOrdersAfterTime->count() > 0) {
                        $newOrdersAfterTime = $newOrdersAfterTime->sortBy([
                            ['next_loading_timestamp', 'asc'],
                        ]);
                        if (Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp'])->lte(isset($currentOrder) ? Carbon::parse($currentOrder['next_loading']) : Carbon::parse($plant_availability))) {
                            $currentOrder = $newOrdersAfterTime->first();
                        }
                    }
                }
                if (!isset($currentOrder)) {
                    if ($finalIteration == true && $newOrdersAfterTime->count() > 0) {
                        $adjustmentPossible = (abs(Carbon::parse($plant_availability)->diffInMinutes(Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp']))) <= $newOrdersAfterTime->first()['current_max_deviation']);
                        if ($adjustmentPossible) {
                            $currentOrder = $newOrdersAfterTime->first();
                        } else {
                            $plant_availability = Carbon::parse($plant_availability)->copy()->addMinute();
                        }
                    } else {
                        $plant_availability = Carbon::parse($plant_availability)->copy()->addMinute();
                    }
                }
            } else if ($newOrdersAfterTime->count() > 0) {
             
                $newOrdersAfterTime = $newOrdersAfterTime->sortBy([
                    ['next_loading_timestamp', 'asc'],
                ]);

                if ($finalIteration == true) {
                    $adjustmentPossible = (abs(Carbon::parse($plant_availability)->diffInMinutes(Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp']))) <= $newOrdersAfterTime->first()['current_max_deviation']);

                    if ($adjustmentPossible) {
                        $currentOrder = $newOrdersAfterTime->first();
                    } else {
                        $plant_availability = Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp'])->copy()->subMinutes($newOrdersAfterTime->first()['current_max_deviation'] - $newOrdersAfterTime->first()['current_deviation']);
                    }
                } else {
                    
                    if (Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp'])->gt(Carbon::parse($plant_availability))) {
                        $plant_availability = Carbon::parse($newOrdersAfterTime->first()['min_start_timestamp']);
                    } else {
                        $currentOrder = $newOrdersAfterTime->first();
                    }
                }
            }

            if (isset($currentOrder)) {
              
                $newCurrentOrder = LiveOrder::find($currentOrder['id']);
                
                if (isset($newCurrentOrder)) {
                    $newLoadingTime = $newCurrentOrder->customer_product?->product?->product_type?->batching_creation_time ?? ConstantHelper::LOADING_TIME;
                }
                $loading_time = isset($newLoadingTime) ? $newLoadingTime : ConstantHelper::LOADING_TIME;
                if ($currentOrder['remaining_temp_quantity']>0) {

                    $loading_time += isset($newCurrentOrder->customer_product?->product?->product_type?->temperature_creation_time) ? $newCurrentOrder->customer_product?->product?->product_type?->temperature_creation_time  : 0;

                }

                $travel_time = $currentOrder['travel_to_site'];
                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;

                $loading_start = Carbon::parse($plant_availability);
                $loading_end = $loading_start->copy()->addMinutes($loading_time)->subMinute();

                $plant = BatchingPlantHelper::getAvailableBatchingPlants($batching_plant_availability, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $currentOrder['assigned_batching_plant']);
                if (isset($plant)) {
                    $batching_plant = $plant['data'];
                    $batching_plant_index = $plant['index'];
                }

                $qc_start = $loading_end->copy()->addMinute();
                $qc_end = $qc_start->copy()->addMinutes($qc_time)->subMinute();

                $travel_start = $qc_end->copy()->addMinute();
                $travel_end = $travel_start->copy()->addMinutes($travel_time)->subMinute();

                $insp_start = $travel_end->copy()->addMinute();
                $insp_end = $insp_start->copy()->addMinutes($insp_time)->subMinute();

                $pouring_time = $currentOrder['pouring_time'];
                $pouring_start = $insp_end->copy()->addMinute();
                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time)->subMinute();

                $cleaning_start = $pouring_end->copy()->addMinute();
                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time)->subMinute();

                $return_time = $currentOrder['return_to_plant'];
                $return_start = $cleaning_end->copy()->addMinute();
                $return_end = $return_start->copy()->addMinutes($return_time)->subMinute();

                $truck_cap = 0;
                $batching_qty = 0;

                foreach ($truck_capacities as $truck_capacity) {
                    $truck = TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location);
                    $truck = isset($truck) ? $truck : TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end);
                    if (isset($truck)) {
                        $transit_mixer = $truck['data'];
                        $transit_mixer_index = $truck['index'];
                        $truck_cap = $truck['data']['truck_capacity'];
                        $batching_qty = min($truck_cap, $currentOrder['remaining_qty']);
                        break;
                    }
                }

                if (!isset($transit_mixer)) {
                    // Log Entry --
                    if ($finalIteration) {
                        $bpScheduleGap[] = array(
                            'group_company_id' => $company,
                            'location' => $location,
                            'plant_name' => $batching_plant['plant_name'],
                            'plant_capacity' => 0,
                            'free_from' => $loading_start,
                            'free_upto' => $loading_start,
                            'user_id' => $user_id,
                            'reason' => ConstantHelper::TM_NOT_AVL
                        );
                    }
                    $plant_availability = Carbon::parse($plant_availability)->copy()->addMinute();
                    $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                    continue;
                }


                if (isset($currentOrder['pump'])) {
                    // Get Pump Start and End Time
                    $release_current_pump = false;
                    $current_remaining_qty = $currentOrder['remaining_qty'] - $truck_cap;
                    $reamining_pump_trips =  ceil($current_remaining_qty / $min_truck_cap);
                    $reamining_pump_trips = $reamining_pump_trips / $currentOrder['pump_qty'];
                    if ($reamining_pump_trips < 1) {
                        $release_current_pump = true;
                    }
                    $lastTripAll = $currentOrder['remaining_qty'] - min([$currentOrder['remaining_qty'], $truck_cap]) <= 0;
                    $pumpTrip = $currentOrder['current_trip'] + 1;
                    $pump_timings = PumpHelper::getPumpStartAndEndTime($qc_start, $pouring_end, $pouring_start, $return_end, $cleaning_end, $release_current_pump, $pumpTrip);
                    $pump_start_time = $pump_timings['pump_start'];
                    $pump_end_time = $pump_timings['pump_end'];

                    $pump = PumpHelper::get_available_pumps($pumps_availabilty, $currentOrder['id'], $company, $pump_start_time, $pump_end_time, $currentOrder['pump'], $pumpTrip, $selected_order_pump_schedules, $location_end_time, $currentOrder['pump_qty'], $location);

                    $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pumps_availabilty, $currentOrder['id'], $company, $pump_start_time, $pump_end_time, $currentOrder['pump'], $pumpTrip, $selected_order_pump_schedules, $location_end_time, $currentOrder['pump_qty']);

                    if (isset($pump)) {
                        $pouring_pump = $pump['pump'];
                        $pouring_pump_index = $pump['index'];
                    } else {
                        $pouring_pump = $pump;
                    }

                    if (!isset($pouring_pump)) {
                        // Log Entry --
                        if ($finalIteration) {
                            $bpScheduleGap[] = array(
                                'group_company_id' => $company,
                                'location' => $batching_plant['location'],
                                'plant_name' => $batching_plant['plant_name'],
                                'plant_capacity' => 0,
                                'free_from' => $loading_start,
                                'free_upto' => $loading_start,
                                'user_id' => $user_id,
                                'reason' => ConstantHelper::PUMP_NOT_AVL
                            );
                        }
                        $plant_availability = $plant_availability->copy()->addMinute();
                        $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                        continue;
                    }
                }


                if (((isset($pouring_pump) && isset($currentOrder['pump'])) || ($pouring_pump === null && $currentOrder['pump'] === null)) && isset($transit_mixer)) {

                    $tms_availabilty[$transit_mixer_index]['free_upto'] = $loading_start->copy()->subMinute();
                    $tms_availabilty[$transit_mixer_index]['location'] = $location;
                    $tms_availabilty[] = array(
                        'truck_name' => $transit_mixer['truck_name'],
                        'truck_capacity' => $transit_mixer['truck_capacity'],
                        'loading_time' => $loading_time,
                        'free_from' => $return_end->copy()->addMinute(),
                        'free_upto' => $transit_mixer['free_upto'],
                        'location' => $location,
                    );

                    if (isset($currentOrder['pump'])) {
                        $pumps_availabilty[$pouring_pump_index]['free_upto'] = Carbon::parse($pump_start_time)->copy()->subMinute();
                        $pumps_availabilty[] = array(
                            'pump_name' => $pouring_pump['pump_name'],
                            'pump_capacity' => $pouring_pump['pump_capacity'],
                            'free_from' => Carbon::parse($pump_end_time)->copy()->addMinute(),
                            'free_upto' => $pouring_pump['free_upto'],
                            'location' => $location,
                            'order_id' => $release_current_pump ? null : $currentOrder['id'] . '-' . (($currentOrder['current_trip'] + 1) + $currentOrder['pump_qty'])
                        );
                        if ($lastTripAll) {
                            foreach ($pumps_availabilty as &$pAvl) {
                                if (Carbon::parse($pAvl['free_upto'])->gte(Carbon::parse($pAvl['free_from']))) {
                                    $order = explode('-', $pAvl['order_id']);
                                    if (count($order) > 0) {
                                        $order_id = $order[0];
                                        if ($order_id == $currentOrder['id']) {
                                            $pAvl['order_id'] = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //make a new helper  for  schedule array 
                    $schArray = array(
                        "order_id" => $currentOrder['id'],
                        'group_company_id' => $company,
                        'user_id' => $user_id,
                        'schedule_date' => $schedule_date,
                        'order_no' => $currentOrder['order_no'],
                        'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                        'location' => $location,
                        'trip' => $currentOrder['current_trip'] + 1,
                        'mix_code' => $currentOrder['mix_code'],
                        'batching_plant' => $batching_plant['plant_name'],
                        'transit_mixer' => $transit_mixer['truck_name'],
                        'expected_delivery_start' => $pouring_start,
                        'batching_qty' => $batching_qty
                    );

                    if($status == ConstantHelper::BATCHING){
                        $schArrayB = array(
                            'expected_loading_time' => $loading_time,
                            'expected_loading_start' => $loading_start,
                            'expected_loading_end' => $loading_end,
                            'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                        );

                        $scheduleArray = array_merge($schArray, $schArrayB);
                    }
                    if($status == ConstantHelper::INTERNAL_QC){
                        $schArrayQ = array(
                            'expected_qc_time' => $qc_time,
                            'expected_qc_start' => $qc_start,
                            'expected_qc_end' => $qc_end,
                            'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                        );

                        $scheduleArray = array_merge($schArray, $schArrayQ);

                    }
                    if($status == ConstantHelper::ON_SITE_TRAVEL){
                        $schArrayT = array(
                            'expected_travel_time' => $travel_time,
                            'expected_travel_start' => $travel_start,
                            'expected_travel_end' => $travel_end,
                            'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                        );

                        $scheduleArray = array_merge($schArray, $schArrayT);
                    }

                    //end helper for schedule array 

                    if(!isset($schedules[$currentOrder['id']])) {
                        $schedules[$currentOrder['id']] = array();
                    }
                    $schedules[$currentOrder['id']][] = $scheduleArray;


                    // $rest = array(
                    //     'expected_insp_time' => $insp_time,
                    //     'expected_insp_start' => $insp_start,
                    //     'expected_insp_end' => $insp_end,
                    //     'expected_pouring_time' => $pouring_time,
                    //     'expected_pouring_start' => $pouring_start,
                    //     'expected_pouring_end' => $pouring_end,
                    //     'expected_cleaning_time' => $cleaning_time,
                    //     'expected_cleaning_start' => $cleaning_start,
                    //     'expected_cleaning_end' => $cleaning_end,
                    //     'expected_return_time' => $return_time,
                    //     'expected_return_start' => $return_start,
                    //     'expected_return_end' => $return_end,
                    //     'expected_delivery_start' => $pouring_start,
                    // );


                    if (isset($currentOrder['pump'])) {

                        $pump_update = CommonHelper::searchAndUpdateArrayReSch(
                            $selected_order_pump_schedules,
                            [
                                'group_company_id' => $company,
                                'schedule_date' => $schedule_date,
                                'order_no' => $currentOrder['order_no'],
                                'pump' => $pouring_pump['pump_name'],
                                'location' => $location
                            ],
                            [
                                'expected_pouring_time' => ['value' => $pouring_end],
                                'expected_pouring_end' => $pouring_end,
                                'expected_cleaning_start' => $cleaning_start,
                                'expected_cleaning_end' => $cleaning_end,
                            ]
                        );
                        if ($pump_update['match'] === false) {

                            $pSchArray = array(
                                'order_id' => $currentOrder['id'],
                                'user_id' => $user_id,
                                'group_company_id' => $company,
                                'schedule_date' => $schedule_date,
                                'order_no' => $currentOrder['order_no'],
                                'pump' => $pouring_pump['pump_name'],
                                'location' => $location,
                                'trip' => $currentOrder['current_trip'] + 1,
                                'mix_code' => $currentOrder['mix_code'],
                                'batching_qty' => $batching_qty,
                                'expected_delivery_start' => $pouring_start,
                            );

                            // if($status == ConstantHelper::BATCHING){
                            //     $pSchArrayB = array(
                            //         'expected_loading_time' => $loading_time,
                            //         'expected_loading_start' => $loading_start,
                            //         'expected_loading_end' => $loading_end,
                            //         'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                            //     );

                            //     $pScheduleArray = array_merge($pSchArray, $pSchArrayB);
                            // }
                            if($status == ConstantHelper::BATCHING){
                                $pSchArrayQ = array(
                                    'expected_qc_time' => $qc_time,
                                    'expected_qc_start' => $qc_start,
                                    'expected_qc_end' => $qc_end,
                                    'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                                );

                                $pScheduleArray = array_merge($pSchArray, $pSchArrayQ);

                            }
                            if($status == ConstantHelper::ON_SITE_TRAVEL){
                                $pSchArrayT = array(
                                    'expected_travel_time' => $travel_time,
                                    'expected_travel_start' => $travel_start,
                                    'expected_travel_end' => $travel_end,
                                    'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]),  $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                                );

                                $pScheduleArray = array_merge($pSchArray, $pSchArrayT);
                            }   

                            if(!isset($selected_order_pump_schedules[$currentOrder['id']])) {
                                $selected_order_pump_schedules[$currentOrder['id']] = array();
                            }


                            $selected_order_pump_schedules[$currentOrder['id']][] = array(
                                
                                'expected_qc_time' => $qc_time,
                                'expected_qc_start' => $qc_start,
                                'expected_qc_end' => $qc_end,
                                'expected_travel_time' => $travel_time,
                                'expected_travel_start' => $travel_start,
                                'expected_travel_end' => $travel_end,
                                'expected_insp_time' => $insp_time,
                                'expected_insp_start' => $insp_start,
                                'expected_insp_end' => $insp_end,
                                'expected_pouring_time' => $pouring_time,
                                'expected_pouring_start' => $pouring_start,
                                'expected_pouring_end' => $pouring_end,
                                'expected_cleaning_time' => $cleaning_time,
                                'expected_cleaning_start' => $cleaning_start,
                                'expected_cleaning_end' => $cleaning_end,
                                'expected_return_time' => 0,
                                'expected_return_start' => null,
                                'expected_return_end' => null,
                                'expected_delivery_start' => $pouring_start
                            );
                        } else {
                            $selected_order_pump_schedules = $pump_update['data'];
                        }
                    }

                    $plant_availability = $loading_end->copy()->addMinute();

                    $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                    //Update Order

                    $orders = $orders->toArray();

                    foreach ($orders as &$order) {
                        if ($currentOrder['id'] == $order['id']) {
                            $tempQty = 0;

                            if (isset($truck_cap, $order['remaining_qty']) && is_numeric($truck_cap) && is_numeric($order['remaining_qty'])) {
                                if ($truck_cap > $order['remaining_qty']) {
                                    // Logic when truck capacity exceeds the remaining order quantity
                                }
                            } else {
                                // Handle invalid or missing values
                                Log::error('Invalid data: truck_cap or order[remaining_qty] is missing or not numeric.', [
                                    'truck_cap' => $truck_cap,
                                    'order' => $order,
                                ]);
                            }
                            $new_next_loading = 0;
                            if ($order['batched_qty'] == 0) {
                                $new_next_loading = $loading_start->copy()->addMinutes($order['max_interval']);
                            } else {
                                if (Carbon::parse($loading_start)->gte(Carbon::parse($order['next_loading']))) {
                                    $new_next_loading = Carbon::parse($order['next_loading'])->copy()->addMinutes($order['max_interval']);
                                } else {
                                    $new_next_loading = $loading_start->copy()->addMinutes($order['max_interval']);
                                }
                            }
                            $order['next_loading'] = $new_next_loading;
                            $order['next_loading_timestamp'] = Carbon::parse($new_next_loading)->timestamp;
                            $order['batched_qty'] = $order['batched_qty'] + $truck_cap;
                            if ($order['remaining_temp_quantity'] > 0) {
                                $order['remaining_temp_quantity'] -= $truck_cap;
                            }
                            $order['remaining_qty'] = $order['remaining_qty'] - $truck_cap;

                            $order['assigned_batching_plant'] = $batching_plant['plant_name'];
                            $order['current_trip'] = $order['current_trip'] + 1;
                            $order['previous_pouring_end'] = $pouring_start;


                            break;
                        }
                    }

                    $orders = collect($orders);
                }
            } else {
// dd('else');
                foreach ($batching_plant_availability as &$bpAvl) {
                    if (Carbon::parse($plant_availability)->gte($bpAvl['free_from'])) {
                        $bpAvl['free_from'] = $plant_availability;
                    }
                }
            }
            // $ctr++;

        } // Batching Plant loop end

// dd($schedules);
        foreach($newArray['sc'] as $orderKey => $array) {
            foreach($array as $k => $s) {         

                $r = $schedules[$orderKey][$k];


                $ls = LiveOrderSchedule::find($s['id']);
                $ls->fill($r);
                $ls->save();

            }
        }
//pump 
        foreach($newArray['sp'] as $orderKey => $array) {

           
            foreach($array as $k => $s) {

                $r = $schedules[$orderKey][$k];


                $ls = LiveOrderPumpSchedule::find($s['id']);
                $ls->fill($r);
                $ls->save();

            }
        }
        DB::commit();
        //dd('ending');
        // DB::table("live_order_schedules")->insert($schedules);
        // DB::table("live_order_pump_schedules")->insert($selected_order_pump_schedules);
        DB::table("batching_plant_availability")->insert($bpScheduleGap);

        $ordersUpdated = $orders->map(function ($item) {
            return $item;
        });

        // foreach ($ordersUpdated as $order) {

        //     $update_order = DB::table('selected_orders as A')
        //         ->where("id", $order['id'])
        //         ->update([
        //             'start_time' => DB::table('selected_order_schedules as B')
        //                 ->select(DB::raw('MIN(pouring_start) AS min_pour'))
        //                 ->where('group_company_id', $company)
        //                 ->where('user_id', $user_id)
        //                 ->where('order_no', $order['order_no'])
        //                 ->first()->min_pour,

        //             'end_time' => DB::table('selected_order_schedules as B')
        //                 ->select(DB::raw('MAX(pouring_end) AS max_pour'))
        //                 ->where('group_company_id', $company)
        //                 ->where('user_id', $user_id)
        //                 ->where('order_no', $order['order_no'])
        //                 ->first()->max_pour,
        //             'location' => $location,
        //             'delivered_quantity' => $order['batched_qty']
        //         ]);

        //     $order_db = DB::table("selected_orders")->where("id", $order['id'])->first();
        //     if (isset($order_db->start_time)) {
        //         $order_deviation = Carbon::parse($order_db->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_db->start_time), false);
        //         DB::table("selected_orders")->where("id", $order['id'])->update([
        //             'deviation' => $order_deviation
        //         ]);
        //     }
        // }
    }

    public static function initializeSchedule(int $user_id, string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $shift_start, string $shift_end, int $interval_deviation, array $modified_orders, $remainingQty,$trip, $newArray,  $status, bool $finalIteration = false)
    {
      
        set_time_limit(480); 

        $location_ids = BatchingPlant::whereIn("id", $batching_plant_ids)->pluck("company_location_id");

        $locations = DB::table("company_locations")->whereIn("id", $location_ids);
        $distinct_location = $locations->distinct("location")->pluck("location");
        

        $pumps_availabilty = PumpHelper::getPumpsAvailability($company, $schedule_date, $pump_ids);
        $tms_availabilty = TransitMixerHelper::getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);

        //Travel restrictions on TM
        $restrictions = TransitMixerRestrictionHelper::getRestrictions($company, $schedule_date, $shift_start);
        $restriction_start = $restrictions['restriction_start'];
        $restriction_end = $restrictions['restriction_end'];
// dd($modified_orders);
        foreach ($distinct_location as $location) {
            
            $location_modified_orders = array_filter($modified_orders, function ($loc_order) use ($location) {
                
                    if ($loc_order['schedule'][0]['location'] == $location) {
                        return true;
                    } else {
                        return false;
                    }
                });
            // dd($location_modified_orders);
            $min_order_start_time = BatchingPlantHelper::getMinOrderScheduleTime($company, $user_id, $shift_start, $shift_end, $schedule_date, $location, $location_modified_orders);

            $bps_availabilty = BatchingPlantHelper::getBatchingPlantAvailability($company, $schedule_date, $batching_plant_ids, $min_order_start_time, $location);
            self::generateReScheduleNewVersion($user_id, $company, $schedule_date, $tms_availabilty, $pumps_availabilty, $bps_availabilty, $shift_start, $shift_end, $restriction_start, $restriction_end, $min_order_start_time, $location, $interval_deviation, $location_modified_orders, $remainingQty, $trip, $newArray, $status, $finalIteration);
        }

        return [
            'pumps_availability' => $pumps_availabilty,
            'tms_availability' => $tms_availabilty,
        ];


    }

    public static function getTripDeviation(int $interval, float $max_interval, Carbon $pouring_end_previous, Carbon $pouring_start_current, $currentOrder)
    {
        $req_duration = $interval;
        $actual_duration = Carbon::parse($pouring_start_current)->diffInMinutes(Carbon::parse($pouring_end_previous));
        $max_duration = $interval * 3;

        $duration_deviation = max([$actual_duration - $req_duration, 0]);

        $duration_deviation_percentage = ((1 - (abs($duration_deviation) / $max_duration)) * 100);
        return round($duration_deviation_percentage, 0);
    }

    public static function getOrderMaxPossibleDeviation(int $deviation)
    {
        if ($deviation >= 0 && $deviation <= 20) {
            return 20;
        } else if ($deviation >= 21 && $deviation <= 60) {
            return 60;
        } else if ($deviation >= 61 && $deviation <= 180) {
            return 180;
        } else if ($deviation >= 181 && $deviation <= 240) {
            return 240;
        } else {
            return 1440;
        }
    }

    
}
