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
    Carbon $loading_start,
    Carbon $return_end,
    $location_end_time,
    $restriction_start,
    $restriction_end,
    $location = null,
    $trip = null,
    $assinedTrucks = [],
    $slots = [],
    $order_no = null
) {
    $location_end_time = $location_end_time instanceof Carbon ? $location_end_time : Carbon::parse($location_end_time);
    $min_date = $location_end_time->lte($return_end) ? $location_end_time : $return_end;

    // restriction window
    if (isset($restriction_start, $restriction_end)) {
        $rStart = Carbon::parse($restriction_start);
        $rEnd   = Carbon::parse($restriction_end);

        if ($loading_start->between($rStart, $rEnd) || $min_date->between($rStart, $rEnd)) {
            return null;
        }
    }

    // busy slots map: truck => intervals
    $busyByTruck = [];
    foreach ($slots as $slot) {
        $tid = $slot['truck_id'] ?? null;
        if (!$tid) continue;

        $busyByTruck[$tid][] = [
            'start' => $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']),
            'end'   => $slot['end']   instanceof Carbon ? $slot['end']   : Carbon::parse($slot['end']),
        ];
    }

    $best = null;

    foreach ($trucks as $truck_key => $truck) {

        if (!isset($truck['truck_name'], $truck['truck_capacity'])) continue;

        // (Optional) if you still want "assigned trucks only first", you can filter outside
        // For now we keep it simple: allow all. If you want strict assigned preference tell me.

        if (!empty($truck['location']) && !empty($location) && $truck['location'] != $location) continue;

        // availability windows
        if (isset($truck['free_from']) && Carbon::parse($truck['free_from'])->gt($loading_start)) continue;
        if (isset($truck['free_from']) && Carbon::parse($truck['free_from'])->gt($min_date)) continue;

        if (isset($truck['free_upto']) && Carbon::parse($truck['free_upto'])->lt($loading_start)) continue;
        if (isset($truck['free_upto']) && Carbon::parse($truck['free_upto'])->lt($min_date)) continue;

        $tName = $truck['truck_name'];

        // overlap check for full trip window [loading_start, return_end)
        $hasOverlap = false;
        foreach ($busyByTruck[$tName] ?? [] as $iv) {
            if ($return_end->gt($iv['start']) && $loading_start->lt($iv['end'])) {
                $hasOverlap = true;
                break;
            }
        }
        if ($hasOverlap) continue;

        // FIFO waiting gap: use latest end <= loading_start
        $lastEnd = null;
        foreach ($busyByTruck[$tName] ?? [] as $iv) {
            if ($iv['end']->lte($loading_start)) {
                if ($lastEnd === null || $iv['end']->gt($lastEnd)) {
                    $lastEnd = $iv['end']->copy();
                }
            }
        }

        // If no history, do NOT let it dominate FIFO (set gap = 0)
        $gap = $lastEnd ? $lastEnd->diffInMinutes($loading_start) : 0;

        if (
            $best === null ||
            $gap > $best['gap'] ||
            ($gap === $best['gap'] && strcmp((string)$tName, (string)$best['data']['truck_name']) < 0)
        ) {
            $best = [
                'data' => $truck,
                'index' => $truck_key,
                'gap' => $gap,
            ];
        }
    }

    return $best ? ['data' => $best['data'], 'index' => $best['index']] : null;
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