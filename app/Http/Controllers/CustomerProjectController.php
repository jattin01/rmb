<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\GroupCompany;
use App\Models\ProjectType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use View;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CustomerProject as Validator;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerProjectsExport;


class CustomerProjectController extends Controller
{
    public function getList(Request $request, String $customerId)
    {
        try {
            $projects = CustomerProject::select("id AS value", "name AS label") -> where([
                ['status', ConstantHelper::ACTIVE],
                ['customer_id', $customerId],
                ['start_date', '<=', Carbon::now()],
                ['end_date', '>=', Carbon::now()],
            ]) -> get();
            return array(
                'data' => array(
                    'customer_projects' => $projects
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function getProjectsForChat(Request $request)
    {
        try {
            $projects = CustomerProject::whereHas('customer', function ($query) use($request) {
                $query -> whereIn('group_company_id', $request -> group_company_ids);
            }) -> with('customer') -> get();

            $html = View::make('layouts.auth.partials.chat_list', ['projects' => $projects]) -> render();

            return response() -> json([
                'html' => $html
            ]);

        } catch(Exception $ex) {
            return response() -> json([
                'html' => '',
                'message' => 'Internal server error',
                'error' => $ex -> getMessage()
            ]);
        }
    }

    public function getProjectChat(Request $request, String $projectId)
    {
        try {
            $customerProject = CustomerProject:: with('customer') -> find($projectId);
            $html = View::make('layouts.auth.partials.chat', ['project' => $customerProject]) -> render();
            return response() -> json([
                'html' => $html
            ]);
        } catch(Exception $ex) {
            return response() -> json([
                'html' => '',
                'message' => 'Internal server error',
                'error' => $ex -> getMessage()
            ]);
        }
    }

    public function getProjectsForChatHeader(Request $request)
    {
        try {
            $projectIds = CustomerProject::select('id', 'customer_id', 'name') -> whereHas('customer', function ($query) use($request) {
                $query -> whereIn('group_company_id', $request -> group_company_ids);
            }) -> with('customer') -> get() -> pluck('id');

            return response() -> json([
                'project_ids' => $projectIds
            ]);

        } catch(Exception $ex) {
            return response() -> json([
                'project_ids' => [],
                'message' => 'Internal server error',
                'error' => $ex -> getMessage()
            ]);
        }
    }

    public function index(Request $request)
    {
        try {

            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');


            $projects = CustomerProject::whereHas('customer', function ($query) {
                $query -> where('group_id', auth() -> user() -> group_id);

            });

            $customers = Customer::select('id', 'name')->whereHas('group_companies', function ($query) use($group_company_ids) {
                $query -> whereIn('group_company_id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE);
            }) -> where('status', ConstantHelper::ACTIVE) -> get();
            $formattedCustomers = $customers->map(function ($customer) {
                $customer -> value = $customer -> id;
                $customer -> label = $customer -> name;
                return $customer;
            });
            if ($request -> customerId) {
                $projects = $projects -> where('customer_id', $request -> customerId);
	    }
            $search = $request->search;
            $projects = $projects
                                ->when($search, function($query)use($search){
                                    $query->where('code', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('name', 'LIKE', '%'.$search.'%');
                                    $query->orWhereHas('customer', function($q)use($search){
                                        $q->where('name', 'LIKE', '%'.$search.'%');
                                    });
                                });
                                if(($request->customer_id)){
                                    $projects=$projects-> where('customer_id',$request->customer_id);
                                }
                                if(($request->name)){
                                    $projects=$projects-> where('name',$request->name);
                                }
                                if(($request->type)){
                                    $projects=$projects-> where('type',$request->type);
                                }
            $projects = $projects->paginate(ConstantHelper::PAGINATE);
            return view('components.customers.projects.index', ['projects' => $projects, 'customerId' => $request -> customerId,
            'search' => $search,
            'customers' => $customers,
            'customers' => $formattedCustomers,

        ]);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function create(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $projectTypes = ProjectType::select('id', 'name') -> where('status', ConstantHelper::ACTIVE)->get();
            $customers = Customer::select('id', 'name')->whereHas('group_companies', function ($query) use($group_company_ids) {
                $query -> whereIn('group_company_id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE);
            }) -> where('status', ConstantHelper::ACTIVE) -> get();
            $formattedCustomers = $customers->map(function ($customer) {
                $customer -> value = $customer -> id;
                $customer -> label = $customer -> name;
                return $customer;
            });
            $data = [
                'projectTypes' => $projectTypes,
                'customerId' => $request->customerId,
                'customers' => $formattedCustomers,
            ];
            return view('components.customers.projects.create_edit', $data);
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validator = (new Validator($request))->store();
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
            }
            // Create/Update Customer's Address
            $customerAddress = $customerProject -> address;
            if (isset($customerAddress)) {
                $customerProject->address()->update([
                    'address' => $request->address
                ]);
            } else {
                $customerProject->address()->create([
                    'address' => $request->address
                ]);
            }
            $chatRoom = ChatRoom::where([
                ['project_id', $customerProject -> id],
                ['entity_type', ConstantHelper::USER_TYPE_CUST],
                ['entity_id', $customerProject -> customer_id],
                ['status', ConstantHelper::ACTIVE],
            ]) -> first();
            if (!isset($chatRoom)) {
                ChatRoom::create([
                    'project_id' => $customerProject -> id,
                    'entity_id' => $customerProject -> customer_id,
                    'entity_type' => ConstantHelper::USER_TYPE_CUST,
                    'status' => ConstantHelper::ACTIVE
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

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_ids');
            $project = CustomerProject::where('id',$request->projectId)->with(['sites', 'products.product'])->first();
            $projectTypes = ProjectType::where('status', ConstantHelper::ACTIVE)->get();
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $customers = Customer::select('id AS value', 'name AS label') -> where('group_company_id', $project ?-> customer ?-> group_company_id) -> where('status', ConstantHelper::ACTIVE) -> get();
            $data = [
                'projectTypes' => $projectTypes,
                'customerId' => $request->customerId,
                'project' => $project,
                'customers' => $customers,
                'groupCompanies' => $groupCompanies
            ];
            if (isset($project)) {
                return view('components.customers.projects.create_edit', $data);
            } else {
                return view('components.customers.projects.create_edit', $data)->with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __("static.project")]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        // Filter data based on request parameters
        $query = CustomerProject::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('name')) {
            $query->where('name', $request->name);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $projects = $query->get(); // Get all filtered data

        // Export using an Excel export class
        return Excel::download(new CustomerProjectsExport($projects), 'CustomerProjects.xlsx');
    }
}
