<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Helpers\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerProject;
use App\Models\CustomerTeamMemberAccessRight;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function switchGroupCompany(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanies = $user -> customer_companies;
            if (count($groupCompanies) <= 1) {
                throw new ApiGenericException("Multiple suppliers does not exists for this customer");
            } else {
                $groupCompaniesData = collect();
                foreach ($groupCompanies as $groupCompany) {
                    $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::USER_TYPE_CUST]);
                    $tokenResult -> accessToken -> user_type_id = $groupCompany -> id;
                    $tokenResult -> accessToken -> save();
                    $accessToken = $tokenResult->plainTextToken;
                    $groupCompaniesData -> push([
                        'email_id' => $user -> email,
                        'user_type_id' => $groupCompany -> id,
                        'group_company_id' => $groupCompany ?-> group_company ?-> id,
                        'group_company_name' => $groupCompany ?-> group_company ?-> comp_name,
                        'group_company_icon' => $groupCompany ?-> group_company ?-> icon_url,
                        'user_type' => ConstantHelper::USER_TYPE_CUST,
                        'access_token' => $accessToken
                    ]);
                }
                return array(
                    'message' => 'Companies retrieved successfully',
                    'data' => array(
                        'group_companies' => $groupCompaniesData
                    )
                );
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getAccessRights(Request $request)
    {
        try {
            $accessRights = CustomerTeamMemberAccessRight::select('id', 'customer_team_member_id', 'customer_project_id', 'order_view', 'order_create', 'order_edit', 'order_cancel', 'chat') -> where('customer_team_member_id', $request -> team_member_id) -> where('status', ConstantHelper::ACTIVE) -> get();
            return array(
                'message' => __("message.found", ['static' => __("static.access_rights")]),
                'data' => array(
                    'access_rights' => $accessRights
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function dashboard(Request $request)
    {
        try {
            $customerProjects = CustomerProject::with('mobile_user_access_right') -> select('id', 'customer_id', 'code', 'name', 'contractor_name', 'type', 'start_date', 'end_date') -> where([
                ["customer_id", $request -> customer_id],
                ['status', ConstantHelper::ACTIVE],
                ['start_date', '<=', Carbon::now()],
                ['end_date', '>=', Carbon::now()],
                ]) -> when(!$request -> is_user_admin, function ($query) use($request) {
                    $query -> whereIn('id', $request -> project_ids);
                }) -> with('address') -> limit(5) -> get();
            $upcomingOrders = OrderHelper::getSortedCustomerUpcomingOrders($request -> customer_id, $request -> is_user_admin, $request -> project_ids, '');
            $ongoingOrders = OrderHelper::getCustomerOngoingOrders($request -> customer_id, $request -> is_user_admin, $request -> project_ids, '');

            foreach ($upcomingOrders as &$upcomingOrder) {
                $upcomingOrder = OrderHelper::appendKeysToOrderForMobileUi(ConstantHelper::UPCOMING_ORDERS, $upcomingOrder);            
            }
            foreach ($ongoingOrders as &$ongoingOrder) {
                $ongoingOrder = OrderHelper::appendKeysToOrderForMobileUi(ConstantHelper::LIVE_ORDERS, $ongoingOrder);            
            }

            return array(
                'message' => __("message.found", ['static' => __("static.projects")]),
                'data' => array(
                    'projects' => $customerProjects,
                    'ongoing_orders' => $ongoingOrders,
                    'upcoming_orders' => $upcomingOrders
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
