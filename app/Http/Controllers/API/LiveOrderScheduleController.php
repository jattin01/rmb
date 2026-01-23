<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\LiveOrderSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class LiveOrderScheduleController extends Controller
{
    public function getLiveOrderTruckLocations(Request $request, String $orderId)
    {
        try {
            $order = LiveOrder::find($orderId);
            $data = LiveOrderSchedule::select('order_id', 'transit_mixer_id', 'driver_id', 'batching_qty', 'planned_travel_start', 'planned_travel_end') -> where([
                ['status', ConstantHelper::ACTIVE],
                ['order_id', $orderId]
            ]) -> whereNotNull('actual_travel_start') -> whereNull('actual_travel_end') -> with('driver_details', function ($query) {
                $query -> select('id','code', 'name', 'email_id', 'phone');
            }) -> with('transit_mixer_detail', function ($query) {
                $query -> select('id', 'truck_name', 'registration_no', 'truck_capacity');
            }) -> get();
            if (isset($data) && count($data) > 0 && isset($order)) {
                $startLocation = CompanyLocation::find($order -> company_location_id);
                $endLocation = CustomerProjectSite::find($order -> site_id);
                foreach ($data as $singleData) {
                    $singleData -> transit_mixer_location = array(
                        'id' => 1,
                        'transit_mixer_id' => $singleData -> transit_mixer_id,
                        'latitude' => $startLocation -> latitude,
                        'longitude' => $startLocation -> longitude
                    );
                }
                return array(
                    'message' => 'Trips retrieved successfully',
                    'data' => array(
                        'trip_trucks' => $data,
                        'start_point' => array(
                            'latitude' => $startLocation ?-> latitude,
                            'longitude' => $startLocation ?-> longitude,
                        ),
                        'end_point' => array(
                            'latitude' => $endLocation ?-> latitude,
                            'longitude' => $endLocation ?-> longitude,
                        ),
                    )
                );
            } else {
                throw new ApiGenericException("Order not found");
            }
            
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
