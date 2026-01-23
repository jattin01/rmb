<?php

namespace App\Http\Controllers;

use App\Exports\StructureExport;
use App\Helpers\ConstantHelper;
use App\Models\GroupCompany;
use App\Models\PumpType;
use App\Models\StructuralReference;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Lib\Validations\Structure as Validator;
use Maatwebsite\Excel\Facades\Excel;

class StructureController extends Controller
{


    public function index(Request $request){

        $search = $request -> search;
        $user = auth() -> user();
        $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
        $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();


        $structures = StructuralReference::select('id','group_company_id', 'pouring_wo_pump_time', 'pouring_w_pump_time', 'name', 'status')
            -> when($search, function ($query) use ($search) {
                $query -> where('name', 'LIKE', '%'.$search.'%')
                    -> orWhere('status', 'LIKE', '%'.$search.'%');

            }) -> whereIn('group_company_id', $groupCompanyIds) -> orderByDesc('created_at');


            if($request->group_company_id){
                $structures=$structures->where('group_company_id',$request->group_company_id);
            }

            $structures=$structures-> paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
        return view('components.settings.structure.index',[
            'structures'=>$structures,
            'groupCompanies'=>$groupCompanies
        ]);

    }
    public function create(Request $request){

        $user = auth() -> user();
        $group_company_ids = $user -> access_rights -> pluck('group_company_id');
        $structure = null;
        if($request->structure_id){
            $structure=StructuralReference::find($request->structure_id);
        }

        // $structures = StructuralReference::select( 'id AS value', 'name AS label')->get();
        $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
        return view('components.settings.structure.create_edit',[
            'groupCompanies'=>$groupCompanies,
            'structure'=>$structure,
            // 'structures'=>$structures
            ]
        );

    }


    public function store(Request $request)
    {
        $validator = (new Validator($request))->structure();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        // update

        if($request->structureRefId){
            $structure = StructuralReference::find($request->structureRefId);
            $structure->name = $request->name;
            $structure->group_company_id = $request->group_company_id;
            $structure->pouring_wo_pump_time = $request->pouring_wo_pump_time;
            $structure->pouring_w_pump_time = $request->pouring_w_pump_time;
            $structure->status = $request->input('status', 'Inactive');
            $structure->save();
        }
        // create
        else{
            $structure = new StructuralReference();
            $structure->name = $request->name;
            $structure->group_company_id = $request->group_company_id;
            $structure->pouring_wo_pump_time = $request->pouring_wo_pump_time;
            $structure->pouring_w_pump_time = $request->pouring_w_pump_time;
            $structure->status = $request->input('status', 'Inactive');
            $structure->save();
        }

        return [
            "status" => 200,
            "data" => $structure,
            "redirect_url" => "/settings/structures",
            "message" => __('message.records_saved_successfully', ['static' => __('static.order')])
        ];
    }

    public function export(){

        $structures = StructuralReference::select('id','group_company_id', 'name', 'status')->get();
        return Excel::download(new StructureExport($structures),'Structure.xlsx');



    }


}
