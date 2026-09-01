<?php

namespace App\Http\Middleware;

use App\Helpers\ConstantHelper;
use App\Models\UserAccessRight;
use App\Models\UserGroupCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth() -> check()) {
            $user = auth() -> user();
            $userGroupCompanies = UserAccessRight::where('user_id', $user -> id) -> where("status", ConstantHelper::ACTIVE) -> get();
            if (count($userGroupCompanies) <= 0) {
                return redirect() -> route('auth.login.view') -> with(ConstantHelper::WARNING, 'No Company assigned');
            } else {
                // $request->merge(['default_group_company_id' => $user -> group_company_id]);
                // $request->merge(['group_company_ids' => $userGroupCompanies]);
            }
        } else {
            return redirect()->route('auth.login.view');
        }
        return $next($request);
    }
}
