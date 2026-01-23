<?php

namespace App\Helpers;

use App\Models\BatchingPlant;
use App\Models\SelectedOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BatchingPlantHelper
{
    public static function batchingPlantSchedule(array $schedules,string $startTime, string $endTime, string $deliveryDate, array $bpScheduleGap) : array 
    {
        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);

        $schArray = [];
        $scheduleData = [];
        $scheduleStripeData = [];

        $new_schedules = [];

        foreach ($schedules as $schValue) {
            $key = array_search($schValue['batching_plant'], array_column($new_schedules, 'batching_plant'));
            if ($key !== false) {
                $new_schedules[$key]['loading_end'] = $schValue['loading_end'];
                $new_schedules[$key]['loading_time'] = $new_schedules[$key]['loading_time'] + $schValue['loading_time'];
                $new_schedules[$key]['total_batching_qty'] += (int)$schValue['batching_qty'];
                $new_schedules[$key]['total_batching_time'] += $schValue['loading_time'];
                $new_schedules[$key]['total_time'] = Carbon::parse($schValue['loading_end']) -> diffInMinutes(Carbon::parse($new_schedules[$key]['loading_start']));

                array_push($new_schedules[$key]['utilization'],[
                    'loading_start' => $schValue['loading_start'],
                    'loading_time' => $schValue['loading_time'],
                    'loading_end' => $schValue['loading_end'],
                    'order_no' => $schValue['order_no'],
                    'batching_qty' => $schValue['batching_qty'],
                    'mix_code' => $schValue['mix_code']
                ]);

            } else {
                array_push($new_schedules, [
                    'batching_plant' => $schValue['batching_plant'],
                    'capacity' => $schValue['capacity'],
                    'order_no' => $schValue['order_no'],
                    'location' => $schValue['location'],
                    'id' => $schValue['id'],
                    'loading_start' => $schValue['loading_start'],
                    'loading_end' => $schValue['loading_end'],
                    'loading_time' => $schValue['loading_time'],
                    'total_batching_qty' => (int)($schValue['batching_qty']),
                    'total_batching_time' => (int)($schValue['loading_time']),
                    'total_time' => (int)($schValue['loading_time']),
                    'utilization' => [
                        array(
                        'loading_start' => $schValue['loading_start'],
                        'loading_end' => $schValue['loading_end'],
                        'loading_time' => $schValue['loading_time'],
                        'order_no' => $schValue['order_no'],
                        'batching_qty' => $schValue['batching_qty'],
                        'mix_code' => $schValue['mix_code']
                        )
                    ]
                ]);
            }
        }
        foreach ($new_schedules as $valkey => $scheduleVal) {
            $scheduleData = [];
            $scheduleStripeData = [];
            $new_i = 1;
            $dTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['loading_start']));
            $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['loading_end']));

            foreach ($slots as $timeKey => $timeValue) {
                if ($dTFSsch == $timeValue['end_time_date']) {
                    $schData = self::scheduleDataBatchingPlants($scheduleVal, $bpScheduleGap);
                    $new_i = $schData['colspan'];
                    $schData['slot'] = $timeValue;
                    array_push($schArray,  $schData);
                    array_push($scheduleData,  $schData);
                    array_push($scheduleStripeData,  $schData['stripe']);
                } else {
                    if($new_i > 1){
                        $new_i--;
                        continue;
                    }
                    $schData = ['null'];
                    $schData['slot'] = $timeValue;
                    array_push($schArray,  $schData);
                    array_push($scheduleData, $schData);
                }
            }
            $new_schedules[$valkey]['resultData'] = $scheduleData;
            $new_schedules[$valkey]['stripe_data'] = $scheduleStripeData;
        }
        return [
            'heading' => $slots,
            'resData' => CommonHelper::groupBy($new_schedules, 'location'),
            'batching_plants' => $new_schedules
        ];
    }

    public static function scheduleDataBatchingPlants(array $value, array $bpScheduleGap) : array
    {

        $newStartDate = strtotime($value['loading_end']) < strtotime($value['loading_start']) ? strtotime($value['loading_end']) : strtotime($value['loading_start']);
        $newEndDate = strtotime($value['loading_end']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['loading_start']);
        $endDate = strtotime($value['loading_end']);
        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $pixelsKeyValue = [];

        foreach ($value['utilization'] as $key => $val) {
            $margin = 0;
            $gapStartTime = null;
            $gapEndTime = null;
            $isGap = false;
            $gapSlot = [];
            if ($key > 0) {
                $margin =  (Carbon::parse($value['utilization'][$key - 1]['loading_end']) -> diffInMinutes($value['utilization'][$key]['loading_start'], false));
                $gapStartTime = Carbon::parse($value['utilization'][$key - 1]['loading_end']) -> copy() -> addMinute() -> format(ConstantHelper::DATE_TIME_FORMAT);
                $gapEndTime = Carbon::parse($value['utilization'][$key]['loading_start']) -> copy() -> subMinute() -> format(ConstantHelper::DATE_TIME_FORMAT);
                $gapSlot = self::isScheduleBetweenGap($gapStartTime, $gapEndTime, $bpScheduleGap);
                $isGap = $margin > 1 && count($gapSlot) > 0;
                // dd($margin, $gapStartTime, $gapEndTime, $gapSlot, $isGap);
            }

            if ($isGap) {
                array_push($pixelsKeyValue, [
                    'margin' => 0,
                    'mix' => $val['mix_code'],
                    'loading_pixels' => ($margin-1)*1.5,
                    'loading_start' => $gapStartTime,
                    'loading_end' => $gapEndTime,
                    'order_no' => $val['order_no'],
                    'batching_qty' => $val['batching_qty'],
                    'type' => "N",
                    'reason' => self::getScheduleGapReason($gapStartTime, $gapEndTime, $gapSlot)
                ]);
            }
                array_push($pixelsKeyValue, [
                    'margin' => $isGap ? 0 : ($margin <= 1 ? 0 : ($margin - 1) * 1.5),
                    'mix' => $val['mix_code'],
                    'loading_pixels' => (1.5 * (int) $val['loading_time']),
                    'loading_start' => $val['loading_start'],
                    'loading_end' => $val['loading_end'],
                    'order_no' => $val['order_no'],
                    'batching_qty' => $val['batching_qty'],
                    'type' => "A"
                ]);
        }

        $loadingPixles = 1.5 * ((int) $value['loading_time'] );
        $totalPixles = 1.5 * ((int) $totalMinutes);

        $hoursDifference = $totalMinutes / 60;
        $colspan =   ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0 ){
            $colspan += 1; 
        }
        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }
        $rData = [
            'id' => $value['id'],
            'batching_plant_name' => $value['batching_plant'],
            'total_minutes' => $totalMinutes,
            'end_time' => $value['loading_end'],
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'total_pixels' => $totalPixles,
            'loading_pixels' => $loadingPixles,
            'start_minutes' => (int) $startMinutes,
            'multi_pixels' => $pixelsKeyValue
        ];
        return $rData;
    }

    public static function get_available_batching_plants($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $location_end_time, $plant_name = null)
    {
        if ($restriction_start && $restriction_end) {
            if (Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) {
                return null;
            }
        }
        $min_date = $loading_end;
        
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($loading_end))) {
            $min_date = $location_end_time;
        }
        // dd($min_date,$location_end_time,$loading_start,$loading_end);
        $data = null;
        $index = null;

        $data_new = null;
        $index_new = null;
        // dd($plant_name);

        foreach ($batching_plants as $batching_plant_key => $batching_plant) {

            if ($batching_plant['location'] == $location
            && Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($min_date))
            && Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($min_date))
            ) {
               
                if (isset($plant_name)) {
                    if ($batching_plant['plant_name'] == $plant_name['plant_name']) {
                        $data =  $batching_plant;
                        $index =  $batching_plant_key;
                        break;
                    }
                    else {
                        if (!isset($data_new)) {
                            $data_new = $batching_plant;
                            $index_new = $batching_plant_key;
                        }
                    }
                } else {
                    $data =  $batching_plant;
                    $index =  $batching_plant_key;
                    break;
                }
            }
        }
        // dd($data);
        if (!isset($data) && !isset($index)) {
            $data = $data_new;
            $index = $index_new;
        }

        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }
    public static function getAvailableBatchingPlants($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $plant_name = null, $orderPump = null)
    {
        
        if (isset($restriction_start) && isset($restriction_end)) {
            if (Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) {
                return null;
            }
        }
        $data = null;
        $index = null;

        $data_new = null;
        $index_new = null;

        // dd($batching_plants);

        foreach ($batching_plants as $batching_plant_key => $batching_plant) {


            if( $batching_plant['location'] != $location) continue;


            if (Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_start)) 
                && Carbon::parse($batching_plant['free_from']) -> lte(Carbon::parse($loading_end))
                &&  Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_start))
                    &&  Carbon::parse($batching_plant['free_upto']) -> gte(Carbon::parse($loading_end))
               ) 
                {   
                if (isset($plant_name)) {
                    if ($batching_plant['plant_name'] == $plant_name) {
                        $data =  $batching_plant;
                        $index =  $batching_plant_key;
                        break;
                    }
                    else {
                        if (!isset($data_new)) {
                            $data_new = $batching_plant;
                            $index_new = $batching_plant_key;
                        }
                    }
                } else {
                    $data =  $batching_plant;
                    $index =  $batching_plant_key;
                    break;
                }
            }
        }
        if (!isset($data) && !isset($index)) {
            $data = $data_new;
            $index = $index_new;
        }
        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }

    public static function getMinOrderScheduleTime(int $company_id, int $user_id, string $shift_start, string $shift_end, string $schedule_date, string $location, array $location_orders) : string
    {
        $start_time = Carbon::parse($schedule_date . ConstantHelper::GROUP_COMP_START_TIME) -> format(ConstantHelper::SQL_DATE_TIME);

        // $order = SelectedOrder::where("group_company_id", $company_id) -> where("user_id", $user_id)
        // -> whereBetween("delivery_date",  [$shift_start, $shift_end])
        // ->whereNull("start_time") -> where("selected", true) -> where("location", $location) -> get() -> sortBy("order_start_time")
        // -> values() -> first();
        // <======================= 8 april 25
        // if($location == 'MUS'){
        //         dd($location_orders);

        //     }
        if (count($location_orders) > 0 && isset($location_orders[0]['next_loading'])) {
            $start_time = Carbon::parse($location_orders[0]['next_loading']) -> format(ConstantHelper::SQL_DATE_TIME);
        }

        foreach ($location_orders as $loc_order) {
            if (isset($location_orders[0]['next_loading']) && Carbon::parse($loc_order['next_loading']) -> lte(Carbon::parse($start_time))) {
                $start_time = $loc_order['next_loading'];
            }
        }
        // ========================>

        // if (isset($order)) {
            $start_time = Carbon::parse($start_time) -> format(ConstantHelper::SQL_DATE_TIME);
        // }
            
        return $start_time;
    }
    public static function getMinOrderScheduleTimeCopy(int $company_id, int $user_id, string $shift_start, string $shift_end, string $schedule_date) : string
    {
        $start_time = Carbon::parse($schedule_date . ConstantHelper::GROUP_COMP_START_TIME) -> format(ConstantHelper::SQL_DATE_TIME);

        $order = SelectedOrder::where("group_company_id", $company_id) -> where("user_id", $user_id)
        -> whereBetween("delivery_date",  [$shift_start, $shift_end])
        ->whereNull("start_time") -> where("selected", true) -> orderBy("order_start_time")
        ->first();

        if (isset($order)) {
            $start_time = Carbon::parse($order -> order_start_time) -> format(ConstantHelper::SQL_DATE_TIME);
        }
        return $start_time;
    }

    public static function getBatchingPlantAvailability(int $company_id, string $schedule_date, array $batching_plant_ids, string $bp_start_time, string $location) : array
    {
        $bps_availabilty = [];

        $bps = BatchingPlant::join("location_shifts", function ($join) {
            $join->on("location_shifts.group_company_id", "=", "batching_plants.group_company_id")
                ->on("location_shifts.company_location_id", "=", "batching_plants.company_location_id");
        })->leftJoin("company_locations", function ($query) {
            $query->on("company_locations.group_company_id", "=", "location_shifts.group_company_id")
                ->on("company_locations.id", "=", "location_shifts.company_location_id");
        })->select("location_shifts.group_company_id", "location", "plant_name", "capacity", "shift_start", "shift_end", "company_locations.location")
            ->where("location_shifts.group_company_id", $company_id)
            ->where("batching_plants.status", ConstantHelper::ACTIVE)
            ->whereIn("batching_plants.id", $batching_plant_ids)
            ->where("company_locations.location", $location)
            ->get();

        foreach ($bps as $bp) {
            // $bps_availabilty[] = array(
            //     'plant_name' => $bp->plant_name,
            //     'plant_capacity' => $bp->capacity,
            //     'free_from' => $bp_start_time,
                // 'free_upto' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->gt(Carbon::parse($schedule_date . ' ' . $bp->shift_end)) ? Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $bp->shift_end)->format(ConstantHelper::SQL_DATE_TIME),
            //     'location' => $bp?->location,
            // );

            $endTime =  Carbon::parse($schedule_date . ' ' . $bp->shift_start)->gt(Carbon::parse($schedule_date . ' ' . $bp->shift_end)) ? Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $bp->shift_end)->format(ConstantHelper::SQL_DATE_TIME);


            $bps_availabilty[] = array(
                'plant_name' => $bp->plant_name,
                'free_from' => $bp_start_time,
                'free_upto' => $endTime,
                'start_time' => $bp_start_time,
                'end_time' => $endTime,
                'location' => $bp?->location,
            );
        }
        return $bps_availabilty;
    }
    public static function getBatchingPlantAvailabilityCopy(int $company_id, string $schedule_date, array $batching_plant_ids, string $bp_start_time) : array
    {
        $bps_availabilty = [];

$bp_start_time = Carbon::parse('2025-03-01 00:00:00');
// dd($bps_start_time);
        $bps = BatchingPlant::join("location_shifts", function ($join) {
            $join->on("location_shifts.group_company_id", "=", "batching_plants.group_company_id")
                ->on("location_shifts.company_location_id", "=", "batching_plants.company_location_id");
        })->leftJoin("company_locations", function ($query) {
            $query->on("company_locations.group_company_id", "=", "location_shifts.group_company_id")
                ->on("company_locations.id", "=", "location_shifts.company_location_id");
        })->select("location_shifts.group_company_id", "location", "plant_name", "capacity", "shift_start", "shift_end", "company_locations.location")
            ->where("location_shifts.group_company_id", $company_id)
            ->whereIn("batching_plants.id", $batching_plant_ids)
            ->get();

        foreach ($bps as $bp) {

            $endTime = Carbon::parse($schedule_date . ' ' . $bp->shift_start)->gt(Carbon::parse($schedule_date . ' ' . $bp->shift_end)) ? Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $bp->shift_end)->format(ConstantHelper::SQL_DATE_TIME);

            $bps_availabilty[] = array(
                'plant_name' => $bp->plant_name,
                'plant_capacity' => $bp->capacity,
                'free_from' => $bp_start_time,
                'free_upto' => $endTime,

                'start_time' => $bp_start_time,
                'end_time' => $endTime,
                'location' => $bp?->location,
            );
            // dd($bps_availabilty);
            // $bps_availabilty[] = array(
            //     'plant_name' => $bp->plant_name,
            //     'free_from' => $bp_start_time,
            //     'start_time' => $bp_start_time,
            //     'end_time' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->gt(Carbon::parse($schedule_date . ' ' . $bp->shift_end)) ? Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $bp->shift_end)->format(ConstantHelper::SQL_DATE_TIME),
            //     'location' => $bp?->location,
            // );
        }
        return $bps_availabilty;
    }

    public static function isScheduleBetweenGap(string $start_time, string $end_time, array $bpScheduleGap) : array 
    {
        $data = array_filter($bpScheduleGap, function ($gap) use ($start_time, $end_time) {
            if (Carbon::parse($gap['free_from']) -> gte(Carbon::parse($start_time)) && Carbon::parse($gap['free_from']) -> lte(Carbon::parse($end_time))) {
                return true;
            }
            else {
                return false;
            }
        });
        return $data;
    }
    public static function getScheduleGapReason(string $start_time, string $end_time, array $bpScheduleGapSlot) : string 
    {
        // dd($bpScheduleGapSlot);
        $reason = [];
        $reasonCount = 0;
        foreach ($bpScheduleGapSlot as $slotKey => $slotVal) {
            if ($reasonCount > 0) {
                if ($bpScheduleGapSlot[$slotKey - 1]['reason'] == $slotVal['reason']) {
                    $reason[$reasonCount - 1]['end_time'] = $slotVal['free_upto'];
                    continue;
                }
            }
            $reason[] = array(
                'start_time' => $slotVal['free_from'],
                'end_time' => $slotVal['free_upto'],
                'reason' => $slotVal['reason']
            );
            $reasonCount += 1;

        }
        $reasonString = "";

        foreach ($reason as $reasonKey => $reasonVal) {
            $reasonString = $reasonString . ($reasonKey > 0 ? ", " : "") .Carbon::parse($reasonVal['start_time']) -> format(ConstantHelper::SLOT_TIME_FORMAT) . " to " . Carbon::parse($reasonVal['end_time']) -> format(ConstantHelper::SLOT_TIME_FORMAT) . " | " . $reasonVal['reason'];
        }
        return $reasonString;
    }

    // public static function getMinAvailTime(array $availability, int $loading_time, $location, ?Carbon $restriction_start = null, ?Carbon $restriction_end = null)
    // {
    //     // Check if the array is empty
    //     if (empty($availability)) {
    //         return null;
    //     }

    //     // Initialize the minimum value with the first element's value
    //     $minValue = $availability[0]['free_from'];
    //     // $bp = $availability[0];
    //     // $bpIndex = 0;

    //     // Iterate through the array to find the minimum value
    //     foreach ($availability as $key => $item) {
    //         $freeFrom = Carbon::parse($item['free_from']);
    //         $freeUpto = Carbon::parse($item['free_upto']);
    //         if ($freeUpto -> gt($freeFrom) && $freeUpto -> diffInMinutes($freeFrom) >= $loading_time && $item['location'] == $location)
    //         {
    //             if (!(isset($restriction_start) && isset($restriction_end) && $freeFrom -> between($restriction_start, $restriction_end))) {
    //                 if ($freeFrom ->lt(Carbon::parse($minValue))) {
    //                     $minValue = $freeFrom;
    //                     $bp = $item;
    //                     $bpIndex = $key;
    //                 }
    //             } 
    //         }
    //     }
    //     // $batching_plant = $bp;
    //     // $batching_plant_index = $bpIndex;
    //     return $minValue;
    // }
    public static function getMinAvailTime(array $availability, int $loading_time, $location, ?Carbon $restriction_start = null, ?Carbon $restriction_end = null)
    {
        // Check if the array is empty
        if (empty($availability)) {
            return null;
        }

        // Initialize the minimum value with the first element's value
        // $minValue = $availability[0]['free_from'];

        $availability = array_values($availability);
        // dd($f); 
        $minValue = $availability[0]['free_from'];

        // Iterate through the array to find the minimum value
        foreach ($availability as $key => $item) {
            if (Carbon::parse($item['free_from']) ->lt(Carbon::parse($minValue))) {
                $minValue = $item['free_from'];
            }
        }
        return $minValue;
    }

    public static function getMinAvailTimeCopy(array $availability, int $loading_time, mixed $batching_plant, ?int $batching_plant_index, ?Carbon $restriction_start = null, ?Carbon $restriction_end = null) 
    {
        // Check if the array is empty
        if (empty($availability)) {
            return null;
        }

        // Initialize the minimum value with the first element's value
        $minValue = $availability[0]['free_from'];
        $bp = $availability[0];
        $bpIndex = 0;

        // Iterate through the array to find the minimum value
        foreach ($availability as $key => $item) {
            $freeFrom = Carbon::parse($item['free_from']);
            $freeUpto = Carbon::parse($item['free_upto']);
            if ($freeUpto -> gt($freeFrom) && $freeUpto -> diffInMinutes($freeFrom) >= $loading_time)
            {
                if (!(isset($restriction_start) && isset($restriction_end) && $freeFrom -> between($restriction_start, $restriction_end))) {
                    if ($freeFrom ->lt(Carbon::parse($minValue))) {
                        $minValue = $freeFrom;
                        $bp = $item;
                        $bpIndex = $key;
                    }
                } 
            }
        }
        $batching_plant = $bp;
        $batching_plant_index = $bpIndex;
        return $minValue;
    }

    public static function generateOrUpdateAvailability(int $user_id, string $schedule_date, int $company_id, string $shift_start, string $shift_end)
    {
        $availability = [];
        $batching_plants = SelectedOrder::join("selected_order_schedules", function ($query) {
            $query -> on("selected_order_schedules.order_id", "=", "selected_orders.id");
        })  
        -> where("selected_orders.group_company_id", $company_id) -> where("selected_orders.user_id", $user_id) -> whereDate("selected_orders.delivery_date", $schedule_date)
        -> orderBy("loading_start") -> get() -> groupBy("batching_plant");
        

        foreach ($batching_plants as $plantKey => $plantVal) {
            $plant_free_from = Carbon::parse($shift_start);
            $plant_location = null;
            foreach ($plantVal as $sch) {
                if ($plant_free_from -> lt(Carbon::parse($sch -> loading_start))) {
                    $availability[] = array(
                        'plant_name' => $sch -> batching_plant,
                        'plant_capacity' => "",
                        'free_from' => $plant_free_from,
                        'free_upto' => Carbon::parse($sch -> loading_start) -> copy() -> subMinute(),
                        'location' => $sch -> location,
                    );
                }
                $plant_free_from = Carbon::parse($sch -> loading_end) -> copy() -> addMinute();
                $plant_location = $sch -> location;
            }
            if ($plant_free_from -> lt(Carbon::parse($shift_end))) {
                $availability[] = array(
                    'plant_name' => $plantKey,
                    'plant_capacity' => "",
                    'free_from' => $plant_free_from,
                    'free_upto' => $shift_end,
                    'location' => $plant_location,
                );
            }
        }

        return $availability;
 
    }

}
