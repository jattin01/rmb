<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CompanyLocation
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $messages = [
            'latitude.required' => 'Please select a valid location'
        ];
        $validator = Validator::make($this->request->all(), [
                'location_code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('company_locations','location')->ignore($this->request->locationId, 'id')
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'contact_person' => [
                    'nullable',
                    'string',
                    'max:255'
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
                    'min:'.ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                ],
                'address' => [
                    'required',
                    'string',
                    'max:2500'
                ],
                'latitude' => [
                    'required'
                ],
            ], $messages
        );
        return $validator;
    }
}
