<?php

namespace App\Lib\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class GeneralRequest
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function companyMasterData(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ],
            ]
        );
        return $validator;
    }
}
