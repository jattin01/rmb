<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Models\GroupCompany;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Lib\Validations\Globale as Validator;
use App\Models\CompanyLocation;
use App\Models\GlobalSetting;
use Exception;
use Illuminate\Support\Facades\DB;

class GlobalController extends Controller
{

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $group_company_ids = $user->access_rights->pluck('group_company_id');
            $location_ids = $user->access_rights->pluck('location_id');
            $search = $request->search;

            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')
                ->whereIn('id', $group_company_ids)
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            $batchingPlants = GlobalSetting::select(
                'id',
                'group_company_id',
                'company_location_id',
                'batching_quality_inspection',
                'mixture_chute_cleaning',
                'site_quality_inspection',
                'chute_cleaning_site',
                'transite_mixture_cleaning'
            )->get();

            $viewData = [
                'batchingPlants' => $batchingPlants,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];

            return view('components.settings.global.index', $viewData);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function create(Request $request)
    {

        $user = auth()->user();
        $group_company_ids = $user->access_rights->pluck('group_company_id');
        $location_ids = $user->access_rights->pluck('location_id');
        $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')->whereIn('id', $group_company_ids)->where('status', ConstantHelper::ACTIVE)->get();
        $locations = collect([]);
        $defaultCompanyId = $groupCompanies->first();
        if (isset($defaultCompanyId)) {
            $locations = CompanyLocation::select('id AS value', DB::raw("CONCAT(site_name, ' - ', location) AS label"))
                ->where('group_company_id', $defaultCompanyId->value)
                ->whereIn('id', $location_ids)
                ->where('status', ConstantHelper::ACTIVE)->get();
        }

        $global = GlobalSetting::select(
            'batching_quality_inspection',
            'mixture_chute_cleaning',
            'site_quality_inspection',
            'chute_cleaning_site',
            'transite_mixture_cleaning'
        )->find($request->plantId);
        $data = ['locations' => $locations, 'groupCompanies' => $groupCompanies];

        return view('components.settings.global.create_edit', [
            'global' => $global,
            'locations' => $locations,
            'groupCompanies' => $groupCompanies,
        ]);
    }




    public function store(Request $request)
    {
        $validator = (new Validator($request))->global();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            // Update Batching Plant
            if ($request->plantId) {
                $global = GlobalSetting::find($request->plantId);
                $global->group_company_id = $request->group_company_id;
                $global->company_location_id = $request->company_location_id;
                $global->batching_quality_inspection = $request->batching_quality_inspection;
                // $global->mixture_chute_cleaning = $request->mixture_chute_cleaning;
                $global->site_quality_inspection = $request->site_quality_inspection;
                // $global->chute_cleaning_site = $request->chute_cleaning_site;
                $global->transite_mixture_cleaning = $request->transite_mixture_cleaning;
                $global->save();
            } else {
                // Save Batching Plant
                $global = new GlobalSetting();
                $global->group_company_id = $request->group_company_id;
                $global->company_location_id = $request->company_location_id;
                $global->batching_quality_inspection = $request->batching_quality_inspection;
                // $global->mixture_chute_cleaning = $request->mixture_chute_cleaning;
                $global->site_quality_inspection = $request->site_quality_inspection;
                // $global->chute_cleaning_site = $request->chute_cleaning_site;
                $global->transite_mixture_cleaning = $request->transite_mixture_cleaning;
                $global->save();
            }
            return [
                        "status" => 200,
                        "data" => $global,
                        "redirect_url" => "/settings/global",
                        "message" => __('message.records_saved_successfully', ['static' => __('static.order')])
                    ];
        } catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }



    public function edit(Request $request)

    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $location_ids = $user -> access_rights -> pluck('location_id');
            if($request->plantId){
                $global = GlobalSetting::where('id', $request -> plantId) -> whereIn('group_company_id', $group_company_ids) -> whereIn('company_location_id', $location_ids) -> first();
                $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
                $locations = collect([]);
                if (isset($global))
                {
                    $locations = CompanyLocation::select('id AS value', DB::raw("CONCAT(location, ' ', site_name) AS label"))
                    -> where('group_company_id', $global -> group_company_id)
                    -> where('status', ConstantHelper::ACTIVE) -> get();
                }
                $data = ['locations' => $locations, 'groupCompanies' => $groupCompanies, 'global' => isset($global) ? $global : ""];
            }
            if (isset($global)) {
                return view('components.settings.global.create_edit', $data);
            } else {
                return view('components.settings.global.create_edit', $data) -> with(ConstantHelper::WARNING, __('message.no_data_found', ['static' => __('static.plant')]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }


}
