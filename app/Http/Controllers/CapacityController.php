<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Models\Capacity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Lib\Validations\Capacity as Validator;





class CapacityController extends Controller


{


    public function index(Request $request)
{

    try {
        // Validate the search query


        $user = auth()->user();
        $search = $request->search;

        $capacities = Capacity::select('id','value', 'uom', 'status')
            ->when($search, function($query) use ($search) {
                $query->where('value', 'LIKE', '%'.$search.'%')
                    ->orWhere('uom', 'LIKE', '%'.$search.'%');
            })
            ->orderByDesc('created_at')
            ->paginate(ConstantHelper::PAGINATE)
            ->appends(['search' => $search]);
        return view('components.settings.capacity.index', compact('capacities'));
    } catch (Exception $ex) {
        // Return a view with error message if something goes wrong
        return view('components.common.internal_error', ['message' => $ex->getMessage()]);
    }
}

    public function create(Request $request)
    {
        try {
            $user = auth() -> user();

            $capacity = Capacity::select('id','value', 'uom') -> where('status', ConstantHelper::ACTIVE) -> get();




            return view('components.settings.capacity.create_edit', $capacity);
        } catch (Exception $ex) {
            return view('components.common.internal_error', ['message' => $ex -> getMessage()]);
        }
    }

    public function store(Request $request){

        $validator = (new Validator($request))->store();
        if($validator->fails()){
            throw new ValidationException($validator);
        }
        try{
            // Update
            if($request->CapacityId){
                // dd($request->CapacityId);
                $capacity = Capacity::find($request->CapacityId);
                $capacity->value = $request->value;
                $capacity->uom = $request->uom;


                $capacity->status = $request->input('status', 'Inactive');
                $capacity->save();
            }else{
                // Save
                $capacity = new Capacity();
                $capacity->value = $request->value;
                $capacity->uom = $request->uom;
                $capacity->status = $request->input('status', 'Inactive');
                $capacity->save();
            }


            return [
                "status" => 200,
                "data" => $capacity,
                "redirect_url" => "/capacity",
                "message" => __('message.records_saved_successfully', ['static' => __('static.capacity')])
            ];
        }catch (\Throwable $th) {
            throw new ApiGenericException($th->getMessage());
        }
    }


    public function edit(Request $request)
    {
        try {
            $user = auth()->user();

            $capacity = Capacity::find($request->CapacityId);



            $activeCapacities = Capacity::select('id','value', 'uom')
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            return view('components.settings.capacity.create_edit', [
                'capacity' => $capacity,
                'activeCapacities' => $activeCapacities
            ]);
        } catch (Exception $ex) {
            // Handle the error and show an internal error view
            return view('components.common.internal_error', ['message' => $ex->getMessage()]);
        }
    }



    }

