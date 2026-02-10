<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exceptions\OrderImportException;
use App\Helpers\BatchingPlantHelper;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\GroupCompanyHelper;
use App\Helpers\LiveOrderHelper;
use App\Helpers\OrderApprovalHelper;
use App\Helpers\OrderHelper;
use App\Helpers\OrderScheduleHelper;
use App\Helpers\V2\OrderScheduleHelper as OrderScheduleHelperV2;

use App\Helpers\PumpHelper;
use App\Helpers\RouteConstantHelper;
use App\Helpers\TransitMixerHelper;
use App\Imports\OrderImport;
use App\Imports\PumpImport;
use App\Imports\TransitMixerImport;
use App\Models\ApprovalLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ApprovalSetup;
use App\Models\BatchingPlant;
use App\Models\BatchingPlantAvailability;
use App\Models\CompanyLocation;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\CustomerProject;
use App\Models\CustomerProjectSite;
use App\Models\GroupCompany;
use App\Models\LiveOrder;
use App\Models\LiveOrderSchedule;
use App\Models\Order;
use App\Models\OrderApproval;
use App\Models\OrderCubeMould;
use App\Models\OrderPump;
use App\Models\OrderTempControl;
use App\Models\PumpType;
use App\Models\SelectedOrder;
use App\Models\OrderPumpSchedule;
use App\Models\OrderSchedule;
use App\Models\Pump;
use App\Models\SelectedOrderPumpSchedule;
use App\Models\SelectedOrderSchedule;
use App\Models\StructuralReference;
use App\Models\Temperature;
use App\Models\TransitMixer;
use Carbon\Carbon;

use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Lib\Validations\Order as Validator;
use Illuminate\Validation\ValidationException;
use App\Helpers\CustomerProjectSiteHelper;
use App\Lib\Services\ScheduleService;


use App\Exports\OrderExport;


class OrderController extends Controller
{
    public function updateOrder(Request $request)
    {
        $validator = (new Validator($request))->updateOrder();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            SelectedOrder::where("id", $request->order_id)->where("user_id", auth()->user()->id)
                ->update(["delivery_date" => $request->delivery_date, "interval" => $request->interval, "interval_deviation" => $request->interval_deviation]);
            // SelectedOrder::where([["id", "!=", $request -> order_id],['user_id', auth() -> user() -> id],
            //  ['priority', "!=", ConstantHelper::DEFAULT_PRIORITY],
            //  ['priority', ">=", $request -> priority]]) -> increment("priority", 1);
            return redirect()->back()->with(ConstantHelper::SUCCESS, __("message.update_success", ['static' => __("static.order")]));
        } catch (Exception $ex) {
            return redirect()->back()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }
    //Generate schedule step one
    public function scheduleViewStepOne(Request $request)
    {
        try {
            $authUser = auth()->user();
            $groupCompanyIds = $authUser->access_rights->pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')->where('status', ConstantHelper::ACTIVE)->whereIn('id', $groupCompanyIds)->get();
            $data = [
                'groupCompanies' => $groupCompanies
            ];
            return view("components.orders.generate_order_step_1", $data);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }
    public function createNewOrder(Request $request)
    {
        try {
            //Retrieve all companies of user
            $user = auth()->user();
            $groupCompanies = $user->access_rights?->pluck('group_company_id');
            $locationIds = $user->access_rights?->pluck('location_id')->toArray();
            $groupCompaniesId = $groupCompanies->toArray();
            $groupCompanies = GroupCompany::select('id', 'id AS value', 'comp_name AS label')->whereIn('id', $groupCompaniesId)
                ->where('status', ConstantHelper::ACTIVE)->orderByDesc('id')->get();
            $groupCompanyLocations = collect();
            $customers = collect();
            $customerProjects = collect();
            $customerProjectSites = collect();
            $customerProjectProducts = collect();
            $structuralReferences = collect();
            $temps = collect();
            $pumpTypes = collect();
            $pumpSizes = collect();

            $firstGroupCompanyId = $groupCompanies->first();
            if (isset($firstGroupCompanyId)) {
                $groupCompanyLocations = CompanyLocation::select('id AS value', 'site_name AS label')->whereIn('id', $locationIds)->get();
                $structuralReferences = StructuralReference::select("id AS value", "name AS label")->where([
                    ['status', ConstantHelper::ACTIVE],
                ])->whereIn('group_company_id', $groupCompaniesId)->get();
                $temps = Temperature::select("temp AS value", "temp AS label")->where([
                    ['status', ConstantHelper::ACTIVE]
                ])->whereIn('group_company_id', $groupCompaniesId)->get();
                $pumpTypes = PumpType::select("type AS value", "type AS label")->where([
                    ['status', ConstantHelper::ACTIVE]
                ])->whereIn('group_company_id', $groupCompaniesId)->get();
                // $pumpSizes = Pump::where([
                //     ["status", ConstantHelper::ACTIVE],
                // ])->select('pump_capacity AS label') -> whereIn('group_company_id',$groupCompaniesId ) ->distinct() -> get();
                // foreach ($pumpSizes as &$pumpSize) {
                //     $pumpSize -> value = $pumpSize -> label;
                // }
                $customers = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label"))->where([
                    ['status', ConstantHelper::ACTIVE]
                ])->wherehas('group_companies', function ($query) use ($groupCompaniesId) {
                    $query->whereIn('group_company_id', $groupCompaniesId);
                })->get();
            }
            return view("components.orders.create_order", [
                'groupCompanies' => $groupCompanies,
                'companyLocations' => $groupCompanyLocations,
                'customers' => $customers,
                'customerProjects' => $customerProjects,
                'customerProjectSites' => $customerProjectSites,
                'customerProjectProducts' => $customerProjectProducts,
                'structuralReferences' => $structuralReferences,
                'temps' => $temps,
                'pumpTypes' => $pumpTypes,
                'pumpSizes' => $pumpSizes,
            ]);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function editOrder(Request $request, string $orderId)
    {
        try {
            $order = Order::with('approvals')->find($orderId);
            if (isset($order)) {
                //Retrieve all companies of user
                $user = auth()->user();
                $groupCompanies = $user->access_rights?->pluck('group_company_id');
                $defaultGroupCompany = $user->group_company_id;
                $groupCompaniesId = $groupCompanies->toArray();
                $groupCompanies = GroupCompany::select('id', 'id AS value', 'comp_name AS label')->whereIn('id', $groupCompaniesId)->where('status', ConstantHelper::ACTIVE)->orderByDesc('id')->get();
                $canApprove = false;
                $approvalSetup = ApprovalSetup::where('group_company_id', auth()->user()->group_company_id)->first();
                $approvalLevels = ApprovalLevel::where('approval_setup_id', $approvalSetup->id)->orderBy('level_no')->get();
                $access = $approvalLevels->firstWhere('user_id', $user->id);
                $approvalDropdownStatuses = [
                    ['value' => 'Approved', 'label' => 'Approve'],
                    ['value' => 'Rejected', 'label' => 'Reject'],
                ];
                $previousLevels = $approvalLevels->filter(function ($level) use ($access) {
                    return ($level->level_no < $access?->level_no);
                });
                if (count($previousLevels) > 0) {
                    $approvalDropdownStatuses[] = ['value' => 'Sent Back', 'label' => 'Send Back'];
                }
                if (isset($access)) {
                    $approvedStatuses = OrderApproval::where([
                        ['order_id', $order->id],
                        ['status', ConstantHelper::ACTIVE],
                        ['approval_status', 'Approved'],
                        ['reset', 0]
                    ])->get();
                    $currentApprovalStatus = $approvedStatuses->firstWhere('approved_by', $user->id);
                    if (isset($currentApprovalStatus)) {
                        $canApprove = false;
                    } else {
                        $canApprove = OrderApprovalHelper::canApprove($access->level_no, $user->id, $approvalLevels, $approvedStatuses);
                    }
                } else {
                    $canApprove = false;
                }

                $groupCompanyLocations = collect();
                $customers = collect();
                $customerProjects = collect();
                $customerProjectSites = collect();
                $customerProjectProducts = collect();
                $structuralReferences = collect();
                $temps = collect();
                $pumpTypes = collect();
                $pumpSizes = collect();

                $firstGroupCompanyId = $groupCompanies->first();
                if (isset($firstGroupCompanyId)) {
                    $groupCompanyLocations = CompanyLocation::select('id AS value', 'site_name AS label')->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $structuralReferences = StructuralReference::select("id AS value", "name AS label")->where([
                        ['status', ConstantHelper::ACTIVE],
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $temps = Temperature::select("temp AS value", "temp AS label")->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $pumpTypes = PumpType::select("type AS value", "type AS label")->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $pumpSizes = Pump::where([
                        ["status", ConstantHelper::ACTIVE],
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->select('pump_capacity AS label')->distinct()->get();
                    foreach ($pumpSizes as &$pumpSize) {
                        $pumpSize->value = $pumpSize->label;
                    }
                    $customers = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label"))->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->wherehas('group_companies', function ($query) use ($groupCompaniesId) {
                        $query->whereIn('group_company_id', $groupCompaniesId);
                    })->get();
                    $customerProjects = CustomerProject::select('id AS value', 'name AS label')->where([
                        ['customer_id', $order->customer_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    $customerProjectSites = CustomerProjectSite::select('id AS value', 'name AS label')->where([
                        ['cust_project_id', $order->project_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    $customerProjectProducts = CustomerProduct::where([
                        ['project_id', $order->project_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    foreach ($customerProjectProducts as &$mixCode) {
                        $mixCode->name = $mixCode->product_name;
                    }
                }
                return view("components.orders.edit_order", [
                    'groupCompanies' => $groupCompanies,
                    'companyLocations' => $groupCompanyLocations,
                    'customers' => $customers,
                    'customerProjects' => $customerProjects,
                    'customerProjectSites' => $customerProjectSites,
                    'customerProjectProducts' => $customerProjectProducts,
                    'structuralReferences' => $structuralReferences,
                    'temps' => $temps,
                    'pumpTypes' => $pumpTypes,
                    'pumpSizes' => $pumpSizes,
                    'order' => $order,
                    'canApprove' => $canApprove,
                    'approvalStatuses' => $approvalDropdownStatuses
                ]);
            } else {
                return redirect()->back()->with('warning', "Order doesn't exist");
            }
        } catch (Exception $ex) {
            dd($ex->getMessage());
        }
    }
    public function editliveSchedule(Request $request, string $orderId)
    {
        try {
            $order = LiveOrder::with('approvals')->find($orderId);
            if (isset($order)) {
                //Retrieve all companies of user
                $user = auth()->user();
                $groupCompanies = $user->access_rights?->pluck('group_company_id');
                $defaultGroupCompany = $user->group_company_id;
                $groupCompaniesId = $groupCompanies->toArray();
                $groupCompanies = GroupCompany::select('id', 'id AS value', 'comp_name AS label')->whereIn('id', $groupCompaniesId)->where('status', ConstantHelper::ACTIVE)->orderByDesc('id')->get();
                $canApprove = false;
                $approvalSetup = ApprovalSetup::where('group_company_id', auth()->user()->group_company_id)->first();
                $approvalLevels = ApprovalLevel::where('approval_setup_id', $approvalSetup->id)->orderBy('level_no')->get();
                $access = $approvalLevels->firstWhere('user_id', $user->id);
                $approvalDropdownStatuses = [
                    ['value' => 'Approved', 'label' => 'Approve'],
                    ['value' => 'Rejected', 'label' => 'Reject'],
                ];
                $previousLevels = $approvalLevels->filter(function ($level) use ($access) {
                    return ($level->level_no < $access?->level_no);
                });
                if (count($previousLevels) > 0) {
                    $approvalDropdownStatuses[] = ['value' => 'Sent Back', 'label' => 'Send Back'];
                }
                if (isset($access)) {
                    $approvedStatuses = OrderApproval::where([
                        ['order_id', $order->id],
                        ['status', ConstantHelper::ACTIVE],
                        ['approval_status', 'Approved'],
                        ['reset', 0]
                    ])->get();
                    $currentApprovalStatus = $approvedStatuses->firstWhere('approved_by', $user->id);
                    if (isset($currentApprovalStatus)) {
                        $canApprove = false;
                    } else {
                        $canApprove = OrderApprovalHelper::canApprove($access->level_no, $user->id, $approvalLevels, $approvedStatuses);
                    }
                } else {
                    $canApprove = false;
                }

                $groupCompanyLocations = collect();
                $customers = collect();
                $customerProjects = collect();
                $customerProjectSites = collect();
                $customerProjectProducts = collect();
                $structuralReferences = collect();
                $temps = collect();
                $pumpTypes = collect();
                $pumpSizes = collect();

                $firstGroupCompanyId = $groupCompanies->first();
                if (isset($firstGroupCompanyId)) {
                    $groupCompanyLocations = CompanyLocation::select('id AS value', 'site_name AS label')->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $structuralReferences = StructuralReference::select("id AS value", "name AS label")->where([
                        ['status', ConstantHelper::ACTIVE],
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $temps = Temperature::select("temp AS value", "temp AS label")->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $pumpTypes = PumpType::select("type AS value", "type AS label")->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->get();
                    $pumpSizes = Pump::where([
                        ["status", ConstantHelper::ACTIVE],
                    ])->whereIn('group_company_id', auth()->user()->access_rights->pluck('location_id')->toArray())->select('pump_capacity AS label')->distinct()->get();
                    foreach ($pumpSizes as &$pumpSize) {
                        $pumpSize->value = $pumpSize->label;
                    }
                    $customers = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label"))->where([
                        ['status', ConstantHelper::ACTIVE]
                    ])->wherehas('group_companies', function ($query) use ($groupCompaniesId) {
                        $query->whereIn('group_company_id', $groupCompaniesId);
                    })->get();
                    $customerProjects = CustomerProject::select('id AS value', 'name AS label')->where([
                        ['customer_id', $order->customer_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    $customerProjectSites = CustomerProjectSite::select('id AS value', 'name AS label')->where([
                        ['cust_project_id', $order->project_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    $customerProjectProducts = CustomerProduct::where([
                        ['project_id', $order->project_id],
                        ['status', ConstantHelper::ACTIVE]
                    ])->get();
                    foreach ($customerProjectProducts as &$mixCode) {
                        $mixCode->name = $mixCode->product_name;
                    }
                }
                return view("components.orders.edit_live_schedule", [
                    'groupCompanies' => $groupCompanies,
                    'companyLocations' => $groupCompanyLocations,
                    'customers' => $customers,
                    'customerProjects' => $customerProjects,
                    'customerProjectSites' => $customerProjectSites,
                    'customerProjectProducts' => $customerProjectProducts,
                    'structuralReferences' => $structuralReferences,
                    'temps' => $temps,
                    'pumpTypes' => $pumpTypes,
                    'pumpSizes' => $pumpSizes,
                    'order' => $order,
                    'canApprove' => $canApprove,
                    'approvalStatuses' => $approvalDropdownStatuses
                ]);
            } else {
                return redirect()->back()->with('warning', "Order doesn't exist");
            }
        } catch (Exception $ex) {
            // dd($ex->getMessage());
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function getCompanyMastersForOrderCreation(string $groupCompanyId)
    {
        try {
            $authUser = auth()->user();
            $groupCompanyIds = $authUser->access_rights->pluck('group_company_id');
            $locationIds = $authUser->access_rights->pluck('location_id');
            $groupCompanyLocations = CompanyLocation::select('id AS value', 'site_name AS label')->where([
                ['group_company_id', $groupCompanyId]
            ])->whereIn('id', $locationIds)->get();
            $structuralReferences = StructuralReference::select("id AS value", "name AS label")->where([
                ['group_company_id', $groupCompanyId],
                ['status', ConstantHelper::ACTIVE],
            ])->get();
            $temps = Temperature::select("temp AS value", "temp AS label")->where([
                ['group_company_id', $groupCompanyId],
                ['status', ConstantHelper::ACTIVE]
            ])->get();
            $pumpTypes = PumpType::select("type AS value", "type AS label")->where([
                ['group_company_id', $groupCompanyId],
                ['status', ConstantHelper::ACTIVE]
            ])->get();
            // $pumpSizes = Pump::where([
            //     ["status", ConstantHelper::ACTIVE],
            //     ['group_company_id', $groupCompanyId]
            // ])->select('pump_capacity AS label') ->distinct() -> get();
            // foreach ($pumpSizes as &$pumpSize) {
            //     $pumpSize -> value = $pumpSize -> label;
            // }
            $customers = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label"))->where([
                ['status', ConstantHelper::ACTIVE]
            ])->whereHas('group_companies', function ($subQuery) use ($groupCompanyId) {
                $subQuery->where('group_company_id', $groupCompanyId);
            })->get();
            return array(
                'message' => 'Data returned successfully',
                'data' => array(
                    'company_locations' => $groupCompanyLocations,
                    'structural_references' => $structuralReferences,
                    'temps' => $temps,
                    'pump_types' => $pumpTypes,
                    'pump_sizes' => [],
                    'customers' => $customers
                )
            );
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }
    //Reset Orders for resetting schedule
    public function resetOrders(Request $request)
    {
        $validator = (new Validator($request))->resetOrders();
        if ($validator->fails()) {
            return redirect()->back()->with(ConstantHelper::WARNING, __("message.invalid_order_step_2"));
        }
        try {
            $company_shifts = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];
            $user_id = auth()->user()->id;

            DB::beginTransaction();
            OrderScheduleHelper::deleteUserSchedules($request->company_id, $user_id);
            $orders = Order::ByCompanyScheduleDate($request->company_id, $shift_start, $shift_end)
                ->get()->toArray();

            $published_flag = false;

            foreach ($orders as &$order) {
                if ($order['published_by'] !== null) {
                    $published_flag = true;
                    break;
                }
                $order['og_order_id'] = $order['id'];
                $order['user_id'] = auth()->user()->id;
                unset($order['created_at']);
                unset($order['updated_at']);
                unset($order['order_status']);
                unset($order['published_by']);
                unset($order['id']);
                // unset($order['site_id']);
                unset($order['in_cart']);
                unset($order['structural_reference']);
                unset($order['approval_status']);
                unset($order['customer_confirm_remarks']);
                unset($order['customer_confirmation_by']);
                unset($order['customer_confirmed_on']);
                unset($order['has_customer_confirmed']);
                unset($order['remarks']);
            }
            //Already Published orders
            if ($published_flag) {
                return redirect()->back()->with(ConstantHelper::WARNING, __("message.action_already_preformed", ['static' => __("static.publish")]));
            }
            SelectedOrder::insert($orders);
            DB::commit();
            return redirect()->route("orders.schedule.step.two", [
                'company_id' => $request->company_id,
                'schedule_date' => $request->schedule_date,
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->back()->with(ConstantHelper::ERROR, $ex->getMessage());
        }
    }
    //Show selected orders listing
    public function scheduleViewStepTwo(Request $request)
    {


        $validator = (new Validator($request))->scheduleStepTwo();
        if ($validator->fails()) {
            return redirect()->back()->with(ConstantHelper::WARNING, __("message.invalid_order_step_2"));
        }
        try {
            $company_shifts = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];
            $groupCompany = GroupCompany::find($request->company_id);


            $orders = SelectedOrder::byUserCompanyScheduleDate($request->company_id, auth()->user()->id, $shift_start, $shift_end)->get();

            foreach ($orders as $order) {

                $travelToSiteDistance = CustomerProjectSiteHelper::assignDistance($order->company_location_id, $order->site_id, 'site');
                //    dd($travelToSiteDistance);
// dump($travelToSiteDistance);
                if ((isset($travelToSiteDistance['rows'][0]['elements'][0]['distance']['value'])) && ($travelToSiteDistance['rows'][0]['elements'][0]['distance']['value'] <= 300000)) {
                    $durationInSec = $travelToSiteDistance['rows'][0]['elements'][0]['duration']['value'];
                    $durationInMinutes = round($durationInSec / 60);

                    $order->travel_to_site = intval($durationInMinutes);
                } else {
                    return redirect()->back()->with('warning', 'Order Number-' . $order->order_no . ' has customer site out of reach');
                }

                $travelToPlantDistance = CustomerProjectSiteHelper::assignDistance($order->company_location_id, $order->site_id, 'plant');

                if (isset($travelToPlantDistance['rows'][0]['elements'][0]['duration']['value'])) {
                    $durationInSec = $travelToPlantDistance['rows'][0]['elements'][0]['duration']['value'];
                    $durationInMinutes = round($durationInSec / 60, 0);
                    $order->return_to_plant = intval($durationInMinutes);
                }
                // dd($durationInMinutes);

            }


            return view("components.orders.generate_order_step_2", [
                'orders' => $orders,
                'shift_start' => $shift_start,
                'shift_end' => $shift_end,
                'groupCompany' => $groupCompany
            ]);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }
    //Step 3 view with resources
    public function scheduleViewStepThree(Request $request)
    {

        $pump_type = [];
        $pump_size = [];
        $type_capacity_pairs = [];
        $validator = (new Validator($request))->scheduleStepThree();
        if ($validator->fails()) {
            return redirect()->back()
                ->with(ConstantHelper::WARNING, __("message.invalid_order_step_2"));
        }
        try {
            $company_shifts = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];
            $groupCompany = GroupCompany::find($request->company_id);

            $orders = SelectedOrder::byUserCompanyScheduleDate($request->company_id, auth()->user()->id, $shift_start, $shift_end)->get();
            foreach ($orders as $order) {

                foreach ($order->order_pumps as $order_pump) {
                    // array_push($pump_type,$order_pump->type);
                    // array_push($pump_size,$order_pump->pump_size);
                    $type_capacity_pairs[] = [
                        'type' => $order_pump->type,
                        'qty' => $order_pump->qty,
                        'pump_capacity' => $order_pump->pump_size,
                    ];
                }
            }
            $pumps = collect();

            foreach ($type_capacity_pairs as $pair) {
                // For each unique pair, query the pumps that match both type and pump_capacity
                $matchedPumps = Pump::where('group_company_id', $request->company_id)
                    ->where('type', $pair['type'])
                    ->where('pump_capacity', $pair['pump_capacity'])
                    ->whereNotNull('operator_id')
                    // ->take($pair['qty']) // You can adjust how you limit the number of pumps as per the order_pump's qty

                    ->get();

                // Merge the pumps into the main collection
                $pumps = $pumps->merge($matchedPumps);
            }

            $groupedPumps = $pumps->groupBy(['type', 'pump_capacity']);

            $tms = TransitMixer::where("group_company_id", $request->company_id)->wherehas('drivers')->where('status', ConstantHelper::ACTIVE)->get()->groupBy('truck_capacity');

            $scheduledPumps = $pumps->pluck('id');
            // dd($scheduledPumps);
            $pumps = Pump::where("group_company_id", $request->company_id)->whereNotNull('operator_id')->where('status', ConstantHelper::ACTIVE)
                ->whereIn('id', $scheduledPumps)
                ->get()->groupBy(["type", "pump_capacity"]);
            // $pumps = Pump::whereIn("id",$pumpList) -> get() -> groupBy(["type", "pump_capacity"]);
            // dd($pumps);

            $selectedSiteLocations = SelectedOrder::where('selected', true)->pluck('company_location_id');
            // dd($siteLocations);
            $batching_plants = BatchingPlant::where("batching_plants.group_company_id", $request->company_id)->where('batching_plants.status', ConstantHelper::ACTIVE)
                ->whereIn('company_location_id', $selectedSiteLocations)
                ->join("company_locations", function ($query) {
                    $query->on("company_locations.id", "=", "batching_plants.company_location_id");
                })->select(
                    "batching_plants.id",
                    "batching_plants.plant_name",
                    "batching_plants.capacity",
                    "company_locations.location",
                    "batching_plants.company_location_id"
                )->get()->groupBy(["location", "capacity"]);

            return view("components.orders.generate_order_step_3", [
                'batching_plants' => $batching_plants,
                'pumps' => $pumps,
                'transit_mixers' => $tms,
                'groupCompany' => $groupCompany
            ]);
        } catch (Exception $ex) {
            return redirect()->back()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }
    //Update selected Orders
    public function updateSelectedOrders(Request $request)
    {

        $validator = (new Validator($request))->updateSelectedOrders();
        if ($validator->fails()) {
            return redirect()->back()
                ->with(ConstantHelper::WARNING, __("message.invalid_order_step_3"));
        }
        try {
            foreach ($request->orders as $order) {
                // dd($order);

                $selectedOrder = SelectedOrder::find($order['order_id']);


                $selectedOrder->fill(
                    [
                        'selected' => isset($order['selected']) ? true : false,
                        'interval' => $order['interval'],
                        'travel_to_site' => $order['travel_to_site'],
                        'return_to_plant' => $order['return_to_plant'],
                        'delivery_date' => Carbon::parse($request->schedule_date)->setTimeFromTimeString($order['time']),
                        'interval_deviation' => $order['interval_deviation'],
                        'pouring_time' => $order['pouring_time'],
                        'priority' => $order['priority'],
                        'flexibility' => $order['flexibility']
                    ]
                );

                $selectedOrder->save();
            }
            //ISSUE URL :ADDED BY ANKIT ON 26TH FEB FOR SIZE LIMIT IN GET URL PARAMETERS ISSUE
            //  session()->flash('schedule_data', [
            //     'schedule_date' => $request->schedule_date,
            //     'company_id' => $request->company_id,
            //     'orders' => $request->orders
            // ]);

            // return view('components.orders.generate_order_step_3');
            return redirect()->route(

                RouteConstantHelper::ORDER_SCHEDULE_STEP_3,
                [
                    'schedule_date' => $request->schedule_date,
                    'company_id' => $request->company_id
                ]

            )->with(ConstantHelper::SUCCESS, __("message.update_success", ['static' => __("static.orders")]));
        } catch (Exception $ex) {

            return redirect()->back()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }
    public function orderScheduleView(Request $request)
    {
        $validator = (new Validator($request))->orderScheduleView();
        if ($validator->fails()) {
            return redirect()->back()
                ->with(ConstantHelper::WARNING, __("message.invalid_order_step_3"));
        }
        try {
            $company_shifts = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];

            $order_shift_start = Carbon::parse($shift_start)->subDay()->format(ConstantHelper::SQL_DATE_TIME);


            $orders = SelectedOrder::with(['schedule', 'pump_schedule'])
                ->byUserCompanyScheduleDate($request->company_id, auth()->user()->id, $shift_start, $shift_end)
                // -> where("selected", true) -> orderByRaw('start_time IS NULL, start_time ASC') -> get();
                ->where("selected", true)
                ->orderBy('start_time', 'ASC')
                ->orderBy('priority', 'ASC')
                ->orderBy('quantity', 'DESC')
                ->get();

            //Batching Plant
            $schedulesBP = SelectedOrderSchedule::rightJoin("batching_plants", function ($query) {
                $query->on("batching_plants.plant_name", "=", "selected_order_schedules.batching_plant");
            })->select(
                    "batching_plants.capacity",
                    "selected_order_schedules.schedule_date",
                    "selected_order_schedules.order_no",
                    "selected_order_schedules.mix_code",
                    "selected_order_schedules.location",
                    "selected_order_schedules.trip",
                    "selected_order_schedules.batching_qty",
                    "selected_order_schedules.batching_plant",
                    "selected_order_schedules.loading_time",
                    "selected_order_schedules.loading_start",
                    "selected_order_schedules.loading_end",
                    "selected_order_schedules.id"
                )
                ->where("selected_order_schedules.group_company_id", $request->company_id)
                ->where("selected_order_schedules.user_id", auth()->user()->id)
                ->whereBetween("selected_order_schedules.loading_start", [$shift_start, $shift_end])
                ->orderBy("selected_order_schedules.loading_start")
                ->get()->toArray();

            $startTime = Carbon::parse($shift_start)->format("H:i");
            $endTime = (Carbon::parse($startTime)->addHours(39)->addMinutes(59))->format("H:i");
            // dd($orders);
            $result = OrderHelper::orderGraphData($orders->toArray(), $startTime, $endTime, $request->schedule_date, count($schedulesBP), $schedulesBP);

            //Batching Plant
            $bpScheduleGap = BatchingPlantAvailability::rightJoin("batching_plants", function ($query) {
                $query->on("batching_plants.plant_name", "=", "batching_plant_availability.plant_name");
            })->select(
                    "batching_plants.capacity",
                    "batching_plant_availability.location",
                    "batching_plant_availability.plant_name",
                    "batching_plant_availability.free_from",
                    "batching_plant_availability.free_upto",
                    "batching_plant_availability.reason",
                    "batching_plant_availability.id"
                )
                ->where("batching_plant_availability.group_company_id", $request->company_id)
                ->where("batching_plant_availability.user_id", auth()->user()->id)
                ->orderBy("batching_plant_availability.free_from")
                ->get()->toArray();



            $resultBP = BatchingPlantHelper::batchingPlantSchedule($schedulesBP, $startTime, $endTime, $request->schedule_date, $bpScheduleGap);

            //Transit Mixer
            $schedulesTM = SelectedOrderSchedule::join("transit_mixers", function ($query) {
                $query->on("transit_mixers.truck_name", "=", "selected_order_schedules.transit_mixer");
            })->select(
                    "transit_mixers.truck_capacity",
                    "selected_order_schedules.schedule_date",
                    "selected_order_schedules.order_no",
                    "selected_order_schedules.location",
                    "selected_order_schedules.trip",
                    "selected_order_schedules.batching_qty",
                    "selected_order_schedules.transit_mixer",
                    "selected_order_schedules.qc_time",
                    "selected_order_schedules.qc_start",
                    "selected_order_schedules.qc_end",
                    "selected_order_schedules.loading_time",
                    "selected_order_schedules.loading_start",
                    "selected_order_schedules.loading_end",
                    "selected_order_schedules.travel_time",
                    "selected_order_schedules.travel_start",
                    "selected_order_schedules.travel_end",
                    "selected_order_schedules.insp_time",
                    "selected_order_schedules.insp_start",
                    "selected_order_schedules.insp_end",
                    "selected_order_schedules.pouring_time",
                    "selected_order_schedules.pouring_start",
                    "selected_order_schedules.pouring_end",

                    "selected_order_schedules.waiting_time",
                    "selected_order_schedules.waiting_start",
                    "selected_order_schedules.waiting_end",

                    "selected_order_schedules.cleaning_time",
                    "selected_order_schedules.cleaning_start",
                    "selected_order_schedules.cleaning_end",
                    "selected_order_schedules.return_time",
                    "selected_order_schedules.return_start",
                    "selected_order_schedules.return_end",
                    "selected_order_schedules.id"
                )
                ->where("selected_order_schedules.group_company_id", $request->company_id)->where("selected_order_schedules.user_id", auth()->user()->id)->whereBetween("selected_order_schedules.loading_start", [$shift_start, $shift_end])->orderBy("selected_order_schedules.loading_start")
                ->orderBy("selected_order_schedules.loading_start")
                ->get();
            $uniqueSchedules = $schedulesTM->unique(function ($item) {
                return serialize($item->toArray()); // serialize entire row
            });


            $resultTM = TransitMixerHelper::transitMixersSchedule($uniqueSchedules->toArray(), $startTime, $endTime, $request->schedule_date);

            //Pumps
            $schedulesPM = SelectedOrderPumpSchedule::join("pumps", function ($query) {
                $query->on("pumps.pump_name", "=", "selected_order_pump_schedules.pump");
            })->select(
                    "pumps.pump_capacity",
                    "pumps.type",
                    "pumps.installation_time",
                    "selected_order_pump_schedules.schedule_date",
                    "selected_order_pump_schedules.order_no",
                    "selected_order_pump_schedules.location",
                    "selected_order_pump_schedules.trip",
                    "selected_order_pump_schedules.batching_qty",
                    "selected_order_pump_schedules.pump",
                    "selected_order_pump_schedules.qc_time",
                    "selected_order_pump_schedules.qc_start",
                    "selected_order_pump_schedules.qc_end",
                    "selected_order_pump_schedules.travel_time",
                    "selected_order_pump_schedules.travel_start",
                    "selected_order_pump_schedules.travel_end",
                    "selected_order_pump_schedules.insp_time",
                    "selected_order_pump_schedules.insp_start",
                    "selected_order_pump_schedules.insp_end",
                    "selected_order_pump_schedules.pouring_time",
                    "selected_order_pump_schedules.pouring_start",
                    "selected_order_pump_schedules.pouring_end",
                    "selected_order_pump_schedules.cleaning_time",
                    "selected_order_pump_schedules.cleaning_start",
                    "selected_order_pump_schedules.cleaning_end",
                    "selected_order_pump_schedules.return_time",
                    "selected_order_pump_schedules.return_start",
                    "selected_order_pump_schedules.return_end",
                    "selected_order_pump_schedules.install_time",
                    "selected_order_pump_schedules.install_start",
                    "selected_order_pump_schedules.install_end",
                    "selected_order_pump_schedules.waiting_time",
                    "selected_order_pump_schedules.waiting_start",
                    "selected_order_pump_schedules.waiting_end",
                    "selected_order_pump_schedules.id"
                )
                ->where("selected_order_pump_schedules.group_company_id", $request->company_id)
                ->where("selected_order_pump_schedules.user_id", auth()->user()->id)
                ->whereBetween("selected_order_pump_schedules.qc_start", [$shift_start, $shift_end])
                ->orderBy("selected_order_pump_schedules.pouring_start", 'asc')
                ->get()->toArray();
            $resultPM = PumpHelper::pumpsSchedule($schedulesPM, $startTime, $endTime, $request->schedule_date);
            

            return view("components.orders.order_schedule_match", [
                'result' => $result,
                'transit_mixer' => $resultTM,
                'batching_plant' => $resultBP,
                'pumps' => $resultPM,
                'schedule_preference_select' => [
                    ['label' => 'Customer Timeline', 'value' => 'customer_timeline'],
                    ['label' => 'Largest Job First', 'value' => 'largest_qty_first'],
                    ['label' => 'FCFS (First Come First Served)', 'value' => 'fcfs'],
                ]
            ]);
        } catch (Exception $ex) {
            dd($ex);
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    //Bulk Upload Orders
    public function importOrders(Request $request)
    {
        $validator = (new Validator($request))->import();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            // OrderPumpSchedule::where("group_company_id", $request -> group_company_id) -> delete();
            // OrderSchedule::where("group_company_id", $request -> group_company_id) -> delete();
            // Order::where("group_company_id", $request -> group_company_id) -> delete();

            Excel::import(new OrderImport($request->group_company_id), $request->file('excel_file'));
            return redirect()->back()->with(ConstantHelper::SUCCESS, __("message.import_success"));
        } catch (OrderImportException $ex) {
            return redirect()->back()->with(ConstantHelper::WARNING, $ex->getMessage());
        } catch (Exception $ex) {
            return redirect()->back()->with(ConstantHelper::ERROR, ($ex->getMessage()));
        }
    }
    public function importTransitMixers(Request $request)
    {
        $validator = (new Validator($request))->importTransitMixers();
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->first()
            ], 422);
        }
        try {
            Excel::import(new TransitMixerImport(), $request->file('excel_file'));
            return response()->json([
                'message' => "Import Success"
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage()
            ], 500);
        }
    }
    public function importDrivers(Request $request)
    {
        $validator = (new Validator($request))->importDrivers();
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->first()
            ], 422);
        }
        try {
            Excel::import(new TransitMixerImport(), $request->file('excel_file'));
            return response()->json([
                'message' => "Import Success"
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage()
            ], 500);
        }
    }
    public function importPumps(Request $request)
    {
        $validator = (new Validator($request))->importTransitMixers();
        if ($validator->fails()) {
            return response()->json([
                'message' => "Enter excel_file and company_id"
            ], 422);
        }
        try {
            Excel::import(new PumpImport(), $request->file('excel_file'));
            return response()->json([
                'message' => "Import Success"
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage()
            ], 500);
        }
    }
    public function generateSchedule(Request $request)
    {
        $validator = (new Validator($request))->generateSchedule();
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }
        try {

            $company_shifts = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];

            $interval_deviation = isset($request->interval_deviation) ? $request->interval_deviation : 100;
            $interval_deviation = (int) ($interval_deviation);


            // OrderScheduleHelper::initializeScheduleOld(

            $scheduleService = new ScheduleService();

            $scheduleService->initializeSchedule(

                auth()->user()->id,
                $request->company_id,
                $request->schedule_date,
                $request->transit_mixers,
                $request->pumps ?? [],
                $request->batching_plants,
                "",
                $shift_start,
                $shift_end,
                $interval_deviation
            );

            // $orderSchedule = new OrderScheduleHelperV2(
            //     auth()->user()->id,
            //     $request->company_id,
            //     $request->schedule_date,
            //     $request->transit_mixers,
            //     $request->pumps ?? [],
            //     $request->batching_plants,
            //     "",
            //     $shift_start,
            //     $shift_end,
            //     $interval_deviation
            // );

            // $orderSchedule->initializeSchedule();

            // return redirect() -> route(RouteConstantHelper::ORDER_SCHEDULE_VIEW, [
            //     'schedule_date' => $request -> schedule_date,
            //     'company_id' => $request -> company_id,
            // ]);
            return response()->json([
                'status' => ConstantHelper::SUCCESS,
                'message' => __("message.action_success", ['static' => __("static.publish")])
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'status' => ConstantHelper::SUCCESS,
                'message' => $ex
            ]);
            // return redirect() -> back() -> with(ConstantHelper::ERROR, __("message.internal_server_error"));
            dd($ex->getMessage() . $ex->getFile() . $ex->getLine());
        }
    }

    //All Orders Listing
    public function ordersOverview(Request $request)
    {
        try {
            $user = auth()->user();

            $search = $request->search;

            $group_company_ids = $user->access_rights->pluck('group_company_id');

            $groupCompanies = $user->access_rights?->pluck('group_company_id');
            $locationIds = $user->access_rights?->pluck('location_id')->toArray();
            $groupCompaniesId = $groupCompanies->toArray();
            $groupCompanies = GroupCompany::select('id', 'id AS value', 'comp_name AS label')->whereIn('id', $groupCompaniesId)
                ->where('status', ConstantHelper::ACTIVE)->orderByDesc('id')->get();


            $orders = Order::whereIn('group_company_id', $group_company_ids)
                ->with('customer_company')
                ->where('in_cart', 0)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query1) use ($search) {
                        $query1->orWhere('order_no', 'LIKE', '%' . $search . '%')
                            ->orWhereHas('customer_company', function ($query2) use ($search) {
                                $query2->where('contact_person', 'LIKE', '%' . $search . '%');
                            })
                            ->orWhereHas('customer_company', function ($query2) use ($search) {
                                $query2->where('name', 'LIKE', '%' . $search . '%');
                            })
                            ->orWhereHas('group_company', function ($query2) use ($search) {
                                $query2->where('comp_name', 'LIKE', '%' . $search . '%');
                            });
                    });
                })
                ->orderByDesc('delivery_date');

            if (($request->customer_id)) {
                $orders = $orders->where('customer_id', $request->customer_id);
            }

            if (($request->type == 'published')) {
                $orders = $orders->wherenotnull('published_by');
            }
            if (($request->type == 'pending')) {
                $orders = $orders->wherenull('published_by');
            }

            if (($request->type)) {
                $orders = $orders->where('order_status', 'pending');
            }

            if (($request->project_id)) {
                $orders = $orders->where('project_id', $request->project_id);
            }
            if (($request->site_id)) {
                $orders = $orders->where('site_id', $request->site_id);
            }
            if (($request->delivery_date)) {
                $orders = $orders->whereDate('delivery_date', $request->delivery_date);
            }
            if (($request->interval_from)) {
                $orders = $orders->where('interval', '>=', $request->interval_from);
            }
            if (($request->interval_to)) {
                $orders = $orders->where('interval', '<=', $request->interval_to);
            }
            $orders = $orders->paginate(ConstantHelper::PAGINATE)
                ->appends($request->query());

            $customers = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label"))->where([
                ['status', ConstantHelper::ACTIVE]
            ])->wherehas('group_companies', function ($query) use ($groupCompaniesId) {
                $query->whereIn('group_company_id', $groupCompaniesId);
            })->get();

            return view('components.orders.orders_overview', [
                'orders' => $orders,
                'search' => $search,
                'customers' => $customers,
                'groupCompanies' => $groupCompanies
            ]);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function publishOrders(Request $request)
    {
        $validator = (new Validator($request))->publishOrders();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        DB::beginTransaction();

        try {
            /* ===========================
             |  SHIFT TIME
             =========================== */
            $companyShifts = GroupCompanyHelper::getShiftTime(
                $request->group_company_id,
                $request->schedule_date
            );

            $shiftStart = $companyShifts['start_time'];
            $shiftEnd = $companyShifts['end_time'];

            /* ===========================
             |  FETCH SELECTED ORDERS
             =========================== */
            $orders = SelectedOrder::where('group_company_id', $request->group_company_id)
                ->where('user_id', auth()->user()->id)
                ->whereBetween('delivery_date', [$shiftStart, $shiftEnd])
                ->where('selected', true)
                ->get();

            if ($orders->isEmpty()) {
                DB::commit();
                return redirect()->back()
                    ->with(ConstantHelper::WARNING, __('No orders found to publish'));
            }

            // 🔹 Needed for FK-safe deletes
            $selectedOrderIds = $orders->pluck('id')->toArray();

            /* ===========================
             |  PROCESS EACH ORDER
             =========================== */
            foreach ($orders as $order) {

                $currentOrder = Order::find($order->og_order_id);
                if (!$currentOrder) {
                    continue;
                }

                /* ===========================
                 |  UPDATE MAIN ORDER
                 =========================== */
                $currentOrder->start_time = $order->start_time;
                $currentOrder->end_time = $order->end_time;
                $currentOrder->deviation = $order->deviation;
                $currentOrder->published_by = auth()->user()->id;
                $currentOrder->save();

                /* ===========================
                 |  ORDER SCHEDULES
                 |  (OLD WORKING LOGIC)
                 =========================== */
                $selectedSchedules = SelectedOrderSchedule::where('order_id', $order->id)
                    ->get()
                    ->toArray();

                foreach ($selectedSchedules as &$sch) {
                    unset(
                        $sch['id'],
                        $sch['created_at'],
                        $sch['updated_at'],
                        $sch['user_id']
                    );

                    // map to actual order
                    $sch['order_id'] = $currentOrder->id;
                }

                OrderSchedule::where('order_id', $currentOrder->id)->delete();

                if (!empty($selectedSchedules)) {
                    OrderSchedule::insert($selectedSchedules);
                }

                /* ===========================
                 |  PUMP SCHEDULES
                 |  (OLD WORKING LOGIC)
                 =========================== */
                $selectedPumpSchedules = SelectedOrderPumpSchedule::where('order_id', $order->id)
                    ->get()
                    ->toArray();

                foreach ($selectedPumpSchedules as &$sch) {
                    unset(
                        $sch['id'],
                        $sch['created_at'],
                        $sch['updated_at'],
                        $sch['user_id']
                    );

                    // map to actual order
                    $sch['order_id'] = $currentOrder->id;
                }

                OrderPumpSchedule::where('order_id', $currentOrder->id)->delete();

                if (!empty($selectedPumpSchedules)) {
                    OrderPumpSchedule::insert($selectedPumpSchedules);
                }
            }

            /* ===========================
             |  FK SAFE DELETE
             =========================== */
            SelectedOrderSchedule::whereIn('order_id', $selectedOrderIds)->delete();
            SelectedOrderPumpSchedule::whereIn('order_id', $selectedOrderIds)->delete();
            SelectedOrder::whereIn('id', $selectedOrderIds)->delete();

            DB::commit();

            return redirect()->route(
                RouteConstantHelper::HOME,
                ['group_company_id' => $request->group_company_id]
            )->with(
                    ConstantHelper::SUCCESS,
                    __("message.action_success", ['static' => __("static.publish")])
                );

        } catch (\Exception $ex) {

            DB::rollBack();

            logger()->error('PublishOrdersV2 Failed', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }



    public function publishOrdersV2(Request $request)
    {
        // dd($request);
        $validator = (new Validator($request))->publishOrders();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            DB::beginTransaction();

            $company_shifts = GroupCompanyHelper::getShiftTime($request->group_company_id, $request->schedule_date);
            $shift_start = $company_shifts['start_time'];
            $shift_end = $company_shifts['end_time'];

            $orders = SelectedOrder::where("group_company_id", $request->group_company_id)->where("user_id", auth()->user()->id)
                ->whereBetween("delivery_date", [$shift_start, $shift_end])
                ->where("selected", true);

            $orders_array = $orders->get()->toArray();

            // $published = Order::where("group_company_id", $request -> group_company_id)
            // -> whereBetween("delivery_date",  [$shift_start, $shift_end])
            // -> where("published_by", null) -> delete();
            // if (isset($published) && $published > 0) {

            foreach ($orders_array as $order) {
                $currentOrder = Order::find($order['og_order_id']);
                if (isset($currentOrder)) {
                    $currentOrder->start_time = $order['start_time'];
                    $currentOrder->end_time = $order['end_time'];
                    $currentOrder->deviation = $order['deviation'];
                    $currentOrder->published_by = auth()->user()->id;
                    $currentOrder->save();

                    // $inserted_order = Order::create([
                    //     'group_company_id' => $order['group_company_id'],
                    //     'order_no' => $order['order_no'],
                    //     'structural_reference_id' => $order['structural_reference_id'],
                    //     'project_id' => $order['project_id'],
                    //     'cust_product_id' => $order['cust_product_id'],
                    //     'is_technician_required' => $order['is_technician_required'],
                    //     'in_cart' => 0,
                    //     'customer_id' => $order['customer_id'],
                    //     'customer' => $order['customer'],
                    //     'project' => $order['project'],
                    //     'site' => $order['site'],
                    //     'mix_code' => $order['mix_code'],
                    //     'quantity' => $order['quantity'],
                    //     'pump_qty' => isset($order['pump_qty']) ? $order['pump_qty'] : 0,
                    //     'delivery_date' => $order['delivery_date'],
                    //     'travel_to_site' => $order['travel_to_site'],
                    //     'return_to_plant' => $order['return_to_plant'],
                    //     'interval' => $order['interval'],
                    //     'pump' => $order['pump'],
                    //     'location' => $order['location'],
                    //     'company_location_id' => $order['company_location_id'],
                    //     'deviation' => $order['deviation'],
                    //     'start_time' => $order['start_time'],
                    //     'end_time' => $order['end_time'],
                    //     'deviation_reason' => $order['deviation_reason'],
                    //     'published_by' => auth() -> user() -> id
                    // ]);

                    $schedules = SelectedOrderSchedule::where("group_company_id", $request->group_company_id)->where("user_id", auth()->user()->id)
                        ->whereBetween("loading_start", [$shift_start, $shift_end])->where("order_no", $order['order_no']);
                    $schedules_array = $schedules->get()->toArray();
                    foreach ($schedules_array as &$order_sch) {
                        unset($order_sch['id']);
                        unset($order_sch['created_at']);
                        unset($order_sch['updated_at']);
                        unset($order_sch['user_id']);
                        $order_sch['order_id'] = $currentOrder->id;
                    }
                    OrderSchedule::where('order_id', $currentOrder->id)->delete();
                    OrderSchedule::insert($schedules_array);
                    $schedules->delete();
                    $selected_order_pump_schedules = SelectedOrderPumpSchedule::where("group_company_id", $request->group_company_id)->where("user_id", auth()->user()->id)
                        ->whereBetween("qc_start", [$shift_start, $shift_end])->where("order_no", $order['order_no']);
                    $selected_order_pump_schedules_array = $selected_order_pump_schedules->get()->toArray();
                    foreach ($selected_order_pump_schedules_array as &$pump_order_sch) {
                        unset($pump_order_sch['id']);
                        unset($pump_order_sch['created_at']);
                        unset($pump_order_sch['user_id']);
                        $pump_order_sch['order_id'] = $currentOrder->id;
                    }

                    OrderPumpSchedule::where('order_id', $currentOrder->id)->delete();
                    OrderPumpSchedule::insert($selected_order_pump_schedules_array);
                    $selected_order_pump_schedules->delete();
                }
            }

            $orders->delete();

            DB::commit();

            return redirect()->route(RouteConstantHelper::HOME, ['group_company_id' => 1])->with(ConstantHelper::SUCCESS, __("message.action_success", ['static' => __("static.publish")]));
            // PUBLISH SUCCESS
            // }
            // else {
            //     // ALREADY PUBLISHED
            //     DB::commit();
            //     return redirect() -> back()->with(ConstantHelper::WARNING, __("message.action_already_preformed", ['static' => __("static.publish")]));
            // }

        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->back()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }

    public function liveScheduleView(Request $request)
    {
        try {
            $request->mergeIfMissing(['company_id' => auth()->user()->group_company_id]);
            $request->mergeIfMissing(['schedule_date' => Carbon::now()->format('Y-m-d')]);

            $shiftTimings = GroupCompanyHelper::getShiftTime($request->company_id, $request->schedule_date);
            $shiftStart = Carbon::parse($shiftTimings['start_time'])->format("H:i");
            $shiftEnd = Carbon::parse($shiftTimings['start_time'])->addHours(27)->addMinutes(59)->format("H:i");

            $slots = CommonHelper::divideTimeEqually($shiftStart, $shiftEnd, $request->schedule_date);
            $liveMarkerMargin = LiveOrderHelper::getLiveOrderMarker();

            $orders = LiveOrder::with('schedule', 'pump_schedule', 'order_temp_control')
                ->byCompanyScheduleDate($request->company_id, $shiftTimings['start_time'], $shiftTimings['end_time'])
                ->orderBy('delivery_date')->orderBy("id")->get();
            //  dd($orders);

            $ordersCollection = $orders;

            //Batching Plant
            $schedulesBP = LiveOrderSchedule::rightJoin("batching_plants", function ($query) {
                $query->on("batching_plants.plant_name", "=", "live_order_schedules.batching_plant");
            })->select(
                    "batching_plants.capacity",
                    "live_order_schedules.schedule_date",
                    "live_order_schedules.order_no",
                    "live_order_schedules.mix_code",
                    "live_order_schedules.location",
                    "live_order_schedules.trip",
                    "live_order_schedules.batching_qty",
                    "live_order_schedules.batching_plant",
                    "live_order_schedules.planned_loading_time",
                    "live_order_schedules.planned_loading_start",
                    "live_order_schedules.planned_loading_end",
                    "live_order_schedules.id"
                )
                ->where("live_order_schedules.group_company_id", $request->company_id)->whereBetween("live_order_schedules.planned_loading_start", [$shiftTimings['start_time'], $shiftTimings['end_time']])->orderBy("live_order_schedules.planned_loading_start")->get()->toArray();

            $batchingPlants = BatchingPlant::select('id AS value', 'plant_name AS label')->where([
                ['group_company_id', $request->company_id],
                ['status', ConstantHelper::ACTIVE],
            ])->get();

            // add temperature
            $temperatures = Temperature::select('id AS value', 'temp AS label')->where([
                ['group_company_id', $request->company_id],
                ['status', ConstantHelper::ACTIVE],
            ])->get();

            $assignedTripIds = LiveOrderSchedule::whereNotNull('driver_id')->whereNull('actual_return_end')->get()->pluck('transit_mixer_id')->toArray();

            $trucks = TransitMixer::select('id AS value', DB::raw("CONCAT(truck_name, ' - ', registration_no) as label"))->where([
                ['group_company_id', $request->company_id],
                ['status', ConstantHelper::ACTIVE],
            ])->whereHas('drivers')->whereNotIn('id', $assignedTripIds)->get();

            $pumps = Pump::select('id AS value', 'pump_name AS label')
                ->where([
                    ['group_company_id', $request->company_id],
                    ['status', ConstantHelper::ACTIVE],
                ])
                ->whereNotNull('operator_id') // Corrected method name
                ->get();


            if ($orders->count() > 0 && count($schedulesBP) > 0) {
                $result = LiveOrderHelper::orderGraphData($orders->toArray(), $shiftStart, $shiftEnd, $request->schedule_date, count($schedulesBP), $schedulesBP);
                return view("components.orders.live_schedule", [
                    'slots' => $slots,
                    'marker_margin' => $liveMarkerMargin,
                    'result' => $result,
                    'orderSchedules' => $ordersCollection,
                    'trucks' => $trucks,
                    'pumps' => $pumps,
                    'batching_plants' => $batchingPlants,
                    'temperatures' => $temperatures

                ]);
            } else {
                return redirect()->route('dashboard.index')->with('warning', 'No orders found');
            }
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function storeSingleOrder(Request $request)
    {

        // dd($request->all());
        // $validator = (new Validator($request))->createOrderAdmin();
        // if ($validator->fails()) {
        //     return redirect() -> back() -> withInput() -> with(ConstantHelper::WARNING, $validator -> messages() -> first());
        // }


        $validator = (new Validator($request))->createOrderAdmin();

        // dd($validator->errors()->has('checkdistance'));
        if ($validator->errors()->has('checkdistance')) {
            throw new ValidationException($validator);
        }

        if ($validator->fails()) {

            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $orderQty = $request->quantity;
            $customerProduct = CustomerProduct::find($request->id);
            $customerProject = CustomerProject::with('address')->find($customerProduct->project_id);
            $customerProjectSite = CustomerProjectSite::find($request->site_id);
            $groupCompanyLocation = CompanyLocation::find($request->company_location_id);
            $customer = Customer::find($request->customer_id);
            $structureReference = StructuralReference::find($request->structural_reference_id);
            $pouringTime = 0;
            $totalPumpQty = 0;
            if ($request->is_pump_required && $request->is_pump_required == "on") {
                $pouringTime = $structureReference?->pouring_w_pump_time;
                foreach ($request->no_of_pumps as $key => $pumpQty) {
                    $totalPumpQty += $pumpQty;
                }
            } else {
                $pouringTime = $structureReference?->pouring_wo_pump_time;
            }

            if (!$pouringTime) {
                $pouringTime = 20;
            }

            //added by Ankit Sharma(2025-02-14) for trip remaining qty less than 8 CUM 

            // $pouringTime = round($pouringTime/8,0);
            //Add up all quantity from Temp Control
            $tempControlQty = 0;
            if ($request->is_temp_required && $request->is_temp_required == "on") {
                $tempControlQty = $orderQty;
                // foreach ($request->temp_qty as $tmpCtrl) {
                //     $tempControlQty += $tmpCtrl;
                // }
            }
            if ($tempControlQty > $orderQty) {
                return redirect()->back()->with(ConstantHelper::WARNING, "Temperature control quantity cannot be greater than total order quantity");
            }
            $order = null;
            $existingQuantity = null;

            if ($request->order_id) {
                $order = Order::find($request->order_id);
                $existingQuantity = $order->quantity;
                $projectSite = CustomerProjectSite::find($request->site_id);
                if (!isset($projectSite)) {
                    return response()->json([
                        'message' => 'Select a Project Site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"Select a Project Site");
                }

                if ($projectSite->service_company_location->status != 'Active') {
                    return response()->json([
                        'message' => 'No Active location found for selected site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"No Active location found for selected site");
                }
                $company_location = $projectSite->service_company_location->location;
                $company_location_id = $projectSite->service_company_location->id;
                $group_company_id = $projectSite->service_company_location->group_company->id;
                $order->update([
                    'group_company_id' => $group_company_id,
                    'customer' => $customer->name,
                    'customer_id' => $request->customer_id,
                    'project' => $customerProject->name,
                    'project_id' => $customerProject->id,
                    'site' => $customerProjectSite?->name ?? "Jebel Ali",
                    'site_id' => $customerProjectSite?->id ?? null,
                    'mix_code' => $customerProduct->mix_code,
                    'pouring_time' => $pouringTime,
                    'quantity' => $orderQty,
                    'pump_qty' => $totalPumpQty,
                    'delivery_date' => $request->delivery_date . " " . $request->delivery_time,
                    'travel_to_site' => 20,
                    'return_to_plant' => 20,
                    'interval' => $request->interval ? $request->interval : 0,
                    'pump' => $request->pump_sizes && count($request->pump_sizes) > 0 && $request->is_pump_required ? $request->pump_sizes[0] : null,
                    'location' => $company_location,
                    'company_location_id' => $company_location_id,
                    'deviation' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'deviation_reason' => null,
                    'published_by' => null,
                    'structural_reference_id' => $request->structural_reference_id,
                    'is_temp_required' => isset($request->is_temp_required) ? 1 : 0,
                    'is_technician_required' => $request->is_tech_required ? 1 : 0,
                    'remarks' => $request->remarks ? $request->remarks : null,
                ]);
            } else {
                $projectSite = CustomerProjectSite::find($request->site_id);
                if (!isset($projectSite)) {
                    return response()->json([
                        'message' => 'Select a Project Site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"Select a Project Site");
                }
                if ($projectSite->service_company_location->status != 'Active') {
                    return response()->json([
                        'message' => 'No Active location found for selected site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"No Active location found for selected site");
                }
                $company_location = $projectSite->service_company_location->location;
                $company_location_id = $projectSite->service_company_location->id;
                $group_company_id = $projectSite->service_company_location->group_company->id;
                $previousOrders = Order::withTrashed()->where([
                    ['group_company_id', $group_company_id],
                    ['customer_id', $customer->id],
                ])->get();
                $previousOrdersCount = isset($previousOrders) && count($previousOrders) > 0 ? count($previousOrders) : 0;
                $newOrderNo = auth()->user()->id . $group_company_id . $request->customer_id . $previousOrdersCount;
                $order = Order::create([
                    'group_company_id' => $group_company_id,
                    'order_no' => $newOrderNo,
                    'cust_product_id' => $customerProduct->id,
                    'in_cart' => false,
                    'customer' => $customer->name,
                    'customer_id' => $request->customer_id,
                    'project' => $customerProject->name,
                    'project_id' => $customerProject->id,
                    'pouring_time' => $pouringTime,
                    'site' => $customerProjectSite?->service_company_location?->location ?? "JEBEL ALI",
                    'site_id' => $customerProjectSite?->id ?? null,
                    'mix_code' => $customerProduct->mix_code,
                    'quantity' => $orderQty,
                    'pump_qty' => $totalPumpQty,
                    'delivery_date' => $request->delivery_date . " " . $request->delivery_time,
                    'travel_to_site' => 20,
                    'return_to_plant' => 20,
                    'interval' => $request->interval ? $request->interval : 0,
                    'pump' => $request->pump_sizes && count($request->pump_sizes) > 0 && $request->is_pump_required ? $request->pump_sizes[0] : null,
                    'location' => $company_location,
                    'company_location_id' => $company_location_id,
                    'deviation' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'deviation_reason' => null,
                    'published_by' => null,
                    'structural_reference_id' => $request->structural_reference_id,
                    'is_temp_required' => isset($request->is_temp_required) ? 1 : 0,
                    'is_technician_required' => $request->is_tech_required ? 1 : 0,
                    'remarks' => $request->remarks ? $request->remarks : null,
                ]);
            }

            if ($request->order_id) {
                OrderTempControl::where('order_id', $order->id)->delete();
                OrderPump::where('order_id', $order->id)->delete();
                OrderCubeMould::where('order_id', $order->id)->delete();
                $resetQuantity = ($customerProduct->ordered_quantity - $existingQuantity ?? 0);
                $customerProduct->ordered_quantity = $resetQuantity + $orderQty;
                $customerProduct->save();
            } else {
                $customerProduct->ordered_quantity = $customerProduct->ordered_quantity + $orderQty;
                $customerProduct->save();
            }

            if ($request->is_temp_required && $request->is_temp_required == "on") {
                foreach ($request->temp_values as $tempKey => $tmpCtrl) {
                    OrderTempControl::create([
                        'order_id' => $order->id,
                        'temp' => $tmpCtrl,
                        // 'quantity' => $request->temp_qty[$tempKey]
                        'quantity' => $orderQty
                    ]);
                }
            }

            if ($request->is_pump_required && $request->is_pump_required == "on") {
                foreach ($request->pump_types as $pumpKey => $pump) {
                    OrderPump::create([
                        'order_id' => $order->id,
                        'capacity' => $request->pump_sizes[$pumpKey],
                        'pipe_size' => isset($request->no_of_pipes[$pumpKey]) ? $request->no_of_pipes[$pumpKey] : null,
                        'type' => $pump,
                        'quantity' => $request->no_of_pumps[$pumpKey]
                    ]);
                }
            }

            if ($request->is_cube_mould_required && $request->is_cube_mould_required == "on") {
                OrderCubeMould::create([
                    'order_id' => $order->id,
                    'mould_size' => null,
                    'quantity' => $request->cube_mould_req_quantity
                ]);
            }



            DB::commit();

            if ($request->order_id) {
                return redirect()->route('orders.overview')->with(ConstantHelper::SUCCESS, 'Order updated successfully');
            }

            return [
                "status" => 200,
                "data" => $customer,
                "redirect_url" => "/orders-overview",
                "message" => __('message.records_saved_successfully', ['static' => __('static.order')])
            ];
        } catch (Exception $ex) {
            // dd($ex);
            DB::rollBack();
            return redirect()->back()->withInput()->with(ConstantHelper::ERROR, $ex->getMessage());
        }
    }
    public function updateLiveScheduleOrder(Request $request)
    {
        // dd($request->all());
        // $validator = (new Validator($request))->createOrderAdmin();
        // if ($validator->fails()) {
        //     return redirect() -> back() -> withInput() -> with(ConstantHelper::WARNING, $validator -> messages() -> first());
        // }


        // $validator = (new Validator($request))->createOrderAdmin();

        // // dd($validator->errors()->has('checkdistance'));
        // if
        // ($validator->errors()->has('checkdistance')){
        //     throw new ValidationException($validator);

        // }

        // if ($validator->fails()) {
        //     throw new ValidationException($validator);

        // }

        try {
            DB::beginTransaction();
            $orderQty = $request->quantity;
            $order = liveOrder::find($request->order_id);
            // dd($order);
            $customerProduct = CustomerProduct::find($order->cust_product_id);
            // $customerProject = CustomerProject::with('address') -> find($customerProduct -> project_id);
            $customerProjectSite = CustomerProjectSite::find($request->site_id);
            $groupCompanyLocation = CompanyLocation::find($request->company_location_id);
            $customer = Customer::find($request->customer_id);
            $structureReference = StructuralReference::find($request->structural_reference_id);
            $pouringTime = 0;
            $totalPumpQty = 0;
            if ($request->is_pump_required && $request->is_pump_required == "on") {
                $pouringTime = $structureReference?->pouring_w_pump_time;
                foreach ($request->no_of_pumps as $key => $pumpQty) {
                    $totalPumpQty += $pumpQty;
                }
            } else {
                $pouringTime = $structureReference?->pouring_wo_pump_time;
            }

            if (!$pouringTime) {
                $pouringTime = 20;
            }
            //Add up all quantity from Temp Control
            $tempControlQty = 0;
            if ($request->is_temp_required && $request->is_temp_required == "on") {
                foreach ($request->temp_qty as $tmpCtrl) {
                    $tempControlQty += $tmpCtrl;
                }
            }
            if ($tempControlQty > $orderQty) {
                return redirect()->back()->with(ConstantHelper::WARNING, "Temperature control quantity cannot be greater than total order quantity");
            }
            $order = null;
            $existingQuantity = null;

            if ($request->order_id) {
                $order = liveOrder::find($request->order_id);
                $existingQuantity = $order->quantity;
                $projectSite = CustomerProjectSite::find($order->site_id);
                if (!isset($projectSite)) {
                    return response()->json([
                        'message' => 'Select a Project Site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"Select a Project Site");
                }

                if ($projectSite->service_company_location->status != 'Active') {
                    return response()->json([
                        'message' => 'No Active location found for selected site'
                    ], 500);
                    // return redirect() -> back()  -> with(ConstantHelper::WARNING,"No Active location found for selected site");
                }
                $company_location = $projectSite->service_company_location->location;
                $company_location_id = $projectSite->service_company_location->id;
                $group_company_id = $projectSite->service_company_location->group_company->id;
                $order->update([
                    // 'group_company_id' => $group_company_id,
                    // 'customer' => $customer -> name,
                    // 'customer_id' => $request -> customer_id,
                    // 'project' => $customerProject -> name,
                    // 'project_id' => $customerProject -> id,
                    // 'site' => $customerProjectSite ?-> name ?? "Jebel Ali",
                    // 'site_id' => $customerProjectSite ?-> id ?? null,
                    // 'mix_code' => $customerProduct -> mix_code,
                    // 'pouring_time' => $pouringTime,
                    'quantity' => $orderQty,
                    // 'pump_qty' => $totalPumpQty,
                    // 'delivery_date' => $request -> delivery_date . " " . $request -> delivery_time,
                    // 'travel_to_site' => 20,
                    // 'return_to_plant' => 20,
                    'interval' => $request->interval ? $request->interval : 0,
                    // 'pump' => $request -> pump_sizes && count($request -> pump_sizes) > 0 && $request -> is_pump_required ? $request -> pump_sizes[0] : null,
                    // 'location' => $company_location ,
                    // 'company_location_id' => $company_location_id,
                    // 'deviation' => null,
                    // 'start_time' => null,
                    // 'end_time' => null,
                    // 'deviation_reason' => null,
                    // 'published_by' => null,
                    'structural_reference_id' => $request->structural_reference_id,
                    'is_technician_required' => $request->is_tech_required ? 1 : 0,
                    // 'remarks' => $request -> remarks ? $request -> remarks : null,
                ]);
            }

            if ($request->order_id) {
                OrderTempControl::where('order_id', $order->og_order_id)->delete();
                OrderPump::where('order_id', $order->og_order_id)->delete();
                OrderCubeMould::where('order_id', $order->og_order_id)->delete();
                $resetQuantity = ($customerProduct->ordered_quantity - $existingQuantity ?? 0);
                $customerProduct->ordered_quantity = $resetQuantity + $orderQty;
                $customerProduct->save();
            } else {
                $customerProduct->ordered_quantity = $customerProduct->ordered_quantity + $orderQty;
                $customerProduct->save();
            }

            if ($request->is_temp_required && $request->is_temp_required == "on") {
                foreach ($request->temp_values as $tempKey => $tmpCtrl) {
                    OrderTempControl::create([
                        'order_id' => $order->og_order_id,
                        'temp' => $tmpCtrl,
                        'quantity' => $request->temp_qty[$tempKey]
                    ]);
                }
            }

            if ($request->is_pump_required && $request->is_pump_required == "on") {
                foreach ($request->pump_types as $pumpKey => $pump) {
                    OrderPump::create([
                        'order_id' => $order->og_order_id,
                        'capacity' => $request->pump_sizes[$pumpKey],
                        'pipe_size' => isset($request->no_of_pipes[$pumpKey]) ? $request->no_of_pipes[$pumpKey] : null,
                        'type' => $pump,
                        'quantity' => $request->no_of_pumps[$pumpKey]
                    ]);
                }
            }

            if ($request->is_cube_mould_required && $request->is_cube_mould_required == "on") {
                OrderCubeMould::create([
                    'order_id' => $order->og_order_id,
                    'mould_size' => null,
                    'quantity' => $request->cube_mould_req_quantity
                ]);
            }



            DB::commit();
            // dd($request->quantity);
            if ($request->order_id) {
                return redirect()->route('web.order.live.schedule')->with(ConstantHelper::SUCCESS, 'Order updated successfully');
            }

            return [
                "status" => 200,
                "data" => $customer,
                "redirect_url" => "/live-schedule",
                "message" => __('message.records_saved_successfully', ['static' => __('static.order')])
            ];
        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->back()->withInput()->with(ConstantHelper::ERROR, $ex->getMessage());
        }
    }

    public function updateSiteStatus(Request $request)
    {
        $validator = (new Validator($request))->updateSiteStatus();
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with(ConstantHelper::WARNING, $validator->messages()->first());
        }
        try {
            DB::beginTransaction();
            $order = Order::find($request->order_id);

            if (isset($order)) {
                $order->order_status = 'Confirmed';
                $order->has_customer_confirmed = 1;
                $order->customer_confirm_remarks = isset($request->remarks) ? $request->remarks : null;
                $order->customer_confirmation_by = auth()->user()->id;
                $order->customer_confirmed_on = Carbon::now();
                $order->save();

                if ($request->hasFile('documents')) {
                    foreach ($request->file('documents') as $document) {
                        $order->addMedia($document)->toMediaCollection(ConstantHelper::CUST_CONFIRMATION_DOC_COLLECTION_NAME);
                    }
                }
                DB::commit();
                return redirect()->back()->with('success', 'Status updated successfully');
            } else {
                DB::rollBack();
                return redirect()->back()->with('error', 'Order Not Found');
            }
        } catch (Exception $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function markAsApproveCopyOld(Request $request)
    {
        $validator = (new Validator($request))->markAsApprove();
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with(ConstantHelper::WARNING, $validator->messages()->first());
        }
        try {
            DB::beginTransaction();
            $approvalSetup = ApprovalSetup::where('group_company_id', auth()->user()->group_company_id)->first();
            //Approval Setup
            if (isset($approvalSetup)) {
                $currentUserId = auth()->user()->id;
                $approvedStatuses = OrderApproval::where([
                    ['status', ConstantHelper::ACTIVE],
                    ['order_id', $request->order_id],
                    ['approval_status', 'Approved'],
                    ['reset', 0],
                ])->get();
                $currentUserApproval = $approvedStatuses->firstWhere('approved_by', $currentUserId);
                if (isset($currentUserApproval)) {
                    DB::rollBack();
                    return redirect()->back()->with('warning', 'Already Approved');
                }
                $approvalLevels = ApprovalLevel::where('approval_setup_id', $approvalSetup->id)->orderBy('level_no')->get();
                //Check if the user has access
                $access = $approvalLevels->firstWhere('user_id', $currentUserId);
                if (isset($access)) { //Access
                    if ($approvalSetup->approval_type === "Horizontal") { //Horizontal Approval
                        OrderHelper::addApproval($request, $currentUserId, $approvalSetup, $approvalLevels, 0);
                        DB::commit();
                        return redirect()->back()->with('success', 'Approved marked successfully');
                    } else { // Vertical Approval
                        $canApprove = OrderApprovalHelper::canApproveVerticalApproval($access->level_no, $currentUserId, $approvalLevels, $approvedStatuses, $approvalSetup);
                        if ($canApprove) {
                            OrderHelper::addApproval($request, $currentUserId, $approvalSetup, $approvalLevels, 0);
                            DB::commit();
                            return redirect()->back()->with('success', 'Approved marked successfully');
                        } else {
                            DB::rollBack();
                            return redirect()->back()->with('warning', 'Cannot approve yet');
                        }
                    }
                } else { //Access Denied
                    DB::rollBack();
                    return redirect()->back()->with('warning', 'Forbidden');
                }
            } else {
                DB::rollBack();
                return redirect()->back()->with('error', "Approval setup not done yet");
            }
        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function markAsApprove(Request $request)
    {
        $validator = (new Validator($request))->markAsApprove();
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with(ConstantHelper::WARNING, $validator->messages()->first());
        }
        try {
            DB::beginTransaction();
            $approvalSetup = ApprovalSetup::where('group_company_id', auth()->user()->group_company_id)->first();
            //Approval Setup
            if (isset($approvalSetup)) {
                $currentUserId = auth()->user()->id;
                $approvedStatuses = OrderApproval::where([
                    ['status', ConstantHelper::ACTIVE],
                    ['order_id', $request->order_id],
                    ['approval_status', 'Approved'],
                    ['reset', 0],
                ])->get();
                $currentUserApproval = $approvedStatuses->firstWhere('approved_by', $currentUserId);
                if (isset($currentUserApproval)) {
                    DB::rollBack();
                    return redirect()->back()->with('warning', 'Already Approved');
                }
                $approvalLevels = ApprovalLevel::where('approval_setup_id', $approvalSetup->id)->orderBy('level_no')->get();
                //Check if the user has access
                $access = $approvalLevels->firstWhere('user_id', $currentUserId);
                if (isset($access)) { //Access
                    $canApprove = OrderApprovalHelper::canApprove($access->level_no, $currentUserId, $approvalLevels, $approvedStatuses);
                    if ($canApprove) {
                        OrderHelper::addApproval($request, $currentUserId, $approvalSetup, $approvalLevels, $access->level_no);
                        DB::commit();
                        return redirect()->back()->with('success', 'Approved marked successfully');
                    } else {
                        DB::rollBack();
                        return redirect()->back()->with('warning', 'Cannot approve yet');
                    }
                } else { //Access Denied
                    DB::rollBack();
                    return redirect()->back()->with('warning', 'Forbidden');
                }
            } else {
                DB::rollBack();
                return redirect()->back()->with('error', "Approval setup not done yet");
            }
        } catch (Exception $ex) {
            DB::rollBack();
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function exportOrders(Request $request)
    {
        // Filter data based on request parameters
        $user = auth()->user();
        $group_company_ids = $user->access_rights->pluck('group_company_id');
        $groupCompanies = $user->access_rights?->pluck('group_company_id');
        $groupCompaniesId = $groupCompanies->toArray();
        $groupCompanies = GroupCompany::select('id', 'id AS value', 'comp_name AS label')->whereIn('id', $groupCompaniesId)
            ->where('status', ConstantHelper::ACTIVE)->orderByDesc('id')->get();
        $orders = Order::whereIn('group_company_id', $group_company_ids)->orderByDesc('created_at')->get();

        return Excel::download(new OrderExport($orders), 'Orders.xlsx');
    }


    public function orderPumpDetail(Request $request)
    {

        $user = auth()->user();
        $order = LiveOrder::find($request->orderId);
        $pumptyps = $order->order->order_pumps->pluck('type')->toArray();
        $pumpsize = $order->order->order_pumps->pluck('pump_size')->toArray();

        $pumps = Pump::select('id AS value', 'pump_name AS label')->wherein('type', $pumptyps)->wherein('pump_capacity', $pumpsize)
            ->get();
        return array(
            'message' => __("message.records_returned_successfully", ['static' => __("static.pump")]),
            'data' => array(
                'pumps' => $pumps,
            )
        );
    }
}
