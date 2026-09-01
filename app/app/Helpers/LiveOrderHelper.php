<?php

namespace App\Helpers;

use Carbon\Carbon;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class LiveOrderHelper
{
    public static function getLiveOrderMarker() : float
    {
        $currentDateTime = Carbon::parse();
        $currentDateResetTime = Carbon::parse() -> setTime(18, 30) -> subDay();

        // $diffInMinutes = $currentDateTime -> diffInMinutes($currentDateResetTime, false);
        $diffInMinutes = $currentDateResetTime -> diffInMinutes($currentDateTime, false);

        if ($diffInMinutes < 0) {
            return 0;
        } else if ($diffInMinutes == 0) {
            return ConstantHelper::LIVE_MARKER_DEFAULT_MARGIN;
        } else {
            return ConstantHelper::LIVE_MARKER_DEFAULT_MARGIN + ($diffInMinutes * ConstantHelper::PER_MIN_MARKER_MARGIN);
        }

    }
    public static function orderGraphData(array $data, string $startTime, string $endTime, string $deliveryDate, int $totalTrips, array $fullSchedule): array
    {

        $resArr = [];
        $totalRdata = [];
        $stripeIdData = [];
        //Make time slots
        $scheduleStartTime = $fullSchedule[0]['planned_loading_start'];
        $scheduleEndTime = $fullSchedule[$totalTrips - 1]['planned_loading_end'];
        $totalBPTime = Carbon::parse($scheduleEndTime) -> diffInHours(Carbon::parse($scheduleStartTime)) > 0 ? Carbon::parse($scheduleEndTime) -> diffInHours(Carbon::parse($scheduleStartTime)) : 1 ;

        $actualQty = 0;
        $plannedQty = 0;

        $bpDetails = [];

        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);
        foreach ($data as $key => &$value) {
            //Actual Qty
            $actualQty += $value['quantity'];

            $totalRdata = [];
            $stripeIdData = [];
            //Set start and end time
            $newStartDate = strtotime($value['delivery_date']) < strtotime($value['planned_start_time']) ? strtotime($value['delivery_date']) : strtotime($value['planned_start_time']);
            $newEndDate = strtotime($value['delivery_date']) > strtotime($value['planned_end_time']) ? strtotime($value['delivery_date']) : strtotime($value['planned_end_time']);
            $dTFS = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, $newStartDate);
            $dTFE = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, $newEndDate);

            $schArray = [];
            $scheduleData = [];
            $scheduleStripeData = [];
            //Order Trip Schedule
            foreach ($value['schedule'] as $valkey => $scheduleVal) {
                //BP Details 
                // if (!array_key_exists($scheduleVal['batching_plant'], $bpDetails)) {
                //     $bpDetails[$scheduleVal['batching_plant']] = array(
                //         'actual_capacity' => $scheduleVal['capacity'],
                //         'avg_mixer_capacity' => $scheduleVal['avg_mixer_capacity']
                //     );
                // }
                //Planned Qty
                $plannedQty += $scheduleVal['batching_qty'];
                $scheduleData = [];
                $scheduleStripeData = [];
                $new_i = 1;
                $dTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['planned_loading_start']));
                $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['planned_return_end']));
                //Check if schedule is within time slot
                foreach ($slots as $timeKey => $timeValue) {
                    if ($dTFSsch == $timeValue['end_time_date']) {
                        $schData = self::scheduleData($scheduleVal);
                        $new_i = $schData['colspan'];
                        array_push($schArray,  $schData);
                        array_push($scheduleData,  $schData);
                        array_push($scheduleStripeData,  $schData['stripe']);
                    } else {
                        if($new_i > 1){
                            $new_i--;
                            continue;
                        }
                        $schData = ['null'];
                        array_push($schArray,  $schData);
                        array_push($scheduleData, $schData);
                    }
                }
                //Set graph data
                $data[$key]['schedule'][$valkey]['resultData'] = $scheduleData;
                $data[$key]['schedule'][$valkey]['stripe_data'] = $scheduleStripeData;
            }
            $pSchArray = [];
            $pumpScheduleData = [];
            $pumpScheduleStripeData = [];
            //Pump Schedule
            foreach ($value['pump_schedule'] as $pumpKey => $pumpSch) {
                $pumpScheduleData = [];
                $pumpScheduleStripeData = [];
                $pump_i = 1;
                $pumpDTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($pumpSch['planned_travel_start']));
                $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($pumpSch['planned_return_end']));
                //Check if schedule is within time slot
                foreach ($slots as $timeKey => $timeValue) {
                    if ($pumpDTFSsch == $timeValue['end_time_date']) {
                        $pSchData = self::pumpScheduleData($pumpSch);
                        $pump_i = $pSchData['colspan'];
                        array_push($pSchArray,  $pSchData);
                        array_push($pumpScheduleData,  $pSchData);
                        array_push($pumpScheduleStripeData,  $pSchData['stripe']);
                    } else {
                        if($pump_i > 1){
                            $pump_i--;
                            continue;
                        }
                        $pSchData = ['null'];
                        array_push($pSchArray,  $pSchData);
                        array_push($pumpScheduleData, $pSchData);
                    }
                }
                //Set graph data
                $data[$key]['pump_schedule'][$pumpKey]['resultData'] = $pumpScheduleData;
                $data[$key]['pump_schedule'][$pumpKey]['stripe_data'] = $pumpScheduleStripeData;
            }
            //Order Main Graph
            $i=1;
            foreach ($slots as $timeKey => $timeValue) {
                if(!empty($value['planned_start_time']) && !empty($value['planned_end_time'])) {
                    if ($dTFS == $timeValue['end_time_date']) {
                        $rData = self::selectedOrdersData($value);
                        $i = $rData['colspan'];
                        array_push($resArr,  $rData);
                        array_push($totalRdata,  $rData);
                        array_push($stripeIdData,  $rData['stripe']);
                    } else {
                        if($i > 1){
                            $i--;
                            continue;
                        }
                        $rData = ['null'];
                        array_push($resArr, $rData);
                        array_push($totalRdata, $rData);
                    }
                }else{
                        $rData = ['null'];
                        array_push($resArr, $rData);
                        array_push($totalRdata, $rData);
                }
            }
            $data[$key]['resultData'] = $totalRdata;
            $data[$key]['stripe_data'] = $stripeIdData;
        }
        // $tnos = 0;
        // foreach ($bpDetails as &$bpDetail) {
        //         $bpDetail['tnos'] = ($totalBPTime * $bpDetail['actual_capacity']) / $bpDetail['avg_mixer_capacity'];
        //         $tnos += $bpDetail['tnos'];
        // }
        return [
            'heading' => $slots,
            'resData' => $data,
        ];
    }

    public static function selectedOrdersData(array $value): array
    {   
        //Planned Start Time
        $startDatePlanned = strtotime($value['planned_start_time']);
        $endDatePlanned = strtotime($value['planned_end_time']);
        $dTFSPlanned = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $startDatePlanned);
        $dTFEPlanned = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $endDatePlanned);

        $startDateTimePlanned = Carbon::parse($dTFSPlanned);
        $endDateTimePlanned = Carbon::parse($dTFEPlanned);

        $colspanTimePlanned = $endDateTimePlanned->diffInMinutes($startDateTimePlanned);

        $totalPixelsPlanned = $colspanTimePlanned;

        $totalMinutesPlanned = $colspanTimePlanned;

        $colArr = []; // Common

        $startMinutesPlanned = date('i', $startDatePlanned);
        $endMinutesPlanned = date('i',$endDatePlanned);

        $totalPixlesPlanned = 1.5 * ((int)$totalPixelsPlanned);

        $hoursDifferencePlanned = ($colspanTimePlanned) / 60;
        $colspanPlanned = ceil($hoursDifferencePlanned) == 0 ? 1 : ceil($hoursDifferencePlanned);

        if((int)$endMinutesPlanned > 0){
            $colspanPlanned += 1; 
        }

        for ($i = 1; $i <= $colspanPlanned * 6; $i++) {
            array_push($colArr, $i);
        }
        
        $rData = [
            'id' => $value['id'],
            'order_no' => $value['order_no'],
            'project' => $value['project'],
            'location' => $value['location'],
            'site' => $value['site'],
            'interval' => $value['interval'],

            'planned_start_time' => $dTFSPlanned,
            'planned_end_time' => $dTFEPlanned,
            'planned_total_minutes' => $totalMinutesPlanned,
            'planned_start_date_time' => $value['planned_start_time'],
            'planned_end_date_time' => $value['planned_end_time'],
            'colspan' => $colspanPlanned,
            'planned_colspan' => $colspanPlanned,
            'planned_margin' => $startDateTimePlanned,
            'stripe' => $colArr,
            'end_minutes' => $endMinutesPlanned,
            'total_pixels' => $totalPixlesPlanned,
            'start_minutes' => (int) $startMinutesPlanned,
            // 'early_deviation' => strval(abs($earlyDeviation)),
            // 'late_deviation' => strval(abs($lateDeviation)),
            'early_deviation' => $value['actual_deviation'] && $value['actual_deviation'] < 0 ? strval(abs($value['actual_deviation'])) : 0,
            'late_deviation' => $value['actual_deviation'] && $value['actual_deviation'] > 0 ? strval(abs($value['actual_deviation'])) : 0,
            'late_deviation_pixel' => strval(abs(0)),
            'early_deviation_pixel' => strval(abs(0))
        ];
        return $rData;
    }

    public static function scheduleData(array $value) : array
    {
        $newStartDate = strtotime($value['planned_return_end']) < strtotime($value['planned_loading_start']) ? strtotime($value['planned_return_end']) : strtotime($value['planned_loading_start']);
        $newEndDate = strtotime($value['planned_return_end']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['planned_loading_start']);
        $endDate = strtotime($value['planned_return_end']);

        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $loadingDelayPixels = 0;
        $loadingEarylPixels = 0;

        $newLoadingStartTime = $value['actual_loading_start'] ?: $value['expected_loading_start'];
// dd($newLoadingStartTime);
        if (isset($value['actual_loading_start']) || $newLoadingStartTime) {
            $loadingDeviation = Carbon::parse($value['planned_loading_start']) -> diffInMinutes($newLoadingStartTime,false);
            if ($loadingDeviation > 0) {
                $loadingDelayPixels = 1.5 * $loadingDeviation;
            }
            if ($loadingDeviation < 0) {
                $loadingEarylPixels = 1.5 * $loadingDeviation;
            }
        }

        $qcDelayPixels = 0;
        $qcEarlyPixels = 0;

        $newQcStartTime = $value['actual_qc_start'] ?: $value['expected_qc_start'];

        if (isset($value['actual_qc_start']) || $newQcStartTime) {
            $qcDeviation = Carbon::parse($value['planned_qc_start']) -> diffInMinutes($newQcStartTime, false);
            if ($qcDeviation > 0) {
                $qcDelayPixels = 1.5 * $qcDeviation;
            }
            if ($qcDeviation < 0) {
                $qcEarlyPixels = 1.5 * $qcDeviation;
            }
        }

        $travelDelayPixels = 0;
        $travelEarlyPixels = 0;

        $newTravelStartTime = $value['actual_travel_start'] ?: $value['expected_travel_start'];

        if (isset($value['actual_travel_start']) || $newTravelStartTime) {
            $travelDeviation = Carbon::parse($value['planned_travel_start']) -> diffInMinutes($newTravelStartTime, false);
            if ($travelDeviation > 0) {
                $travelDelayPixels = 1.5 * $travelDeviation;
            }
            if ($travelDeviation < 0) {
                $travelEarlyPixels = 1.5 * $travelDeviation;
            }
        }

        $inspDelayPixels = 0;
        $inspEarlyPixels = 0;

        if (isset($value['actual_insp_start']) && isset($value['actual_insp_end'])) {
            $inspDeviation = Carbon::parse($value['planned_insp_start']) -> diffInMinutes($value['actual_insp_start'], false);
            if ($inspDeviation > 0) {
                $inspDelayPixels = 1.5 * $inspDeviation;
            }
            if ($inspDeviation < 0) {
                $inspEarlyPixels = 1.5 * $inspDeviation;
            }
        }

        $pouringDelayPixels = 0;
        $pouringEarlyPixels = 0;

        if (isset($value['actual_pouring_start']) && isset($value['actual_pouring_end'])) {
            $pouringDeviation = Carbon::parse($value['planned_pouring_start']) -> diffInMinutes($value['actual_pouring_start'], false);
            if ($pouringDeviation > 0) {
                $pouringDelayPixels = 1.5 * $pouringDeviation;
            }
            if ($pouringDeviation < 0) {
                $pouringEarlyPixels = 1.5 * $pouringDeviation;
            }
        }
        
        $cleaningDelayPixels = 0;
        $cleaningEarlyPixels = 0;

        if (isset($value['actual_cleaning_start']) && isset($value['actual_cleaning_end'])) {
            $cleaningDeviation = Carbon::parse($value['planned_cleaning_start']) -> diffInMinutes($value['actual_cleaning_start'], false);
            if ($cleaningDeviation > 0) {
                $cleaningDelayPixels = 1.5 * $cleaningDeviation;
            }
            if ($cleaningDeviation < 0) {
                $cleaningEarlyPixels = 1.5 * $cleaningDeviation;
            }
        }

        $returnDelayPixels = 0;
        $returnEarlyPixels = 0;

        if (isset($value['actual_return_start']) && isset($value['actual_return_end'])) {
            $returnDeviation = Carbon::parse($value['planned_return_start']) -> diffInMinutes($value['actual_return_start'], false);
            if ($returnDeviation > 0) {
                $returnDelayPixels = 1.5 * $returnDeviation;
            }
            if ($returnDeviation < 0) {
                $returnEarlyPixels = 1.5 * $returnDeviation;
            }
        }

        $loadingPixles = 1.5 * ((int) ($value['actual_loading_time'] ? $value['actual_loading_time'] : $value['planned_loading_time']));
        $qcPixles = 1.5 * ((int) ($value['actual_qc_time'] ? $value['actual_qc_time'] : $value['planned_qc_time']));
        $travelPixles = 1.5 * ((int) ($value['actual_travel_time'] ? $value['actual_travel_time'] : $value['planned_travel_time']));
        $inspPixles = 1.5 * ((int) ($value['actual_insp_time'] ? $value['actual_insp_time'] : $value['planned_insp_time']));
        $pourPixles = 1.5 * ((int) ($value['actual_pouring_time'] ? $value['actual_pouring_time'] : $value['planned_pouring_time']));
        $cleanPixles = 1.5 * ((int) ($value['actual_cleaning_time'] ? $value['actual_cleaning_time'] : $value['planned_cleaning_time']));
        $returnPixles = 1.5 * ((int) ($value['actual_return_time'] ? $value['actual_return_time'] : $value['planned_return_time']));

        $totalPixles = $loadingPixles + $qcPixles + $travelPixles + $inspPixles + $pourPixles + $cleanPixles + $returnPixles + ($loadingDelayPixels + $loadingEarylPixels + $qcDelayPixels + $qcEarlyPixels + $travelDelayPixels + $travelEarlyPixels + $inspDelayPixels + $inspEarlyPixels + $pouringDelayPixels + $pouringEarlyPixels + $cleaningDelayPixels + $cleaningEarlyPixels + $returnDelayPixels + $returnEarlyPixels);
        $totalMinutes = $totalPixles / 1.5;

        $hoursDifference = $totalMinutes / 60;
        $colspan = ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0){
            $colspan += 1; 
        }

        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }

        $live_marker_width = 0;
        $currentDateTime = Carbon::now() -> setDateFrom($startDateTime) -> addHours(5) -> addMinutes(30);

        if ($currentDateTime -> lte($startDateTime)) {
            $live_marker_width = 0;
        } else {
            $live_marker_width = min(($currentDateTime -> diffInMinutes($startDateTime)) * 1.5, $totalPixles);
        }

        $rData = [
            'id' => $value['id'],
            'truck_name' => $value['transit_mixer'],
            'batching_plant_name' => $value['batching_plant'],
            'total_minutes' => $totalMinutes,
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'planned_total_pixels' => $totalPixles,
            'planned_loading_pixels' => $loadingPixles,
            'loading_delay_pixels' => $loadingDelayPixels,
            'loading_early_pixels' => $loadingEarylPixels,
            'qc_delay_pixels' => $qcDelayPixels,
            'qc_early_pixels' => $qcEarlyPixels,
            'travel_delay_pixels' => $travelDelayPixels,
            'travel_early_pixels' => $travelEarlyPixels,
            'insp_delay_pixels' => $inspDelayPixels,
            'insp_early_pixels' => $inspEarlyPixels,
            'pouring_delay_pixels' => $pouringDelayPixels,
            'pouring_early_pixels' => $pouringEarlyPixels,
            'cleaning_delay_pixels' => $cleaningDelayPixels,
            'cleaning_early_pixels' => $cleaningEarlyPixels,
            'return_delay_pixels' => $returnDelayPixels,
            'return_early_pixels' => $returnDelayPixels,
            'planned_qc_pixels' => $qcPixles,
            'planned_travel_pixels' => $travelPixles,
            'planned_insp_pixels' => $inspPixles,
            'planned_pouring_pixels' => $pourPixles,
            'planned_cleaning_pixels' => $cleanPixles,
            'planned_return_pixels' => $returnPixles,
            'live_marker_width' => $live_marker_width,
            'start_minutes' => (int) $startMinutes * 1.5,
        ];
        return $rData;
    }

    public static function pumpScheduleData(array $value) : array
    {
        $newStartDate = strtotime($value['planned_cleaning_end']) < strtotime($value['planned_qc_start']) ? strtotime($value['planned_cleaning_end']) : strtotime($value['planned_qc_start']);
        $newEndDate = strtotime($value['planned_cleaning_end']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['planned_qc_start']);
        $endDate = strtotime($value['planned_cleaning_end']);

        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $qcDelayPixels = 0;
        $qcEarlyPixels = 0;

        $newQcStartTime = $value['actual_qc_start'] ?: $value['expected_qc_start'];

        if (isset($value['actual_qc_start']) || $newQcStartTime) {
            $qcDeviation = Carbon::parse($value['planned_qc_start']) -> diffInMinutes($newQcStartTime, false);
            if ($qcDeviation > 0) {
                $qcDelayPixels = 1.5 * $qcDeviation;
            }
            if ($qcDeviation < 0) {
                $qcEarlyPixels = 1.5 * $qcDeviation;
            }
        }

        $qcPixles = 1.5 * ((int) $value['planned_qc_time'] );
        $travelPixles = 1.5 * ((int) $value['planned_travel_time']);
        $inspPixels = 1.5 * ((int) $value['planned_insp_time']);
        $pourPixles = 1.5 * ((int) $value['planned_pouring_time']);
        $cleanPixels = 1.5 * ((int) $value['planned_cleaning_time']);
        $returnPixles = 1.5 * ((int) $value['planned_return_time']);
        $totalPixles = 1.5 * ((int) $totalMinutes);

        $hoursDifference = $totalMinutes / 60;
        $colspan =   ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0){
            $colspan += 1; 
        }
        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }

        $live_marker_width = 0;
        $currentDateTime = Carbon::now() -> setDateFrom($startDateTime) -> addHours(5) -> addMinutes(30);

        if ($currentDateTime -> lte($startDateTime)) {
            $live_marker_width = 0;
        } else {
            $live_marker_width = min(($currentDateTime -> diffInMinutes($startDateTime)) * 1.5, $totalPixles);
        }

        $rData = [
            'id' => $value['id'],
            'pump_name' => $value['pump'],
            'total_minutes' => $totalMinutes,
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'planned_total_pixels' => $totalPixles,
            'planned_qc_pixels' => $qcPixles,
            'planned_travel_pixels' => $travelPixles,
            'planned_pouring_pixels' => $pourPixles,
            'planned_insp_pixels' => $inspPixels,
            'planned_cleaning_pixels' => $cleanPixels,
            'planned_return_pixels' => $returnPixles,
            'live_marker_width' => $live_marker_width,
            'start_minutes' => (int) $startMinutes * 1.5,
            'qc_delay_pixels' => $qcDelayPixels,
            'qc_early_pixels' => $qcEarlyPixels,
        ];
        return $rData;
    }

    //Batching Plants
    public static function batchingPlantSchedule(array $schedules,string $startTime, string $endTime, string $deliveryDate) : array 
    {
        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);
        $schArray = [];
        $scheduleData = [];
        $scheduleStripeData = [];

        $new_schedules = [];

        foreach ($schedules as $schValue) {
            $key = array_search($schValue['batching_plant'], array_column($new_schedules, 'batching_plant'));
            if ($key !== false) {
                $new_schedules[$key]['loading_end'] = $schValue['planned_loading_end'];
                $new_schedules[$key]['loading_time'] = $new_schedules[$key]['loading_time'] + $schValue['planned_loading_time'];
                $new_schedules[$key]['total_batching_qty'] += (int)$schValue['batching_qty'];
                $new_schedules[$key]['total_batching_time'] += $schValue['planned_loading_time'];
                $new_schedules[$key]['total_time'] = Carbon::parse($schValue['planned_loading_end']) -> diffInMinutes(Carbon::parse($new_schedules[$key]['planned_loading_start']));

                array_push($new_schedules[$key]['utilization'],[
                    'loading_start' => $schValue['planned_loading_start'],
                    'loading_time' => $schValue['planned_loading_time'],
                    'loading_end' => $schValue['planned_loading_end'],
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
                    'loading_start' => $schValue['planned_loading_start'],
                    'loading_end' => $schValue['planned_loading_end'],
                    'loading_time' => $schValue['planned_loading_time'],
                    'total_batching_qty' => (int)($schValue['batching_qty']),
                    'total_batching_time' => (int)($schValue['planned_loading_time']),
                    'total_time' => (int)($schValue['planned_loading_time']),
                    'utilization' => [
                        array(
                        'loading_start' => $schValue['planned_loading_start'],
                        'loading_end' => $schValue['planned_loading_end'],
                        'loading_time' => $schValue['planned_loading_time'],
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
                    $schData = self::scheduleDataBatchingPlants($scheduleVal);
                    $new_i = $schData['colspan'];
                    array_push($schArray,  $schData);
                    array_push($scheduleData,  $schData);
                    array_push($scheduleStripeData,  $schData['stripe']);
                } else {
                    if($new_i > 1){
                        $new_i--;
                        continue;
                    }
                    $schData = ['null'];
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

    public static function scheduleDataBatchingPlants(array $value) : array
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
                $isGap = false;
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
}
