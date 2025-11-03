<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // Check if user has admin role (role_id 1 or 2)
                if ($user && in_array($user->role_id, [1, 2])) {
                    // Redirect admin users to dashboard when accessing guest-only pages
                    return redirect('/admin/dashboard');
                } else {
                    // Non-admin users should be logged out completely
                    $request->session()->flush();
                    Auth::guard($guard)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    $response = redirect('/login')->with('error', 'Access denied. Admin privileges required.');
                    // Clear session cookie
                    $response->withCookie(cookie()->forget(config('session.cookie')));
                    return $response;
                }
            }
        }

        return $next($request);
    }
}