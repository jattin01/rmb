<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\OrderPump;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PumpHelper
{


    public function getPumpsAvailability(int $company_id, string $schedule_date, array $pump_ids): array
    {
        $pumps_availabilty = [];

        $ps = Pump::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "pumps.group_company_id");
        })->select("pump_name", "pump_capacity", "type", "working_hrs_s", "working_hrs_e",'installation_time')
            ->where("group_companies.id", $company_id)
            ->where("pumps.status", ConstantHelper::ACTIVE)
            ->whereIn("pumps.id", $pump_ids)
            ->get();

        foreach ($ps as $p) {
            $pumps_availabilty[] = array(
                'pump_name' => $p->pump_name,
                'pump_type' => $p->type,
                'pump_capacity' => $p->pump_capacity,
                'installation_time' => $p->installation_time,
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



    public static function getAvailablePumps(
        $pumps,
        $order_id,
        $company,
        $pump_start_time,
        $pump_end_time,
        $pump_cap,
        $trip,
        $selected_order_pump_schedules,
        $location_end_time,
        $pump_qty,
        $location = null,
        $assinedPump = null,
        $assinedPumps = array(),
        $scheduleData
    ) {
        try {
        
            $data = null;
            $index = null;

            $order = SelectedOrder::find($order_id);

            $capacities = OrderPump::where('order_id', $order->og_order_id)
                ->pluck('quantity', 'capacity')->toArray();

            $capacityKeys = array_keys($capacities);

            $totalAssignedPumps = $assinedPump
                ? collect($assinedPump)->flatten()->toArray()
                : [];

            /* ===== helper: detect time or datetime ===== */
            $makeDateTime = function ($date, $value) {
                if (!$value)
                    return null;

                // already has date
                if (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
                    return Carbon::parse($value);
                }

                // only time -> attach date
                return Carbon::parse("$date $value");
            };

            /* ================= FIRST LOOP ================= */
            foreach ($pumps as $pumpKey => $pump) {

                // $installMinutes = $pump['installation_time'] ?? 10;
                // $travelMinutes = $scheduleData->travel_time ?? 0;
                // $qcTime = $scheduleData->qc_time ?? 0;
                // $loadingTime = $scheduleData->loading_time ?? 0;
                // $insp_time = $scheduleData->insp_time ?? 0;





                // $pumpCount = is_array($pumps) ? count($pumps) : $pumps->count();

                // $subMinutes = $installMinutes + $travelMinutes + $qcTime + $insp_time;



                // $new_pump_start = $pump_start_time;

                // $new_pump_start = Carbon::parse($pump_start_time)
                //     ->subMinutes($subMinutes)
                //     ->format('Y-m-d H:i:s');

                // Log::info("Checking Pump", [
                //     'order' => $order->order_no,
                //     'trip' => $trip,
                //     'pump' => $pump['pump_name'],
                //     'start' => $pump_start_time,
                //     'end' => $pump_end_time,
                //     'new_start' => $new_pump_start
                // ]);



                /* ---- capacity & assignment ---- */
                if (!in_array($pump['pump_capacity'], $capacityKeys)) {
                    Log::info('Skipping pump - capacity mismatch', [
                        'order_id' => $order_id,
                        'pump_name' => $pump['pump_name'],
                        'pump_capacity' => $pump['pump_capacity'],
                        'required_capacity_keys' => $capacityKeys
                    ]);
                    continue;
                }

                if (count($totalAssignedPumps) && !in_array($pump['pump_name'], $totalAssignedPumps)) {
                    if (
                        isset($assinedPump[$pump['pump_capacity']]) &&
                        count($assinedPump[$pump['pump_capacity']]) >= $capacities[$pump['pump_capacity']]
                    ) {
                        Log::info('Skipping pump - fully assigned for this capacity', [
                            'order_id' => $order_id,
                            'pump_name' => $pump['pump_name'],
                            'assigned_count' => count($assinedPump[$pump['pump_capacity']]),
                            'capacity_limit' => $capacities[$pump['pump_capacity']]
                        ]);
                        continue;
                    }
                }

                if (count($assinedPumps) && !in_array($pump['pump_name'], $assinedPumps)) {
                    Log::info('Skipping pump - not in pre-assigned pool', [
                        'order_id' => $order_id,
                        'pump_name' => $pump['pump_name'],
                        'assinedPumps' => $assinedPumps
                    ]);
                    continue;
                }

                if ($pump['location'] && $location && $pump['location'] != $location) {
                    Log::info('Skipping pump - location mismatch', [
                        'order_id' => $order_id,
                        'pump_name' => $pump['pump_name'],
                        'pump_location' => $pump['location'],
                        'required_location' => $location
                    ]);
                    continue;
                }

                /* ---- SAFE TIME CHECK ---- */
                $orderDate = Carbon::parse($pump_start_time)->toDateString();

                $freeFrom = $makeDateTime($orderDate, $pump['free_from']);
                $freeUpto = $makeDateTime($orderDate, $pump['free_upto']);
                $start = Carbon::parse($pump_start_time)->format('Y-m-d H:i:s');
                $end = Carbon::parse($pump_end_time)->format('Y-m-d H:i:s');

                if (
                    ($freeFrom && $freeFrom->gt($start)) ||   // free baad mai start ho rahi
                    ($freeUpto && $freeUpto->lt($end))        // free pehle khatam ho rahi
                ) {
                    Log::info('Skipping pump - not free for full time window', [
                        'order_id' => $order_id,
                        'pump_name' => $pump['pump_name'],
                        'free_from' => $freeFrom,
                        'free_upto' => $freeUpto,
                        'start' => $start,
                        'end' => $end
                    ]);
                    continue;
                }

                /* ---- AVAILABLE ---- */
                $data = $pump;
                $index = $pumpKey;
                $pump_start_time = $pump_start_time;
                break;
            }

            if ($data) {
                return [
                    'pump' => $data,
                    'index' => $index,
                    'pump_start_time' => $pump_start_time
                ];
            }

            /* ================= SECOND LOOP ================= */
            foreach ($pumps as $pumpKey => $pump) {

                if (!in_array($pump['pump_capacity'], $capacityKeys))
                    continue;

                if (count($totalAssignedPumps) && !in_array($pump['pump_name'], $totalAssignedPumps)) {
                    if (
                        isset($assinedPump[$pump['pump_capacity']]) &&
                        count($assinedPump[$pump['pump_capacity']]) >= $capacities[$pump['pump_capacity']]
                    ) {
                        continue;
                    }
                }

                if ($pump['location'] && $location && $pump['location'] != $location)
                    continue;

                $orderDate = Carbon::parse($pump_start_time)->toDateString();

                $freeFrom = $makeDateTime($orderDate, $pump['free_from']);
                $freeUpto = $makeDateTime($orderDate, $pump['free_upto']);
               
                $start = Carbon::parse($pump_start_time)->format('Y-m-d H:i:s');
                $end = Carbon::parse($pump_end_time)->format('Y-m-d H:i:s');

                if (
                    ($freeFrom && $freeFrom->gt($start)) ||   
                    ($freeUpto && $freeUpto->lt($end))        
                ) {

                    continue;
                }

                $data = $pump;
                $index = $pumpKey;
                break;
            }

            return $data ? ['pump' => $data, 'index' => $index] : null;

        } catch (\Exception $e) {
            Log::error('getAvailablePumps error', [
                'order_id' => $order_id,
                'trip' => $trip,
                'error' => $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage()
            ]);
            return null;
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