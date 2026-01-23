<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\ProductMixTypeExport;
use App\Helpers\ConstantHelper;
use App\Models\GroupCompany;
use App\Models\ProductType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\ProductType as Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProductTypeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $productTypes = ProductType::when($search, function($query)use($search){
                                $query->where('type', 'LIKE', '%'.$search.'%');
                                $query->orWhere('description', 'LIKE', '%'.$search.'%');
                                $query->orWhere('status', 'LIKE', '%'.$search.'%');
                            }) -> whereIn('group_company_id', $group_company_ids) -> orderByDesc('created_at');
                            if(($request->type)){
                                $productTypes=$productTypes-> where('type',$request->type);
                            }

                           $productTypes=$productTypes -> paginate(ConstantHelper::PAGINATE)->appends(['search' => $request -> search]);
            $data = [
                'productTypes' => $productTypes,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.product_types.index', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function store(Request $request)
    {
        // dd($request->typeId);
        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try{
            // Update Product Type
            if($request->typeId){
                $type = ProductType::find($request->typeId);
                $type->group_company_id = $request->group_company_id;
                $type->type = $request->type;
                $type->batching_creation_time = $request->batching_creation_time;
                $type->temperature_creation_time = $request->temperature_creation_time;
                $type->description = $request->description ?? null;
                $type->status = $request->input('type_status', 'Inactive');
                $type->save();
            }else{
                // Save Product
                $type = new ProductType();
                $type->group_company_id = $request->group_company_id;
                $type->type = $request->type;
                $type->batching_creation_time = $request->batching_creation_time;
                $type->temperature_creation_time = $request->temperature_creation_time;

                $type->description = $request->description ?? null;
                $type->status = $request->input('type_status', 'Inactive');
                $type->save();
            }
            return [
                "status" => 200,
                "data" => $type,
                "redirect_url" => "/settings/product_types",
                "message" => __('message.records_saved_successfully', ['static' => __('static.type')])
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
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $data = ['groupCompanies' => $groupCompanies];
            return view('components.settings.product_types.create_edit', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $productType = ProductType::find($request -> typeId);
            $data = [
                'typeDetail' => $productType,
                'groupCompanies' => $groupCompanies
            ];
            if (isset($productType)) {
                return view('components.settings.product_types.create_edit', $data);
            } else {
                return view('components.settings.product_types.create_edit', $data)->with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __('static.type')]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function getByCompany(Request $request, String $groupCompanyId)
    {
        try {
            $productTypes = ProductType::select('id AS value', 'type AS label') -> where('group_company_id', $groupCompanyId) -> where('status', ConstantHelper::ACTIVE) -> get();
            return array(
                'message' => 'Product Types found',
                'data' => array(
                    'product_types' => $productTypes
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(){
        $productTypes = ProductType::select('group_company_id','type', 'description', 'status')-> orderByDesc('created_at')->get();
        return Excel::download(new ProductMixTypeExport($productTypes),'ProductMixType.xlsx');
    }
}
