<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Pump;
use App\Models\PumpType;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\GeneralRequest as Validator;
use Illuminate\Validation\ValidationException;

class PumpController extends Controller
{
    public function getPumpSizes(Request $request)
    {
        $validator = (new Validator($request))->companyMasterData();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $pumpSizes = Pump::where([
                ["status", ConstantHelper::ACTIVE],
                ['group_company_id', $request -> group_company_id, $request->type]
            ])->select('pump_capacity') ->distinct() -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.pumps")]),
                'data' => array(
                    'pump_sizes' => $pumpSizes,
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
