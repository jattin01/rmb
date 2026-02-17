<?php
namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\TransitMixer;
use Carbon\Carbon;

class TransitMixerHelper
{


    public function getTrucksAvailability(int $company_id, string $schedule_date, array $transit_mixer_ids): array
    {
        $tms_availabilty = [];

        $tms = TransitMixer::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "transit_mixers.group_company_id");
        })->select("truck_name", "truck_capacity", "loading_time", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company_id)
            ->where("transit_mixers.status", ConstantHelper::ACTIVE)
            ->whereIn("transit_mixers.id", $transit_mixer_ids)
            ->orderBy('transit_mixers.truck_capacity')
            ->get();

        foreach ($tms as $tm) {
            $tms_availabilty[] = array(
                'truck_name' => $tm->truck_name,
                'truck_capacity' => $tm->truck_capacity,
                'loading_time' => $tm->loading_time,
                'free_from' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->subDays(1)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
            );
        }
        return $tms_availabilty;
    }
public static function getAvailableTrucks(
    array $trucks,
    $truck_cap,
    Carbon $loading_start,
    Carbon $return_end,
    $location_end_time,
    $restriction_start,
    $restriction_end,
    $location = null,
    $trip = null,
    array $assignedTrucks = [],
    array $slots = []
) {
    if(count($slots))
        dd($slots,$trucks);
    // ---------------------------
    // 1) Min boundary & restriction
    // ---------------------------
    $location_end_time = $location_end_time instanceof Carbon ? $location_end_time : Carbon::parse($location_end_time);
    $min_date = $location_end_time->lte($return_end) ? $location_end_time : $return_end;

    if (isset($restriction_start, $restriction_end)) {
        $rStart = Carbon::parse($restriction_start);
        $rEnd   = Carbon::parse($restriction_end);

        if ($loading_start->between($rStart, $rEnd) || $min_date->between($rStart, $rEnd)) {
            return null;
        }
    }

    // ---------------------------
    // 2) Build busy map (truck => intervals)
    // ---------------------------
    $busyByTruck = [];
    foreach ($slots as $s) {
        $tid = $s['truck_id'] ?? null;
        if (!$tid) continue;

        $busyByTruck[$tid][] = [
            'start' => $s['start'] instanceof Carbon ? $s['start'] : Carbon::parse($s['start']),
            'end'   => $s['end']   instanceof Carbon ? $s['end']   : Carbon::parse($s['end']),
        ];
    }

    // ---------------------------
    // 3) Helpers
    // ---------------------------
    $overlapsTripWindow = function (string $truckName) use (&$busyByTruck, $loading_start, $return_end): bool {
        foreach ($busyByTruck[$truckName] ?? [] as $iv) {
            if ($loading_start->lt($iv['end']) && $return_end->gt($iv['start'])) {
                return true;
            }
        }
        return false;
    };

    /**
     * FIFO metric: "waiting minutes" (bigger = older = FIFO winner)
     * - If any interval covers loading_start => not eligible (return null)
     * - Else take the latest end <= loading_start => lastReturn
     * - gap = minutes(lastReturn -> loading_start)
     */
    $fifoGapMinutes = function (string $truckName) use (&$busyByTruck, $loading_start): ?int {
        $lastEnd = null;

        foreach ($busyByTruck[$truckName] ?? [] as $iv) {
            // If busy covers required loading time => not free at loading_start
            if ($iv['start']->lte($loading_start) && $iv['end']->gt($loading_start)) {
                return null;
            }

            // Track last end before/at loading_start
            if ($iv['end']->lte($loading_start)) {
                if ($lastEnd === null || $iv['end']->gt($lastEnd)) {
                    $lastEnd = $iv['end']->copy();
                }
            }
        }

        // If never used before, treat as "not FIFO preferred" OR "most preferred"
        // Choose ONE rule:
        // A) If you want new trucks NOT to dominate FIFO -> return 0
        // B) If you want new trucks to be strongest FIFO -> set very old date
        if ($lastEnd === null) {
            return 0; // ✅ recommended for your case (prevents 'no-history' truck winning)
            // OR: $lastEnd = Carbon::create(1970,1,1,0,0,0); return $lastEnd->diffInMinutes($loading_start);
        }

        return $lastEnd->diffInMinutes($loading_start);
    };

    $eligible = function (array $truck, bool $requireCapacityMatch) use (
        $truck_cap, $location, $loading_start, $min_date, $overlapsTripWindow
    ): bool {
        if (!isset($truck['truck_name'], $truck['truck_capacity'])) return false;

        if ($requireCapacityMatch && $truck['truck_capacity'] != $truck_cap) return false;

        if (!empty($truck['location']) && !empty($location) && $truck['location'] != $location) return false;

        // Optional hard windows (only if you trust them)
        if (isset($truck['free_upto']) && Carbon::parse($truck['free_upto'])->lt($min_date)) return false;
        if (isset($truck['free_from']) && Carbon::parse($truck['free_from'])->gt($loading_start)) return false;

        // Must not overlap during [loading_start, return_end]
        if ($overlapsTripWindow($truck['truck_name'])) return false;

        return true;
    };
    


    $pickFIFOFromCandidates = function (array $candidateTrucks) use ($fifoGapMinutes): ?array {
        $best = ['data' => $candidateTrucks[0], 'index' => 0];        
        return $best ? ['data' => $best['data'], 'index' => $best['index']] : null;
    };

    // ---------------------------
    // 4) PASS 1: Assigned trucks
    // ---------------------------
    $cand = [];
    foreach ($trucks as $i => $t) {
        if (!in_array($t['truck_name'] ?? '', $assignedTrucks, true)) continue;
        if ($eligible($t, false)) $cand[$i] = $t;
    }
    $picked = $pickFIFOFromCandidates($cand);
    if ($picked) return $picked;

    // ---------------------------
    // 5) PASS 2: Capacity match
    // ---------------------------
    $cand = [];
    foreach ($trucks as $i => $t) {
        if ($eligible($t, true)) $cand[$i] = $t;
    }
    $picked = $pickFIFOFromCandidates($cand);
    if ($picked) return $picked;

    // ---------------------------
    // 6) PASS 3: Any capacity
    // ---------------------------
    $cand = [];
    foreach ($trucks as $i => $t) {
        if ($eligible($t, false)) $cand[$i] = $t;
    }
    return $pickFIFOFromCandidates($cand);
}


    private static function nextAvailableAtFromSlots(
        array $trucks,
        Carbon $loading_start,
        array $slots
    ): ?array {

        // Guard clauses
        if (empty($trucks)) {
            return null;
        }
        if (empty($slots)) {
            return [
                'data' => $trucks[0] ?: null,
                'index' => 0,
            ];
        }

        $busy = [];

        foreach ($slots as $slot) {
            foreach ($trucks as $truck) {
                if (($slot['truck_id'] ?? null) !== ($truck['truck_name'] ?? null)) {
                    continue;
                }

                $e = $slot['end'] instanceof Carbon
                    ? $slot['end']
                    : Carbon::parse($slot['end']);

                $gap = $e->copy()->diffInMinutes($loading_start);

                $busy[] = [
                    'gap' => $gap,
                    'truck_name' => $truck['truck_name'],
                ];
            }
        }

        // No matching truck/slot combinations
        if (empty($busy)) {
            return [
                'data' => $trucks[0] ?: null,
                'index' => 0,
            ];
        }

        $gaps = array_column($busy, 'gap');
        $minGap = min($gaps);
        $minIndex = array_search($minGap, $gaps, true);

        if ($minIndex === false) {
            return null;
        }

        $winner = $busy[$minIndex];


        $cursor = null;
        $index = null;

        foreach ($trucks as $i => $truck) {
            if (($truck['truck_name'] ?? null) === $winner['truck_name']) {
                $cursor = $truck;
                $index = $i;
                break;
            }
        }

        return [
            'data' => $cursor,
            'index' => $index,
        ];

    }



    public function getTrucksLocationAvailability($trucks, $location)
    {
        $availablity = false;

        foreach ($trucks as $truck) {


            if (!isset($truck['truck_capacity']))
                continue;

            if (!$truck['location']) {
                $availablity = true;
                break;
            }


            if ($truck['location'] == $location) {
                $availablity = true;
                break;
            }
        }

        return $availablity;
    }
    private static function fifoGapFromSlots(string $truckName, Carbon $loading_start, array $busyByTruck): ?int
{
    $lastEnd = null;

    foreach ($busyByTruck[$truckName] ?? [] as $iv) {
        // if busy covers loading_start => not free at required time
        if ($iv['start']->lte($loading_start) && $iv['end']->gt($loading_start)) {
            return null;
        }

        // last end before loading_start
        if ($iv['end']->lte($loading_start)) {
            if ($lastEnd === null || $iv['end']->gt($lastEnd)) {
                $lastEnd = $iv['end']->copy();
            }
        }
    }

    // If never used, treat as waiting since shift start (not 1970)
    if ($lastEnd === null) {
        // choose a sane baseline: start of day or shift start if you have it
        $lastEnd = $loading_start->copy()->startOfDay();
    }

    return $lastEnd->diffInMinutes($loading_start);
}

}