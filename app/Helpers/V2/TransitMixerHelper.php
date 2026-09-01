<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Lib\Services\ScheduleService;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;



class TransitMixerHelper
{


    public function getTrucksAvailability(int $company_id, string $schedule_date, array $transit_mixer_ids): array
    {
        $tms_availabilty = [];

        $tms = TransitMixer::join("group_companies", function ($join) {
            $join->on("group_companies.id", "=", "transit_mixers.group_company_id");
        })->select('transit_mixers.id', "truck_name", "truck_capacity", "loading_time", "working_hrs_s", "working_hrs_e")
            ->where("group_companies.id", $company_id)
            ->where("transit_mixers.status", ConstantHelper::ACTIVE)
            ->where('transit_mixers.truck_capacity',8)
            ->whereIn("transit_mixers.id", $transit_mixer_ids)
            ->orderBy('transit_mixers.truck_capacity', 'desc')
            ->get();

        foreach ($tms as $tm) {
            $tms_availabilty[] = array(
                'truck_name' => $tm->truck_name,
                'truck_capacity' => $tm->truck_capacity,
                'loading_time' => $tm->loading_time,
                'free_from' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->subDays(2)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
            );
        }
        return $tms_availabilty;
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
        $assignedTrucks = [],
        $scheduleData = null,   // ← added: needed to check plant free window
        $baseLoadingTime = null // ← added: loading time for standard 8 m³ truck
    ) {
        $minDate = Carbon::parse($location_end_time)->lte(Carbon::parse($return_end))
            ? Carbon::parse($location_end_time)
            : Carbon::parse($return_end);

        $loadingStart = Carbon::parse($loading_start);

        if (
            isset($restriction_start, $restriction_end) &&
            (
                $loadingStart->between(Carbon::parse($restriction_start), Carbon::parse($restriction_end)) ||
                $minDate->between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))
            )
        ) {
            return null;
        }

        // ── Helper: check if the assigned batching plant is free ─────────────
        // for a given loading_end (may be extended due to larger truck capacity)
        $plantFreeForLoadingEnd = function (Carbon $loadingEnd) use ($scheduleData, $loadingStart): bool {
            // If no scheduleData or no assigned plant — assume OK (no check possible)
            if (!$scheduleData || !$scheduleData->assigned_plant) {
                return true;
            }

            $plantName = $scheduleData->assigned_plant;

            foreach ($scheduleData->bps_availability as $plant) {
                if ($plant['plant_name'] !== $plantName) {
                    continue;
                }
                $freeFrom = Carbon::parse($plant['free_from']);
                $freeUpto = Carbon::parse($plant['free_upto']);

                // Plant must cover loading_start → loading_end fully
                if ($freeFrom->lte($loadingStart) && $freeUpto->gte($loadingEnd)) {
                    return true;
                }
            }

            // Log::info("[TRUCK_SELECT] Plant {$plantName} NOT free until "
            //     . $loadingEnd->format('H:i') . " — cannot use larger truck");

            return false;
        };

        // ── Calculate loading_end for a given truck capacity ─────────────────
        // loading_time scales proportionally with truck capacity vs base 8 m³
        $loadingEndForCapacity = function (int $capacity) use ($loadingStart, $baseLoadingTime): Carbon {
            $base = $baseLoadingTime ?? 20; // fallback default
            $scaledLoadingTime = (int) round(($capacity / 8) * $base);
            return $loadingStart->copy()->addMinutes($scaledLoadingTime);
        };

        $tier = [
            1 => null,
            2 => null,
            3 => null,
            4 => null,
            5 => null,
            6 => null,
            7 => null,
            8 => null
        ];
        // Tiers 1-4: larger/matched truck with plant check passed
        // Tiers 5-8: fallback to capacity = 8 (standard truck, plant always fits)

        foreach ($trucks as $key => $truck) {

            if (!isset($truck['truck_capacity'])) {
                continue;
            }

            if ($truck['location'] && $location && $truck['location'] !== $location) {
                continue;
            }

            $freeFrom = Carbon::parse($truck['free_from']);
            $freeUpto = Carbon::parse($truck['free_upto']);

            if ($freeFrom->gt($loadingStart) || $freeFrom->gt($minDate)) {
                continue;
            }
            if ($freeUpto->lt($loadingStart) || $freeUpto->lt($minDate)) {
                continue;
            }

            $isAssigned      = in_array($truck['truck_name'], $assignedTrucks);
            $capacityMatches = ($truck_cap === null || (int)$truck['truck_capacity'] === (int)$truck_cap);
            $isStandard      = ((int)$truck['truck_capacity'] === 8);

            // ── Check if plant can handle this truck's loading time ───────────
            $loadingEnd    = $loadingEndForCapacity((int)$truck['truck_capacity']);
            $plantOk       = $plantFreeForLoadingEnd($loadingEnd);

            if ($plantOk) {
                // Plant has room — use full capacity matching tiers
                if ($tier[1] === null && $isAssigned && $capacityMatches) {
                    $tier[1] = ['data' => $truck, 'index' => $key, 'loading_end' => $loadingEnd];
                }
                if ($tier[2] === null && $isAssigned) {
                    $tier[2] = ['data' => $truck, 'index' => $key, 'loading_end' => $loadingEnd];
                }
                if ($tier[3] === null && !$isAssigned && $capacityMatches) {
                    $tier[3] = ['data' => $truck, 'index' => $key, 'loading_end' => $loadingEnd];
                }
                if ($tier[4] === null && !$isAssigned) {
                    $tier[4] = ['data' => $truck, 'index' => $key, 'loading_end' => $loadingEnd];
                }
            } else {
                // Plant cannot handle extended loading — only allow standard 8 m³ trucks
                if ($isStandard) {
                    $standardLoadingEnd = $loadingEndForCapacity(8);
                    if ($tier[5] === null && $isAssigned && $capacityMatches) {
                        $tier[5] = ['data' => $truck, 'index' => $key, 'loading_end' => $standardLoadingEnd];
                    }
                    if ($tier[6] === null && $isAssigned) {
                        $tier[6] = ['data' => $truck, 'index' => $key, 'loading_end' => $standardLoadingEnd];
                    }
                    if ($tier[7] === null && !$isAssigned && $capacityMatches) {
                        $tier[7] = ['data' => $truck, 'index' => $key, 'loading_end' => $standardLoadingEnd];
                    }
                    if ($tier[8] === null && !$isAssigned) {
                        $tier[8] = ['data' => $truck, 'index' => $key, 'loading_end' => $standardLoadingEnd];
                    }
                }
            }

            if ($tier[1] && $tier[2] && $tier[3] && $tier[4]) {
                break;
            }
        }

        $result = $tier[1] ?? $tier[2] ?? $tier[3] ?? $tier[4]
            ?? $tier[5] ?? $tier[6] ?? $tier[7] ?? $tier[8];

        // if ($result) {
        //     Log::info("[TRUCK_SELECT] trip={$trip} "
        //         . "truck={$result['data']['truck_name']} "
        //         . "cap={$result['data']['truck_capacity']} "
        //         . "loading_end={$result['loading_end']->format('H:i')} "
        //         . "plant_checked=" . ($scheduleData?->assigned_plant ?? 'none'));
        // }

        return $result;
    }
    public static function calculateTotalExtraTime(
        $standardCapacity,
        $actualCapacity,
        $standardLoadingTime,
        $standardPouringTime
    ) {
        if ($standardCapacity == 0) {
            return 0;
        }

        // Loading extra
        $loadingExtra = $standardLoadingTime * (($actualCapacity - $standardCapacity) / $standardCapacity);

        // Pouring extra
        $pouringExtra = $standardPouringTime * (($actualCapacity - $standardCapacity) / $standardCapacity);

        // Total extra
        return $loadingExtra + $pouringExtra;
    }
}