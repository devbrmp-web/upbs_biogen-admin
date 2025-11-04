<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersIndexOrdersSuperadminFirstTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['id' => 1, 'name' => 'super_admin']);
        Role::create(['id' => 2, 'name' => 'admin']);
        
        // Create admin user for authentication with proper role_id
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'test@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1, // Use superadmin role to pass middleware
        ]);
    }

    public function test_superadmin_appears_first_in_users_index(): void
    {
        // Create regular admin first
        $regularAdmin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regular@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'created_at' => Carbon::now()->subHour(),
        ]);

        // Create superadmin after regular admin (but should still appear first due to role_id ordering)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => Carbon::now(),
        ]);

        // Act as admin and visit users index
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(200);

        // Get the users from the response
        $users = $response->viewData('admins')->items();

        // We expect 3 users: authenticated admin (superadmin), new superadmin, regular admin
        $this->assertCount(3, $users);

        // All superadmins should come first (ordered by created_at ASC)
        $this->assertEquals(1, $users[0]->role_id, 'First user should be superadmin (role_id=1)');
        $this->assertEquals(1, $users[1]->role_id, 'Second user should be superadmin (role_id=1)');
        $this->assertEquals(2, $users[2]->role_id, 'Third user should be regular admin (role_id=2)');
        
        // The authenticated admin (created first in setUp) should be first
        $this->assertEquals($this->adminUser->id, $users[0]->id, 'First should be authenticated admin (oldest superadmin)');
        $this->assertEquals($superAdmin->id, $users[1]->id, 'Second should be new superadmin');
        $this->assertEquals($regularAdmin->id, $users[2]->id, 'Third should be regular admin');
    }

    public function test_multiple_superadmins_ordered_by_created_at()
    {
        // Create multiple superadmins with different creation times
        $superAdmin1 = User::create([
            'name' => 'Super Admin 1',
            'email' => 'super1@admin.local',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        $superAdmin2 = User::create([
            'name' => 'Super Admin 2',
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

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admin-users.index'));

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
}