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
    public static function getAvailablePumps(
        $scheduleData,
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
        $assinedPumps = []
    ) {
        try {

            $order = SelectedOrder::find($order_id);

            // capacity => qty
            $capacities = OrderPump::where('order_id', $order->og_order_id)
                ->pluck('quantity', 'capacity')
                ->toArray();

            $capacityKeys = array_keys($capacities);

            $totalAssignedPumps = $assinedPump
                ? collect($assinedPump)->flatten()->toArray()
                : [];

            $pumps = collect($pumps);

            $filtered = $pumps->filter(function ($pump) use ($trip,$assinedPump, $capacities, $pump_qty, $scheduleData, $pump_start_time, $pump_end_time, $capacityKeys, $totalAssignedPumps, $assinedPumps, $location) {

                // capacity check
                if (!in_array($pump['pump_capacity'], $capacityKeys)) {
                    return false;
                }
               

                // assigned pumps logic
                if ($totalAssignedPumps && in_array($pump['pump_name'], $totalAssignedPumps)) {
                    return false;
                }

                if ($assinedPumps && in_array($pump['pump_name'], $assinedPumps)) {
                    return false;
                }

                // location check
                if ($pump['location'] && $location && $pump['location'] !== $location) {
                    return false;
                }

                // time calculation
                $installTime = $pump['installation_time'] ?? 10;
                $totalTime =
                    $installTime +
                    $scheduleData->qc_time +
                    $scheduleData->insp_time +
                    $scheduleData->travel_time + 4;

                $startTime = Carbon::parse($pump_start_time)->copy()->subMinutes($totalTime);

                $freeFrom = Carbon::parse($pump['free_from']);
                $freeUpto = Carbon::parse($pump['free_upto']);

                if ($freeFrom->gt($pump_end_time) || $freeUpto->lt($startTime)) {
                    return false;
                }
                if(count($assinedPump)>count($capacities))
                    return false;
                if($trip>$pump_qty)
                    return false;

                return true;
            })->values();
            //  if($assinedPump!=null)
            //     dd($assinedPump,$filtered);

            if ($filtered->isEmpty()) {
                return null;
            }

            return [
                'pump' => $filtered->first(),
                'index' => $filtered->keys()->first()
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

}