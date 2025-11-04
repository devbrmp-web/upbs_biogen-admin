<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersSearchRespectsOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['id' => 1, 'name' => 'super_admin']);
        Role::create(['id' => 2, 'name' => 'admin']);

        // Create authenticated admin user (superadmin to pass middleware)
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'test@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);
    }

    public function test_search_respects_superadmin_first_ordering()
    {
        // Create multiple superadmins with different creation times
        $superAdmin1 = User::create([
            'name' => 'Super Admin One',
            'email' => 'super1@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        $superAdmin2 = User::create([
            'name' => 'Super Admin Two',
            'email' => 'super2@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => Carbon::now()->subHour(),
            'updated_at' => Carbon::now()->subHour(),
        ]);

        // Create regular admin
        $regularAdmin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regular@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'created_at' => Carbon::now()->subMinutes(30),
            'updated_at' => Carbon::now()->subMinutes(30),
        ]);

        // Search for "Admin" - should match all users
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admin-users.index', ['search' => 'Admin']));

        $response->assertStatus(200);
        $users = $response->viewData('admins')->items();

        // We expect 4 users: authenticated admin (superadmin), superAdmin1, superAdmin2, regularAdmin
        $this->assertCount(4, $users);

        // Assert ordering: superadmins first (by created_at ASC), then regular admin
        // The authenticated admin (created in setUp) should be first (oldest)
        $this->assertEquals($this->adminUser->id, $users[0]->id, 'First should be authenticated admin (oldest superadmin)');
        $this->assertEquals($superAdmin1->id, $users[1]->id, 'Second should be superAdmin1');
        $this->assertEquals($superAdmin2->id, $users[2]->id, 'Third should be superAdmin2');
        $this->assertEquals($regularAdmin->id, $users[3]->id, 'Fourth should be regular admin');
    }

    public function test_search_with_no_superadmin_match_shows_only_admins(): void
    {
        // Create users where only regular admin matches search
        $regularAdmin = User::create([
            'name' => 'Special Admin',
            'email' => 'special@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 2,
        ]);

        $superAdmin = User::create([
            'name' => 'Super User',
            'email' => 'super@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);

        // Search for "Special" - only regular admin should match
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admin-users.index', ['q' => 'Special']));

        $response->assertStatus(200);
        $users = $response->viewData('admins')->items();

        $this->assertCount(1, $users, 'Should find only 1 user matching "Special"');
        $this->assertEquals($regularAdmin->id, $users[0]->id, 'Should be the regular admin');
        $this->assertEquals(2, $users[0]->role_id, 'Should be regular admin role');
    }

    public function test_ajax_request_maintains_ordering(): void
    {
        // Create test users
        $regularAdmin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regular@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'created_at' => now()->subHour(),
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => now(),
        ]);

        // Make AJAX request
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(200);
        
        // Should return partial view for AJAX
        $this->assertStringContainsString('Super Admin', $response->getContent());
        $this->assertStringContainsString('Regular Admin', $response->getContent());
        
        // Verify ordering in response content (superadmin should appear before regular admin)
        $content = $response->getContent();
        $superAdminPos = strpos($content, 'Super Admin');
        $regularAdminPos = strpos($content, 'Regular Admin');
        
        $this->assertLessThan($regularAdminPos, $superAdminPos, 'Super Admin should appear before Regular Admin in AJAX response');
    }

    public function test_search_pagination_maintains_ordering()
    {
        // Create many users to test pagination
        $superAdmins = [];
        for ($i = 1; $i <= 15; $i++) {
            $superAdmins[] = User::create([
                'name' => "Super Admin $i",
                'email' => "super$i@admin.local",
                'password' => bcrypt('password'),
                'role_id' => 1,
                'created_at' => Carbon::now()->subHours(20 - $i), // Older first
                'updated_at' => Carbon::now()->subHours(20 - $i),
            ]);
        }

        $regularAdmins = [];
        for ($i = 1; $i <= 10; $i++) {
            $regularAdmins[] = User::create([
                'name' => "Regular Admin $i",
                'email' => "regular$i@admin.local",
                'password' => bcrypt('password'),
                'role_id' => 2,
                'created_at' => Carbon::now()->subMinutes(60 - $i), // Older first
                'updated_at' => Carbon::now()->subMinutes(60 - $i),
            ]);
        }

        // Search for "Admin" with pagination (per_page=10)
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admin-users.index', ['search' => 'Admin', 'per_page' => 10]));

        $response->assertStatus(200);
        $users = $response->viewData('admins')->items();

        // First page should have 10 users, all superadmins (including authenticated admin)
        $this->assertCount(10, $users);
        
        // All users on first page should be superadmins
        foreach ($users as $user) {
            $this->assertEquals(1, $user->role_id, 'All users on first page should be superadmins');
        }

        // First user should be the authenticated admin (oldest)
        $this->assertEquals($this->adminUser->id, $users[0]->id, 'First should be authenticated admin (oldest superadmin)');
        
        // Second user should be the first created superadmin
        $this->assertEquals($superAdmins[0]->id, $users[1]->id, 'Second should be first created superadmin');
    }
}