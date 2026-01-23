<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class BatchingPlant
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'company_location_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'plant_name' => [
                    'required',
                    'string',
                    Rule::unique('batching_plants', 'plant_name') -> ignore($this -> request -> plantId) -> where(function ($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'long_name' => [
                    'required',
                    'string'
                ],
                'capacity' => [
                    'required',
                    'numeric',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT,
                    'min:'.ValidationConstantHelper::CAPACITY_MIN_LIMIT
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
