<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
            
        }
        
        if (!in_array($request->user()->role->name, ['admin', 'super_admin'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki hak akses ke area ini.');
        }

        return $next($request);
    }
}