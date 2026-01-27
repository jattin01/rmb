<?php
namespace App\Helpers\V2;

use App\Helpers\ConstantHelper;
use App\Models\TransitMixer;
use Carbon\Carbon;

class TransitMixerHelper {
    

    public  function getTrucksAvailability(int $company_id, string $schedule_date, array $transit_mixer_ids) : array
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

    public static function getAvailableTrucks($trucks, $truck_cap, $loading_start, $return_end, $location_end_time, $restriction_start, $restriction_end, $location = null, $trip, $assinedTrucks = array())
    {

        $data = null;
        $index = null;

        $min_date = $return_end;
        if (Carbon::parse($location_end_time) -> lte(Carbon::parse($return_end))) {
            $min_date = $location_end_time;
        }
        if (isset($restriction_start) && isset($restriction_end)) {
            if ((Carbon::parse($loading_start) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end))) || (Carbon::parse($min_date) -> between(Carbon::parse($restriction_start), Carbon::parse($restriction_end)))) {
                return null;
            }
        }
        foreach ($trucks as $truck_key => $truck) {

            if (!in_array($truck['truck_name'], $assinedTrucks)) {
                continue;
            }

        
            if(!isset($truck['truck_capacity'])) continue;

            if($truck['location'] && $location && $truck['location'] != $location) {
                continue;
            }
        
      // Removed by ANKIT SHARMA ON 11 JUN ,FOR TRUCK MAX UTILIZATION ISSUE       
            // if ($truck['truck_capacity'] != $truck_cap) {
            //     continue ;
            // }
            
            if( Carbon::parse($truck['free_from']) -> gt(Carbon::parse($loading_start)) ) {
                continue;
            }
            if(  Carbon::parse($truck['free_from']) -> gt($min_date) ) {
                continue;
            }
            if(Carbon::parse($truck['free_upto']) -> lt(Carbon::parse($loading_start) ) ) {
                continue;
            } 
            
            if( Carbon::parse($truck['free_upto']) -> lt($min_date)) {
                continue;
            }

            $data = $truck;
            $index = $truck_key;
            break;
        }

        // Step 2: If no assigned truck is available, choose another truck
        if (!isset($data) || !isset($index)) {
            foreach ($trucks as $truck_key => $truck) {

               
                if(!isset($truck['truck_capacity'])) continue;
    
                if ($truck['truck_capacity'] != $truck_cap) {
                    continue ;
                }

                if($truck['location'] && $location && $truck['location'] != $location) {
                    continue;
                }
                
                if( Carbon::parse($truck['free_from']) -> gt(Carbon::parse($loading_start)) ) {
                    continue;
                }
                if(  Carbon::parse($truck['free_from']) -> gt($min_date) ) {
                    continue;
                }
                if(Carbon::parse($truck['free_upto']) -> lt(Carbon::parse($loading_start) ) ) {
                    continue;
                } 
                
                if( Carbon::parse($truck['free_upto']) -> lt($min_date)) {
                    continue;
                }
    
                $data = $truck;
                $index = $truck_key;
                break;
            }
        }

         // Step 2: If no assigned truck is available, choose another truck
         if (!isset($data) || !isset($index)) {
            foreach ($trucks as $truck_key => $truck) {

               
                if(!isset($truck['truck_capacity'])) continue;
    
                if($truck['location'] && $location && $truck['location'] != $location) {
                    continue;
                }
                
                if( Carbon::parse($truck['free_from']) -> gt(Carbon::parse($loading_start)) ) {
                    continue;
                }
                if(  Carbon::parse($truck['free_from']) -> gt($min_date) ) {
                    continue;
                }
                if(Carbon::parse($truck['free_upto']) -> lt(Carbon::parse($loading_start) ) ) {
                    continue;
                } 
                
                if( Carbon::parse($truck['free_upto']) -> lt($min_date)) {
                    continue;
                }
    
                $data = $truck;
                $index = $truck_key;
                break;
            }
        }

        // if($trip == 4) {
        //     dd('Truck', $assinedTrucks, $data, $index, $min_date, $loading_start, $location_end_time);
        // }
        
        if (isset($data) && isset($index)) {
            return ['data' => $data, 'index' => $index];
        } else {
            return null;
        }
    }

    public function getTrucksLocationAvailability($trucks, $location)
    {
        $availablity = false;
        
        foreach ($trucks as $truck) {


            if(!isset($truck['truck_capacity'])) continue;

            if(!$truck['location']) {
                $availablity = true;
                break;
            }  
                

            if($truck['location'] == $location) {
               $availablity = true;
               break;
            }
        }
        
        return $availablity;
    }
}