<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        // If user is already authenticated, redirect to admin dashboard
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && in_array($user->role_id, [1, 2])) {
                return redirect('/admin/dashboard');
            }
        }
        
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        // Regenerate session to prevent session fixation
        $request->session()->regenerate();

        // Ensure user has admin privileges
        $user = $request->user();
        if (!$user || !in_array($user->role_id, [1, 2])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Access denied. Admin privileges required.',
            ]);
        }

        // Clear any previous intended URL and other potential redirect sources
        $request->session()->forget(['url.intended', 'login.intended']);
        
        // Clear any cached redirect responses
        $request->session()->reflash();

        return redirect('/admin/dashboard');
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // Log the user before logout for audit purposes
        $user = Auth::user();
        if ($user) {
            \App\Models\AuditLog::logLogout($user);
        }
        
        // Store session cookie name before logout
        $sessionCookieName = config('session.cookie');
        
        // Logout first to prevent any middleware issues
        Auth::guard('web')->logout();
        
        // Clear all session data
        $request->session()->flush();

        // Invalidate the session completely
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();
        
        // Create response with proper headers to prevent caching
        $response = redirect('/login')->with('status', 'You have been logged out successfully.');
        
        // Clear all relevant cookies more comprehensively
        $response->withCookie(cookie()->forget($sessionCookieName));
        $response->withCookie(cookie()->forget($sessionCookieName . '_remember'));
        $response->withCookie(cookie()->forget('remember_web_' . sha1(static::class)));
        $response->withCookie(cookie()->forget('laravel_session'));
        $response->withCookie(cookie()->forget('XSRF-TOKEN'));
        
        // Clear any potential custom cookies
        if (config('session.driver') === 'cookie') {
            $response->withCookie(cookie()->forget('laravel_cookie_consent'));
        }
        
        // Add comprehensive headers to prevent caching and ensure fresh login
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        $response->header('Clear-Site-Data', '"cache", "cookies", "storage"');
        
        return $response;
    }
}
