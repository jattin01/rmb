<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\LiveOrderScheduleReport;
use Exception;
use App\Lib\Validations\LiveOrderScheduleReport as Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LiveOrderScheduleReportController extends Controller
{
    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            $report = LiveOrderScheduleReport::create([
                'trip_id' => $request -> trip_id,
                'report_reason_id' => $request -> report_reason_id,
                'remarks' => $request -> remarks,
                'activity' => $request -> activity
            ]);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $document) {
                    $report->addMedia($document)->toMediaCollection(ConstantHelper::TRIP_REPORT_IMG_COLLECTION);
                }
            }
            return array(
                'message' => 'Reported successfully',
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
