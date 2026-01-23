<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\DriverExport;
use App\Helpers\ConstantHelper;
use App\Models\Driver;
use App\Models\DriverTransitMixer;
use App\Models\GroupCompany;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Driver as Validator;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request -> search;
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $drivers = Driver::select('id', 'group_company_id', 'code', 'employee_code', 'name', 'email_id', 'username','user_role', 'phone', 'license_no', 'license_expiry', 'status')
                -> when($search, function ($query) use ($search) {
                    $query -> where('code', 'LIKE', '%'.$search.'%')
                        -> orWhere('name', 'LIKE', '%'.$search.'%')
                        -> orWhere('phone', 'LIKE', '%'.$search.'%')
                        -> orWhere('email_id', 'LIKE', '%'.$search.'%');
                }) -> whereIn('group_company_id', $groupCompanyIds) -> orderByDesc('created_at') ;
                if($request->group_company_id){
                    $drivers=$drivers->where('group_company_id',$request->group_company_id);
                }

                $drivers=$drivers-> paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
            return view('components.settings.drivers.index', ['drivers' => $drivers, 'search' => $search]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> get();
            return view('components.settings.drivers.create_edit', ['groupCompanies' => $groupCompanies]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> get();
            $driver = Driver::select('id', 'group_company_id', 'code', 'employee_code', 'name', 'email_id', 'username', 'user_role','phone', 'license_no', 'license_expiry', 'status', 'user_id') -> where('id', $request -> driver_id) -> whereIn('group_company_id', $groupCompanyIds) -> first();
            if (isset($driver))
            {
                return view('components.settings.drivers.create_edit', ['groupCompanies' => $groupCompanies, 'driver' => $driver]);
            }
            else
            {
                return view('components.settings.drivers.create_edit', ['groupCompanies' => $groupCompanies, 'driver' => $driver ])->with(ConstantHelper::WARNING, 'Driver Not Found');
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = (new Validator($request))->store();
            if($validator->fails()){
                throw new ValidationException($validator);
            }
            $currentUser = auth() -> user();
            $groupCompanyIds = $currentUser -> access_rights -> pluck('group_company_id');
            DB::beginTransaction();
            //Validation success
            if ($request -> driver_id) { //Update
                $driver = Driver::where('id', $request -> driver_id) -> whereIn('group_company_id', $groupCompanyIds) -> first();
                if (isset($driver)) {
                    $user = User::find($driver -> user_id);
                    if (isset($user)) {
                        $user -> name = $request -> name;
                        $user -> username = $request -> username;
                        $user -> email = $request -> email_id;
                        $user -> mobile_no = $request -> phone;
                        $user -> save();

                        $driverTransitMixers = DriverTransitMixer::where('driver_id', $driver -> id) -> get();
                        if (count($driverTransitMixers) > 0 && $driver -> group_company_id !== $request -> group_company_id)
                        {
                            DB::rollBack();
                            throw new ApiGenericException("Please Re-assign driver trucks first");
                        }

                        $driver -> group_company_id = $request -> group_company_id;
                        $driver -> code = $request -> username;
                        $driver -> employee_code = $request -> employee_code;
                        $driver -> name = $request -> name;
                        $driver -> email_id = $request -> email_id;
                        $driver -> username = $request -> username;
                        $driver -> user_role = $request -> user_role;
                        $driver -> phone = $request -> phone;
                        $driver -> license_no = $request -> license_no;
                        $driver -> license_expiry = $request -> license_expiry;
                        $driver -> status = $request -> input('driver_status', ConstantHelper::INACTIVE);
                        $driver -> save();
                    } else {
                        DB::rollBack();
                        throw new ApiGenericException(__("message.no_data_found", ['static' => __('static.user')]));
                    }
                } else {
                    DB::rollBack();
                    throw new ApiGenericException(__("message.no_data_found", ['static' => __('static.driver')]));
                }
            } else { //Create New
                $role = Role::where('name', ConstantHelper::USER_TYPE_DRIVER)->where('access_level', ConstantHelper::ACCESS_LEVEL_SYSTEM)->where('status', ConstantHelper::ACTIVE)->first();
                if($role){
                    $userRoleId = $role->id;
                }
                $user = User::create([
                    'group_id' => $currentUser -> group_id,
                    'name' => $request -> name,
                    'username' => $request -> username,
                    'email' => $request -> email_id,
                    'password' => bcrypt($request -> phone),
                    'mobile_no' => $request -> phone,
                    'role_id' => $userRoleId ?? null,
                    'user_type' => ConstantHelper::USER_TYPE_DRIVER,
                    'status' => ConstantHelper::ACTIVE
                ]);
                $driver = Driver::create([
                    'user_id' => $user -> id,
                    'group_company_id' => $request -> group_company_id,
                    'code' => $request -> username,
                    'employee_code' => $request -> employee_code,
                    'name' => $request -> name,
                    'email_id' => $request -> email_id,
                    'username' => $request -> username,
                    'user_role' => $request -> user_role,
                    'phone' => $request -> phone,
                    'license_no' => $request -> license_no,
                    'license_expiry' => $request -> license_expiry,
                    'status' => $request -> input('driver_status', ConstantHelper::INACTIVE)
                ]);
            }
            if ($request -> image) {
                if ($user -> hasMedia(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE)) {
                    $user -> clearMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
                }
                $user -> addMediaFromRequest('image') -> toMediaCollection(ConstantHelper::PROFILE_COLLECTION_NAME_SPATIE);
            }
            DB::commit();
            return [
                "status" => 200,
                "data" => $driver,
                "redirect_url" => "/settings/drivers",
                "message" => __('message.records_saved_successfully', ['static' => __('static.driver')])
            ];
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(){
        $drivers = Driver::select('id', 'group_company_id', 'code', 'employee_code', 'name', 'email_id', 'username', 'phone', 'license_no', 'license_expiry', 'status')->orderByDesc('created_at')->get();
        return Excel::download(new DriverExport($drivers),'Driver.xlsx');
    }
}
