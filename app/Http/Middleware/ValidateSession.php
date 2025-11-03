<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Skip validation for authentication routes to prevent loops
            if ($this->shouldSkipValidation($request)) {
                return $next($request);
            }
            
            // Check for potential redirect loops first
            if ($this->isRedirectLoop($request)) {
                Log::warning('Potential redirect loop detected', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'referer' => $request->header('referer')
                ]);
                
                return $this->handleRedirectLoop($request);
            }
            
            // Check if session is corrupted or invalid
            if ($this->isSessionCorrupted($request)) {
                Log::warning('Corrupted session detected, clearing session', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl()
                ]);
                
                return $this->handleCorruptedSession($request);
            }
            
        } catch (\Exception $e) {
            Log::error('Session validation error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            return $this->handleSessionError($request);
        }

        return $next($request);
    }
    
    /**
     * Check if validation should be skipped for this request
     */
    private function shouldSkipValidation(Request $request): bool
    {
        $skipRoutes = [
            'login', 'logout', 'register', 'password.request', 
            'password.email', 'password.reset', 'password.update'
        ];
        
        $skipPaths = [
            'login', 'logout', 'register', 'forgot-password', 
            'reset-password', 'verify-email'
        ];
        
        // Check route names
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $skipRoutes)) {
            return true;
        }
        
        // Check paths
        foreach ($skipPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle redirect loop scenario
     */
    private function handleRedirectLoop(Request $request): Response
    {
        $this->clearCorruptedSession($request);
        
        $response = redirect('/login')->with('error', 'Session error detected. Please log in again.');
        return $this->addNoCacheHeaders($response);
    }
    
    /**
     * Handle corrupted session scenario
     */
    private function handleCorruptedSession(Request $request): Response
    {
        $this->clearCorruptedSession($request);
        
        // If this is an admin route, redirect to login
        if ($request->is('admin/*')) {
            $response = redirect('/login')->with('warning', 'Your session has expired. Please log in again.');
            return $this->addNoCacheHeaders($response);
        }
        
        // For non-admin routes, continue processing
        $response = redirect('/login')->with('warning', 'Your session has expired. Please log in again.');
        return $this->addNoCacheHeaders($response);
    }
    
    /**
     * Handle general session errors
     */
    private function handleSessionError(Request $request): Response
    {
        $this->clearCorruptedSession($request);
        
        $response = redirect('/login')->with('error', 'A session error occurred. Please log in again.');
        return $this->addNoCacheHeaders($response);
    }
    
    /**
     * Add no-cache headers to response
     */
    private function addNoCacheHeaders(Response $response): Response
    {
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        
        return $response;
    }
    
    /**
     * Check if session is corrupted
     */
    private function isSessionCorrupted(Request $request): bool
    {
        // Skip corruption check during logout process
        if ($request->is('logout') || $request->route()?->getName() === 'logout') {
            return false;
        }
        
        // Check if user is authenticated but session data is missing
        if (Auth::check()) {
            $user = Auth::user();
            
            // If user exists but is null or invalid
            if (!$user || !$user->id) {
                return true;
            }
            
            // Only check for CSRF token on non-GET requests
            if (!$request->isMethod('GET') && !$request->session()->has('_token')) {
                return true;
            }
            
            // Check if user ID in session matches authenticated user (more lenient)
            $sessionKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard');
            $sessionUserId = $request->session()->get($sessionKey);
            
            // Only consider it corrupted if session user ID exists but doesn't match
            if ($sessionUserId && (string)$sessionUserId !== (string)$user->id) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for redirect loops
     */
    private function isRedirectLoop(Request $request): bool
    {
        $referer = $request->header('referer');
        $currentUrl = $request->fullUrl();
        
        // Skip redirect loop detection for logout requests
        if ($request->is('logout') || $request->route()?->getName() === 'logout') {
            return false;
        }
        
        // Skip redirect loop detection for login page
        if ($request->is('login') || $request->route()?->getName() === 'login') {
            return false;
        }
        
        // Only check for exact URL matches to avoid false positives
        if ($referer && $referer === $currentUrl) {
            // Count consecutive redirects to same URL in session
            $redirectCount = $request->session()->get('redirect_count', 0);
            if ($redirectCount > 3) {
                return true;
            }
            $request->session()->put('redirect_count', $redirectCount + 1);
        } else {
            // Reset redirect count if URL changed
            $request->session()->forget('redirect_count');
        }
        
        return false;
    }
    
    /**
     * Clear corrupted session completely
     */
    private function clearCorruptedSession(Request $request): void
    {
        Log::warning('Clearing corrupted session', [
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);
        
        try {
            // Logout user if authenticated
            if (Auth::check()) {
                Auth::logout();
            }
            
            // Clear session data
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Clear redirect count to prevent loops
            $request->session()->forget('redirect_count');
            
        } catch (\Exception $e) {
            // If session clearing fails, log it but continue
            Log::error('Failed to clear corrupted session: ' . $e->getMessage());
        }
    }
}
