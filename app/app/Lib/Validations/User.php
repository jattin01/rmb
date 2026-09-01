<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class User
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'username' => [
                    'required',
                    'string',
                    'min:1',
                    'max:30',
                    Rule::unique('users','username')->ignore($this->request->userId, 'id')
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                    Rule::unique('users','email')->ignore($this->request->userId, 'id') -> where(function ($query) {
                        $query -> where('group_id', auth() -> user() -> group_id);
                    })
                ],
                'phone' => [
                    'required',
                    'string',
                    'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                    Rule::unique('users','mobile_no')->ignore($this->request->userId, 'id') -> where(function ($query) {
                        $query -> where('group_id', auth() -> user() -> group_id);
                    })
                ],
                'role_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:roles,id'
                ],
                'company_locations' => [
                    'required',
                    'array'
                ],
                'company_locations.*' => [
                    'required',
                    'numeric',
                    'integer'
                ]
            ]
        );

        return $validator;
    }

    public function locationStore(): ValidationValidator
    {
        $messages = [
            'latitude.required' => 'Please select a valid location'
        ];
        $validator = Validator::make($this->request->all(), [

                'location_code' => [
                    'required',
                    Rule::unique('company_locations','location')->ignore($this->request->locationId, 'id')
                ],

                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],

                'name' => [
                    'required',
                    'string',
                ],
                'contact_person' => [
                    'nullable',
                    'string',
                ],
                'email' => [
                    'nullable',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                ],

                'mobile' => [
                    'nullable',
                    'string',
                    'min:8',
                    'max:12'
                ],
                // 'country' => [
                //     'required'
                // ],
                // 'province' => [
                //     'required'
                // ],
                'address' => [
                    'required'
                ],
                'latitude' => [
                    'required'
                ],
            ], $messages
        );

        return $validator;
    }

    public function saveProfileImage(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'image' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif,svg',
                    'max:2048'
                ]
            ]
        );

        return $validator;
    }
    public function updateUserProfile(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'profile_image' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,gif,svg',
                    'max:2048'
                ],
                'phone_no' => [
                    'nullable',
                    'string',
                   'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                   'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                ]
            ]
        );

        return $validator;
    }

    public function updateProfileWeb(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'user_profile_name' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT,
                ],
                'user_profile_username' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::MAX_USERNAME_LIMIT,
                    Rule::unique('users', 'username') -> ignore(auth() -> user() -> id)
                ],
                'user_profile_email' => [
                    'required',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                    Rule::unique('users', 'email') -> ignore(auth() -> user() -> id) -> where(function ($query) {
                        $query -> where('user_type', ConstantHelper::USER_TYPE_ADMIN);
                    })
                ],
                'user_profile_mobile_no' => [
                    'required',
                    'string',
                    'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                    Rule::unique('users', 'mobile_no') -> ignore(auth() -> user() -> id) -> where(function ($query) {
                        $query -> where('user_type', ConstantHelper::USER_TYPE_ADMIN);
                    })
                ],
                'user_profile_profile_img' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,gif,svg',
                    'max:2048'
                ]
            ]
        );

        return $validator;
    }

}
