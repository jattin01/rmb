<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\OrderScheduleHelper;
use App\Helpers\LiveOrderReScheduleHelper;
use App\Helpers\FirestoreHelper;
use App\Helpers\LiveScheduleHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\CompanyLocation;
use App\Models\CustomerProjectSite;
use App\Models\LiveOrder;
use App\Models\LiveOrderSchedule;
use App\Models\OrderSchedule;
use App\Models\TransitMixer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\Driver as Validator;
use App\Models\LiveOrderPumpSchedule;
use App\Models\Pump;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    public function getDashboardData(Request $request)
    {
        try {
            $transitMixer = TransitMixer::select("id", "truck_name", "registration_no", "registration_expiry", "truck_capacity", "description") -> find($request -> transit_mixer_id);
            $liveDeliveries = LiveOrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereDoesntHave('rejections') -> whereNotNull('actual_loading_start') -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "site_id", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> get();
            $processedLiveDeliveries = array();
            foreach ($liveDeliveries as &$liveDelivery) {
                $liveDelivery -> current_activity = $liveDelivery -> getCurrentActivity();
                $liveDelivery -> batching_plant_name = $liveDelivery -> batching_plant_detail ?-> plant_name;
                $liveDelivery -> order -> group_company_name = $liveDelivery -> order -> group_company_name();
                $liveDelivery -> order -> company_location_name = $liveDelivery -> order -> company_location_name();
                $liveDelivery -> order -> customer_company_name = $liveDelivery -> order -> customer_company_name();
                $liveDelivery -> order -> start_point = array(
                    'latitude' => $liveDelivery -> order -> company_location ?-> latitude,
                    'longitude' => $liveDelivery -> order -> company_location ?-> longitude,
                );
                $liveDelivery -> order -> end_point = array(
                    'latitude' => $liveDelivery -> order -> customer_site ?-> latitude,
                    'longitude' => $liveDelivery -> order -> customer_site ?-> longitude,
                );
                $processedLiveDeliveries[] = $liveDelivery -> only(["id", "order_id", "schedule_date", "order_no", "location", "trip", "batching_qty", "current_activity", "order", "batching_plant_name", "driver_id", "planned_travel_end", "planned_return_end"]);
            }
            $liveDeliveriesQuery = LiveOrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereNotNull('actual_delivery_start');
            $lastSixMonthDeliveries = $liveDeliveriesQuery -> where('actual_delivery_start', '>=', Carbon::now()->subMonths(6))->get();
            $thisMonthDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();;
            $thisWeekDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();;
            $todayDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
            return array(
                'message' => 'Details Retrieved successfully',
                'data' => array(
                    'truck_details' => $transitMixer,
                    'live_deliveries' => $processedLiveDeliveries,
                    'statistics' => array(
                        'six_month' => count($lastSixMonthDeliveries),
                        'this_month' => count($thisMonthDeliveries),
                        'this_week' => count($thisWeekDeliveries),
                        'today' => count($todayDeliveries)
                    )
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }





    public function getTripDetails(Request $request, String $id)
    {
        try {
            $tripDetails = LiveOrderSchedule::where([
                ['id', $id],
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE],
            ]) -> with('rejections') -> whereNotNull('actual_loading_start') -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id", "site_id",) -> with('customer_site') -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> first();

            $activitiesList = array();

            if (isset($tripDetails)) {
                $rejection = $tripDetails -> rejections -> first();
                foreach (ConstantHelper::LIVE_TRIP_ACTIVITIES as $key => $activity) {
                    $latitude = null;
                    $longitude = null;
                    $startPointLat = $tripDetails -> order ?-> company_location ?-> latitude;
                    $startPointLng = $tripDetails -> order ?-> company_location ?-> longitude;
                    $endPointLat = $tripDetails -> order ?-> customer_site ?-> latitude;
                    $endPointLng = $tripDetails -> order ?-> customer_site ?-> longitude;
                    if ($activity['map']) {
                        if ($activity['name'] === ConstantHelper::ON_SITE_TRAVEL) {
                            $latitude = $tripDetails -> order ?-> customer_site ?-> latitude;
                            $longitude = $tripDetails -> order ?-> customer_site ?-> longitude;
                        } else if ($activity['name'] === ConstantHelper::RETURN) {
                            $latitude = $tripDetails -> order ?-> company_location ?-> latitude;
                            $longitude = $tripDetails -> order ?-> company_location ?-> longitude;
                        } else {
                            $latitude = null;
                            $longitude = null;
                        }
                    }
                    $deviation = 0;
                    if (isset($tripDetails -> {"actual_{$activity['key']}_start"})) {
                        $deviation = Carbon::parse($tripDetails -> {"planned_{$activity['key']}_end"}) -> diffInMinutes(Carbon::parse($tripDetails -> {"actual_{$activity['key']}_end"}), false);
                    }
                    $rejectedStatus = false;
                    //Disable further steps if trip quantity is rejected
                    if (isset($rejection)) {
                        //Disable till last
                        if ($rejection -> activity === ConstantHelper::INTERNAL_QC && $activity['name'] !== ConstantHelper::BATCHING) {
                            $rejectedStatus = true;
                        } else if ($rejection -> activity === ConstantHelper::ON_SITE_INSP && !in_array($activity['name'], [
                            ConstantHelper::BATCHING,
                            ConstantHelper::ON_SITE_INSP,
                            ConstantHelper::ON_SITE_TRAVEL,
                            ConstantHelper::RETURN
                        ])) {
                            $rejectedStatus = true;
                        }
                    }
                    $activitiesList[] = array(
                        'id' => $key,
                        'activity_name' => $activity['name'],
                        'planned_start_time' => $tripDetails -> {"planned_{$activity['key']}_start"},
                        'planned_time' => $tripDetails -> {"planned_{$activity['key']}_time"},
                        'planned_end_time' => $tripDetails -> {"planned_{$activity['key']}_end"},
                        'actual_start_time' => $tripDetails -> {"actual_{$activity['key']}_start"},
                        'actual_time' => $tripDetails -> {"actual_{$activity['key']}_time"},
                        'actual_end_time' => $tripDetails -> {"actual_{$activity['key']}_end"},
                        'is_completed' => $tripDetails -> {"actual_{$activity['key']}_end"} ? true : false,
                        'is_current' => $tripDetails -> {"actual_{$activity['key']}_start"} && $tripDetails -> {"actual_{$activity['key']}_end"} === null ? true : false,
                        'button_report' => $activity['report'],
                        'button_reject' => $activity['reject'],
                        'show_map' => $activity['map'],
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'start_lat' => $startPointLat,
                        'start_lng' => $startPointLng,
                        'end_lat' => $endPointLat,
                        'end_lng' => $endPointLng,
                        'deviation' => $deviation,
                        'deviation_string' => CommonHelper::getMinutesToHumanDiff(abs($deviation)) . ' ' . CommonHelper::getDeviationStringType($deviation),
                        'is_rejected' => $rejectedStatus
                    );
                }

                usort($activitiesList, function($a, $b) {
                    return strtotime($a['planned_start_time']) <=> strtotime($b['planned_start_time']);
                });

                $tripDetails -> activity_list = $activitiesList;
                $tripDetails -> current_activity = $tripDetails -> getCurrentActivity();
                $tripDetails -> batching_plant_name = $tripDetails -> batching_plant_detail ?-> plant_name;
                $tripDetails -> order -> group_company_name = $tripDetails -> order -> group_company_name();
                $tripDetails -> order -> company_location_name = $tripDetails -> order -> company_location_name();
                $tripDetails -> order -> customer_company_name = $tripDetails -> order -> customer_company_name();
                $tripDetails -> makeHidden(['created_by', 'updated_by', 'deleted_by', 'deleted_at', 'created_at', 'updated_at', 'status', 'batching_plant_detail']);

                return array(
                    'message' => 'Trip details retrieved',
                    'data' => array(
                        'trip_details' => $tripDetails,
                        'driver_details' => array(
                            'id' => auth() -> user() -> id,
                            'name' => auth() -> user() -> name
                        )
                    )
                );
            } else {
                throw new ApiGenericException("Trip Not Found");
            }

        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }



    public function updateLiveScheduleTrip(Request $request)
    {

        $validator = (new Validator($request))->updateLiveScheduleTrip();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {

            DB::beginTransaction();
            $trip = LiveOrderSchedule::where([
                ['id', $request -> id],
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE]
            ]) -> first();

            if (isset($trip)) {
                $isNextStepMapView = false;
                $mapViewType = null;
                $currentTime = Carbon::now() -> addHours(5) -> addMinutes(30);
                $currentTimeNextStatus = $currentTime -> copy() -> addMinute();
                switch ($request -> status) {
                    case ConstantHelper::BATCHING:
                        $trip -> actual_loading_end = $currentTime;
                        $trip -> actual_loading_time = Carbon::parse($trip -> actual_loading_start) -> diffInMinutes($currentTime);
                        $trip -> actual_qc_start = $currentTimeNextStatus;
                        // LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_loading_start, $trip -> planned_loading_start, $trip);

                    //Re-schedule Helper call
                        $delay = Carbon::parse($trip -> planned_loading_end) -> diffInMinutes($currentTime);
                        if($delay){
                          //  $reshceduleOrders = LiveOrderReScheduleHelper::initializeReSchedule($trip, ConstantHelper::BATCHING, $currentTime);

                        }

                        break;

                    case ConstantHelper::INTERNAL_QC:
                        $trip -> actual_qc_end = $currentTime;
                        $trip -> actual_qc_time = Carbon::parse($trip -> actual_qc_start) -> diffInMinutes($currentTime);
                        $trip -> actual_travel_start = $currentTimeNextStatus;
                        $isNextStepMapView = true;
                        $mapViewType = ConstantHelper::ON_SITE_TRAVEL;
                        $liveOrder = LiveOrder::find($trip -> order_id);
                        $firestoreCollectionData = $trip -> only('id','order_id', 'transit_mixer_id', 'driver_id', 'batching_qty', 'planned_travel_start', 'planned_travel_end');
                        $firestoreCollectionData['driver_details'] = $trip -> driver_details ?-> only('id','code', 'name', 'email_id', 'phone');
                        $firestoreCollectionData['transit_mixer_detail'] = $trip -> transit_mixer_detail ?-> only('id', 'truck_name', 'registration_no', 'truck_capacity');
                        $startLocation = CompanyLocation::find($liveOrder ?-> company_location_id);
                        $firestoreCollectionData['transit_mixer_location'] = array(
                            'id' => 1,
                            'transit_mixer_id' => $trip -> transit_mixer_id,
                            'latitude' => $request -> latitude ? $request -> latitude : $startLocation -> latitude,
                            'longitude' => $request -> longitude ? $request -> longitude : $startLocation -> longitude
                        );
                        $firestoreCollectionData['current_activity'] = ConstantHelper::ON_SITE_TRAVEL;
                        $firestoreCollectionData['is_show_on_map'] = true;
                        $firestore = new FirestoreHelper();
                        $firestore -> createOrUpdateDriverLocations($firestoreCollectionData, $liveOrder, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id);
                        // LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_qc_start, $trip -> actual_qc_end, $trip);
                        // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' On Site Travel has been started', 'Order Trip Update');

                        //Re-schedule Helper call
                        $delay = Carbon::parse($trip -> planned_qc_end) -> diffInMinutes($currentTime);
                        if($delay){

                            //$rescheduleOrders = LiveOrderReScheduleHelper::initializeReSchedule($trip, ConstantHelper::INTERNAL_QC, $currentTime);
                        }
                        break;

                        case ConstantHelper::ON_SITE_TRAVEL:
                        $trip -> actual_travel_end = $currentTime;
                        $trip -> actual_travel_time = Carbon::parse($trip -> actual_travel_start) -> diffInMinutes($currentTime);
                        $trip -> actual_insp_start = $currentTimeNextStatus;
                        $firestore = new FirestoreHelper();
                        $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::ON_SITE_INSP, false);
                        // LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_travel_start, $trip -> actual_travel_end, $trip);
                        // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' On Site Inspection has been started', 'Order Trip Update');

                        //Re-schedule Helper call
                        $delay = Carbon::parse($trip -> planned_travel_end) -> diffInMinutes($currentTime);
                        if($delay){

                           // $rescheduleOrders = LiveOrderReScheduleHelper::initializeReSchedule($trip, ConstantHelper::ON_SITE_TRAVEL, $currentTime);
                        }
                        break;

                    case ConstantHelper::ON_SITE_INSP:
                        $trip -> actual_insp_end = $currentTime;
                        $trip -> actual_insp_time = Carbon::parse($trip -> actual_insp_start) -> diffInMinutes($currentTime);
                        $trip -> actual_pouring_start = $currentTimeNextStatus;
                        $trip -> actual_delivery_start = $currentTimeNextStatus;
                        $trip -> actual_deviation = ($currentTimeNextStatus) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));
                        $firestore = new FirestoreHelper();
                        $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::POURING, false);
                        // LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_insp_start, $trip -> actual_insp_end, $trip);
                        // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' Pouring has been started', 'Order Delivery Started');
                        break;

                    case ConstantHelper::POURING:
                        $trip -> actual_pouring_end = $currentTime;
                        $trip -> actual_pouring_time = Carbon::parse($trip -> actual_pouring_start) -> diffInMinutes($currentTime);
                        $trip -> actual_cleaning_start = $currentTimeNextStatus;
                        $firestore = new FirestoreHelper();
                        $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::CLEAN_ON_SITE, false);
                        // LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_pouring_start, $trip -> actual_pouring_end, $trip);
                        // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' Pouring has been completed', 'Order Delivered');
                        break;

                    case ConstantHelper::CLEAN_ON_SITE:
                        $trip -> actual_cleaning_end = $currentTime;
                        $trip -> actual_cleaning_time = Carbon::parse($trip -> actual_cleaning_start) -> diffInMinutes($currentTime);
                        $trip -> actual_return_start = $currentTimeNextStatus;
                        $isNextStepMapView = true;
                        $mapViewType = ConstantHelper::RETURN;
                        $firestore = new FirestoreHelper();
                        $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::RETURN, false);
                        break;

                    case ConstantHelper::RETURN:
                        $trip -> actual_return_end = $currentTime;
                        $trip -> actual_return_time = Carbon::parse($trip -> actual_return_start) -> diffInMinutes($currentTime);
                        // $firestore = new FirestoreHelper();
                        // $firestore -> updateHasReturnedTripTruck(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT, $trip -> id);
                        $mapViewType = "End";
                        $firestore = new FirestoreHelper();
                        $firestore -> removeDriver(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id);
                        break;

                    default:

                        break;
                }
                $trip -> save();
                DB::commit();
                return array(
                    'message' => 'Trip Update Success',
                    'data' => array(
                        'show_map' => $isNextStepMapView,
                        'map_type' => $mapViewType
                    )
                );
            } else {
                DB::rollBack();
                throw new ApiGenericException("Trip not found or status cannot be updated");
            }

        } catch(Exception $ex) {

            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }



    public function switchTransitMixer(Request $request)
    {
        try {
            $user = auth() -> user();
            $transitMixers = $user -> driver ?-> transit_mixers;
            if (count($transitMixers) <= 1) {
                throw new ApiGenericException("Multiple transit mixers are not assigned to this driver");
            } else {
                $trucksResponse = collect();
                //Create token for each company user
                foreach ($transitMixers as &$driverTruck) {
                    $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::USER_TYPE_DRIVER]);
                    $tokenResult -> accessToken -> user_type_id = $driverTruck -> driver_id;
                    $tokenResult -> accessToken -> user_type_sub_id = $driverTruck -> id;
                    $tokenResult -> accessToken -> save();
                    $accessToken = $tokenResult->plainTextToken;
                    $trucksResponse -> push([
                        'truck_id' => $driverTruck -> transit_mixer ?-> id,
                        'truck_name' => $driverTruck -> transit_mixer ?-> truck_name,
                        'truck_icon' => $driverTruck -> transit_mixer ?-> image_url,
                        'truck_capacity' => $driverTruck -> transit_mixer ?-> truck_capacity,
                        'truck_no' => $driverTruck -> transit_mixer ?-> registration_no,
                        'user_type' => $user -> user_type,
                        'access_token' => $accessToken
                    ]);
                }
                return array(
                    'message' => 'Transit Mixers retrieved successfully',
                    'data' => array(
                        'trucks' => $trucksResponse
                    )
                );
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getPastDeliveries(Request $request)
    {
        try {
            $liveCompletedDeliveries = LiveOrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['status', ConstantHelper::ACTIVE],
            ]) -> where(function ($subQuery) {
                $subQuery -> whereNotNull('actual_return_end') -> orWhereHas('rejections');
            }) -> with('rejections') -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "actual_start_time", "actual_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> orderBy("actual_return_end", "desc") -> get();

            $pastCompletedOrders = OrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereNotNull('return_end') -> where('return_end', '<=', Carbon::now()) -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "start_time", "end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> orderBy("return_end", "desc") -> get();

            $allDeliveries = $liveCompletedDeliveries -> merge($pastCompletedOrders);
            $processesPastDeliveries = collect();

            foreach ($allDeliveries as &$delivery) {
                $delivery -> order -> group_company_name = $delivery -> order -> group_company_name();
                $delivery -> order -> company_location_name = $delivery -> order -> company_location_name();
                if ($delivery -> actual_deviation === 0) {
                    $delivery -> actual_deviation = "On Time";
                } else if ($delivery -> actual_deviation > 0) {
                    $delivery -> actual_deviation = CommonHelper::getMinutesToHumanDiff(abs($delivery -> actual_deviation)) . " Delay";
                } else if ($delivery -> actual_deviation < 0) {
                    $delivery -> actual_deviation = CommonHelper::getMinutesToHumanDiff(abs($delivery -> actual_deviation)) . " Early";
                } else if (isset($delivery -> rejections) && count($delivery -> rejections) > 0) {
                    $delivery -> actual_deviation = $delivery -> rejections -> first() -> remarks ?? 'Rejected';
                } else {
                    $delivery -> actual_deviation = "";
                }
                $delivery = $delivery -> only(["id", "order_id", "schedule_date", "order_no", "location", "trip", "batching_qty", "current_activity", "order", "actual_deviation", "actual_delivery_start"]);
                $processesPastDeliveries -> push($delivery);
            }
            return array(
                'message' => 'Deliveries retrieved successfully',
                'data' => array(
                    'past_deliveries' => $processesPastDeliveries
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }


    public function getAssignedProjects(Request $request)
    {
        try {
            $liveDeliveries = LiveOrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereNotNull('actual_loading_start') -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> get();

            $projects = collect([]);

            foreach ($liveDeliveries as $liveDelivery) {
                $projects -> push([
                    'id' => $liveDelivery -> order ?-> project ?-> id,
                    'code' => $liveDelivery -> order ?-> project ?-> code,
                    'name' => $liveDelivery -> order ?-> project ?-> name,
                    'address' => $liveDelivery -> order ?-> project ?-> address,
                ]);
            }

            return array(
                'messsage' => 'Projects returned successfully',
                'data' => array(
                    'projects' => $projects,
                    'driver_details' => array(
                        'id' => auth() -> user() -> id,
                        'name' => auth() -> user() -> name
                    )
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getAssignedProjectsCount(Request $request)
    {
        try {
            $liveDeliveries = LiveOrderSchedule::where([
                ['group_company_id', $request -> group_company_id],
                ['transit_mixer_id', $request -> transit_mixer_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE],
            ]) -> whereNotNull('actual_loading_start') -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> get();

            $projects = collect([]);

            foreach ($liveDeliveries as $liveDelivery) {
                $existingProject = $projects -> where('id', $liveDelivery -> order ?-> project ?-> id);
                if ($existingProject -> isEmpty()) {
                    $projects -> push([
                        'id' => $liveDelivery -> order ?-> project ?-> id,
                        'code' => $liveDelivery -> order ?-> project ?-> code,
                        'name' => $liveDelivery -> order ?-> project ?-> name,
                        'address' => $liveDelivery -> order ?-> project ?-> address,
                    ]);
                }
            }

            $projectsCount = $projects -> count();

            return array(
                'message' => 'Projects count retrieved successfully',
                'data' => array(
                    'projects_count' => $projectsCount,
                    'project_id' => $projectsCount == 1 ? $projects -> first()['id'] : null,
                    'project_data' => $projectsCount == 1 ? $projects -> first() : null
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }



    // operator data

      public function operatorDashboardData(Request $request)
      {
          try {
              $transitMixer = Pump::select('group_company_id',
              'pump_name',
              'type',
              'description',
              'operator_id',
              'pump_capacity',
              'status') -> find($request -> pump_id);
              $liveDeliveries = LiveOrderPumpSchedule::where([
                  ['group_company_id', $request -> group_company_id],
                  ['pump_id', $request -> pump_id],
                  ['actual_return_end', null],
                  ['status', ConstantHelper::ACTIVE],
              ]) -> with(['order' => function ($query) {
                  $query -> select("id", "order_no", "site_id", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id") -> with(['customer_product' => function ($custQuery) {
                      $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                  }]) -> with(['project' => function ($subQuery) {
                      $subQuery -> select('id','code' , 'name') -> with('address');
                  }]);
              }]) -> get();
              $processedLiveDeliveries = array();
              foreach ($liveDeliveries as &$liveDelivery) {
                  $liveDelivery -> current_activity = $liveDelivery -> getCurrentActivity();
                //   $liveDelivery -> batching_plant_name = $liveDelivery -> batching_plant_detail ?-> plant_name;
                  $liveDelivery -> batching_plant_name = "Batching Plant";
                  $liveDelivery -> order -> group_company_name = $liveDelivery -> order -> group_company_name();
                  $liveDelivery -> order -> company_location_name = $liveDelivery -> order -> company_location_name();
                  $liveDelivery -> order -> customer_company_name = $liveDelivery -> order -> customer_company_name();
                  $liveDelivery -> order -> start_point = array(
                      'latitude' => $liveDelivery -> order -> company_location ?-> latitude,
                      'longitude' => $liveDelivery -> order -> company_location ?-> longitude,
                  );
                  $liveDelivery -> order -> end_point = array(
                      'latitude' => $liveDelivery -> order -> customer_site ?-> latitude,
                      'longitude' => $liveDelivery -> order -> customer_site ?-> longitude,
                  );
                  $processedLiveDeliveries[] = $liveDelivery -> only(["id", "order_id", "schedule_date", "order_no", "location", "trip", "batching_qty", "current_activity", "order", "batching_plant_name", "driver_id", "planned_travel_end", "planned_return_end"]);
              }
              $liveDeliveriesQuery = LiveOrderSchedule::where([
                  ['group_company_id', $request -> group_company_id],
                  ['pump_id', $request -> pump_id],
                  ['status', ConstantHelper::ACTIVE],
              ]) -> whereNotNull('actual_delivery_start');
              $lastSixMonthDeliveries = $liveDeliveriesQuery -> where('actual_delivery_start', '>=', Carbon::now()->subMonths(6))->get();
              $thisMonthDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();;
              $thisWeekDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();;
              $todayDeliveries = $liveDeliveriesQuery -> whereBetween('actual_delivery_start', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
              return array(
                  'message' => 'Details Retrieved successfully',
                  'data' => array(
                      'truck_details' => $transitMixer,
                      'live_deliveries' => $processedLiveDeliveries,
                      'statistics' => array(
                          'six_month' => count($lastSixMonthDeliveries),
                          'this_month' => count($thisMonthDeliveries),
                          'this_week' => count($thisWeekDeliveries),
                          'today' => count($todayDeliveries)
                      )
                  )
              );
          } catch(Exception $ex) {
              throw new ApiGenericException($ex -> getLine());
          }
      }


        // operator
    public function getOperatorTripDetails(Request $request, String $id)
    {

        try {
            $tripDetails = LiveOrderPumpSchedule::where([
                ['id', $id],
                ['group_company_id', $request -> group_company_id],
                ['pump_id', $request -> pump_id],
                ['actual_return_end', null],
                ['status', ConstantHelper::ACTIVE],
            ])
            // ->whereNotNull('actual_qc_start')
            -> with(['order' => function ($query) {
                $query -> select("id", "order_no", "planned_start_time", "planned_end_time", "project_id", "structural_reference_id", "cust_product_id", "group_company_id", "company_location_id", "customer_id", "site_id",) -> with('customer_site') -> with(['customer_product' => function ($custQuery) {
                    $custQuery -> select("id", "product_id", "total_quantity", "ordered_quantity");
                }]) -> with(['project' => function ($subQuery) {
                    $subQuery -> select('id','code' , 'name') -> with('address');
                }]);
            }]) -> first();

            $activitiesList = array();

            // dd($tripDetails);
            if (isset($tripDetails)) {

                $rejection = null;
                foreach (ConstantHelper::LIVE_PUMP_ACTIVITIES as $key => $activity) {
                    $latitude = null;
                    $longitude = null;
                    $startPointLat = $tripDetails -> order ?-> company_location ?-> latitude;
                    $startPointLng = $tripDetails -> order ?-> company_location ?-> longitude;
                    $endPointLat = $tripDetails -> order ?-> customer_site ?-> latitude;
                    $endPointLng = $tripDetails -> order ?-> customer_site ?-> longitude;
                    if ($activity['map']) {
                        if ($activity['name'] === ConstantHelper::ON_SITE_TRAVEL) {
                            $latitude = $tripDetails -> order ?-> customer_site ?-> latitude;
                            $longitude = $tripDetails -> order ?-> customer_site ?-> longitude;
                        } else if ($activity['name'] === ConstantHelper::RETURN) {
                            $latitude = $tripDetails -> order ?-> company_location ?-> latitude;
                            $longitude = $tripDetails -> order ?-> company_location ?-> longitude;
                        } else {
                            $latitude = null;
                            $longitude = null;
                        }
                    }
                    $deviation = 0;
                    if (isset($tripDetails -> {"actual_{$activity['key']}_start"})) {
                        $deviation = Carbon::parse($tripDetails -> {"planned_{$activity['key']}_end"}) -> diffInMinutes(Carbon::parse($tripDetails -> {"actual_{$activity['key']}_end"}), false);
                    }
                    $rejectedStatus = false;
                    //Disable further steps if trip quantity is rejected
                    if (isset($rejection)) {
                        //Disable till last
                        if ($rejection -> activity === ConstantHelper::INTERNAL_QC && $activity['name'] !== ConstantHelper::BATCHING) {
                            $rejectedStatus = true;
                        } else if ($rejection -> activity === ConstantHelper::ON_SITE_INSP && !in_array($activity['name'], [
                            ConstantHelper::BATCHING,
                            ConstantHelper::ON_SITE_INSP,
                            ConstantHelper::ON_SITE_TRAVEL,
                            ConstantHelper::RETURN
                        ])) {
                            $rejectedStatus = true;
                        }
                    }

                    $activitiesList[] = array(
                        'id' => $key,
                        'activity_name' => $activity['name'],
                        'planned_start_time' => $tripDetails -> {"planned_{$activity['key']}_start"},
                        'planned_time' => $tripDetails -> {"planned_{$activity['key']}_time"},
                        'planned_end_time' => $tripDetails -> {"planned_{$activity['key']}_end"},
                        'actual_start_time' => $tripDetails -> {"actual_{$activity['key']}_start"},
                        'actual_time' => $tripDetails -> {"actual_{$activity['key']}_time"},
                        'actual_end_time' => $tripDetails -> {"actual_{$activity['key']}_end"},
                        'is_completed' => $tripDetails -> {"actual_{$activity['key']}_end"} ? true : false,
                        'is_current' => $tripDetails -> {"actual_{$activity['key']}_start"} && $tripDetails -> {"actual_{$activity['key']}_end"} === null ? true : false,
                        'button_report' => $activity['report'],
                        'button_reject' => $activity['reject'],
                        'show_map' => $activity['map'],
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'start_lat' => $startPointLat,
                        'start_lng' => $startPointLng,
                        'end_lat' => $endPointLat,
                        'end_lng' => $endPointLng,
                        'deviation' => $deviation,
                        'deviation_string' => CommonHelper::getMinutesToHumanDiff(abs($deviation)) . ' ' . CommonHelper::getDeviationStringType($deviation),
                        'is_rejected' => $rejectedStatus
                    );
                }

                usort($activitiesList, function($a, $b) {
                    return strtotime($a['planned_start_time']) <=> strtotime($b['planned_start_time']);
                });
                $tripDetails -> activity_list = $activitiesList;
                $tripDetails -> current_activity = $tripDetails -> getCurrentActivity();
                $tripDetails -> current_activity ='status';
                $tripDetails -> batching_plant_name = $tripDetails -> batching_plant_detail ?-> plant_name;
                $tripDetails -> order -> group_company_name = $tripDetails -> order -> group_company_name();
                $tripDetails -> order -> company_location_name = $tripDetails -> order -> company_location_name();
                $tripDetails -> order -> customer_company_name = $tripDetails -> order -> customer_company_name();
                $tripDetails -> makeHidden(['created_by', 'updated_by', 'deleted_by', 'deleted_at', 'created_at', 'updated_at', 'status', 'batching_plant_detail']);

                return array(
                    'message' => 'Trip details retrieved',
                    'data' => array(
                        'trip_details' => $tripDetails,
                        'driver_details' => array(
                            'id' => auth() -> user() -> id,
                            'name' => auth() -> user() -> name
                        )
                    )
                );
            } else {
                throw new ApiGenericException("Trip Not Found");
            }

        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }


      // operator

      public function updateOperatorLiveScheduleTrip(Request $request)
      {
          $validator = (new Validator($request))->updateLiveScheduleTrip();
          if ($validator->fails()) {
              throw new ValidationException($validator);
          }
          try {
              DB::beginTransaction();
              $trip = LiveOrderSchedule::where([
                  ['id', $request -> id],
                  ['group_company_id', $request -> group_company_id],
                  ['pump_id', $request -> pump_id],
                  ['actual_return_end', null],
                  ['status', ConstantHelper::ACTIVE]
              ]) -> first();

              if (isset($trip)) {
                  $isNextStepMapView = false;
                  $mapViewType = null;
                  $currentTime = Carbon::now() -> addHours(5) -> addMinutes(30);
                  $currentTimeNextStatus = $currentTime -> copy() -> addMinute();
                  switch ($request -> status) {
                      case ConstantHelper::BATCHING:
                          $trip -> actual_loading_end = $currentTime;
                          $trip -> actual_loading_time = Carbon::parse($trip -> actual_loading_start) -> diffInMinutes($currentTime);
                          $trip -> actual_qc_start = $currentTimeNextStatus;
                        //   LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_loading_start, $trip -> planned_loading_start, $trip);

                      //Re-schedule Helper call
                          $delay = Carbon::parse($trip -> planned_loading_end) -> diffInMinutes($currentTime);
                          if($delay){
                              $reshceduleOrders = OrderScheduleHelper::initializeReSchedule($trip, ConstantHelper::BATCHING, $currentTime);
                          }

                          break;

                      case ConstantHelper::INTERNAL_QC:
                          $trip -> actual_qc_end = $currentTime;
                          $trip -> actual_qc_time = Carbon::parse($trip -> actual_qc_start) -> diffInMinutes($currentTime);
                          $trip -> actual_travel_start = $currentTimeNextStatus;
                          $isNextStepMapView = true;
                          $mapViewType = ConstantHelper::ON_SITE_TRAVEL;
                          $liveOrder = LiveOrder::find($trip -> order_id);
                          $firestoreCollectionData = $trip -> only('id','order_id', 'pump_id', 'driver_id', 'batching_qty', 'planned_travel_start', 'planned_travel_end');
                          $firestoreCollectionData['driver_details'] = $trip -> driver_details ?-> only('id','code', 'name', 'email_id', 'phone');
                          $firestoreCollectionData['transit_mixer_detail'] = $trip -> transit_mixer_detail ?-> only('id', 'truck_name', 'registration_no', 'truck_capacity');
                          $startLocation = CompanyLocation::find($liveOrder ?-> company_location_id);
                          $firestoreCollectionData['transit_mixer_location'] = array(
                              'id' => 1,
                              'pump_id' => $trip -> pump_id,
                              'latitude' => $request -> latitude ? $request -> latitude : $startLocation -> latitude,
                              'longitude' => $request -> longitude ? $request -> longitude : $startLocation -> longitude
                          );
                          $firestoreCollectionData['current_activity'] = ConstantHelper::ON_SITE_TRAVEL;
                          $firestoreCollectionData['is_show_on_map'] = true;
                          $firestore = new FirestoreHelper();
                          $firestore -> createOrUpdateDriverLocations($firestoreCollectionData, $liveOrder, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id);
                        //   LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_qc_start, $trip -> actual_qc_end, $trip);
                          // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' On Site Travel has been started', 'Order Trip Update');
                          break;

                          case ConstantHelper::ON_SITE_TRAVEL:
                          $trip -> actual_travel_end = $currentTime;
                          $trip -> actual_travel_time = Carbon::parse($trip -> actual_travel_start) -> diffInMinutes($currentTime);
                          $trip -> actual_insp_start = $currentTimeNextStatus;
                          $firestore = new FirestoreHelper();
                          $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::ON_SITE_INSP, false);
                        //   LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_travel_start, $trip -> actual_travel_end, $trip);
                          // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' On Site Inspection has been started', 'Order Trip Update');
                          break;

                      case ConstantHelper::ON_SITE_INSP:
                          $trip -> actual_insp_end = $currentTime;
                          $trip -> actual_insp_time = Carbon::parse($trip -> actual_insp_start) -> diffInMinutes($currentTime);
                          $trip -> actual_pouring_start = $currentTimeNextStatus;
                          $trip -> actual_delivery_start = $currentTimeNextStatus;
                          $trip -> actual_deviation = ($currentTimeNextStatus) -> diffInMinutes(Carbon::parse($trip -> planned_pouring_start));
                          $firestore = new FirestoreHelper();
                          $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::POURING, false);
                        //   LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_insp_start, $trip -> actual_insp_end, $trip);
                          // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' Pouring has been started', 'Order Delivery Started');
                          break;

                      case ConstantHelper::POURING:
                          $trip -> actual_pouring_end = $currentTime;
                          $trip -> actual_pouring_time = Carbon::parse($trip -> actual_pouring_start) -> diffInMinutes($currentTime);
                          $trip -> actual_cleaning_start = $currentTimeNextStatus;
                          $firestore = new FirestoreHelper();
                          $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::CLEAN_ON_SITE, false);
                        //   LiveScheduleHelper::updateTripOnDriverUpdate($request -> status, $trip -> actual_pouring_start, $trip -> actual_pouring_end, $trip);
                          // NotificationHelper::sendToCustomers($trip ?-> order -> customer_id, $trip ?-> order ?-> project_id, 'Order No - ' . $trip ?-> order ?-> order_no . ', Trip - ' . $trip -> trip . ' Pouring has been completed', 'Order Delivered');
                          break;

                      case ConstantHelper::CLEAN_ON_SITE:
                          $trip -> actual_cleaning_end = $currentTime;
                          $trip -> actual_cleaning_time = Carbon::parse($trip -> actual_cleaning_start) -> diffInMinutes($currentTime);
                          $trip -> actual_return_start = $currentTimeNextStatus;
                          $isNextStepMapView = true;
                          $mapViewType = ConstantHelper::RETURN;
                          $firestore = new FirestoreHelper();
                          $firestore -> updateCurrentActivity(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id, ConstantHelper::RETURN, false);
                          break;

                      case ConstantHelper::RETURN:
                          $trip -> actual_return_end = $currentTime;
                          $trip -> actual_return_time = Carbon::parse($trip -> actual_return_start) -> diffInMinutes($currentTime);
                          // $firestore = new FirestoreHelper();
                          // $firestore -> updateHasReturnedTripTruck(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT, $trip -> id);
                          $mapViewType = "End";
                          $firestore = new FirestoreHelper();
                          $firestore -> removeDriver(ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_COLLECTION, ConstantHelper::DRIVER_LOCATIONS_FIRESTORE_DOCUMENT . $trip -> order_id, ConstantHelper::DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION, $trip -> driver_id);
                          break;

                      default:

                          break;
                  }
                  $trip -> save();
                  DB::commit();
                  return array(
                      'message' => 'Trip Update Success',
                      'data' => array(
                          'show_map' => $isNextStepMapView,
                          'map_type' => $mapViewType
                      )
                  );
              } else {
                  DB::rollBack();
                  throw new ApiGenericException("Trip not found or status cannot be updated");
              }

          } catch(Exception $ex) {
              DB::rollBack();
              throw new ApiGenericException($ex -> getMessage());
          }
      }
}
