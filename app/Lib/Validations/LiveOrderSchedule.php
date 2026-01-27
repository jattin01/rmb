<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class LiveOrderSchedule
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function getLiveOrderTruckLocations(): ValidationValidator
    {

        $validator = Validator::make($this->request->all(), [
                'order_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:orders,id'
                ]
            ]
        );

        return $validator;
    }

    public function assignTrip() : ValidationValidator
    {
        $messages = [
            'batching_plant_id.required' => 'Please select a Batching Plant',
            'transit_mixer_id.required' => 'Please select a Transit Mixer',
        ];
        $validator = Validator::make($this->request->all(), [
            'trip_id' => [
                'required',
                'numeric',
                'integer',
            ],
            'transit_mixer_id' => [
                'required',
                'numeric',
                'integer',
                'exists:transit_mixers,id'
            ],
            'batching_plant_id' => [
                'required',
                'numeric',
                'integer',
                'exists:batching_plants,id'
            ],
            'pump_id' => [
                'nullable',
                'numeric',
                'integer',
                'exists:pumps,id'
            ]
            ], $messages
    );

    return $validator;
    }
}
