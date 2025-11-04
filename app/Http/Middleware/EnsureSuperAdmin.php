<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    /**
     * Allow only super_admin (role_id = 1).
     * This middleware is specifically for Admin Management functionality.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If not logged in, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has super_admin role (role_id = 1)
        $roleId = (int) ($user->role_id ?? 0);

        if ($roleId === 1) {
            return $next($request);
        }

        // Return 403 Forbidden for non-super admin users
        abort(403, 'Access denied. Super Admin privileges required.');
    }
}