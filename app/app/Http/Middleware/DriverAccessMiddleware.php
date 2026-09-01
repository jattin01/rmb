<?php

namespace App\Http\Middleware;

use App\Exceptions\UserNotFound;
use App\Helpers\ConstantHelper;
use App\Models\Driver;
use App\Models\DriverTransitMixer;
use App\Models\Pump;
use App\Models\TransitMixer;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class DriverAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $personalAccessToken = PersonalAccessToken::findToken($token);
        if ($personalAccessToken) {
            $driver = Driver::find($personalAccessToken -> user_type_id);
            $driverTruckMapping = DriverTransitMixer::where('driver_id', $driver ?-> id) -> where('transit_mixer_id',$personalAccessToken -> user_type_sub_id)->where('status', ConstantHelper::ACTIVE) -> first();
            $pump = Pump::where('operator_id',$driver->id)->where('id',$personalAccessToken->user_type_sub_id)->where('status','active')->first();
            // dd($pump);
            if (isset($driver) && ($driverTruckMapping||(isset($pump)))) {
        // dd('a');
                $request->merge(['driver_id' => $personalAccessToken->user_type_id]);
                $request->merge(['transit_mixer_id' => $driverTruckMapping?->transit_mixer_id]);
                $request->merge(['pump_id' => $pump?->id]);
                $request->merge(['group_company_id' => $driver->group_company_id]);
            } else {
                throw new UserNotFound(__("message.invalid_data", ['static' => __("static.driver")]));
            }
        }
        return $next($request);
    }
}
