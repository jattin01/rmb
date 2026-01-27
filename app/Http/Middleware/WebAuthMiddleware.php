<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (auth()->check()) {
                $user = auth()->user();
                if (!session()->has('auth_access_token')) {
                    $user->tokens()->where(['tokenable_id' => $user->id, 'name' => "WEB_TOKEN"])->delete();
                    $accessToken = $user->createToken("WEB_TOKEN");
                    Session::put('auth_access_token', $accessToken);
                    return $next($request);
                }
                return $next($request);
            } else {
                return redirect()->route('auth.login');
            }
        } catch (\Exception $e) {
            return redirect()->route('auth.login');
        }
    }
}
