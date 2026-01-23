<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Role;
use App\Models\User;
use App\Models\Country;
use App\Models\Province;
use App\Models\CompanyLocation;
use App\Helpers\ConstantHelper;
use App\Exceptions\ApiGenericException;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\User as Validator;

class SettingController extends Controller
{
    public function index(Request $request){
        $search = $request->search;
        $search_location = $request->search_location;

        $users = User::when($search, function($query)use($search){
                        $query->orWhere('name', 'LIKE', '%'.$search.'%');
                        $query->orWhere('email', 'LIKE', '%'.$search.'%');
                        $query->orWhere('mobile_no', 'LIKE', '%'.$search.'%');
                        $query->orWhere('user_type', 'LIKE', '%'.$search.'%');
                    });
        $users = $users->paginate(ConstantHelper::PAGINATE,['*'], 'users_page'); //Implement later
        // $users = $users->get();

        $locations = CompanyLocation::when($search_location, function($query)use($search_location){
            $query->orWhere('location', 'LIKE', '%'.$search_location.'%');
            $query->orWhere('site_name', 'LIKE', '%'.$search_location.'%');
            $query->orWhere('province', 'LIKE', '%'.$search_location.'%');
            $query->orWhere('contact_person', 'LIKE', '%'.$search_location.'%');
            $query->orWhere('phone', 'LIKE', '%'.$search_location.'%');
            $query->orWhere('email', 'LIKE', '%'.$search_location.'%');
        });
        $locations = $locations->paginate(ConstantHelper::PAGINATE,['*'], 'locations_page'); //Implement later
        // $locations = $locations->get();

        $data = [
            'users' => $users,
            'locations' => $locations,
            'search' => $search,
            'search_location' => $search_location
        ];
        return view('setting.index', $data);
    }

    public function home()
    {
        return view('components.settings.index');
    }

    public function createUser(Request $request){
        $roles = Role::get();

        $data = [
            'roles' => $roles,
        ];

        return view('setting.user.create', $data);
    }

    public function storeUser(Request $request){

        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{

            if($request->userId){
                // Update User
                $user = User::where('id', $request->userId)->first();
                if($user){
                    $user->group_company_id = Auth::user()->group_company_id;
                    $user->name = $request->name;
                    $user->email = $request->email;
                    $user->mobile_no = $request->phone;
                    $user->password = bcrypt($request->phone);
                    $user->status = $request->input('status', 'Inactive');
                    $user->update();
                }

            }else{

                // Save Customer in Users
                $isUserExist = User::where('email', $request->email)->first();
                if(!$isUserExist){
                    $user = new User();
                    $user->group_company_id = Auth::user()->group_company_id;
                    $user->name = $request->name;
                    $user->email = $request->email;
                    $user->password = bcrypt($request->phone);
                    $user->mobile_no = $request->phone;
                    $role = Role::where('id', $request->role_id)->first();
                    if($role){
                        $user->role_id = $role->id;
                        $user->user_type = $role->name;
                    }
                    $user->status = $request->input('status', 'Inactive');
                    $user->save();

                }else{
                    return [
                        "status" => 401,
                        "data" => "",
                        "message" => "User already exist!"
                    ];
                }

            }

            return [
                "status" => 200,
                "data" => $user,
                "message" => __('message.records_saved_successfully', ['static' => __('static.user')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editUser(Request $request){
        $user = User::where('id', $request->userId)->first();
        $roles = Role::get();

        $data = [
            'user' => $user,
            'roles' => $roles,
        ];

        return view('setting.user.create', $data);
    }

    public function createLocation(Request $request){
        $countries = Country::where('status', ConstantHelper::ACTIVE)->get();
        $data = [
            'countries' => $countries
        ];
        return view('setting.location.create', $data);
    }

    public function getProvinces(Request $request){
        if($request->countryId){
            $provinces = Province::where('country_id', $request->countryId)->where('status', ConstantHelper::ACTIVE)->get();
            if($provinces){
                return $provinces;
            }
        }
        return false;
    }

    public function storeLocation(Request $request){

        $validator = (new Validator($request))->locationStore();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            if($request->locationId){
                // Update Company Location
                $location = CompanyLocation::where('id', $request->locationId)->first();
                if($location){
                    $location->group_company_id = Auth::user()->group_company_id;
                    $location->location = $request->location_code;
                    $location->site_name = $request->name;
                    $location->contact_person = $request->contact_person;
                    $location->email = $request->email;
                    $location->phone = $request->mobile;
                    $location->country = $request->country;
                    $location->province = $request->province;
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
                    $location->group_company_id = Auth::user()->group_company_id;
                    $location->location = $request->location_code;
                    $location->site_name = $request->name;
                    $location->contact_person = $request->contact_person;
                    $location->email = $request->email;
                    $location->phone = $request->mobile;
                    $location->country = $request->country;
                    $location->province = $request->province;
                    $location->address = $request->address;
                    $location->latitude = $request->latitude;
                    $location->longitude = $request->longitude;
                    $location->status = $request->input('status', 'Inactive');
                    $location->save();

                }else{
                    return [
                        "status" => 401,
                        "data" => "",
                        "message" => "Location already exist!"
                    ];
                }

            }

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

    public function editLocation(Request $request){
       if($request->locationId){
            $location = CompanyLocation::where('id', $request->locationId)->first();
            if($location){
                $countries = Country::where('status', ConstantHelper::ACTIVE)->get();
                $data = [
                    'countries' => $countries,
                    'location' => $location
                ];
                return view('setting.location.create', $data);
            }
       }
    }
}
