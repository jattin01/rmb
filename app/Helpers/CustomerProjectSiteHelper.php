<?php

namespace App\Helpers;
use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CustomerProjectSiteHelper
{
    /* Function to assign the closest location of company to customer's project site*/
    public static function assignServiceLocation(string $siteLat, string $siteLng, Collection $companyLocations) : null|int
    {
        $apiURL = config('app.google_maps_api_base_url') . '/maps/api/distancematrix/json';
        $queryParams = [
            'key' => config('app.google_map_key'),
            'origins' => $siteLat . "," . $siteLng,
            'destinations' => ''
        ];
        $companyLocationId = $companyLocations -> first() -> id;
        $lowestValue = ConstantHelper::MAX_DISTANCE; //Assign max possible distance
        foreach ($companyLocations as $locKey => $loc) {
            $lat = (string) $loc -> latitude;
            $lng = (string) $loc -> longitude;
            $queryParams['destinations'] .= ($lat . "," . $lng) . (($locKey === count($companyLocations) - 1) ? "" : "|");
        }
        $response = Http::get($apiURL, $queryParams);
        if ($response->successful()) {
            $responseJson = $response -> json();
            if ($responseJson['status'] == 'OK') {
                foreach ($responseJson['rows'] as $row) {
                    foreach ($row['elements'] as $rowElementKey => $rowElement) {
                        if ($rowElement['status'] === "OK" && isset($rowElement['distance']) && isset($rowElement['distance']['value'])) {
                            //If Current Location distance is less, update location Id
                            if ($lowestValue > $rowElement['distance']['value']) {
                                $lowestValue = $rowElement['distance']['value'];
                                $companyLocationId = $companyLocations -> values() -> get($rowElementKey) ?-> id;
                            }
                        }
                    }
                }
            }
        }
        return $companyLocationId;
    }
    public static function assignDistance(int $companyLocationId,int $customerProjectSiteId, $type)

    {

        $companyLocation =CompanyLocation::find($companyLocationId);
        $customerProjectSite =CustomerProjectSite::find($customerProjectSiteId);
        $apiURL = config('app.google_maps_api_base_url') . '/maps/api/distancematrix/json';
        if($type == 'site'){
            $queryParams = [
                'key' => config('app.google_map_key'),
                'origins' => $customerProjectSite->latitude . "," . $customerProjectSite->longitude,
                'destinations' => $companyLocation->latitude . "," . $companyLocation->longitude,
            ];
        }

        if($type == 'plant'){
            $queryParams = [
                'key' => config('app.google_map_key'),
                'origins' => $companyLocation->latitude . "," . $companyLocation->longitude,
                'destinations' => $customerProjectSite->latitude . "," . $customerProjectSite->longitude,
            ];
        }
        // $companyLocationId = $companyLocations -> first() -> id;
        // $lowestValue = ConstantHelper::MAX_DISTANCE; //Assign max possible distance
        // foreach ($companyLocations as $locKey => $loc) {
        //     $lat = (string) $loc -> latitude;
        //     $lng = (string) $loc -> longitude;
        //     $queryParams['destinations'] .= ($lat . "," . $lng) . (($locKey === count($companyLocations) - 1) ? "" : "|");
        // }
        $response = Http::get($apiURL, $queryParams);
        // if ($response->successful()) {
        //     $responseJson = $response -> json();
        //     if ($responseJson['status'] == 'OK') {
        //         foreach ($responseJson['rows'] as $row) {
        //             foreach ($row['elements'] as $rowElementKey => $rowElement) {
        //                 if ($rowElement['status'] === "OK" && isset($rowElement['distance']) && isset($rowElement['distance']['value'])) {
        //                     //If Current Location distance is less, update location Id
        //                     if ($lowestValue > $rowElement['distance']['value']) {
        //                         $lowestValue = $rowElement['distance']['value'];
        //                         $companyLocationId = $companyLocations -> values() -> get($rowElementKey) ?-> id;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }
        return   $response -> json();

    }

    public static function assignNewBatchingPlant($order,$locations)

    {
        $batching = NULL;
        $minDistance = 200000;

        foreach($locations as $location){

            $companyLocation =CompanyLocation::where('location','=',$location)->first();
            $customerProjectSite =CustomerProjectSite::find($order->site_id);
            
            $apiURL = config('app.google_maps_api_base_url') . '/maps/api/distancematrix/json';

            $queryParams = [
                'key' => config('app.google_map_key'),
                'origins' => $companyLocation->latitude . "," . $companyLocation->longitude,
                'destinations' => $customerProjectSite->latitude . "," . $customerProjectSite->longitude,
            ];
            $response = Http::get($apiURL, $queryParams);
            if($response){
                // dd($response->json());
                //need to compare all location and finalize least distance location as $batching 

                $data = $response->json();

                // Check API response validity and parse distance (in meters)
                if (
                    isset($data['rows'][0]['elements'][0]['distance']['value'])
                    && $data['rows'][0]['elements'][0]['status'] == 'OK'
                ) {

                    $distance = $data['rows'][0]['elements'][0]['distance']['value'];
                    // dd($distance,$minDistance);
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $batching = $companyLocation;
                        // dd($batching);
                    }
                }
            }
            
        }
        $order->location = $batching->location;
        $order->company_location_id = $batching->id;

        $travelToSiteDistance =CustomerProjectSiteHelper::assignDistance($batching->id,$order->site_id,'site');
        $durationInSec = $travelToSiteDistance['rows'][0]['elements'][0]['duration']['value'];
        $durationInMinutes =  round($durationInSec / 60);
        $order->travel_to_site = intval($durationInMinutes);

        $travelToPlantDistance = CustomerProjectSiteHelper::assignDistance($batching->id, $order->site_id, 'plant');
        $durationInSec = $travelToPlantDistance['rows'][0]['elements'][0]['duration']['value'];
        $durationInMinutes = round($durationInSec / 60, 0);
        $order->return_to_plant = intval($durationInMinutes);

        $order->save();
        return $batching;

    }
}
