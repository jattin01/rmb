<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\CommonHelper;
use App\Models\Customer;
use App\Models\CustomerTeamMember;
use App\Models\CustomerTeamMemberAccessRight;
use App\Models\Driver;
use App\Models\DriverTransitMixer;
use App\Models\GroupCompany;
use App\Models\OtpRequest;
use App\Models\TransitMixer;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Setup\UserDevice;
use App\Helpers\ConstantHelper;
use App\Exceptions\UserNotFound;
use App\Http\Controllers\Controller;
use App\Exceptions\ApiGenericException;
use App\Lib\Validations\Auth as Validator;
use App\Models\Pump;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    // use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'verifyOtp', 'verifyResetPassword']);
    }

    /**
     * user login from API
     * @param $request ServerRequestInterface
     */
    public function loginOld(Request $request)
    {
        $validator = (new Validator($request))->appLogin();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        if (!auth()->attempt($credentials)) {
            throw new UserNotFound(__('message.credentials_do_not_match', ['static' => __('static.user')]));
        }
        //Retrieve roles of the user type
        $user = auth()->user()->load('roles');
        $role = Role::where('name', $user->user_type)->first();
        if (!isset($role)) {
            auth()->logout();
            throw new ApiGenericException(__("message.no_data_found", ['static' => __("static.roles")]));
        }
        if ($user && $role->access_type != ConstantHelper::MOBILE_ACCESS_TYPE) {
            auth()->logout();
            throw new ApiGenericException(__('message.no_login_permission', ['static' => $user->name]));
        }
        if ($user && $user->status !== ConstantHelper::ACTIVE) {
            auth()->logout();
            throw new ApiGenericException(__('message.account_status', ['status' => 'pending']));
        }
        //Get User Companies
        $companies = $user -> user_type === ConstantHelper::USER_TYPE_CUST ?  $user -> customer_companies : $user -> driver_company;
        $driverTrucks = $user -> driver ?-> transit_mixers;
        if (isset($companies) && count($companies) > 0) {
            //Company Selected or Single User Exists
            if ((isset($request -> group_company_id) && isset($request -> user_type)) || (count($companies) == 1)) {
                $groupCompanyId = $request -> group_company_id ?? $companies[0] -> group_company ?-> id;
                $userType = $user -> user_type;
                $entity = null;
                //Retrieve data according to user type
                if ($userType === ConstantHelper::USER_TYPE_CUST) {
                    $entity = Customer::where('email_id', $request -> email) -> where('status', ConstantHelper::ACTIVE) -> where('group_company_id', $groupCompanyId) -> first();
                } else if ($userType === ConstantHelper::USER_TYPE_DRIVER) {
                    $entity = Driver::where('email_id', $request -> email) -> where('status', ConstantHelper::ACTIVE) -> where('group_company_id', $groupCompanyId) -> first();
                }
                if (!isset($entity)) {
                    throw new UserNotFound(__("message.invalid_user"));
                }
                $groupCompany = GroupCompany::find($groupCompanyId);
                if ($userType === ConstantHelper::USER_TYPE_DRIVER) {
                    if (count($driverTrucks) === 1) { //Driver (Single Truck)
                        $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [$userType]);
                        $tokenResult -> accessToken -> user_type_id = $entity -> id;
                        $tokenResult -> accessToken -> user_type_sub_id = $driverTrucks[0] -> id;
                        $tokenResult -> accessToken -> save();
                        $accessToken = $tokenResult->plainTextToken;
                        $user->device_token = $request->device_token ?? null;
                        $user->save();
                        $user->access_token = $accessToken;
                        $user->company_icon = $groupCompany ?-> icon_url;
                        if ($user) {
                            (new UserDevice($request))->store();
                        }
                        $user -> makeHidden(['customer_companies', 'driver_company', 'media', 'driver']); // Removed redundant data
                        return array(
                            'message' => __('message.logged_in_successfully'),
                            'data' => array(
                                'user' => $user,
                                'multiple_companies' => false,
                                'multiple_trucks' => false,
                                'group_companies' => array(),
                                'group_trucks' => array()
                            ),
                        );
                    } else { //Driver (Multiple Truck)
                        $user->device_token = $request->device_token ?? null;
                        $user->save();
                        $trucksResponse = collect();
                        //Create token for each company user
                        foreach ($driverTrucks as &$driverTruck) {
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
                        $user -> makeHidden(['customer_companies', 'driver_company', 'media', 'driver']); // Removed redundant data
                        return array(
                            'message' => __('message.logged_in_successfully'),
                            'data' => array(
                                'user' => $user,
                                'multiple_companies' => false,
                                'multiple_trucks' => true,
                                'group_companies' => array(),
                                'group_trucks' => $trucksResponse
                            )
                        );
                    }
                } else { // Customer
                    //Create token and save other details
                    $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [$userType]);
                    $tokenResult -> accessToken -> user_type_id = $entity -> id;
                    $tokenResult -> accessToken -> save();
                    $accessToken = $tokenResult->plainTextToken;
                    $user->device_token = $request->device_token ?? null;
                    $user->save();
                    $user->access_token = $accessToken;
                    $user->company_icon = $groupCompany ?-> icon_url;
                    if ($user) {
                        (new UserDevice($request))->store();
                    }
                    $user -> makeHidden(['customer_companies', 'driver_company', 'media', 'driver']); // Removed redundant data
                    return array(
                        'message' => __('message.logged_in_successfully'),
                        'data' => array(
                            'user' => $user,
                            'multiple_companies' => false,
                            'group_companies' => array(),
                            'multiple_trucks' => false,
                            'group_trucks' => array()
                        ),
                    );

                }
            } else { // Multiple company assoication - Need to show company List (Only for Customer)
                $user->device_token = $request->device_token ?? null;
                $user->save();
                $companiesResponse = collect();
                //Create token for each company user
                foreach ($companies as &$company) {
                    $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [$company -> user_type]);
                    $tokenResult -> accessToken -> user_type_id = $company -> id;
                    $tokenResult -> accessToken -> save();
                    $accessToken = $tokenResult->plainTextToken;
                    $companiesResponse -> push([
                        'email_id' => $company -> email_id,
                        'user_type_id' => $company -> id,
                        'group_company_id' => $company -> group_company ?-> id,
                        'group_company_name' => $company -> group_company ?-> comp_name,
                        'group_company_icon' => $company -> group_company ?-> icon_url,
                        'user_type' => $user -> user_type,
                        'access_token' => $accessToken
                    ]);
                }
                $user -> makeHidden(['customer_companies', 'driver_company', 'media', 'driver']); // Removed redundant data
                return array(
                    'message' => __('message.logged_in_successfully'),
                    'data' => array(
                        'user' => $user,
                        'multiple_companies' => true,
                        'multiple_trucks' => false,
                        'group_companies' => $companiesResponse,
                        'group_trucks' => array()
                    )
                );
            }
        } else {
            throw new ApiGenericException(__("message.invalid_user"));
        }
    }
    public function login(Request $request)
    {
        $validator = (new Validator($request))->appLogin();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $credentials = [
                'username' => $request->username,
                'password' => $request->password,
            ];
            if (!auth()->attempt($credentials)) {
                throw new UserNotFound(__('message.credentials_do_not_match', ['static' => __('static.user')]));
            }
            //Retrieve roles of the user type
            $user = auth()->user()->load('roles');
            $role = Role::where('name', $user->user_type) -> where('access_type', ConstantHelper::MOBILE_ACCESS_TYPE)->where('access_level', ConstantHelper::ACCESS_LEVEL_SYSTEM)->first();
            if (!isset($role)) {
                auth()->logout();
                throw new ApiGenericException(__("message.no_data_found", ['static' => __("static.roles")]));
            }
            if ($user && $user->status !== ConstantHelper::ACTIVE) {
                auth()->logout();
                throw new ApiGenericException(__('message.account_status', ['status' => ConstantHelper::INACTIVE]));
            }
            //Customer Role
            if ($user -> user_type === ConstantHelper::USER_TYPE_CUST)
            {
                $member = CustomerTeamMember::where('user_id', $user -> id) -> first();
                if (!isset($member)) {
                    auth()->logout();
                    throw new ApiGenericException(__('message.invalid_user'));
                }

               $customer = $member->customer ;
               if($customer  -> status !== ConstantHelper::ACTIVE)
               {
                auth()->logout();
                throw new ApiGenericException(__('message.account_status', ['status' => ConstantHelper::INACTIVE]));
               }

                if ($member -> status !== ConstantHelper::ACTIVE) {
                    auth()->logout();
                    throw new ApiGenericException(__('message.account_status', ['status' => ConstantHelper::INACTIVE]));
                }
                $accessRights = CustomerTeamMemberAccessRight::select('id', 'customer_project_id', 'order_view', 'order_create', 'order_edit', 'order_cancel', 'chat') -> where('customer_team_member_id', $member -> id) -> where('status', ConstantHelper::ACTIVE) -> get();
                if (count($accessRights) === 0 && !$member -> is_admin)
                {
                    auth()->logout();
                    throw new ApiGenericException(__('message.no_data_found', ['static' => __('static.access_rights')]));
                }
                $user -> device_token = $request -> device_token;
                $user -> save();
                $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::USER_TYPE_CUST]);
                $tokenResult -> accessToken -> user_type_id = $member -> id;
                $tokenResult -> accessToken -> save();
                $accessToken = $tokenResult->plainTextToken;
                return array(
                    'message' => __('message.logged_in_successfully'),
                    'data' => array(
                        'user' => $user,
                        'access_rights' => $accessRights,
                        'access_token' => $accessToken,
                        'is_admin' => $member -> is_admin,
                        'multiple_trucks' => false,
                        'group_trucks' => collect([]),
                        'user_type_id' => $member -> customer_id
                    ),
                );
            }
            else if ($user -> user_type === ConstantHelper::USER_TYPE_DRIVER)
            {
                $driver = Driver::where('user_id', $user -> id) -> first();
                if (!isset($driver)) {
                    auth()->logout();
                    throw new ApiGenericException(__('message.invalid_user'));
                }
                if ($driver -> status !== ConstantHelper::ACTIVE) {
                    auth()->logout();
                    throw new ApiGenericException(__('message.account_status', ['status' => ConstantHelper::INACTIVE]));
                }

                // for operator
                if($driver->user_role ==='operator')
                {

                    $assignedTrucks = Pump::select('id','group_company_id','pump_name','type','description','operator_id','pump_capacity', 'status')
                     -> where('operator_id', $driver->id) -> where('status', ConstantHelper::ACTIVE) -> get();
                    if (count($assignedTrucks) === 0)
                    {
                        auth()->logout();
                        throw new ApiGenericException("No pumps assigned yet");
                    }
                    $trucksWithAccessToken = collect([]);
                    foreach ($assignedTrucks as $truck) {
                        $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::USER_TYPE_DRIVER]);
                        $tokenResult -> accessToken -> user_type_id = $driver -> id;
                        $tokenResult -> accessToken -> user_type_sub_id = $truck -> id;
                        $tokenResult -> accessToken -> save();
                        $accessToken = $tokenResult->plainTextToken;
                        $trucksWithAccessToken -> push([
                            'truck_id' => $truck ?-> id,
                            'truck_name' => $truck ?-> pump_name,
                            'truck_icon' => $truck ?-> image_url,
                            'truck_capacity' => $truck ?-> pump_capacity,
                            'truck_no' => '',
                            'user_type' => $user -> user_type,
                            'access_token' => $accessToken
                        ]);
                    }
                    $user -> device_token = $request -> device_token;
                    $user -> save();
                    return array(
                        'message' => __('message.logged_in_successfully'),
                        'data' => array(
                            'user' => $user,
                            'driver_sub_type'=>ConstantHelper::DRIVER_SUB_TYPE_PUMP,
                            'access_rights' => collect([]),
                            'access_token' => count($trucksWithAccessToken) == 1 ? $trucksWithAccessToken[0]['access_token'] : null,
                            'is_admin' => false,
                            'multiple_trucks' => count($trucksWithAccessToken) == 1 ? false : true,
                            'group_trucks' => $trucksWithAccessToken,
                            'user_type_id' => $driver -> id
                        ),
                    );

                }
                    else{

                 $transitMixerIds = DriverTransitMixer::select('driver_id', 'transit_mixer_id') -> where('driver_id', $driver -> id) -> where('status', ConstantHelper::ACTIVE) -> get() -> pluck('transit_mixer_id');
                $assignedTrucks = TransitMixer::select('id', 'group_company_id', 'truck_name', 'truck_capacity', 'registration_no') -> whereIn('id', $transitMixerIds) -> where('status', ConstantHelper::ACTIVE) -> get();
                if (count($assignedTrucks) === 0)
                {
                    auth()->logout();
                    throw new ApiGenericException("No Transit Mixers assigned yet");
                }
                $trucksWithAccessToken = collect([]);
                foreach ($assignedTrucks as $truck) {
                    $tokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::USER_TYPE_DRIVER]);
                    $tokenResult -> accessToken -> user_type_id = $driver -> id;
                    $tokenResult -> accessToken -> user_type_sub_id = $truck -> id;
                    $tokenResult -> accessToken -> save();
                    $accessToken = $tokenResult->plainTextToken;
                    $trucksWithAccessToken -> push([
                        'truck_id' => $truck ?-> id,
                        'truck_name' => $truck ?-> truck_name,
                        'truck_icon' => $truck ?-> image_url,
                        'truck_capacity' => $truck ?-> truck_capacity,
                        'truck_no' => $truck ?-> registration_no,
                        'user_type' => $user -> user_type,
                        'access_token' => $accessToken
                    ]);
                }
                $user -> device_token = $request -> device_token;
                $user -> save();
                return array(
                    'message' => __('message.logged_in_successfully'),
                    'data' => array(
                        'user' => $user,
                        'driver_sub_type'=>ConstantHelper::DRIVER_SUB_TYPE_TRUCK,
                        'access_rights' => collect([]),
                        'access_token' => count($trucksWithAccessToken) == 1 ? $trucksWithAccessToken[0]['access_token'] : null,
                        'is_admin' => false,
                        'multiple_trucks' => count($trucksWithAccessToken) == 1 ? false : true,
                        'group_trucks' => $trucksWithAccessToken,
                        'user_type_id' => $driver -> id
                    ),
                );
}
// end operator


            }
            else
            {
                auth()->logout();
                throw new ApiGenericException(__('message.invalid_user'));
            }
        } catch(Exception $ex) {
            auth() -> logout();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = (new Validator($request))->forgotPassword();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $user = null;
            $users = null;
            if (($request -> username) || ($request -> email)) {
                if (($request -> username)) { // Username
                    $user = User::where('username', $request -> username) -> where('status', ConstantHelper::ACTIVE) -> first();
                } else { //Username is not present
                    $users = User::where('email', $request -> email) -> where('status', ConstantHelper::ACTIVE) -> get();
                }
            } else {
                DB::rollBack();
                throw new ApiGenericException("Please enter either Email or Username");
            }
            $type = ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP;

            if (isset($user) || (isset($users) && count($users) > 0)) {
                if (isset($user) || count($users) == 1) {
                    $user = isset($user) ? $user : $users -> first();
                } else {
                    $type = ConstantHelper::RESET_PASSWORD_MULTIPLE_TYPE_FOR_OTP;
                }
            } else {
                DB::rollBack();
                throw new ApiGenericException(__("message.credentials_do_not_match", ['static' => __('static.user')]));
            }

            // Generate OTP
            $otp = CommonHelper::generateOtp();
            $otpRequest = new OtpRequest();
            $otpRequest->user_id = $type === ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP ? $user -> id : $users -> first() -> id;
            $otpRequest->email_id = $type === ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP ? $user -> email : $users -> first() -> email;
            $otpRequest->mobile_no = $type === ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP ? $user -> mobile : $users -> first() -> mobile;
            $otpRequest->type = $type;
            $otpRequest->expired_at = now()->addMinutes(ConstantHelper::OTP_EXPIRY_TIME_IN_MINS); // Set OTP expiry time
            $otpRequest->otp = $otp;
            $otpRequest->save();
            //Generate a temporary token to verify OTP and new password
            $authUser = User::find($type === ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP ? $user -> id : $users -> first() -> id);
            $tempTokenResult = $authUser->createToken(ConstantHelper::APP_TOKEN_NAME, [$type]);
            $tempTokenResult -> accessToken -> save();
            $tempAccessToken = $tempTokenResult->plainTextToken;

            // Send OTP notification via SMS
            // if ($request->has('mobile')) {

            //     $result = SmsHelper::sendSMSWithOTP($request->mobile, $otpRequest->otp);
            //     ##*** Check the result
            //     if (isset($result['error'])) {
            //         ##** Handle failure
            //         return response()->json(['error' => $result['error']], 500);
            //     } else {
            //         ##** Handle success
            //         // return response()->json(['message' => $result['message']], 200);
            //         $user['otp'] = $otpRequest->otp;
            //         return [
            //             'message' => __('passwords.sent_mobile_otp'),
            //             'data' => $user,
            //         ];
            //     }
            // }

            // ##** Send OTP notification via email
            // $notify = new CommonNotification([
            //     'subject' => config('app.name') . ': OTP Send Notification',
            //     'template' => 'reset-password',
            //     'data' => [
            //         'otp' => $otpRequest->otp,
            //         'full_name' => $user->name,
            //         'content' => $user->name,
            //     ]
            // ]);
            // $notify->toMail($user->email); // Sending email notification
            DB::commit();
            return [
                'message' => __('passwords.sent'),
                'data' => array(
                    'type' => $type,
                    'token' => $tempAccessToken,
                    'otp' => $otp
                ),
            ];
        } catch (Exception $ex) {
            DB::commit();
            throw new ApiGenericException($ex->getMessage());
        }
    }


    // public function verifyPassword(Request $request)
    // {
    //     $validator = (new Validator($request))->verifyResetPassword();

    //     if ($validator->fails()) {
    //         throw new ValidationException($validator);
    //     }
    //     try {
    //         $passwordReset = PasswordReset::whereToken($request->token)
    //             ->whereEmail($request->email)
    //             ->first();

    //         if (!$passwordReset) {
    //             throw new ApiGenericException(__('message.token_invalid'));
    //         }

    //         $user = User::where('email', '=', $passwordReset->email)->first();

    //         if (!$user) {
    //             throw new ApiGenericException(__('message.doesnt_exist', ['static' => __('static.user')]));
    //         }

    //         $user->password = bcrypt($request->password);
    //         $user->email_verified_at = now();
    //         $user->setRememberToken('');
    //         $user->save();

    //         // Deleting current password reset token
    //         $passwordReset->whereToken($request->token)
    //             ->whereEmail($request->email)
    //             ->delete();

    //         // Send Reset Password Confirmation Mail
    //         $user->notify(new CommonNotification([
    //             'subject' => config('app.name') . ': ' . __('message.change_password'),
    //             'template' => 'change-password',
    //             'data' => [
    //                 'full_name' => $user->name,
    //                 'mail_body' => __('message.change_password')
    //             ]
    //         ]));

    //         ##Call Notification Api

    //         $senderType = 'system';
    //         $projectId = NULL;
    //         $event = 'password_change';
    //         $senderId = User::where('user_type', ConstantHelper::SUPER_ADMIN)->value('id');
    //         $routeId = null;
    //         $receiverId = $user->id;
    //         // $route='project';
    //         ##Calling SendNotification function inside NotificationController
    //         $result = (new NotificationController)->sendNotification($event, $senderType, $senderId, $routeId, $receiverId, $projectId);

    //         $user->tokens()->where('tokenable_id', $user->id)->delete();

    //         return array(
    //             'message' => __('message.updated_successfully', ['static' => __('static.password')])
    //         );
    //     } catch (\Exception $e) {

    //         throw new ApiGenericException(__('message.unable_to_update', [
    //             'static' => __('static.password'),
    //             'reason' => $e->getMessage()
    //         ]));
    //     }
    // }

    public function verifyResetPassword(Request $request)
    {
        $validator = (new Validator($request))->verifyResetPassword();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $user = User::where('username', $request -> username) -> where('status', ConstantHelper::ACTIVE) -> first();
            if (isset($user) && $user -> email === auth() -> user() -> email) {
                $user -> password = bcrypt($request -> password);
                $user->setRememberToken('');
                $user->save();
                return array(
                    'message' => __("message.update_success", ['static' => __("static.password")]),
                    'data' => null
                );
            } else {
                throw new UserNotFound(__("message.invalid_user"));
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = (new Validator($request))->verifyOTP();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $user = null;
            $users = null;
            $type = ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP;
            if (($request -> username) || ($request -> email_id)) {
                if (($request -> username)) { // Username
                    $user = User::where('username', $request -> username) -> where('status', ConstantHelper::ACTIVE) -> first();
                } else { //Username is not present
                    $users = User::where('email', $request -> email_id) -> where('status', ConstantHelper::ACTIVE) -> get();
                }
            } else {
                throw new ApiGenericException("Please enter either Email or Username");
            }
            if (isset($user) || (isset($users) && count($users) > 0)) {
                if (isset($user) || count($users) == 1) {
                    $type = ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP;
                } else {
                    $type = ConstantHelper::RESET_PASSWORD_MULTIPLE_TYPE_FOR_OTP;
                }
                $user = $user ? $user : $users -> first();
            } else {
                throw new ApiGenericException(__("message.credentials_do_not_match", ['static' => __('static.user')]));
            }
            $otp = OtpRequest::where([
                ["email_id", $user -> email],
                ['type', $type],
                ['otp', $request -> otp],
            ]) -> latest() -> first();
            if (isset($otp)) {
                $otpExpiry = Carbon::parse($otp -> expired_at);
                if ($otpExpiry -> gt(Carbon::now())) {
                    $otp -> delete();
                    $userAccounts = collect([]);
                    if ($type === ConstantHelper::RESET_PASSWORD_MULTIPLE_TYPE_FOR_OTP) {
                        foreach($users as $singleUser)
                        {
                            $userAccounts -> push([
                                'id' => $user -> id,
                                'username' => $singleUser -> username,
                                'group' => $singleUser -> group ?-> name,
                                'group_icon' => $singleUser -> group ?-> image_url
                            ]);
                        }
                    } else {
                        $userAccounts -> push([
                            'user' => $user -> id,
                            'username' => $user -> username,
                            'group' => $user -> group ?-> name,
                            'group_icon' => $user -> group ?-> image_url
                        ]);
                    }
                    return array(
                        'message' => __("message.verify_success", ['static' => __("static.otp")]),
                        'data' => array(
                            'is_multiple_accounts' => $type === ConstantHelper::RESET_PASSWORD_MULTIPLE_TYPE_FOR_OTP ? true : false,
                            'accounts' => $userAccounts
                        )
                    );
                } else {
                    throw new ApiGenericException(__("message.otp_expired"));
                }
            } else {
                throw new ApiGenericException(__("message.invalid_data", ['static' => __('static.otp')]));
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    // public function createPassword(Request $request)
    // {
    //     $validator = (new Validator($request))->changePassword();

    //     if ($validator->fails()) {
    //         throw new ValidationException($validator);
    //     }

    //     $user = User::find($request->id);
    //     $user->password = bcrypt($request->password);
    //     $user->is_first = 1;
    //     $user->update();

    //     // Delete previous token of user
    //     // $token = request()->user()->currentAccessToken()->token;
    //     // $user->tokens()->where('tokenable_id', $user->id)->where('token','!=',$token)->delete();
    //     $notify = new CommonNotification([
    //         'subject' => config('app.name') . ': Created Password Notification',
    //         'template' => 'change-password',
    //         'data' => [
    //             'full_name' => $user->name,
    //             'content' => $user->name,
    //         ]
    //     ]);

    //     $notify->toMail($user->email);

    //     return [
    //         'message' => __('message.updated_successfully', ['static' => __('static.password')]),
    //         'data' => $user->user_type
    //     ];
    // }


    // public function loginWithOtp(Request $request)
    // {

    //     #Validation
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'otp_number' => 'required|string|max:4|min:4',
    //     ]);

    //     #Validation Logic
    //     $otpRequest = OtpRequest::where('user_id', $request->user_id)
    //         ->where('otp', $request->otp_number)
    //         ->first();

    //     $now = Carbon::now('UTC');
    //     // dd($now,$otpRequest && $now->isAfter($otpRequest->expired_at));
    //     if (!$otpRequest) {
    //         if ($request->expectsJson()) {
    //             throw new ApiGenericException(__('message.otp_invalid'));
    //         }
    //         return redirect()->back()->with('error', __('message.otp_invalid'));
    //     } elseif ($otpRequest && $now->isAfter($otpRequest->expired_at)) {
    //         if ($request->expectsJson()) {
    //             throw new ApiGenericException(__('message.otp_expired'));
    //         }
    //         return redirect()->back()->with('error', __('message.otp_expired'));
    //     }

    //     $user = User::find($request->user_id);
    //     if ($user) {
    //         // Expire The OTP
    //         $otpRequest->update([
    //             'expired_at' => Carbon::now('UTC')
    //         ]);

    //         \Auth::login($user);
    //         $user->login_at = now();
    //         $user->save();

    //         if ($request->expectsJson()) {

    //             $user = auth()->user()->load('roles');

    //             $accessToken = $user->createToken('authAppToken')->plainTextToken;
    //             $user->access_token = $accessToken;

    //             return array(
    //                 'message' => __('message.login_with_otp'),
    //                 'data' => $user
    //             );
    //         }

    //         return [
    //             'message' => __('message.login_with_otp'),
    //             'data' => $user
    //         ];
    //     }

    //     return redirect()->back()->with('error', __('message.unable_to_validate_otp'));
    // }

    public function logout(Request $request)
    {
        try {
            $request -> user() -> currentAccessToken() -> delete();
            return array(
                'message' => __("message.logged_out_successfully")
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function unauthenticated(Request $request)
    {
        throw new ApiGenericException("Unauthenticated", 401);
    }
}
