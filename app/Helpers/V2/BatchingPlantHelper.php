<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\BatchingPlant;
use App\Models\SelectedOrder;
use Carbon\Carbon;

class BatchingPlantHelper
{

    public function getMinOrderScheduleTimeCopy(int $company_id, int $user_id, string $shift_start, string $shift_end, string $schedule_date): string
    {
        $start_time = Carbon::parse($schedule_date . ConstantHelper::GROUP_COMP_START_TIME)->format(ConstantHelper::SQL_DATE_TIME);

        $order = SelectedOrder::where("group_company_id", $company_id)->where("user_id", $user_id)
            ->whereBetween("delivery_date", [$shift_start, $shift_end])
            ->whereNull("start_time")->where("selected", true)->orderBy("order_start_time")
            ->first();

        if (isset($order)) {
            $start_time = Carbon::parse($order->order_start_time)->format(ConstantHelper::SQL_DATE_TIME);
        }
        return $start_time;
    }

    public function getBatchingPlantAvailabilityCopy(int $company_id, string $schedule_date, array $batching_plant_ids, string $bp_start_time): array
    {
        $bps_availabilty = [];

        // $bp_start_time = Carbon::parse('2025-03-01 00:00:00');

        $bps = BatchingPlant::join("location_shifts", function ($join) {
            $join->on("location_shifts.group_company_id", "=", "batching_plants.group_company_id")
                ->on("location_shifts.company_location_id", "=", "batching_plants.company_location_id");
        })->leftJoin("company_locations", function ($query) {
            $query->on("company_locations.group_company_id", "=", "location_shifts.group_company_id")
                ->on("company_locations.id", "=", "location_shifts.company_location_id");
        })->select("location_shifts.group_company_id", "location", "plant_name", "capacity", "shift_start", "shift_end", "company_locations.location")
            ->where("location_shifts.group_company_id", $company_id)
            ->whereIn("batching_plants.id", $batching_plant_ids)
            ->get();

        foreach ($bps as $bp) {

            // dd($bp);
            $bps_availabilty[] = array(
                'plant_name' => $bp->plant_name,
                'plant_capacity' => $bp->capacity,
                //'free_from' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->subHours(6)->format(ConstantHelper::SQL_DATE_TIME),
                //'free_upto' => Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addHours(6)->format(ConstantHelper::SQL_DATE_TIME),

                 'free_from' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->subDays(1)->format(ConstantHelper::SQL_DATE_TIME),
                 'free_upto' => Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),

                'location' => $bp?->location,
            );
        }

        // dd($bps_availabilty);
        return $bps_availabilty;
    }

    public static function getMinAvailTimeCopy(array $availability, int $loading_time, mixed $batching_plant, ?int $batching_plant_index, ?Carbon $restriction_start = null, ?Carbon $restriction_end = null)
    {
        // Check if the array is empty
        if (empty($availability)) {
            return null;
        }

        // Initialize the minimum value with the first element's value
        $minValue = $availability[0]['free_from'];
        $bp = $availability[0];
        $bpIndex = 0;

        // Iterate through the array to find the minimum value
        foreach ($availability as $key => $item) {
            $freeFrom = Carbon::parse($item['free_from']);
            $freeUpto = Carbon::parse($item['free_upto']);
            if ($freeUpto->gte($freeFrom) && $freeUpto->diffInMinutes($freeFrom) >= $loading_time) {
                if (!(isset($restriction_start) && isset($restriction_end) && $freeFrom->between($restriction_start, $restriction_end))) {
                    if ($freeFrom->lte(Carbon::parse($minValue))) {
                        $minValue = $freeFrom;
                        $bp = $item;
                        $bpIndex = $key;
                    }
                }
            }
        }
        $batching_plant = $bp;
        $batching_plant_index = $bpIndex;
        return $minValue;
    }

  public static function getAvailableBatchingPlants2(
    $batching_plants,
    $location,
    $loading_start,
    $loading_end,
    $restriction_start,
    $restriction_end,
    $assignedPlants,
    $assignedPlant = null,
) {
    if (
        isset($restriction_start, $restriction_end) &&
        Carbon::parse($loading_start)->between(
            Carbon::parse($restriction_start),
            Carbon::parse($restriction_end)
        )
    ) {
        return null;
    }

    $loadingStart = Carbon::parse($loading_start);
    $loadingEnd   = Carbon::parse($loading_end);
   
    // ── Fixed plant — only return that exact plant ────────────────────────
    if ($assignedPlant !== null) {
        foreach ($batching_plants as $key => $plant) {
            if ($plant['plant_name'] !== $assignedPlant) continue;
            if ($plant['location']   !== $location)      continue;
            if (Carbon::parse($plant['free_from'])->gt($loadingStart)) continue;
            if (Carbon::parse($plant['free_upto'])->lt($loadingEnd))   continue;
            return ['data' => $plant, 'index' => $key];
        }
        return null;
    }

    // ── No fixed plant — collect all available plants, apply FIFO ────────
    // FIFO = plant whose free_from is earliest goes first (it was freed longest ago)
    $available = [];

    foreach ($batching_plants as $key => $plant) {
        if ($plant['location'] !== $location)                          continue;
        if (Carbon::parse($plant['free_from'])->gt($loadingStart))    continue;
        if (Carbon::parse($plant['free_upto'])->lt($loadingEnd))      continue;

        $available[] = [
            'data'      => $plant,
            'index'     => $key,
            'free_from' => Carbon::parse($plant['free_from'])->timestamp,
            'preferred' => in_array($plant['plant_name'], $assignedPlants),
        ];
    }

    if (empty($available)) {
        return null;
    }

    // Sort: preferred (already used by this order) first,
    // then by free_from ascending (FIFO — earliest freed plant first)
    usort($available, function ($a, $b) {
        // Preferred plant comes first
        if ($a['preferred'] !== $b['preferred']) {
            return $b['preferred'] <=> $a['preferred']; // true (1) before false (0)
        }
        // Among equals — earliest free_from wins (FIFO)
        return $a['free_from'] <=> $b['free_from'];
    });

    $best = $available[0];
    return ['data' => $best['data'], 'index' => $best['index']];
}
public static function getAvailableBatchingPlants(
    $batching_plants,
    $location,
    $loading_start,
    $loading_end,
    $restriction_start,
    $restriction_end,
    $assignedPlants,
    $assignedPlant = null,
    $plantBusySlots = [],
) {
    // Restriction window check
    if (
        isset($restriction_start, $restriction_end) &&
        Carbon::parse($loading_start)->between(
            Carbon::parse($restriction_start),
            Carbon::parse($restriction_end)
        )
    ) {
        return null;
    }

    $loadingStart = Carbon::parse($loading_start);
    $loadingEnd   = Carbon::parse($loading_end);

    // ── Helper: check if loading window overlaps any busy slot for a given plant ──
    $hasPlantConflict = function (string $plantName) use ($plantBusySlots, $loadingStart, $loadingEnd): bool {
        foreach ($plantBusySlots as $slot) {
            if (($slot['plant_id'] ?? null) !== $plantName) continue;

            $slotStart = $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']);
            $slotEnd   = $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']);

            // Overlap: loadingStart < slotEnd AND loadingEnd > slotStart
            if ($loadingStart->lt($slotEnd) && $loadingEnd->gt($slotStart)) {
                return true; // conflict found
            }
        }
        return false;
    };

    // ── If order already has a fixed plant — ONLY return that plant's earliest-free slot ──
    if ($assignedPlant !== null) {
        $assignedCandidates = [];

        foreach ($batching_plants as $key => $plant) {
            if ($plant['plant_name'] !== $assignedPlant) continue;
            if ($plant['location']   !== $location)      continue;
            if (Carbon::parse($plant['free_from'])->gt($loadingStart)) continue;
            if (Carbon::parse($plant['free_upto'])->lt($loadingEnd))   continue;

            // ── NEW: Also check plant_busy_slots for conflicts ──
            if (!empty($plantBusySlots) && $hasPlantConflict($plant['plant_name'])) {
                continue;
            }

            $assignedCandidates[] = [
                'data'      => $plant,
                'index'     => $key,
                'free_from' => Carbon::parse($plant['free_from'])->timestamp,
            ];
        }

        if (empty($assignedCandidates)) {
            return null;
        }

        // FIFO — earliest free_from wins (the slot that has been idle longest)
        usort($assignedCandidates, fn($a, $b) => $a['free_from'] <=> $b['free_from']);

        return [
            'data'  => $assignedCandidates[0]['data'],
            'index' => $assignedCandidates[0]['index'],
        ];
    }

    // ── No fixed plant yet — collect every eligible slot, then FIFO pick ──
    $preferred = []; // plants already assigned elsewhere (reuse for consistency)
    $fallback  = []; // never-used plants

    foreach ($batching_plants as $key => $plant) {
        if ($plant['location'] !== $location) continue;
        if (Carbon::parse($plant['free_from'])->gt($loadingStart)) continue;
        if (Carbon::parse($plant['free_upto'])->lt($loadingEnd))   continue;

        // ── NEW: Also check plant_busy_slots for conflicts ──
        if (!empty($plantBusySlots) && $hasPlantConflict($plant['plant_name'])) {
            continue;
        }

        $row = [
            'data'      => $plant,
            'index'     => $key,
            'free_from' => Carbon::parse($plant['free_from'])->timestamp,
        ];

        if (in_array($plant['plant_name'], $assignedPlants)) {
            $preferred[] = $row;
        } else {
            $fallback[] = $row;
        }
    }

    // Sort both pools by earliest free_from (FIFO)
    usort($preferred, fn($a, $b) => $a['free_from'] <=> $b['free_from']);
    usort($fallback,  fn($a, $b) => $a['free_from'] <=> $b['free_from']);

    $winner = $preferred[0] ?? $fallback[0] ?? null;

    if (!$winner) return null;

    return [
        'data'  => $winner['data'],
        'index' => $winner['index'],
    ];
}

    public static function getAvailableBatchingPlantsNew(
        $batching_plants,
        $company,
        $location,
        $loading_start,
        $loading_end,
        $restriction_start,
        $restriction_end,
        $trip,
        $assignedPlants,
        $orderNo,
        array &$slots = [],
        $plant_name = null  // ✅ pass-by-ref slots
    ) {
        // Restriction window
        // if (isset($restriction_start) && isset($restriction_end)) {
        //     dd($restriction_start,$restriction_end);
        //     $rs = Carbon::parse($restriction_start);
        //     $re = Carbon::parse($restriction_end);
        //     $ls = Carbon::parse($loading_start);
        //     $le = Carbon::parse($loading_end);

        //     // if ANY part falls in restriction
        //     if ($ls->between($rs, $re) || $le->between($rs, $re) || ($ls->lte($rs) && $le->gte($re))) {
        //         return null;
        //     }
        // }

        $startNeed = Carbon::parse($loading_start);
        $endNeed = Carbon::parse($loading_end);

        // overlap helper
        $overlaps = function (Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool {
            // overlap if aStart < bEnd AND aEnd > bStart
            return $aStart->lte($bEnd) && $aEnd->gte($bStart);
        };

        $canUsePlant = function ($plant) use ($location, $startNeed, $endNeed, &$slots, $overlaps) {
            if (!isset($plant['plant_name'], $plant['location'], $plant['free_from'], $plant['free_upto'])) {
                return [false, null, null];
            }

            // location must match
            if ((string) $plant['location'] !== (string) $location) {
                return [false, null, null];
            }

            $freeFrom = Carbon::parse($plant['free_from']);
            $freeUpto = Carbon::parse($plant['free_upto']);

            // availability must cover [startNeed..endNeed]
            if ($freeFrom->gte($startNeed) || $freeUpto->lte($endNeed)) {
                return [false, $freeFrom, $freeUpto];
            }

            // slots overlap check (same plant_name)
            foreach ($slots as $slot) {
                if (($slot['plant_id'] ?? null) !== $plant['plant_name'])
                    continue;

                $slotStart = $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']);
                $slotEnd = $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']);

                if ($overlaps($startNeed, $endNeed, $slotStart, $slotEnd)) {
                    return [false, $freeFrom, $freeUpto];
                }
            }

            return [true, $freeFrom, $freeUpto];
        };

        $plants = collect($batching_plants);

        // ✅ 1) Prefer already assigned plants (reuse if free)
        if (!empty($assignedPlants)) {
            foreach ($assignedPlants as $preferredPlantName) {
                $plant = $plants->firstWhere('plant_name', $preferredPlantName);
                if (!$plant)
                    continue;

                [$ok, $freeFrom, $freeUpto] = $canUsePlant($plant);
                if ($ok) {
                    // add slot AFTER selection
                    return [
                        'data' => $plant,
                        'index' => $plants->search(fn($x) => ($x['plant_name'] ?? null) === $plant['plant_name']),
                    ];
                }
            }
        }

        $candidates = [];

        foreach ($plants as $idx => $plant) {
            [$ok, $freeFrom, $freeUpto] = $canUsePlant($plant);
            if (!$ok)
                continue;

            // FIFO scoring:
            // 1) earliest free_from
            // 2) smallest idle gap between free_from and startNeed
            $idleGap = abs($freeFrom->diffInMinutes($startNeed, false));

            $candidates[] = [
                'data' => $plant,
                'index' => $idx,
                'score_free_from' => $freeFrom->timestamp,
                'score_idle_gap' => $idleGap,
                'score_name' => (string) ($plant['plant_name'] ?? ''),
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, function ($a, $b) {
            if ($a['score_free_from'] !== $b['score_free_from']) {
                return $a['score_free_from'] <=> $b['score_free_from'];
            }
            if ($a['score_idle_gap'] !== $b['score_idle_gap']) {
                return $a['score_idle_gap'] <=> $b['score_idle_gap'];
            }
            return strcmp($a['score_name'], $b['score_name']);
        });
        if ($plant_name) {
            $targetPlant = $plant_name; // plant you want to allow

            $candidates = array_filter($candidates, function ($candidate) use ($targetPlant) {
                return $candidate['data']['plant_name'] === $targetPlant;
            });

            if (empty($candidates)) {
                return null;
            }

            usort($candidates, function ($a, $b) {
                if ($a['score_free_from'] !== $b['score_free_from']) {
                    return $a['score_free_from'] <=> $b['score_free_from'];
                }
                if ($a['score_idle_gap'] !== $b['score_idle_gap']) {
                    return $a['score_idle_gap'] <=> $b['score_idle_gap'];
                }
                return strcmp($a['score_name'], $b['score_name']);
            });
        }
        $winner = $candidates[0]['data'];




        return [
            'data' => $winner,
            'index' => $candidates[0]['index'],
        ];
    }


    public static function getAvailableBatchingPlantsOld($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $trip, $assinedPlants)
    {

        $plant_name = null;

        if (isset($restriction_start) && isset($restriction_end)) {
            if (Carbon::parse($loading_start)->between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) {
                return null;
            }
        }
        $data = null;
        $index = null;

        $data_new = null;
        $index_new = null;
        if ($trip == 6) {
            // dd($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $trip);
        }
        foreach ($batching_plants as $batching_plant_key => $batching_plant) {
            if ($batching_plant['location'] !== $location) {
                continue;
            }

            if (Carbon::parse($batching_plant['free_from'])->gte(Carbon::parse($loading_start))) {
                continue;
            }


            if (Carbon::parse($batching_plant['free_upto'])->lte(Carbon::parse($loading_end))) {
                continue;
            }

            if (isset($plant_name) && $batching_plant['plant_name'] != $plant_name) {
                continue;
            }


            $data = $batching_plant;
            $index = $batching_plant_key;
            break;
        }

        // if($trip == 2 ) {
        //     dd($data, $index);
        // }

        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }

}