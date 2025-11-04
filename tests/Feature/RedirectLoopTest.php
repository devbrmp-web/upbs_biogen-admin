<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_redirect_loops_on_admin_routes(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role_id' => 2]);
        
        // Test key admin routes that we know exist
        $routes = [
            '/admin/dashboard',
            '/admin/seed-lots',
            '/admin/varieties'
        ];
        
        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            
            // Should get 200 OK, not redirect
            $this->assertEquals(200, $response->getStatusCode(), 
                "Route {$route} returned {$response->getStatusCode()} instead of 200");
        }
    }

    public function test_guest_redirects_properly(): void
    {
        // Test that guest users are redirected to login, not in loops
        $routes = [
            '/admin/dashboard',
            '/admin/seed-lots'
        ];
        
        foreach ($routes as $route) {
            $response = $this->get($route);
            
            // Should redirect to login
            $response->assertRedirect('/login');
        }
    }

    public function test_authenticated_admin_accessing_login_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create(['role_id' => 2]);
        
        $response = $this->actingAs($admin)->get('/login');
        
        // Should redirect to dashboard, not cause loop
        $response->assertRedirect('/admin/dashboard');
    }
}