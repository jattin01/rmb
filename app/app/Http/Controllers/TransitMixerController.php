<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\TransitMixerExport;
use App\Helpers\ConstantHelper;
use App\Models\Driver;
use App\Models\DriverTransitMixer;
use App\Models\GroupCompany;
use App\Models\TransitMixer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\TransitMixer as Validator;
use App\Models\Capacity;
use Maatwebsite\Excel\Facades\Excel;

class TransitMixerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $transitMixers = TransitMixer::select('id', 'group_company_id','driver_id', 'truck_name', 'registration_no', 'truck_capacity', 'description', 'status')
                            ->when($search, function($query) use($search) {
                                $query->where('truck_name', 'LIKE', '%'.$search.'%')
                                    ->orWhere('registration_no', 'LIKE', '%'.$search.'%')
                                    ->orWhere('description', 'LIKE', '%'.$search.'%')
                                    ->orWhereHas('driverDetail', function ($subQuery) use($search) {
                                        $subQuery -> where('name', 'LIKE', '%'.$search.'%');
                                    });
                            }) -> whereIn('group_company_id', $group_company_ids)-> orderByDesc('created_at');
                            if(isset($request->group_company_id)){

                                $transitMixers =$transitMixers ->where('group_company_id',$request->group_company_id);

                        }
                            $transitMixers=$transitMixers-> paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
            $viewData = [
                'transitMixers' => $transitMixers,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.transit_mixers.index', $viewData);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $capacities = Capacity::select('id AS value', 'value AS label')->where('uom','CUM')->get();
            if($request->mixerId){
                $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
                $mixerDetails = TransitMixer::where('id', $request -> mixerId) -> whereIn('group_company_id', $group_company_ids) -> first();
                $drivers = collect([]);
                if(isset($mixerDetails)){
                    $drivers = Driver::select('id', 'code', 'name') -> whereIn('group_company_id', $group_company_ids)->where('user_role', 'driver')-> where('status', ConstantHelper::ACTIVE)->get();
                }
            }


            $data = ['capacities' => $capacities, 'drivers' => isset($drivers) ? $drivers : [],'mixerDetails' => isset($mixerDetails) ? $mixerDetails : null, 'groupCompanies' => isset($groupCompanies) ? $groupCompanies : []];
            if(isset($mixerDetails)) {
                return view('components.settings.transit_mixers.create_edit', $data);
            } else {
                return view('components.settings.transit_mixers.create_edit', $data)->with(ConstantHelper::WARNING, __('message.no_data_found', ['static' => __('static.mixer')]));
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
        try{
            DB::beginTransaction();

            // Update Transit Mixer
            if($request->mixerId){
                $mixer = TransitMixer::find($request->mixerId);
                $mixer->group_company_id = $request->group_company_id;
                $mixer->truck_name = $request->truck_name;
                $mixer->registration_no = $request->plate_no;
                $mixer->loading_time = 10;
                $mixer->driver_id = $request->driver_code;
                // $mixer->driver_name = $request->driver_name;
                $mixer->truck_capacity = $request->capacity;
                $mixer->description = $request->description ?? null;
                $mixer->status = $request->input('mixer_status', 'Inactive');
                $mixer->save();

            }else{
                // Save Batching Plant
                $mixer = new TransitMixer();
                $mixer->group_company_id = $request -> group_company_id;
                $mixer->truck_name = $request->truck_name;
                $mixer->registration_no = $request->plate_no;
                $mixer->loading_time = 10;
                $mixer->driver_id = $request->driver_code;
                // $mixer->driver_name = $request->driver_name;
                $mixer->truck_capacity = $request->capacity;
                $mixer->description = $request->description ?? null;
                $mixer->status = $request->input('mixer_status', 'Inactive');
                $mixer->save();

            }

            DriverTransitMixer::where('transit_mixer_id', $mixer -> id) -> delete();

            DriverTransitMixer::updateOrCreate(
                ['driver_id' => $request -> driver_code,
                 'transit_mixer_id' => $mixer -> id],
                ['status' => 'Active']
            );

            DB::commit();
            return [
                "status" => 200,
                "data" => $mixer,
                "redirect_url" => "/settings/transit-mixers",
                "message" => __('message.records_saved_successfully', ['static' => __('static.mixer')])
            ];

        }catch (\Throwable $th) {
            DB::rollBack();
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $capacities = Capacity::select('id AS value', 'value AS label')->where('uom','CUM')->get();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $drivers = Driver::select('id', 'code', 'name') -> whereIn('group_company_id', $group_company_ids)->where('user_role','driver') -> where('status', ConstantHelper::ACTIVE)->get();
            $data = ['capacities'=>$capacities,'drivers' => $drivers, 'groupCompanies' => $groupCompanies];
            return view('components.settings.transit_mixers.create_edit', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function export(Request $request){
        $transitMixers = TransitMixer::select('id', 'group_company_id','driver_id', 'truck_name', 'registration_no', 'truck_capacity', 'description', 'status')->orderByDesc('created_at')->get();
        return Excel::download(new TransitMixerExport($transitMixers), 'TransitMixer.xlsx');

    }
}
