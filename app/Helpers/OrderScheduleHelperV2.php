<?php
namespace App\Helpers;

use App\Models\BatchingPlant;
use App\Models\BatchingPlantAvailability;
use App\Models\GlobalSetting;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\LiveOrderPumpSchedule;
use App\Models\LiveOrderSchedule;
use App\Models\Order;
use App\Models\OrderPump;
use App\Models\SelectedOrderSchedule;
use App\Models\SelectedOrder;
use App\Models\Pump;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
*/

class OrderScheduleHelperV2
{
    public static function generateSchedule(int $user_id, string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty, string $schedule_preference, string $shift_start, string $shift_end, $restriction_start, $restriction_end, string $min_order_start_time, int $interval_deviation, bool $generateLog, bool $execute = false)
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

    //Not in use
    public static function generateScheduleOld(int $user_id, string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty, string $schedule_preference, string $shift_start, string $shift_end, $restriction_start, $restriction_end, string $min_order_start_time)
    {
        // dd('aaa');
        $qc_time = ConstantHelper::QC_TIME;
        $insp_time = ConstantHelper::INSP_TIME;
        $cleaning_time = ConstantHelper::CLEANING_TIME;
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
        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", "pouring_time", "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)->where("user_id", $user_id)->whereBetween("delivery_date", [$location_start_time, $location_end_time])->whereNull("start_time")
            ->where("selected", true)
            ->get()
            ->sortBy(ConstantHelper::LARGEST_JOB_GEN_SCH_ORDER_SORT)
            ->values();

        $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));
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

        foreach ($orders as $order)
        { // Order loop start
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
                $location = $loc;
                //Check for first available plant time
                $plant_availability = BatchingPlantHelper::getMinAvailTime($batching_plant_availability_copy, ConstantHelper::LOADING_TIME, $batching_plant, $batching_plant_index, $restriction_start_parsed, $restriction_end_parsed);
                //Start the delivery acc to avl time
                $delivery_time = $schedule_preference !== ConstantHelper::CUSTOMER_TIMELINE_PREF ? (isset($order->priority) && $order->priority < ConstantHelper::DEFAULT_PRIORITY ? $order->delivery_date : $plant_availability) : $delivery_date;

                $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                $delivery_date_n = Carbon::parse($delivery_time)->copy()
                    ->addMinutes($sch_adj_time);
                $delivery_date_p = Carbon::parse($delivery_time)->copy()
                    ->subMinutes($sch_adj_time);

                $avl = 0;
                $avlCounter = 0;
                $restriction_flag = Carbon::parse(ConstantHelper::DEFAULT_DATE_TIME);
                //Schedule adjustment based on availability LOOP
                while ($avl == 0 && $avlCounter <= 5)
                {
                    $avlCounter++;
                    //Forward backward adjustment loop
                    foreach (ConstantHelper::TO_FROM_LOOP as $val)
                    {
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
                        $qtyCounter = 0;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0 && $qtyCounter <= 5)
                        {
                            $qtyCounter++;
                            //Truck Loop
                            dd($truck_capacities);
                            foreach ($truck_capacities as $truck_capacity)
                            {
                                $transit_mixer = null;
                                $transit_mixer_index = null;
                                $loading_time = ConstantHelper::LOADING_TIME;
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
                                    $delivery_date = $pouring_interval > 0 ? $pouring_start_prev->copy()
                                        ->addMinutes($pouring_interval) : $pouring_end_prev->copy()
                                        ->subMinutes($pouring_interval)->addMinute();
                                    Log::info('TRIP'.$trip.' D_date :'.$delivery_date.'--val:'.$val);
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
                                            break;
                                        }
                                    }
                                }

                                //Time calculation for activities
                                $travel_time = $order->travel_to_site;
                                $total_time = ((int)$loading_time) + $qc_time + ((int)$travel_time) + $insp_time;

                                $loading_start = $delivery_date->copy()
                                    ->subMinutes($total_time);
                                $loading_end = $loading_start->copy()
                                    ->addMinutes($loading_time)->subMinute();

                                $qc_start = $loading_end->copy()
                                    ->addMinute();
                                $qc_end = $qc_start->copy()
                                    ->addMinutes($qc_time)->subMinute();

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

                                $plant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $batching_plant);
                                if (isset($plant))
                                {
                                    $batching_plant = $plant['data'];
                                    $batching_plant_index = $plant['index'];
                                }
                                else
                                {
                                    $batching_plant = $plant;
                                }
                                if (!isset($batching_plant))
                                {
                                    $bplant = null;
                                    $bplant = BatchingPlantHelper::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_start, $restriction_start, $restriction_end);
                                    if (!isset($bplant))
                                    {
                                        break;
                                    }
                                }
                                else
                                {
                                    $bplant = ['data' => $batching_plant, 'index' => $batching_plant_index];
                                }

                                if (isset($order->pump))
                                {
                                    // Get Pump Start and End Time
                                    $lastTrip = $qty - min([$qty, $truck_capacity]) <= 0;
                                    $pumpTrip = $trip;
                                    if ($trip > 1)
                                    {
                                        $temp_pump_ids = $pump_ids;
                                        if (count($temp_pump_ids) < $order->pump_qty)
                                        {
                                            $pumpTrip = 1;
                                        }
                                    }

                                    $pump_timings = PumpHelper::getPumpStartAndEndTime($qc_start, $pouring_end, $pouring_end_prev, $return_end, $lastTrip, $pumpTrip);
                                    $pump_start_time = $pump_timings['pump_start'];
                                    $pump_end_time = $pump_timings['pump_end'];

                                    $pump = PumpHelper::get_available_pumps($pump_availability_copy, $pump_availability, $pump_ids, $company, $pump_start_time, $pump_end_time, $order->pump, $first_trip_qc_start, $trip, $selected_order_pump_schedules, $location_end_time, $qty, $truck_capacity, $order->pump_qty, true, $location);
                                    $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pump_availability_copy, $pump_availability, $pump_ids, $company, $pump_start_time, $pump_end_time, $order->pump, $first_trip_qc_start, $trip, $selected_order_pump_schedules, $location_end_time, $qty, $truck_capacity, $order->pump_qty, true);
                                    if (isset($pump))
                                    {
                                        $pouring_pump = $pump['pump'];
                                        $pouring_pump_index = $pump['index'];
                                    }
                                    else
                                    {
                                        $pouring_pump = $pump;
                                    }

                                }

                                //Assign current Truck capacity and check AVL
                                $truck_cap = (int)$truck_capacity;

                                $truck = TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location);
                                $truck = isset($truck) ? $truck : TransitMixerHelper::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end);
                                if (isset($truck))
                                {
                                    $transit_mixer = $truck['data'];
                                    $transit_mixer_index = $truck['index'];
                                }
                                else
                                {
                                    $transit_mixer = $truck;
                                }

                                if (!isset($transit_mixer))
                                {
                                    continue;
                                }

                                //All resources assigned
                                if (((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant))
                                {
                                    //Assign pouring end starting next trip
                                    $pouring_end_prev = $pouring_end->copy()
                                        ->addMinutes($pouring_time);
                                    // $pouring_end_prev = $pouring_end -> copy() -> addMinutes(1);
                                    // $pouring_end_prev = $pouring_end;
                                    $pouring_start_prev = $pouring_start;
                                    $trip_reset_time = $pouring_end;
                                    //Update Pump AVL
                                    if (isset($order->pump))
                                    {
                                        $pump_availability_copy[$pouring_pump_index]['free_upto'] = Carbon::parse($pump_start_time)->copy()
                                            ->subMinute();
                                        $pump_availability_copy[$pouring_pump_index]['location'] = $location;
                                        $pump_availability_copy[] = array(
                                            'pump_name' => $pouring_pump['pump_name'],
                                            'pump_capacity' => $pouring_pump['pump_capacity'],
                                            'free_from' => Carbon::parse($pump_end_time)->copy()
                                                ->addMinute() ,
                                            'free_upto' => $pouring_pump['free_upto'],
                                            'location' => $location
                                        );
                                    }
                                    //Update Transit Mixer AVL
                                    $transit_mixer_availability_copy[$transit_mixer_index]['free_upto'] = $loading_start->copy()
                                        ->subMinute();
                                    $transit_mixer_availability_copy[$transit_mixer_index]['location'] = $location;
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
                                    $batching_plant_availability_copy[$batching_plant_index]['free_upto'] = $loading_start->copy()
                                        ->subMinute();
                                    $batching_plant_availability_copy[] = array(
                                        'location' => $location,
                                        'plant_name' => $batching_plant['plant_name'],
                                        'plant_capacity' => $batching_plant['plant_capacity'],
                                        'free_from' => $loading_end->copy()
                                            ->addMinute() ,
                                        'free_upto' => $batching_plant['free_upto'],
                                    );
                                    $batching_qty = min([$truck_capacity, $qty]);
                                    break;
                                }

                            } //End Truck Loop
                            //Trip adjustiment
                            if ((!isset($transit_mixer)) || (!isset($batching_plant)) || (!isset($pouring_pump) && isset($order->pump)))
                            {
                                // if (isset($bplant)) {
                                //     $bplant = $bplant['data'];
                                //     $reason = ConstantHelper::TRIP_GAP;
                                //     if (!isset($transit_mixer) && (!isset($pouring_pump) && isset($order->pump))) {
                                //         $reason = ConstantHelper::TM_AND_PUMP_NOT_AVL;
                                //     } else if (!isset($transit_mixer)) {
                                //         $reason = ConstantHelper::TM_NOT_AVL;
                                //     } else if ((!isset($pouring_pump) && isset($order->pump))) {
                                //         $reason = ConstantHelper::PUMP_NOT_AVL;
                                //     }
                                //     $b_flag = false;
                                //     foreach ($bpScheduleGap as $gap) {
                                //         if (Carbon::Parse($loading_start)->eq(Carbon::parse($gap['free_from']))) {
                                //             $b_flag = true;
                                //             break;
                                //         }
                                //     }
                                //     if ($b_flag == false && $reason !== ConstantHelper::TRIP_GAP) {
                                //         $bpScheduleGap[] = array(
                                //             'group_company_id' => $company,
                                //             'location' => $location . ', Order - ' . $order->order_no . ', Trip - ' . $trip,
                                //             'plant_name' => $bplant['plant_name'],
                                //             'plant_capacity' => $bplant['plant_capacity'],
                                //             'free_from' => $loading_start,
                                //             'free_upto' => $loading_start,
                                //             'user_id' => $user_id,
                                //             'reason' => $reason
                                //         );
                                //     }
                                // }
                                if ($trip > 1)
                                {
                                    // if ((Carbon::parse($pouring_end_prev) -> diffInMinutes(Carbon::parse($trip_reset_time))) > $pouring_time )
                                    if ((Carbon::parse($pouring_end_prev)->diffInMinutes(Carbon::parse($trip_reset_time))) <= 0)
                                    {
                                        break;
                                    }
                                    else
                                    {
                                        // $pouring_end_prev = Carbon::parse($pouring_end_prev) -> copy() -> addMinute();
                                        $pouring_end_prev = Carbon::parse($pouring_end_prev)->copy()
                                            ->subMinute();
                                        $pouring_start_prev = Carbon::parse($pouring_start_prev)->copy();
                                        continue;
                                    }
                                }
                                else
                                {
                                    break;
                                }
                            }
                            else
                            { //Trip fulfilled
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
                                if (isset($order->pump))
                                {

                                    $pump_update = CommonHelper::searchAndUpdateArray($selected_order_pump_schedules, ['group_company_id' => $company, 'schedule_date' => $schedule_date, 'order_no' => $order->order_no, 'pump' => $pouring_pump['pump_name'], 'location' => $location], ['pouring_time' => ['value' => $pouring_time], 'pouring_end' => $pouring_end, 'cleaning_start' => $cleaning_start, 'cleaning_end' => $cleaning_end, 'return_time' => $return_time, 'return_start' => $return_start, 'return_end' => $return_end]);
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
                                            'return_time' => $return_time,
                                            'return_start' => $return_start,
                                            'return_end' => $return_end,
                                            'delivery_start' => $delivery_date
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
                                //Next trip
                                $qty = $qty - $batching_qty;
                                $trip += 1;

                            }
                        } //End Loop Trips
                        $pump_ids = [];
                        //All resources fulfilled
                        if (((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)) && isset($transit_mixer) && isset($batching_plant))
                        {
                            DB::table("selected_order_schedules")->insert($schedules);
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
                            $transit_mixer_availability = $transit_mixer_availability_copy;
                            $pump_availability = $pump_availability_copy;
                            $batching_plant_availability = $batching_plant_availability_copy;

                            //COMMIT AND BREAK
                            DB::commit();
                            $avl = 1;
                            break;
                        }
                        else
                        { // All resources not fulfilled (ROLLBACK/ RESET)
                            $schedules = [];
                            $selected_order_pump_schedules = [];
                            $transit_mixer_availability_copy = $transit_mixer_availability;
                            $pump_availability_copy = $pump_availability;
                            $batching_plant_availability_copy = $batching_plant_availability;
                            $pump_ids = [];

                            DB::rollBack();
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

                    //Shift crossed or day crossed
                    if ($delivery_date_p->copy()
                        ->lt(Carbon::parse($delivery_date)->copy()
                        ->subMinutes($sch_adj_to)) && $delivery_date_n->copy()
                        ->gt(Carbon::parse($delivery_date)->copy()
                        ->addMinutes($sch_adj_to)))
                    {
                        $avl = 1;
                    }
                    if ($delivery_date_p->copy()
                        ->lt($location_start_time) && $delivery_date_n->copy()
                        ->gt($location_end_time))
                    {
                        if ((($delivery_date_p->copy()
                            ->subMinutes($total_time))->lt($location_start_time)) && (($delivery_date_n->copy()
                            ->subMinutes($total_time))->gt($location_end_time)))
                        {
                            $avl = 1;
                        }
                    }
                    if ($avl == 1)
                    {
                        break;
                    }
                } //Schedule adjustment based on availability LOOP END
                if (isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order->pump)) || ($pouring_pump === null && $order->pump === null)))
                {
                    break;
                }
            } //Location loops end
            
        } //Orders Loop End
        foreach (array_chunk($bpScheduleGap, 5000) as $gap)
        {
            DB::table('batching_plant_availability')->insert($gap);
        }
    }

    // Define the copy_recursive function
    public static function copy_recursive($item)
    {
        if (is_array($item))
        {
            return array_map('copy_recursive', $item);
        }
        elseif (is_object($item))
        {
            return clone $item;
        }
        else
        {
            return $item;
        }
    }

    public static function generateScheduleNewVersion(int $user_id, string $company, string $schedule_date, array &$tms_availabilty, array &$pumps_availabilty, array $bps_availabilty, string $shift_start, string $shift_end, $restriction_start, $restriction_end, string $min_order_start_time, string $location, int $interval_deviation, array $modified_orders, bool $finalIteration = false)
    {
        // echo "newVersion ";
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
            // $transit_mixer_availability = $tms_availabilty;
            // $pump_availability = $pumps_availabilty;
            $batching_plant_availability = $bps_availabilty;

            $bps_availabilty_old = [];
            //Copies for rollback
            // $transit_mixer_availability_copy = $tms_availabilty;
            // $pump_availability_copy = $pumps_availabilty;
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
            // $orders = SelectedOrder::select(
            //     "group_company_id",
            //     "id",
            //     "order_no",
            //     "customer",
            //     "project",
            //     "site",
            //     "location",
            //     "mix_code",
            //     "quantity",
            //     "delivery_date",
            //     "interval",
            //     "pump",
            //     "pouring_time",
            //     "travel_to_site",
            //     "return_to_plant",
            //     "pump_qty",
            //     "priority",
            //     "interval_deviation"
            // )->where("group_company_id", $company)->where("user_id", $user_id)
            //     ->whereBetween("delivery_date", [$location_start_time, $location_end_time])
            //     ->whereNull("start_time")->where("selected", true)->where("location", $location)
            //     ->get()->sortBy(ConstantHelper::LARGEST_JOB_GEN_SCH_ORDER_SORT)->values();
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
            $counter = 1;
            // $orders = $orders -> toArray();
            $orders = $modified_orders;
            // dd($orders);
            $same_interval_count = 1;

            foreach ($orders as & $currOrder)
            {
                // Log::info("currOrder:" . $ctr++);
                if ($currOrder['pump_qty'] > 0)
                {
                    $currOrder['max_interval'] = (int)(ceil($currOrder['max_interval'] / $currOrder['pump_qty']));
                }
                $currOrder['current_trip'] = 0;
                $currOrder['next_loading_timestamp'] = Carbon::parse($currOrder['next_loading'])->timestamp;
                // $currOrder['next_min_loading'] = Carbon::parse($currOrder['next_loading']);
                $interval = $currOrder['max_interval'];
                $max_interval = $currOrder['max_interval'] + (((isset($currOrder['interval_deviation']) ? $currOrder['interval_deviation'] : $interval_deviation) / 100) * $currOrder['max_interval']);
                $currOrder['max_interval'] = round($max_interval, 0);
                $currOrder['previous_pouring_end'] = null;
                if ($currOrder['interval'] == $currOrder['max_interval'])
                {
                    $currOrder['is_interval_same'] = $same_interval_count;
                    $same_interval_count += 1;
                }
                else
                {
                    $currOrder['is_interval_same'] = 0;
                }

                $currOrder['min_start_timestamp'] = min(Carbon::parse($currOrder['next_loading'])->timestamp, Carbon::parse($currOrder['order_start_time'])->timestamp);
                // dd($currOrder);
                
            }

            $orders = collect($orders);

            // dd($orders);
            while (Carbon::parse($plant_availability)->lte(Carbon::parse($location_end_time)->copy()
                ->addMinutes($loading_time))) // Batching Plant Loop through end time
            
            {
                $ctr++;
                $batching_plant = null;
                $transit_mixer = null;
                $pouring_pump = null;

                $plant_availability = BatchingPlantHelper::getMinAvailTime($batching_plant_availability, $loading_time, $location, $restriction_start_parsed, $restriction_end_parsed);

                if (Carbon::parse($plant_availability)->gte(Carbon::parse($location_end_time)))
                {
                    break;
                }

                $remainingOrders = $orders->filter(function ($order)
                {
                    if ($order['remaining_qty'] > 0)
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                });

                //No orders
                if ($remainingOrders->count() == 0)
                {
                    break;
                }

                $pumpPriorityOrders = new Collection();
                $orderBatchedFixInt = new Collection();
                $orderBatchedFlexInt = new Collection();
                $newOrdersWithinTime = new Collection();
                $orderBatchedAfterTime = new Collection();
                $newOrdersAfterTime = new Collection();

                $ordersNew = $orders->map(function ($item)
                {
                    return $item;
                });

                $orders = $ordersNew;

                $batchingStart = 0;
                // echo "aaaaaaaa";
                foreach ($orders as $key => $order)
                {
                    if ($order['remaining_qty'] <= 0)
                    {
                        continue;
                    }

                    // dd(count($pumps_availabilty));
                    // if(isset($currentOrder)){
                    //     $selectedOrderPumpScheduleArray  = array_filter($selected_order_pump_schedules , function ($item) use($currentOrder) {
                    //                 // dd($order_id, $item['order_id']);
                    //                 return ($item['order_id'] == $currentOrder['order_id']);
                    //             });
                    //             $selectedOrderPumpScheduleCount = count($selectedOrderPumpScheduleArray);
                    //             if($selectedOrderPumpScheduleCount < $currentOrder['pump_qty']){
                    //                 $requiredPumpCount = true;
                    //             }
                    //             else{
                    //                 $requiredPumpCount = false;
                    //             }
                    // }
                    //      $availablePumpQty = 0;
                    //      $orderPumps = OrderPump::where('order_id', $currentOrder['og_order_id']) -> get();
                    //      $pumpTypes = $orderPumps -> pluck('type') -> toArray();
                    //      $pumpCapacites = $orderPumps -> pluck('capacity') -> toArray();
                    //      //dd($pumps_availabilty);
                    //      foreach ($pumps_availabilty as $pump_availabilty) {
                    //          if (in_array($pump_availabilty['pump_type'], $pumpTypes) && in_array($pump_availabilty['pump_capacity'], $pumpCapacites) && $pump_availabilty['order_id'] == null) {
                    //                    $availablePumpQty += 1;
                    //               }
                    //      }
                    //      $batchingStart = 1;
                    //      //  dd($orderBatchedFlexInt[0]);
                    //      }   //  if($order['pump_qty'] <= $availablePumpQty){ $flag = true; }
                    // if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0 && $order['interval'] == $order['max_interval']) {
                    //     $orderBatchedFixInt->push($order);
                    // }
                    // else if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0 && $order['interval'] != $order['max_interval']) {
                    //     $orderBatchedFlexInt->push($order);
                    // }
                    //Pump Qty Priority
                    if (isset($order['pump_priority']) && $order['pump_priority'])
                    {
                        // $pumpPriorityOrders->push($order);
                        
                    }
                    if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0)
                    {
                        // echo ": a";
                        $orderBatchedFlexInt->push($order);
                    }
                    // else if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0) {
                    else if (Carbon::parse($order['next_loading'])->lte(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0)
                    {
                        // echo ": b";
                        // echo "--- ".$order['order_no'];
                        $newOrdersWithinTime->push($order);
                    }
                    else if (Carbon::parse($order['next_loading'])->gt(Carbon::parse($plant_availability)) && $order['batched_qty'] > 0)
                    {
                        // echo ": c";
                        $orderBatchedAfterTime->push($order);
                        // } else if (Carbon::parse($order['next_loading'])->gt(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0) {
                        
                    }
                    else if (Carbon::parse($order['next_loading'])->gt(Carbon::parse($plant_availability)) && $order['batched_qty'] == 0)
                    {
                        // echo ": d";
                        $newOrdersAfterTime->push($order);
                    }
                }
                if ($ctr == 1)
                {

                    // dd($orderBatchedFlexInt, $newOrdersWithinTime, $orderBatchedAfterTime, $newOrdersAfterTime);
                    
                }

                //echo '::o::';
                $orders = $ordersNew;

                $currentOrder = null;

                // if (count($orderBatchedFixInt) > 0) {
                //     $orderBatchedFixInt->sortBy([
                //         ['next_loading', 'asc'],
                //         // ['remaining_qty', 'desc'],
                //         ['batched_qty', 'asc'],
                //     ]);
                //     $currentOrder = $orderBatchedFixInt->first();
                // }
                // else if (count($orderBatchedFlexInt) > 0) {
                //     $orderBatchedFlexInt->sortBy([
                //         ['next_loading', 'asc'],
                //         // ['remaining_qty', 'desc'],
                //         ['batched_qty', 'asc']
                //     ]);
                //     $currentOrder = $orderBatchedFlexInt->first();
                // }
                // if (count($pumpPriorityOrders)) {
                //     $pumpPriorityOrders = $pumpPriorityOrders->sortBy([
                //         ['next_loading_timestamp', 'asc'],
                //         // ['remaining_qty', 'desc'],
                //         ['batched_qty', 'asc'],
                //         ['is_interval_same', 'desc']
                //     ]);
                //     $currentOrder = $pumpPriorityOrders->first();
                // }
                if (count($orderBatchedFlexInt) > 0)
                {
                    $orderBatchedFlexInt = $orderBatchedFlexInt->sortBy([['next_loading_timestamp', 'asc'],
                    // ['remaining_qty', 'desc'],
                    ['batched_qty', 'asc'], ['is_interval_same', 'desc']]);

                    $currentOrder = $orderBatchedFlexInt->first();
                    // if (!isset($currentOrder)) {
                    //     $plant_availability = Carbon::parse($plant_availability) -> copy() -> addMinute();
                    // }
                    
                }
                else if (count($newOrdersWithinTime) > 0)
                {
                    $newOrdersWithinTime = $newOrdersWithinTime->sortBy([
                    // ['next_loading_timestamp', 'asc'],
                    ['next_loading_timestamp', 'asc'],
                    // ['remaining_qty', 'desc'],
                    // ['batched_qty', 'asc']
                    ]);

                    $currentOrder = $newOrdersWithinTime->first();

                }
                else if (count($orderBatchedAfterTime) > 0)
                {
                    $orderBatchedAfterTime = $orderBatchedAfterTime->sortBy([['next_loading_timestamp', 'asc'],
                    // ['remaining_qty', 'desc'],
                    ['batched_qty', 'asc']]);
                    foreach ($orderBatchedAfterTime as $orderCheck)
                    {

                        $orderCheckSchedules = array_filter($schedules, function ($item) use ($orderCheck)
                        {
                            return ($item['order_id'] == $orderCheck['id']);
                        });
                        usort($orderCheckSchedules, function ($a, $b)
                        {
                            return $a['loading_end']->lt($b['loading_end']) ? 1 : -1;
                        });
                        // if ($ctr == 11) {
                        //     dd($orderCheckSchedules, $plant_availability);
                        // }
                        if (count($orderCheckSchedules) > 0)
                        {

                            $end_time = $orderCheckSchedules[0]['loading_end'];
                            if (Carbon::parse($end_time)->gte(Carbon::parse($plant_availability)))
                            {
                                continue;
                            }
                            else
                            {
                                $currentOrder = $orderCheck;
                                break;
                            }
                        }
                        else
                        {
                            $currentOrder = $orderCheck;
                            break;
                        }
                    }
                    // if (!isset($currentOrder)) {
                    //     $plant_availability = Carbon::parse($plant_availability) -> copy() -> addMinute();
                    // }
                    if (!isset($currentOrder) || (isset($currentOrder) && Carbon::parse($currentOrder['next_loading'])->gt(Carbon::Parse($plant_availability))))
                    {
                        if ($newOrdersAfterTime->count() > 0)
                        {
                            $newOrdersAfterTime = $newOrdersAfterTime->sortBy([
                            // ['next_loading_timestamp', 'asc'],
                            ['next_loading_timestamp', 'asc'], ]);
                            // $plant_availability = Carbon::parse($newOrdersAfterTime -> first()['min_start_timestamp']);
                            if (Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp'])
                                ->lte(isset($currentOrder) ? Carbon::parse($currentOrder['next_loading']) : Carbon::parse($plant_availability)))
                            {
                                $currentOrder = $newOrdersAfterTime->first();
                            }
                        }
                    }
                    if (!isset($currentOrder))
                    {
                        if ($finalIteration == true && $newOrdersAfterTime->count() > 0)
                        {
                            $adjustmentPossible = (abs(Carbon::parse($plant_availability)->diffInMinutes(Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp']))) <= $newOrdersAfterTime->first() ['current_max_deviation']);
                            if ($adjustmentPossible)
                            {
                                $currentOrder = $newOrdersAfterTime->first();
                            }
                            else
                            {
                                $plant_availability = Carbon::parse($plant_availability)->copy()
                                    ->addMinute();
                            }
                        }
                        else
                        {
                            $plant_availability = Carbon::parse($plant_availability)->copy()
                                ->addMinute();
                        }
                    }

                }
                else if ($newOrdersAfterTime->count() > 0)
                {
                    $newOrdersAfterTime = $newOrdersAfterTime->sortBy([
                    // ['next_loading_timestamp', 'asc'],
                    ['next_loading_timestamp', 'asc'], ]);
                    if ($finalIteration == true)
                    {
                        $adjustmentPossible = (abs(Carbon::parse($plant_availability)->diffInMinutes(Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp']))) <= $newOrdersAfterTime->first() ['current_max_deviation']);
                        if ($adjustmentPossible)
                        {
                            $currentOrder = $newOrdersAfterTime->first();
                        }
                        else
                        {
                            // $plant_availability = Carbon::parse($newOrdersAfterTime -> first()['min_start_timestamp']);
                            $plant_availability = Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp'])
                                ->copy()
                                ->subMinutes($newOrdersAfterTime->first() ['current_max_deviation'] - $newOrdersAfterTime->first() ['current_deviation']);
                        }
                    }
                    else
                    {
                        if (Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp'])
                            ->gt(Carbon::parse($plant_availability)))
                        {
                            $plant_availability = Carbon::parse($newOrdersAfterTime->first() ['min_start_timestamp']);
                        }
                        else
                        {
                            $currentOrder = $newOrdersAfterTime->first();
                        }
                    }
                    // if (Carbon::parse($newOrdersAfterTime->first()['order_start_time']) -> lte(Carbon::parse($plant_availability))) {
                    //     $currentOrder = $newOrdersAfterTime->first();
                    // } else {
                    //     // if (Carbon::parse($newOrdersAfterTime->first()['order_start_time']) -> lt(Carbon::parse($newOrdersAfterTime->first()['next_loading']))) {
                    //     //     $plant_availability = $newOrdersAfterTime->first()['order_start_time'];
                    //     // } else {
                    //     //     $plant_availability = $newOrdersAfterTime->first()['next_loading'];
                    //     // }
                    //     //Calculate min time out of all orders
                    //     $min_time = self::getMinTimeWithinOrders($newOrdersAfterTime, Carbon::parse($plant_availability));
                    //     if (Carbon::parse($min_time) -> gt(Carbon::parse($plant_availability)))
                    //     {
                    //         // $plant_availability = Carbon::parse($plant_availability) -> addMinute();
                    //         $plant_availability = Carbon::parse($min_time);
                    //         // foreach ($batching_plant_availability as &$bpAvl) {
                    //         //     if (Carbon::parse($plant_availability) -> gte($bpAvl['free_from'])) {
                    //         //         $bpAvl['free_from'] = $plant_availability;
                    //         //     }
                    //         // }
                    //     }
                    //     else {
                    //         $currentOrder = $newOrdersAfterTime -> first();
                    //     }
                    // }
                    
                }

                // if (isset($currentOrder)) {
                //     //No new orders and previous order might have loading time greater than required
                //     if (Carbon::Parse($plant_availability) -> lt(Carbon::parse($currentOrder['next_min_loading']))) {
                //         $plant_availability = $currentOrder['next_min_loading'];
                //         $currentOrder = null;
                //     }
                // }
                if (isset($currentOrder))
                {
                    $newCurrentOrder = SelectedOrder::find($currentOrder['id']);
                    if (isset($newCurrentOrder))
                    {
                        $newLoadingTime = $newCurrentOrder->customer_product ?->product ?->product_type ?->batching_creation_time ?? ConstantHelper::LOADING_TIME;
                    }
                    $loading_time = isset($newLoadingTime) ? $newLoadingTime : ConstantHelper::LOADING_TIME;
                    // if ($currentOrder['remaining_temp_quantity']>0) {
                    //     $loading_time += isset($newCurrentOrder->customer_product?->product?->product_type?->temperature_creation_time) ? $newCurrentOrder->customer_product?->product?->product_type?->temperature_creation_time  : 0;
                    // }
                    $travel_time = $currentOrder['travel_to_site'];
                    $total_time = ((int)$loading_time) + $qc_time + ((int)$travel_time) + $insp_time;

                    $loading_start = Carbon::parse($plant_availability);
                    $loading_end = $loading_start->copy()
                        ->addMinutes($loading_time)->subMinute();

                    $plant = BatchingPlantHelper::getAvailableBatchingPlants($batching_plant_availability, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $currentOrder['assigned_batching_plant']);
                    if (isset($plant))
                    {
                        $batching_plant = $plant['data'];
                        $batching_plant_index = $plant['index'];
                    }


                    $qc_start = $loading_end->copy()
                        ->addMinute();
                    $qc_end = $qc_start->copy()
                        ->addMinutes($qc_time)->subMinute();

                    $travel_start = $qc_end->copy()
                        ->addMinute();
                    $travel_end = $travel_start->copy()
                        ->addMinutes($travel_time)->subMinute();

                    $insp_start = $travel_end->copy()
                        ->addMinute();
                    $insp_end = $insp_start->copy()
                        ->addMinutes($insp_time)->subMinute();

                    $pouring_time = $currentOrder['pouring_time'];
                    $pouring_start = $insp_end->copy()
                        ->addMinute();
                    $pouring_end = $pouring_start->copy()
                        ->addMinutes($pouring_time)->subMinute();

                    $cleaning_start = $pouring_end->copy()
                        ->addMinute();
                    $cleaning_end = $cleaning_start->copy()
                        ->addMinutes($cleaning_time)->subMinute();

                    $return_time = $currentOrder['return_to_plant'];
                    $return_start = $cleaning_end->copy()
                        ->addMinute();
                    $return_end = $return_start->copy()
                        ->addMinutes($return_time)->subMinute();

                    $truck_cap = 0;
                    $batching_qty = 0;

                    foreach ($truck_capacities as $truck_capacity)
                    {
                        $truck = TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location);
                        $truck = isset($truck) ? $truck : TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end);
                        if (isset($truck))
                        {
                            $transit_mixer = $truck['data'];
                            $transit_mixer_index = $truck['index'];
                            $truck_cap = $truck['data']['truck_capacity'];
                            $batching_qty = min($truck_cap, $currentOrder['remaining_qty']);
                            break;
                        }
                    }

                    if (!isset($transit_mixer))
                    {
                        // Log Entry --
                        if ($finalIteration)
                        {
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
                        $plant_availability = Carbon::parse($plant_availability)->copy()
                            ->addMinute();
                        $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                        continue;
                    }

                    if (isset($currentOrder['pump']))
                    {
                        // Get Pump Start and End Time
                        $release_current_pump = false;
                        $current_remaining_qty = $currentOrder['remaining_qty'] - $truck_cap;
                        $reamining_pump_trips = ceil($current_remaining_qty / $min_truck_cap);
                        $reamining_pump_trips = $reamining_pump_trips / $currentOrder['pump_qty'];
                        if ($reamining_pump_trips < 1)
                        {
                            $release_current_pump = true;
                        }
                        $lastTripAll = $currentOrder['remaining_qty'] - min([$currentOrder['remaining_qty'], $truck_cap]) <= 0;
                        $pumpTrip = $currentOrder['current_trip'] + 1;
                        $pump_timings = PumpHelper::getPumpStartAndEndTime($qc_start, $pouring_end, $pouring_start, $return_end, $cleaning_end, $release_current_pump, $pumpTrip);
                        $pump_start_time = $pump_timings['pump_start'];
                        $pump_end_time = $pump_timings['pump_end'];
                        // dd($currOrder);
                        $pump = PumpHelper::get_available_pumps($pumps_availabilty, $currentOrder['id'], $company, $pump_start_time, $pump_end_time, $currentOrder['pump'], $pumpTrip, $selected_order_pump_schedules, $location_end_time, $currentOrder['pump_qty'], $location);
                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pumps_availabilty, $currentOrder['id'], $company, $pump_start_time, $pump_end_time, $currentOrder['pump'], $pumpTrip, $selected_order_pump_schedules, $location_end_time, $currentOrder['pump_qty']);
                        $pump = isset($pump) ? $pump : PumpHelper::get_available_pumps($pumps_availabilty, $currentOrder['id'], $company, $pump_start_time, $pump_end_time, $currentOrder['pump'], null, $selected_order_pump_schedules, $location_end_time, $currentOrder['pump_qty']);
                        // if ($ctr == 2) {
                        //     dd($pump);
                        // }
                        // if (Carbon::parse($plant_availability)  -> gte(Carbon::parse("2024-12-31 10:51:00")) && $currentOrder['order_no'] == 1132) {
                        //     dd($pump, $currentOrder, Carbon::Parse($plant_availability) -> format("h:i"));
                        // }
                        // if ($currentOrder['order_no'] == 1132 && $currentOrder['current_trip'] == 2) {
                        //     dd($pump, $plant_availability, $pump_start_time, $pump_end_time);
                        // }
                        if (isset($pump))
                        {
                            $pouring_pump = $pump['pump'];
                            $pouring_pump_index = $pump['index'];
                        }
                        else
                        {
                            $pouring_pump = $pump;
                        }

                        if (!isset($pouring_pump))
                        {
                            // Log Entry --
                            if ($finalIteration)
                            {
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
                            $plant_availability = Carbon::parse($plant_availability)->copy()
                                ->addMinute();
                            $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                            continue;
                        }
                    }
                    // echo("  ------------- " .$ctr);
                    // if ($currentOrder['order_no'] == 1132 && $ctr > 45) {
                    //     dd($plant_availability, $ctr);
                    // }
                    if (((isset($pouring_pump) && isset($currentOrder['pump'])) || ($pouring_pump === null && $currentOrder['pump'] === null)) && isset($transit_mixer))
                    {

                        $tms_availabilty[$transit_mixer_index]['free_upto'] = $loading_start->copy()
                            ->subMinute();
			$tms_availabilty[$transit_mixer_index]['location'] = $location;
			if($tms_availabilty[$transit_mixer_index]['free_upto'] <= $tms_availabilty[$transit_mixer_index]['free_from']){
                            unset($tms_availabilty[$transit_mixer_index]);
                        }
                        $tms_availabilty[] = array(
                            'truck_name' => $transit_mixer['truck_name'],
                            'truck_capacity' => $transit_mixer['truck_capacity'],
                            'loading_time' => $loading_time,
                            'free_from' => $return_end->copy()
                                ->addMinute() ,
                            'free_upto' => $transit_mixer['free_upto'],
                            'location' => $location,
                        );

                        if (isset($currentOrder['pump']))
                        {
                            $pumps_availabilty[$pouring_pump_index]['free_upto'] = Carbon::parse($pump_start_time)->copy()
			  ->subMinute();
			    if($pumps_availabilty[$pouring_pump_index]['free_upto'] <= $pumps_availabilty[$pouring_pump_index]['free_from']){
                            unset($pumps_availabilty[$pouring_pump_index]);
                        }
                            $pumps_availabilty[] = array(
                                'pump_name' => $pouring_pump['pump_name'],
                                'pump_type' => $pouring_pump['pump_type'],
                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                'free_from' => Carbon::parse($pump_end_time)->copy()
                                    ->addMinute() ,
                                'free_upto' => $pouring_pump['free_upto'],
                                'location' => $location,
                                'order_id' => $release_current_pump ? null : $currentOrder['id'] . '-' . (($currentOrder['current_trip'] + 1) + $currentOrder['pump_qty']) ,
                                'order_id_wo_trip' => $release_current_pump ? null : $currentOrder['id']

                            );
                            //
                            // if ($currentOrder['id'] == 62) {
                            //     // dd($schedules);
                            // }
                            // if ($currentOrder['pump_qty'] > 1) {
                            //     for ($i=0; $i < $currentOrder['pump_qty'] - 1; $i++) {
                            //         foreach ($pumps_availabilty as &$pmpAvail) {
                            //             if (!isset($pmpAvail['order_id']) && !isset($pmpAvail['lock_future_order']) && $pmpAvail['pump_name'] != $pouring_pump['pump_name']) {
                            //                 $pmpAvail['lock_future_order'] = $currentOrder['id'];
                            //             }
                            //         }
                            //         // dd($pumps_availabilty);
                            //     }
                            // }
                            if ($lastTripAll)
                            {
                                foreach ($pumps_availabilty as & $pAvl)
                                {
                                    if (Carbon::parse($pAvl['free_upto'])->gte(Carbon::parse($pAvl['free_from'])))
                                    {
                                        $order = explode('-', $pAvl['order_id']);
                                        if (count($order) > 0)
                                        {
                                            $order_id = $order[0];
                                            if ($order_id == $currentOrder['id'])
                                            {
                                                $pAvl['order_id'] = null;
                                                $pAvl['lock_future_order'] = null;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($currentOrder['pump']))
                        {

                            $pump_update = CommonHelper::searchAndUpdateArray($selected_order_pump_schedules, ['group_company_id' => $company, 'schedule_date' => $schedule_date, 'order_no' => $currentOrder['order_no'], 'pump' => $pouring_pump['pump_name'], 'location' => $location], ['pouring_time' => ['value' => $pouring_end], 'pouring_end' => $pouring_end, 'cleaning_start' => $cleaning_start, 'cleaning_end' => $cleaning_end, ]);
                            if ($pump_update['match'] === false)
                            {
                                $selected_order_pump_schedules[] = array(
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
                                    'qc_time' => $qc_time,
                                    'qc_start' => $qc_start,
                                    'qc_end' => $qc_end,
                                    'travel_time' => $travel_time,
                                    'travel_start' => $travel_start,
                                    'travel_end' => $travel_end,
                                    'insp_time' => $insp_time,
                                    'insp_start' => $insp_start,
                                    'insp_end' => $insp_end,
                                    'pouring_time' => $pouring_time, //round(($pouring_time/8) * $batching_qty,2),
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
                        }

                        $schedules[] = array(
                            "order_id" => $currentOrder['id'],
                            'group_company_id' => $company,
                            'user_id' => $user_id,
                            'schedule_date' => $schedule_date,
                            'order_no' => $currentOrder['order_no'],
                            'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                            'location' => $location,
                            'trip' => $currentOrder['current_trip'] + 1,
                            'pump_trip' => count($selected_order_pump_schedules) ? $selected_order_pump_schedules[count($selected_order_pump_schedules) - 1]['trip'] : null,
                            'mix_code' => $currentOrder['mix_code'],
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
                            'pouring_time' => $pouring_time, //round(($pouring_time/8) * $batching_qty,2),
                            'pouring_start' => $pouring_start,
                            'pouring_end' => $pouring_end,
                            'cleaning_time' => $cleaning_time,
                            'cleaning_start' => $cleaning_start,
                            'cleaning_end' => $cleaning_end,
                            'return_time' => $return_time,
                            'return_start' => $return_start,
                            'return_end' => $return_end,
                            'delivery_start' => $pouring_start,
                            'deviation' => $currentOrder['previous_pouring_end'] === null ? 100 : self::getTripDeviation(max([$currentOrder['interval'], $currentOrder['pouring_time']]) , $currentOrder['max_interval'], $currentOrder['previous_pouring_end'], $pouring_start, $currentOrder)
                        );

                        // if (isset($currentOrder['pump'])) {
                        //     $pump_update = CommonHelper::searchAndUpdateArray(
                        //         $selected_order_pump_schedules,
                        //         [
                        //             'group_company_id' => $company,
                        //             'schedule_date' => $schedule_date,
                        //             'order_no' => $currentOrder['order_no'],
                        //             'pump' => $pouring_pump['pump_name'],
                        //             'location' => $location
                        //         ],
                        //         [
                        //             'pouring_time' => ['value' => $pouring_end],
                        //             'pouring_end' => $pouring_end,
                        //             'cleaning_start' => $cleaning_start,
                        //             'cleaning_end' => $cleaning_end,
                        //         ]
                        //     );
                        //     if ($pump_update['match'] === false) {
                        //         $selected_order_pump_schedules[] = array(
                        //             'order_id' => $currentOrder['id'],
                        //             'user_id' => $user_id,
                        //             'group_company_id' => $company,
                        //             'schedule_date' => $schedule_date,
                        //             'order_no' => $currentOrder['order_no'],
                        //             'pump' => $pouring_pump['pump_name'],
                        //             'location' => $location,
                        //             'trip' => $currentOrder['current_trip'] + 1,
                        //             'mix_code' => $currentOrder['mix_code'],
                        //             'batching_qty' => $batching_qty,
                        //             'qc_time' => $qc_time,
                        //             'qc_start' => $qc_start,
                        //             'qc_end' => $qc_end,
                        //             'travel_time' => $travel_time,
                        //             'travel_start' => $travel_start,
                        //             'travel_end' => $travel_end,
                        //             'insp_time' => $insp_time,
                        //             'insp_start' => $insp_start,
                        //             'insp_end' => $insp_end,
                        //             'pouring_time' => $pouring_time,
                        //             'pouring_start' => $pouring_start,
                        //             'pouring_end' => $pouring_end,
                        //             'cleaning_time' => $cleaning_time,
                        //             'cleaning_start' => $cleaning_start,
                        //             'cleaning_end' => $cleaning_end,
                        //             'return_time' => 0,
                        //             'return_start' => null,
                        //             'return_end' => null,
                        //             'delivery_start' => $pouring_start
                        //         );
                        //     } else {
                        //         $selected_order_pump_schedules = $pump_update['data'];
                        //     }
                        // }
                        $plant_availability = $loading_end->copy()
                            ->addMinute();

                        $batching_plant_availability[$batching_plant_index]['free_from'] = $plant_availability;
                        //Update Order
                        $orders = $orders->toArray();

                        // dd('aaaa');
                        foreach ($orders as & $order)
                        {
                            //Assign Pump condition
                            // dd($currentOrder);
                            if ($currentOrder['id'] == $order['id'])
                            {
                                // if ($order['pump_qty'] > 1 && ($order['current_trip'] + 1) < $order['pump_qty']) {
                                //     $order['pump_priority'] = true;
                                // } else {
                                //     $order['pump_priority'] = false;
                                // }
                                if ($truck_cap > $order['remaining_qty'])
                                {
                                    $truck_cap = $order['remaining_qty'];
                                }
                                // $order['next_loading'] = $order['batched_qty'] == 0 ? $loading_start->copy()->addMinutes($order['max_interval']) : Carbon::parse($order['next_loading'])->copy()->addMinutes($order['max_interval']);
                                $new_next_loading = 0;
                                $new_min_next_loading = 0;
                                if ($order['batched_qty'] == 0)
                                {
                                    $new_next_loading = $loading_start->copy()
                                        ->addMinutes($order['interval']);
                                    // $new_min_next_loading = $loading_start->copy()->addMinutes($order['interval']);
                                    
                                }
                                else
                                {
                                    if (Carbon::parse($loading_start)->gte(Carbon::parse($order['next_loading'])))
                                    {
                                        $new_next_loading = Carbon::parse($order['next_loading'])->copy()
                                            ->addMinutes($order['interval']);
                                        // $new_min_next_loading = Carbon::parse($order['next_loading'])->copy()->addMinutes($order['interval']);
                                        
                                    }
                                    else
                                    {
                                        $new_next_loading = $loading_start->copy()
                                            ->addMinutes($order['interval']);
                                        // $new_min_next_loading = $loading_start->copy()->addMinutes($order['interval']);
                                        
                                    }
                                }

                                $order['next_loading'] = $new_next_loading;
                                // $order['next_min_loading'] = Carbon::parse($new_next_loading);
                                $order['next_loading_timestamp'] = Carbon::parse($new_next_loading)->timestamp;
                                $order['batched_qty'] = $order['batched_qty'] + $truck_cap;
                                $order['remaining_qty'] = $order['remaining_qty'] - $truck_cap;
                                $order['assigned_batching_plant'] = $batching_plant['plant_name'];
                                $order['current_trip'] = $order['current_trip'] + 1;
                                $order['previous_pouring_end'] = $pouring_start;
                                break;
                            }

                        }
                        $orders = collect($orders);
                    }
                }
                else
                {
                    //Resources
                    // foreach ($truck_capacities as $truck_capacity) {
                    //     $truck = TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $plant_availability, $plant_availability, $location_end_time, $restriction_start, $restriction_end, $location);
                    //     $truck = isset($truck) ? $truck : TransitMixerHelper::getAvailableTrucks($tms_availabilty, $truck_capacity, $plant_availability, $plant_availability, $location_end_time, $restriction_start, $restriction_end);
                    // }
                    foreach ($batching_plant_availability as & $bpAvl)
                    {
                        if (Carbon::parse($plant_availability)->gte($bpAvl['free_from']))
                        {
                            $bpAvl['free_from'] = $plant_availability;
                        }
                        // if (!isset($truck)) {
                        //     // Log Entry --
                        //     $bpScheduleGap[] = array(
                        //         'group_company_id' => $company,
                        //         'location' => $location,
                        //         'plant_name' => $bpAvl['plant_name'],
                        //         'plant_capacity' => 0,
                        //         'free_from' => $plant_availability,
                        //         'free_upto' => $plant_availability,
                        //         'user_id' => $user_id,
                        //         'reason' => ConstantHelper::TM_NOT_AVL
                        //     );
                        // }
                        
                    }
                }
                // dd('aaa');
                // $ctr++;
                // echo "--".$ctr;
                // if($ctr > 2000){dd('infintie');}
                
            } // Batching Plant loop end
            // dd($schedules);
            // dd($selected_order_pump_schedules);
            // dd("WHILE");
            DB::table("selected_order_schedules")->insert($schedules);
            DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
            DB::table("batching_plant_availability")->insert($bpScheduleGap);

            $ordersUpdated = $orders->map(function ($item)
            {
                return $item;
            });

            foreach ($ordersUpdated as $order)
            {

                $update_order = DB::table('selected_orders as A')->where("id", $order['id'])->update(['start_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                    ->where('group_company_id', $company)->where('user_id', $user_id)->where('order_no', $order['order_no'])->first()->min_pour,

                'end_time' => DB::table('selected_order_schedules as B')
                    ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                    ->where('group_company_id', $company)->where('user_id', $user_id)->where('order_no', $order['order_no'])->first()->max_pour, 'location' => $location, 'delivered_quantity' => $order['batched_qty']]);

                $order_db = DB::table("selected_orders")->where("id", $order['id'])->first();
                if (isset($order_db->start_time))
                {
                    $order_deviation = Carbon::parse($order_db->delivery_date)
                        ->copy()
                        ->diffInMinutes(Carbon::parse($order_db->start_time) , false);
                    DB::table("selected_orders")
                        ->where("id", $order['id'])->update(['deviation' => $order_deviation]);
                }
            }
        }
        catch(\Exception $e)
        {
            dd($e);
        }
        // dump('l');
        
    }

    public static function initializeSchedule(int $user_id, string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $shift_start, string $shift_end, int $interval_deviation, array $modified_orders, bool $finalIteration = false)
    {
        set_time_limit(5000); //NEED TO REMOVE
        SelectedOrderSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        SelectedOrderPumpSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        // BatchingPlantAvailability::where("group_company_id", $company)->where("user_id", $user_id)->delete();
        SelectedOrder::where("group_company_id", $company)->whereBetween("delivery_date", [$shift_start, $shift_end])->where("user_id", $user_id)->update(['start_time' => null, 'end_time' => null, 'deviation' => null, 'delivered_quantity' => 0]);

        $location_ids = BatchingPlant::whereIn("id", $batching_plant_ids)->where('status', ConstantHelper::ACTIVE)
            ->pluck("company_location_id");

        $locations = DB::table("company_locations")->where('status', ConstantHelper::ACTIVE)
            ->whereIn("id", $location_ids);
        $location_names = $locations->pluck("location");
        $distinct_location = $locations->distinct("location")
            ->pluck("location");
        $location_name = $locations->first()->location;

        SelectedOrder::where("group_company_id", $company)->whereBetween("delivery_date", [$shift_start, $shift_end])->where("user_id", $user_id)->whereNotIn("location", $location_names)->update(['location' => $location_name]);

        $pumps_availabilty = PumpHelper::getPumpsAvailability($company, $schedule_date, $pump_ids);
        $tms_availabilty = TransitMixerHelper::getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);

        //Travel restrictions on TM
        $restrictions = TransitMixerRestrictionHelper::getRestrictions($company, $schedule_date, $shift_start);
        $restriction_start = $restrictions['restriction_start'];
        $restriction_end = $restrictions['restriction_end'];
        // dd($distinct_location);
        foreach ($distinct_location as $location)
        {
            $location_modified_orders = array_filter($modified_orders, function ($loc_order) use ($location)
            {
                if ($loc_order['location'] == $location)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            });

            // $min_order_start_time = $schedule_preference != ConstantHelper::CUSTOMER_TIMELINE_PREF ? BatchingPlantHelper::getMinOrderScheduleTime($company, $user_id, $shift_start, $shift_end, $schedule_date) : $shift_start;
            $min_order_start_time = BatchingPlantHelper::getMinOrderScheduleTime($company, $user_id, $shift_start, $shift_end, $schedule_date, $location, $location_modified_orders);
            $bps_availabilty = BatchingPlantHelper::getBatchingPlantAvailability($company, $schedule_date, $batching_plant_ids, $min_order_start_time, $location);

            self::generateScheduleNewVersion($user_id, $company, $schedule_date, $tms_availabilty, $pumps_availabilty, $bps_availabilty, $shift_start, $shift_end, $restriction_start, $restriction_end, $min_order_start_time, $location, $interval_deviation, $location_modified_orders, $finalIteration);
        }

        return ['pumps_availability' => $pumps_availabilty, 'tms_availability' => $tms_availabilty, ];
        // self::generateScheduleStepTwo($user_id, $company, $schedule_date, 0, ConstantHelper::MAX_MINS_LOOP_CHECK, $tms_availabilty, $pumps_availabilty, $bps_availabilty, $schedule_preference, $shift_start, $shift_end, $restriction_start, $restriction_end, $min_order_start_time);
        

        
    }

    public static function deleteUserSchedules(int $group_company_id, int $user_id) : void
    {
        SelectedOrderSchedule::where("group_company_id", $group_company_id)->where("user_id", $user_id)->delete();
        SelectedOrderPumpSchedule::where("group_company_id", $group_company_id)->where("user_id", $user_id)->delete();
        SelectedOrder::where("group_company_id", $group_company_id)->where("user_id", $user_id)->delete();
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

    //initialize old
    public static function initializeScheduleOld(int $user_id, string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $schedule_preference, string $shift_start, string $shift_end, int $interval_deviation)
    {
        try
        {
            // dd('a');
            set_time_limit(5000); //NEED TO REMOVE
            SelectedOrderSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
            SelectedOrderPumpSchedule::where("group_company_id", $company)->where("user_id", $user_id)->delete();
            BatchingPlantAvailability::where("group_company_id", $company)->where("user_id", $user_id)->delete();

            SelectedOrder::where("group_company_id", $company)->whereBetween("delivery_date", [$shift_start, $shift_end])->where("user_id", $user_id)->update(['start_time' => null, 'end_time' => null, 'deviation' => null, ]);

            $pumps_availabilty = PumpHelper::getPumpsAvailability($company, $schedule_date, $pump_ids);
            // dd($pumps_availabilty);
            $tms_availabilty = TransitMixerHelper::getTrucksAvailability($company, $schedule_date, $transit_mixer_ids);
            $min_order_start_time = BatchingPlantHelper::getMinOrderScheduleTimeCopy($company, $user_id, $shift_start, $shift_end, $schedule_date);
            
            $bps_availabilty = BatchingPlantHelper::getBatchingPlantAvailabilityCopy($company, $schedule_date, $batching_plant_ids, $min_order_start_time);

            //Travel restrictions on TM
            $restrictions = TransitMixerRestrictionHelper::getRestrictions($company, $schedule_date, $shift_start);
            $restriction_start = $restrictions['restriction_start'];
            $restriction_end = $restrictions['restriction_end'];


            // $schedule_loop = [180, 360, 1440, 1440];
            $schedule_loop = [1440];

            $scheduled_orders = [];

            foreach ($schedule_loop as $loop_key => $loop_time)
            {

                $modified_orders = self::generateSchedule($user_id, $company, $schedule_date, 0, $loop_time, $tms_availabilty, $pumps_availabilty, $bps_availabilty, $schedule_preference, $shift_start, $shift_end, $restriction_start, $restriction_end, $min_order_start_time, $interval_deviation, $loop_key == 3 ? true : false);
                // dd($modified_orders);
                $scheduled_orders = array_merge($scheduled_orders, $modified_orders);

                $availabilities = self::initializeSchedule($user_id, $company, $schedule_date, $transit_mixer_ids, $pump_ids, $batching_plant_ids, $shift_start, $shift_end, $interval_deviation, $scheduled_orders, true);
                $pumps_availabilty = $availabilities['pumps_availability'];
                $tms_availabilty = $availabilities['tms_availability'];
                $bps_availabilty = BatchingPlantHelper::generateOrUpdateAvailability($user_id, $schedule_date, $company, $min_order_start_time, $shift_end);

                $orders = SelectedOrder::where("group_company_id", $company)->where("user_id", $user_id)->whereBetween("delivery_date", [$shift_start, $shift_end])->whereNull("start_time")
                    ->where("selected", true)
                    ->get()
                    ->toArray();

                if ($orders && count($orders) == 0)
                {
                    break;
                }
            }
        }
        catch(\Exception $e)
        {
            dd($e);
        }
    }

    public static function getMinTimeWithinOrders(Collection $orders, Carbon $currentTime) : Carbon
    {
        $orders = $orders->sortBy([['min_start_timestamp', 'asc'], ]);
        if (Carbon::parse($orders->first() ['min_start_timestamp'])
            ->lte(Carbon::parse($currentTime)))
        {
            return $currentTime;
        }
        else
        {
            return Carbon::parse($orders->first() ['min_start_timestamp']);
        }
    }

    public static function getOrderMaxPossibleDeviation(int $deviation)
    {
        if ($deviation >= 0 && $deviation <= 20)
        {
            return 20;
        }
        else if ($deviation >= 21 && $deviation <= 60)
        {
            return 60;
        }
        else if ($deviation >= 61 && $deviation <= 180)
        {
            return 180;
        }
        else if ($deviation >= 181 && $deviation <= 240)
        {
            return 240;
        }
        else
        {
            return 1440;
        }
    }

    public static function generateLiveSchedule(int $companyId, string $scheduleDate)
    {
        DB::beginTransaction();

        try
        {
            $shift_timings = GroupCompanyHelper::getShiftTime($companyId, $scheduleDate);

            $currentLiveSchedule = LiveOrder::with("schedule", "pump_schedule")->where('group_company_id', $companyId)->get(); // Need to change
            if ($currentLiveSchedule->count() > 0)
            { // Old live schedule exits
                foreach ($currentLiveSchedule as $currLiveSchedule)
                {
                    foreach ($currLiveSchedule->schedule as $liveSch)
                    {
                        foreach ($liveSch->reports as $liveReport)
                        {
                            $liveReport->delete();
                        }
                        foreach ($liveSch->rejections as $liveRejections)
                        {
                            $liveRejections->delete();
                        }
                        $liveSch->delete();
                    }
                    foreach ($currLiveSchedule->pump_schedule as $livePumpSch)
                    {
                        $livePumpSch->delete();
                    }
                    $currLiveSchedule->delete();
                }
            }
            $publishedSchedule = Order::select('id', 'structural_reference_id', 'customer_id', 'project_id', 'cust_product_id', 'is_technician_required', 'company_location_id', 'site_id', 'group_company_id', 'order_no', 'customer', 'project', 'site', 'mix_code', 'quantity', 'delivery_date', 'interval', 'pump', 'pump_qty', 'location', 'travel_to_site', 'return_to_plant', 'start_time AS planned_start_time', 'end_time AS planned_end_time', 'deviation AS planned_deviation')->ByCompanyScheduleDate($companyId, $shift_timings['start_time'], $shift_timings['end_time'])->with('pump_schedule', 'schedule')
                ->whereNotNull('published_by')
                ->get();

            $publishedScheduleCollection = $publishedSchedule; // To make orders inactive
            $publishedSchedule = $publishedSchedule->makeHidden(['created_at', 'updated_at', 'deleted_at', 'published_by', 'interval_deviation', 'deviation_reason'])
                ->toArray();
            $firestoreLiveOrderTripsDoc = collect([]);
            foreach ($publishedSchedule as $pubScheduleKey => $pubSchedule)
            {
                $currentSchedule = $pubSchedule['schedule'];
                $currentPumpSchedule = $pubSchedule['pump_schedule'];

                unset($pubSchedule['schedule']);
                unset($pubSchedule['pump_schedule']);
                $pubSchedule['og_order_id'] = $pubSchedule['id'];
                unset($pubSchedule['id']);

                $currentOrder = LiveOrder::create($pubSchedule);

                foreach ($currentSchedule as & $subPubSch)
                {
                    $subPubSch['order_id'] = $currentOrder->id;
                    LiveOrderSchedule::insert($subPubSch);
                    $firestoreLiveOrderTripsDoc->push($subPubSch);
                }
                foreach ($currentPumpSchedule as & $subPubSchPump)
                {
                    $subPubSchPump['order_id'] = $currentOrder->id;
                    LiveOrderPumpSchedule::insert($subPubSchPump);
                }

                $publishedScheduleCollection[$pubScheduleKey]->status = ConstantHelper::INACTIVE;
                $publishedScheduleCollection[$pubScheduleKey]->save();
            }
            DB::commit();
            // $firestore = new FirestoreHelper();
            // $firestore -> createBulkDocuments($firestoreLiveOrderTripsDoc);
            
        }
        catch(Exception $ex)
        {
            DB::rollBack();
            dd($ex->getMessage() . ' at line -> ' . $ex->getLine() . " in file " . $ex->getFile());
        }

    }
}

