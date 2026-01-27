<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\ApprovalSetpExport;
use App\Helpers\ConstantHelper;
use App\Models\ApprovalSetup;
use App\Models\ApprovalSetupLevel;
use App\Models\ApprovalSetupLevelUser;
use App\Models\CompanyLocation;
use App\Models\GroupCompany;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\ApprovalSetup as Validator;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalSetupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $locationIds = $user -> access_rights -> pluck('location_id');
             $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $setups = ApprovalSetup::with('levels') -> select('id', 'group_company_id', 'location_id', 'approval_level_users', 'status')
            -> whereIn('group_company_id', $groupCompanyIds) -> whereIn('location_id', $locationIds) -> paginate(ConstantHelper::PAGINATE);
            return view('components.settings.order_approvals.index', ['setups' => $setups,'groupCompanies'=>$groupCompanies]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $locationIds = $user -> access_rights -> pluck('location_id');
            $existingSetupLocationIds = ApprovalSetup::select('id', 'location_id') -> whereIn('group_company_id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> get() -> pluck('location_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> get();
            $locations = collect([]);
            $firstCompany = $groupCompanies -> first();
            if (isset($firstCompany))
            {
                $locations = CompanyLocation::select('id AS value', 'site_name AS label') -> where('group_company_id', $firstCompany -> value) -> whereIn('id', $locationIds) -> whereNotIn('id', $existingSetupLocationIds) -> where('status', ConstantHelper::ACTIVE) -> get();
            }

            $users = collect([]);

            $types = ConstantHelper::APPROVAL_LEVEL_TYPES;

            return view('components.settings.order_approvals.create_edit', ['groupCompanies' => $groupCompanies, 'locations' => $locations, 'users' => $users, 'types' => $types]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $locationIds = $user -> access_rights -> pluck('location_id');

            $setup = ApprovalSetup::with('levels.users') -> whereIn('group_company_id', $groupCompanyIds) -> whereIn('location_id', $locationIds) -> where('id', $request -> input('approval_setup_id', null)) -> first();

            $groupCompanies = collect([]);
            $locations = collect([]);

            $users = collect([]);

            $types = ConstantHelper::APPROVAL_LEVEL_TYPES;

            if (isset($setup))
            {
                $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> where('id', $setup -> group_company_id) ->  where('status', ConstantHelper::ACTIVE) -> get();
                $locations = CompanyLocation::select('id AS value', 'site_name AS label') -> where('id', $setup -> location_id) -> where('status', ConstantHelper::ACTIVE) -> get();
                $users = User::select('id AS value', 'name AS label') -> where('user_type', ConstantHelper::USER_TYPE_ADMIN) -> where('status', ConstantHelper::ACTIVE) -> whereHas('access_rights', function ($query) use ($setup) {
                    $query -> where('location_id', $setup -> location_id) -> where('status', ConstantHelper::ACTIVE);
                }) -> get();
                return view('components.settings.order_approvals.create_edit', ['setup' => $setup, 'groupCompanies' => $groupCompanies, 'locations' => $locations, 'users' => $users, 'types' => $types]);
            }
            else
            {
                return view('components.settings.order_approvals.create_edit', ['setup' => null, 'groupCompanies' => $groupCompanies, 'locations' => $locations, 'users' => $users, 'types' => $types]) -> with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __("static.approval_setup")]));
            }
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
            //Update
            $approvalSetup = null;
            if ($request -> approval_setup_id)
            {
                $approvalSetup = ApprovalSetup::find($request -> approval_setup_id);
                if (!isset($approvalSetup))
                {
                    DB::rollBack();
                    throw new ApiGenericException("Setup does not exists");
                }
                $approvalSetup -> status = $request->input('setup_status', ConstantHelper::INACTIVE);
                $levels = ApprovalSetupLevel::where('approval_setup_id', $request -> approval_setup_id) -> get();
                foreach ($levels as $level) {
                    foreach ($level -> users as $levelUser) {
                        $levelUser -> forceDelete();
                    }
                    $level -> forceDelete();
                }
                $approvalSetup -> save();
            }
            //Create
            else
            {
                $approvalSetup = ApprovalSetup::create([
                    'group_company_id' => $request -> group_company_id,
                    'location_id' => $request -> location_id,
                    'transaction_type' => 'Approval',
                    'approval_level_users' => 'All',
                    'status' => $request->input('setup_status', ConstantHelper::INACTIVE)
                ]);
            }
            $userIds = array();
            foreach ($request -> level_types as $levelNoKey => $levelType) {
                $levelNoIndex = $levelNoKey + 1;
                $newLevel = ApprovalSetupLevel::create([
                    'approval_setup_id' => $approvalSetup -> id,
                    'level_no' => $levelNoIndex,
                    'type' => $levelType
                ]);
                $requestKeyForUserLevel = 'level_' . $levelNoIndex . '_users';
                $levelUsers = $request -> input($requestKeyForUserLevel, []);
                foreach ($levelUsers as $singleLevelUser) {
                    if (in_array($singleLevelUser, $userIds))
                    {
                        DB::rollBack();
                        throw new ApiGenericException("Same user cannot be contained withing multiple levels");
                    }
                    ApprovalSetupLevelUser::create([
                        'level_id' => $newLevel -> id,
                        'user_id' => $singleLevelUser,
                        'status' => ConstantHelper::ACTIVE
                    ]);
                    array_push($userIds, $singleLevelUser);
                }
            }
            DB::commit();
            return [
                "status" => 200,
                "data" => $approvalSetup,
                "message" => __('message.records_saved_successfully', ['static' => __('static.approval_setup')])
            ];

        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(){
        $approvalOrders = ApprovalSetup::with('levels') -> select('id', 'group_company_id', 'location_id', 'approval_level_users', 'status')->get();

            return Excel::download(new ApprovalSetpExport($approvalOrders),'ApprovalSetup.xlsx');
    }
}
