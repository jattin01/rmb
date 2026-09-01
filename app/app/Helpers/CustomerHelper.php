<?php

namespace App\Helpers;
use App\Models\Customer;
use App\Models\CustomerTeamMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CustomerHelper
{
    //Function to create a new customer and add it as a user
    public function create(array $input) : void
    {
        DB::beginTransaction();
        $customer = Customer::create([
            'group_company_id' => $input['group_company_id'],
            'code' => $input['code'],
            'name' => $input['name'],
            'type' => $input['type'],
            'contact_person' => $input['contact_person'],
            'email_id' => $input['email_id'],
            'country_code_id' => $input['country_code_id'],
            'mobile_no' => $input['mobile_no'],
            'status' => $input['status']
        ]);
        $alreadyExists = User::where("email", $input['email_id']) -> first();
        if (!$alreadyExists) {
            User::create([
                'name' => $input['name'],
                'email' => $input['email_id'],
                'password' => $input['mobile_no'],
                'role_id' => 1, //Customer Role Id 
                'user_type' => 'Customer',
                'country_id' => $input['country_code_id'],
                'mobile_no' => $input['mobile_no'],
            ]);
        }
        DB::commit();
        //TO-DO : Need to add roles mapping
    }

    public static function createCustomerDefaultAdmin(Customer $customer, string $name, string $username, string $email, string $phoneNo, int $customerTeamMemberId = null)
    {
        if (isset($customerTeamMemberId)) { //UPDATE
            $member = CustomerTeamMember::find($customerTeamMemberId);
            $member -> username = $username;
            $member -> name = $name;
            $member -> email = $email;
            $member -> phone_no = $phoneNo;
            $member -> is_admin = 1;
            $member -> is_contact_person = 1;

            $member -> save();

            $user = User::find($member -> user_id);
            $user -> username = $username;
            $user -> name = $name;
            $user -> email = $email;
            $user -> mobile_no = $phoneNo;

            $user -> save(); 

            return ['status' => true, 'message' => ''];
        } else { // CREATE
            $user = User::where('username', $username) -> first();
            if (isset($user))
            {
                return ["status" => false, "message" => "Username already exists"];
            }
            else
            {
                $existingUser = User::where('email', $email) -> where('group_id', auth() -> user() -> group_id)->first();
                if (isset($existingUser))
                {
                    return ["status" => false, "message" => "User already exists with same credentials"];
                }
                $user = new User();
                $user->group_id = auth() -> user()->group_id;
                $user->name = $name;
                $user->username = $username;
                $user->email = $email;
                $user->password = bcrypt($phoneNo);
                $user->mobile_no = $phoneNo;
                $role = Role::where('name', ConstantHelper::USER_TYPE_CUST)->where('access_level', ConstantHelper::ACCESS_LEVEL_SYSTEM)->where('status', ConstantHelper::ACTIVE)->first();
                if($role){
                    $user->role_id = $role->id;
                    $user->user_type = ConstantHelper::USER_TYPE_CUST;
                }
                $user->status = ConstantHelper::ACTIVE;
                $user->save();

                $customerTeamMember = CustomerTeamMember::create([
                    'username' => $username,
                    'user_id' => $user -> id,
                    'customer_id' => $customer -> id,
                    'name' => $name,
                    'email' => $email,
                    'phone_no' => $phoneNo,
                    'is_admin' => 1,
                    'is_contact_person' => 1
                ]);

                return ['status' => true, 'message' => ''];
            }
        }
    }
}
