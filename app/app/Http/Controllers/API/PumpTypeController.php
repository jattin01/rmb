<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\PumpType;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\GeneralRequest as Validator;
use Illuminate\Validation\ValidationException;

class PumpTypeController extends Controller
{
    public function index(Request $request)
    {
        $validator = (new Validator($request))->companyMasterData();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $pumpTypes = PumpType::where([
                ["status", ConstantHelper::ACTIVE],
                ['group_company_id', $request -> group_company_id]
            ])->select('type') ->distinct() -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.pumps")]),
                'data' => array(
                    'pump_structure_types' => $pumpTypes
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
