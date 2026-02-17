<?php
namespace App\Helpers\V2;
use App\Helpers\ConstantHelper;
use App\Lib\Services\ScheduleService;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\OrderPump;
use Illuminate\Support\Facades\Log;


use Carbon\Carbon;
class PumpHelper
{
    public function getPumpsAvailability(int $company_id, string $schedule_date, array $pump_ids): array
    {
        $pumps_availabilty = [];
        $ps = Pump::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "pumps.group_company_id");
        })->select("pump_name", "pump_capacity", "type", "working_hrs_s", "working_hrs_e", "installation_time", "pumps.id")
            ->where("group_companies.id", $company_id)
            ->where("pumps.status", ConstantHelper::ACTIVE)
            ->whereIn("pumps.id", $pump_ids)
            ->get();
        foreach ($ps as $p) {
            $pumps_availabilty[] = array(
                'pump_id' => $p->id,
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
    $assinedPumps = [],
    $required = null,
    array &$slots = []
) {
    try {
        $order = SelectedOrder::find($order_id);
        if (!$order) return null;

        $reqCap  = $required['capacity'] ?? null;
        $reqType = $required['type'] ?? null;

        $startNeed = $pump_start_time instanceof Carbon ? $pump_start_time : Carbon::parse($pump_start_time);
        $endNeed   = $pump_end_time   instanceof Carbon ? $pump_end_time   : Carbon::parse($pump_end_time);

        // helper: check overlap
        $overlaps = function (Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool {
            // overlap if aStart < bEnd AND aEnd > bStart
            return $aStart->lt($bEnd) && $aEnd->gt($bStart);
        };

        // helper: can this pump be used?
        $canUsePump = function ($pump) use (
            $scheduleData, $reqCap, $reqType, $location, $startNeed, $endNeed, &$slots, $overlaps
        ): array {
            if (!isset($pump['pump_id'], $pump['pump_name'], $pump['pump_capacity'], $pump['pump_type'], $pump['free_from'], $pump['free_upto'])) {
                return [false, null, null];
            }

            // required match
            if ($reqCap !== null && (float)$pump['pump_capacity'] !== (float)$reqCap) return [false, null, null];
            if ($reqType !== null && (string)$pump['pump_type'] !== (string)$reqType) return [false, null, null];

            // location check
            if (!empty($pump['location']) && $location && $pump['location'] !== $location) return [false, null, null];

            $installTime = (int)($pump['installation_time'] ?? 10);

            // Busy start is BEFORE pump_start_time (QC/Travel/Insp/Install etc.)
            $totalTime = $installTime
                + (int)$scheduleData->qc_time
                + (int)$scheduleData->insp_time
                + (int)$scheduleData->travel_time
                + 4;

            $busyStart = $startNeed->copy()->subMinutes($totalTime);
            $busyEnd   = $endNeed->copy(); // until return_end of that pump group/order

            // availability window check (pump must cover [busyStart..busyEnd])
            $freeFrom = Carbon::parse($pump['free_from']);
            $freeUpto = Carbon::parse($pump['free_upto']);

            if ($freeFrom->gt($busyEnd) || $freeUpto->lt($busyStart)) {
                return [false, $busyStart, $busyEnd];
            }

            // busy slots overlap check
            foreach ($slots as $slot) {
                if ((int)$slot['pump_id'] !== (int)$pump['pump_id']) continue;

                $slotStart = $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']);
                $slotEnd   = $slot['end']   instanceof Carbon ? $slot['end']   : Carbon::parse($slot['end']);

                if ($overlaps($busyStart, $busyEnd, $slotStart, $slotEnd)) {
                    return [false, $busyStart, $busyEnd];
                }
            }

            return [true, $busyStart, $busyEnd];
        };

        $pumps = collect($pumps);

        // 1) ✅ Prefer already assigned pumps (reuse if free)
        if (!empty($assinedPumps)) {
            foreach ($assinedPumps as $preferredPumpName) {
                $pump = $pumps->firstWhere('pump_name', $preferredPumpName);
                if (!$pump) continue;

                [$ok, $busyStart, $busyEnd] = $canUsePump($pump);
                if ($ok) {
                    // add slot ONLY after selection
                    $slots[] = [
                        'pump_id' => (int)$pump['pump_id'],
                        'start'   => $busyStart->copy(),
                        'end'     => $busyEnd->copy(),
                        'order_no'=> $order->order_no,
                    ];

                    Log::info("Picked preferred pump {$pump['pump_name']} for order {$order->order_no}", [
                        'busy_start' => $busyStart->toDateTimeString(),
                        'busy_end'   => $busyEnd->toDateTimeString(),
                    ]);

                    return ['pump' => $pump, 'index' => $pumps->search(fn($x) => $x['pump_id'] == $pump['pump_id'])];
                }
            }
        }

        // 2) Otherwise pick any available pump (FIFO style optional)
        $candidates = [];
        foreach ($pumps as $idx => $pump) {
            [$ok, $busyStart, $busyEnd] = $canUsePump($pump);
            if (!$ok) continue;

            $freeFrom = Carbon::parse($pump['free_from']);
            $idleGap  = abs($freeFrom->diffInMinutes($busyStart, false));

            $candidates[] = [
                'pump' => $pump,
                'index' => $idx,
                'score_free_from' => $freeFrom->timestamp,
                'score_idle_gap' => $idleGap,
                'busyStart' => $busyStart,
                'busyEnd' => $busyEnd,
            ];
        }

        if (empty($candidates)) return null;

        usort($candidates, function ($a, $b) {
            if ($a['score_free_from'] !== $b['score_free_from']) return $a['score_free_from'] <=> $b['score_free_from'];
            return $a['score_idle_gap'] <=> $b['score_idle_gap'];
        });

        $winner = $candidates[0]['pump'];
        $busyStart = $candidates[0]['busyStart'];
        $busyEnd   = $candidates[0]['busyEnd'];

        // add slot ONLY after selection
        $slots[] = [
            'pump_id' => (int)$winner['pump_id'],
            'start'   => $busyStart->copy(),
            'end'     => $busyEnd->copy(),
            'order_no'=> $order->order_no,
        ];

        Log::info("Picked pump {$winner['pump_name']} for order {$order->order_no}", [
            'busy_start' => $busyStart->toDateTimeString(),
            'busy_end'   => $busyEnd->toDateTimeString(),
        ]);

        return [
            'pump'  => $winner,
            'index' => $candidates[0]['index']
        ];

    } catch (\Exception $e) {
        Log::error("getAvailablePumps error: " . $e->getMessage());
        return null;
    }
}


}