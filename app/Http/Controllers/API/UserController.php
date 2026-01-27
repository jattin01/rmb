<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTeamMember;
use App\Models\CustomerTeamMemberAccessRight;
use App\Models\Driver;
use App\Models\Group;
use App\Models\GroupCompany;
use App\Models\Order;
use App\Models\RequestAccess;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\User as Validator;



class UserController extends Controller
{
    //Admin Login Function

    public function getUserDetails(Request $request)
    {
        try {
            $user = auth() -> user() -> makeHidden('status', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at', 'media');
            $personalAccessToken = PersonalAccessToken::findToken($request -> bearerToken());
            $groupCompanyId = null;
            if ($user -> user_type === ConstantHelper::USER_TYPE_CUST) {
                $customerTeamMember = CustomerTeamMember::find($personalAccessToken -> user_type_id);
                $customer = Customer::find($customerTeamMember -> customer_id);
                $groupCompanyId = auth() -> user() -> group_id;
                $user -> my_company = $customer ?-> name;
                $user -> user_name = $customerTeamMember ?-> name;
                $user -> multiple_companies = false;
                $user -> is_admin = $customerTeamMember ?-> is_admin;
                $user -> makeHidden(['customer_companies']);
                // $upcomingOrders = Order::where([
                //     ['customer_id', $customer -> id],
                //     ['status', ConstantHelper::ACTIVE],
                //     ['in_cart', 0]
                // ]) -> whereIn('project_id', $request -> project_ids) -> whereDate('delivery_date', '>', Carbon::now()) -> get();
                $user -> new_orders = 0;
                $groupCompany = GroupCompany::find($groupCompanyId);
            } else if ($user -> user_type === ConstantHelper::USER_TYPE_DRIVER) {
                $driver = Driver::find($personalAccessToken -> user_type_id);
                $groupCompanyId = $driver ?-> group_company_id;
                $user -> my_company = $driver ?-> group_company ?-> comp_name;
                $user -> new_orders = null;
                $user -> user_name = $driver ?-> name;
                $user -> multiple_companies = false;
                $user -> is_admin = false;
                $groupCompany = GroupCompany::find($groupCompanyId);
            }
            return array(
                'message' => __("message.found", ['static' => __("static.user")]),
                'data' => array(
                    'user' => $user,
                    'group_company' => array(
                        'comp_id' => $groupCompany ?-> id,
                        'comp_name' => $groupCompany ?-> comp_name,
                        'comp_icon' => $groupCompany ?-> image_url
                    )
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getFile() . $ex -> getLine());
        }
    }

    public function getCustomerUserAccessRights(Request $request)
    {
        try {
            $member = CustomerTeamMember::find($request -> team_member_id);
            
            if (isset($member)) {
                $accessRights = $member -> access_rights_mobile;
                $mobileBottomBar = $member -> is_admin ? ConstantHelper::COMPLETE_MOBILE_BOTTOM_BAR : ConstantHelper::DEFAULT_MOBILE_BOTTOM_BAR;
                if (!$member -> is_admin) {
                    foreach ($accessRights as $accessRight) {
                        if ($accessRight -> chat) {
                            $mobileBottomBar['chat'] = 1;
                        }
                        if ($accessRight -> order_view || $accessRight -> order_create || $accessRight -> order_cancel || $accessRight -> order_edit) {
                            $mobileBottomBar['home'] = 1;
                        }
                        if ($accessRight -> order_create) {
                            $mobileBottomBar['cart'] = 1;
                        }
                        if ($accessRight -> order_view || $accessRight -> order_create || $accessRight -> order_cancel || $accessRight -> order_edit) {
                            $mobileBottomBar['order'] = 1;
                        }
                    }
                }
                return array(
                    'message' => __("message.records_returned_successfully", ['static' => __("static.access_rights")]),
                    'data' => array(
                        'is_admin' => $member -> is_admin,
                        'access_rights' => $mobileBottomBar
                    )
                );
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function saveProfileImage(Request $request)
    {
        $validator = (new Validator($request))->saveProfileImage();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $user = auth() -> user();
            if ($user -> hasMedia(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE)) {
                $user -> clearMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
            }
            $user -> addMediaFromRequest('image') -> toMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
            return array(
                'message' => 'Profile Updated successfully'
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
    public function updateUserProfile(Request $request)
    {
        $validator = (new Validator($request))->updateUserProfile();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $user = auth() -> user();
            if ($request -> profile_image) {
                if ($user -> hasMedia(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE)) {
                    $user -> clearMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
                }
                $user -> addMediaFromRequest('profile_image') -> toMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
            }
            //Check User Type
            $type = $user -> user_type;
            if ($request -> phone_no) {
                $user -> phone_no = $request -> phone_no;
                if ($type === ConstantHelper::USER_TYPE_CUST) {
                    $teamMember = CustomerTeamMember::where('user_id', $user -> id) -> first();
                    if (isset($teamMember)) {
                        $teamMember -> phone_no = $request -> phone_no;
                        $teamMember -> save();
                    }
                } else if ($type === ConstantHelper::USER_TYPE_DRIVER) {
                    $driver = Driver::where('user_id', $user -> id) -> first();
                    if (isset($driver)) {
                        $driver -> phone = $request -> phone_no;
                        $driver -> save();
                    }
                } else {
                    DB::rollBack();
                    throw new ApiGenericException("Invalid User");
                }
            }
            DB::commit();
            return array(
                'message' => 'Profile Updated successfully',
            );
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function userBanner(Request $request) : JsonResponse
    {
       $authUser = Auth::user();
       
    }

    public function requestAccess(Request $request)
    {
        
        try {
            $requestAccess = RequestAccess::create([
                'name' => $request -> name,
                'mobile' => $request -> mobile,
                'email' => $request -> email
            ]);

            return array(
                'message' => 'Request Sent successfully',
                'status' => 200,
                'data' => $requestAccess,
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

}
