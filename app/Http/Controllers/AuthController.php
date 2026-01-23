<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\RouteConstantHelper;
use App\Models\OtpRequest;
use App\Models\Role;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Exception;
use Hash;
use Illuminate\Http\Request;
use App\Lib\Validations\Auth as Validator;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;
use Psy\TabCompletion\Matcher\ConstantsMatcher;

class AuthController extends Controller
{
    public function loginView(Request $request)
    {
        //Already logged in
        if (auth() -> check()) {
            return redirect() -> route(RouteConstantHelper::DASHBOARD);
        } else {
            return view("auth.login");
        }
    }

    public function login(Request $request)
    {
        //Check validations
        $validator = (new Validator($request))->webLogin();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            $userDetails = User::where('username', $request -> username) -> orWhere('email', $request -> username) -> first();
            if (!isset($userDetails)) // User not Found
            {
                return back()->withInput()->with(ConstantHelper::WARNING, __("message.credentials_do_not_match", ['static' => __("static.user")]));
            }
            if ($userDetails -> status === ConstantHelper::INACTIVE) //Inactive User
            {
                return back()->withInput()->with(ConstantHelper::WARNING, __("message.account_status", ['status' => ConstantHelper::INACTIVE]));
            }
            $role = Role::where('id', $userDetails -> role_id) ->
            where('access_type', ConstantHelper::WEB_ACCESS_TYPE) -> where('status', ConstantHelper::ACTIVE) -> first();
            if (ucfirst($userDetails -> user_type) != ConstantHelper::USER_TYPE_ADMIN || !isset($role)) { //Invalid Role or Type
                return back()->withInput()->with(ConstantHelper::WARNING, __("message.invalid_data", ['static' => __('static.user')]));
            }
            $fieldType = $userDetails -> email === $request -> username ? 'email' : 'username';
            $user = Auth::attempt([$fieldType => $request -> username, 'password' => $request -> password]);
            if ($user) { // Login success
                return redirect() -> route(RouteConstantHelper::DASHBOARD) ->with(ConstantHelper::SUCCESS, __("auth.login_success"));
            } else { //Incorrect password
                return back()->withInput()->with(ConstantHelper::WARNING, __("auth.failed"));
            }
        } catch(Exception $ex) {
            return back()->withInput()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }

    public function logout()
    {
        try {
            auth() -> logout();
            Session::flush();
            return redirect() -> route(RouteConstantHelper::LOGIN) -> with(ConstantHelper::SUCCESS,"Logged out successfully");
        } catch (Exception $ex) {
            return redirect() -> route(RouteConstantHelper::LOGIN) -> with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }

    public function forgotPasswordView(Request $request)
    {
        try {
            return view('auth.forgot_password');
        } catch(Exception $ex) {
            return back()->withInput()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = (new Validator($request))->forgotPasswordWeb();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            if ($request -> username || $request -> email) {
                $user = null;
                if ($request -> username) {
                    $user = User::where('username', $request -> username) -> where('status', ConstantHelper::ACTIVE) -> first();
                } else {
                    $user = User::where('email', $request -> email) -> where('status', ConstantHelper::ACTIVE) -> first();
                }
                if (isset($user)) {
                    $otp = CommonHelper::generateOtp();
                    $otpRequest = new OtpRequest();
                    $otpRequest->user_id = $user -> id;
                    $otpRequest->email_id = $user -> email;
                    $otpRequest->mobile_no = $user -> mobile;
                    $otpRequest->type = ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP;
                    $otpRequest->expired_at = now()->addMinutes(ConstantHelper::OTP_EXPIRY_TIME_IN_MINS); // Set OTP expiry time
                    $otpRequest->otp = $otp;
                    $otpRequest->save();
                    return redirect() -> route(RouteConstantHelper::FORGOT_PASSWORD_OTP_VERIFY_VIEW, ['id' => $user -> id]) -> with(ConstantHelper::SUCCESS, "OTP has beed sent (" . $otp . ")");
                } else {
                    return redirect() -> back() -> with(ConstantHelper::WARNING, 'User not found');
                }
            } else {
                return redirect() -> back() -> with(ConstantHelper::WARNING, 'Either Username or Email is required');
            }
        } catch(Exception $ex) {
            return redirect() -> back() -> with(ConstantHelper::ERROR, $ex -> getMessage());
        }
    }
    public function resendOTPForPassword(Request $request)
    {
        $validator = (new Validator($request))->resendOTPForPassword();
        if ($validator->fails()) {
            return response() -> json([
                'message' => $validator -> messages() -> first()
            ], 422);
        }
        try {
                $user = User::find($request -> id);
                if (isset($user)) {
                    if ($user -> status !== ConstantHelper::ACTIVE) {
                        return response() -> json([
                            'message' => 'User is Inactive'
                        ], 422);
                    }
                    if ($user -> user_type !== ConstantHelper::USER_TYPE_ADMIN) {
                        return response() -> json([
                            'message' => 'Invalid User'
                        ], 422);
                    }
                    $otp = CommonHelper::generateOtp();
                    $otpRequest = new OtpRequest();
                    $otpRequest->user_id = $user -> id;
                    $otpRequest->email_id = $user -> email;
                    $otpRequest->mobile_no = $user -> mobile;
                    $otpRequest->type = ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP;
                    $otpRequest->expired_at = now()->addMinutes(ConstantHelper::OTP_EXPIRY_TIME_IN_MINS); // Set OTP expiry time
                    $otpRequest->otp = $otp;
                    $otpRequest->save();
                    return response() -> json([
                        'message' => "OTP has beed sent (" . $otp . ")"
                    ]);
                } else {
                    return response() -> json([
                        'message' => "User not found"
                    ], 422);
                }

        } catch(Exception $ex) {
            return response() -> json([
                'message' => $ex -> getMessage()
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = (new Validator($request))->verifyOtpWeb();
        if ($validator->fails()) {
            return redirect() -> back() -> with(ConstantHelper::WARNING, $validator -> messages() -> first());
        }
        try {
            $user = User::find($request -> user_id);
            $requestOtp = $request -> otp_1 . $request -> otp_2 . $request -> otp_3 . $request -> otp_4;
            if (isset($user)) {
                $otp = OtpRequest::where([
                    ["user_id", $user -> id],
                    ['type', ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP],
                    ['otp', $requestOtp],
                ]) -> latest() -> first();
                if (isset($otp)) {
                    $otpExpiry = Carbon::parse($otp -> expired_at);
                    if ($otpExpiry -> gt(Carbon::now())) {
                        $otp -> delete();
                        $tempTokenResult = $user->createToken(ConstantHelper::APP_TOKEN_NAME, [ConstantHelper::RESET_PASSWORD_SINGLE_TYPE_FOR_OTP]);
                        $tempTokenResult -> accessToken -> save();
                        $tempAccessToken = $tempTokenResult->plainTextToken;
                        return redirect() -> route(RouteConstantHelper::RESET_PASSWORD_VIEW, [
                            'token' => $tempAccessToken,
                        ]) -> with(ConstantHelper::SUCCESS, "OTP Verification success");
                    } else {
                        return redirect() -> back() -> withInput() -> with(ConstantHelper::WARNING,__("message.otp_expired"));
                    }
                } else {
                    return redirect() -> back() -> with(ConstantHelper::WARNING,__("message.invalid_data", ['static' => __('static.otp')]));
                }
            } else {
                return redirect() -> back() -> with(ConstantHelper::WARNING, "User not found");
            }
        } catch(Exception $ex) {
            return redirect() -> back() -> with(ConstantHelper::ERROR, $ex -> getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = (new Validator($request))->resetPasswordWeb();
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        try {
            $plainTextToken = urldecode($request -> token);
            [$tokenId, $tokenValue] = explode('|', $plainTextToken);
            $tokenRecord = PersonalAccessToken::find($tokenId);
            if (isset($tokenRecord) && hash_equals($tokenRecord->token, hash(ConstantHelper::DEFULT_HASH_ALGO_STRING, $tokenValue))) {
                $user_id = $tokenRecord -> tokenable_id;
                $user = User::find($user_id);
                if (isset($user)) {
                    $user -> password = bcrypt($request -> password);
                    $user ->save();
                    return redirect() -> route(RouteConstantHelper::LOGIN) -> with(ConstantHelper::SUCCESS, 'Password reset success');
                } else {
                    return redirect() -> route(RouteConstantHelper::LOGIN) -> with(ConstantHelper::WARNING, 'Invalid User');
                }
            } else {
                return redirect() -> route(RouteConstantHelper::LOGIN) -> with(ConstantHelper::WARNING, 'Unauthorized access');
            }
        } catch(Exception $ex) {
            return redirect() -> back() -> with(ConstantHelper::ERROR, $ex -> getMessage());
        }
    }

    public function otpVerifyView(Request $request)
    {
        try {
            if (isset($request -> id)) {
                return view('auth.verify_otp');
            } else {
                return redirect()->route(RouteConstantHelper::LOGIN);
            }
        } catch(Exception $ex) {
            return back()->withInput()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }

    public function resetPasswordView(Request $request)
    {
        try {
            if (isset($request -> token)) {
                return view('auth.reset_password');
            } else {
                return redirect()->route(RouteConstantHelper::LOGIN);
            }
        } catch(Exception $ex) {
            return back()->withInput()->with(ConstantHelper::ERROR, __("message.internal_server_error"));
        }
    }
}
