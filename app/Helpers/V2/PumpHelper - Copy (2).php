<?php
namespace App\Helpers\V2;
use App\Helpers\ConstantHelper;
use App\Lib\Services\ScheduleService;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\OrderPump;
use App\Models\OrderTempControl;
use App\Models\ProductType;
use App\Models\SelectedOrderPumpSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;




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
        array &$slots = [],
        $qc,
        $insp,
        $travel,
        $loading
    ) {
        try {
            $order = SelectedOrder::find($order_id);
            if (!$order)
                return null;

            $reqCap = $required['capacity'] ?? null;
            $reqType = $required['type'] ?? null;

            $startNeed = $pump_start_time instanceof Carbon ? $pump_start_time : Carbon::parse($pump_start_time);
            $endNeed = $pump_end_time instanceof Carbon ? $pump_end_time : Carbon::parse($pump_end_time);

            $overlaps = function (Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool {
                return $aStart->lte($bEnd) && $aEnd->gte($bStart);
            };

            $canUse = function ($pump) use ($scheduleData, $reqCap, $reqType, $location, $startNeed, $endNeed, &$slots, $overlaps, $qc, $insp, $loading, $travel) {
                if (!isset($pump['pump_id'], $pump['pump_name'], $pump['pump_capacity'], $pump['pump_type'], $pump['free_from'], $pump['free_upto'])) {
                    return [false, null, null];
                }

                if ($reqCap !== null && (float) $pump['pump_capacity'] !== (float) $reqCap)
                    return [false, null, null];
                if ($reqType !== null && (string) $pump['pump_type'] !== (string) $reqType)
                    return [false, null, null];

                if (!empty($pump['location']) && $location && $pump['location'] !== $location)
                    return [false, null, null];

                $installTime = (int) ($pump['installation_time'] ?? 10);
                $totalTime = $installTime + (int) $qc + (int) $insp + (int) $travel + 4;

                $busyStart = $startNeed->copy()->subMinutes($totalTime);
                $busyEnd = $endNeed->copy();

                $freeFrom = Carbon::parse($pump['free_from']);
                $freeUpto = Carbon::parse($pump['free_upto']);

                // must cover whole busy window
                if ($freeFrom->gte($busyStart) || $freeUpto->lte($busyEnd)) {
                    return [false, $busyStart, $busyEnd];
                }

                // slot overlap
                foreach ($slots as $slot) {
                    if ((int) $slot['pump_id'] !== (int) $pump['pump_id'])
                        continue;
                    $slotStart = $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']);
                    $slotEnd = $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']);

                    if ($overlaps($busyStart, $busyEnd, $slotStart, $slotEnd)) {
                        return [false, $busyStart, $busyEnd];
                    }
                }

                return [true, $busyStart, $busyEnd];
            };

            $pumpsCol = collect($pumps);

            // ✅ 1) Prefer already-used pumps (reuse if free)
            if (!empty($assinedPumps)) {
                foreach ($assinedPumps as $preferredName) {
                    $pump = $pumpsCol->firstWhere('pump_name', $preferredName);
                    if (!$pump)
                        continue;

                    [$ok, $busyStart, $busyEnd] = $canUse($pump);
                    if ($ok) {
                        $slots[] = [
                            'pump_id' => (int) $pump['pump_id'],
                            'start' => $busyStart->copy(),
                            'end' => $busyEnd->copy(),
                            'order_no' => $order->order_no,
                        ];

                        Log::info("Picked preferred pump {$pump['pump_name']} for {$order->order_no}", [
                            'busy_start' => $busyStart->toDateTimeString(),
                            'busy_end' => $busyEnd->toDateTimeString(),
                        ]);

                        return ['pump' => $pump, 'index' => $pumpsCol->search(fn($x) => (int) $x['pump_id'] == (int) $pump['pump_id'])];
                    }
                }
            }

            // ✅ 2) FIFO pick from all candidates
            $candidates = [];
            foreach ($pumpsCol as $idx => $pump) {
                [$ok, $busyStart, $busyEnd] = $canUse($pump);
                if (!$ok)
                    continue;

                $freeFrom = Carbon::parse($pump['free_from']);
                $idleGap = abs($freeFrom->diffInMinutes($busyStart, false));

                $candidates[] = [
                    'pump' => $pump,
                    'index' => $idx,
                    'free_from_ts' => $freeFrom->timestamp,
                    'idle_gap' => $idleGap,
                    'busyStart' => $busyStart,
                    'busyEnd' => $busyEnd,
                ];
            }

            if (empty($candidates))
                return null;

            usort($candidates, function ($a, $b) {
                if ($a['free_from_ts'] !== $b['free_from_ts'])
                    return $a['free_from_ts'] <=> $b['free_from_ts'];
                return $a['idle_gap'] <=> $b['idle_gap'];
            });

            $winner = $candidates[0]['pump'];
            $busyStart = $candidates[0]['busyStart'];
            $busyEnd = $candidates[0]['busyEnd'];

            // ✅ add slot ONLY for winner
            $slots[] = [
                'pump_id' => (int) $winner['pump_id'],
                'start' => $busyStart->copy(),
                'end' => $busyEnd->copy(),
                'order_no' => $order->order_no,
            ];

            Log::info("Picked pump {$winner['pump_name']} for {$order->order_no}", [
                'busy_start' => $busyStart->toDateTimeString(),
                'busy_end' => $busyEnd->toDateTimeString(),
            ]);

            return ['pump' => $winner, 'index' => $candidates[0]['index']];

        } catch (\Exception $e) {
            Log::error("getAvailablePumps error: " . $e->getMessage());
            return null;
        }
    }
    public static function getOverlapPumps(
        $scheduleData,
        $pumps,
        $order_id,
        $pump_start_time,
        $pump_end_time,
        $clean_end,
        $required = null,
        $pourStart,
        $qc,
        $Loading,
        $travel,
        $inspTime,
        $installTime
    ) {
        try {

            return DB::transaction(function () use ($Loading, $scheduleData, $pumps, $order_id, $pump_start_time, $pump_end_time, $clean_end, $required, $pourStart, $qc, $travel, $inspTime, $installTime) {

                $order = SelectedOrder::find($order_id);
                if (!$order)
                    return null;

                $reqCap = $required['capacity'] ?? null;
                $reqType = $required['type'] ?? null;
                $pumps = collect($scheduleData->pump_busy_slots)
                    ->filter(function ($slot) use ($reqCap, $reqType, $order) {
                        return $slot['capacity'] == $reqCap
                            && $slot['type'] == $reqType
                            && $slot['order_no'] != $order->order_no;
                    })
                    ->pluck('pump_id')
                    ->unique()
                    ->values();

                foreach ($pumps as $key => $pump) {

                    $previousSlots = collect($scheduleData->pump_busy_slots)
                        ->where('pump_id', $pump)
                        ->sortBy('pouring_start')
                        ->values();


                    $currentPour = Carbon::parse($pourStart);

                    $previousSlot = $previousSlots
                        ->filter(function ($slot) use ($currentPour) {
                            return Carbon::parse($slot['pouring_start'])
                                ->lt($currentPour);
                        })
                        ->sortByDesc('pouring_start')
                        ->first();

                    $nextSlot = $previousSlots
                        ->filter(function ($slot) use ($currentPour) {
                            return Carbon::parse($slot['pouring_start'])
                                ->gt($currentPour);
                        })
                        ->sortBy('pouring_start')
                        ->first();

                    $isFirst = !$previousSlot && $nextSlot;
                    $isLast = !$nextSlot && $previousSlot;
                    $isMiddle = $previousSlot && $nextSlot;

                    if ($previousSlot === null && $nextSlot === null) {
                        continue;
                    };

                    if ($isFirst) {
                        $qcTime = $qc;
                        $travelTime = $order->travel_to_site;
                        $returnTime =
                            ScheduleService::getDistance(
                                $order->site_id,
                                $nextSlot['location']
                            );
                    }
                    if ($isMiddle) {

                        $qcTime = 0;
                        $travelTime = 0;
                        $returnTime =
                                ScheduleService::getDistance(
                                    $order->site_id,
                                    $nextSlot['location']
                                );
                        
                    }
                    if ($isLast) {
                        $qcTime = 0;
                        $travelTime = 0;
                        $returnTime = $order->return_to_plant;
                    }

                    $totalTime =
                        $installTime +
                        $qcTime +
                        $travelTime +
                        $inspTime +
                        (
                            ($installTime > 0 ? 1 : 0) +
                            ($qcTime > 0 ? 1 : 0) +
                            ($travelTime > 0 ? 1 : 0) +
                            ($inspTime > 0 ? 1 : 0)
                        );

                    $start = Carbon::parse($pourStart)
                        ->copy()
                        ->subMinutes($totalTime);

                    $end = Carbon::parse($clean_end)
                        ->copy()
                        ->addMinutes($returnTime);

                    /* -------------------------------------------------
                       STEP 2 — Overlap Check (pure time logic)
                    -------------------------------------------------*/

                    $hasConflict = false;

                    foreach ($previousSlots as $existing) {
                        $existingStart = Carbon::parse($existing['start']);
                        $existingEnd = Carbon::parse($existing['end']);

                        if (
                            $start->lt($existingEnd) &&
                            $end->gt($existingStart)
                        ) {
                            $hasConflict = true;
                            break;
                        }
                    }

                    if ($hasConflict)
                        continue;

                    $pumpData = Pump::find($pump);
                    if ($isFirst) {

                        $nextData = SelectedOrderPumpSchedule::where('order_no', $nextSlot['order_no'])
                            ->where('pump', $pumpData->pump_name)
                            ->where('pouring_start', $nextSlot['pouring_start'])
                            ->first();

                        if ($nextData) {
                            $nextData->qc_time = 0;
                            $nextData->travel_time = 0;
                            $nextData->travel_start = null;
                            $nextData->travel_end = null;
                            $nextData->qc_start = null;
                            $nextData->qc_end = null;
                            $nextData->save();

                            foreach ($scheduleData->pump_busy_slots as $i => $slot) {

                                if (
                                    $slot['order_no'] == $nextSlot['order_no'] &&
                                    $slot['pump_id'] == $pump
                                ) {
                                    $scheduleData->pump_busy_slots[$i]['qc_time'] = 0;
                                    $scheduleData->pump_busy_slots[$i]['travel_time'] = 0;
                                    $scheduleData->pump_busy_slots[$i]['start']= $nextData->insp_start;

                                }
                            }
                        }
                    }
                    if ($isLast) {
                        
                        $prevData = SelectedOrderPumpSchedule::where('order_no', $previousSlot['order_no'])
                            ->where('pump', $pumpData->pump_name)
                            ->where('pouring_start', $previousSlot['pouring_start'])
                            ->first();
                        $orderPrev = SelectedOrder::find($prevData->order_id);

                        if ($prevData) {
                            $prevReturnTime = ScheduleService::getDistance($orderPrev->site_id,$order->site_id);
                            $returnPreStart = Carbon::parse($prevData->cleaning_end)->copy()->adMinute();
                            $returnPreEnd = $returnPreStart->copy()->addMinutes($prevReturnTime);
                            $prevData->return_time = $prevReturnTime;
                            $prevData->return_start = $returnPreStart;
                            $prevData->return_end = $returnPreEnd;
                            $prevData->save();

                            foreach ($scheduleData->pump_busy_slots as $i => $slot) {

                                if (
                                    $slot['order_no'] == $previousSlot['order_no'] &&
                                    $slot['pump_id'] == $pump
                                ) {
                                    $scheduleData->pump_busy_slots[$i]['return_time'] = $prevReturnTime;
                                    $scheduleData->pump_busy_slots[$i]['end']= $returnPreEnd;

                                }
                            }
                        }

                    }
                     if ($isMiddle) {
                        
                        $prevData = SelectedOrderPumpSchedule::where('order_no', $previousSlot['order_no'])
                            ->where('pump', $pumpData->pump_name)
                            ->where('pouring_start', $previousSlot['pouring_start'])
                            ->first();
                        $orderPrev = SelectedOrder::find($prevData->order_id);

                        if ($prevData) {
                            $prevReturnTime = ScheduleService::getDistance($orderPrev->site_id,$order->site_id);
                            $returnPreStart = Carbon::parse($prevData->cleaning_end)->copy()->adMinute();
                            $returnPreEnd = $returnPreStart->copy()->addMinutes($prevReturnTime);
                            $prevData->return_time = $prevReturnTime;
                            $prevData->return_start = $returnPreStart;
                            $prevData->return_end = $returnPreEnd;
                            $prevData->save();

                            foreach ($scheduleData->pump_busy_slots as $i => $slot) {

                                if (
                                    $slot['order_no'] == $previousSlot['order_no'] &&
                                    $slot['pump_id'] == $pump
                                ) {
                                    $scheduleData->pump_busy_slots[$i]['return_time'] = $prevReturnTime;
                                    $scheduleData->pump_busy_slots[$i]['end']= $returnPreEnd;

                                }
                            }
                        }
                           $nextData = SelectedOrderPumpSchedule::where('order_no', $nextSlot['order_no'])
                            ->where('pump', $pumpData->pump_name)
                            ->where('pouring_start', $nextSlot['pouring_start'])
                            ->first();

                        if ($nextData) {
                            $nextData->qc_time = 0;
                            $nextData->travel_time = 0;
                            $nextData->travel_start = null;
                            $nextData->travel_end = null;
                            $nextData->qc_start = null;
                            $nextData->qc_end = null;
                            $nextData->save();

                            foreach ($scheduleData->pump_busy_slots as $i => $slot) {

                                if (
                                    $slot['order_no'] == $nextSlot['order_no'] &&
                                    $slot['pump_id'] == $pump
                                ) {
                                    $scheduleData->pump_busy_slots[$i]['qc_time'] = 0;
                                    $scheduleData->pump_busy_slots[$i]['travel_time'] = 0;
                                    $scheduleData->pump_busy_slots[$i]['start']= $nextData->insp_start;

                                }
                            }
                        }

                    }

                    return [
                        'pump' => $pump,
                        'index' => $key,
                        'travel_time' => $travelTime,
                        'qc_time' => $qcTime,
                        'return_time' => $returnTime,
                        'slot_type' => $isFirst ? 'first' : ($isMiddle ? 'next' : 'last'),
                        'site_to_site' => true,
                    ];
                }

                return null;
            });

        } catch (\Throwable $e) {

            Log::error('Pump overlap error', [
                'order_id' => $order_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

}