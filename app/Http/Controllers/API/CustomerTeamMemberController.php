<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerTeamMember;
use App\Models\CustomerTeamMemberAccessRight;
use App\Models\Role;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CustomerTeamMember as Validator;

class CustomerTeamMemberController extends Controller
{
    public function storeTeamMember(Request $request)
    {
        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $access_rights = $request -> access_rights;
            if (!$request -> is_admin && (count($access_rights) == 0) || !isset($access_rights)) {
                throw new ApiGenericException("Please select access rights");
            }
            DB::beginTransaction();
            if ($request -> member_id)
            {
                $member = CustomerTeamMember::find($request -> member_id);
                $member -> username = $request -> username;
                $member -> name = $request -> name;
                $member -> email = $request -> email;
                $member -> phone_no = $request -> phone_no;
                $member -> is_admin = $request -> is_admin ? 1 : 0;

                $member -> save();

                $user = User::find($member -> user_id);
                $user -> username = $request -> username;
                $user -> name = $request -> name;
                $user -> email = $request -> email;
                $user -> mobile_no = $request -> phone_no;

                $user -> save();

                if (!$request -> is_admin)
                {
                    $admin = CustomerTeamMember::select('id') -> where('is_admin', 1) -> where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> first();
                    if (!isset($admin))
                    {
                        DB::rollBack();
                        throw new ApiGenericException("Atleast one admin should be in your team");
                    }

                    CustomerTeamMemberAccessRight::where('customer_team_member_id', $member -> id) -> delete();

                    foreach ($access_rights as $access_right) {
                        if ($access_right['order_view'] || $access_right['order_create'] || $access_right['order_edit'] || $access_right['order_cancel'] || $access_right['chat']) //Atleast one right is there
                        {
                            CustomerTeamMemberAccessRight::create([
                                'customer_team_member_id' => $member -> id,
                                'customer_project_id' => $access_right['project_id'],
                                'order_view' => $access_right['order_view'] ? $access_right['order_view'] : 0,
                                'order_create' => $access_right['order_create'] ? $access_right['order_create'] : 0,
                                'order_edit' => $access_right['order_edit'] ? $access_right['order_edit'] : 0,
                                'order_cancel' => $access_right['order_cancel'] ? $access_right['order_cancel'] : 0,
                                'chat' => $access_right['chat'] ? $access_right['chat'] : 0,
                            ]);
                        }
                    }
                }
                DB::commit();
                return array(
                    'message' => __("message.records_saved_successfully", ['static' => __("static.team_member")])
                );
            }
            else 
            {
                $user = User::where('username', $request -> username) -> first();
                if (isset($user))
                {
                    DB::rollBack();
                    throw new ApiGenericException("Username already exists");
                }
                else
                {
                    $existingUser = User::where('email', $request -> email) -> where('group_id', auth() -> user() -> group_id)->first();
                    if (isset($existingUser))
                    {
                        DB::rollBack();
                        throw new ApiGenericException("User already exists with same credentials");
                    }
                    $user = new User();
                    $user->group_id = auth() -> user()->group_id;
                    $user->name = $request->name;
                    $user->username = $request->username;
                    $user->email = $request->email;
                    $user->password = bcrypt($request->phone_no);
                    $user->mobile_no = $request->phone_no;
                    $role = Role::where('name', ConstantHelper::USER_TYPE_CUST)->where('access_level', ConstantHelper::ACCESS_LEVEL_SYSTEM)->where('status', ConstantHelper::ACTIVE)->first();
                    if($role){
                        $user->role_id = $role->id;
                        $user->user_type = ConstantHelper::USER_TYPE_CUST;
                    }
                    $user->status = ConstantHelper::ACTIVE;
                    $user->save();

                    $customerTeamMember = CustomerTeamMember::create([
                        'username' => $request -> username,
                        'user_id' => $user -> id,
                        'customer_id' => $request -> customer_id,
                        'name' => $request -> name,
                        'email' => $request -> email,
                        'phone_no' => $request -> phone_no,
                        'is_admin' => $request -> is_admin ? 1 : 0
                    ]);
                    if (!$request -> is_admin)
                    {
                        $admin = CustomerTeamMember::select('id') -> where('is_admin', 1) -> where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> first();
                        if (!isset($admin))
                        {
                            DB::rollBack();
                            throw new ApiGenericException("Atleast one admin should be in your team");
                        }
                        foreach ($access_rights as $access_right) {
                            if ($access_right['order_view'] || $access_right['order_create'] || $access_right['order_edit'] || $access_right['order_cancel']) //Atleast one right is there
                            {
                                CustomerTeamMemberAccessRight::create([
                                    'customer_team_member_id' => $customerTeamMember -> id,
                                    'customer_project_id' => $access_right['project_id'],
                                    'order_view' => $access_right['order_view'] ? $access_right['order_view'] : 0,
                                    'order_create' => $access_right['order_create'] ? $access_right['order_create'] : 0,
                                    'order_edit' => $access_right['order_edit'] ? $access_right['order_edit'] : 0,
                                    'order_cancel' => $access_right['order_cancel'] ? $access_right['order_cancel'] : 0,
                                    'chat' => $access_right['chat'] ? $access_right['chat'] : 0,
                                ]);
                            }
                        }
                    }
                    DB::commit();
                    return array(
                        'message' => __("message.records_saved_successfully", ['static' => __("static.team_member")])
                    );
                }
            }  
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }
    public function getTeamMembers(Request $request)
    {
        try {
            $search = $request -> search;
            $customerTeamMembers = CustomerTeamMember::with('access_rights') -> select('id', 'username', 'user_id', 'name', 'email', 'phone_no', 'is_admin') 
            -> where('customer_id', $request -> customer_id) -> where('is_contact_person', 0) -> where('id', '!=', $request -> team_member_id) -> when($search, function ($query) use($search) {
                $query -> where('username', 'LIKE', '%'.$search.'%') -> orWhere('name', 'LIKE', '%'.$search.'%') -> orWhere('email', 'LIKE', '%'.$search.'%');
            }) -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.team_members")]),
                'data' => array(
                    'members' => $customerTeamMembers
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
