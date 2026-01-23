<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class ProductType
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'type' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('product_types', 'type') -> ignore($this -> request -> typeId)
                ],

                'batching_creation_time' => [
                    'required',
                    'integer',
                    'max:60'
                ],
                'temperature_creation_time' => [
                    'integer',
                    'max:60'
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:255'
                ],
            ]
        );
        return $validator;
    }
}
