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
        })->select("pump_name", "pump_capacity", "type", "working_hrs_s", "working_hrs_e", "installation_time")
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
                'order_id_wo_trip' => null,
                "installation_time" => $p->installation_time
            );
        }
        return $pumps_availabilty;
    }
    public static function getAvailablePumps($scheduleData, $pumps, $order_id, $company, $pump_start_time, $pump_end_time, $pump_cap, $trip, $selected_order_pump_schedules, $location_end_time, $pump_qty, $location = null, $assinedPump = null, $assinedPumps = array())
    {
        try {

            $data = null;
            $index = null;
            $order = SelectedOrder::find($order_id);
            $capacities = OrderPump::where('order_id', $order->og_order_id)->pluck('quantity', 'capacity')->toArray();
            $capacityKeys = array_keys($capacities);
            $totalAssignedPumps = $assinedPump ? collect($assinedPump)->flatten()->toArray() : [];
            foreach ($pumps as $pumpKey => $pump) {
                $installTime = $pump['installation_time'] ?? 10;
                $qcTime = $scheduleData->qc_time;
                $inspTime = $scheduleData->insp_time;
                $travelTime = $scheduleData->travel_time;

                $totalTime = $installTime + $qcTime + $inspTime + $travelTime+4;
                $start_time = Carbon::parse($pump_start_time)->copy()->subMinutes($totalTime);

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
                $pumpFreeFrom = Carbon::parse($pump['free_from']);
                $pumpFreeUpto = Carbon::parse($pump['free_upto']);

                if ($pumpFreeFrom->gt($pump_end_time) || $pumpFreeUpto->lt($start_time)) {
                    continue;
                }
                $data = $pump;
                $index = $pumpKey;
                break;
            }
            if ($data) {
                return ['pump' => $data, 'index' => $index];
            }
            foreach ($pumps as $pumpKey => $pump) {
                $installTime = $pump['installation_time'] ?? 10;
                $qcTime = $scheduleData->qc_time;
                $inspTime = $scheduleData->insp_time;
                $travelTime = $scheduleData->travel_time;

                $totalTime = $installTime + $qcTime + $inspTime + $travelTime;
                $start_time = $scheduleData->pump_loading_time->copy()->subMinutes($totalTime);

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

                $pumpFreeFrom = Carbon::parse($pump['free_from']);
                $pumpFreeUpto = Carbon::parse($pump['free_upto']);

               if ($pumpFreeFrom->gt($pump_end_time) || $pumpFreeUpto->lt($start_time)) {
                    continue;
                }
                $data = $pump;
                $index = $pumpKey;
                break;
            }
            if (isset($data) && $data) {
                return ['pump' => $data, 'index' => $index];
            } else {
                return null;
            }
        } catch (\Exception $e) {
        }
    }
}