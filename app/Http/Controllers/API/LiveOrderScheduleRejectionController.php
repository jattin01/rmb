<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\LiveOrderScheduleRejection;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\LiveOrderScheduleRejection as Validator;
use Illuminate\Validation\ValidationException;


class LiveOrderScheduleRejectionController extends Controller
{
    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $rejection = LiveOrderScheduleRejection::create([
                'trip_id' => $request -> trip_id,
                'reject_reason_id' => $request -> reject_reason_id,
                'activity' => $request -> activity,
                'remarks' => $request -> remarks,
                'quantity' => $request -> quantity
            ]);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $document) {
                    $rejection->addMedia($document)->toMediaCollection(ConstantHelper::TRIP_REJECT_IMG_COLLECTION);
                }
            }
            return array(
                'message' => 'Rejected marked successfully'
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
