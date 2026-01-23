<?php

namespace App\Lib\Validations;

use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Carbon\Carbon;


class Product
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function store(): ValidationValidator
    {
        $validator = Validator::make($this->request->all(), [
                'code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products','code')->ignore($this->request->productId, 'id')->where(function ($query) {
                        $query -> where('group_company_id', $this -> request -> group_company_id);
                    })
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:group_companies,id'
                ],
                'product_type_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:product_types,id'
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'density' => [
                    'required',
                    'numeric',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT
                ],
                'usage' => [
                    'required',
                    'string',
                    'max:255'
                ],
                // 'batching_creation_time' => [
                //     'required',
                //     'integer',

                //     'max:60'
                // ],
                // 'temperature_creation_time' => [
                //     'integer',

                //     'max:60'
                // ],
                'image' => [
                    'nullable',
                    'mimes:jpeg,png,jpg',
                    'max:5120'
                ],
            ]
        );
        return $validator;


    }

    public function storeContent(): ValidationValidator
    {
        $isNewProductContent = empty($this->request->productContentId);

        $validator = Validator::make($this->request->all(), [
                'product_id' => [
                    'required',
                    'numeric',
                    'integer',
                    'exists:products,id'
                ],
                'content' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'quantity' => [
                    'required',
                    'numeric',
                    'max:'.ValidationConstantHelper::CAPACITY_MAX_LIMIT
                ],
            ]
        );
        return $validator;
    }

    public function storeType(): ValidationValidator
    {
        $isNewProductType = empty($this->request->typeId);

        $validator = Validator::make($this->request->all(), [
                'type' => [
                    'required',
                ],
                'group_company_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'description' => [
                    'nullable',
                    'string'
                ],

            ]
        );

        return $validator;
    }
}
