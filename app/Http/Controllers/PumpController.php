<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\PumpExport;
use App\Helpers\ConstantHelper;
use App\Models\GroupCompany;
use App\Models\Pump;
use App\Models\PumpType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Pump as Validator;
use App\Models\Capacity;
use App\Models\Driver;
use Maatwebsite\Excel\Facades\Excel;

class PumpController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $pumps = Pump::select('id', 'group_company_id', 'pump_name', 'pump_capacity', 'description', 'operator_id','type', 'status')
                            ->when($search, function($query) use($search) {
                                $query->where('pump_name', 'LIKE', '%'.$search.'%')
                                    ->orWhere('type', 'LIKE', '%'.$search.'%')
                                    ->orWhere('pump_capacity', 'LIKE', '%'.$search.'%')
                                    ->orWhere('description', 'LIKE', '%'.$search.'%');
                            }) -> whereIn('group_company_id', $group_company_ids)->with('operator') -> orderByDesc('created_at');
                            if(isset($request->group_company_id)){

                                $pumps =$pumps ->where('group_company_id',$request->group_company_id);

                        }
                         $pumps=$pumps -> paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
                        //  dd($pumps);
            $viewData = [
                'pumps' => $pumps,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.pumps.index', $viewData);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }
    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
                        $pumpDetails = Pump::find($request->pumpId);
                        $capacities = Capacity::select('id AS value', 'value AS label')->where('uom','MTR')->get();


            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $firstGroupCompany = $groupCompanies -> first();
            $pumpType = collect([]);
            if (isset($firstGroupCompany))
            {
                $pumpType = PumpType::select('type AS value', 'type AS label') -> where('group_company_id', $firstGroupCompany -> value) -> where('status', ConstantHelper::ACTIVE) -> get();
            }


        // Filter users with user_role as 'operator'
        $operators = Driver::select('id', 'name')
            ->whereIn('group_company_id', $group_company_ids)
            ->where('status', ConstantHelper::ACTIVE)
            ->where('user_role', 'operator')
            ->get();
            $data = ['capacities'=>$capacities,'pumpTypes' => $pumpType, 'groupCompanies' => $groupCompanies,'operators' => $operators,'pumpDetails'=>$pumpDetails];
            return view('components.settings.pumps.create_edit', $data);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function store(Request $request){

        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try{
            // Update Pump
            if($request->pumpId){
                $pump = Pump::find($request->pumpId);
                $pump->group_company_id = $request->group_company_id;
                $pump->pump_name = $request->pump_name;
                $pump->operator_id = $request->operator_id;

                $pump->type = $request->type;
                $pump->pump_capacity = $request->pump_capacity;
                $pump->installation_time = $request->installation_time;
                $pump->description = $request->description ?? null;
                $pump->status = $request->input('pump_status', 'Inactive');
                $pump->save();
            }else{
                // Save Pump
                $pump = new Pump();
                $pump->group_company_id = $request->group_company_id;
                $pump->pump_name = $request->pump_name;
                $pump->operator_id = $request->operator_id;

                $pump->type = $request->type;
                $pump->pump_capacity = $request->pump_capacity;
                $pump->installation_time = $request->installation_time;
                $pump->description = $request->description ?? null;
                $pump->status = $request->input('pump_status', 'Inactive');
                $pump->save();
            }


            return [
                "status" => 200,
                "data" => $pump,
                "redirect_url" => "/settings/pumps",
                "message" => __('message.records_saved_successfully', ['static' => __('static.pump')])
            ];
        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $pumpDetails = Pump::find($request->pumpId);
            $operators = Driver::select('id', 'name')
            ->whereIn('group_company_id', $group_company_ids)
            ->where('status', ConstantHelper::ACTIVE)
            ->where('user_role', 'operator')
            ->get();
            $capacities = Capacity::select('id AS value', 'value AS label')->where('uom','MTR')->get();

            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            if($pumpDetails){
                $pumpType = PumpType::select('type AS value', 'type AS label') -> where('group_company_id', $pumpDetails -> group_company_id) -> where('status', ConstantHelper::ACTIVE) -> get();
                $data = ['capacities'=>$capacities,'pumpTypes' => $pumpType, 'pumpDetails' => $pumpDetails, 'groupCompanies' => $groupCompanies,'operators'=>$operators];
                return view('components.settings.pumps.create_edit', $data);
            } else {
                return view('components.settings.pumps.create_edit', ['pumpTypes' => [], 'pumpDetails' => null, 'groupCompanies' => $groupCompanies])->with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __('static.pump')]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function getSize( Request $request,string $type){

        $pumpSizes = Pump:: select('pump_capacity')->where('group_company_id', $request -> group_company_id)->where('type', $type) -> where('status', ConstantHelper::ACTIVE)->distinct() -> get();
        foreach ( $pumpSizes as  &$pumpSize) {
        $pumpSize->label= $pumpSize->pump_capacity;
        $pumpSize->value= $pumpSize->pump_capacity;
        }
        return array(
            'message' => 'Data returned successfully',
            'data' => array(
                'pump_sizes' => $pumpSizes,

            )
        );
    }

    public function export(){
        $pumps = Pump::select('id', 'group_company_id', 'pump_name', 'pump_capacity', 'description', 'type', 'status')->orderByDesc('created_at')->get();
        return Excel::download(new PumpExport($pumps),'Pump.xlsx' );


    }

}
