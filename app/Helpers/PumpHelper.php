<?php

namespace App\Helpers;

use App\Models\Pump;
use Carbon\Carbon;
use App\Models\SelectedOrder;
use Illuminate\Support\Facades\Log;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class PumpHelper
{
    public static function pumpsSchedule(array $schedules,string $startTime, string $endTime, string $deliveryDate) : array 
    {
        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);
        $schArray = [];
        $scheduleData = [];
        $scheduleStripeData = [];

        $new_schedules = [];

        foreach ($schedules as $schValue) {
            $key = array_search($schValue['pump'], array_column($new_schedules, 'pump'));
            if ($key !== false) {
                $new_schedules[$key]['end_time'] = $schValue['cleaning_end'];
                $new_schedules[$key]['total_time'] = $new_schedules[$key]['total_time'] + ($schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time']);
                $new_schedules[$key]['total_actual_time'] = Carbon::parse($schValue['cleaning_end']) -> diffInMinutes(Carbon::parse($new_schedules[$key]['start_time']));
                $new_schedules[$key]['total_batching_qty'] += (int)$schValue['batching_qty'];
                array_push($new_schedules[$key]['utilization'],[

                    'order_no' => $schValue['order_no'],
                    'batching_qty' => $schValue['batching_qty'],

                    'qc_start' => $schValue['qc_start'],
                    'qc_time' => $schValue['qc_time'],
                    'qc_end' => $schValue['qc_end'],

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
                    'pump' => $schValue['pump'],
                    'type' => $schValue['type'],
                    'capacity' => $schValue['pump_capacity'],
                    'order_no' => $schValue['order_no'],
                    'location' => $schValue['location'],
                    'id' => $schValue['id'],
                    'start_time' => $schValue['qc_start'],
                    'end_time' => $schValue['cleaning_end'],
                    'total_time' => $schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time'],
                    'total_actual_time' => $schValue['qc_time'] + $schValue['travel_time'] + $schValue['pouring_time'] + $schValue['insp_time'] + $schValue['cleaning_time'],
                    'total_batching_qty' => (int)($schValue['batching_qty']),
                    'utilization' => [
                        array(
                            'order_no' => $schValue['order_no'],
                            'batching_qty' => $schValue['batching_qty'],

                            'qc_start' => $schValue['qc_start'],
                            'qc_time' => $schValue['qc_time'],
                            'qc_end' => $schValue['qc_end'],
        
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
                    $schData = self::scheduleDataPumps($scheduleVal);
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
            'pumps' => $new_schedules
        ];
    }

    public static function scheduleDataPumps(array $value) : array
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
                $margin =  (Carbon::parse($value['utilization'][$key - 1]['cleaning_end']) -> diffInMinutes($value['utilization'][$key]['qc_start'], false));
            }
                array_push($pixelsKeyValue, [
                    'margin' => $margin <= 1 ? 0 : ($margin-1)*1.5,

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
        $colspan =   ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0){
            $colspan += 1; 
        }
        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }

        $rData = [
            'id' => $value['id'],
            'pump' => $value['pump'],
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

    public static function get_available_pumps($pumps, $order_id, $company, $pump_start_time, $pump_end_time, $pump_cap, $trip, $selected_order_pump_schedules, $location_end_time, $pump_qty, $location = null)
    {

        try{
        
        $data = null;
        $index = null;
        $dataNew = null;
        $indexNew = null;

        $min_end_date = $pump_end_time;
        $min_start_date = $pump_start_time;
        // if($order_id == 39 ){
        //     dd($min_end_date,$min_start_date);
        // }
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($pump_end_time))) {
            $min_end_date = $location_end_time;
        }

        $dataNext = null;
        $indexNext = null;

        $requiredPumpCount = false;
       
            $order = SelectedOrder::find($order_id);
            // if($order->order_no){
            //     Log::info("PUMP".json_encode($pumps));
            // }
            foreach($pumps as $pumpKey => $pump) {

                // if(!isset($pump['pump_capacity'])) {
                //     dd($pumps, $order);
                // }
                if($pump['pump_capacity'] != $order->pump){
                
                    continue;
                }

                // if (Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date) &&
                //     Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)
                // ) {
                    // if ((isset($pump['lock_future_order']) && $pump['lock_future_order'] == $order_id) || !isset($pump['lock_future_order'])) {
                    $pumpAvlOrderId = isset($trip) ? $pump['order_id'] : $pump['order_id_wo_trip'];
                    $pumpOrderId = isset($trip) ? $order_id . "-" . $trip : $order_id;
                    $selectedOrderPumpScheduleArray  = array_filter($selected_order_pump_schedules , function ($item) use($order_id) {
                        
                        return ($item['order_id'] == $order_id);
                    });

                    $selectedOrderPumpScheduleCount = count($selectedOrderPumpScheduleArray);

                    if($selectedOrderPumpScheduleCount < $pump_qty){
                        $requiredPumpCount = true;
                    }
                    else{
                        $requiredPumpCount = false;
                    }                    

                    // if($pump['order_id']) {
                    //     if ($trip) {
                    //         $pumpAvlOrderId = $pump['order_id'];
                    //         $pumpOrderId = $order_id . '-' . $trip;
                    //     } else {
                    //         $pumpOrderId = $order_id;
                    //         $pumpAvlOrderIdIndex = implode("-",$pump['order_id']);
                    //         $pumpAvlOrderId = $pumpAvlOrderIdIndex[0];
                    //     }
                    // }
                    // if($requiredPumpCount){

                        if ($pumpAvlOrderId == $pumpOrderId ) {
                            
                          
                            if (Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date) &&
                            Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)) {
                                // if($order_id == 3 && $trip == 3){
                                //     echo ",,  order2->".$order_id.'LOC:'.$location;
                                //     echo "-2";
                                // }
                                $data = $pump;
                                $index = $pumpKey;
                                // echo "[BREAK]";
                                break;
                            }
                            
                        } else {
                            if($requiredPumpCount){
                                
                                if (Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date) &&
                                Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)
                                ) 
                                {   
                                    if (!isset($dataNew) && $pump['order_id'] == null) {

                                        if ((isset($location) && $location == $pump['location']) || empty($pump['location'])) {
                                          

                                            $dataNew = $pump;
                                            $indexNew = $pumpKey;
                                        } else {
                                            
                                            if (!isset($dataNext)) {
                                                $dataNext = $pump;
                                                $indexNext = $pumpKey;
                                            }
                                        }
                                    }  
                                }
                                
                            }
                           
                            
                        }
            }
        
        if (!isset($data)) {
            $data = $dataNew;
            $index = $indexNew;
        }

        if (!isset($data)) {
            $data = $dataNext;
            $index = $indexNext;
        }
        
        if (isset($data) && $data) {
            
            return ['pump' => $data, 'index' => $index];
        } else {
            return null;
        }
    }catch(\Exception $e){
        dd($e);
    }

    }

    public static function get_available_pumps_copy(&$pumps, &$pumps_availability, &$pump_ids, $company, $pump_start_time, $pump_end_time, $pump_cap, $first_trip_qc_start, $trip, &$selected_order_pump_schedules, $location_end_time, $qty, $truck_capacity, $pump_qty, $location = null)
    {
        $data = null;
        $index = null;

        $data_new = null;
        $index_new = null;

        $data_no_loc = null;
        $index_no_loc = null;

        $min_end_date = $pump_end_time;
        $min_start_date = $pump_start_time;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($pump_start_time))) {
            $min_start_date = $location_end_time;
        }

        foreach ($pumps as $pumpKey => $pump) {
            if ( 
            Carbon::parse($pump['free_from']) -> lte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_from']) -> lte($min_end_date) &&
            Carbon::parse($pump['free_upto']) -> gte(Carbon::parse($min_start_date)) &&  Carbon::parse($pump['free_upto']) -> gte($min_end_date)
            ) {
                if (count($pump_ids) > 0)
                {
                    if (in_array($pump['pump_name'], $pump_ids))
                    {
                        if (isset($location)) {
                            if ($pump['location'] == $location) {
                                $data = $pump;
                                $index = $pumpKey;
                                break;
                            }
                            else {
                                if (!isset($data_no_loc))
                                {
                                    $data_no_loc = $pump;
                                    $index_no_loc = $pumpKey;
                                } 
                            }
                        } else {
                            $data = $pump;
                            $index = $pumpKey;
                            break;
                        }
                    }
                    else 
                    {
                        if (!isset($data_new))
                        {
                            if (isset($location)) {
                                if ($pump['location'] == $location) {
                                    $data_new = $pump;
                                    $index_new = $pumpKey;
                                }
                                else {
                                    if (!isset($data_no_loc))
                                    {
                                        $data_no_loc = $pump;
                                        $index_no_loc = $pumpKey;
                                    } 
                                }
                            } else {
                                $data_new = $pump;
                                $index_new = $pumpKey;
                            }
                        }  
                    }
                }
                else 
                {
                    if (isset($location)) {
                        if ($pump['location'] == $location) {
                            $data = $pump;
                            $index = $pumpKey;
                            break;
                        }
                        else {
                            if (!isset($data_no_loc))
                            {
                                $data_no_loc = $pump;
                                $index_no_loc = $pumpKey;
                            } 
                        }
                    } else {
                        $data = $pump;
                        $index = $pumpKey;
                        break;
                    }
                }    
            }
                
        }

        if (!isset($data))
        {
            $data = $data_new;
            $index = $index_new;
        }

        if (!isset($data))
        {
            $data = $data_no_loc;
            $index = $index_no_loc;
        }
        
        if (isset($data) && isset($index)) {
            $temp_pump_ids = $pump_ids;
            $temp_pump_ids[] = $data['pump_name'];
            $temp_pump_ids = array_unique($temp_pump_ids);

            if (count($pump_ids) > 0 && (!in_array($data['pump_name'], $temp_pump_ids))) {
                return null;
            } else {
                return ['pump' => $data, 'index' => $index];
            }
        } else {
            return null;
        }
    }

    public static function getPumpsAvailability(int $company_id, string $schedule_date, array $pump_ids) : array
    {
        $pumps_availabilty = [];

        $ps = Pump::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "pumps.group_company_id");
        })->select("pump_name", "pump_capacity", "type", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company_id) 
            ->where("pumps.status", ConstantHelper::ACTIVE) 
            -> whereIn("pumps.id", $pump_ids) 
            ->get();

        foreach ($ps as $p) {
            $pumps_availabilty[] = array(
                'pump_name' => $p->pump_name,
                'pump_type' => $p->type,
                'pump_capacity' => $p->pump_capacity,
                'free_from' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->gt(Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)) ? Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->addDay()->format(ConstantHelper::SQL_DATE_TIME) : Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
                'order_id' => null,
                'order_id_wo_trip' => null
            );
        }
        return $pumps_availabilty;
    }

    public static function getPumpStartAndEndTime(string $qc_start, string $pouring_end, string $pouring_end_prev, string $return_end, string $cleaning_end, bool $last_trip, int $trip)
    {
        //Only Valid for single Pump
        $pump_start = "";
        $pump_end = "";
        if ($trip == 1) // First Trip
        {
            $pump_start = $qc_start;
            if (!$last_trip) //More trips are required
            {
                $pump_end = $pouring_end;
            } else {
                // $pump_end = $return_end;
                $pump_end = $cleaning_end;
            }
        }
        else if (!$last_trip) // Middle Trip
        {
            $pump_start = $pouring_end_prev;
            $pump_end = $pouring_end;
        }
        else // Last Trip
        {
            $pump_start = $pouring_end_prev;
            // $pump_end = $return_end;
            $pump_end = $cleaning_end;
        }
        return [
            'pump_start' => $pump_start,
            'pump_end' => $pump_end,
        ];
    }
    public static function getPumpStartAndEndTimeCopy(string $qc_start, string $pouring_end, string $pouring_end_prev, string $return_end, string $cleaning_end, bool $last_trip, int $trip)

    {
        //Only Valid for single Pump
        $pump_start = "";
        $pump_end = "";
        if ($trip == 1) // First Trip
        {
            $pump_start = $qc_start;
            if (!$last_trip) //More trips are required
            {
                $pump_end = $pouring_end;
            } else {
                $pump_end = $return_end;
            }
        }
        else if (!$last_trip) // Middle Trip
        {
            $pump_start = $pouring_end_prev;
            $pump_end = $pouring_end;
        }
        else // Last Trip
        {
            $pump_start = $pouring_end_prev;
            $pump_end = $return_end;
        }
        return [
            'pump_start' => $pump_start,
            'pump_end' => $pump_end,
        ];
    }
}
