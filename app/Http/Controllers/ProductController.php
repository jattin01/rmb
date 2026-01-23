<?php

namespace App\Http\Controllers;

use App\Models\GroupCompany;
use Exception;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductContent;
use App\Helpers\ConstantHelper;
use App\Exceptions\ApiGenericException;
use App\Exports\ProductMixExport;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Product as Validator;
use App\Models\ProductStructuralReference;
use App\Models\ProductStructureReference;
use App\Models\StructuralReference;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $group_company_ids = $user->access_rights->pluck('group_company_id');
            $search = $request->search;

            $group_company_ids = $user->access_rights->pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')->whereIn('id', $group_company_ids)->where('status', ConstantHelper::ACTIVE)->get();

            $products = Product::with('product_type','structuralReferences.structural')
                ->when($search, function ($query) use ($search) {
                    $query->where('code', 'LIKE', '%' . $search . '%');
                    $query->orWhere('name', 'LIKE', '%' . $search . '%');
                    $query->orWhere('usage', 'LIKE', '%' . $search . '%');
                    $query->orWhere('density', 'LIKE', '%' . $search . '%');
                    $query->orWhere('status', 'LIKE', '%' . $search . '%');
                    $query->orWhereHas('product_type', function ($q) use ($search) {
                        $q->where('type', 'LIKE', '%' . $search . '%');
                    });
                })->whereIn('group_company_id', $group_company_ids)->orderByDesc('created_at')->paginate(ConstantHelper::PAGINATE)->appends(['search' => $search]);
                // dd($products);
            $data = [
                'products' => $products,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.products.index', $data);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();
            $selectedStructure= [];
            $group_company_ids = $user->access_rights->pluck('group_company_id');

            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')
                ->whereIn('id', $group_company_ids)
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            $productTypes = collect([]);
            $defaultCompanyId = $groupCompanies->first();

            if (isset($defaultCompanyId)) {
                $productTypes = ProductType::select('id AS value', 'type AS label')
                    ->where('group_company_id', $defaultCompanyId->value)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->get();
            }

            $data = [
                'productTypes' => $productTypes,
                'groupCompanies' => $groupCompanies
            ];

            $structures = StructuralReference::select('id', 'name')->get();

            return view('components.settings.products.create_edit', array_merge(['structures' => $structures,'selectedStructure'=>$selectedStructure], $data));
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }


    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            // $selectedStructureIds = $request->input('structure_reference_id', []);
            if ($request->productId) {
                $product = Product::find($request->productId);

                // Update product fields
                $product->group_company_id = $request->group_company_id;
                $product->code = $request->code;
                $product->name = $request->name;
                $product->product_type_id = $request->product_type_id;
                $product->density = $request->density;
                $product->usage = $request->usage;
                $product->batching_creation_time = $request->batching_creation_time;
                $product->temperature_creation_time = $request->temperature_creation_time;
                $product->status = $request->input('product_status', 'Inactive');
                $product->save();

                // Update product structural references
                // if (!empty($selectedStructureIds)) {
                //     $product->structuralReferences()->whereNotIn('id', $selectedStructureIds)->delete();
                // }

                // foreach ($request->structure_reference_id as $structure_id) {
                //     ProductStructuralReference::updateOrCreate([
                //         'product_id' => $product->id,
                //         'structure_reference_id' => $structure_id
                //     ]);
                // }

            } else {
                // Save Product
                $product = new Product();
                $product->group_company_id = $request->group_company_id;
                $product->code = $request->code;
                $product->name = $request->name;
                $product->product_type_id = $request->product_type_id;
                $product->density = $request->density;
                $product->usage = $request->usage;
                $product->batching_creation_time = $request->batching_creation_time;
                $product->temperature_creation_time = $request->temperature_creation_time;
                $product->status = $request->input('product_status', 'Inactive');
                $product->save();
// Save product structural references
                // foreach ($request->structure_reference_id as $structure_id) {
                //     ProductStructuralReference::updateOrCreate([
                //         'product_id' => $product->id,
                //         'structure_reference_id' => $structure_id
                //     ]);
                // }

                }
                if ($request->hasFile('image')) {
                    // delete existing image
                    if ($product->getMedia('image')->first()) {
                        $product->getMedia('image')->first()->delete();
                    }
                    // Add new image to media table
                    $product->addMediaFromRequest('image')->toMediaCollection('image');
                }
                return [
                    "status" => 200,
                    "data" => $product,
                    "redirect_url" => "/settings/products",
                    "message" => __('message.records_saved_successfully', ['static' => __('static.product')])
                ];

        } catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth()->user();
            $group_company_ids = $user->access_rights->pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label')->whereIn('id', $group_company_ids)->where('status', ConstantHelper::ACTIVE)->get();
            $product  = Product::where('id', $request->productId)->whereIn('group_company_id', $group_company_ids)->with('productContents')->first();
            $productTypes = collect([]);
            if (isset($product)) {
                $productTypes = ProductType::select('id As value', 'type AS label')->where('group_company_id', $product->group_company_id)->where('status', ConstantHelper::ACTIVE)->get();
            }
            $data = [
                'productTypes' => $productTypes,
                'product' => @$product,
                'groupCompanies' => $groupCompanies
            ];

            $structures = StructuralReference::select('id', 'name')->get();

            $selectedStructure = ProductStructuralReference::where('product_id', $product->id)->get()->pluck('structure_reference_id')->toArray();

            if (isset($product)) {
                return view('components.settings.products.create_edit', array_merge(['structures' => $structures, 'selectedStructure' => $selectedStructure], $data));
            } else {
                return view('components.settings.products.create_edit', $data)->with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __("static.product")]));
            }
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }


    public function storeProductContent(Request $request)
    {
        $validator = (new Validator($request))->storeContent();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            // Update Product
            if ($request->productContentId) {
                $content = ProductContent::find($request->productContentId);
                $content->product_id = $request->product_id;
                $content->content = $request->content;
                $content->quantity = $request->quantity;
                $content->status = $request->input('content_status', 'Inactive');
                $content->save();
            } else {
                // Save Product
                $content = new ProductContent();
                $content->product_id = $request->product_id;
                $content->content = $request->content;
                $content->quantity = $request->quantity;
                $content->status = $request->input('content_status', 'Inactive');
                $content->save();
            }
            return [
                "status" => 200,
                "data" => $content,
                "redirect_url" => "/settings/products/edit?productId={$content->product_id}",
                "message" => __('message.records_saved_successfully', ['static' => __('static.content')])
            ];
        } catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editProductContent(Request $request)
    {
        try {
            if ($request->productContentId) {
                $contentDetail = ProductContent::find($request->productContentId);
                if ($contentDetail) {
                    return $contentDetail;
                }
            }
            return false;
        } catch (Exception $ex) {
            throw new ApiGenericException($ex->getMessage());
        }
    }

    public function export(){
        $products = Product::with('product_type','structuralReferences.structural') ->orderByDesc('created_at')->get();

return Excel::download(new ProductMixExport($products),'ProductMix.xlsx');

    }
}
