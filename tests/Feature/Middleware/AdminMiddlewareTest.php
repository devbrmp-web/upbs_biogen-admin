<?php

namespace Tests\Feature\Middleware;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Gunakan artisan db:seed untuk memastikan roles sudah ada
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder']);
    }

    public function test_non_admin_gets_403(): void
    {
        // Create user with viewer role
        $user = User::factory()->create(['role_id' => 3]);
        
        $this->actingAs($user);
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        // Create user with admin role (ID 2 sesuai RoleSeeder)
        $user = User::factory()->create(['role_id' => 2]);
        
        $this->actingAs($user);
        $this->get('/admin/dashboard')->assertStatus(200);
    }
    
    public function test_super_admin_can_access_dashboard(): void
    {
        // Create user with super_admin role (ID 1 sesuai RoleSeeder)
        $user = User::factory()->create(['role_id' => 1]);
        
        $this->actingAs($user);
        $this->get('/admin/dashboard')->assertStatus(200);
    }
    
    public function test_guest_redirected_to_login(): void
    {
        // Guest user should be redirected to login
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }
}