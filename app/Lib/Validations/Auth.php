<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Auth
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;

    }
    public function webLogin(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'username' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT
                ],
                'password' => [
                    'required',
                    'string',
                    'min:'.ValidationConstantHelper::MIN_PASSWORD_LIMIT,
                    'max:'.ValidationConstantHelper::MAX_PASSWORD_LIMIT
                ]
            ]
        );
        return $validator;
    }
    public function appLogin(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'device_token' => [
                    'nullable',
                    'string',
                ],
                'username' => [
                    'required',
                    'string',
                    'max:30',
                    'exists:users,username'
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:20'
                ],
            ]
        );
        return $validator;
    }
    public function forgotPassword(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                    'exists:users,email'
                ],
                'username' => [
                    'nullable',
                    'string',
                    'max:30',
                    'exists:users,username'
                ],
            ]
        );
        return $validator;
    }
    public function forgotPasswordWeb(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                    'exists:users,email'
                ],
                'username' => [
                    'nullable',
                    'string',
                    'max:30',
                    'exists:users,username'
                ],
            ]
        );
        return $validator;
    }
    public function resendOTPForPassword(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:users,id'
                ],
            ]
        );
        return $validator;
    }
    public function resetPasswordWeb(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'password' => [
                    'required',
                    'string',
                    'min:'.ValidationConstantHelper::MIN_PASSWORD_LIMIT,
                    'max:'.ValidationConstantHelper::MAX_PASSWORD_LIMIT,
                    'confirmed'
                ],
                'token' => [
                    'required',
                    'string',
                ],
            ]
        );
        return $validator;
    }
    public function verifyOtpWeb(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'user_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:users,id'
                ],
                'otp_1' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:0',
                    'max:9'
                ],
                'otp_2' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:0',
                    'max:9'
                ],
                'otp_3' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:0',
                    'max:9'
                ],
                'otp_4' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:0',
                    'max:9'
                ],
            ]
        );
        return $validator;
    }

    public function getUserCompanies() : ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
            'username' => [
                'required',
                'string',
                'email',
                'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                'exists:users,email'
            ]
        ]
    );
    return $validator;
    }
    public function verifyResetPassword() : ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
            'username' => [
                'required',
                'string',
                'exists:users,username'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20'
            ]
        ]
    );
    return $validator;
    }
    public function verifyOTP() : ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
            'email_id' => [
                'nullable',
                'string',
                'email',
                'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                'exists:users,email'
            ],
            'username' => [
                'nullable',
                'string',
                'max:'.ValidationConstantHelper::MAX_USERNAME_LIMIT,
                'exists:users,username'
            ],
            'otp' => [
                'required',
                'numeric',
                'integer',
            ],
        ]
    );
    return $validator;
    }
}
