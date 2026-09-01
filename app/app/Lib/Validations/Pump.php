<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Pump
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function getPumpTypes(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ],
            ]
        );

        return $validator;
    }

    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'pump_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('pumps', 'pump_name') -> ignore($this -> request -> pumpId) -> where(function($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'type' => [
                    'required',
                    'string',
                    'exists:pump_types,type'
                ],
                'pump_capacity' => [
                    'required',
                    'numeric',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT
                ],
                'description' => [
                    'nullable',
                    'string'
                ],
                'operator_id' => [
                    'required',
                    'numeric'
                ],
            ]
        );
        return $validator;
    }

}
