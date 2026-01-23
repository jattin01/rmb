<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\CustomerProjectTeamMemberExport;
use App\Helpers\ConstantHelper;
use App\Models\CustomerProject;
use App\Models\CustomerTeamMember;
use App\Models\CustomerTeamMemberAccessRight;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CustomerTeamMember as Validator;
use Maatwebsite\Excel\Facades\Excel;

class CustomerTeamMemberController extends Controller
{
    public function index(Request $request, String $customerId)
{
    try {
        $search = $request->input('search');

        // Retrieve team members with optional search
        $teamMembers = CustomerTeamMember::select('id', 'user_id', 'customer_id', 'name', 'email', 'phone_no', 'is_admin', 'username', 'status')
            ->where('customer_id', $customerId)
            ->when($search, function($query) use ($search) {
                $query->where(function($query) use ($search) {
                    $query->orWhere('name', 'LIKE', '%' . $search . '%')
                          ->orWhere('email', 'LIKE', '%' . $search . '%')
                          ->orWhere('username', 'LIKE', '%' . $search . '%')
                          ->orWhere('phone_no', 'LIKE', '%' . $search . '%');
                }) -> orWhereHas('customer', function ($custQuery) use($search) {
                    $custQuery -> where('name', 'LIKE', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(ConstantHelper::PAGINATE);
        $data = [
            'teamMembers' => $teamMembers,
            'search' => $search,
        ];

        return view('components.customers.team_members.index', $data);
    } catch (Exception $ex) {
        return view('components.common.internal_error', ['message' => $ex->getMessage()]);
    }
}


    public function create(Request $request)
    {
        try {
            $projects = CustomerProject::where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> get();
            return view('components.customers.team_members.create_edit', ['projects' => $projects]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }

    }

    public function edit(Request $request)
    {
        try {
            $member = CustomerTeamMember::find($request -> member_id);
            $access_rights = CustomerTeamMemberAccessRight::select('customer_project_id', 'order_view', 'order_create', 'order_edit', 'order_cancel', 'chat') -> where('status', ConstantHelper::ACTIVE) -> where('customer_team_member_id', $member -> id) -> get();
            $projects = CustomerProject::where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> get();
            return view('components.customers.team_members.create_edit', ['member' => $member, 'accessRights' => $access_rights, 'projects' => $projects]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $access_rights = json_decode($request -> access_rights, true);
            if ($access_rights === null && json_last_error() !== JSON_ERROR_NONE && $request -> is_admin !== "on") {
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
                $member -> is_admin = $request -> is_admin == "on" ? 1 : 0;

                $member -> save();

                $user = User::find($member -> user_id);
                $user -> username = $request -> username;
                $user -> name = $request -> name;
                $user -> email = $request -> email;
                $user -> mobile_no = $request -> phone_no;

                $user -> save();

                if ($request -> is_admin !== "on")
                {
                    $admin = CustomerTeamMember::select('id') -> where('is_admin', 1) -> where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> first();
                    if (!isset($admin))
                    {
                        DB::rollBack();
                        throw new ApiGenericException("Atleast one admin should be in your team");
                    }

                    CustomerTeamMemberAccessRight::where('customer_team_member_id', $member -> id) -> delete();

                    foreach ($access_rights as $access_right) {
                        CustomerTeamMemberAccessRight::create([
                            'customer_team_member_id' => $member -> id,
                            'customer_project_id' => $access_right['project_id'],
                            'order_view' => $access_right['order_view'] ?? 0,
                            'order_create' => $access_right['order_create'] ?? 0,
                            'order_edit' => $access_right['order_edit'] ?? 0,
                            'order_cancel' => $access_right['order_cancel'] ?? 0,
                            'chat' => $access_right['chat'] ?? 0,
                        ]);
                    }
                }

                DB::commit();
                    return [
                        "status" => 200,
                        "data" => $member,
                        "redirect_url" => "/settings/customer-team/" . $request -> customer_id,
                        "message" => __('message.records_saved_successfully', ['static' => __('static.customer')])
                    ];

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
                        'is_admin' => $request -> is_admin == "on" ? 1 : 0
                    ]);

                    if ($request -> is_admin !== "on")
                    {
                        $admin = CustomerTeamMember::select('id') -> where('is_admin', 1) -> where('customer_id', $request -> customer_id) -> where('status', ConstantHelper::ACTIVE) -> first();
                        if (!isset($admin))
                        {
                            DB::rollBack();
                            throw new ApiGenericException("Atleast one admin should be in your team");
                        }

                        foreach ($access_rights as $access_right) {
                            CustomerTeamMemberAccessRight::create([
                                'customer_team_member_id' => $customerTeamMember -> id,
                                'customer_project_id' => $access_right['project_id'],
                                'order_view' => $access_right['order_view'] ?? 0,
                                'order_create' => $access_right['order_create'] ?? 0,
                                'order_edit' => $access_right['order_edit'] ?? 0,
                                'order_cancel' => $access_right['order_cancel'] ?? 0,
                                'chat' => $access_right['chat'] ?? 0,
                            ]);
                        }
                    }

                    DB::commit();
                    return [
                        "status" => 200,
                        "data" => $customerTeamMember,
                        "redirect_url" => "/settings/customer-team/" . $request -> customer_id,
                        "message" => __('message.records_saved_successfully', ['static' => __('static.customer')])
                    ];
                }
            }

        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(Request $request, String $customerId)
    {
        $teamMembers = CustomerTeamMember::select('id', 'user_id', 'customer_id', 'name', 'email', 'phone_no', 'is_admin', 'username', 'status')
            ->where('customer_id', $customerId)
        ->orderByDesc('created_at')->get();

        // Export using an Excel export class
        return Excel::download(new CustomerProjectTeamMemberExport($teamMembers), 'CustomerProjectTeamMember.xlsx');
    }
}
