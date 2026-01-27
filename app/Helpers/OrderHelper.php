<?php

namespace App\Helpers;

use App\Models\ApprovalSetup;
use App\Models\LiveOrder;
use App\Models\Order;
use App\Models\OrderApproval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as DatabaseRowCollection;
use Illuminate\Http\Request;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class OrderHelper
{
    public static function orderGraphData(array $data, string $startTime, string $endTime, string $deliveryDate, int $totalTrips, array $fullSchedule): array
    {

        $resArr = [];
        $totalRdata = [];
        $stripeIdData = [];
        //Make time slots
        $scheduleStartTime = $fullSchedule[0]['loading_start'];
        $scheduleEndTime = $fullSchedule[$totalTrips - 1]['loading_end'];

        $totalBPTime = Carbon::parse($scheduleEndTime) -> diffInHours(Carbon::parse($scheduleStartTime)) > 0 ? Carbon::parse($scheduleEndTime) -> diffInHours(Carbon::parse($scheduleStartTime)) : 1 ;

        $actualQty = 0;
        $plannedQty = 0;

        $bpDetails = [];

        $slots = CommonHelper::divideTimeEqually($startTime, $endTime, $deliveryDate);

        foreach ($data as $key => &$value) {
            //Actual Qty
            $actualQty += $value['quantity'];

            $value['performance'] = self::getOrderPerformance(isset($value['deviation']) ? $value['deviation'] : ConstantHelper::MAX_DELAY_IN_MINS);
            $value['cs_score'] = $value['deviation'] !== null ? self::getOrderCustomerSatisfactionScore($value['deviation']) : 0;
            $value['cs_weighted_score'] = self::getOrderWightedCustomerSatisfactionScore($value['cs_score'], count($value['schedule']), $totalTrips);
            $value['customer_satisfaction'] = self::getCustomerSatisfaction($value['schedule']);
            $totalRdata = [];
            $stripeIdData = [];
            //Set start and end time
            $newStartDate = strtotime($value['delivery_date']) < strtotime($value['start_time']) ? strtotime($value['delivery_date']) : strtotime($value['start_time']);
            $newEndDate = strtotime($value['delivery_date']) > strtotime($value['end_time']) ? strtotime($value['delivery_date']) : strtotime($value['end_time']);
            $dTFS = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, $newStartDate);
            $dTFE = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, $newEndDate);

            $schArray = [];
            $scheduleData = [];
            $scheduleStripeData = [];
            //Order Trip Schedule

            foreach ($value['schedule'] as $valkey => $scheduleVal) {
                //BP Details 
                if (!array_key_exists($scheduleVal['batching_plant'], $bpDetails)) {
                    $bpDetails[$scheduleVal['batching_plant']] = array(
                        'actual_capacity' => $scheduleVal['capacity'],
                        'avg_mixer_capacity' => $scheduleVal['avg_mixer_capacity']
                    );
                }
                //Planned Qty
                $plannedQty += $scheduleVal['batching_qty'];
                $scheduleData = [];
                $scheduleStripeData = [];
                $new_i = 1;
                $dTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['loading_start']));
                $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($scheduleVal['return_end']));
                //Check if schedule is within time slot
               
                foreach ($slots as $timeKey => $timeValue) {
                    // dd($slots);
                    if ($dTFSsch == $timeValue['end_time_date']) {
                        $schData = OrderHelper::scheduleData($scheduleVal);
                    $schData['slot'] = $timeValue;
                    

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
                        $schData['slot'] = $timeValue;
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
                $pumpDTFSsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($pumpSch['travel_start']));
                $dTFEsch = date(ConstantHelper::DATE_HOUR_ONLY_FORMAT, strtotime($pumpSch['return_end']));
                //Check if schedule is within time slot
                foreach ($slots as $timeKey => $timeValue) {
                    if ($pumpDTFSsch == $timeValue['end_time_date']) {
                        $pSchData = OrderHelper::pumpScheduleData($pumpSch);
                        $pSchData['slot'] = $timeValue;
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
                        $pSchData['slot'] = $timeValue;
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
                if(!empty($value['start_time']) && !empty($value['end_time'])) {
                    if ($dTFS == $timeValue['end_time_date']) {
                        $rData = OrderHelper::selectedOrdersData($value);
                        // dd($rData);
                        $rData['slot'] = $timeValue;
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
                        $rData['slot'] = $timeValue;
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
        $tnos = 0;
        foreach ($bpDetails as &$bpDetail) {
                $bpDetail['tnos'] = ($totalBPTime * $bpDetail['actual_capacity']) / $bpDetail['avg_mixer_capacity'];
                $tnos += $bpDetail['tnos'];
        }

        return [
            'heading' => $slots,
            'resData' => $data,
            'productivity' => self::getScheduleProductivity($totalTrips, $plannedQty, $actualQty, $bpDetails)
        ];
    }

    public static function getScheduleProductivity(int $totalTrips, float $plannedQty, float $totalQty, array $bpDetails)
    {
        $theoryShipments = 0;

        foreach ($bpDetails as $bpDetail) {
            $theoryShipments += $bpDetail['tnos'];
	    }
	    $theoryShipments = ($theoryShipments == 0) ? 1 : $theoryShipments;
        $shipmentScore = (($totalTrips / $theoryShipments) * 10);
        $qtyScore = (($plannedQty / $totalQty) * 10);
        $prdScore = round(($shipmentScore + $qtyScore)/ 2, 0);
        return $prdScore;
    }

    public static function selectedOrdersData(array $value): array
    {
       
        $newStartDate = strtotime($value['delivery_date']) < strtotime($value['start_time']) ? strtotime($value['delivery_date']) : strtotime($value['start_time']);
        $newEndDate = strtotime($value['delivery_date']) > strtotime($value['end_time']) ? strtotime($value['delivery_date']) : strtotime($value['end_time']);
        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $colspanTime = $endDateTime->diffInMinutes($startDateTime);

        $pStartETime = $endDateTime->diffInMinutes($startDateTime);

        $deviationTime = Carbon::parse(strtotime($value['delivery_date']))->diffInMinutes(Carbon::parse(strtotime($value['start_time'])));
        $devSign = strtotime($value['delivery_date']) > strtotime($value['start_time']) ? -1 : 1;
        $deviationTime = $deviationTime * $devSign;
        $totalPixels = $colspanTime - abs($deviationTime);
        $totalMinutes = $pStartETime;


        $earlyDeviation = 0;
        $lateDeviation = 0;

        if($deviationTime < 0){
            $earlyDeviation =  abs($deviationTime);
        }else{
            $lateDeviation = abs($deviationTime);
        }

        $colArr = [];

        $startMinutes = date('i', $newStartDate);
        $endMinutes = date('i',$newEndDate);

// echo $value['order_no'];
        $lateDeviationPixels = 0;
        $earlyDeviationPixels = 0;
        $totalPixles = 1.5 * ((int)$totalPixels);

        $lateDeviationPixels = 1.5 * ((int) $lateDeviation);
        $earlyDeviationPixels = 1.5 * ((int) $earlyDeviation);
        $hoursDifference = ($colspanTime) / 60;
        $colspan = ceil($hoursDifference) == 0 ? 1 : ceil($hoursDifference);

        if((int)$endMinutes > 0){
            $colspan += 1; 
        }

        for ($i = 1; $i <= $colspan * 6; $i++) {
            array_push($colArr, $i);
        }
        
        $rData = [
            'id' => $value['id'],
            'order_no' => $value['order_no'],
            'project' => $value['project'],
            'location' => $value['location'],
            'site' => $value['site'],
            'interval' => $value['interval'],
            'start_time' => $dTFS,
            'end_time' => $dTFE,
            'total_minutes' => $totalMinutes,
            'start_date_time' => $value['delivery_date'],
            'end_date_time' => $value['end_time'],
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'total_pixels' => $totalPixles,
            'start_minutes' => (int) $startMinutes,
            'deviation' => $value['deviation'],
            'early_deviation' => strval(abs($earlyDeviation)),
            'late_deviation' => strval(abs($lateDeviation)),
            'late_deviation_pixel' => strval(abs($lateDeviationPixels)),
            'early_deviation_pixel' => strval(abs($earlyDeviationPixels))
        ];
        return $rData;
    }

    public static function scheduleData(array $value) : array
    {
        $newStartDate = strtotime($value['return_end']) < strtotime($value['loading_start']) ? strtotime($value['return_end']) : strtotime($value['loading_start']);
        $newEndDate = strtotime($value['return_end']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['loading_start']);
        $endDate = strtotime($value['return_end']);

        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $loadingPixles = 1.5 * ((int) $value['loading_time'] );
        $qcPixles = 1.5 * ((int) $value['qc_time'] );
        $travelPixles = 1.5 * ((int) $value['travel_time']);
        $inspPixles = 1.5 * ((int) $value['insp_time']);
        $pourPixles = 1.5 * ((int) $value['pouring_time']);
        $cleanPixles = 1.5 * ((int) $value['cleaning_time']);
        $returnPixles = 1.5 * ((int) $value['return_time']);
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
            'truck_name' => $value['transit_mixer'],
            'batching_plant_name' => $value['batching_plant'],
            'total_minutes' => $totalMinutes,
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'total_pixels' => $totalPixles,
            'loading_pixels' => $loadingPixles,
            'qc_pixels' => $qcPixles,
            'travel_pixels' => $travelPixles,
            'insp_pixels' => $inspPixles,
            'pouring_pixels' => $pourPixles,
            'cleaning_pixels' => $cleanPixles,
            'return_pixels' => $returnPixles,
            'start_minutes' => (int) $startMinutes * 1.5,
        ];
        return $rData;
    }

    public static function pumpScheduleData(array $value) : array
    {
        $newStartDate = strtotime($value['cleaning_end']) < strtotime($value['qc_start']) ? strtotime($value['cleaning_end']) : strtotime($value['qc_start']);
        $newEndDate = strtotime($value['cleaning_end']);

        $dTFS = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newStartDate);
        $dTFE = date(ConstantHelper::COMPLETE_DATE_TIME_FORMAT, $newEndDate);

        $startDateTime = Carbon::parse($dTFS);
        $endDateTime = Carbon::parse($dTFE);

        $totalMinutes = $endDateTime->diffInMinutes($startDateTime);

        $colArr = [];

        $startDate = strtotime($value['qc_start']);
        $endDate = strtotime($value['cleaning_end']);
        $startMinutes = date('i',$startDate);
        $endMinutes = date('i',$endDate);

        $qcPixles = 1.5 * ((int) $value['qc_time'] );
        $travelPixles = 1.5 * ((int) $value['travel_time']);
        $inspPixels = 1.5 * ((int) $value['insp_time']);
        $pourPixles = 1.5 * ((int) $value['pouring_time']);
        $cleanPixels = 1.5 * ((int) $value['cleaning_time']);
        $returnPixles = 1.5 * ((int) $value['return_time']);
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
            'pump_name' => $value['pump'],
            'install_time'=>$value['installation_time'] ?? 10,
            'total_minutes' => $totalMinutes,
            'colspan' => $colspan,
            'margin' => $startDateTime,
            'stripe' => $colArr,
            'end_minutes' => $endMinutes,
            'total_pixels' => $totalPixles,
            'qc_pixels' => $qcPixles,
            'travel_pixels' => $travelPixles,
            'pouring_pixels' => $pourPixles,
            'insp_pixels' => $inspPixels,
            'cleaning_pixels' => $cleanPixels,
            'return_pixels' => $returnPixles,
            'start_minutes' => (int) $startMinutes * 1.5,
        ];
        return $rData;
    }

    public static function getOrderPerformance(int $deviation)
    {
        //Old Calculations
        $performancePercentage = ( ( 1 - (abs($deviation) / ConstantHelper::MAX_DELAY_IN_MINS) ) * 100);
        return round($performancePercentage, 0);
        //New calculations
    }

    public static function getOrderCustomerSatisfactionScore(int $deviation) : int
    {
        $absoluteDeviation = abs($deviation);

        if ($absoluteDeviation >= ConstantHelper::FOUR_HOUR_MINS) {
            return 0;
        } else if ($absoluteDeviation >= ConstantHelper::THREE_HOUR_MINS) {
            return 5;
        } else if ($absoluteDeviation >= ConstantHelper::ONE_HOUR_MINS) {
            return 7;
        } else {
            return 10;
        }
    }
    public static function getOrderWightedCustomerSatisfactionScore(int $csScore, int $trips, int $totalTrips) : float
    {
        $weightage = $trips / $totalTrips;
        // $weightedScore = round($csScore * $weightage, 2);
        $weightedScore = $csScore * $weightage;
        return $weightedScore;
    }
    public static function getCustomerSatisfaction(array $trips)
    {
        if (count($trips) == 0) {
            return 0;
        } else if (count($trips) == 1) {
            return 100;
        }
        array_splice($trips, 0,1);
        $avgCs = 0;
        foreach ($trips as $trip) {
            $avgCs += $trip['deviation'];
        }
        $avgCs = round($avgCs/count($trips),0);
        return $avgCs;

    }

    public static function getLiveOrderCurrentActivity() {
        return "Batching";
    }

    public static function getSortedCustomerUpcomingOrders(int $customerId, int $isUserAdmin, array $projectIds, string $search = "") : DatabaseRowCollection
    {
        $upcomingOrders = Order::with('mobile_user_access_right') -> where([
            ['customer_id', $customerId],
            ['status', ConstantHelper::ACTIVE],
            ['in_cart', 0],
        ])-> when(!$isUserAdmin, function ($query) use($projectIds) {
            $query -> whereIn('project_id', $projectIds);
        })->select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "deviation", "start_time", "end_time", "has_customer_confirmed", "remarks", "structural_reference_id", "site_id", "published_by", "project_id", "group_company_id")
            ->with('schedule')-> whereDate('delivery_date', '>=', Carbon::now())
                ->with('customer_product', function ($query) {
                $query->select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id')->with('customer_project', function ($subQuery) {
                    $subQuery->select('id', 'name')->with('address');
                });
            })->orderBy('delivery_date')->get();

            $upcomingOrderNos = $upcomingOrders -> pluck('order_no') -> toArray();

        $liveUpcomingOrders = LiveOrder::with('mobile_user_access_right') -> where([
            ['customer_id', $customerId],
            ['status', ConstantHelper::ACTIVE]
        ]) -> when(!$isUserAdmin, function ($query) use($projectIds) {
            $query -> whereIn('project_id', $projectIds);
        }) -> whereDate('delivery_date', Carbon::now()) -> select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "planned_deviation AS deviation", "planned_start_time AS start_time", "planned_end_time AS end_time", "structural_reference_id", "site_id", "project_id", "group_company_id") -> with('schedule') -> with('customer_product', function ($query) {
            $query -> select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id') -> with('customer_project', function ($subQuery) {
                $subQuery -> select('id' , 'name') ->  with('address');
            });
        }) -> whereDoesntHave('schedule', function ($query) {
            $query -> whereNotNull('actual_loading_start');
        })-> whereNotIn('order_no', $upcomingOrderNos) -> get();

        $liveUpcomingOrderNos = $liveUpcomingOrders -> pluck('order_no') -> toArray();

        $upcomingOrders = Order::with('mobile_user_access_right') -> where([
            ['customer_id', $customerId],
            ['status', ConstantHelper::ACTIVE],
            ['in_cart', 0],
        ])-> when(!$isUserAdmin, function ($query) use($projectIds) {
            $query -> whereIn('project_id', $projectIds);
        })->select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "deviation", "start_time", "end_time", "has_customer_confirmed", "remarks", "structural_reference_id", "site_id", "published_by", "project_id", "group_company_id")
            ->with('schedule')-> whereDate('delivery_date', '>=', Carbon::now())
                ->with('customer_product', function ($query) {
                $query->select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id')->with('customer_project', function ($subQuery) {
                    $subQuery->select('id', 'name')->with('address');
                });
            })->whereNotIn('order_no', $liveUpcomingOrderNos) ->orderBy('delivery_date')->get();

        $allUpcomingOrders = $upcomingOrders -> merge($liveUpcomingOrders);
        // $allUpcomingOrders = $upcomingOrders->merge($liveUpcomingOrders)
        //                             ->sortBy(function($order) {
        //                                 return strtotime($order->delivery_date);
        //                             });
        return $allUpcomingOrders;
    }  
    
    public static function getCustomerPastOrders(int $customerId, int $isUserAdmin, array $projectIds, string $search = "") : DatabaseRowCollection
    {
        $pastOrders = Order::with('mobile_user_access_right') -> where([
            ['customer_id', $customerId],
            ['status', ConstantHelper::ACTIVE],
            ['in_cart', 0],
        ])-> when(!$isUserAdmin, function ($query) use($projectIds) {
            $query -> whereIn('project_id', $projectIds);
        })->select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "deviation", "start_time", "end_time", "has_customer_confirmed", "remarks", "structural_reference_id", "site_id", "published_by", "project_id", "group_company_id")
        -> whereNotNull('published_by') -> whereHas('schedule') -> with('schedule') -> whereDate('delivery_date', '<', Carbon::now())->with('customer_product', function ($query) {
                $query->select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id')->with('customer_project', function ($subQuery) {
                    $subQuery->select('id', 'name')->with('address');
                });
            })->orderByDesc('delivery_date')->get();
        return $pastOrders;
    }

    public static function getCustomerOngoingOrders(int $customerId, int $isUserAdmin, array $projectIds, string $search = "") : DatabaseRowCollection
    {
        $ongoingOrders = LiveOrder::with('mobile_user_access_right') -> where([
            ['customer_id', $customerId],
            ['status', ConstantHelper::ACTIVE]
        ]) -> when(!$isUserAdmin, function ($query) use($projectIds) {
            $query -> whereIn('project_id', $projectIds);
        }) -> whereDate('delivery_date', Carbon::now()) -> select("id", "order_no", "delivery_date", "interval", "quantity", "cust_product_id", "planned_deviation AS deviation", "planned_start_time AS start_time", "planned_end_time AS end_time", "structural_reference_id", "site_id", "project_id","group_company_id") -> with('schedule') -> with('customer_product', function ($query) {
            $query -> select('id', 'total_quantity', 'ordered_quantity', 'project_id', 'product_id') -> with('customer_project', function ($subQuery) {
                    $subQuery -> select('id' , 'name') ->  with('address');
            });
        }) -> whereHas('schedule', function ($query) {
            $query -> whereNotNull('actual_loading_start');
        })-> get();
        return $ongoingOrders;
    }

    public static function appendKeysToOrderForMobileUi(string $filterType, Order|LiveOrder &$order) : Order|LiveOrder
    {
            $order -> has_customer_confirmed = isset($order -> has_customer_confirmed) ? $order -> has_customer_confirmed : 1;
            $order -> deviation_type =  $order -> deviation == 0 ? "On Time" : ($order -> deviation > 0 ? "Delay" : "Early");
            $order -> site_name = $order -> customer_site ?-> name;
            $order -> site_address = $order -> customer_site ?-> address;

            $deliveredQty = 0;
            $onTheWayQty = 0;
            $batchingQty = 0;
            $remainingQty = 0;
            $trip = -1;

            if ($filterType === ConstantHelper::LIVE_ORDERS)
            {
                foreach($order -> schedule as $orderSchKey => $orderSch) {
                    $currentActivity = $orderSch -> getCurrentActivity();
                    if ($currentActivity === ConstantHelper::BATCHING || $currentActivity === ConstantHelper::INTERNAL_QC) {
                        $batchingQty += $orderSch -> batching_qty;
                        $trip = $orderSchKey;
                    } else if ($currentActivity === ConstantHelper::ON_SITE_TRAVEL || $currentActivity === ConstantHelper::ON_SITE_INSP) {
                        $onTheWayQty += $orderSch -> batching_qty;
                        $trip = $orderSchKey;
                    } else if (($currentActivity === ConstantHelper::POURING || $currentActivity === ConstantHelper::CLEAN_ON_SITE || $currentActivity === ConstantHelper::RETURN) || (isset($orderSch -> actual_return_end))) {
                        $deliveredQty += $orderSch -> batching_qty;
                        $trip = $orderSchKey;
                    } else {
                        $remainingQty += $orderSch -> batching_qty;
                        $trip = 1;
                    }
                }
            }
            $order -> live_delivered_qty = $deliveredQty;
            $order -> live_delivered_qty_percentage = round(($deliveredQty/$order -> quantity)*100,0);
            $order -> on_the_way_qty = $onTheWayQty;
            $order -> on_the_way_qty_percentage = round(($onTheWayQty/$order -> quantity)*100,0);
            $order -> batching_qty = $batchingQty;
            $order -> batching_qty_percentage = round(($batchingQty/$order -> quantity)*100,0);
            $order -> remaining_qty = $remainingQty;
            $order -> remaining_qty_percentage = round(($remainingQty/$order -> quantity)*100,0);
            $order -> progress = $order -> live_delivered_qty_percentage;
            $order -> trips = $trip + 1;
            $order -> makeHidden(['schedule']);
            $order -> current_activity = "Batching"; // Need to remove after latest App Build

        return $order;
    }

    public static function addApproval(Request $request, int $userId, ApprovalSetup $approvalSetup, DatabaseRowCollection $approvalLevels, $currentLevel)
    {
        $orderApproval = OrderApproval::create([
            'order_id' => $request -> order_id,
            'approved_by' => $userId,
            'approval_status' => $request -> approval_status,
            'reset' => 0,
            'remarks' => $request -> remarks ? $request -> remarks : null
        ]);

        if ($request -> approval_status === 'Rejected') {
            OrderApproval::where([
                ['order_id', $request -> order_id],
            ]) -> update([
                'reset' => 1
            ]);
        }

        if ($request ->approval_status === "Sent Back") {
            $previousLevelUserId = null;
            foreach ($approvalLevels as $approvalLev) {
                if ($approvalLev -> level_no == ($currentLevel - 1)) {
                    $previousLevelUserId = $approvalLev -> user_id;
                    break;
                }
            }
            $prevApproval = OrderApproval::where([
                ['order_id', $request -> order_id],
                ['approved_by', $previousLevelUserId],
            ]) -> latest() -> first();
            if (isset($prevApproval)) {
                $prevApproval -> reset = 1;
                $prevApproval -> save();
            }
        }

        if ($request->hasFile('docs')) {
            foreach ($request->file('docs') as $document) {
                $orderApproval->addMedia($document)->toMediaCollection('order_approval_docs');
            }
        }

        $approvedStatuses = OrderApproval::where([
            ['status', ConstantHelper::ACTIVE],
            ['order_id', $request -> order_id],
            ['reset', 0],
        ]) -> get();

        $approvalStatus = "Pending";
        $approvalsCount = 0;

        foreach ($approvalLevels as $approvalLevel) {
            $approvalExists = $approvedStatuses -> firstWhere('approved_by', $approvalLevel -> user_id);
            if (isset($approvalExists)) {
                if ($approvalExists -> approval_status === "Rejected") {
                    $approvalStatus = "Rejected";
                    break;
                }
                if ($approvalExists -> approval_status === "Approved") {
                    $approvalStatus = "Partially Approved";
                    $approvalsCount += 1;
                }
            }
        }
        // if ($request -> approval_status === "Approved")
        // {
        //     $approvalsCount += 1;
        // }
        if ($approvalStatus !== "Rejected") {
            $approvalStatus = $approvalsCount >= count($approvalLevels) ? "Approved" : "Partially Approved";
        }

        $order = Order::find($request -> order_id);
        if (isset($order)) {
            $order -> approval_status = $approvalStatus;
            $order -> save();
        }
    }
}
