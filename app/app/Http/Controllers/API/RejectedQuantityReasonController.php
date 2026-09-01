<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\RejectedQuantityReason;
use Exception;
use Illuminate\Http\Request;

class RejectedQuantityReasonController extends Controller
{
    public function index(Request $request)
    {
        try {
            $reasons = RejectedQuantityReason::select("id", "reason") -> where([
                ['group_company_id', $request -> group_company_id],
                ['status', ConstantHelper::ACTIVE]
            ]) -> get();
            return array(
                'message' => "Reasons retrieved successfully",
                'data' => array(
                    'reasons' => $reasons
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
