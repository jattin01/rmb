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
    /**
     * One Google Distance Matrix request. Retries short timeouts instead of
     * waiting 2 minutes once, and returns null when Google can't be reached
     * so a slow network never aborts the whole schedule.
     */
    private static function distanceMatrix(array $queryParams): ?array
    {
        $apiURL = config('app.google_maps_api_base_url') . '/maps/api/distancematrix/json';
        try {
            $response = Http::connectTimeout(10)
                ->timeout(20)
                ->retry(3, 1000, null, false)
                ->get($apiURL, $queryParams);
        } catch (\Throwable $e) {
            // Log the points only: the exception message contains the URL with the API key.
            Log::warning('[GOOGLE_MAPS] distance request failed after 3 tries ('
                . $queryParams['origins'] . ' -> ' . $queryParams['destinations'] . '): '
                . get_class($e));
            return null;
        }

        if (!$response->successful()) {
            Log::warning('[GOOGLE_MAPS] distance request returned HTTP ' . $response->status()
                . ' (' . $queryParams['origins'] . ' -> ' . $queryParams['destinations'] . ')');
            return null;
        }

        return $response->json();
    }

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
        $responseJson = self::distanceMatrix($queryParams);
        if ($responseJson !== null) {
            if (($responseJson['status'] ?? null) == 'OK') {
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
        // Callers read ['rows'][0]['elements'][0] behind isset(), so an empty
        // result on a Google failure is handled like "no distance found".
        return self::distanceMatrix($queryParams) ?? [];
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
            $data = self::distanceMatrix($queryParams);
            if($data){
                //need to compare all location and finalize least distance location as $batching

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

        if ($batching === null) {
            // Google gave no distance for any plant: keep the order's current plant
            // if it is still allowed, otherwise use the first allowed plant.
            $locations = collect($locations)->values();
            $fallback = $locations->contains($order->location) ? $order->location : $locations->first();
            $batching = CompanyLocation::where('location', '=', $fallback)->first();
            Log::warning("[GOOGLE_MAPS] order {$order->order_no}: no distances from Google, "
                . "using plant '{$fallback}' and the stored travel times");
        }

        $order->location = $batching->location;
        $order->company_location_id = $batching->id;

        // Keep the stored travel times when Google doesn't answer.
        $travelToSiteDistance =CustomerProjectSiteHelper::assignDistance($batching->id,$order->site_id,'site');
        if (isset($travelToSiteDistance['rows'][0]['elements'][0]['duration']['value'])) {
            $durationInSec = $travelToSiteDistance['rows'][0]['elements'][0]['duration']['value'];
            $order->travel_to_site = intval(round($durationInSec / 60));
        }

        $travelToPlantDistance = CustomerProjectSiteHelper::assignDistance($batching->id, $order->site_id, 'plant');
        if (isset($travelToPlantDistance['rows'][0]['elements'][0]['duration']['value'])) {
            $durationInSec = $travelToPlantDistance['rows'][0]['elements'][0]['duration']['value'];
            $order->return_to_plant = intval(round($durationInSec / 60, 0));
        }

        $order->save();
        return $batching;

    }
}
