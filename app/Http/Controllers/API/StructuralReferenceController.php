<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\StructuralReference;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\GeneralRequest as Validator;
use Illuminate\Validation\ValidationException;

class StructuralReferenceController extends Controller
{
    public function index(Request $request)
    {
        $validator = (new Validator($request))->companyMasterData();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $structuralReferences = StructuralReference::select('id', 'name') 
            -> where([["status", ConstantHelper::ACTIVE], ['group_company_id', $request -> group_company_id]]) -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.structural_references")]),
                'data' => array(
                    'structural_references' => $structuralReferences
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
