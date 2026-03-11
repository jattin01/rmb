<?php
namespace App\Helpers\V2;
use App\Helpers\ConstantHelper;
use App\Models\Pump;
use App\Models\SelectedOrder;
use App\Models\OrderPump;
use App\Models\OrderTempControl;
use App\Models\ProductType;
use App\Models\SelectedOrderPumpSchedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Lib\Services\ScheduleService;
use Carbon\Carbon;
class PumpHelper
{
    // protected $scheduleService;

    // // public function __construct(
    // // ) {
    // //     $this->scheduleService = new ScheduleService;
    // // }
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

    ) {
        try {

            return DB::transaction(function () use ($pump_end_time, $scheduleData, $pump_start_time, $pumps, $order_id, $clean_end, $required, $pourStart) {

                $order = SelectedOrder::find($order_id);
                if (!$order)
                    return null;

                $reqCap = $required['capacity'] ?? null;
                $reqType = $required['type'] ?? null;
                $pumpsCollect = collect($scheduleData->pump_busy_slots)
                    ->filter(function ($slot) use ($reqCap, $reqType, $order) {
                        return $slot['capacity'] == $reqCap
                            && $slot['type'] == $reqType
                            && $slot['order_no'] != $order->order_no;
                    })
                    ->pluck('pump_id')
                    ->unique()
                    ->values();

                $overlapSlot = collect($scheduleData->pump_busy_slots)
                    ->first(function ($slot) use ($pump_end_time, $reqCap, $reqType, $order, $pump_start_time) {

                        if (
                            $slot['capacity'] != $reqCap ||
                            $slot['type'] != $reqType ||
                            $slot['order_no'] == $order->order_no
                        ) {
                            return false;
                        }

                        $existingStart = Carbon::parse($slot['start']);
                        $existingEnd = Carbon::parse($slot['end']);

                        return $pump_start_time->lt($existingEnd) || $pump_end_time->gt($existingStart);
                    });
                if (!$overlapSlot)
                    return null;

                foreach ($pumpsCollect as $key => $pump) {

                    $pumpSlots = collect($scheduleData->pump_busy_slots)
                        ->where('pump_id', $pump)
                        ->sortBy('pouring_start')
                        ->values();

                    $currentPour = Carbon::parse($pourStart);

                    $previousSlot = $pumpSlots
                        ->where('pouring_start', '<', $currentPour)
                        ->sortByDesc('pouring_start')
                        ->first();

                    $nextSlot = $pumpSlots
                        ->where('pouring_start', '>', $currentPour)
                        ->sortBy('pouring_start')
                        ->first();

                    if (!$previousSlot && !$nextSlot) {
                        continue;
                    }

                    $slotType = !$previousSlot ? 'first'
                        : (!$nextSlot ? 'last' : 'middle');

                    $qcTime = $slotType === 'first' ? $scheduleData->qc_time : 0;
                    $travelTime = $slotType === 'first' ? $order->travel_to_site : 0;

                    $returnTime = match ($slotType) {
                        'first' => ScheduleService::getDistance($order->site_id, $nextSlot['location']),
                        'middle' => ScheduleService::getDistance($order->site_id, $nextSlot['location']),
                        'last' => $order->return_to_plant,
                    };
                    $pumpData = Pump::find($pump);

                    $totalTime = $pumpData->installation_time + $qcTime + $travelTime + $scheduleData->insp_time + (
                        ($pumpData->installation_time > 0 ? 1 : 0) +
                        ($qcTime > 0 ? 1 : 0) +
                        ($travelTime > 0 ? 1 : 0) +
                        ($scheduleData->insp_time > 0 ? 1 : 0));

                    $start = Carbon::parse($pump_start_time)->subMinutes($totalTime);
                    $end = Carbon::parse($clean_end)->addMinutes($returnTime);

                    $hasConflict = $pumpSlots->contains(function ($existing) use ($start, $end) {
                        $existingStart = Carbon::parse($existing['start']);
                        $existingEnd = Carbon::parse($existing['end']);

                        return $start->lt($existingEnd) && $end->gt($existingStart);
                    });

                    if ($hasConflict) {
                        continue;
                    }

 $slotDiff = self::getSlotsDiff($previousSlot, $nextSlot, $start, $end, $order->interval, $pumpData, $order, $qcTime, $order->travel_to_site);
                    if (!$slotDiff) {
                        continue;
                    }
                    $waiting = 0;


                    if ($previousSlot) {
                        self::updatePreviousSlot($previousSlot, $order, $pump, $pumpData, $scheduleData);
                        $waiting = $previousSlot['end']->copy()->diffInMinutes($start);
                    }

                    if ($nextSlot) {
                        self::updateNextSlot($nextSlot, $pump, $pumpData, $scheduleData, $end);
                    }

                    $filtered = array_filter($pumps, function ($pump) use ($pumpData) {
                        return $pump['pump_id'] == $pumpData->id;
                    });

                    if (empty($filtered)) {
                        return null;
                    }

                    $key = array_key_first($filtered);   // get original key
                    $pumpValue = $filtered[$key];


                    return [
                        'pump' => $pumpValue,
                        'index' => $key,
                        'travel_time' => $travelTime,
                        'qc_time' => $qcTime,
                        'return_time' => $returnTime,
                        'slot_type' => $slotType,
                        'waiting' => $waiting
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
    private static function updatePreviousSlot($previousSlot, $order, $pump, $pumpData, $scheduleData)
    {
        $prevData = SelectedOrderPumpSchedule::where('order_no', $previousSlot['order_no'])
            ->where('pump', $pumpData->pump_name)
            ->where('pouring_start', $previousSlot['pouring_start'])
            ->first();

        if (!$prevData)
            return;

        $orderPrev = SelectedOrder::find($prevData->order_id);
        if (!$orderPrev)
            return;

        $prevReturnTime = ScheduleService::getDistance(
            $orderPrev->site_id,
            $order->site_id
        );

        $returnPreStart = Carbon::parse($prevData->cleaning_end)->addMinute();
        $returnPreEnd = $returnPreStart->copy()->addMinutes($prevReturnTime);

        $prevData->update([
            'return_time' => $prevReturnTime,
            'return_start' => $returnPreStart,
            'return_end' => $returnPreEnd,
        ]);

        foreach ($scheduleData->pump_busy_slots as $i => $slot) {
            if (
                $slot['order_no'] == $previousSlot['order_no'] &&
                $slot['pump_id'] == $pump &&
                $slot['pouring_start'] == $previousSlot['pouring_start']
            ) {
                $scheduleData->pump_busy_slots[$i]['end'] = $returnPreEnd;
                Log::info("updated end " . $returnPreEnd);
                break;
            }
        }
    }
    private static function updateNextSlot($nextSlot, $pump, $pumpData, $scheduleData, $end)
    {
        $nextData = SelectedOrderPumpSchedule::where('order_no', $nextSlot['order_no'])
            ->where('pump', $pumpData->pump_name)
            ->where('pouring_start', $nextSlot['pouring_start'])
            ->first();


        if (!$nextData)
            return;

        $waiting = $end->diffInMinutes(Carbon::parse($nextData->insp_start));
        $waiting_time = $nextData->waiting_time + $waiting;


        $slot_start = $end->copy()->addMinute();
        Log::info("old start " . $nextData->insp_start . " new " . $slot_start->format('Y-m-d H:i:s') . "order_no " . $nextData->order_no);


        $slot_start_str = $slot_start->format('Y-m-d H:i:s');
        $nextData->update([
            'qc_time' => 0,
            'travel_time' => 0,
            'travel_start' => $slot_start_str,
            'travel_end' => $slot_start_str,
            'qc_start' => $slot_start_str,
            'qc_end' => $slot_start_str,
            'waiting_time' => $waiting_time,
            'waiting_start' => Carbon::parse($nextData->waiting_start)->subMinutes($waiting)->format('Y-m-d H:i:s'),
            'insp_start' => $slot_start_str,
            'insp_end' => Carbon::parse($nextData->insp_end)->subMinutes($waiting)->format('Y-m-d H:i:s'),
            'install_start' => Carbon::parse($nextData->install_start)->subMinutes($waiting)->format('Y-m-d H:i:s'),
            'install_end' => Carbon::parse($nextData->install_end)->subMinutes($waiting)->format('Y-m-d H:i:s'),
        ]);
        $nextData->refresh();
        Log::info("updated" . $nextData->insp_start);

        foreach ($scheduleData->pump_busy_slots as $i => $slot) {
            if (
                $slot['order_no'] == $nextSlot['order_no'] &&
                $slot['pump_id'] == $pump &&
                $slot['pouring_start'] == $nextSlot['pouring_start']
            ) {
                $scheduleData->pump_busy_slots[$i]['start'] = $slot_start->copy();
                $scheduleData->pump_busy_slots[$i]['waiting'] = $waiting;
                break;
            }
        }
    }
   public static function getSlotsDiff($previousSlot, $nextSlot, $start, $end, $interval, $pumpData, $order, $qc, $travel)
    {
        $result = [];
        $interval = 100;


        Log::info('Checking slot gaps', [
            'start' => $start,
            'end' => $end,
            'interval' => $interval
        ]);

        // Previous slot gap
        if ($previousSlot) {

            $prevData = SelectedOrderPumpSchedule::where('order_no', $previousSlot['order_no'])
                ->where('pump', $pumpData->pump_name)
                ->where('pouring_start', $previousSlot['pouring_start'])
                ->first();


            if (!$prevData)
                return;

            $orderPrev = SelectedOrder::find($prevData->order_id);
            if (!$orderPrev)
                return;

            $prevReturnTime = ScheduleService::getDistance(
                $orderPrev->site_id,
                $order->site_id
            );
            $returnGap = $orderPrev->return_to_plant - $prevReturnTime;
            $totalTimeSiteToPant = $returnGap + $qc + $travel;





            $returnPreStart = Carbon::parse($prevData->cleaning_end)->addMinute();
            $prevReturnEnd = $returnPreStart->copy()->addMinutes($prevReturnTime);

            $currentInspStart = Carbon::parse($start);




            $gapPrev = $prevReturnEnd->diffInMinutes($currentInspStart, false);

            Log::info('Previous slot gap', [
                'prev_return_end' => $previousSlot['end'],
                'current_start' => $start,
                'gap_prev' => $gapPrev,
                'interval' => $interval
            ]);

            if ($gapPrev > $interval) {
                Log::warning('Previous gap exceeded interval', [
                    'gap_prev' => $gapPrev,
                    'interval' => $interval
                ]);
                return false;
            }

            $result['previous_gap'] = $gapPrev;
        }

        // Next slot gap
        if ($nextSlot) {
            $nextData = SelectedOrderPumpSchedule::where('order_no', $nextSlot['order_no'])
                ->where('pump', $pumpData->pump_name)
                ->where('pouring_start', $nextSlot['pouring_start'])
                ->first();
            $nextSlotInterval = $nextSlot['interval'];
            $nextSlotInterval = 100;
            $currentReturnEnd = Carbon::parse($end);
            $nextInspStart = Carbon::parse($nextData->insp_start);

            $gapNext = $currentReturnEnd->diffInMinutes($nextInspStart, false);

            Log::info('Next slot gap', [
                'current_end' => $end,
                'next_insp_start' => $nextSlot['start'],
                'gap_next' => $gapNext,
                'interval' => $nextSlotInterval
            ]);

            if ($gapNext > $nextSlotInterval) {
                Log::warning('Next gap exceeded interval', [
                    'gap_next' => $gapNext,
                    'interval' => $nextSlotInterval
                ]);
                return false;
            }

            $result['next_gap'] = $gapNext - 1;
        }

        Log::info('Slot gap check passed', $result);

        return $result;
    }

}