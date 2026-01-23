<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\BatchingPlantExport;
use App\Exports\CustomerProjectTeamMemberExport;
use App\Helpers\ConstantHelper;
use App\Models\BatchingPlant;
use App\Models\CompanyLocation;
use App\Models\GroupCompany;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\BatchingPlant as Validator;
use Maatwebsite\Excel\Facades\Excel;

class BatchingPlantController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $location_ids = $user -> access_rights -> pluck('location_id');
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $batchingPlants = BatchingPlant::select('id', 'group_company_id', 'company_location_id', 'plant_name', 'long_name', 'capacity', 'description', 'status')
                            ->when($search, function($query) use($search) {
                                $query->where('plant_name', 'LIKE', '%'.$search.'%')
                                    ->orWhere('long_name', 'LIKE', '%'.$search.'%')
                                    ->orWhere('description', 'LIKE', '%'.$search.'%')
                                    ->orWhereHas('company_location', function ($subQuery) use($search) {
                                        $subQuery -> where('location', 'LIKE', '%'.$search.'%');
                                    });
                            }) -> whereIn('group_company_id', $group_company_ids) -> whereIn('company_location_id', $location_ids) -> orderByDesc('created_at');
                            if(isset($request->group_company_id)){

                                    $batchingPlants =$batchingPlants ->where('group_company_id',$request->group_company_id);

                            }
                            if(isset($request->company_location_id)){

                                    $batchingPlants =$batchingPlants ->where('company_location_id',$request->company_location_id);

                            }


                            $batchingPlants = $batchingPlants->paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
            $viewData = [
                'batchingPlants' => $batchingPlants,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.batching_plants.index', $viewData);
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
        try{
            // Update Batching Plant
            if($request->plantId){
                $plant = BatchingPlant::find($request->plantId);
                $plant->group_company_id = $request->group_company_id;
                $plant->company_location_id = $request->company_location_id;
                $plant->plant_name = $request->plant_name;
                $plant->long_name = $request->long_name;
                $plant->capacity = $request->capacity;
                $plant->description = $request->description ?? null;
                $plant->status = $request->input('plant_status', 'Inactive');
                $plant->save();
            }else{
                // Save Batching Plant
                $plant = new BatchingPlant();
                $plant->group_company_id = $request->group_company_id;
                $plant->company_location_id = $request->company_location_id;
                $plant->plant_name = $request->plant_name;
                $plant->long_name = $request->long_name;
                $plant->capacity = $request->capacity;
                $plant->description = $request->description ?? null;
                $plant->status = $request->input('plant_status', 'Active');
                $plant->save();
            }
            return [
                "status" => 200,
                "data" => $plant,
                "redirect_url" => "/settings/batching-plants",
                "message" => __('message.records_saved_successfully', ['static' => __('static.plant')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $location_ids = $user -> access_rights -> pluck('location_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $locations = collect([]);
            $defaultCompanyId = $groupCompanies -> first();
            if (isset($defaultCompanyId))
            {
                $locations = CompanyLocation::select('id AS value', DB::raw("CONCAT(site_name, ' - ', location) AS label"))
                -> where('group_company_id', $defaultCompanyId -> value)
                -> whereIn('id', $location_ids)
                -> where('status', ConstantHelper::ACTIVE) -> get();
            }
            $data = ['locations' => $locations, 'groupCompanies' => $groupCompanies];
            return view('components.settings.batching_plants.create_edit', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $location_ids = $user -> access_rights -> pluck('location_id');
            if($request->plantId){
                $plantDetail = BatchingPlant::where('id', $request -> plantId) -> whereIn('group_company_id', $group_company_ids) -> whereIn('company_location_id', $location_ids) -> first();
                $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
                $locations = collect([]);
                if (isset($plantDetail))
                {
                    $locations = CompanyLocation::select('id AS value', DB::raw("CONCAT(location, ' ', site_name) AS label"))
                    -> where('group_company_id', $plantDetail -> group_company_id)
                    -> where('status', ConstantHelper::ACTIVE) -> get();
                }
                $data = ['locations' => $locations, 'groupCompanies' => $groupCompanies, 'plantDetail' => isset($plantDetail) ? $plantDetail : ""];
            }
            if (isset($plantDetail)) {
                return view('components.settings.batching_plants.create_edit', $data);
            } else {
                return view('components.settings.batching_plants.create_edit', $data) -> with(ConstantHelper::WARNING, __('message.no_data_found', ['static' => __('static.plant')]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function export(Request $request){
        $batchingPlants = BatchingPlant::select('id', 'group_company_id', 'company_location_id', 'plant_name', 'long_name', 'capacity', 'description', 'status')->get();
        return Excel::download(new BatchingPlantExport($batchingPlants), 'BatchingPlant.xlsx');

    }
}
