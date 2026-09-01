<?php

namespace App\Http\Controllers\API;

use App\Helpers\OrderScheduleHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LiveOrderController extends Controller
{
    public function generateLiveSchedule(Request $request)
    {
        $request -> mergeIfMissing(['company_id' => 1]);
        $request -> mergeIfMissing(['schedule_date' => Carbon::now() -> format('Y-m-d')]);
        OrderScheduleHelper::generateLiveSchedule($request -> company_id, $request -> schedule_date);
        return response() -> json([
            'message' => 'Live Order generation success'
        ]);
    }
}
