<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Structure
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function structure(): ValidationValidator
    {

        $validator = Validator::make($this->request->all(), [

                'name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'pouring_wo_pump_time' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:1',
                    'max:100',
                ],
                'pouring_w_pump_time' => [
                    'required',
                    'numeric',
                    'integer',
                    'min:1',
                    'max:100',
                ]
            ]
        );

        return $validator;
    }

}
