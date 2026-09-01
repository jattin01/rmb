<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Driver
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function updateLiveScheduleTrip(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'status' => [
                    'required',
                    'string',
                    Rule::in(ConstantHelper::TRIP_ACTIVITIES)
                ],
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:live_order_schedules,id'
                ]
            ]
        );

        return $validator;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ],
                // 'code' => [
                //     'required',
                //     'string',
                //     'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT,
                //     Rule::unique('drivers', 'code') -> ignore($this -> request -> driver_id)
                // ],
                'employee_code' => [
                    'nullable',
                    'string',
                    'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT,
                    Rule::unique('drivers', 'employee_code') -> ignore($this -> request -> driver_id)
                ],
                'name' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT
                ],
                'email_id' => [
                    'required',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                    Rule::unique('drivers', 'email_id') -> ignore($this -> request -> driver_id) -> where(function ($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'phone' => [
                    'required',
                    'string',
                    'min:'.ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                    Rule::unique('drivers', 'phone') -> ignore($this -> request -> driver_id) -> where(function ($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'username' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::MAX_USERNAME_LIMIT,
                    Rule::unique('drivers', 'username') -> ignore($this -> request -> driver_id)
                ],
                'license_no' => [
                    'required',
                    'string',
                    'min:7',
                    // 'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT
                ],
                'license_expiry' => [
                    'required',
                    'date',
                ],
                'image'=>['nullable']
            ]
        );

        return $validator;
    }
}
