<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\CustomerProjectMixDesignExport;
use App\Helpers\ConstantHelper;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Models\CustomerProject;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CustomerProduct as Validator;
use Maatwebsite\Excel\Facades\Excel;

class CustomerProductController extends Controller
{
    public function show(Request $request, String $id)
    {
        try {
            $data = CustomerProduct::find($id);
            return array(
                'data' => array(
                    'customer_product' => $data
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            if ($request -> project_id)
            {
                $products = CustomerProduct::whereHas('customer_project', function ($query) use($request, $search) {
                    $query -> when($search, function ($projectQuery) use($search) {
                        $projectQuery -> where('name', "LIKE", '%'.$search.'%');
                    }) -> orWhereHas('customer', function ($subQuery) use($request, $search) {
                        $subQuery -> where('id', $request -> customer_id) -> when($search, function ($custQuery) use($search) {
                            $custQuery -> where('name', 'LIKE', '%'.$search.'%');
                        });
                    });
                }) -> where('project_id', $request -> project_id) -> orderByDesc('created_at') -> paginate(ConstantHelper::PAGINATE);
            }

            else
            {
                $products = CustomerProduct::whereHas('customer_project', function ($query) use($request, $search) {
                    $query -> when($search, function ($projectQuery) use($search) {
                        $projectQuery -> where('name', "LIKE", '%'.$search.'%');
                    }) -> whereHas('customer', function ($subQuery) use($request, $search) {
                        $subQuery -> where('id', $request -> customer_id) -> when($search, function ($custQuery) use($search) {
                            $custQuery -> where('name', 'LIKE', '%'.$search.'%');
                        });
                    });
                }) -> orderByDesc('created_at');

                if(($request->name)){
                    $products=$products-> where('project_id',$request->name);
                }

                $products=$products-> paginate(ConstantHelper::PAGINATE);
            }
            $allProducts = Product::whereIn('group_company_id', $group_company_ids) -> get();
            $customers = Customer::select('id AS value', 'name AS label') -> where('group_id', $user -> group_id) -> where('status', ConstantHelper::ACTIVE) -> get();
            $firstCustomer = $customers -> firstWhere('value', $request -> customer_id);
            $projects = collect([]);
            if (isset($firstCustomer))
            {
                $projects = CustomerProject::select('id AS value', 'name AS label') -> where('customer_id', $firstCustomer -> value)  -> where('status', ConstantHelper::ACTIVE) -> get();
            }
            return view('components.customers.projects.mix_designs.index', ['products' => $products, 'customers' => $customers, 'projects' => $projects, 'allProducts' => $allProducts,'search'=>$search]);
        } catch(Exception $ex){
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function getProductDetails(Request $request)
    {
        try {
            if($request->id){
                $product = Product::where('id', $request->id)->with('product_type')->first();
                if($product){
                    return $product;
                }
            }
            return false;
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try{
            // Update Product
            if($request->customerProductId){
                $existingProduct = CustomerProduct::select('id') -> where('id', '!=', $request -> customerProductId) -> where('customer_id', $request -> customer_id) -> where('project_id', $request -> project_id) -> where('product_id', $request -> product_id) -> first();
                if (isset($existingProduct)) {
                    throw new ApiGenericException("Selected Mix Code is already assigned");
                }
                $customerProduct = CustomerProduct::find($request->customerProductId);
                $customerProduct->customer_id = $request->customer_id;
                $customerProduct->project_id = $request->project_id;
                $customerProduct->product_id = $request->product_id;
                $customerProduct->total_quantity = $request->total_qty;
                $customerProduct->status = $request->input('product_status', 'Inactive');
                $customerProduct->save();
            }else{
                // Save Product
                $existingProduct = CustomerProduct::select('id') -> where('customer_id', $request -> customer_id) -> where('product_id', $request -> product_id) -> where('project_id', $request -> project_id) -> first();
                if (isset($existingProduct)) {
                    throw new ApiGenericException("Selected Mix Code is already assigned");
                }
                $customerProduct = new CustomerProduct();
                $customerProduct->customer_id = $request->customer_id;
                $customerProduct->project_id = $request->project_id;
                $customerProduct->product_id = $request->product_id;
                $customerProduct->total_quantity = $request->total_qty;
                $customerProduct->status = $request->input('product_status', 'Inactive');
                $customerProduct->save();
            }
            return [
                "status" => 200,
                "data" => $customerProduct,
                "message" => __('message.records_saved_successfully', ['static' => __('static.product')])
            ];
        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            if($request->productId){
                $productDetail = CustomerProduct::where('id', $request->productId)->with('product.product_type')->first();
                $customerProducts = CustomerProduct::where(['customer_id' =>$request->customerId, 'project_id'=>$request->projectId])->pluck('product_id')->toArray();
                if($customerProducts){
                    $products = Product::whereNotIn('id', $customerProducts)->get();
                }else{
                    $products = collect();
                }
                if($productDetail){
                    return [
                        'productDetail' => $productDetail,
                        'products' => $products
                    ];
                }
            }
            return false;
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(Request $request)
    {

        $search = $request->input('search', null);
        $products = CustomerProduct::whereHas('customer_project', function ($query) use ($request, $search) {

            $query->when($search, function ($projectQuery) use ($search) {
                $projectQuery->where('name', 'LIKE', '%' . $search . '%');
            })->whereHas('customer', function ($customerQuery) use ($request, $search) {
                $customerQuery->where('id', $request->customer_id)
                    ->when($search, function ($custQuery) use ($search) {
                        $custQuery->where('name', 'LIKE', '%' . $search . '%');
                    });
            });

        })->where('project_id',$request->project_id)-> orderByDesc('created_at')->get();
       

        return Excel::download(new CustomerProjectMixDesignExport($products), 'CustomerProjectMixDesign.xlsx');
    }

}
