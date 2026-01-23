<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ApprovalSetup
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
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
                'location_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:company_locations,id'
                ],
                'level_types' => [
                    'required',
                    'array'
                ],
                'level_types.*' => [
                    'required',
                    'string',
                    Rule::in(ConstantHelper::APPROVAL_LEVEL_TYPES)
                ]
            ]
        );

        return $validator;
    }
}
