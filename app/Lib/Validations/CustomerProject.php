<?php

namespace App\Lib\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CustomerProject
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
                'project_code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('customer_projects','code')->ignore($this->request->projectId, 'id')
                ],
                'project_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('customer_projects','name')->ignore($this->request->projectId, 'id') -> where(function ($query) {
                        $query -> where('customer_id', $this -> request -> customer_id);
                    })
                ],
                'contractor_name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'project_type' => [
                    'required',
                ],
               'start_date' => [
                    'required',
                    'date'
               ],
               'end_date' => [
                    'required',
                    'date',
                    'after:start_date'
                ],
                'image' => [
                    'nullable',
                    'mimes:jpeg,png,jpg',
                    'max:5120'
                ],
            ]
        );
        return $validator;
    }
}
