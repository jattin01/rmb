<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\Temperature;
use Exception;
use Illuminate\Http\Request;

class TemperatureController extends Controller
{
    public function index(Request $request)
    {
        try {
            $temps = Temperature::select('id', 'temp') -> where([
                ['group_company_id', $request -> group_company_id],
                ['status', ConstantHelper::ACTIVE]
            ]) -> get();
            return array(
                'message' => 'Tempratures retrieved successfully',
                'data' => array(
                    'temperatures' => $temps
                )
            );
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
