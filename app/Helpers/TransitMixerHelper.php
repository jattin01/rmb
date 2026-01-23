<?php

namespace App\Helpers;

use App\Models\TransitMixer;
use Carbon\Carbon;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class TransitMixerHelper
{

    
    public static function transitMixersSchedule(array $schedules,string $startTime, string $endTime, string $deliveryDate) : array 
    {

        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);
        $schArray = [];
        $scheduleData = [];
        $scheduleStripeData = [];

        $new_schedules = [];
// dd($schedules);
        foreach ($schedules as $schValue) {

            $key = array_search($schValue['transit_mixer'], array_column($new_schedules, 'transit_mixer'));

            if ($key !== false) {
                $new_schedules[$key]['end_time'] = $schValue['return_end'];
                $new_schedules[$key]['total_time'] = $new_schedules[$key]['total_time'] + ($schValue['loading_time'] + $schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time'] + $schValue['return_time']);
                $new_schedules[$key]['total_batching_qty'] += (int)$schValue['batching_qty'];
                $new_schedules[$key]['total_actual_time'] = Carbon::parse($schValue['return_end']) -> diffInMinutes(Carbon::parse($new_schedules[$key]['start_time']));
                array_push($new_schedules[$key]['utilization'],[

                    'order_no' => $schValue['order_no'],
                    'batching_qty' => $schValue['batching_qty'],

                    'qc_start' => $schValue['qc_start'],
                    'qc_time' => $schValue['qc_time'],
                    'qc_end' => $schValue['qc_end'],

                    'loading_start' => $schValue['loading_start'],
                    'loading_time' => $schValue['loading_time'],
                    'loading_end' => $schValue['loading_end'],

                    'travel_start' => $schValue['travel_start'],
                    'travel_time' => $schValue['travel_time'],
                    'travel_end' => $schValue['travel_end'],

                    'pouring_start' => $schValue['pouring_start'],
                    'pouring_time' => $schValue['pouring_time'],
                    'pouring_end' => $schValue['pouring_end'],

                    'insp_start' => $schValue['insp_start'],
                    'insp_time' => $schValue['insp_time'],
                    'insp_end' => $schValue['insp_end'],

                    'cleaning_start' => $schValue['cleaning_start'],
                    'cleaning_time' => $schValue['cleaning_time'],
                    'cleaning_end' => $schValue['cleaning_end'],

                    'return_start' => $schValue['return_start'],
                    'return_time' => $schValue['return_time'],
                    'return_end' => $schValue['return_end'],
                ]);
            } else {
                array_push($new_schedules, [
                    'transit_mixer' => $schValue['transit_mixer'],
                    'capacity' => $schValue['truck_capacity'],
                    'order_no' => $schValue['order_no'],
                    'location' => $schValue['location'],
                    'id' => $schValue['id'],
                    'start_time' => $schValue['loading_start'],
                    'end_time' => $schValue['return_end'],
                    'total_time' =>  $schValue['loading_time'] + $schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time'] + $schValue['return_time'],
                    'total_batching_qty' =>$schValue['batching_qty'] ,// $schValue['loading_time'] + $schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time'] + $schValue['return_time'],

                    'total_actual_time' => Carbon::parse($schValue['return_end']) -> diffInMinutes(Carbon::parse($schValue['loading_start'])),
                    'utilization' => [
                        array(
                            'order_no' => $schValue['order_no'],
                            'batching_qty' => $schValue['batching_qty'],

                            'qc_start' => $schValue['qc_start'],
                            'qc_time' => $schValue['qc_time'],
                            'qc_end' => $schValue['qc_end'],
        
                            'loading_start' => $schValue['loading_start'],
                            'loading_time' => $schValue['loading_time'],
                            'loading_end' => $schValue['loading_end'],
        
                            'travel_start' => $schValue['travel_start'],
                            'travel_time' => $schValue['travel_time'],
                            'travel_end' => $schValue['travel_end'],
        
                            'pouring_start' => $schValue['pouring_start'],
                            'pouring_time' => $schValue['pouring_time'],
                            'pouring_end' => $schValue['pouring_end'],
        
                            'insp_start' => $schValue['insp_start'],
                            'insp_time' => $schValue['insp_time'],
                            'insp_end' => $schValue['insp_end'],
        
                            'cleaning_start' => $schValue['cleaning_start'],
                            'cleaning_time' => $schValue['cleaning_time'],
                            'cleaning_end' => $schValue['cleaning_end'],
        
                            'return_start' => $schValue['return_start'],
                            'return_time' => $schValue['return_time'],
                            'return_end' => $schValue['return_end'],
                        )
                    ]
                ]);
            }
        }
        foreach ($new_schedules as $valkey => $scheduleVal) {
            $scheduleData = [];
            $scheduleStripeData = [];
            $new_i = 1;
            $dTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['start_time']));
            $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['end_time']));

            foreach ($slots as $timeKey => $timeValue) {
                if ($dTFSsch == $timeValue['end_time_date']) {
                    $schData = self::scheduleDataTransitMixers($scheduleVal);
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
            'transit_mixers' => $new_schedules
        ];
    }

    public static function scheduleDataTransitMixers(array $value) : array
    {
        $newStartDate = strtotime($value['end_time']) < strtotime($value['start_time']) ? strtotime($value['end_time']) : strtotime($value['start_time']);
        $newEndDate = strtotime($value['end_time']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['start_time']);
        $endDate = strtotime($value['end_time']);
        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $pixelsKeyValue = [];

        foreach ($value['utilization'] as $key => $val) {
            $margin = 0;
            if ($key > 0) {
                $margin =  (Carbon::parse($value['utilization'][$key - 1]['return_end']) -> diffInMinutes($value['utilization'][$key]['loading_start'], false));
            }
                array_push($pixelsKeyValue, [
                    'margin' => $margin <= 1 ? 0 : ($margin-1)*1.5,

                    'loading_pixels' => 1.5 * (int) $val['loading_time'],
                    'loading_start' => $val['loading_start'],
                    'loading_end' => $val['loading_end'],

                    'qc_pixels' => 1.5 * (int) $val['qc_time'],
                    'qc_start' => $val['qc_start'],
                    'qc_end' => $val['qc_end'],

                    'insp_pixels' => 1.5 * (int) $val['insp_time'],
                    'insp_start' => $val['insp_start'],
                    'insp_end' => $val['insp_end'],

                    'travel_pixels' => 1.5 * (int) $val['travel_time'],
                    'travel_start' => $val['travel_start'],
                    'travel_end' => $val['travel_end'],

                    'pouring_pixels' => 1.5 * (int) $val['pouring_time'],
                    'pouring_start' => $val['pouring_start'],
                    'pouring_end' => $val['pouring_end'],

                    'cleaning_pixels' => 1.5 * (int) $val['cleaning_time'],
                    'cleaning_start' => $val['cleaning_start'],
                    'cleaning_end' => $val['cleaning_end'],

                    'return_pixels' => 1.5 * (int) $val['return_time'],
                    'return_start' => $val['return_start'],
                    'return_end' => $val['return_end'],

                    'order_no' => $val['order_no'],
                    'batching_qty' => $val['batching_qty'],
                ]);
        }

        $totalPixles = 1.5 * ((int) $totalMinutes);

        $hoursDifference = $totalMinutes / 60;
        $colspan =  ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0){
            $colspan += 1; 
        }
        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }

        $rData = [
            'id' => $value['id'],
            'transit_mixer' => $value['transit_mixer'],
            'total_minutes' => $totalMinutes,
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'total_pixels' => $totalPixles,
            'start_minutes' => (int) $startMinutes,
            'multi_pixels' => $pixelsKeyValue
        ];
        return $rData;
    }

    public static function get_available_trucks($trucks, $company, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location = null)
    {
        $data = null;
        $index = null;
        // $data_no_loc = null;
        // $index_no_loc = null;

        $min_date = $return_end;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
            $min_date = $location_end_time;
        }
        if (isset($restriction_start) && isset($restriction_end)) {
            if ((Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) || (Carbon::parse($min_date) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end)))) {
                return null;
            }
        }
        foreach ($trucks as $truck_key => $truck) {
            if ($truck['truck_capacity'] == $truck_cap 
            && Carbon::parse($truck['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_from']) -> lte($min_date)
            && Carbon::parse($truck['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_upto']) -> gte($min_date)
            ) {
                if (isset($location)) {
                    if ($truck['location'] == $location) {
                        $data = $truck;
                        $index = $truck_key;
                        break;
                    }
                    // else {
                    //     if (!isset($data_no_loc))
                    //     {
                    //         $data_no_loc = $truck;
                    //         $index_no_loc = $truck_key;
                    //     } 
                    // }
                } else {
                    $data = $truck;
                    $index = $truck_key;
                    break;
                }
            }
        }
        // if (!isset($data) && !isset($index)) {
        //     $data = $data_no_loc;
        //     $index = $index_no_loc;
        // }
        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }
    public static function getAvailableTrucks($trucks, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location = null)
    {
        $data = null;
        $index = null;
        // $data_no_loc = null;
        // $index_no_loc = null;

        $min_date = $return_end;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
            $min_date = $location_end_time;
        }
        if (isset($restriction_start) && isset($restriction_end)) {
            if ((Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) || (Carbon::parse($min_date) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end)))) {
                return null;
            }
        }
        foreach ($trucks as $truck_key => $truck) {
            if ($truck['truck_capacity'] == $truck_cap && Carbon::parse($truck['free_from']) -> lte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_from']) -> lte($min_date)
            && Carbon::parse($truck['free_upto']) -> gte(Carbon::parse($loading_start)) &&  Carbon::parse($truck['free_upto']) -> gte($min_date)
            ) {
                if (isset($location)) {
                    if ($truck['location'] == $location) {
                        $data = $truck;
                        $index = $truck_key;
                        break;
                    }
                    // else {
                    //     if (!isset($data_no_loc))
                    //     {
                    //         $data_no_loc = $truck;
                    //         $index_no_loc = $truck_key;
                    //     } 
                    // }
                } else {
                    $data = $truck;
                    $index = $truck_key;
                    break;
                }
            }
        }
        // if (!isset($data) && !isset($index)) {
        //     $data = $data_no_loc;
        //     $index = $index_no_loc;
        // }
        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }

    public static function getTrucksAvailability(int $company_id, string $schedule_date, array $transit_mixer_ids) : array
    {
        $tms_availabilty = [];

        $tms = TransitMixer::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "transit_mixers.group_company_id");
        })->select("truck_name", "truck_capacity", "loading_time", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company_id)
            ->where("transit_mixers.status", ConstantHelper::ACTIVE)
            ->whereIn("transit_mixers.id", $transit_mixer_ids)
            ->get();

        foreach ($tms as $tm) {
            $tms_availabilty[] = array(
                'truck_name' => $tm->truck_name,
                'truck_capacity' => $tm->truck_capacity,
                'loading_time' => $tm->loading_time,
                'free_from' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->gt(Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)) ? Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
            );
        }
        return $tms_availabilty;
    }
}
