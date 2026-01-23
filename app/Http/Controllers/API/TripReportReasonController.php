<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\TripReportReason;
use Exception;
use Illuminate\Http\Request;

class TripReportReasonController extends Controller
{
    public function index(Request $request)
    {
        try {
            $reasons = TripReportReason::select("id", "reason") -> where([
                ['status', ConstantHelper::ACTIVE],
                ['group_company_id', $request -> group_company_id],
            ]) -> orWhere([
                ['status', ConstantHelper::ACTIVE],
                ['group_company_id', null],
            ]) -> get();

            return array(
                'message' => 'Reasons found',
                'data' => array(
                    'reasons' => $reasons
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
