<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CustomerProduct
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customers,id'
                ],
                'project_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:customer_projects,id'
                ],
                'product_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:products,id'
                ],
                'total_qty' => [
                    'required',
                    'numeric',
                    'min:1',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT
                ]
            ]
        );
        return $validator;
    }
}
