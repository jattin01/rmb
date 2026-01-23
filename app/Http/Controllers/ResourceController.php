<?php

namespace App\Http\Controllers;

use App\Models\DriverTransitMixer;
use Auth;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\BatchingPlant;
use App\Models\CompanyLocation;
use App\Models\TransitMixer;
use App\Models\PumpType;
use App\Models\Pump;
use App\Helpers\ConstantHelper;
use App\Exceptions\ApiGenericException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Resource as Validator;

class ResourceController extends Controller
{
    public function index(Request $request){
        $search = $request->search;
        $mixerSearch = $request->mixer_search;
        $pumpSearch = $request->pump_search;

        $companyLocations = CompanyLocation::with('batchingPlants')
                                ->when($search, function($query)use($search){
                                    $query->where('location', 'LIKE', '%'.$search.'%');
                                    $query->orWhere('address', 'LIKE', '%'.$search.'%');
                                    $query->orWhereHas('batchingPlants', function($q)use($search){
                                        $q->where('plant_name', 'LIKE', '%'.$search.'%');
                                        $q->orWhere('long_name', 'LIKE', '%'.$search.'%');
                                        $q->orWhere('description', 'LIKE', '%'.$search.'%');
                                    });
                                })
                            ->get();

        $transitMixers = TransitMixer::when($mixerSearch, function($query)use($mixerSearch){
                                        $query->where('truck_name', 'LIKE', '%'.$mixerSearch.'%');
                                        $query->orWhere('plate_no', 'LIKE', '%'.$mixerSearch.'%');
                                        $query->orWhere('driver_code', 'LIKE', '%'.$mixerSearch.'%');
                                        $query->orWhere('driver_name', 'LIKE', '%'.$mixerSearch.'%');
                                        // $query->orWhere('truck_capacity', 'LIKE', '%'.$search.'%'); 
                                    })
                                    ->get();

        $pumps = Pump::when($pumpSearch, function($query)use($pumpSearch){
                                    $query->where('pump_name', 'LIKE', '%'.$pumpSearch.'%');
                                    $query->orWhere('type', 'LIKE', '%'.$pumpSearch.'%');
                                    $query->orWhere('description', 'LIKE', '%'.$pumpSearch.'%');
                                })
                                ->get();

        $data = [
            'companyLocations' => $companyLocations,
            'transitMixers' => $transitMixers,
            'pumps' => $pumps,
            'search' => $search,
            'mixerSearch' => $mixerSearch,
            'pumpSearch' => $pumpSearch
        ];

        return view('resources.index', $data);
    }

    public function createBatchingPlant(Request $request){
        $locations = CompanyLocation::get();
        $data = [
            'locations' => $locations
        ];

        return view('resources.batching_plant_create', $data);
    }

    public function createTransitMixer(Request $request){
        $drivers = Driver::where('status', ConstantHelper::ACTIVE)->get();
        $data = ['drivers' => $drivers];
        return view('resources.transit_mixer_create', $data);
    }

    public function storeBatchingPlant(Request $request){
        $validator = (new Validator($request))->storeBatchingPlant();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            // Update Batching Plant
            if($request->plantId){
                $plant = BatchingPlant::find($request->plantId);
                $plant->group_company_id = Auth::user()->group_company_id;
                $plant->company_location_id = $request->company_location_id;
                $plant->plant_name = $request->plant_name;
                $plant->long_name = $request->long_name;
                $plant->capacity = $request->capacity;
                $plant->description = $request->description;
                $plant->status = $request->input('plant_status', 'Inactive');
                $plant->save();

            }else{
                // Save Batching Plant
                $plant = new BatchingPlant();
                $plant->group_company_id = Auth::user()->group_company_id;
                $plant->company_location_id = $request->company_location_id;
                $plant->plant_name = $request->plant_name;
                $plant->long_name = $request->long_name;
                $plant->capacity = $request->capacity;
                $plant->description = $request->description;
                $plant->status = $request->input('plant_status', 'Active');
                $plant->save();

            }

            return [
                "status" => 200,
                "data" => $plant,
                "redirect_url" => "/resources/index",
                "message" => __('message.records_saved_successfully', ['static' => __('static.plant')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editBatchingPlant(Request $request){
        if($request->plantId){
            $locations = CompanyLocation::get();
            $plantDetail = BatchingPlant::find($request->plantId);

            $data = [
                'locations' => $locations,
                'plantDetail' => isset($plantDetail) ? $plantDetail : ""
            ];
        }
        return view('resources.batching_plant_create', $data);
    }

    public function storeTransitMixer(Request $request){
        
        $validator = (new Validator($request))->storeTransitMixer();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            DB::beginTransaction();
            // Update Transit Mixer
            if($request->mixerId){
                $mixer = TransitMixer::find($request->mixerId);
                $mixer->group_company_id = Auth::user()->group_company_id;
                $mixer->truck_name = $request->truck_name;
                $mixer->registration_no = $request->plate_no;
                $mixer->loading_time = 10;
                $mixer->driver_id = $request->driver_code;
                // $mixer->driver_name = $request->driver_name;
                $mixer->truck_capacity = $request->capacity;
                $mixer->description = $request->description;
                $mixer->status = $request->input('mixer_status', 'Inactive');
                $mixer->save();

            }else{
                // Save Batching Plant
                $mixer = new TransitMixer();
                $mixer->group_company_id = Auth::user()->group_company_id;
                $mixer->truck_name = $request->truck_name;
                $mixer->registration_no = $request->plate_no;
                $mixer->loading_time = 10;
                $mixer->driver_id = $request->driver_code;
                // $mixer->driver_name = $request->driver_name;
                $mixer->truck_capacity = $request->capacity;
                $mixer->description = $request->description;
                $mixer->status = $request->input('mixer_status', 'Inactive');
                $mixer->save();

            }

            DriverTransitMixer::where('transit_mixer_id', $mixer -> id) -> delete();

            DriverTransitMixer::updateOrCreate(
                ['driver_id' => $request -> driver_code, 'transit_mixer_id' => $mixer -> id],
                ['status' => 'Active']
            );

            DB::commit();
            return [
                "status" => 200,
                "data" => $mixer,
                "redirect_url" => "/resources/index",
                "message" => __('message.records_saved_successfully', ['static' => __('static.mixer')])
            ];

        }catch (\Throwable $th) {
            DB::rollBack();
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editTransitMixer(Request $request){
        if($request->mixerId){
            $mixerDetails = TransitMixer::find($request->mixerId);
            if($mixerDetails){
                $drivers = Driver::where('status', ConstantHelper::ACTIVE)->get();
                $data = ['drivers' => $drivers, 'mixerDetails' => $mixerDetails];
                return view('resources.transit_mixer_create', $data);
            }
        }
    }

    public function createPump(Request $request){
        $pumpType = PumpType::get();
        $data = ['pumpTypes' => $pumpType];

        return view('resources.pump_create', $data);
    }

    public function storePump(Request $request){
        
        $validator = (new Validator($request))->storePump();
        if($validator->fails()){
            throw new ValidationException($validator);
        }

        try{
            // Update Pump
            if($request->pumpId){
                $pump = Pump::find($request->pumpId);
                $pump->group_company_id = Auth::user()->group_company_id;
                $pump->pump_name = $request->pump_name;
                $pump->type = $request->type;
                $pump->pump_capacity = $request->pump_capacity;
                $pump->description = $request->description;
                $pump->status = $request->input('pump_status', 'Inactive');
                $pump->save();

            }else{
                // Save Pump
                $pump = new Pump();
                $pump->group_company_id = Auth::user()->group_company_id;
                $pump->pump_name = $request->pump_name;
                $pump->type = $request->type;
                $pump->pump_capacity = $request->pump_capacity;
                $pump->description = $request->description;
                $pump->status = $request->input('pump_status', 'Inactive');
                $pump->save();

            }

            return [
                "status" => 200,
                "data" => $pump,
                "redirect_url" => "/resources/index",
                "message" => __('message.records_saved_successfully', ['static' => __('static.pump')])
            ];

        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }

    public function editPump(Request $request){
        if($request->pumpId){
            $pumpDetails = Pump::find($request->pumpId);

            if($pumpDetails){
                $pumpType = PumpType::get();
                $data = ['pumpTypes' => $pumpType, 'pumpDetails' => $pumpDetails];
                return view('resources.pump_create', $data);
            }
        }
    }
}
