<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Customer
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $isNewCustomer = empty($this->request->customerId);

        $validator = Validator::make($this->request->all(), [

                'name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'code' => [
                    'required',
                    'string',
                    Rule::unique('customers','code')->ignore($this->request->customerId, 'id')
                ],
                'group_companies' => [
                    'required',
                    'array',
                ],
                'group_companies.*' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'contact_person' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'email_id' => [
                    'required',
                    'string',
                   'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,

                    Rule::unique('customers','email_id')->ignore($this->request->customerId, 'id')->where(function ($query) {
                        $query -> where('group_id', auth() -> user() -> group_id);
                    })
                ],
                'mobile_no' => [
                    'required',
                    'string',
                    'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,

                    Rule::unique('customers','mobile_no')->ignore($this->request->customerId, 'id')->where(function ($query) {
                        $query -> where('group_id', auth() -> user() -> group_id);
                    })
                ],
                'address' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'image' => [
                    // Required only for new customer creation
                    $isNewCustomer ? 'required' : 'nullable',
                    'mimes:jpeg,png,jpg',
                    'max:5120'
                ],
            ]
        );

        return $validator;
    }

    public function storeProject(): ValidationValidator
    {

        $validator = Validator::make($this->request->all(), [
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customers,id'
                ],
                'project_code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('customer_projects','code')->ignore($this->request->projectId, 'id') -> where(function ($query) {
                        $query -> where('customer_id', $this -> request -> customer_id);
                    })
                ],
                'project_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('customer_projects','name')->ignore($this->request->projectId, 'id') -> where(function ($query) {
                        $query -> where('customer_id', $this -> request -> customer_id);
                    })
                ],
                'contractor_name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'project_type' => [
                    'required',
                ],
               'start_date' => [
                    'required',
                    'date'
               ],
               'end_date' => [
                    'required',
                    'date',
                    'after:start_date'
                ],
               'address' => [
                    'required',
                    'string'
                ],
                'image' => [
                    'nullable',
                    'mimes:jpeg,png,jpg',
                    'max:5120'
                ],
            ]
        );

        return $validator;
    }

    public function storeSiteAddress(): ValidationValidator
    {
        $isNewSite = empty($this->request->siteId);

        $messages = [
            'latitude.required' => 'Please select a valid location'
        ];
        $validator = Validator::make($this->request->all(), [

                'site_name' => [
                    'required',
                    'string',
                    Rule::unique('customer_project_sites','name')->ignore($this->request->siteId, 'id')
                ],
                'site_address' => [
                    'required',
                ],
                'latitude' => [
                    'required'
                ],
                'company_location_id' => [
                    'required',
                    'numeric',
                    'integer'
                ]

                ], $messages
        );

        return $validator;
    }

    public function storeCustomerProduct(): ValidationValidator
    {
        $isNewProduct = empty($this->request->productId);

        $validator = Validator::make($this->request->all(), [

                'customer_id' => [
                    'required',
                ],
                'project_id' => [
                    'required',
                ],
                'product_id' => [
                    'required',
                ],
                'total_qty' => [
                    'required',
                    'numeric',
                    'min:0'
                ]

            ]
        );

        return $validator;
    }
}
