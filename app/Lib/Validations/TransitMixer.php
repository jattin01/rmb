<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class TransitMixer
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'truck_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('transit_mixers', 'truck_name') -> ignore($this -> request -> mixerId) -> where(function ($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'plate_no' => [
                    'required',
                    'string',
                    Rule::unique('transit_mixers', 'registration_no') -> ignore($this -> request -> mixerId)
                ],
                'driver_code' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:drivers,id'
                ],
                'capacity' => [
                    'required',
                    'numeric',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT
                ],
                'description' => [
                    'nullable',
                    'string'
                ],
            ]
        );

        return $validator;
    }


    public function storePump(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'pump_name' => [
                    'required',
                ],
                'type' => [
                    'required',
                ],
                'pump_capacity' => [
                    'required',
                ],
                'description' => [
                    'nullable',
                    'string'
                ],
                
            ]
        );

        return $validator;
    }
}
