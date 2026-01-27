<?php

namespace App\Http\Middleware;

use App\Exceptions\UserNotFound;
use App\Models\Customer;
use App\Models\CustomerTeamMember;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CustomerAccessMiddleware
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
            $customer = CustomerTeamMember::with('access_rights') -> find($personalAccessToken -> user_type_id);
            if ((isset($customer) && isset($customer -> access_rights) && count($customer -> access_rights) > 0) || isset($customer) && $customer -> is_admin) {
                $request->merge(['customer_id' => $customer->customer_id]);
                $request->merge(['project_ids' => $customer -> access_rights -> pluck('customer_project_id') -> toArray()]);
                $request->merge(['team_member_id' => $customer -> id]);
                $request->merge(['is_user_admin' => $customer -> is_admin]);
            } else {
                throw new UserNotFound(__("message.invalid_data", ['static' => __("static.customer")]));
            }
        }
        return $next($request);
    }
}
