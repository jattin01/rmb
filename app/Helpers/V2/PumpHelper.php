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
        array &$slots = []
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
                return $aStart->lt($bEnd) && $aEnd->gt($bStart);
            };

            $canUse = function ($pump) use ($scheduleData, $reqCap, $reqType, $location, $startNeed, $endNeed, &$slots, $overlaps) {
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
                $totalTime = $installTime + (int) $scheduleData->qc_time + (int) $scheduleData->insp_time + (int) $scheduleData->travel_time + 4;

                $busyStart = $startNeed->copy()->subMinutes($totalTime);
                $busyEnd = $endNeed->copy();

                $freeFrom = Carbon::parse($pump['free_from']);
                $freeUpto = Carbon::parse($pump['free_upto']);

                // must cover whole busy window
                if ($freeFrom->gt($busyStart) || $freeUpto->lt($busyEnd)) {
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
        $required = null,
        $pourStart,
        $assignedPumps = null
    ) {
        try {

            return DB::transaction(function () use ($assignedPumps, $scheduleData, $pumps, $order_id, $pump_start_time, $pump_end_time, $required, $pourStart) {
             $order = SelectedOrder::find($order_id);
                if (!$order)
                    return null;    
            $productType = ProductType::where('type', '=', $order->mix_code)
                    ->first();
                $orderTempControl = OrderTempControl::where('order_id', $order->og_order_id)->first();
                if ($productType) {
                    $tempLoadingTime = 0;
                    if ($orderTempControl) {
                        $tempQuantity = $orderTempControl->quantity;
                        $tempLoadingTime = $productType->temperature_creation_time;
                    }
                    $scheduleData->loading_time = $productType->batching_creation_time + $tempLoadingTime;
                }
                $installTime = (int) ($pump['installation_time'] ?? 10);
                $totalTime = $installTime + (int) $scheduleData->qc_time + (int) $scheduleData->insp_time + (int) $scheduleData->travel_time + 4;

                $busyStart = $pump_start_time->copy()->subMinutes($totalTime);

               

                $reqCap = $required['capacity'] ?? null;
                $reqType = $required['type'] ?? null;
                $filteredSlots = array_filter(
                    $scheduleData->pump_busy_slots,
                    function ($slot) use ($reqCap, $reqType, $pumps, $order) {
                        $pump = collect($pumps)->firstWhere('pump_id', $slot['pump_id']);
                        if (!$pump)
                            return false;
                        return $slot['type'] === $reqType
                            && $slot['capacity'] === $reqCap
                            && $slot['order_no'] !== $order->order_no;
                    }
                );
               

                foreach ($filteredSlots as $key => $previous) {
                    $site_to_site_distance = $previous['pouring_start']->lt($pourStart) ? ScheduleService::getDistance($previous['location'],$order->site_id) : ScheduleService::getDistance($order->site_id,$previous['location']);
                    $pump = collect($pumps)->firstWhere('pump_id', $previous['pump_id']);

                    if (!$pump)
                        continue;
                    $slotStart = Carbon::parse($previous['start']);
                    $slotEnd = Carbon::parse($previous['end']);


                    $overlaps = $slotStart->lt(Carbon::parse($pump_end_time)) && $slotEnd->gt($busyStart);
                    

                    if ($overlaps) {
                        $endTimePre = $previous['end']->copy();
                        $startTimePre = $previous['start']->copy();

                        if (!$previous['site_to_site']) {
                            $pumpId = $previous['pump_id'];

                            $qcTime = $previous['pouring_start']->lt($pourStart) ? 0 : $scheduleData->qc_time;
                            $travelTime = $previous['pouring_start']->lt($pourStart) ? 0 : $order->travel_to_site;
                            $returnTime = $previous['pouring_start']->lt($pourStart) ? $order->return_to_plant : $site_to_site_distance;


                            $inspTime = $scheduleData->insp_time;
                            $waitingTime = $scheduleData->qc_time + $inspTime + $order->travel_to_site + $scheduleData->loading_time + 4;
                            $waitingTimePre = $scheduleData->qc_time + $inspTime + $order->travel_to_site + $scheduleData->loading_time;
                            $installTime = $pump['installation_time'] ?? 10;
                            $totalTime = $installTime + $waitingTime + $qcTime + $travelTime + $inspTime + 5;


                            $qcStart = Carbon::parse($pourStart)->copy()->subMinutes($totalTime);
                            $qcEnd = $qcTime!==0?$qcStart->copy()->addMinutes($qcTime):$qcStart->copy();
                            $travelStart = $travelTime!==0?$qcEnd->copy()->addMinute():$qcEnd->copy();
                            $travelEnd = $travelStart->copy()->addMinutes($travelTime);
                            $inspStart =  $travelTime!==0 ? $travelEnd->copy()->addMinute(): $travelEnd->copy();
                            $inspEnd = $inspStart->copy()->addMinutes($inspTime);
                            $installStart = $inspEnd->copy()->addMinute();
                            $installEnd = $installStart->copy()->addMinutes($installTime);
                            $waitingStart = $inspEnd->copy()->addMinute();
                            $waitingEnd = $waitingStart->copy()->addMinutes($waitingTime);
                            $returnStart = $pump_end_time->copy()->subMinutes($order->return_to_plant + 1);
                            $returnEnd = $returnStart->copy()->addMinutes($returnTime);




                            $qcTimePre = $previous['pouring_start']->lt($pourStart) ? $scheduleData->qc_time : 0;
                            $travelTimePre = $previous['pouring_start']->lt($pourStart) ? $order->travel_to_site : 0;
                            $returnTimePre = $previous['pouring_start']->lt($pourStart) ? $site_to_site_distance : $order->return_to_plant;
                            $totalTimePre = $installTime + $waitingTimePre + $qcTimePre + $travelTimePre + $inspTime + 5;






                            $qcStartPre = Carbon::parse($previous['pouring_start'])->copy()->subMinutes($totalTimePre);
                            $qcEndPre = $qcTimePre!==0?$qcStartPre->copy()->addMinutes($qcTimePre):$qcStartPre->copy();
                            $travelStartPre = $travelTimePre!==0?$qcEndPre->copy()->addMinute():$qcEndPre->copy();
                            $travelEndPre = $travelStartPre->copy()->addMinutes($travelTimePre);
                            $inspStartPre =  $travelTimePre!==0 ? $travelEndPre->copy()->addMinute(): $travelEndPre->copy();
                            $inspEndPre = $inspStartPre->copy()->addMinutes($inspTime);
                            $installStartPre = $inspEndPre->copy()->addMinute();
                            $installEndPre = $installStartPre->copy()->addMinutes($installTime);
                            $waitingStartPre = $inspEndPre->copy()->addMinute();
                            $waitingEndPre = $waitingStartPre->copy()->addMinutes($waitingTime);
                            $returnStartPre = $previous['end']->copy()->subMinutes($order->return_to_plant);
                            $returnEndPre = $returnStartPre->copy()->addMinutes($returnTimePre);



                            $previousStart = $qcStartPre;
                            $previousEnd = $returnEndPre;
                            $newStart = $qcStart;
                            $newEnd = $returnEnd;


                            if ($previousStart->lte($newEnd) && $previousEnd->gte($newStart)) {
                                continue;
                            }

                            $pumpData = Pump::find($previous['pump_id']);
                            $previousData = SelectedOrderPumpSchedule::
                                where('pump', $pumpData->pump_name)
                                ->where('qc_start', $previous['start'])
                                ->where('return_end', $previous['end'])
                                ->where('order_no', $previous['order_no'])
                                ->first();




                            $returnEnd = carbon::parse($previousData->return_start)->copy()->addMinutes($returnTimePre);
                            $previousData->qc_start = $qcStartPre;
                            $previousData->qc_time = $qcTimePre;
                            $previousData->qc_end = $qcEndPre;
                            $previousData->travel_start = $travelStartPre;
                            $previousData->travel_time = $travelTimePre;
                            $previousData->travel_end = $travelEndPre;
                            $previousData->insp_start = $inspStartPre;
                            $previousData->insp_end = $inspEndPre;
                            $previousData->install_start = $installStartPre;
                            $previousData->install_end = $installEndPre;
                            $previousData->waiting_start = $waitingStartPre;
                            $previousData->waiting_end = $waitingEndPre;
                            $previousData->return_time = $returnTimePre;
                            $previousData->return_end = $returnEndPre;
                            $previousData->save();

                            $scheduleData->pump_busy_slots[$key]['return_time'] = $returnTimePre;
                            $scheduleData->pump_busy_slots[$key]['end'] = $returnEndPre;
                            $scheduleData->pump_busy_slots[$key]['site_to_site'] = $previous['pouring_start']->lt($pourStart) ? true: false;



                            return [
                                'pump' => $pump,
                                'index' => $key,
                                'travel_time' => $travelTime,
                                'qc_time' => $qcTime,
                                'return_time' => $returnTime,
                                'site_to_site' => $previous['pouring_start']->lt($pourStart) ? false : true,
                            ];
                        }
                    }
                }
                return null;
            });

        } catch (\Throwable $e) {

            DB::rollBack(); // only needed if using manual transaction

            Log::error('Pump overlap error: ' . $e->getTraceAsString(), [
                'order_id' => $order_id
            ]);

            return null; // or throw $e;
        }


    }


}