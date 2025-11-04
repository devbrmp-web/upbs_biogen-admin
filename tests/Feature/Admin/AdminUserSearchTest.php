<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $anotherAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles first
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create test users
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'role_id' => 1 // super_admin
        ]);
        
        $this->admin = User::factory()->create([
            'name' => 'Regular Admin User',
            'email' => 'admin@example.com',
            'role_id' => 2 // admin
        ]);
        
        $this->anotherAdmin = User::factory()->create([
            'name' => 'Another Admin User',
            'email' => 'another@example.com',
            'role_id' => 2 // admin
        ]);
    }

    /** @test */
    public function can_search_admin_users_by_name_using_search_parameter()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'Regular']));

        $response->assertStatus(200);
        $response->assertSee('Regular Admin User');
        $response->assertDontSee('Another Admin User');
    }

    /** @test */
    public function can_search_admin_users_by_email_using_search_parameter()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'another@example.com']));

        $response->assertStatus(200);
        $response->assertSee('Another Admin User');
        $response->assertDontSee('Regular Admin User');
    }

    /** @test */
    public function can_search_admin_users_by_name_using_q_parameter()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['q' => 'Super']));

        $response->assertStatus(200);
        $response->assertSee('Super Admin User');
        $response->assertDontSee('Regular Admin User');
    }

    /** @test */
    public function search_is_case_insensitive()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'REGULAR']));

        $response->assertStatus(200);
        $response->assertSee('Regular Admin User');
    }

    /** @test */
    public function empty_search_returns_all_admin_users()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => '']));

        $response->assertStatus(200);
        $response->assertSee('Super Admin User');
        $response->assertSee('Regular Admin User');
        $response->assertSee('Another Admin User');
    }

    /** @test */
    public function search_query_is_preserved_in_pagination()
    {
        // Create more admin users to trigger pagination
        User::factory()->count(20)->create(['role_id' => 2]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'Regular', 'page' => 1]));

        $response->assertStatus(200);
        // Check that the search input has the correct value
        $response->assertSee('value="Regular"', false);
    }

    /** @test */
    public function search_input_value_is_preserved_in_view()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'test query']));

        $response->assertStatus(200);
        $response->assertSee('value="test query"', false);
    }

    /** @test */
    public function search_only_returns_admin_users()
    {
        // Create a regular user (non-admin)
        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'role_id' => null
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index', ['search' => 'Regular']));

        $response->assertStatus(200);
        $response->assertSee('Regular Admin User');
        $response->assertDontSee('Regular User'); // Should not see non-admin user
    }
}