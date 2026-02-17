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
            ->orderBy('transit_mixers.truck_capacity', 'desc')
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
        $trucks,
        $truck_cap,
        $loading_start,
        $return_end,
        $location_end_time,
        $restriction_start,
        $restriction_end,
        $location = null,
        $trip,
        $assinedTrucks = array(),
        $slots = array()
    ) {
        $loading_start = $loading_start instanceof Carbon ? $loading_start : Carbon::parse($loading_start);
        $return_end = $return_end instanceof Carbon ? $return_end : Carbon::parse($return_end);

        usort($slots, function ($a, $b) {
            // Ensure 'end' is a Carbon instance
            $aEnd = $a['end'] instanceof Carbon ? $a['end'] : Carbon::parse($a['end']);
            $bEnd = $b['end'] instanceof Carbon ? $b['end'] : Carbon::parse($b['end']);

            // 1. Sort by truck capacity descending
            if ($a['cap'] !== $b['cap']) {
                return $b['cap'] <=> $a['cap'];
            }

            // 2. Sort by truck name ascending
            if ($a['truck_id'] !== $b['truck_id']) {
                return strcmp($a['truck_id'], $b['truck_id']);
            }

            // 3. Sort by end time ascending
            return $aEnd->timestamp <=> $bEnd->timestamp;
        });
        $overlaps = function (Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool {
            return $aStart->lt($bEnd) && $aEnd->gt($bStart);
        };

        // min_date boundary
        $location_end_time = $location_end_time instanceof Carbon ? $location_end_time : Carbon::parse($location_end_time);
        $min_date = $location_end_time->lte($return_end) ? $location_end_time : $return_end;

        // restriction check
        if (isset($restriction_start) && isset($restriction_end)) {
            $rStart = Carbon::parse($restriction_start);
            $rEnd = Carbon::parse($restriction_end);

            if ($loading_start->between($rStart, $rEnd) || $min_date->between($rStart, $rEnd)) {
                return null;
            }
        }

        /**
         * Find last slot end BEFORE loading_start for a truck.
         * If no slot found, fallback to truck free_from.
         */
        $getLastBusyEndBefore = function (array $truck) use ($slots, $loading_start): Carbon {
            $lastEnd = null;

            foreach ($slots as $slot) {
                if (($slot['truck_id'] ?? null) !== $truck['truck_name'])
                    continue;

                $slotEnd = $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']);

                // only consider slots that ended before (or exactly at) loading_start
                if ($slotEnd->lte($loading_start)) {
                    if ($lastEnd === null || $slotEnd->gt($lastEnd)) {
                        $lastEnd = $slotEnd;
                    }
                }
            }

            if ($lastEnd)
                return $lastEnd;

            return $truck['free_from'] instanceof Carbon
                ? $truck['free_from']
                : Carbon::parse($truck['free_from']);
        };

        /**
         * Collect eligible candidates, then pick STRICT FIFO = max gap (loading_start - lastEnd).
         */
        $pickStrictFifo = function (callable $filter) use ($trucks, $truck_cap, $location, $loading_start, $return_end, $min_date, $overlaps, $slots, $getLastBusyEndBefore) {
            $candidates = [];

            foreach ($trucks as $truck_key => $truck) {
                if (!isset($truck['truck_capacity']))
                    continue;

                // custom filter (assigned-first / capacity-match / etc.)
                if (!$filter($truck))
                    continue;

                // location filter
                if (!empty($truck['location']) && $location && $truck['location'] != $location)
                    continue;

                // time window checks
                $freeFrom = $truck['free_from'] instanceof Carbon ? $truck['free_from'] : Carbon::parse($truck['free_from']);
                $freeUpto = $truck['free_upto'] instanceof Carbon ? $truck['free_upto'] : Carbon::parse($truck['free_upto']);

                if ($freeFrom->gt($loading_start))
                    continue;
                if ($freeFrom->gt($min_date))
                    continue;
                if ($freeUpto->lt($loading_start))
                    continue;
                if ($freeUpto->lt($min_date))
                    continue;

                // overlap check with truck slots
                $hasOverlap = false;
                foreach ($slots as $slot) {
                    if (($slot['truck_id'] ?? null) !== $truck['truck_name'])
                        continue;

                    $slotStart = $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']);
                    $slotEnd = $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']);

                    if ($overlaps($loading_start, $return_end, $slotStart, $slotEnd)) {
                        $hasOverlap = true;
                        break;
                    }
                }
                if ($hasOverlap)
                    continue;

                // STRICT FIFO score = waiting gap minutes
                $lastEnd = $getLastBusyEndBefore($truck);
                $gapMin = $lastEnd->diffInMinutes($loading_start, false); // positive if lastEnd < loading_start

                if ($gapMin < 0) {
                    continue;
                }

                $candidates[] = [
                    'data' => $truck,
                    'index' => $truck_key,
                    'gap' => $gapMin,
                    'last_end' => $lastEnd,
                    'free_from' => $freeFrom,
                ];
            }

            if (empty($candidates))
                return null;

            // Pick MAX gap (truck waiting longest). Tie-breakers:
            // 1) older last_end (smaller last_end => waited more in real time)
            // 2) older free_from
            // 3) stable by index
            usort($candidates, function ($a, $b) {
                if ($a['gap'] !== $b['gap'])
                    return $b['gap'] <=> $a['gap']; // DESC gap

                $le = $a['last_end']->timestamp <=> $b['last_end']->timestamp; // ASC last_end
                if ($le !== 0)
                    return $le;

                $ff = $a['free_from']->timestamp <=> $b['free_from']->timestamp; // ASC free_from
                if ($ff !== 0)
                    return $ff;

                return $a['index'] <=> $b['index'];
            });

            return [
                'data' => $candidates[0]['data'],
                'index' => $candidates[0]['index']
            ];
        };

        /**
         * 1) Assigned trucks only (STRICT FIFO among them)
         */
        $result = $pickStrictFifo(function ($truck) use ($assinedTrucks) {
            return in_array($truck['truck_name'], $assinedTrucks);
        });
        if ($result)
            return $result;

        /**
         * 2) Capacity match (STRICT FIFO)
         */
        $result = $pickStrictFifo(function ($truck) use ($truck_cap) {
            return isset($truck['truck_capacity']) && $truck['truck_capacity'] == $truck_cap;
        });
        if ($result)
            return $result;

        /**
         * 3) Any capacity (STRICT FIFO)
         */
        $result = $pickStrictFifo(function ($truck) {
            return true;
        });
        if ($result)
            return $result;

        return null;
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
}