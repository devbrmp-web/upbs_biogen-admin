<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat role admin
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Administrator'
        ]);
        
        // Buat user admin untuk testing dengan role_id 1 (sesuai controller logic)
        User::create([
            'name' => 'Test Admin',
            'email' => 'test@biogen.local',
            'password' => Hash::make('password'),
            'role_id' => 1  // Hardcode role_id 1 untuk admin
        ]);
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_admin_can_login_with_correct_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@biogen.local',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_admin_cannot_login_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@biogen.local',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
