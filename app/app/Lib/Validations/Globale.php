<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Globale
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function global(): ValidationValidator
    {

        $validator = Validator::make($this->request->all(), [

                'batching_quality_inspection' => [
                    'required',
                    'integer',
                    'max:60'
                ],
                'company_location_id' => [
                    'required',
                    'integer',
                    'max:60'
                ],
                'mixture_chute_cleaning' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'site_quality_inspection' => [
                    'required',
                    'integer',
                    'max:60'
                ],
                'chute_cleaning_site' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'transite_mixture_cleaning' => [
                    'required',
                    'integer',
                    'max:60'
                ],



            ]
        );

        return $validator;
    }
    public function update(): ValidationValidator
    {

        $validator = Validator::make($this->request->all(), [

                'batching_quality_inspection' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'mixture_chute_cleaning' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'site_quality_inspection' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'chute_cleaning_site' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],
                'transite_mixture_cleaning' => [
                    'nullable',
                    'integer',
                    'max:60'
                ],



            ]
        );

        return $validator;
    }

}
