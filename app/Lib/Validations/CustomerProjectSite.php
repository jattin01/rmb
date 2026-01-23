<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CustomerProjectSite
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function storeOrUpdate(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'project_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_projects,id'
                ],
                'name' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT
                ],
                'is_default' => [
                    'required',
                    'boolean',
                ],
                'address' => [
                    'required',
                    'string',
                ],
                'latitude' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::MAX_LAT_LNG_LENGTH
                ],
                'longitude' => [
                    'required',
                    'string',
                    'max:'.ValidationConstantHelper::MAX_LAT_LNG_LENGTH
                ],
                'id' => [
                    'numeric',
                    'integer',
                    'exists:customer_project_sites,id'
                ]
            ]
        );

        return $validator;
    }
    public function markAsDefault(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'project_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_projects,id'
                ],
                'id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_project_sites,id'
                ]
            ]
        );

        return $validator;
    }

    public function store(): ValidationValidator
    {
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
                'string',
            ],
            'latitude' => [
                'required',
                'string',
                'max:255'
            ],
            'project_id' => [
                'required',
                'numeric',
                'integer',
                'exists:customer_projects,id'
            ],
            'company_location_id' => [
                'required',
                'numeric',
                'integer',
                'exists:company_locations,id'
            ]
        ], $messages);
        return $validator;
    }

}
