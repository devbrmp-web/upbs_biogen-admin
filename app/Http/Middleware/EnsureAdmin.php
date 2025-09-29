<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    /**
     * Allow only super_admin (1) and admin (2).
     * - Guest: biarkan 'auth' yang handle (atau fallback redirect).
     * - User tanpa role_id: 403 (forbidden).
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Jika belum login, biarkan middleware 'auth' menangani redirect.
        // (Fallback aman bila urutan middleware berubah)
        if (!$user) {
            return redirect()->route('login');
        }

        // Cek role_id langsung (lebih robust daripada $user->role->name)
        $roleId = (int) ($user->role_id ?? 0);

        if (in_array($roleId, [1, 2], true)) {
            return $next($request);
        }

        abort(403);
    }
}
