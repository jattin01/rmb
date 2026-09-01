<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Resource
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function storeBatchingPlant(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_location_id' => [
                    'required',
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'plant_name' => [
                    'required',
                ],
                'long_name' => [
                    'required',
                ],
                'capacity' => [
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

    public function storeTransitMixer(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'truck_name' => [
                    'required',
                ],
                // 'group_company_ids' => [
                //     'required',
                //     'array',
                // ],
                'group_company_ids.*' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'plate_no' => [
                    'required',
                ],
                'driver_code' => [
                    'required',
                ],
                'capacity' => [
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
