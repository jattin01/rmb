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
                'free_from' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_s)->subDays(1)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $tm->working_hrs_e)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),
                'location' => null,
            );
        }
        return $tms_availabilty;
    }

    public static function getAvailableTrucksNew(
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
        $order_no = null,
        $quantity = null
    ) {
        Log::info('QUantity' . $quantity);

        $location_end_time = $location_end_time instanceof Carbon ? $location_end_time : Carbon::parse($location_end_time);
        $min_date = $location_end_time->lte($return_end) ? $location_end_time : $return_end;

        // restriction window
        if (isset($restriction_start, $restriction_end)) {
            $rStart = Carbon::parse($restriction_start);
            $rEnd = Carbon::parse($restriction_end);

            if ($loading_start->between($rStart, $rEnd) || $min_date->between($rStart, $rEnd)) {
                return null;
            }
        }

        // busy slots map: truck => intervals
        $busyByTruck = [];
        foreach ($slots as $slot) {
            $tid = $slot['truck_id'] ?? null;
            if (!$tid)
                continue;

            $busyByTruck[$tid][] = [
                'start' => $slot['start'] instanceof Carbon ? $slot['start'] : Carbon::parse($slot['start']),
                'end' => $slot['end'] instanceof Carbon ? $slot['end'] : Carbon::parse($slot['end']),
            ];
        }

        $best = null;

        foreach ($trucks as $truck_key => $truck) {

            if (!isset($truck['truck_name'], $truck['truck_capacity']))
                continue;

            // (Optional) if you still want "assigned trucks only first", you can filter outside
            // For now we keep it simple: allow all. If you want strict assigned preference tell me.

            if (!empty($truck['location']) && !empty($location) && $truck['location'] != $location)
                continue;

            // availability windows
            if (isset($truck['free_from']) && Carbon::parse($truck['free_from'])->gte($loading_start))
                continue;
            if (isset($truck['free_from']) && Carbon::parse($truck['free_from'])->gte($min_date))
                continue;

            if (isset($truck['free_upto']) && Carbon::parse($truck['free_upto'])->lte($loading_start))
                continue;
            if (isset($truck['free_upto']) && Carbon::parse($truck['free_upto'])->lte($min_date))
                continue;

            $tName = $truck['truck_name'];

            // overlap check for full trip window [loading_start, return_end)
            $hasOverlap = false;
            foreach ($busyByTruck[$tName] ?? [] as $iv) {
                if ($return_end->gte($iv['start']) && $loading_start->lte($iv['end'])) {
                    $hasOverlap = true;
                    break;
                }
            }
            if ($hasOverlap)
                continue;

            $eliglibleTruck[] = $truck;
        }
        $requiredQty = min($quantity, max(array_column($trucks, 'truck_capacity')));

        $optimalCapacity = ScheduleService::getOptimalTruckCapacity(
            $trucks,
            $requiredQty,
            $order_no,
            $trip
        );

        $eligibleTrucks = array_filter($eliglibleTruck, function ($truck) use ($optimalCapacity) {
            return $truck['truck_capacity'] == $optimalCapacity;
        });

        $eligibleTrucks = array_values($eligibleTrucks);

        if (!empty($eligibleTrucks)) {

            return [
                'data' => $eligibleTrucks[0],
                'index' => 0
            ];
        }

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
        $assignedTrucks = []
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

        $tier = [1 => null, 2 => null, 3 => null, 4=>null];

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

            $isAssigned = in_array($truck['truck_name'], $assignedTrucks);
            $capacityMatches = ($truck_cap === null || (int)$truck['truck_capacity'] === (int)$truck_cap);

            if ($tier[1] === null && $isAssigned && $capacityMatches) {
                $tier[1] = ['data' => $truck, 'index' => $key];
            }
            if ($tier[2] === null && $isAssigned) {
                $tier[2] = ['data' => $truck, 'index' => $key];
            }
           
            if ($tier[3] === null && !$isAssigned && $capacityMatches) {
                $tier[3] = ['data' => $truck, 'index' => $key];
            }

            if ($tier[4] === null && !$isAssigned) {
                $tier[4] = ['data' => $truck, 'index' => $key];
            }
           
            if ($tier[1] && $tier[2] && $tier[3] && $tier[4]) {
                break;
            }
           
        }
         
        return $tier[1] ?? $tier[2] ?? $tier[3] ?? $tier[4];
    }
    public static function getAvailableTrucksNewMy(
        $trucks,
        $truck_cap,
        $loading_start,
        $return_end,
        $location_end_time,
        $restriction_start,
        $restriction_end,
        $location = null,
        $trip = null,
        $assinedTrucks = [],
        $slots = [],
        $order_no = null,
        $quantity = null,
        $scheduleData
    ) {

        $data = null;
        $index = null;

        $loading_start = Carbon::parse($loading_start);
        $return_end = Carbon::parse($return_end);
        $location_end_time = Carbon::parse($location_end_time);

        $min_date = $location_end_time->lte($return_end) ? $location_end_time : $return_end;

        /*
        --------------------------------
        RESTRICTION CHECK
        --------------------------------
        */

        if ($restriction_start && $restriction_end) {

            $rStart = Carbon::parse($restriction_start);
            $rEnd = Carbon::parse($restriction_end);

            if (
                $loading_start->between($rStart, $rEnd) ||
                $min_date->between($rStart, $rEnd)
            ) {
                return null;
            }
        }

        /*
        --------------------------------
        BUILD BUSY SLOTS MAP
        --------------------------------
        */

        $busyByTruck = [];

        foreach ($slots as $slot) {

            $truckId = $slot['truck_id'] ?? null;
            if (!$truckId)
                continue;

            $busyByTruck[$truckId][] = [
                'start' => Carbon::parse($slot['start']),
                'end' => Carbon::parse($slot['end'])
            ];
        }

        /*
        --------------------------------
        COMMON AVAILABILITY CHECK
        --------------------------------
        */

        $isTruckAvailable = function ($truck) use ($loading_start, $return_end, $min_date, $location, $busyByTruck) {

            if (!isset($truck['truck_capacity']))
                return false;

            if ($truck['location'] && $location && $truck['location'] != $location) {
                return false;
            }

            if (Carbon::parse($truck['free_from'])->gte($loading_start))
                return false;

            if (Carbon::parse($truck['free_from'])->gte($min_date))
                return false;

            if (Carbon::parse($truck['free_upto'])->lte($loading_start))
                return false;

            if (Carbon::parse($truck['free_upto'])->lte($min_date))
                return false;

            /*
            SLOT OVERLAP CHECK
            */

            $truckName = $truck['truck_name'];

            foreach ($busyByTruck[$truckName] ?? [] as $slot) {

                if ($return_end->gte($slot['start']) && $loading_start->lte($slot['end'])) {
                    return false;
                }
            }


            return true;
        };
        if ($truck_cap == null && $quantity != null) {

            $requiredQty = $quantity;

            $truck_cap = ScheduleService::getOptimalTruckCapacity(
                $trucks,
                $requiredQty,
                $order_no,
                $trip

            );
        }


        foreach ($trucks as $truck_key => $truck) {

            if (!in_array($truck['truck_name'], $assinedTrucks))
                continue;

            if ($truck_cap && $truck['truck_capacity'] != $truck_cap)
                continue;

            if (!$isTruckAvailable($truck))
                continue;

            $data = $truck;
            $index = $truck_key;
            break;
        }



        if (!$data) {

            foreach ($trucks as $truck_key => $truck) {

                if ($truck['truck_capacity'] != $truck_cap)
                    continue;

                if (!$isTruckAvailable($truck))
                    continue;

                $data = $truck;
                $index = $truck_key;
                break;
            }
        }

        /*
        --------------------------------
        4️⃣ ANY AVAILABLE TRUCK
        --------------------------------
        */

        if (!$data) {

            foreach ($trucks as $truck_key => $truck) {



                if ($truck_cap && $truck['truck_capacity'] > $quantity) {
                    continue;
                }
                if ($quantity <= 8 && $truck['truck_capacity'] !== 8) {
                    continue;
                }


                if (!$isTruckAvailable($truck))
                    continue;


                $data = $truck;
                $index = $truck_key;
                break;
            }
        }
        // return $data ? ['data' => $data, 'index' => $index] : null;
        if ($data) {

            $selectedTruck = $data;

            /*
            --------------------------------
            ONLY APPLY EXTRA IF > 8
            --------------------------------
            */
            if ($selectedTruck['truck_capacity'] > 8) {
                $ReqQuantity = min($selectedTruck['truck_capacity'], $quantity);

                $totalExtra = self::calculateTotalExtraTime(
                    8,
                    $ReqQuantity,
                    $scheduleData->loading_time,
                    $scheduleData->pouring_time
                );
                //dd($totalExtra,$order_no,$trip);

                $newReturnEnd = $return_end->copy()->addMinutes($totalExtra);

                $truckName = $selectedTruck['truck_name'];
                $conflict = false;

                foreach ($busyByTruck[$truckName] ?? [] as $slot) {

                    if (
                        $newReturnEnd->gte($slot['start']) &&
                        $loading_start->lte($slot['end'])
                    ) {
                        $conflict = true;
                        break;
                    }
                }

                /*
                --------------------------------
                IF CONFLICT → USE 8 CAPACITY
                --------------------------------
                */
                if ($conflict) {

                    foreach ($trucks as $truck_key => $truck) {

                        if ($truck['truck_capacity'] != 8)
                            continue;

                        if (!$isTruckAvailable($truck))
                            continue;

                        // ✅ NO extra time here
                        return [
                            'data' => $truck,
                            'index' => $truck_key
                        ];
                    }

                    return null; // no fallback found
                } else {
                    $scheduleData->return_end = $newReturnEnd->copy();
                }

            }
        }
        return $data ? ['data' => $data, 'index' => $index] : null;
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