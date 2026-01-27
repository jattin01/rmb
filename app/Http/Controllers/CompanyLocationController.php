<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Exports\CompanyLocationExport;
use App\Helpers\ConstantHelper;
use App\Models\CompanyLocation;
use App\Models\Country;
use App\Models\GroupCompany;
use App\Models\LocationShift;
use App\Models\User;
use App\Models\UserAccessRight;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\CompanyLocation as Validator;
use Maatwebsite\Excel\Facades\Excel;

class CompanyLocationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth() -> user();
            $location_ids = $user -> access_rights -> pluck('location_id');
            $groupCompanyIds = $user -> access_rights -> pluck('group_company_id');
            $search = $request->search;
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();

            $locations = CompanyLocation::when($search, function($query)use($search){
                $query->orWhere('location', 'LIKE', '%'.$search.'%');
                $query->orWhere('site_name', 'LIKE', '%'.$search.'%');
                $query->orWhere('province', 'LIKE', '%'.$search.'%');
                $query->orWhere('contact_person', 'LIKE', '%'.$search.'%');
                $query->orWhere('phone', 'LIKE', '%'.$search.'%');
                $query->orWhere('email', 'LIKE', '%'.$search.'%');
            }) -> whereIn('group_company_id', $groupCompanyIds) -> orderByDesc('created_at') -> paginate(ConstantHelper::PAGINATE) -> appends(['search' => $search]);
            $data = [
                'locations' => $locations,
                'search' => $search,
                'groupCompanies' => $groupCompanies,
            ];
            return view('components.settings.company_locations.index', $data);
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
            $data = [
                'countries' => $countries,
                'groupCompanies' => $groupCompanies
            ];
            return view('components.settings.company_locations.create_edit', $data);
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
            if($request->locationId){
                // Update Company Location
                $location = CompanyLocation::where('id', $request->locationId)->first();
                if($location){
                    $location->group_company_id = $request->group_company_id;
                    $location->location = $request->location_code;
                    $location->site_name = $request->name;
                    $location->contact_person = $request->contact_person;
                    $location->email = $request->email;
                    $location->phone = $request->mobile;
                    $location->address = $request->address;
                    $location->latitude = $request->latitude;
                    $location->longitude = $request->longitude;
                    $location->status = $request->input('status', 'Inactive');
                    $location->update();
                }
            }else{
                // Save Location in CompanyLocation
                $isLocationExist = CompanyLocation::where('location', $request->location_code)->first();
                if(!$isLocationExist){
                    $location = new CompanyLocation();
                    $location->group_company_id = $request->group_company_id;
                    $location->location = $request->location_code;
                    $location->site_name = $request->name;
                    $location->contact_person = $request->contact_person;
                    $location->email = $request->email;
                    $location->phone = $request->mobile;
                    $location->address = $request->address;
                    $location->latitude = $request->latitude;
                    $location->longitude = $request->longitude;
                    $location->status = $request->input('status', 'Inactive');
                    $location->save();
                }else{
                    throw new ApiGenericException("Location already exists");
                }
            }
            UserAccessRight::updateOrCreate(
                ['user_id' => auth() -> user() -> id, 'group_id' => auth() -> user() -> group_id, 'group_company_id' => $request -> group_company_id, 'location_id' => $location ?-> id],
                ['status' => ConstantHelper::ACTIVE]
            );
            LocationShift::updateOrCreate(
                ['group_company_id' => $request -> group_company_id, 'company_location_id' => $location -> id],
                ['shift_start' => '00:00:00', 'shift_end' => "23:59:00", 'status' => ConstantHelper::ACTIVE]
            );
            return [
                "status" => 200,
                "data" => $location,
                "message" => __('message.records_saved_successfully', ['static' => __('static.location')])
            ];
        }
        catch(\Throwable $th){
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $user = auth() -> user();
            $group_company_ids = $user -> access_rights -> pluck('group_company_id');
            $groupCompanies = GroupCompany::select('id AS value', 'comp_name AS label') -> whereIn('id', $group_company_ids) -> where('status', ConstantHelper::ACTIVE) -> get();
            $location = CompanyLocation::where('id', $request->locationId)->first();
            $countries = Country::where('status', ConstantHelper::ACTIVE)->get();
            $data = [
                'countries' => $countries,
                'location' => $location,
                'groupCompanies' => $groupCompanies
            ];
            if (isset($location))
            {
                return view('components.settings.company_locations.create_edit', $data);
            }
            else
            {
                return view('components.settings.company_locations.create_edit', $data) -> with(ConstantHelper::WARNING, __("message.no_data_found", ['static' => __("static.location")]));
            }
        } catch(Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function getUsers(Request $request, String $locationId)
    {
        try {
            $users = User::select('id As value', 'name AS label') -> where('status', ConstantHelper::ACTIVE) -> where('user_type', ConstantHelper::USER_TYPE_ADMIN) -> whereHas('access_rights', function ($query) use($locationId) {
                $query -> where('location_id', $locationId) -> where('status', ConstantHelper::ACTIVE);
            }) -> get();
            return array(
                'message' => 'Users found successfully',
                'data' => array(
                    'users' => $users
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    public function export(){
        
        $locations = CompanyLocation::select('group_company_id','location', 'site_name', 'contact_person', 'email', 'phone', 'country', 'province', 'status') -> orderByDesc('created_at')->get();

        return Excel::download(new CompanyLocationExport($locations),'CompanyLocation.xlsx' );
    }
}
