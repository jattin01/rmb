<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\OrderPump;
use Carbon\Carbon;

class PumpHelper
{


    public function getPumpsAvailability(int $company_id, string $schedule_date, array $pump_ids): array
    {
        $pumps_availabilty = [];

        $ps = Pump::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "pumps.group_company_id");
        })->select("pump_name", "pump_capacity", "type", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company_id)
            ->where("pumps.status", ConstantHelper::ACTIVE)
            ->whereIn("pumps.id", $pump_ids)
            ->get();

        foreach ($ps as $p) {
            $pumps_availabilty[] = array(
                'pump_name' => $p->pump_name,
                'pump_type' => $p->type,
                'pump_capacity' => $p->pump_capacity,
                'free_from' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->subDays(1)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
                'order_id' => null,
                'order_id_wo_trip' => null
            );
        }

        // dd($pumps_availabilty);
        return $pumps_availabilty;
    }


    public static function getAvailablePumps($pumps, $order_id, $company, $pump_start_time, $pump_end_time, $pump_cap, $trip, $selected_order_pump_schedules, $location_end_time, $pump_qty, $location = null, $assinedPump = null, $assinedPumps = array())
    {

        try {

            $data = null;
            $index = null;


            $order = SelectedOrder::find($order_id);

            $capacities = OrderPump::where('order_id', $order->og_order_id)->pluck('quantity', 'capacity')->toArray();

            $capacityKeys = array_keys($capacities);

            // dd(collect($assinedPump)->flatten()->toArray());
            $totalAssignedPumps = $assinedPump ? collect($assinedPump)->flatten()->toArray() : [];
            // if($order->order_no == 114512 && $trip > 2){
            //     // dd($location,$pumps, $assinedPumps,$assinedPump);
            //         dd($totalAssignedPumps);
            // }
            // dd($capacityKeys);

            foreach ($pumps as $pumpKey => $pump) {
                $installMinutes = $pump['installation_time'] ?? 10;
                $travelMinutes = $order->travel_to_site ?? 0;



                $pumpCount = is_array($pumps) ? count($pumps) : $pumps->count();

                if ($pumpCount == 1)
                    $subMinutes = ($installMinutes + $travelMinutes) * 2;
                else
                    $subMinutes = $installMinutes + $travelMinutes;


                $pump_start_time = Carbon::parse($pump_start_time)
                    ->subMinutes($subMinutes)->format('Y-m-d H:i:s');



                if (!in_array($pump['pump_capacity'], $capacityKeys)) {

                    continue;
                }
                if (count($totalAssignedPumps) && !in_array($pump['pump_name'], $totalAssignedPumps)) {

                    if (isset($assinedPump[$pump['pump_capacity']]) && count($assinedPump[$pump['pump_capacity']]) >= $capacities[$pump['pump_capacity']]) {
                        continue;
                    }
                }

                if (count($assinedPumps) && !in_array($pump['pump_name'], $assinedPumps)) {
                    continue;
                }

                if ($pump['location'] && $location && $pump['location'] != $location) {
                    continue;
                }
                if (Carbon::parse($pump['free_from'])->gt(Carbon::parse($pump_start_time)))
                    continue;

                if (Carbon::parse($pump['free_from'])->gt($pump_end_time))
                    continue;

                if (Carbon::parse($pump['free_upto'])->lt(Carbon::parse($pump_start_time)))
                    continue;

                if (Carbon::parse($pump['free_upto'])->lt($pump_end_time))
                    continue;

                $data = $pump;
                $index = $pumpKey;
                break;
            }

            // dd($order_id, $data, $pump_start_time, $pump_end_time, $trip);

            // if( $trip == 115)
            //     dd($pumps, $pump_start_time, $pump_end_time, $data, $index, $assinedPumps);

            if ($data) {
                return ['pump' => $data, 'index' => $index];
            }

            // if( $pump_start_time->gt(Carbon::parse("2025-03-01 22:00:00"))) {
            //     dd($order_id, $data, $pump_start_time, $pump_end_time, $trip);
            // }

            // if pump limit already reached 


            //  If no assigned pump is available, choose another pump
            foreach ($pumps as $pumpKey => $pump) {

                if (!in_array($pump['pump_capacity'], $capacityKeys)) {

                    continue;
                }
                if (count($totalAssignedPumps) && !in_array($pump['pump_name'], $totalAssignedPumps)) {
                    if (isset($assinedPump[$pump['pump_capacity']]) && count($assinedPump[$pump['pump_capacity']]) >= $capacities[$pump['pump_capacity']]) {
                        continue;
                    }
                }

                if ($pump['location'] && $location && $pump['location'] != $location) {
                    continue;
                }

                if (Carbon::parse($pump['free_from'])->gt(Carbon::parse($pump_start_time)))
                    continue;

                if (Carbon::parse($pump['free_from'])->gt($pump_end_time))
                    continue;

                if (Carbon::parse($pump['free_upto'])->lt(Carbon::parse($pump_start_time)))
                    continue;

                if (Carbon::parse($pump['free_upto'])->lt($pump_end_time))
                    continue;


                $data = $pump;
                $index = $pumpKey;
                // echo "[BREAK]";
                break;
            }


            if (isset($data) && $data) {

                return ['pump' => $data, 'index' => $index];
            } else {
                return null;
            }

        } catch (\Exception $e) {

            // dd($pumps, $e);
        }

    }


    public static function searchAndUpdateArray($arrayOfArrays, $searchCriteria, $updateValues)
    {
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
                    $innerArray[$key] = is_array($value) ? $innerArray[$key] + Carbon::parse($innerArray['pouring_end'])->diffInMinutes(Carbon::parse($value['value'])) : $value;
                }
                break;
            }
        }
        return ['data' => $arrayOfArrays, 'match' => $match];
    }

}