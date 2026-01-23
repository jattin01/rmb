<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class LiveOrderScheduleRejection
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'trip_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:live_order_schedules,id'
                ],
                'reject_reason_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:rejected_quantity_reasons,id'
                ],
                'remarks' => [
                    'string',
                ],
                'quantity' => [
                    'required',
                    'numeric',
                ],
                'activity' => [
                    'required',
                    'string',
                    Rule::in(ConstantHelper::TRIP_ACTIVITIES)
                ],
                'images' => [
                    'array',
                    'max:3'
                ],
                'images.*' => [
                    'required',
                    'file',
                    'mimes:jpeg,png,jpg,gif',
                    'max:2048'
                ]
            ]
        );

        return $validator;
    }

}
