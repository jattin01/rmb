<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\UserExport;
use App\Helpers\ConstantHelper;
use App\Models\CompanyLocation;
use App\Models\GroupCompany;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAccessRight;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\User as Validator;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->search;
            $users = User::select('id', 'name', 'username', 'mobile_no', 'email', 'role_id', 'status') ->when($search, function($query)use($search){
                        $query->orWhere('name', 'LIKE', '%'.$search.'%');
                        $query->orWhere('email', 'LIKE', '%'.$search.'%');
                        $query->orWhere('mobile_no', 'LIKE', '%'.$search.'%');
                        $query->orWhere('username', 'LIKE', '%'.$search.'%');
                    }) -> whereHas('role', function ($query) {
                        $query -> where('access_level', ConstantHelper::ACCESS_LEVEL_USER);
                    }) -> where('group_id', auth() -> user() -> group_id) -> orderByDesc('created_at')
                    ->paginate(ConstantHelper::PAGINATE) ->appends(['search' => $search]);
            $data = [
                'users' => $users,
                'search' => $search,
            ];
            return view('components.settings.users.index', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);

        }
    }
    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $accessCollection = $user -> access_rights;
            $groupCompanyIds = [];
            $locationIds = [];
            if (isset($accessCollection) && count($accessCollection) > 0)
            {
                $groupCompanyIds = $accessCollection -> pluck('group_company_id');
                $locationIds = $accessCollection -> pluck('location_id');
            }
            $roles = Role::select('id', 'name') -> where('group_id', $user -> group_id) -> where("access_level", ConstantHelper::ACCESS_LEVEL_USER) -> where('status', ConstantHelper::ACTIVE) -> get();
            $companies = GroupCompany::select('id', 'comp_name') -> whereIn('id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> with('company_locations', function ($query) use($locationIds) {
                $query -> whereIn('id', $locationIds) -> where('status', ConstantHelper::ACTIVE);
            }) -> get();
            $data = [
                'roles' => $roles,
                'groupCompanies' => $companies,
            ];
            return view('components.settings.users.create_edit', $data);
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
            DB::beginTransaction();
            $authUser = auth() -> user();
            $user = null;
            if($request->userId){
                // Update User
                $user = User::find($request->userId);
                if($user){
                    $user->name = $request->name;
                    $user->email = $request->email;
                    $user->mobile_no = $request->phone;
                    $user->username = $request->username;
                    $user->status = $request->input('status', 'Inactive');
                    $user->save();
                }
            } else {
                // Save Customer in Users
                $user = User::create([
                    'group_id' => $authUser -> group_id,
                    'username' => $request -> username,
                    'user_type' => ConstantHelper::USER_TYPE_ADMIN,
                    'country_id' => null,
                    'name' => $request -> name,
                    'email' => $request -> email,
                    'password' => bcrypt($request -> phone),
                    'mobile_no' => $request -> phone,
                    'role_id' => $request -> role_id,
                    'status' => $request->input('status', ConstantHelper::INACTIVE)
                ]);
            }
            //Save or update company access
            foreach($request -> company_locations as $locationId)
                {
                    $location = CompanyLocation::find($locationId);
                    if (isset($location) && isset($location -> group_company) && isset($location -> group_company -> group))
                    {
                        UserAccessRight::updateOrCreate(
                            [
                                'user_id' => $user -> id,
                                'location_id' => $location -> id
                            ],
                            [
                                'group_id' => $location -> group_company ?-> group -> id,
                                'group_company_id' => $location -> group_company ?-> id,
                                'status' => ConstantHelper::ACTIVE
                            ]
                        );
                    }
                }
            UserAccessRight::where('user_id', $user -> id) -> whereNotIn('location_id', $request -> company_locations) -> delete();
            DB::commit();
            return [
                "status" => 200,
                "data" => $user,
                "message" => __('message.records_saved_successfully', ['static' => __('static.user')])
            ];

        }catch (\Throwable $th) {
            DB::rollBack();
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $editUser = User::where('id', $request->userId)->where('group_id', auth() -> user() -> group_id)->first();
            $user = auth() -> user();
            $accessCollection = $user -> access_rights;
            $groupCompanyIds = [];
            $locationIds = [];
            if (isset($accessCollection) && count($accessCollection) > 0)
            {
                $groupCompanyIds = $accessCollection -> pluck('group_company_id');
                $locationIds = $accessCollection -> pluck('location_id');
            }
            $roles = Role::select('id', 'name') -> where('group_id', $user -> group_id) -> where("access_level", ConstantHelper::ACCESS_LEVEL_USER) -> where('status', ConstantHelper::ACTIVE) -> get();
            $companies = GroupCompany::select('id', 'comp_name') -> whereIn('id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> with('company_locations', function ($query) use($locationIds) {
                $query -> whereIn('id', $locationIds) -> where('status', ConstantHelper::ACTIVE);
            }) -> get();
            $data = [
                'roles' => $roles,
                'groupCompanies' => $companies,
                'user' => $editUser
            ];
            if (isset($editUser)) {
                return view('components.settings.users.create_edit', $data);
            } else {
                return view('components.settings.users.create_edit', $data)->with(ConstantHelper::WARNING,__("message.no_data_found", ['static' => __('static.user')]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function updateMyProfile(Request $request)
    {
        $validator = (new Validator($request))->updateProfileWeb();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try {
            $user = auth() -> user();
            $user -> name = $request -> user_profile_name;
            $user -> username = $request -> user_profile_username;
            $user -> email = $request -> user_profile_email;
            $user -> mobile_no = $request -> user_profile_mobile_no;
            if ($request -> user_profile_profile_img) {
                if ($user -> hasMedia(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE)) {
                    $user -> clearMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
                }
                $user -> addMediaFromRequest('user_profile_profile_img') -> toMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
            }
            $user -> save();
            return [
                "status" => 200,
                "message" => __('message.records_saved_successfully', ['static' => __('static.profile')])
            ];
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(){
        // $users = User::select('id', 'name', 'username', 'mobile_no', 'email', 'role_id', 'status')->orderByDesc('created_at')->get();

        $users = User::select('id', 'name', 'username', 'mobile_no', 'email', 'role_id', 'status')
           -> whereHas('role', function ($query) {
            $query -> where('access_level', ConstantHelper::ACCESS_LEVEL_USER);
        }) -> where('group_id', auth() -> user() -> group_id) -> orderByDesc('created_at')->get();
        return Excel::download(new UserExport($users),'User.xlsx');
    }
}
