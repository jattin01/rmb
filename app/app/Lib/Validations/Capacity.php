<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class Capacity
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'value' => [
                    'required',
                    'numeric',
                    'max:255',

                ],
                'uom' => [
                    'required',
                    'string',

                ],

            ]
        );
        $validator->sometimes('value', 'unique:capacities,value,NULL,id,uom,' . $this->request->input('uom'), function ($input) {
            return true;
        });

        return $validator;
    }

}
