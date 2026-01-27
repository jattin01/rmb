<?php

namespace App\Lib\Validations;

use App\Helpers\ConstantHelper;
use App\Helpers\ValidationConstantHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CustomerTeamMember
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    public function store(): ValidationValidator
    {
        $validator = Validator::make(
            $this->request->all(),
            [
                'customer_id' => [
                    'required',
                    'numeric',
                    'integer',
                ],
                'username' => [
                    'required',
                    'string',
                    Rule::unique('customer_team_members', 'username')->ignore(request()->member_id)
                ],
                'name' => [
                    'required',
                    'string',
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                ],
                'phone_no' => [
                    'required',
                    'string',
                    'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:' . ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                ],
            ]
        );

        return $validator;
    }
    public function storeFromApp(): ValidationValidator
    {
        $validator = Validator::make(
            $this->request->all(),
            [
                'username' => [
                    'required',
                    'string',
                    Rule::unique('customer_team_members', 'username')->ignore(request()->member_id)
                ],
                'name' => [
                    'required',
                    'string',
                    'max:' . ValidationConstantHelper::DEFAULT_CHARACTER_LIMIT
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'regex:'.ValidationConstantHelper::REGEX_EMAIL,
                ],
                'phone_no' => [
                    'required',
                    'string',
                    'min:' . ValidationConstantHelper::MIN_PHONE_NO_DIGITS,
                    'max:' . ValidationConstantHelper::MAX_PHONE_NO_DIGITS,
                    'regex:'.ValidationConstantHelper::REGEX_MOBILE_NUMBER,
                ],
                'is_admin' => [
                    'boolean'
                ],
                'access_rights' => [
                    'array'
                ],
                'access_rights.*.project_id' => [
                    'required',
                    'numeric',
                    'integer'
                ],
                'access_rights.*.order_view' => [
                    'required',
                    'boolean'
                ],
                'access_rights.*.order_create' => [
                    'required',
                    'boolean',
                ],
                'access_rights.*.order_edit' => [
                    'required',
                    'boolean'
                ],
                'access_rights.*.order_cancel' => [
                    'required',
                    'boolean'
                ],
                'access_rights.*.chat' => [
                    'required',
                    'boolean',
                ],
            ]
        );

        return $validator;
    }
}
