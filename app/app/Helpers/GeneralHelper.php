<?php

namespace App\Helpers;

use App\Models\GroupCompany;
use App\Models\SelectedOrder;
use App\Models\Pump;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GeneralHelper
{
    public static function get_travel_time(string $company, string $location, string $site, string $endTime): int
    {
        $travelTime = 20; //Default assignment
        return $travelTime;

        $formattedEndTime = Carbon::parse($endTime)->format(ConstantHelper::DATE_TIME_FORMAT);

        foreach (ConstantHelper::TIME_RANGES as $timeRange => $range) {
            $start = Carbon::parse($formattedEndTime)->setTimeFrom($range[0]);
            $end = Carbon::parse($formattedEndTime)->setTimeFrom($range[1]);

            if ($formattedEndTime >= $start && $formattedEndTime <= $end) {
                $result = DB::table('company_locations')
                    ->where('group_company_id', $company)
                    ->where('location', $location)
                    ->where('site_name', $site)
                    ->value('time_' . $timeRange);

                $travelTime = $result !== null ? $result : 30;
                break;
            }
        }
        
    }

    public static function get_order_start_time(string $company, int $order_no): string
    {
        //Default values (in mins)
        $qc_time = 5; // Quality check 
        $insp_time = 10; // Inspection Time 
        $location = "";
        $site = "";
        $delivery_date = "";

        $order = DB::table("selected_orders")->select("location", "site", "delivery_date",)
            ->where("group_company_id", $company)->where("order_no", $order_no)->first();
        $loading_time = DB::table("transit_mixers")->select("loading_time")
            ->orderByDesc("truck_capacity")->first();

        if (isset($order)) {
            $location = $order->location;
            $site = $order->site;
            $delivery_date = $order->delivery_date;
        }

        $delivery_date = Carbon::parse($delivery_date);
        //Create a copy instance for supplying to function
        $delivery_date_copy = $delivery_date->copy()->subMinutes($qc_time);
        $travel_time = self::get_travel_time($company, $location, $site, $delivery_date_copy);
        $total_time = (isset($loading_time) ? $loading_time->loading_time : 0) + ($qc_time) + $travel_time + $insp_time;
        $start_time = $delivery_date->copy()->subMinutes($total_time);

        return Carbon::parse($start_time)->format(ConstantHelper::DATE_TIME_FORMAT);
    }

    public static function order_schedule_log(string $company, string $schedule_date, int $order_no, string $delv_date, int $p_trip, int $quantity, string $load_start_date, string $load_end_date, string $pour_start_date, string $return_end, string $reason, string $location): array
    {
        return [
            'group_company_id' => $company,
            'schedule_date' => $schedule_date,
            'order_no' => $order_no,
            'location' => $location,
            'delivery_date' => $delv_date,
            'trip' => $p_trip,
            'loading_start' => $load_start_date,
            'loading_end' => $load_end_date,
            'pouring_start' => $pour_start_date,
            'return_end' => $return_end,
            'reason' => $reason
        ];
    }

    public static function generate_schedule_v1(string $company, string $schedule_date)
    {

        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", "pump_qty")->
            where("group_company_id", $company)->whereDate("delivery_date", Carbon::parse($schedule_date))->get()->sortBy("expected_start_time") -> values();

        $qc_time = 5;
        $insp_time = 5;
        $cleaning_time = 5;
        $batching_qty = 0;

        $truck_capacities = DB::table("transit_mixer_availablity")->select("truck_capacity")->distinct()->where("group_company_id", $company)->orderByDesc("truck_capacity")->get();

        $logs = [];

        foreach ($orders as $order) {

            DB::beginTransaction();

            $pouring_time = $order -> pouring_time;
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = 30;
            }

            if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }

            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $qty = $order->quantity;
            $trip = 1;

            $loading_time = 0;
            $pouring_end_1 = "";

            $delivery_date = "";
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;

            $location = "";

            //Trips Loop
            while ($qty > 0) {

                foreach ($truck_capacities as $truck_capacity) {

                    $transit_mixer = null;

                    $loading_time = DB::table("transit_mixer_availablity")->where("group_company_id", $company)->where("truck_capacity", $truck_capacity->truck_capacity)->max("loading_time");

                    //First Trip
                    if ($trip == 1) {
                        $delivery_date = $order->delivery_date;
                        //Subsequent Trips
                    } else {
                        $delivery_date = $pouring_end_1 -> copy() -> subMinutes($pouring_interval) -> addMinute();                        
                    }
                    $delivery_date = Carbon::parse($delivery_date);

                    $travel_time = self::get_travel_time($company, $order->location, $order->site, $delivery_date->copy()->subMinutes($qc_time));
                    $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                    $loading_start = $delivery_date->copy()->subMinutes($total_time);

                    $loading_end = $loading_start->copy()->addMinutes($loading_time)-> subMinute();

                    $qc_start = $loading_end -> copy() -> addMinute();
                    $qc_end = ($qc_start->copy()->addMinutes($qc_time)) -> subMinute();

                    $travel_start = $qc_end -> copy() -> addMinute();
                    $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                    $insp_start = $travel_end -> copy() -> addMinute();
                    $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                    $pouring_start = $insp_end -> copy() -> addMinute();
                    $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                    $cleaning_start = $pouring_end -> copy() -> addMinute();
                    $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                    $return_time = $travel_time;
                    $return_start = $cleaning_end -> copy() -> addMinute();
                    $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();

                    $deviation = ($pouring_start->copy())->diffInMinutes($delivery_date);

                    $location = $order->location;

                    $truck_cap = $truck_capacity->truck_capacity;

                    $truck = self::get_available_trucks($company, $truck_cap, $loading_start, $return_end, $location);
                    $truck = isset($truck) ? $truck : self::get_available_trucks($company, $truck_cap, $loading_start, $return_end);
                    $transit_mixer = $truck;

                    if (!isset($transit_mixer)) {
                        continue;
                    }

                    if (isset($order -> pump)) {
                        $pump = self::get_available_pumps($company, $qc_start, $pouring_start, $pouring_end, $order->pump, $order -> order_no, $trip);
                        $pouring_pump = $pump;
                    }

                    if ($trip > 1) {
                        $plant = self::get_available_batching_plants($company, $location, $loading_start, $loading_end, $batching_plant);
                        $batching_plant = $plant;
                    }

                    if (!isset($batching_plant)) {
                        $plant = self::get_available_batching_plants($company, $location, $loading_start, $loading_end);
                        $batching_plant = $plant;
                    }

                    if (!isset($batching_plant)) {
                        //LOGIC TO BE ADDED
                    }

                    //Both truck and plant assigned
                    if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump == null && $order -> pump == null)) && isset($transit_mixer) && isset($batching_plant)) {
                        
                        $pouring_end_1 = $pouring_end;
                        //Update and insert into PA
                        if (isset($order -> pump)) {
                            if ($trip > 1) {
                                DB::table("pump_availability")->where("id", $pouring_pump->id)
                                    ->update([
                                        'free_upto' => $pouring_start->copy()->subMinute(),
                                        'location' => $location
                                    ]);
    
                                DB::table("pump_availability")->insertGetId([
                                    'group_company_id' => $company,
                                    'pump_name' => $pouring_pump->pump_name,
                                    'pump_capacity' => $pouring_pump->pump_capacity,
                                    'free_from' => $pouring_end->copy()->addMinute(),
                                    'free_upto' => $pouring_pump->free_upto,
                                    'location' => $order->location
                                ]);    
                                
                            } else {
                                DB::table("pump_availability")->where("id", $pouring_pump->id)
                                    ->update([
                                        'free_upto' => $qc_start->copy()->subMinute(),
                                        'location' => $location
                                    ]);
    
                                DB::table("pump_availability")->insertGetId([
                                    'group_company_id' => $company,
                                    'pump_name' => $pouring_pump->pump_name,
                                    'pump_capacity' => $pouring_pump->pump_capacity,
                                    'free_from' => $pouring_end->copy()->addMinute(),
                                    'free_upto' => $pouring_pump->free_upto,
                                    'location' => $order->location
                                ]);
        
                            }
                        }
                        
                        //Update and insert into TM 
                        DB::table("transit_mixer_availablity")->where("id", $transit_mixer->id)
                            ->update([
                                'free_upto' => $loading_start->copy()->subMinute(),
                                'location' => $location
                            ]);

                        DB::table("transit_mixer_availablity")->insert([
                            'group_company_id' => $company,
                            'truck_name' => $transit_mixer->truck_name,
                            'truck_capacity' => $truck_capacity->truck_capacity,
                            'loading_time' => $loading_time,
                            'free_from' => $return_end->copy()->addMinute(),
                            'free_upto' => $transit_mixer->free_upto,
                            'location' => $order->location
                        ]);

                        //Update and insert into BP 
                        DB::table("batching_plant_availablity")->where("id", $batching_plant->id)
                            ->update([
                                'free_upto' => $loading_start->copy()->subMinute(),
                            ]);

                        DB::table("batching_plant_availablity")->insert([
                            'group_company_id' => $company,
                            'location' => $order->location,
                            'plant_name' => $batching_plant->plant_name,
                            'plant_capacity' => $batching_plant->plant_capacity,
                            'free_from' => $loading_end->copy()->addMinute(),
                            'free_upto' => $batching_plant->free_upto,
                        ]);

                        $batching_qty = min([$truck_capacity->truck_capacity, $qty]);
                        break;
                    }

                } //End Truck Loop

                if (!isset($transit_mixer)) {
                    $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Transit Mixer not available', $location);
                    break;
                } else if (!isset($batching_plant)) {
                    $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Batching Plant not available', $location);
                    break;
                } else if (!isset($pouring_pump) && isset($order -> pump)) {
                    self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Pump not available', $location);
                    break;
                } else {
                    DB::table('selected_order_schedules')->insert([
                        'group_company_id' => $company,
                        'schedule_date' => $schedule_date,
                        'order_no' => $order->order_no,
                        'location' => $location,
                        'trip' => $trip,
                        'pump' => isset($pouring_pump) ? $pouring_pump -> pump_name : null,
                        'batching_plant' => $batching_plant->plant_name,
                        'transit_mixer' => $transit_mixer->truck_name,
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
                        'deviation' => $deviation,
                    ]);
                    if (isset($order -> pump)) {
                        $updated = DB::table('selected_order_pump_schedules')->where([
                            ['group_company_id', $company],
                            ['schedule_date', $schedule_date],
                            ['order_no', $order->order_no],
                            ['pump', $pouring_pump->pump_name],
                            ['location', $location]
                        ])->increment('pouring_time', $pouring_time, [
                                'pouring_end' => $pouring_end,
                                'return_time' => $return_time,
                                'return_start' => $return_start,
                                'return_end' => $return_end,
                            ]);
    
                        if ($updated == 0) {
                            DB::table('selected_order_pump_schedules')->insert([
                                'group_company_id' => $company,
                                'schedule_date' => $schedule_date,
                                'order_no' => $order->order_no,
                                'pump' => $pouring_pump->pump_name,
                                'location' => $location,
                                'trip' => $trip,
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
                            ]);
                        }
                    }
                    
                    $qty = $qty - $batching_qty;
                    $trip += 1;

                }
            } //End Loop Trips

            if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump == null && $order -> pump == null)) && isset($transit_mixer) && isset($batching_plant)) {

                $update_order = DB::table('selected_orders as A')
                    ->where('id', $order->id)
                    ->update([
                        'start_time' => DB::table('selected_order_schedules as B')
                            ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                            ->where('group_company_id', $company)
                            ->where('order_no', $order->order_no)
                            ->first()->min_pour,

                        'end_time' => DB::table('selected_order_schedules as B')
                            ->select(DB::raw('MAX(pouring_start) AS max_pour'))
                            ->where('group_company_id', $company)
                            ->where('order_no', $order->order_no)
                            ->first()->max_pour,
                    ]);

                $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes($order_deviation->start_time);
                DB::table("selected_orders")->where("id", $order->id)->update([
                    'deviation' => $order_deviation
                ]);
                //COMMIT
                DB::table("order_schedule_logs")->insert($logs);
                DB::commit();
            } else {

                DB::rollBack();
            }
        } //Orders Loop End
    }

    public static function get_available_trucks($trucks, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $location = null)
    {
        $data = null;
        $min_date = $return_end;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
            $min_date = $location_end_time;
        }
        // $data = array_filter($trucks, function ($element) use ($company, $loading_start, $return_end, $truck_cap) {
        //     if ($element['group_company_id'] == $company && $element['truck_capacity'] == $truck_cap 
        //     && Carbon::parse($element['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($element['free_from']) -> lte(Carbon::parse($return_end))
        //     && Carbon::parse($element['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($element['free_upto']) -> gte(Carbon::parse($return_end))
        //     ) {
        //         if (isset($location)) {
        //             if ($element['location'] == $location) {
        //                 return true;
        //             } else {
        //                 return false;
        //             }
        //         } else {
        //             return true;
        //         }
        //     } else {
        //         return false;
        //     }
        // });
        foreach ($trucks as $truck) {
            if ($truck['group_company_id'] == $company && $truck['truck_capacity'] == $truck_cap 
            && Carbon::parse($truck['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_from']) -> lte($min_date)
            && Carbon::parse($truck['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_upto']) -> gte($min_date)
            && ( (Carbon::parse($truck['restriction_start']) -> gt(Carbon::parse($loading_start)) && Carbon::parse($truck['restriction_start']) -> gt(Carbon::parse($min_date)))
            ||  ( Carbon::parse($truck['restriction_end']) -> lt(Carbon::parse($loading_start)) && Carbon::parse($truck['restriction_end']) -> lt($min_date)) )
            ) {
                if (isset($location)) {
                    if ($truck['location'] == $location) {
                        $data = $truck;
                        break;
                    }
                } else {
                    $data = $truck;
                    break;
                }
            }
        }
        // $data = DB::table("transit_mixer_availablity")->select("id", "truck_name", "free_from", "free_upto")
        //     ->where("group_company_id", $company)
        //     ->where("truck_capacity", $truck_cap)
        //     ->where("free_from", "<=", $loading_start)
        //     ->where("free_upto", ">=", $loading_start)
        //     ->where("free_from", "<=", $return_end)
        //     ->where("free_upto", ">=", $return_end);
        // if (isset($location)) {
        //     $data = $data->where("location", $location);
        // }
        // else {
        //     $data = $data -> whereNull("location");
        // }
        // if (count($data) > 0) {
        //     return current($data);
        // } else {
        //     return null;
        // }
        return $data;
    }

    public static function get_available_pumps($pumps, $pump_ids, $company, $qc_start, $pouring_start, $pouring_end, $return_end, $pump_cap, $order_no, $trip, $schedules, $location_end_time, $qty, $truck_capacity)
    {
        $data = null;
        $index = null;
        $min_end_date = "";
        $min_start_date = $pouring_start;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($pouring_start))) {
            $min_start_date = $location_end_time;
        }
        if ($qty - min([$qty, $truck_capacity]) > 0) { // Middle Trip
            $min_end_date = $pouring_end;
            if (Carbon::parse($location_end_time) -> lte(Carbon::parse($pouring_end))) {
                $min_end_date = $location_end_time;
            }
        } else { // Last trip 
            $min_end_date = $return_end;
            if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
                $min_end_date = $location_end_time;
            }
        }

         if (count($pump_ids) > 0) {
            foreach ($pumps as $pumpKey => $pump) {
                if ($pump['group_company_id'] == $company 
                // && Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date)
                && Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)
                && in_array($pump['pump_name'], $pump_ids)) {
                    $data = $pump;
                    $index = $pumpKey;
                    break;
                }
            }
            // $data = DB::table("pump_availability")->select("id", "pump_name", "free_from", "free_upto", "pump_capacity")
            // ->where("group_company_id", $company)
            // ->where("pump_capacity", $pump_cap)
            // ->where("free_from", "<=", $pouring_start)
            // ->where("free_upto", ">=", $pouring_start)
            // ->where("free_from", "<=", $pouring_end)
            // ->where("free_upto", ">=", $pouring_end)
            // ->whereIn("pump_name", $pump_ids)->first();
         }

        //  if ($trip == 5 && $order_no == 6) {
        //     dd($pump_ids, $data, $min_end_date, $min_start_date, $pouring_end);
        // }

        

        $min_start_date = $qc_start;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($qc_start))) {
            $min_start_date = $location_end_time;
        }
        if ($qty - min([$qty, $truck_capacity]) > 0) { //Middle Trip
            $min_end_date = $pouring_end;
            if (Carbon::parse($location_end_time) -> lte(Carbon::parse($pouring_end))) {
                $min_end_date = $location_end_time;
            }
        } else { // Last trip
            $min_end_date = $return_end;
            if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
                $min_end_date = $location_end_time;
            }
        }
        
        //Check for other
        if (!isset($data)) {
            foreach ($pumps as $pumpKey => $pump) {
                if ($pump['group_company_id'] == $company 
                && Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date)
                && Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)
                ) {
                    $data = $pump;
                    $index = $pumpKey;
                    break;
                }
            }
            // $data = DB::table("pump_availability")->select("id", "pump_name", "free_from", "free_upto", "pump_capacity")
            // ->where("group_company_id", $company)
            // ->where("pump_capacity", $pump_cap)
            // ->where("free_from", "<=", $qc_start)
            // ->where("free_upto", ">=", $qc_start)
            // ->where("free_from", "<=", $pouring_end)
            // ->where("free_upto", ">=", $pouring_end) -> first();
        }
        if (isset($data) && isset($index)) {
            return ['pump' => $data, 'index' => $index];
        } else {
            return null;
        }
    }

    public static function get_available_batching_plants($batching_plants, $company, $location, $loading_start, $loading_end, $plant_name = null)
    {
        // $data = null;
        // foreach ($batching_plants as $batching_plant) {
        //     if ($batching_plant['group_company_id'] == $company && $batching_plant['location'] == $location
        //     && Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_end))
        //     && Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_end))
        //     ) {
        //         if (isset($plant_name)) {
        //             if ($batching_plant['plant_name'] == $plant_name['plant_name']) {
        //                 $data =  $batching_plant;
        //                 break;
        //             }
        //         } else {
        //             $data =  $batching_plant;
        //             break;
        //         }
        //     }
        // }
        // return $data;
        $data = null;
        $index = null;
        foreach ($batching_plants as $batching_plant_key => $batching_plant) {
            if ($batching_plant['group_company_id'] == $company && $batching_plant['location'] == $location
            && Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_end))
            && Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_end))
            && (Carbon::parse($batching_plant['restriction_start']) -> gt(Carbon::parse($loading_start)) || Carbon::parse($batching_plant['restriction_end']) -> lt(Carbon::parse($loading_start)))
            ) {
                if (isset($plant_name)) {
                    if ($batching_plant['plant_name'] == $plant_name['plant_name']) {
                        $data =  $batching_plant;
                        $index =  $batching_plant_key;
                        break;
                    }
                } else {
                    $data =  $batching_plant;
                    $index =  $batching_plant_key;
                    break;
                }
            }
        }
        return $data;
    }

    public static function generate_schedule_v3(string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, bool $customer_timeline = false)
    {
        $qc_time = 5;
        $insp_time = 5;
        $cleaning_time = 5;
        $batching_qty = 0;

        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", 
            "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", 
            "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)
            ->whereDate("delivery_date", Carbon::parse($schedule_date))
            ->whereNull("start_time") -> where("selected", true)
            -> get() -> sortBy([
                ['priority', 'asc'],
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();

        $truck_capacities = DB::table("transit_mixer_availablity")->select("truck_capacity")->distinct()->where("group_company_id", $company)->orderByDesc("truck_capacity")->get();

        foreach ($orders as $order) { // Order loop start

            $pouring_time = 0;

            if ($order -> pouring_time > $order -> interval) {
                $pouring_time = $order -> pouring_time;
            } else {
                $pouring_time = $order -> interval;
            }
            
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = 30;
            }

            if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }

            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $loading_time = 0;
            $pouring_end_1 = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = $order -> delivery_date;
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;

            $location = "";

            $sch_adj_time = 0;

            // $shift_location = null;

            //Get Locations availability
            $locations = DB::table("batching_plant_availablity")->select("location")->distinct()->where("group_company_id", $company)->orderByRaw("FIELD(location, '" . $order->location . "') DESC")->get();

            foreach ($locations as $loc) {
                $location = $loc->location;
                $location_start_time = Carbon::parse(Carbon::parse($order -> delivery_date) -> format("Y-m-d") . " 08:00");
                $plant_availability = DB::table("batching_plant_availablity")->select("id", "plant_name", "plant_capacity", "free_from", "free_upto")
                    ->where("group_company_id", $company)
                    ->where("location", $location)
                    ->where("free_upto", ">=", "free_from") -> orderBy("free_from") -> first() ?-> free_from;

                $delivery_time = $customer_timeline == false ? (isset($order -> priority) && $order -> priority < 999 ? $order -> delivery_date : $plant_availability) : $delivery_date;
                $location_end_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> addDay() -> format("Y-m-d") . " 07:59");

                // $shift_location = self::get_available_locations($company, $location);

                $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);

                // if ($delivery_date_p -> lt(Carbon::parse($schedule_date . $shift_location -> shift_start)) && $delivery_date_n -> gt(Carbon::parse($schedule_date . $shift_location -> shift_end))) {
                //     continue;
                // }

                $avl = 0;
                //Schedule adjustment based on availability LOOP
                while ($avl == 0) {
                    $to_from_array = [1, 2];
                    //Forward backward adjustment loop
                    foreach ($to_from_array as $val) {

                        $transit_mixer = null;
                        $pouring_pump = null;
                        // $batching_plant = null;
                        $qty = $order->quantity;
                        $trip = 1;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0) {

                            foreach ($truck_capacities as $truck_capacity) {

                                $transit_mixer = null;
                                $loading_time = DB::table("transit_mixer_availablity")->where("group_company_id", $company)->where("truck_capacity", $truck_capacity->truck_capacity)->max("loading_time");

                                //First Trip
                                if ($trip == 1) {
                                    if ($sch_adj_time == 0) {
                                        // $delivery_date = $order->delivery_date;
                                        $delivery_date = $delivery_time;
                                    } else {
                                        
                                        if ($val == 1) {
                                            $delivery_date = $delivery_date_n;
                                        } else {
                                            $delivery_date = $delivery_date_p;
                                        }                                        
                                    }
                                    //Subsequent Trips
                                } else {
                                    $delivery_date = $pouring_end_1 -> copy() -> subMinutes($pouring_interval) -> addMinute();
                                }
                                $delivery_date = Carbon::parse($delivery_date);
                                

                                // if ($delivery_date -> lt(Carbon::parse($schedule_date . $shift_location -> shift_start)) || $delivery_date -> gt(Carbon::parse($schedule_date . $shift_location -> shift_end))) {
                                //     $avl = 0;
                                //     continue;
                                // }
                                

                                // $travel_time = self::get_travel_time($company, $location, $order->site, $delivery_date->copy()->subMinutes($qc_time));
                                $travel_time = $order -> travel_to_site;
                                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                                $loading_start = $delivery_date->copy()->subMinutes($total_time);

                                $loading_end = $loading_start->copy()->addMinutes($loading_time) -> subMinute();
                                
                                $qc_start = $loading_end -> copy() -> addMinute();
                                $qc_end = $qc_start->copy()->addMinutes($qc_time) -> subMinute();

                                $travel_start = $qc_end -> copy() -> addMinute();
                                $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                                $insp_start = $travel_end -> copy() -> addMinute();
                                $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                                $pouring_start = $insp_end -> copy() -> addMinute();
                                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                                $cleaning_start = $pouring_end -> copy() -> addMinute();
                                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                                $return_time = $order -> return_to_plant;
                                $return_start = $cleaning_end -> copy() -> addMinute();
                                $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();

                                $deviation = ($pouring_start->copy())->diffInMinutes($order->delivery_date);

                                $truck_cap = $truck_capacity->truck_capacity;
                                
                                $truck = self::get_available_trucks($company, $truck_cap, $loading_start, $return_end, $location);
                                $truck = isset($truck) ? $truck : self::get_available_trucks($company, $truck_cap, $loading_start, $return_end);
                                $transit_mixer = $truck;

                                
                                if (isset($order -> pump)) {
                                    $pump = self::get_available_pumps($company, $qc_start, $pouring_start, $pouring_end, $order->pump, $order -> order_no, $trip);
                                    $pouring_pump = $pump;
                                }


                                if (!isset($transit_mixer)) {
                                    continue;
                                }
                               

                                if ($trip > 1) {
                                    $plant = self::get_available_batching_plants($company, $location, $loading_start, $loading_end, $batching_plant);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    $plant = self::get_available_batching_plants($company, $location, $loading_start, $loading_end);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    //LOGIC TO BE ADDED
                                }

                                //Both truck and plant assigned
                                if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {

                                    $pouring_end_1 = $pouring_end;
                                    if (isset($order -> pump)) {
                                        if ($trip > 1) {
                                            DB::table("pump_availability")->where("id", $pouring_pump->id)
                                                ->update([
                                                    'free_upto' => $pouring_start->copy()->subMinute(),
                                                    'location' => $location
                                                ]);
                
                                            $p_id = DB::table("pump_availability")->insertGetId([
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump->pump_name,
                                                'pump_capacity' => $pouring_pump->pump_capacity,
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump->free_upto,
                                                'location' => $location
                                            ]);
                
                                            // $pouring_pump = DB::table("pump_availability") -> where("id", $p_id) -> first();
                
                                            
                                        } else {
                                            DB::table("pump_availability")->where("id", $pouring_pump->id)
                                                ->update([
                                                    'free_upto' => $qc_start->copy()->subMinute(),
                                                    'location' => $location
                                                ]);
                
                                            $p_id = DB::table("pump_availability")->insertGetId([
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump->pump_name,
                                                'pump_capacity' => $pouring_pump->pump_capacity,
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump->free_upto,
                                                'location' => $location
                                            ]);
                
                                            // $pouring_pump = DB::table("pump_availability") -> where("id", $p_id) -> first();
                
                                        }
                                    }

                                    //Update and insert into TM 
                                    DB::table("transit_mixer_availablity")->where("id", $transit_mixer->id)
                                        ->update([
                                            'free_upto' => $loading_start->copy()->subMinute(),
                                            'location' => $location
                                        ]);

                                    DB::table("transit_mixer_availablity")->insert([
                                        'group_company_id' => $company,
                                        'truck_name' => $transit_mixer->truck_name,
                                        'truck_capacity' => $truck_capacity->truck_capacity,
                                        'loading_time' => $loading_time,
                                        'free_from' => $return_end->copy()->addMinute(),
                                        'free_upto' => $transit_mixer->free_upto,
                                        'location' => $location
                                    ]);

                                    //Update and insert into BP 
                                    DB::table("batching_plant_availablity")->where("id", $batching_plant->id)
                                        ->update([
                                            'free_upto' => $loading_start->copy()->subMinute(),
                                        ]);

                                    DB::table("batching_plant_availablity")->insert([
                                        'group_company_id' => $company,
                                        'location' => $location,
                                        'plant_name' => $batching_plant->plant_name,
                                        'plant_capacity' => $batching_plant->plant_capacity,
                                        'free_from' => $loading_end->copy()->addMinute(),
                                        'free_upto' => $batching_plant->free_upto,
                                    ]);

                                    $batching_qty = min([$truck_capacity->truck_capacity, $qty]);
                                    break;
                                }

                            } //End Truck Loop

                            if (!isset($transit_mixer)) {
                                $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Transit Mixer not available', $location);
                                break;
                            } else if (!isset($batching_plant)) {
                                $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Batching Plant not available', $location);
                                break;
                            } else if (!isset($pouring_pump) && isset($order -> pump)) {
                                self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Pump not available', $location);
                                break;
                            } else {
                                DB::table('selected_order_schedules')->insert([
                                    'group_company_id' => $company,
                                    'schedule_date' => $schedule_date,
                                    'order_no' => $order->order_no,
                                    'pump' => isset($pouring_pump) ? $pouring_pump->pump_name : null,
                                    'location' => $location,
                                    'trip' => $trip,
                                    'batching_plant' => $batching_plant->plant_name,
                                    'transit_mixer' => $transit_mixer->truck_name,
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
                                    'deviation' => $deviation,
                                ]);
                                if (isset($order -> pump)) {
                                    $updated = DB::table('selected_order_pump_schedules')->where([
                                        ['group_company_id', $company],
                                        ['schedule_date', $schedule_date],
                                        ['order_no', $order->order_no],
                                        ['pump', $pouring_pump->pump_name],
                                        ['location', $location]
                                    ])->increment('pouring_time', $pouring_time, [
                                            'pouring_end' => $pouring_end,
                                            'return_time' => $return_time,
                                            'return_start' => $return_start,
                                            'return_end' => $return_end,
                                        ]);
                
                                    if ($updated == 0) {
                                        DB::table('selected_order_pump_schedules')->insert([
                                            'group_company_id' => $company,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => $pouring_pump->pump_name,
                                            'location' => $location,
                                            'trip' => $trip,
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
                                        ]);
                                    }
                                }

                                $qty = $qty - $batching_qty;
                                $trip += 1;
                            }
                        } //End Loop Trips
                        if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {

                            $update_order = DB::table('selected_orders as A')
                                ->where('id', $order->id)
                                ->update([
                                    'start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->max_pour,
                                    'location' => $location
                                ]);

                            $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                            $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
                            DB::table("selected_orders")->where("id", $order->id)->update([
                                'deviation' => $order_deviation
                            ]);
                            //COMMIT
                            // DB::table("order_schedule_logs")->insert($logs);
                            DB::commit();

                            $avl = 1;
                            break;
                        } else {
                            DB::rollBack();
                            if ($sch_adj_time == 0) {
                                $avl = 0;
                                break;
                            }
                        }

                    } //End Forward backward adjustment loop

                    $sch_adj_time += 1;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                    $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);


                    if ($delivery_date_p->copy()->lt(Carbon::parse($delivery_date)->copy()->subMinutes($sch_adj_to)) && $delivery_date_n->copy()->gt(Carbon::parse($delivery_date)->copy()->addMinutes($sch_adj_to))) {
                        $avl = 1;
                    }
                    if ($delivery_date_p->copy()->lt($delivery_time) && $delivery_date_n->copy()->gt($location_end_time)) {
                        $avl = 1;
                    }

                    if ($avl == 1) {
                        break;
                    }
                } //Schedule adjustment based on availability LOOP END
                if (isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null))) {
                    break;
                }
            } //Location loops end
        } //Orders Loop End
    }

    public static function generate_schedule_v4(string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty,  string $schedule_preference, $restriction_start, $restriction_end)
    {
        $qc_time = 5;
        $insp_time = 5;
        $cleaning_time = 5;
        $batching_qty = 0;

        //Order and Pump Schedules
        $selected_order_pump_schedules = [];
        $schedules = [];

        //Availabilities data
        $transit_mixer_availability = $tms_availabilty;
        $pump_availability = $pumps_availabilty;
        $batching_plant_availability = $bps_availabilty;

        $transit_mixer_availability_copy = $tms_availabilty;
        $pump_availability_copy = $pumps_availabilty;
        $batching_plant_availability_copy = $bps_availabilty;

        $company_shift = GroupCompany::find($company);

        $location_start_time = Carbon::parse($schedule_date . ' ' . "08:00");
        $location_end_time = Carbon::parse($schedule_date . ' ' . "07:59") -> copy() -> addDay();
        if (isset($company_shift)) {
                $location_start_time = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_s);
                $location_end_time_1 = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_e);
                $location_end_time = Carbon::parse($location_start_time) -> gt(Carbon::parse($location_end_time_1)) ? $location_end_time_1 -> copy() -> addDay() : $location_start_time -> copy() -> setTimeFromTimeString("11:59");
        }

        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", 
        "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", 
        "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)
        -> whereBetween("delivery_date",  [$location_start_time, $location_end_time])
        ->whereNull("start_time") -> where("selected", true)
        -> get();


        if ($schedule_preference == "largest_qty_first") {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['quantity', 'desc'], //expected_start_time
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        } else {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        }
        

        $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));

        $locations = array_unique(array_column($batching_plant_availability, 'location'));

        foreach ($orders as $order) { // Order loop start
            $pump_ids = [];
            $pouring_time = 0;
            //Assign large values between interval and pouring time
            if ($order -> pouring_time > $order -> interval) {
                $pouring_time = $order -> pouring_time;
            } else {
                $pouring_time = $order -> interval;
            }
            //Divide pouring time acc to pump qty
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = 30;
            } if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }

            //Initialize variables
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $loading_time = 0;
            $pouring_end_1 = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = $order -> delivery_date;
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;

            $location = "";

            $sch_adj_time = 0;




            //Get Locations availability
            $index = array_search($order -> location, $locations);
                
            if ($index !== false) {
                unset($locations[$index]);
                array_unshift($locations, $order -> location);
            }
            //Locations Loop
            foreach($locations as $loc) {
                // if ($order -> location != $locations[$i] && $i < (count($locations) - 1)) { //NEED TO REFACTOR
                //     continue;
                // }
                $location = $loc;
                //Check for first available plant time
                $plant_availability_array = array_filter($batching_plant_availability_copy, function ($item) use($company, $location) {
                    if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from'])) ) {
                        return true;
                    } else {
                        return false;
                    }
                });
                usort($plant_availability_array, function ($a, $b) {
                    return $a['free_from'] <=> $b['free_from'];
                });
                $plant_availability = $plant_availability_array[0]['free_from'];

                $plant_availability_array_copy = [];
                
                $delivery_time = $schedule_preference != "customer_timeline" ? (isset($order -> priority) && $order -> priority < 999 ? $order -> delivery_date : $plant_availability) : $delivery_date;
                // $location_end_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> copy() -> addDay() -> format("Y-m-d") . " 07:59");
                // $location_start_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> format("Y-m-d") . " 08:00");

                $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);

                $avl = 0;
                $restriction_flag = Carbon::parse("2001-01-01 00:00:00");
                //Schedule adjustment based on availability LOOP
                while ($avl == 0) {
                    
                    $to_from_array = [1, 2];
                    //Forward backward adjustment loop
                    foreach ($to_from_array as $val) {
                        
                        $transit_mixer = null;
                        $pouring_pump = null;
                        $batching_plant = null;
                        $qty = $order->quantity;
                        $trip = 1;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0) {

                            foreach ($truck_capacities as $truck_capacity) {
                                $transit_mixer = null;
                                $loading_time = 10;
                                // $loading_time = DB::table("transit_mixer_availablity")->where("group_company_id", $company)->where("truck_capacity", $truck_capacity->truck_capacity)->max("loading_time");

                                //First Trip
                                if ($trip == 1) {
                                    if ($sch_adj_time == 0) {
                                        $delivery_date = $delivery_time;
                                    } else {
                                        if ($val == 1) {
                                            $delivery_date = $delivery_date_n;
                                        } else {
                                            $delivery_date = $delivery_date_p;
                                        }                                        
                                    }
                                    //Subsequent Trips
                                } else {
                                    $delivery_date = $pouring_end_1 -> copy() -> subMinutes($pouring_interval) -> addMinute();
                                }
                                $delivery_date = Carbon::parse($delivery_date);

                                if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                    if ($delivery_date -> gte(Carbon::parse($restriction_start)) && $delivery_date -> lte(Carbon::parse($restriction_end))) {
                                       if (Carbon::parse($restriction_flag) -> notEqualTo(Carbon::parse($restriction_start))) {
                                            $delivery_time = Carbon::parse($restriction_end) -> copy() -> addMinute();
                                            $sch_adj_time  = -1;
                                            $avl = 0;
                                            $restriction_flag = $restriction_start;
                                            break;
                                       }
                                    }
                                }

                                $travel_time = $order -> travel_to_site;
                                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                                $loading_start = $delivery_date->copy()->subMinutes($total_time);

                                $loading_end = $loading_start->copy()->addMinutes($loading_time) -> subMinute();
                                
                                $qc_start = $loading_end -> copy() -> addMinute();
                                $qc_end = $qc_start->copy()->addMinutes($qc_time) -> subMinute();

                                $travel_start = $qc_end -> copy() -> addMinute();
                                $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                                $insp_start = $travel_end -> copy() -> addMinute();
                                $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                                $pouring_start = $insp_end -> copy() -> addMinute();
                                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                                $cleaning_start = $pouring_end -> copy() -> addMinute();
                                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                                $return_time = $order -> return_to_plant;
                                $return_start = $cleaning_end -> copy() -> addMinute();
                                $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();
                               
                                  
                                //Restriction
                                // if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                //     if (($loading_start -> gte(Carbon::parse($restriction_start)) && $loading_start -> lte(Carbon::parse($restriction_end))) 
                                //     || ($return_end -> gte(Carbon::parse($restriction_start)) && $return_end -> lte(Carbon::parse($restriction_end)))) {
                                        
                                //         $min_date = $loading_start;
                                //         if (Carbon::parse($loading_start) -> gt(Carbon::parse($restriction_start))) {
                                //             $min_date = $restriction_start;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $min_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($min_date) -> gt(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });

                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $b['free_from'] <=> $a['free_from'];
                                //         });
                                //         $delivery_date_p = isset($plant_availability_array_copy[0]['free_upto']) ? $plant_availability_array_copy[0]['free_upto'] : $location_start_time -> copy() -> subMinute();
                                //         if (Carbon::parse($delivery_date_p) -> gte(Carbon::parse($min_date))) {
                                //             $delivery_date_p = $location_start_time -> copy() -> subMinute();
                                //         }
                                //         $plant_availability_array_copy = [];

                                //         $max_date = $return_end;
                                //         if (Carbon::parse($return_end) -> lt(Carbon::parse($restriction_end))) {
                                //             $max_date = $restriction_end;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $max_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($max_date) -> lte(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });
                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $a['free_from'] <=> $b['free_from'];
                                //         });
                                //         $delivery_date_n = isset($plant_availability_array_copy[0]['free_from']) ? $plant_availability_array_copy[0]['free_from'] : $location_end_time -> copy() -> addMinute();
                                        
                                //         if (Carbon::parse($delivery_date_n) -> lt(Carbon::parse($max_date))) {
                                //             // $delivery_date_n = $location_end_time -> copy() -> addMinute();
                                //             $delivery_date_n = Carbon::parse($max_date);
                                //         }
                                        
                                //         $interval = (int)((Carbon::parse($delivery_date_n) -> diffInMinutes(Carbon::parse($delivery_date_p)))/2);
                                        
                                //         $sch_adj_time = $interval;

                                //         $delivery_time = Carbon::parse($delivery_date_p) -> copy() -> addMinutes($interval);

                                //         if ($order -> order_no == 11 && $delivery_date_n -> gt("2024-01-27 16:00:00") ) {
                                //             dd($loading_start, $return_end, $delivery_date_n, $delivery_date_p, $delivery_date, $delivery_time, $max_date);
                                //         }
                                //         break;
                                //     }
                                // }
                                
                                $deviation = ($pouring_start->copy())->diffInMinutes($order->delivery_date);

                                if ($trip > 1) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $batching_plant);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    break;
                                }
                                
                                if (isset($order -> pump)) {
                                    $pump = self::get_available_pumps($pump_availability_copy, $pump_ids, $company, $qc_start, $pouring_start, $return_end, $order->pump, $order -> order_no, $trip, $schedules, $location_end_time);
                                    $pouring_pump = $pump;

                                    if (!isset($pouring_pump)) {
                                        break;
                                    }
                                }

                                $truck_cap = (int)$truck_capacity;
                                
                                $truck = self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $location);
                                $truck = isset($truck) ? $truck : self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time);
                                $transit_mixer = $truck;
                                
                                if (!isset($transit_mixer)) {
                                    continue;
                                }

                                //Both truck and plant assigned
                                if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {

                                    $pouring_end_1 = $pouring_end;
                                    if (isset($order -> pump)) {
                                        if ($trip > 1) {
                                            $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $pouring_start->copy()->subMinute();
                                            $pump_availability_copy[$pouring_pump['id']]['location'] = $location;

                                            $pump_availability_copy[] = array(
                                                'id' => end($pump_availability_copy)['id'] + 1,
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location
                                            );
                                            
                                        } else {
                                            $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $qc_start->copy()->subMinute();
                                            $pump_availability_copy[$pouring_pump['id']]['location'] = $location;
                                            
                                            $pump_availability_copy[] = array(
                                                'id' => end($pump_availability_copy)['id'] + 1,
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location
                                            );
                
                                        }
                                    }
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['location'] = $location;

                                    $transit_mixer_availability_copy[] = array(
                                        'id' => end($transit_mixer_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'truck_name' => $transit_mixer['truck_name'],
                                        'truck_capacity' => $truck_capacity,
                                        'loading_time' => $loading_time,
                                        'free_from' => $return_end->copy()->addMinute(),
                                        'free_upto' => $transit_mixer['free_upto'],
                                        'location' => $location
                                    );
                                    
                                    $batching_plant_availability_copy[$batching_plant['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $batching_plant_availability_copy[] = array(
                                        'id' => end($batching_plant_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'location' => $location,
                                        'plant_name' => $batching_plant['plant_name'],
                                        'plant_capacity' => $batching_plant['plant_capacity'],
                                        'free_from' => $loading_end->copy()->addMinute(),
                                        'free_upto' => $batching_plant['free_upto'],
                                    );

                                    $batching_qty = min([$truck_capacity, $qty]);
                                    break;
                                }

                            } //End Truck Loop

                            if (!isset($transit_mixer)) {
                                // $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Transit Mixer not available', $location);
                                break;
                            } else if (!isset($batching_plant)) {
                                // $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Batching Plant not available', $location);
                                break;
                            } else if (!isset($pouring_pump) && isset($order -> pump)) {
                                // self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Pump not available', $location);
                                break;
                            } else {
                                $schedules[] = array(
                                    'group_company_id' => $company,
                                    'schedule_date' => $schedule_date,
                                    'order_no' => $order->order_no,
                                    'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                                    'location' => $location,
                                    'trip' => $trip,
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
                                if (isset($order -> pump)) {
                                    $pump_update = self::searchAndUpdateArray($selected_order_pump_schedules,[
                                        'group_company_id' => $company,
                                        'schedule_date' => $schedule_date,
                                        'order_no' => $order->order_no,
                                        'pump' => $pouring_pump['pump_name'],
                                        'location' => $location
                                    ],
                                    [
                                        'pouring_time' => ['value' => $pouring_time],
                                        'pouring_end' => $pouring_end,
                                        'cleaning_start' => $cleaning_start,
                                        'cleaning_end' => $cleaning_end,
                                        'return_time' => $return_time,
                                        'return_start' => $return_start,
                                        'return_end' => $return_end
                                    ]
                                    );
                                    
                                    if ($pump_update['match'] === false) {
                                        $selected_order_pump_schedules[] = array(
                                            'group_company_id' => $company,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => $pouring_pump['pump_name'],
                                            'location' => $location,
                                            'trip' => $trip,
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
                                        );
                                    } else {
                                        $selected_order_pump_schedules = $pump_update['data'];
                                    }
                                    $pump_ids[] = $pouring_pump['pump_name'];
                                }

                                $qty = $qty - $batching_qty;
                                $trip += 1;
                            }
                        } //End Loop Trips
                        if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {
                            DB::table("selected_order_schedules")->insert($schedules);
                            DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
                            
                            $update_order = DB::table('selected_orders as A')
                                ->where('id', $order->id)
                                ->update([
                                    'start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->max_pour,
                                    'location' => $location,
                                ]);

                            $schedules = [];
                            $selected_order_pump_schedules = [];

                            $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                            $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
                            DB::table("selected_orders")->where("id", $order->id)->update([
                                'deviation' => $order_deviation
                            ]);
                            //COMMIT
                            $transit_mixer_availability = $transit_mixer_availability_copy;
                            $pump_availability = $pump_availability_copy;
                            $batching_plant_availability = $batching_plant_availability_copy;
                            
                            DB::commit();

                            $avl = 1;
                            break;
                        } else {
                            $schedules = [];
                            $selected_order_pump_schedules = [];

                            $transit_mixer_availability_copy = $transit_mixer_availability;
                            $pump_availability_copy = $pump_availability;
                            $batching_plant_availability_copy = $batching_plant_availability;

                            DB::rollBack();
                            
                            if ($sch_adj_time <= 0) {
                                $avl = 0;
                                break;
                            }
                        }
                        $pump_ids = [];

                    } //End Forward backward adjustment loop 
                    
                    $sch_adj_time += 1;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                    $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);
                    
                    if ($delivery_date_p->copy()->lt(Carbon::parse($delivery_date)->copy()->subMinutes($sch_adj_to)) && $delivery_date_n->copy()->gt(Carbon::parse($delivery_date)->copy()->addMinutes($sch_adj_to))) {
                        $avl = 1;
                    }
                    if ($delivery_date_p->copy()->lt($location_start_time) && $delivery_date_n->copy()->gt($location_end_time)) {
                        if ( ( ($delivery_date_p->copy()->subMinutes($total_time)) -> lt($location_start_time) ) &&
                                ( ($delivery_date_n->copy()->subMinutes($total_time))  -> gt($location_end_time) ) ) {
                            $avl = 1;
                        } 
                    }

                    if ($avl == 1) {
                        break;
                    }
                } //Schedule adjustment based on availability LOOP END

                if (isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null))) {
                    break;
                }
            } //Location loops end
        } //Orders Loop End
    }
    public static function generate_schedule_v6(string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty,  string $schedule_preference, $restriction_start, $restriction_end)
    {
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
        
        //Get Company Shift
        $company_shift = GroupCompany::find($company);
        $location_start_time = Carbon::parse($schedule_date . ' ' . ConstantHelper::GROUP_COMP_START_TIME);
        $location_end_time = Carbon::parse($schedule_date . ' ' . ConstantHelper::GROUP_COMP_END_TIME) -> copy() -> addDay();
        if (isset($company_shift)) {
                $location_start_time = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_s);
                $location_end_time_1 = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_e);
                $location_end_time = Carbon::parse($location_start_time) -> gt(Carbon::parse($location_end_time_1)) ? $location_end_time_1 -> copy() -> addDay() : $location_start_time -> copy() -> setTimeFromTimeString(ConstantHelper::DAY_END_TIME);
        }
        //Orders
        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", 
        "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", 
        "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)
        -> whereBetween("delivery_date",  [$location_start_time, $location_end_time])
        ->whereNull("start_time") -> where("selected", true)
        -> get();
        //Order By on preference
        if ($schedule_preference == ConstantHelper::LARGEST_JOB_FIRST_PREF) {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['quantity', 'desc'], //expected_start_time
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        } else {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        }
        
        $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));

        $locations = array_unique(array_column($batching_plant_availability, 'location'));

        foreach ($orders as $order) { // Order loop start
            
            $pouring_time = 0;
            //Assign large values between interval and pouring time
            if ($order -> pouring_time > $order -> interval) {
                $pouring_time = $order -> pouring_time;
            } else {
                $pouring_time = $order -> interval;
            }
            //Divide pouring time acc to pump qty
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = ConstantHelper::POURING_TIME;
            } if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }
            //Initialize variables
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;
            $pump_ids = [];

            $loading_time = 0;
            $pouring_end_prev = "";
            $trip_reset_time = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = $order -> delivery_date;
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;
            $location = "";
            $sch_adj_time = 0;

            //Get Locations availability
            $index = array_search($order -> location, $locations);
            if ($index !== false) {
                unset($locations[$index]);
                array_unshift($locations, $order -> location);
            }
            //Locations Loop
            foreach($locations as $loc) {
                $location = $loc;
                //Check for first available plant time
                // foreach ($batching_plant_availability_copy as $bp_avl_key => $bp_avl_val) {
                //     if ($bp_avl_val['group_company_id'] == $company && Carbon::parse($bp_avl_val['free_upto']) -> gte(Carbon::parse($bp_avl_val['free_from'])) ) {
                //         $plant_availability = $bp_avl_val['free_from'];
                //         break;
                //     }
                // }
                $plant_availability_array = array_filter($batching_plant_availability_copy, function ($item) use($company, $location) {
                    if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from'])) ) {
                        return true;
                    } else {
                        return false;
                    }
                });
                usort($plant_availability_array, function ($a, $b) {
                    return $a['free_from'] <=> $b['free_from'];
                });
                $plant_availability = $plant_availability_array[0]['free_from'];
                //Start the delivery acc to avl time                
                $delivery_time = $schedule_preference != ConstantHelper::CUSTOMER_TIMELINE_PREF ? (isset($order -> priority) && $order -> priority < ConstantHelper::DEFAULT_PRIORITY ? $order -> delivery_date : $plant_availability) : $delivery_date;
                $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);

                $avl = 0;
                $restriction_flag = Carbon::parse(ConstantHelper::DEFAULT_DATE_TIME);
                //Schedule adjustment based on availability LOOP
                while ($avl == 0) {
                    //Forward backward adjustment loop
                    foreach (ConstantHelper::TO_FROM_LOOP as $val) {
                        //Reset resources
                        $transit_mixer = null;
                        $pouring_pump = null;
                        $batching_plant = null;
                        $pump_ids = [];
                        $qty = $order->quantity;
                        $trip = 1;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0) {
                            //Truck Loop
                            foreach ($truck_capacities as $truck_capacity) {
                                $transit_mixer = null;
                                $loading_time = ConstantHelper::LOADING_TIME;
                                //First Trip
                                if ($trip == 1) {
                                    if ($sch_adj_time == 0) {
                                        $delivery_date = $delivery_time;
                                    } else {
                                        if ($val == 1) {
                                            $delivery_date = $delivery_date_n;
                                        } else {
                                            $delivery_date = $delivery_date_p;
                                        }                                        
                                    }
                                //Subsequent Trips
                                } else {
                                    $delivery_date = $pouring_end_prev -> copy() -> subMinutes($pouring_interval) -> addMinute();
                                }
                                $delivery_date = Carbon::parse($delivery_date);

                                //Restriction check
                                if (isset($restriction_start) && isset($restriction_end)) {
                                    if ($delivery_date -> gte(Carbon::parse($restriction_start)) && $delivery_date -> lte(Carbon::parse($restriction_end))) {
                                       if (Carbon::parse($restriction_flag) -> notEqualTo(Carbon::parse($restriction_start))) {
                                            $delivery_time = Carbon::parse($restriction_end) -> copy() -> addMinute();
                                            $sch_adj_time  = -1;
                                            $avl = 0;
                                            $restriction_flag = $restriction_start;
                                            break;
                                       }
                                    }
                                }
                                //Time calculation for activities
                                $travel_time = $order -> travel_to_site;
                                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                                $loading_start = $delivery_date->copy()->subMinutes($total_time);

                                $loading_end = $loading_start->copy()->addMinutes($loading_time) -> subMinute();
                                
                                $qc_start = $loading_end -> copy() -> addMinute();
                                $qc_end = $qc_start->copy()->addMinutes($qc_time) -> subMinute();

                                $travel_start = $qc_end -> copy() -> addMinute();
                                $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                                $insp_start = $travel_end -> copy() -> addMinute();
                                $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                                $pouring_start = $insp_end -> copy() -> addMinute();
                                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                                $cleaning_start = $pouring_end -> copy() -> addMinute();
                                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                                $return_time = $order -> return_to_plant;
                                $return_start = $cleaning_end -> copy() -> addMinute();
                                $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();

                                $deviation = ($pouring_start->copy())->diffInMinutes($order->delivery_date);

                                if ($trip > 1) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $batching_plant);
                                    $batching_plant = $plant;
                                }
                                if (!isset($batching_plant)) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end);
                                    $batching_plant = $plant;
                                }
                                if (!isset($batching_plant)) {
                                    break;
                                }
                                
                                if (isset($order -> pump)) {
                                    $pump = self::get_available_pumps($pump_availability_copy, $pump_ids, $company, $qc_start, $pouring_start, $pouring_end, $return_end, $order->pump, $order -> order_no, $trip, $schedules, $location_end_time, $qty, $truck_capacity);
                                    if (isset($pump)) {
                                        $pouring_pump = $pump['pump'];
                                        $pouring_pump_index = $pump['index'];
                                    } else {
                                        $pouring_pump = null;
                                    }
                                    if (!isset($pouring_pump)) {
                                        break;
                                    }
                                }
                                //Assign current Truck capacity and check AVL
                                $truck_cap = (int)$truck_capacity;
                                $truck = self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $location);
                                $truck = isset($truck) ? $truck : self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time);
                                $transit_mixer = $truck;
                                if (!isset($transit_mixer)) {
                                    continue;
                                }

                                //All resources assigned 
                                if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {
                                    //Assign pouring end starting next trip
                                    $pouring_end_prev = $pouring_end;
                                    $trip_reset_time = $pouring_end;
                                    //Update Pump AVL
                                    if (isset($order -> pump)) {
                                        $pump_availability_copy[$pouring_pump_index]['free_upto'] = ($trip == 1) ? $qc_start->copy()->subMinute() : $pouring_start->copy()->subMinute();
                                        $pump_availability_copy[$pouring_pump_index]['location'] = $location;
                                        $pump_availability_copy[] = array(
                                            'group_company_id' => $company,
                                            'pump_name' => $pouring_pump['pump_name'],
                                            'pump_capacity' => $pouring_pump['pump_capacity'],
                                            'free_from' => (($qty - min([$truck_capacity, $qty])) > 0) ? $pouring_end->copy()->addMinute() : $return_end -> copy() -> addMinute(),
                                            'free_upto' => $pouring_pump['free_upto'],
                                            'location' => $location
                                        );
                                    }
                                    //Update Transit Mixer AVL
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['location'] = $location;
                                    $transit_mixer_availability_copy[] = array(
                                        'id' => end($transit_mixer_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'truck_name' => $transit_mixer['truck_name'],
                                        'truck_capacity' => $truck_capacity,
                                        'loading_time' => $loading_time,
                                        'free_from' => $return_end->copy()->addMinute(),
                                        'free_upto' => $transit_mixer['free_upto'],
                                        'location' => $location,
                                        'restriction_start' => $restriction_start,
                                        'restriction_end' => $restriction_end,
                                    );
                                    //Update Batching Plant AVL
                                    $batching_plant_availability_copy[$batching_plant['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $batching_plant_availability_copy[] = array(
                                        'id' => end($batching_plant_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'location' => $location,
                                        'plant_name' => $batching_plant['plant_name'],
                                        'plant_capacity' => $batching_plant['plant_capacity'],
                                        'free_from' => $loading_end->copy()->addMinute(),
                                        'free_upto' => $batching_plant['free_upto'],
                                        'restriction_start' => $restriction_start,
                                        'restriction_end' => $restriction_end,
                                    );

                                    $batching_qty = min([$truck_capacity, $qty]);
                                    break;
                                }
                            } //End Truck Loop

                            //Trip adjustiment
                            if ((!isset($transit_mixer)) || (!isset($batching_plant)) || (!isset($pouring_pump) && isset($order -> pump))) {
                                if ($trip > 1) {
                                    if ((Carbon::parse($pouring_end_prev) -> diffInMinutes(Carbon::parse($trip_reset_time))) > $pouring_time ) {
                                        break;
                                    } else {
                                        $pouring_end_prev = Carbon::parse($pouring_end_prev) -> copy() -> addMinute();
                                        continue;
                                    } 
                                } else {
                                    break;
                                }
                            } else { //Trip fulfilled
                                $schedules[] = array(
                                    'group_company_id' => $company,
                                    'schedule_date' => $schedule_date,
                                    'order_no' => $order->order_no,
                                    'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                                    'location' => $location,
                                    'trip' => $trip,
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
                                if (isset($order -> pump)) {
                                    $pump_update = self::searchAndUpdateArray($selected_order_pump_schedules,[
                                        'group_company_id' => $company,
                                        'schedule_date' => $schedule_date,
                                        'order_no' => $order->order_no,
                                        'pump' => $pouring_pump['pump_name'],
                                        'location' => $location
                                    ],
                                    [
                                        'pouring_time' => ['value' => $pouring_time],
                                        'pouring_end' => $pouring_end,
                                        'cleaning_start' => $cleaning_start,
                                        'cleaning_end' => $cleaning_end,
                                        'return_time' => $return_time,
                                        'return_start' => $return_start,
                                        'return_end' => $return_end
                                    ]
                                    );
                                    if ($pump_update['match'] === false) {
                                        $selected_order_pump_schedules[] = array(
                                            'group_company_id' => $company,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => $pouring_pump['pump_name'],
                                            'location' => $location,
                                            'trip' => $trip,
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
                                        );
                                    } else {
                                        $selected_order_pump_schedules = $pump_update['data'];
                                    }
                                    $pump_ids[] = $pouring_pump['pump_name'];
                                }
                                //Next trip
                                $qty = $qty - $batching_qty;
                                $trip += 1;
                            }
                        } //End Loop Trips
                        $pump_ids = [];
                        //All resources fulfilled
                        if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {
                            DB::table("selected_order_schedules")->insert($schedules);
                            DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
                            $update_order = DB::table('selected_orders as A')
                                ->where('id', $order->id)
                                ->update([
                                    'start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->max_pour,
                                    'location' => $location,
                                ]);
                            //Reset schedules
                            $schedules = [];
                            $selected_order_pump_schedules = [];
                            //Update Deviation and AVL
                            $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                            $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
                            DB::table("selected_orders")->where("id", $order->id)->update([
                                'deviation' => $order_deviation
                            ]);
                            $transit_mixer_availability = $transit_mixer_availability_copy;
                            $pump_availability = $pump_availability_copy;
                            $batching_plant_availability = $batching_plant_availability_copy;

                            //COMMIT AND BREAK
                            DB::commit();
                            $avl = 1;
                            break;
                        } else { // All resources not fulfilled (ROLLBACK/ RESET)
                            $schedules = [];
                            $selected_order_pump_schedules = [];
                            $transit_mixer_availability_copy = $transit_mixer_availability;
                            $pump_availability_copy = $pump_availability;
                            $batching_plant_availability_copy = $batching_plant_availability;

                            DB::rollBack();
                            if ($sch_adj_time <= 0) {
                                $avl = 0;
                                break;
                            }
                        }
                    } //End Forward backward adjustment loop 

                    //Order adjustment
                    $sch_adj_time += 1;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                    $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);
                    //Shift crossed or day crossed
                    if ($delivery_date_p->copy()->lt(Carbon::parse($delivery_date)->copy()->subMinutes($sch_adj_to)) && $delivery_date_n->copy()->gt(Carbon::parse($delivery_date)->copy()->addMinutes($sch_adj_to))) {
                        $avl = 1;
                    }
                    if ($delivery_date_p->copy()->lt($location_start_time) && $delivery_date_n->copy()->gt($location_end_time)) {
                        if ( ( ($delivery_date_p->copy()->subMinutes($total_time)) -> lt($location_start_time) ) &&
                                ( ($delivery_date_n->copy()->subMinutes($total_time))  -> gt($location_end_time) ) ) {
                            $avl = 1;
                        } 
                    }
                    if ($avl == 1) {
                        break;
                    }
                } //Schedule adjustment based on availability LOOP END
                if (isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null))) {
                    break;
                }
            } //Location loops end
        } //Orders Loop End
    }

    //Original V6 (latest)
    public static function generate_schedule_v7(string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty,  string $schedule_preference, $restriction_start, $restriction_end)
    {
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

        //Get Company Shift
        $company_shift = GroupCompany::find($company);
        $location_start_time = Carbon::parse($schedule_date . ' ' . ConstantHelper::GROUP_COMP_START_TIME);
        $location_end_time = Carbon::parse($schedule_date . ' ' . ConstantHelper::GROUP_COMP_END_TIME) -> copy() -> addDay();
        if (isset($company_shift)) {
                $location_start_time = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_s);
                $location_end_time_1 = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_e);
                $location_end_time = Carbon::parse($location_start_time) -> gt(Carbon::parse($location_end_time_1)) ? $location_end_time_1 -> copy() -> addDay() : $location_start_time -> copy() -> setTimeFromTimeString("11:59");
        }

        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", 
        "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", 
        "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)
        -> whereBetween("delivery_date",  [$location_start_time, $location_end_time])
        ->whereNull("start_time") -> where("selected", true)
        -> get();


        if ($schedule_preference == "largest_qty_first") {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['quantity', 'desc'], //expected_start_time
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        } else {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        }
        

        $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));

        $locations = array_unique(array_column($batching_plant_availability, 'location'));

        foreach ($orders as $order) { // Order loop start
            $pump_ids = [];
            $pouring_time = 0;
            //Assign large values between interval and pouring time
            if ($order -> pouring_time > $order -> interval) {
                $pouring_time = $order -> pouring_time;
            } else {
                $pouring_time = $order -> interval;
            }
            //Divide pouring time acc to pump qty
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = 30;
            } if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }

            //Initialize variables
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $loading_time = 0;
            $pouring_end_1 = "";
            $trip_reset_time = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = $order -> delivery_date;
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;

            $location = "";

            $sch_adj_time = 0;

            //Get Locations availability
            $index = array_search($order -> location, $locations);
                
            if ($index !== false) {
                unset($locations[$index]);
                array_unshift($locations, $order -> location);
            }
            //Locations Loop
            foreach($locations as $loc) {
                // if ($order -> location != $locations[$i] && $i < (count($locations) - 1)) { //NEED TO REFACTOR
                //     continue;
                // }
                $location = $loc;
                //Check for first available plant time
                $plant_availability_array = array_filter($batching_plant_availability_copy, function ($item) use($company, $location) {
                    if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from'])) ) {
                        return true;
                    } else {
                        return false;
                    }
                });
                usort($plant_availability_array, function ($a, $b) {
                    return $a['free_from'] <=> $b['free_from'];
                });
                $plant_availability = $plant_availability_array[0]['free_from'];

                $plant_availability_array_copy = [];
                
                $delivery_time = $schedule_preference != "customer_timeline" ? (isset($order -> priority) && $order -> priority < 999 ? $order -> delivery_date : $plant_availability) : $delivery_date;
                // $location_end_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> copy() -> addDay() -> format("Y-m-d") . " 07:59");
                // $location_start_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> format("Y-m-d") . " 08:00");

                $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);

                $avl = 0;
                $restriction_flag = Carbon::parse("2001-01-01 00:00:00");
                //Schedule adjustment based on availability LOOP
                while ($avl == 0) {
                    
                    $to_from_array = [1, 2];
                    //Forward backward adjustment loop
                    foreach ($to_from_array as $val) {
                        
                        $transit_mixer = null;
                        $pouring_pump = null;
                        $batching_plant = null;
                        $qty = $order->quantity;
                        $trip = 1;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0) {

                            foreach ($truck_capacities as $truck_capacity) {
                                $transit_mixer = null;
                                $loading_time = 10;
                                // $loading_time = DB::table("transit_mixer_availablity")->where("group_company_id", $company)->where("truck_capacity", $truck_capacity->truck_capacity)->max("loading_time");

                                //First Trip
                                if ($trip == 1) {
                                    if ($sch_adj_time == 0) {
                                        $delivery_date = $delivery_time;
                                    } else {
                                        if ($val == 1) {
                                            $delivery_date = $delivery_date_n;
                                        } else {
                                            $delivery_date = $delivery_date_p;
                                        }                                        
                                    }
                                    //Subsequent Trips
                                } else {
                                    $delivery_date = $pouring_end_1 -> copy() -> subMinutes($pouring_interval) -> addMinute();
                                }
                                $delivery_date = Carbon::parse($delivery_date);

                                if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                    if ($delivery_date -> gte(Carbon::parse($restriction_start)) && $delivery_date -> lte(Carbon::parse($restriction_end))) {
                                       if (Carbon::parse($restriction_flag) -> notEqualTo(Carbon::parse($restriction_start))) {
                                            $delivery_time = Carbon::parse($restriction_end) -> copy() -> addMinute();
                                            $sch_adj_time  = -1;
                                            $avl = 0;
                                            $restriction_flag = $restriction_start;
                                            break;
                                       }
                                    }
                                }

                                $travel_time = $order -> travel_to_site;
                                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                                $loading_start = $delivery_date->copy()->subMinutes($total_time);

                                $loading_end = $loading_start->copy()->addMinutes($loading_time) -> subMinute();
                                
                                $qc_start = $loading_end -> copy() -> addMinute();
                                $qc_end = $qc_start->copy()->addMinutes($qc_time) -> subMinute();

                                $travel_start = $qc_end -> copy() -> addMinute();
                                $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                                $insp_start = $travel_end -> copy() -> addMinute();
                                $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                                $pouring_start = $insp_end -> copy() -> addMinute();
                                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                                $cleaning_start = $pouring_end -> copy() -> addMinute();
                                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                                $return_time = $order -> return_to_plant;
                                $return_start = $cleaning_end -> copy() -> addMinute();
                                $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();
                               
                                  
                                //Restriction
                                // if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                //     if (($loading_start -> gte(Carbon::parse($restriction_start)) && $loading_start -> lte(Carbon::parse($restriction_end))) 
                                //     || ($return_end -> gte(Carbon::parse($restriction_start)) && $return_end -> lte(Carbon::parse($restriction_end)))) {
                                        
                                //         $min_date = $loading_start;
                                //         if (Carbon::parse($loading_start) -> gt(Carbon::parse($restriction_start))) {
                                //             $min_date = $restriction_start;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $min_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($min_date) -> gt(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });

                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $b['free_from'] <=> $a['free_from'];
                                //         });
                                //         $delivery_date_p = isset($plant_availability_array_copy[0]['free_upto']) ? $plant_availability_array_copy[0]['free_upto'] : $location_start_time -> copy() -> subMinute();
                                //         if (Carbon::parse($delivery_date_p) -> gte(Carbon::parse($min_date))) {
                                //             $delivery_date_p = $location_start_time -> copy() -> subMinute();
                                //         }
                                //         $plant_availability_array_copy = [];

                                //         $max_date = $return_end;
                                //         if (Carbon::parse($return_end) -> lt(Carbon::parse($restriction_end))) {
                                //             $max_date = $restriction_end;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $max_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($max_date) -> lte(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });
                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $a['free_from'] <=> $b['free_from'];
                                //         });
                                //         $delivery_date_n = isset($plant_availability_array_copy[0]['free_from']) ? $plant_availability_array_copy[0]['free_from'] : $location_end_time -> copy() -> addMinute();
                                        
                                //         if (Carbon::parse($delivery_date_n) -> lt(Carbon::parse($max_date))) {
                                //             // $delivery_date_n = $location_end_time -> copy() -> addMinute();
                                //             $delivery_date_n = Carbon::parse($max_date);
                                //         }
                                        
                                //         $interval = (int)((Carbon::parse($delivery_date_n) -> diffInMinutes(Carbon::parse($delivery_date_p)))/2);
                                        
                                //         $sch_adj_time = $interval;

                                //         $delivery_time = Carbon::parse($delivery_date_p) -> copy() -> addMinutes($interval);

                                //         if ($order -> order_no == 11 && $delivery_date_n -> gt("2024-01-27 16:00:00") ) {
                                //             dd($loading_start, $return_end, $delivery_date_n, $delivery_date_p, $delivery_date, $delivery_time, $max_date);
                                //         }
                                //         break;
                                //     }
                                // }
                                
                                $deviation = ($pouring_start->copy())->diffInMinutes($order->delivery_date);

                                if ($trip > 1) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $batching_plant);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end);
                                    $batching_plant = $plant;
                                }

                                if (!isset($batching_plant)) {
                                    break;
                                }
                                
                                if (isset($order -> pump)) {
                                    $pump = self::get_available_pumps($pump_availability_copy, $pump_ids, $company, $qc_start, $pouring_start, $return_end, $order->pump, $order -> order_no, $trip, $schedules, $location_end_time);
                                    $pouring_pump = $pump;

                                    if (!isset($pouring_pump)) {
                                        break;
                                    }
                                }

                                $truck_cap = (int)$truck_capacity;
                                
                                $truck = self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $location);
                                $truck = isset($truck) ? $truck : self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time);
                                $transit_mixer = $truck;
                                
                                if (!isset($transit_mixer)) {
                                    continue;
                                }

                                //Both truck and plant assigned
                                if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {

                                    $pouring_end_1 = $pouring_end;
                                    $trip_reset_time = $pouring_end;

                                    if (isset($order -> pump)) {
                                        if ($trip > 1) {
                                            $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $pouring_start->copy()->subMinute();
                                            $pump_availability_copy[$pouring_pump['id']]['location'] = $location;

                                            $pump_availability_copy[] = array(
                                                'id' => end($pump_availability_copy)['id'] + 1,
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location
                                            );
                                            
                                        } else {
                                            $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $qc_start->copy()->subMinute();
                                            $pump_availability_copy[$pouring_pump['id']]['location'] = $location;
                                            
                                            $pump_availability_copy[] = array(
                                                'id' => end($pump_availability_copy)['id'] + 1,
                                                'group_company_id' => $company,
                                                'pump_name' => $pouring_pump['pump_name'],
                                                'pump_capacity' => $pouring_pump['pump_capacity'],
                                                'free_from' => $pouring_end->copy()->addMinute(),
                                                'free_upto' => $pouring_pump['free_upto'],
                                                'location' => $location
                                            );
                
                                        }
                                    }
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $transit_mixer_availability_copy[$transit_mixer['id']]['location'] = $location;

                                    $transit_mixer_availability_copy[] = array(
                                        'id' => end($transit_mixer_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'truck_name' => $transit_mixer['truck_name'],
                                        'truck_capacity' => $truck_capacity,
                                        'loading_time' => $loading_time,
                                        'free_from' => $return_end->copy()->addMinute(),
                                        'free_upto' => $transit_mixer['free_upto'],
                                        'location' => $location,
                                        'restriction_start' => $restriction_start,
                                        'restriction_end' => $restriction_end,
                                    );
                                    
                                    $batching_plant_availability_copy[$batching_plant['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    $batching_plant_availability_copy[] = array(
                                        'id' => end($batching_plant_availability_copy)['id'] + 1,
                                        'group_company_id' => $company,
                                        'location' => $location,
                                        'plant_name' => $batching_plant['plant_name'],
                                        'plant_capacity' => $batching_plant['plant_capacity'],
                                        'free_from' => $loading_end->copy()->addMinute(),
                                        'free_upto' => $batching_plant['free_upto'],
                                        'restriction_start' => $restriction_start,
                                        'restriction_end' => $restriction_end,
                                    );

                                    $batching_qty = min([$truck_capacity, $qty]);
                                    break;
                                }

                            } //End Truck Loop

                            if ((!isset($transit_mixer)) || (!isset($batching_plant)) || (!isset($pouring_pump) && isset($order -> pump))) {
                                if ($trip > 1) {
                                    if ((Carbon::parse($pouring_end_1) -> diffInMinutes(Carbon::parse($trip_reset_time))) > $pouring_time ) {
                                        break;
                                    } else {
                                        $pouring_end_1 = Carbon::parse($pouring_end_1) -> copy() -> addMinute();
                                        continue;
                                    } 
                                } else {
                                    break;
                                }
                            }
                             else {
                                $schedules[] = array(
                                    'group_company_id' => $company,
                                    'schedule_date' => $schedule_date,
                                    'order_no' => $order->order_no,
                                    'pump' => isset($pouring_pump) ? $pouring_pump['pump_name'] : null,
                                    'location' => $location,
                                    'trip' => $trip,
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
                                if (isset($order -> pump)) {
                                    $pump_update = self::searchAndUpdateArray($selected_order_pump_schedules,[
                                        'group_company_id' => $company,
                                        'schedule_date' => $schedule_date,
                                        'order_no' => $order->order_no,
                                        'pump' => $pouring_pump['pump_name'],
                                        'location' => $location
                                    ],
                                    [
                                        'pouring_time' => ['value' => $pouring_time],
                                        'pouring_end' => $pouring_end,
                                        'cleaning_start' => $cleaning_start,
                                        'cleaning_end' => $cleaning_end,
                                        'return_time' => $return_time,
                                        'return_start' => $return_start,
                                        'return_end' => $return_end
                                    ]
                                    );
                                    
                                    if ($pump_update['match'] === false) {
                                        $selected_order_pump_schedules[] = array(
                                            'group_company_id' => $company,
                                            'schedule_date' => $schedule_date,
                                            'order_no' => $order->order_no,
                                            'pump' => $pouring_pump['pump_name'],
                                            'location' => $location,
                                            'trip' => $trip,
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
                                        );
                                    } else {
                                        $selected_order_pump_schedules = $pump_update['data'];
                                    }
                                    $pump_ids[] = $pouring_pump['pump_name'];
                                }

                                $qty = $qty - $batching_qty;
                                $trip += 1;
                            }
                        } //End Loop Trips
                        if (((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null)) && isset($transit_mixer) && isset($batching_plant)) {
                            DB::table("selected_order_schedules")->insert($schedules);
                            DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
                            
                            $update_order = DB::table('selected_orders as A')
                                ->where('id', $order->id)
                                ->update([
                                    'start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->max_pour,
                                    'location' => $location,
                                ]);

                            $schedules = [];
                            $selected_order_pump_schedules = [];

                            $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                            $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
                            DB::table("selected_orders")->where("id", $order->id)->update([
                                'deviation' => $order_deviation
                            ]);
                            //COMMIT
                            $transit_mixer_availability = $transit_mixer_availability_copy;
                            $pump_availability = $pump_availability_copy;
                            $batching_plant_availability = $batching_plant_availability_copy;
                            
                            DB::commit();

                            $avl = 1;
                            break;
                        } else {
                            $schedules = [];
                            $selected_order_pump_schedules = [];

                            $transit_mixer_availability_copy = $transit_mixer_availability;
                            $pump_availability_copy = $pump_availability;
                            $batching_plant_availability_copy = $batching_plant_availability;

                            DB::rollBack();
                            
                            if ($sch_adj_time <= 0) {
                                $avl = 0;
                                break;
                            }
                        }
                        $pump_ids = [];

                    } //End Forward backward adjustment loop 
                    
                    $sch_adj_time += 1;
                    $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                    $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);
                    
                    if ($delivery_date_p->copy()->lt(Carbon::parse($delivery_date)->copy()->subMinutes($sch_adj_to)) && $delivery_date_n->copy()->gt(Carbon::parse($delivery_date)->copy()->addMinutes($sch_adj_to))) {
                        $avl = 1;
                    }
                    if ($delivery_date_p->copy()->lt($location_start_time) && $delivery_date_n->copy()->gt($location_end_time)) {
                        if ( ( ($delivery_date_p->copy()->subMinutes($total_time)) -> lt($location_start_time) ) &&
                                ( ($delivery_date_n->copy()->subMinutes($total_time))  -> gt($location_end_time) ) ) {
                            $avl = 1;
                        } 
                    }

                    if ($avl == 1) {
                        break;
                    }
                } //Schedule adjustment based on availability LOOP END

                if (isset($transit_mixer) && isset($batching_plant) && ((isset($pouring_pump) && isset($order -> pump)) || ($pouring_pump === null && $order -> pump === null))) {
                    break;
                }
            } //Location loops end
        } //Orders Loop End
    }

    public static function generate_schedule_v5(string $company, string $schedule_date, int $sch_adj_from, int $sch_adj_to, array $tms_availabilty, array $pumps_availabilty, array $bps_availabilty,  string $schedule_preference, $restriction_start, $restriction_end)
    {
        $qc_time = 5;
        $insp_time = 5;
        $cleaning_time = 5;
        $batching_qty = 0;

        //Order and Pump Schedules
        $selected_order_pump_schedules = [];
        $schedules = [];

        //Availabilities data
        $transit_mixer_availability = $tms_availabilty;
        $pump_availability = $pumps_availabilty;
        $batching_plant_availability = $bps_availabilty;

        $transit_mixer_availability_copy = $tms_availabilty;
        $pump_availability_copy = $pumps_availabilty;
        $batching_plant_availability_copy = $bps_availabilty;

        $company_shift = GroupCompany::find($company);

        $location_start_time = Carbon::parse($schedule_date . ' ' . "08:00");
        $location_end_time = Carbon::parse($schedule_date . ' ' . "07:59") -> copy() -> addDay();
        if (isset($company_shift)) {
                $location_start_time = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_s);
                $location_end_time_1 = Carbon::parse($schedule_date . ' ' .$company_shift -> working_hrs_e);
                $location_end_time = Carbon::parse($location_start_time) -> gt(Carbon::parse($location_end_time_1)) ? $location_end_time_1 -> copy() -> addDay() : $location_start_time -> copy() -> setTimeFromTimeString("11:59");
        }

        $orders = SelectedOrder::select("group_company_id", "id", "order_no", "customer", "project", 
        "site", "location", "mix_code", "quantity", "delivery_date", "interval", "pump", 
        "travel_to_site", "return_to_plant", "pump_qty", "priority")->where("group_company_id", $company)
        -> whereBetween("delivery_date",  [$location_start_time, $location_end_time])
        ->whereNull("start_time") -> where("selected", true)
        -> get();


        if ($schedule_preference == "largest_qty_first") {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['quantity', 'desc'], //expected_start_time
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        } else {
            $orders = $orders -> sortBy([
                ['priority', 'asc'],
                ['delivery_date', 'asc'], //expected_start_time
            ]) -> values();
        }
        

        $truck_capacities = array_unique(array_column($transit_mixer_availability, 'truck_capacity'));

        $locations = array_unique(array_column($batching_plant_availability, 'location'));

        foreach ($orders as $order) { // Order loop start
            $pump_ids = [];
            $pouring_time = 0;
            //Assign large values between interval and pouring time
            if ($order -> pouring_time > $order -> interval) {
                $pouring_time = $order -> pouring_time;
            } else {
                $pouring_time = $order -> interval;
            }
            //Divide pouring time acc to pump qty
            $pouring_interval = 0;
            if (!isset($pouring_time)) {
                $pouring_time = 30;
            } if ($order -> pump_qty > 1) {
                $pouring_interval = round(($pouring_time/$order -> pump_qty),0);
            }

            //Initialize variables
            $pouring_pump = null;
            $transit_mixer = null;
            $batching_plant = null;

            $loading_time = 0;
            $pouring_end_1 = "";

            $delivery_date_n = "";
            $delivery_date_p = "";
            $delivery_date = $order -> delivery_date;
            $travel_time = "";
            $total_time = "";

            $loading_start = "";
            $loading_end = "";

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

            $deviation = 0;

            $location = "";

            $sch_adj_time = 0;

            //Get Locations availability
            $index = array_search($order -> location, $locations);
                
            if ($index !== false) {
                unset($locations[$index]);
                array_unshift($locations, $order -> location);
            }
            //Locations Loop
            foreach($locations as $loc) {
                // if ($order -> location != $locations[$i] && $i < (count($locations) - 1)) { //NEED TO REFACTOR
                //     continue;
                // }
                $location = $loc;
                //Check for first available plant time
                $plant_availability_array = array_filter($batching_plant_availability_copy, function ($item) use($company, $location) {
                    if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from'])) ) {
                        return true;
                    } else {
                        return false;
                    }
                });
                usort($plant_availability_array, function ($a, $b) {
                    return $a['free_from'] <=> $b['free_from'];
                });
                $plant_availability = $plant_availability_array[0]['free_from'];

                $plant_availability_array_copy = [];
                
                // $delivery_time = $schedule_preference != "customer_timeline" ? (isset($order -> priority) && $order -> priority < 999 ? $order -> delivery_date : $plant_availability) : $delivery_date;
                $delivery_time = $delivery_date;
                // $location_end_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> copy() -> addDay() -> format("Y-m-d") . " 07:59");
                // $location_start_time = Carbon::parse((Carbon::parse($order -> delivery_date)) -> format("Y-m-d") . " 08:00");

                // $sch_adj_time = isset($sch_adj_from) ? $sch_adj_from : 0;
                // $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                // $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);

                $avl = 0;
                $restriction_flag = Carbon::parse("2001-01-01 00:00:00");
                //Schedule adjustment based on availability LOOP
                //while ($avl == 0) {
                    
                    //$to_from_array = [1, 2];
                    //Forward backward adjustment loop
                    //foreach ($to_from_array as $val) {
                        
                        $transit_mixer = null;
                        $pouring_pump = null;
                        $batching_plant = null;
                        $qty = $order->quantity;
                        $trip = 1;

                        DB::beginTransaction();
                        //Trips Loop
                        while ($qty > 0) {

                            //foreach ($truck_capacities as $truck_capacity) {
                                $transit_mixer = null;
                                $loading_time = 10;
                                // $loading_time = DB::table("transit_mixer_availablity")->where("group_company_id", $company)->where("truck_capacity", $truck_capacity->truck_capacity)->max("loading_time");

                                //First Trip
                                if ($trip == 1) {
                                    // if ($sch_adj_time == 0) {
                                        $delivery_date = $delivery_time;
                                    // }
                                    //  else {
                                    //     if ($val == 1) {
                                    //         $delivery_date = $delivery_date_n;
                                    //     } else {
                                    //         $delivery_date = $delivery_date_p;
                                    //     }                                        
                                    }
                                    //Subsequent Trips
                                //}
                                 else {
                                    $delivery_date = $pouring_end_1 -> copy() -> subMinutes($pouring_interval) -> addMinute();
                                }
                                $delivery_date = Carbon::parse($delivery_date);

                                // if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                //     if ($delivery_date -> gte(Carbon::parse($restriction_start)) && $delivery_date -> lte(Carbon::parse($restriction_end))) {
                                //        if (Carbon::parse($restriction_flag) -> notEqualTo(Carbon::parse($restriction_start))) {
                                //             $delivery_time = Carbon::parse($restriction_end) -> copy() -> addMinute();
                                //             $sch_adj_time  = -1;
                                //             $avl = 0;
                                //             $restriction_flag = $restriction_start;
                                //             break;
                                //        }
                                //     }
                                // }

                                $travel_time = $order -> travel_to_site;
                                $total_time = ((int) $loading_time) + $qc_time + ((int) $travel_time) + $insp_time;
                                $loading_start = $delivery_date->copy()->subMinutes($total_time);

                                $loading_end = $loading_start->copy()->addMinutes($loading_time) -> subMinute();
                                
                                $qc_start = $loading_end -> copy() -> addMinute();
                                $qc_end = $qc_start->copy()->addMinutes($qc_time) -> subMinute();

                                $travel_start = $qc_end -> copy() -> addMinute();
                                $travel_end = $travel_start->copy()->addMinutes($travel_time) -> subMinute();

                                $insp_start = $travel_end -> copy() -> addMinute();
                                $insp_end = $insp_start->copy()->addMinutes($insp_time) -> subMinute();

                                $pouring_start = $insp_end -> copy() -> addMinute();
                                $pouring_end = $pouring_start->copy()->addMinutes($pouring_time) -> subMinute();

                                $cleaning_start = $pouring_end -> copy() -> addMinute();
                                $cleaning_end = $cleaning_start->copy()->addMinutes($cleaning_time) -> subMinute();

                                $return_time = $order -> return_to_plant;
                                $return_start = $cleaning_end -> copy() -> addMinute();
                                $return_end = $return_start->copy()->addMinutes($return_time) -> subMinute();
                               
                                  
                                //Restriction
                                // if (isset($restriction_start) && isset($restriction_end)) {
                                    
                                //     if (($loading_start -> gte(Carbon::parse($restriction_start)) && $loading_start -> lte(Carbon::parse($restriction_end))) 
                                //     || ($return_end -> gte(Carbon::parse($restriction_start)) && $return_end -> lte(Carbon::parse($restriction_end)))) {
                                        
                                //         $min_date = $loading_start;
                                //         if (Carbon::parse($loading_start) -> gt(Carbon::parse($restriction_start))) {
                                //             $min_date = $restriction_start;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $min_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($min_date) -> gt(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });

                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $b['free_from'] <=> $a['free_from'];
                                //         });
                                //         $delivery_date_p = isset($plant_availability_array_copy[0]['free_upto']) ? $plant_availability_array_copy[0]['free_upto'] : $location_start_time -> copy() -> subMinute();
                                //         if (Carbon::parse($delivery_date_p) -> gte(Carbon::parse($min_date))) {
                                //             $delivery_date_p = $location_start_time -> copy() -> subMinute();
                                //         }
                                //         $plant_availability_array_copy = [];

                                //         $max_date = $return_end;
                                //         if (Carbon::parse($return_end) -> lt(Carbon::parse($restriction_end))) {
                                //             $max_date = $restriction_end;
                                //         }
                                //         $plant_availability_array_copy = array_filter($batching_plant_availability_copy, function ($item) use($company, $location, $max_date) {
                                //             if ($item['group_company_id'] == $company && Carbon::parse($item['free_upto']) -> gte(Carbon::parse($item['free_from']))
                                //                 && (Carbon::parse($max_date) -> lte(Carbon::parse($item['free_upto']))) ) {
                                //                 return true;
                                //             } else {
                                //                 return false;
                                //             }
                                //         });
                                //         usort($plant_availability_array_copy, function ($a, $b) {
                                //             return $a['free_from'] <=> $b['free_from'];
                                //         });
                                //         $delivery_date_n = isset($plant_availability_array_copy[0]['free_from']) ? $plant_availability_array_copy[0]['free_from'] : $location_end_time -> copy() -> addMinute();
                                        
                                //         if (Carbon::parse($delivery_date_n) -> lt(Carbon::parse($max_date))) {
                                //             // $delivery_date_n = $location_end_time -> copy() -> addMinute();
                                //             $delivery_date_n = Carbon::parse($max_date);
                                //         }
                                        
                                //         $interval = (int)((Carbon::parse($delivery_date_n) -> diffInMinutes(Carbon::parse($delivery_date_p)))/2);
                                        
                                //         $sch_adj_time = $interval;

                                //         $delivery_time = Carbon::parse($delivery_date_p) -> copy() -> addMinutes($interval);

                                //         if ($order -> order_no == 11 && $delivery_date_n -> gt("2024-01-27 16:00:00") ) {
                                //             dd($loading_start, $return_end, $delivery_date_n, $delivery_date_p, $delivery_date, $delivery_time, $max_date);
                                //         }
                                //         break;
                                //     }
                                // }
                                
                                $deviation = ($pouring_start->copy())->diffInMinutes($order->delivery_date);

                                // if ($trip > 1) {
                                //     $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end, $batching_plant);
                                //     $batching_plant = $plant;
                                // }

                                // if (!isset($batching_plant)) {
                                //     $plant = self::get_available_batching_plants($batching_plant_availability_copy, $company, $location, $loading_start, $loading_end);
                                //     $batching_plant = $plant;
                                // }

                                // if (isset($batching_plant)) {
                                //     dd("BP");
                                // }
                                

                                // if (!isset($batching_plant)) {
                                //     $delivery_time = Carbon::parse($delivery_time) -> copy() -> addMinute();
                                //     continue;
                                // }
                                
                                // if (isset($order -> pump)) {
                                //     $pump = self::get_available_pumps($pump_availability_copy, $pump_ids, $company, $qc_start, $pouring_start, $return_end, $order->pump, $order -> order_no, $trip, $schedules, $location_end_time);
                                //     $pouring_pump = $pump;

                                //     if (!isset($pouring_pump)) {
                                //         break;
                                //     }
                                // }

                                $truck_cap = 8;
                                
                                // $truck = self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $location);
                                // $truck = isset($truck) ? $truck : self::get_available_trucks($transit_mixer_availability_copy, $company, $truck_cap, $loading_start, $return_end, $location_end_time);
                                // $transit_mixer = $truck;
                                
                                // if (!isset($transit_mixer)) {
                                //     continue;
                                // }

                                //Both truck and plant assigned
                                //if (isset($batching_plant)) {

                                    $pouring_end_1 = $pouring_end;
                                    // if (isset($order -> pump)) {
                                    //     if ($trip > 1) {
                                    //         $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $pouring_start->copy()->subMinute();
                                    //         $pump_availability_copy[$pouring_pump['id']]['location'] = $location;

                                    //         $pump_availability_copy[] = array(
                                    //             'id' => end($pump_availability_copy)['id'] + 1,
                                    //             'group_company_id' => $company,
                                    //             'pump_name' => $pouring_pump['pump_name'],
                                    //             'pump_capacity' => $pouring_pump['pump_capacity'],
                                    //             'free_from' => $pouring_end->copy()->addMinute(),
                                    //             'free_upto' => $pouring_pump['free_upto'],
                                    //             'location' => $location
                                    //         );
                                            
                                    //     } else {
                                    //         $pump_availability_copy[$pouring_pump['id']]['free_upto'] = $qc_start->copy()->subMinute();
                                    //         $pump_availability_copy[$pouring_pump['id']]['location'] = $location;
                                            
                                    //         $pump_availability_copy[] = array(
                                    //             'id' => end($pump_availability_copy)['id'] + 1,
                                    //             'group_company_id' => $company,
                                    //             'pump_name' => $pouring_pump['pump_name'],
                                    //             'pump_capacity' => $pouring_pump['pump_capacity'],
                                    //             'free_from' => $pouring_end->copy()->addMinute(),
                                    //             'free_upto' => $pouring_pump['free_upto'],
                                    //             'location' => $location
                                    //         );
                
                                    //     }
                                    // }
                                    // $transit_mixer_availability_copy[$transit_mixer['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    // $transit_mixer_availability_copy[$transit_mixer['id']]['location'] = $location;

                                    // $transit_mixer_availability_copy[] = array(
                                    //     'id' => end($transit_mixer_availability_copy)['id'] + 1,
                                    //     'group_company_id' => $company,
                                    //     'truck_name' => $transit_mixer['truck_name'],
                                    //     'truck_capacity' => $truck_capacity,
                                    //     'loading_time' => $loading_time,
                                    //     'free_from' => $return_end->copy()->addMinute(),
                                    //     'free_upto' => $transit_mixer['free_upto'],
                                    //     'location' => $location
                                    // );
                                    
                                    // $batching_plant_availability_copy[$batching_plant['id']]['free_upto'] = $loading_start->copy()->subMinute();
                                    // $batching_plant_availability_copy[] = array(
                                    //     'id' => end($batching_plant_availability_copy)['id'] + 1,
                                    //     'group_company_id' => $company,
                                    //     'location' => $location,
                                    //     'plant_name' => $batching_plant['plant_name'],
                                    //     'plant_capacity' => $batching_plant['plant_capacity'],
                                    //     'free_from' => $loading_end->copy()->addMinute(),
                                    //     'free_upto' => $batching_plant['free_upto'],
                                    // );

                                    $batching_qty = min([8, $qty]);
                                    
                                //}
                            //} //End Truck Loop

                            // if (!isset($transit_mixer)) {
                            //     // $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Transit Mixer not available', $location);
                            //     break;
                            // }
                            //if (!isset($batching_plant)) {
                                // $logs[] = self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Batching Plant not available', $location);
                                //break;
                            //} 
                            // else if (!isset($pouring_pump) && isset($order -> pump)) {
                            //     // self::order_schedule_log($company, $schedule_date, $order->order_no, $order->delivery_date, $trip, $batching_qty, $loading_start, $loading_end, $pouring_start, $return_end, 'Pump not available', $location);
                            //     break;
                            // } 
                            //else {
                                $schedules[] = array(
                                    'group_company_id' => $company,
                                    'schedule_date' => $schedule_date,
                                    'order_no' => $order->order_no,
                                    'pump' => "PUMP",
                                    'location' => $location,
                                    'trip' => $trip,
                                    'batching_plant' => "BP",
                                    'transit_mixer' => "TM",
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
                                // if (isset($order -> pump)) {
                                //     $pump_update = self::searchAndUpdateArray($selected_order_pump_schedules,[
                                //         'group_company_id' => $company,
                                //         'schedule_date' => $schedule_date,
                                //         'order_no' => $order->order_no,
                                //         'pump' => $pouring_pump['pump_name'],
                                //         'location' => $location
                                //     ],
                                //     [
                                //         'pouring_time' => ['value' => $pouring_time],
                                //         'pouring_end' => $pouring_end,
                                //         'cleaning_start' => $cleaning_start,
                                //         'cleaning_end' => $cleaning_end,
                                //         'return_time' => $return_time,
                                //         'return_start' => $return_start,
                                //         'return_end' => $return_end
                                //     ]
                                //     );
                                    
                                //     if ($pump_update['match'] === false) {
                                //         $selected_order_pump_schedules[] = array(
                                //             'group_company_id' => $company,
                                //             'schedule_date' => $schedule_date,
                                //             'order_no' => $order->order_no,
                                //             'pump' => $pouring_pump['pump_name'],
                                //             'location' => $location,
                                //             'trip' => $trip,
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
                                //             'return_time' => $return_time,
                                //             'return_start' => $return_start,
                                //             'return_end' => $return_end,
                                //         );
                                //     } else {
                                //         $selected_order_pump_schedules = $pump_update['data'];
                                //     }
                                //     $pump_ids[] = $pouring_pump['pump_name'];
                                // }

                                $qty = $qty - $batching_qty;
                                $trip += 1;
                            //}
                        } //End Loop Trips
                        //if (isset($batching_plant)) {
                            DB::table("selected_order_schedules")->insert($schedules);


                            // DB::table("selected_order_pump_schedules")->insert($selected_order_pump_schedules);
                            
                            $update_order = DB::table('selected_orders as A')
                                ->where('id', $order->id)
                                ->update([
                                    'start_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MIN(pouring_start) AS min_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->min_pour,

                                    'end_time' => DB::table('selected_order_schedules as B')
                                        ->select(DB::raw('MAX(pouring_end) AS max_pour'))
                                        ->where('group_company_id', $company)
                                        ->where('order_no', $order->order_no)
                                        ->first()->max_pour,
                                    'location' => $location,
                                ]);



                            $schedules = [];
                            $selected_order_pump_schedules = [];

                            $order_deviation = DB::table("selected_orders")->where("id", $order->id)->first();
                            $order_deviation = Carbon::parse($order_deviation->delivery_date)->copy()->diffInMinutes(Carbon::parse($order_deviation->start_time), false);
                            DB::table("selected_orders")->where("id", $order->id)->update([
                                'deviation' => $order_deviation
                            ]);

                            //COMMIT
                            // $transit_mixer_availability = $transit_mixer_availability_copy;
                            // $pump_availability = $pump_availability_copy;
                            // $batching_plant_availability = $batching_plant_availability_copy;
                            
                            DB::commit();



                            // $avl = 1;
                            break;
                        // } else {
                        //     $schedules = [];
                        //     $selected_order_pump_schedules = [];

                        //     $transit_mixer_availability_copy = $transit_mixer_availability;
                        //     $pump_availability_copy = $pump_availability;
                        //     $batching_plant_availability_copy = $batching_plant_availability;

                        //     DB::rollBack();
                            
                        //     if ($sch_adj_time <= 0) {
                        //         $avl = 0;
                        //         break;
                        //     }
                        // }
                        // $pump_ids = [];

                    //} //End Forward backward adjustment loop 
                    
                    // $sch_adj_time += 1;
                    // $delivery_date_n = Carbon::parse($delivery_time)->copy()->addMinutes($sch_adj_time);
                    // $delivery_date_p = Carbon::parse($delivery_time)->copy()->subMinutes($sch_adj_time);
                    
                    // if ($delivery_date_p->copy()->lt(Carbon::parse($delivery_date)->copy()->subMinutes($sch_adj_to)) && $delivery_date_n->copy()->gt(Carbon::parse($delivery_date)->copy()->addMinutes($sch_adj_to))) {
                    //     $avl = 1;
                    // }
                    // if ($delivery_date_p->copy()->lt($location_start_time) && $delivery_date_n->copy()->gt($location_end_time)) {
                    //     if ( ( ($delivery_date_p->copy()->subMinutes($total_time)) -> lt($location_start_time) ) &&
                    //             ( ($delivery_date_n->copy()->subMinutes($total_time))  -> gt($location_end_time) ) ) {
                    //         $avl = 1;
                    //     } 
                    // }

                    // if ($avl == 1) {
                    //     break;
                    // }
                //} //Schedule adjustment based on availability LOOP END

                // if (isset($batching_plant)) {
                //     break;
                // }
            } //Location loops end
        } //Orders Loop End
    }

    public static function get_available_locations($company, $location)
    {
        $location_id = DB::table("company_locations")->where("location", $location)->first();
        return DB::table("location_shifts")->select("shift_start", "shift_end")->where("group_company_id", $company)->where("company_location_id", $location_id->id)->first();
    }

    public static function initialize_schedule_generate(string $company, string $schedule_date, array $transit_mixer_ids, array $pump_ids, array $batching_plant_ids, string $schedule_preference, string $shift_start, string $shift_end)
    {
        set_time_limit(5000); //NEED TO REMOVE
        $restriction_start = null;
        $restriction_end = null;

        $pumps_availabilty = [];
        $tms_availabilty = [];
        $bps_availabilty = [];

        $ps = Pump::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "pumps.group_company_id");
        })->select("pump_name", "pump_capacity", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company) 
            -> whereIn("pumps.id", $pump_ids) 
            ->get();
        $ps_idx = 0;

        foreach ($ps as $p) {
            $pumps_availabilty[] = array(
                // 'id' => $ps_idx,
                'group_company_id' => $company,
                'pump_name' => $p->pump_name,
                'pump_capacity' => $p->pump_capacity,
                'free_from' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->gt(Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)) ? Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
            );
            $ps_idx += 1;
        }
        //Travel restrictions on TM
        $restrictions = DB::table("transit_mixer_restrictions")
            ->select("restriction_start", "restriction_end")
            ->where("group_company_id", $company)->first();
        
        $restriction_start = null;
        $restriction_end = null;

        if (isset($restrictions)) {
            $restriction_date = Carbon::parse($schedule_date . ' ' . $restrictions -> restriction_start) -> lt(Carbon::parse($schedule_date . ' '. $shift_start)) ? Carbon::parse($schedule_date) -> copy() -> addDay() -> toDateString() : $schedule_date;
            $restriction_start = Carbon::parse($restriction_date . " " . $restrictions->restriction_start)->format(ConstantHelper::SQL_DATE_TIME);
            $restriction_end = Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->lt(Carbon::parse($restriction_date . " " . $restrictions->restriction_start)) ?
                Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME)
                : Carbon::parse($restriction_date . " " . $restrictions->restriction_end)->format(ConstantHelper::SQL_DATE_TIME);
        }

        $tms = TransitMixer::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "transit_mixers.group_company_id");
        })->select("truck_name", "truck_capacity", "loading_time", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company)
            ->whereIn("transit_mixers.id", $transit_mixer_ids)
            ->get();

        $tms_idx = 0;

        foreach ($tms as $tm) {
            $tms_availabilty[] = array(
                'id' => $tms_idx,
                'group_company_id' => $company,
                'truck_name' => $tm->truck_name,
                'truck_capacity' => $tm->truck_capacity,
                'loading_time' => $tm->loading_time,
                'free_from' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->gt(Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)) ? Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
                'restriction_start' => $restriction_start,
                'restriction_end' => $restriction_end,
            );
            $tms_idx+=1;
        }

        

        // $tm_avail_data = array_filter($tms_availabilty, function ($obj) use($company, $restriction_start, $restriction_end) {
        //     if ($obj['group_company_id'] == $company 
        //         && Carbon::parse($obj['free_from']) -> lte(Carbon::parse($restriction_start)) &&  Carbon::parse($obj['free_from']) -> lte(Carbon::parse($restriction_end))
        //         && Carbon::parse($obj['free_upto']) -> gte(Carbon::parse($restriction_start)) &&  Carbon::parse($obj['free_upto']) -> gte(Carbon::parse($restriction_end))
        //         ) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        // });

        // $tm_idx_copy = count($tms_availabilty);

        // foreach ($tm_avail_data as $tm_avail) {

        //     foreach ($tms_availabilty as $key => $item) {
        //         if ($item['id'] == $tm_avail['id']) {
        //             $tms_availabilty[$key]['free_upto'] = Carbon::parse($restriction_start)->copy()->subMinute()->format(ConstantHelper::SQL_DATE_TIME);  
        //         }
        //     }

        //     $tms_availabilty[] = array(
        //         'id' => $tm_idx_copy,
        //         'group_company_id' => $company,
        //         'truck_name' => $tm_avail['truck_name'],
        //         'truck_capacity' => $tm_avail['truck_capacity'],
        //         'loading_time' => $tm_avail['loading_time'],
        //         'free_from' => Carbon::parse($restriction_end)->addMinute()->format(ConstantHelper::SQL_DATE_TIME),
        //         'free_upto' => $tm_avail['free_upto'],
        //         'location' => null,
        //     );

        //     $tm_idx_copy +=1;
        // }

        $bps = DB::table("batching_plants")->join("location_shifts", function ($join) {
            $join->on("location_shifts.group_company_id", "=", "batching_plants.group_company_id")
                ->on("location_shifts.company_location_id", "=", "batching_plants.company_location_id");
        })->leftJoin("company_locations", function ($query) {
            $query->on("company_locations.group_company_id", "=", "location_shifts.group_company_id")
                ->on("company_locations.id", "=", "location_shifts.company_location_id");
        })->select("location_shifts.group_company_id", "location", "plant_name", "capacity", "shift_start", "shift_end", "company_locations.location")
            ->where("location_shifts.group_company_id", $company)
            ->whereIn("batching_plants.id", $batching_plant_ids)
            ->get();

        $bps_idx = 0;

        foreach ($bps as $bp) {
            $bps_availabilty[] = array(
                'id' => $bps_idx,
                'group_company_id' => $company,
                'plant_name' => $bp->plant_name,
                'plant_capacity' => $bp->capacity,
                'free_from' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->gt(Carbon::parse($schedule_date . ' ' . $bp->shift_end)) ? Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $bp->shift_end)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => $bp?->location,
                'restriction_start' => $restriction_start,
                'restriction_end' => $restriction_end,
            );
            $bps_idx += 1;
        }

        //BPS AVL
        // $bp_avail_data = array_filter($bps_availabilty, function ($obj) use($company, $restriction_start, $restriction_end) {
        //     if ($obj['group_company_id'] == $company 
        //         && Carbon::parse($obj['free_from']) -> lte(Carbon::parse($restriction_start)) &&  Carbon::parse($obj['free_from']) -> lte(Carbon::parse($restriction_end))
        //         && Carbon::parse($obj['free_upto']) -> gte(Carbon::parse($restriction_start)) &&  Carbon::parse($obj['free_upto']) -> gte(Carbon::parse($restriction_end))
        //         ) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        // });

        // $bp_idx_copy = count($bps_availabilty);

        // foreach ($bp_avail_data as $bp_avail) {

        //     foreach ($bps_availabilty as $key => $item) {
        //         if ($item['id'] == $bp_avail['id']) {
        //             $bps_availabilty[$key]['free_upto'] = Carbon::parse($restriction_start)->copy()->subMinute()->format(ConstantHelper::SQL_DATE_TIME);  
        //         }
        //     }

        //     $bps_availabilty[] = array(
        //         'id' => $bp_idx_copy,
        //         'group_company_id' => $company,
        //         'plant_name' => $bp_avail['plant_name'],
        //         'plant_capacity' => $bp_avail['plant_capacity'],
        //         'free_from' => Carbon::parse($restriction_end)->addMinute()->format(ConstantHelper::SQL_DATE_TIME),
        //         'free_upto' => $bp_avail['free_upto'],
        //         'location' => $bp_avail['location'],
        //     );

        //     $bp_idx_copy +=1;
        // }


        DB::table("selected_order_schedules")->where('group_company_id', $company)->delete();
        DB::table("selected_order_pump_schedules")->where('group_company_id', $company)->delete();

        DB::table("selected_orders")->where("group_company_id", $company)->update([
            'start_time' => null,
            'end_time' => null,
            'deviation' => null,
            'start' => null,
            'end' => null
        ]);

        $loop_ctr = 1;

        $adj_time_from = 0;
        $adj_time_to = 1500;

        while ($loop_ctr >= 0) {
            self::generate_schedule_v6($company, $schedule_date, $adj_time_from, $adj_time_to, $tms_availabilty, $pumps_availabilty, $bps_availabilty, $schedule_preference, $restriction_start, $restriction_end);
            $adj_time_from = $adj_time_to + 1;
            $adj_time_to = $adj_time_to + 1500;
            $loop_ctr-=1;
        }
    }

    public static function groupBy(array $array, $groupByKey)
    {
        return array_reduce($array, function ($result, $item) use ($groupByKey) {
            $key = $item[$groupByKey];

            if (!isset($result[$key])) {
                $result[$key] = [];
            }

            $result[$key][] = $item;

            return $result;
        }, []);
    }

    
    public static function searchAndUpdateArray($arrayOfArrays, $searchCriteria, $updateValues) {

        $match = false;

        foreach ($arrayOfArrays as &$innerArray) {
            
            foreach ($searchCriteria as $key => $value) {
                if (!isset($innerArray[$key]) || $innerArray[$key] !== $value) {
                    $match = false;
                    break;
                } else {
                    $match = true;
                }
            }

            if ($match) {
                // Update the matched array with new values
                foreach ($updateValues as $key => $value) {
                    $innerArray[$key] = is_array($value) ? $innerArray[$key] + $value['value'] : $value;
                }
            }
        }

        return ['data' => $arrayOfArrays, 'match' => $match];
    }

    public static function sortByDate($a , $b, $key) {
        return $a[$key] <=> $b[$key];
    }

}