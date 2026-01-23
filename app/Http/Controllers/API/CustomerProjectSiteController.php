<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Helpers\CustomerProjectSiteHelper;
use App\Http\Controllers\Controller;
use App\Models\CompanyLocation;
use App\Models\Customer;
use App\Models\CustomerProjectSite;
use DB;
use Exception;
use Illuminate\Http\Request;
use App\Lib\Validations\CustomerProjectSite as Validator;
use Illuminate\Validation\ValidationException;

class CustomerProjectSiteController extends Controller
{
    public function index(Request $request, String $projectId)
    {
        try {
            $sites = CustomerProjectSite::where([
                ['cust_project_id', $projectId],
                ['status', ConstantHelper::ACTIVE]
            ]) -> select('id', 'name', 'is_default', 'address', 'latitude', 'longitude', 'company_location_id') -> orderByRaw('is_default DESC') -> get();
            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.sites")]),
                'data' => array(
                    'sites' => $sites
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function markAsDefault(Request $request)
    {
        $validator = (new Validator($request))->markAsDefault();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            CustomerProjectSite::where([
                ['status', ConstantHelper::ACTIVE],
                ['cust_project_id', $request -> project_id]
            ]) -> where('id', '!=', $request -> id) -> update([
                'is_default' => 0
            ]);
            $defaultProjectSite = CustomerProjectSite::where([
                ['status', ConstantHelper::ACTIVE],
            ]) -> where('id', $request -> id) -> first();
            if (isset($defaultProjectSite)) {
                $defaultProjectSite -> is_default = 1;
                $defaultProjectSite -> save();
                DB::commit();
                return array(
                    'message' => "Default marked successfully",
                    'data' => array(
                        'name' => $defaultProjectSite -> name,
                        'address' => $defaultProjectSite -> address,
                        'id' => $defaultProjectSite -> id
                    )
                );
            } else {
                DB::rollBack();
                throw new ApiGenericException("Site does not exists");
            }
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = (new Validator($request))->storeOrUpdate();
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        try {
            DB::beginTransaction();
            $customerProjectSite = null;
            if ($request -> id) {
                $customerProjectSite = CustomerProjectSite::select('id', 'name', 'address', 'latitude', 'longitude', 'is_default', 'cust_project_id', 'company_location_id') -> find($request -> id);
            } else {
                $customerProjectSite = new CustomerProjectSite();
            }
            $customerProjectSite -> cust_project_id = $request -> project_id;
            $customerProjectSite -> name = $request -> name;
            $customerProjectSite -> address = $request -> address;
            $customerProjectSite -> latitude = $request -> latitude;
            $customerProjectSite -> longitude = $request -> longitude;
            $customerProjectSite -> is_default = $request -> is_default;
            $customer = Customer::find($request -> customer_id);
            //Assign closest location
            $groupCompanyIds = $customer -> group_companies -> pluck('group_company_id');
            $locations = CompanyLocation::whereIn('group_company_id', $groupCompanyIds) -> where('status', ConstantHelper::ACTIVE) -> orderByDesc('created_at') -> limit(25) -> get();
            $company_location_id = CustomerProjectSiteHelper::assignServiceLocation($request -> latitude, $request -> longitude, $locations);
            $customerProjectSite -> company_location_id = $company_location_id;
            $customerProjectSite -> save();
            //Hide or Remove unecessary data
            $customerProjectSite -> makeHidden(['created_by' ,'updated_at', 'created_at']);
            if ($customerProjectSite -> is_default) {
                CustomerProjectSite::where([
                    ['status', ConstantHelper::ACTIVE],
                    ['cust_project_id', $customerProjectSite -> cust_project_id]
                ]) -> where('id', '!=', $customerProjectSite -> id) -> update([
                    'is_default' => 0
                ]);
            }
            DB::commit();
            return array(
                'message' => 'Location saved successfully',
                'data' => array(
                    'site' => $customerProjectSite
                )
            );
        } catch(Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function view(Request $request, String $id)
    {
        try {
            $customerProjectSite = CustomerProjectSite::select('id', 'name', 'address', 'latitude', 'longitude', 'is_default', 'cust_project_id', 'company_location_id') -> find($id);
            if (isset($customerProjectSite)) {
                return array(
                    'message' => 'Site retrieved successfully',
                    'data' => array(
                        'customer_project_site' => $customerProjectSite
                    )
                );
            } else {
                throw new ApiGenericException("Project Sites not found");
            }
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
