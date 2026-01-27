<?php

namespace App\Http\Controllers;

use App\Helpers\CustomerHelper;
use App\Models\CustomerGroupCompany;
use App\Models\GroupCompany;
use DB;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProjectType;
use App\Models\CustomerProject;
use App\Models\CustomerProduct;
use App\Models\CustomerProjectSite;
use Exception;
use Illuminate\Http\Request;
use App\Helpers\ConstantHelper;
use App\Exceptions\ApiGenericException;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Customer as Validator;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerExport;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $search = $request->search;

            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $customers = Customer::with('address')
                                ->when($search, function($query)use($search){
                                    $query->where('code', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('name', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('email_id', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('mobile_no', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('contact_person', 'LIKE', '%'.$search.'%');
                                    $query->orWhereHas('address', function($q)use($search){
                                        $q->where('address', 'LIKE', '%'.$search.'%');
                                    });


                                })->where('group_id', $user -> group_id) -> orderByDesc('created_at');



                                if(isset($request->customer_id)){
                                    $customers=$customers-> where('id',$request->customer_id);
                                }

                                if(isset($request->group_company_id)){
                                    $customers=$customers-> whereHas('group_companies',function($subq)use($request){
                                        $subq ->wherein('group_company_id',$request->group_company_id);
                                    });
                                }

                                $searchCustomer = Customer::select('id AS value', DB::raw("CONCAT(contact_person, ' - ', name) AS label")) -> where([
                                    ['status', ConstantHelper::ACTIVE]
                                ]) -> wherehas('group_companies', function ($query) use($group_company_ids) {
                                    $query -> whereIn('group_company_id', $group_company_ids);
                                })-> get();

            $customers = $customers->paginate(ConstantHelper::PAGINATE);
            $data = [
                'customers' => $customers,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
                'searchCustomer' => $searchCustomer,
            ];
            return view('components.customers.index', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $countries = Country::where('status', ConstantHelper::ACTIVE)->get();
            $data = ['countries' => $countries, 'groupCompanies' => $groupCompanies];
            return view('components.customers.create_edit', $data);
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
        DB::beginTransaction();
        try{
            // Update Customer
            if($request->customerId){
                $customer = Customer::find($request->customerId);
                $customer->code = $request->code;
                $customer->name = $request->name;
                $customer->type = 'Credit';
                $customer->contact_person = $request->contact_person;
                $customer->mobile_no = $request->mobile_no;
                $customer->email_id = $request->email_id;
                $customer->status = $request->input('status', 'Inactive');
                $customer->save();
                // Update Customer's Address
                $customer->address()->update([
                    'country_id' => $customer->country_code_id,
                    'address' => $request->address
                ]);
                CustomerGroupCompany::where('customer_id', $customer -> id) -> delete();
                //Save access rights
                foreach ($request -> group_companies as $companyId) {
                    CustomerGroupCompany::create([
                        'customer_id' => $customer -> id,
                        'group_id' => auth() -> user() -> group_id,
                        'group_company_id' => $companyId
                    ]);
                }
                $contactPerson = $customer -> contact_person_details;
                $helperResponse = CustomerHelper::createCustomerDefaultAdmin($customer, $request -> contact_person, $request -> username,  $request -> email_id, $request -> mobile_no, $contactPerson ?-> id);
                if ($helperResponse['status'] === false) {
                    DB::rollBack();
                    throw new ApiGenericException($helperResponse['message']);
                }
            }else{
                // Save Customer
                $customer = new Customer();
                $customer->group_id = auth() -> user() -> group_id;
                $customer->code = $request->code;
                $customer->name = $request->name;
                $customer->type = 'Credit';
                $customer->contact_person = $request->contact_person;
                $customer->mobile_no = $request->mobile_no;
                $customer->email_id = $request->email_id;
                $customer->status = $request->input('status', 'Inactive');
                $customer->save();
                // Save Customer's Address
                $customer->address()->create([
                    'address' => $request->address
                ]);
                //Save access rights
                foreach ($request -> group_companies as $companyId) {
                    CustomerGroupCompany::create([
                        'customer_id' => $customer -> id,
                        'group_id' => auth() -> user() -> group_id,
                        'group_company_id' => $companyId
                    ]);
                }
                $helperResponse = CustomerHelper::createCustomerDefaultAdmin($customer, $request -> contact_person, $request -> username,  $request -> email_id, $request -> mobile_no);
                if ($helperResponse['status'] === false) {
                    DB::rollBack();
                    throw new ApiGenericException($helperResponse['message']);
                }
            }
            // Save Customer Image in Media
            if ($request->hasFile('image')) {
                // delete existing image
                if ($customer->getMedia('image')->first()) {
                    $customer->getMedia('image')->first()->delete();
                }
                 // Add new image to media table
                $customer->addMediaFromRequest('image')->toMediaCollection('image');
            }
            DB::commit();
            return [
                "status" => 200,
                "data" => $customer,
                "redirect_url" => "/customers/index",
                "message" => __('message.records_saved_successfully', ['static' => __('static.customer')])
            ];
        }catch (\Throwable $th) {
            DB::rollBack();
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $customer = Customer::with(['address', 'projects'])->where('id', $request->customerId)->where('group_id', $user -> group_id)->first();
            $customerGroupCompanies = CustomerGroupCompany::select('id', 'group_id', 'group_company_id')->where('customer_id', $customer ?-> id) -> where('status', ConstantHelper::ACTIVE) -> get() -> pluck('group_company_id');
            $countries = Country::where('status', ConstantHelper::ACTIVE)->get();
            $data = [
                'customer' => $customer,
                'countries' => $countries,
                'groupCompanies' => $groupCompanies,
                'customerGroupCompanies' => $customerGroupCompanies
            ];
            return view('components.customers.create_edit', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function createProject(Request $request){
        $projectTypes = ProjectType::where('status', ConstantHelper::ACTIVE)->get();
        $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $request -> group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> orderByRaw("id = ? DESC", [auth() -> user() -> group_company_id]) -> get();
        $firstGroupCompany = $groupCompanies -> first();
        $customers = collect([]);
        if ($request -> customer_id) {
            $customer = Customer::find($request -> customer_id);
            $customers = Customer::select('id AS value', 'name AS label') -> where('group_company_id', $customer ?-> group_company_id) -> where('status', ConstantHelper::ACTIVE) -> get();
        } else {
            if (isset($firstGroupCompany)) {
                $customers = Customer::select('id AS value', 'name AS label') -> where('group_company_id', $firstGroupCompany -> value) -> where('status', ConstantHelper::ACTIVE) -> get();
            }
        }
        $data = [
            'projectTypes' => $projectTypes,
            'customerId' => $request->customerId,
            'customers' => $customers,
            'groupCompanies' => $groupCompanies
        ];
        return view('customers.create_project', $data);
    }

    public function storeProject(Request $request){

        $validator = (new Validator($request))->storeProject();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            // Update Project
            if($request->projectId){
                $customerProject = CustomerProject::find($request->projectId);
                $customerProject->customer_id = $request->customer_id;
                $customerProject->code = $request->project_code;
                $customerProject->name = $request->project_name;
                $customerProject->contractor_name = $request->contractor_name;
                $customerProject->type = $request->project_type;
                $customerProject->start_date = $request->start_date;
                $customerProject->end_date = $request->end_date;
                $customerProject->status = $request->input('project_status', ConstantHelper::INACTIVE);
                $customerProject->save();

                // Update Customer's Address
                $customerProject->address()->update([
                    'address' => $request->address
                ]);

            }else{
                // Save Project
                $customerProject = new CustomerProject();
                $customerProject->customer_id = $request->customer_id;
                $customerProject->code = $request->project_code;
                $customerProject->name = $request->project_name;
                $customerProject->contractor_name = $request->contractor_name;
                $customerProject->type = $request->project_type;
                $customerProject->start_date = $request->start_date;
                $customerProject->end_date = $request->end_date;
                $customerProject->status = $request->input('project_status', ConstantHelper::INACTIVE);
                $customerProject->save();

                // Save Customer's Address
                $customerProject->address()->create([
                    'address' => $request->address
                ]);
            }

            // Save Customer's Project Image in Media
            if ($request->hasFile('image')) {
                // delete existing image
                if ($customerProject->getMedia('image')->first()) {
                    $customerProject->getMedia('image')->first()->delete();
                }
                 // Add new image to media table
                $customerProject->addMediaFromRequest('image')->toMediaCollection('image');
            }

            return [
                "status" => 200,
                "data" => $customerProject,
                "redirect_url" => "/customer-projects?customerId={$customerProject->customer_id}",
                "message" => __('message.records_saved_successfully', ['static' => __('static.project')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editProject(Request $request)
    {
        $project = CustomerProject::where('id',$request->projectId)->with(['sites', 'products.product'])->first();
        $projectTypes = ProjectType::where('status', ConstantHelper::ACTIVE)->get();
        $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $request -> group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> orderByRaw("id = ? DESC", [auth() -> user() -> group_company_id]) -> get();
        $customers = Customer::select('id AS value', 'name AS label') -> where('group_company_id', $project ?-> customer ?-> group_company_id) -> where('status', ConstantHelper::ACTIVE) -> get();
        // dd($project->toArray());
        $data = [
            'projectTypes' => $projectTypes,
            'customerId' => $request->customerId,
            'project' => $project,
            'customers' => $customers,
            'groupCompanies' => $groupCompanies
        ];
        return view('customers.create_project', $data);
    }

    public function storeProjectSiteAddress(Request $request){
        $validator = (new Validator($request))->storeSiteAddress();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            // Update Site Address
            if($request->siteId){
                $site = CustomerProjectSite::find($request->siteId);
                $site->cust_project_id = $request->project_id;
                $site->company_location_id = $request->company_location_id;
                $site->name = $request->site_name;
                $site->address = $request->site_address;
                $site->latitude = $request->latitude;
		$site->longitude = $request->longitude;
                $site->status = $request->input('site_status', 'Inactive');
                $site->save();

            }else{
                // Save Site Address
                $site = new CustomerProjectSite();
                $site->cust_project_id = $request->project_id;
                $site->company_location_id = $request->company_location_id;
                $site->name = $request->site_name;
                $site->address = $request->site_address;
                $site->latitude = $request->latitude;
		$site->longitude = $request->longitude;
		$site->is_default = 1;
                $site->status = $request->input('site_status', 'Inactive');
                $site->save();
            }

            return [
                "status" => 200,
                "data" => $site,
                "message" => __('message.records_saved_successfully', ['static' => __('static.project_site')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function getProductDetails(Request $request){
        if($request->id){
            $product = Product::where('id', $request->id)->with('product_type')->first();
            if($product){
                return $product;
            }
        }
        return false;
    }

    public function storeProduct(Request $request){
        $validator = (new Validator($request))->storeCustomerProduct();
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

    public function deleteProjectSite(Request $request){

        if($request->addressId){
            CustomerProjectSite::where('id', $request->addressId)->forceDelete();
            return [
                "success" => true,
                "status" => 200,
                "message" => __('message.records_deleted_successfully', ['static' => __('static.project_site')])
            ];
        }else{
            return [
                "success" => false,
                "status" => 401,
                "message" => "Something went wrong!"
            ];
        }
    }

    public function deleteProjectProduct(Request $request){

        if($request->productId){
            CustomerProduct::where('id', $request->productId)->forceDelete();
            return [
                "success" => true,
                "status" => 200,
                "message" => __('message.records_deleted_successfully', ['static' => __('static.product')])
            ];
        }else{
            return [
                "success" => false,
                "status" => 401,
                "message" => "Something went wrong!"
            ];
        }
    }

    public function editProjectSite(Request $request){
        if($request->siteId){
            $siteDetail = CustomerProjectSite::with('service_company_location') -> where('id', $request->siteId) ->first();
            if($siteDetail){
                return $siteDetail;
            }
        }
        return false;
    }

    public function editProjectProduct(Request $request){
        if($request->productId){
            $productDetail = CustomerProduct::where('id', $request->productId)->with('product.product_type')->first();
            $customerProducts = CustomerProduct::where(['customer_id' =>$request->customerId, 'project_id'=>$request->projectId])->pluck('product_id')->toArray();
            if($customerProducts){
                $products = Product::whereNotIn('id', $customerProducts)->get();
            }else{
                $products = collect();
            }

            if($productDetail){
                return $data = [
                    'productDetail' => $productDetail,
                    'products' => $products
                ];
            }
        }
        return false;
    }

   public function exportCustomers(Request $request)
{
    $user = auth() -> user();
    // Filter data based on request parameters
    $customers = Customer::query()->where('group_id', $user -> group_id);

    if ($request->filled('search')) {
        $customers->where('name', 'LIKE', "%{$request->search}%");
    }

    if ($request->filled('group_company_id')) {
        $customers->withWhereHas('group_companies', function ($subq) use ($request) {
            $subq->where('group_company_id', $request->group_company_id)
                 ->with('company'); // Ensure company relationship is loaded
        });
    } else {
        $customers->with('group_companies.company'); // Load company if no specific filter
    }

    $filteredCustomers = $customers->get();

    // Export using an Excel export class
    return Excel::download(new CustomerExport($filteredCustomers), 'Customers.xlsx');
}

}
