<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Helpers\LiveOrderReScheduleHelper;
use App\Models\ChatRoom;
use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use App\Models\DriverTransitMixer;
use App\Models\LiveOrder;
use App\Models\LiveOrderPumpSchedule;
use App\Models\LiveOrderSchedule;
use App\Models\TransitMixer;
use App\Services\FirestoreService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\LiveOrderSchedule as Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use View;

class LiveOrderController extends Controller
{
    public function getAssignedResources(Request $request)
    {
        try {
            $trip = LiveOrderSchedule::find($request -> id);
            if (isset($trip)) {
                $trip->temp= $trip->order?->order_temp_control ->first()?true:false;
                return array(
                    'data' => array(
                        'trip' => $trip
                    )
                );
            } else {
                return redirect() -> back() -> with('error', 'Trip not found');
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function assignTrip(Request $request)
    {
        // dd($request->all());
        $validator = (new Validator($request))->assignTrip();
        if ($validator->fails()) {
            return redirect() -> back() -> withInput() -> with(ConstantHelper::WARNING, $validator -> messages() -> first());
        }
        try {
            DB::beginTransaction();
            $trip = LiveOrderSchedule::find($request -> trip_id);
            $tm = TransitMixer::find($request -> transit_mixer_id);
            if (isset($trip))
            {
                $trip -> batching_plant_id = $request -> batching_plant_id ? $request -> batching_plant_id : $trip -> batching_plant_id;
                $trip -> transit_mixer_id = $request -> transit_mixer_id ? $request -> transit_mixer_id : $trip -> transit_mixer_id;
                if (isset($tm)) {
                    $trip -> transit_mixer = $tm -> truck_name;
                }
                $trip -> pump_id = $request -> pump_id ? $request -> pump_id : $trip -> pump_id;
                //Assign to pump trip
                $pumpTrip = LiveOrderPumpSchedule::where('order_id', $trip -> order_id) -> where('trip', $trip -> pump_trip) -> first();
            //    dd($pumpTrip);
                if (isset($pumpTrip)) {
                    if (!isset($pumpTrip -> pump_id)) {
                        $pumpTrip -> pump_id = $request -> pump_id ?? null;
                        $pumpTrip -> save();
                    }
                }
                $trip -> actual_loading_start = Carbon::now() ->addHours(5) -> addMinutes(30);
                $driver = DriverTransitMixer::where([
                    ['transit_mixer_id', $trip -> transit_mixer_id],
                    ['status', ConstantHelper::ACTIVE]
                ]) -> first();
                if (isset($driver)) {
                    $trip -> driver_id = $driver -> driver_id;
                    $trip -> save();
                    $chatRoom = ChatRoom::where([
                        ['project_id', $trip ?-> order ?-> project_id],
                        ['entity_type', ConstantHelper::USER_TYPE_DRIVER],
                        ['entity_id', $driver -> driver_id],
                        ['status', ConstantHelper::ACTIVE],
                    ]) -> first();
                    if (!isset($chatRoom)) {
                        ChatRoom::create([
                            'project_id' => $trip ?-> order ?-> project_id,
                            'entity_id' => $driver -> driver_id,
                            'entity_type' => ConstantHelper::USER_TYPE_DRIVER,
                            'status' => ConstantHelper::ACTIVE
                        ]);
                    }
                    DB::commit();
                    $currentTime = Carbon::now() -> addHours(5) -> addMinutes(30);
                    $delay = Carbon::parse($trip -> planned_loading_end) -> diffInMinutes($currentTime);
                    if($delay){
                       
                     //   $reshceduleOrders = LiveOrderReScheduleHelper::initializeReSchedule($trip, ConstantHelper::BATCHING, $currentTime);

                    }
                    return redirect() -> back() -> with(ConstantHelper::SUCCESS, 'Trip assigned successfully');
                } else {
                    DB::rollBack();
                    return redirect() -> back() -> with(ConstantHelper::WARNING, 'No Driver Assigned for this Transit Mixer');
                }
            } else {
                DB::rollBack();
                return redirect() -> back() -> with(ConstantHelper::WARNING, 'Trip not found');
            }
        } catch(Exception $ex) {
            DB::rollBack();
            return redirect() -> back() -> with('error', $ex -> getMessage());
        }
    }

    public function getOrderDetail(Request $request, String $orderId)
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
            }) -> with('transit_mixer_location', function ($query) {
                $query -> select('id', 'transit_mixer_id', 'latitude', 'longitude');
            }) -> get();
            if (isset($order)) {
                $startLocation = CompanyLocation::find($order -> company_location_id);
                $endLocation = CustomerProjectSite::find($order -> site_id);

                $html = View::make('components.orders.partials.live_order_tracking_details', [
                    'orderDetail' => $order,
                    'trip_trucks' => $data,
                    'start_point' => array(
                        'latitude' => $startLocation ?-> latitude,
                        'longitude' => $startLocation ?-> longitude,
                        'name' => $startLocation ?-> site_name,
                    ),
                    'end_point' => array(
                        'latitude' => $endLocation ?-> latitude,
                        'longitude' => $endLocation ?-> longitude,
                        'name' => $endLocation ?-> name
                    ),

                ]) -> render();

                return response() -> json(['html' => $html]);
            } else {
                    throw new ApiGenericException("Order not found");
                }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage() . $ex -> getLine());
        }
    }
}
