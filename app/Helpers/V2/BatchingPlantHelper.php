<?php

namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\BatchingPlant;
use App\Models\SelectedOrder;
use Carbon\Carbon;

class BatchingPlantHelper {
    
    public function getMinOrderScheduleTimeCopy(int $company_id, int $user_id, string $shift_start, string $shift_end, string $schedule_date) : string
    {
        $start_time = Carbon::parse($schedule_date . ConstantHelper::GROUP_COMP_START_TIME) -> format(ConstantHelper::SQL_DATE_TIME);

        $order = SelectedOrder::where("group_company_id", $company_id) -> where("user_id", $user_id)
        -> whereBetween("delivery_date",  [$shift_start, $shift_end])
        ->whereNull("start_time") -> where("selected", true) -> orderBy("order_start_time")
        ->first();

        if (isset($order)) {
            $start_time = Carbon::parse($order -> order_start_time) -> format(ConstantHelper::SQL_DATE_TIME);
        }
        return $start_time;
    }

    public  function getBatchingPlantAvailabilityCopy(int $company_id, string $schedule_date, array $batching_plant_ids, string $bp_start_time) : array
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
                'free_from' => Carbon::parse($schedule_date . ' ' . $bp->shift_start)->format(ConstantHelper::SQL_DATE_TIME),
                'free_upto' => Carbon::parse($schedule_date . ' ' . $bp->shift_end)->addDay()->format(ConstantHelper::SQL_DATE_TIME),

                // 'free_from' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_s)->subDays(1)->format(ConstantHelper::SQL_DATE_TIME),
                // 'free_upto' => Carbon::parse($schedule_date . ' ' . $p->working_hrs_e)->addDays(2)->format(ConstantHelper::SQL_DATE_TIME),
               
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
            if ($freeUpto -> gt($freeFrom) && $freeUpto -> diffInMinutes($freeFrom) >= $loading_time)
            {
                if (!(isset($restriction_start) && isset($restriction_end) && $freeFrom -> between($restriction_start, $restriction_end))) {
                    if ($freeFrom ->lt(Carbon::parse($minValue))) {
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


    public static function getAvailableBatchingPlants($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $trip, $assignedPlants, $orderNo)
    {
       
           
           // if($orderNo == 3612714 && $trip > 15){
           //      dd($loading_start);
           //  } 
      // dd($orderNo);
        // Check if the loading time falls within the restriction period
        if (isset($restriction_start) && isset($restriction_end)) {
            if (Carbon::parse($loading_start)->between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) {
                return null;
            }
        }
         
        $data = null;
        $index = null;

        // Step 1: Prioritize assigned plants
        foreach ($batching_plants as $batching_plant_key => $batching_plant) {
            // Skip plants that are not in the assigned plants list
            if (!in_array($batching_plant['plant_name'], $assignedPlants)) {
                // if($trip == 3 && $orderNo == '11343') echo ($batching_plant['plant_name'] . 'not in assigned plants');
                continue;
            }


            // Check if the plant is in the same location
            if ($batching_plant['location'] !== $location) {
                // if($trip == 3 && $orderNo == '11343') echo ($batching_plant['plant_name']. 'not in assigned location');

                continue;
            }

            // Check if the plant is free during the required time
            if (Carbon::parse($batching_plant['free_from'])->gt(Carbon::parse($loading_start))) {
                // if($trip == 3 && $orderNo == '11343') echo ($batching_plant['plant_name']. 'not free from');

                continue;
            }

            if (Carbon::parse($batching_plant['free_upto'])->lt(Carbon::parse($loading_end))) {
                // if($trip == 3 && $orderNo == '11343') echo ( $batching_plant['plant_name'].'not free upto');
                continue;
            }
            
        
            // Assign the batching plant
            $data = $batching_plant;
            $index = $batching_plant_key;
            break;
        }

        // dd($location, $batching_plants);

        
        // if($trip == 6) {
        // Step 2: If no assigned plant is available, choose another plant
        if (!isset($data) || !isset($index)) {
            foreach ($batching_plants as $batching_plant_key => $batching_plant) {
                // Check if the plant is in the same location
                if ($batching_plant['location'] !== $location) {
                    // if($trip == 3 && $orderNo == '11343') echo ($batching_plant['plant_name']. 'not in assigned location');

                    continue;
                }

                // Check if the plant is free during the required time
                if (Carbon::parse($batching_plant['free_from'])->gt(Carbon::parse($loading_start))) {
                    // if($trip == 3 && $orderNo == '11343') echo ($batching_plant['plant_name']. 'not free from');

                    continue;
                }

                if (Carbon::parse($batching_plant['free_upto'])->lt(Carbon::parse($loading_end))) {
                    // if($trip == 3 && $orderNo == '11343') echo ( $batching_plant['plant_name'].'not free upto');

                    continue;
                }

    
                // Assign the batching plant
                $data = $batching_plant;
                $index = $batching_plant_key;
                break;
            }
        }

        // if($trip == 3 && $orderNo == '11343') {
        //     dd( $loading_start, $loading_end, $data, $index, $batching_plants, $assignedPlants);
        // }
       
        // Return the selected batching plant or null if none is available
        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }


    public static function getAvailableBatchingPlantsOld($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end,  $trip, $assinedPlants)
    {

        $plant_name = null;
        
        if (isset($restriction_start) && isset($restriction_end)) {
            if (Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) {
                return null;
            }
        }
        $data = null;
        $index = null;

        $data_new = null;
        $index_new = null;
        if($trip == 6) {
            // dd($batching_plants, $company, $location, $loading_start, $loading_end, $restriction_start, $restriction_end, $trip);
        }
        foreach ($batching_plants as $batching_plant_key => $batching_plant) {
            if ($batching_plant['location'] !== $location ) {
                continue;
            }

            if( Carbon::parse($batching_plant['free_from']) -> gt(Carbon::parse($loading_start))) {
                continue;
            }
            

            if( Carbon::parse($batching_plant['free_upto']) -> lt(Carbon::parse($loading_end))) {
                continue;
            }

            if (isset($plant_name) && $batching_plant['plant_name'] != $plant_name) {
                continue;
            }


            $data =  $batching_plant;
            $index =  $batching_plant_key;
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