<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $superAdminRole = Role::create([
            'id' => 1,
            'name' => 'super_admin',
            'description' => 'Super Administrator dengan akses penuh'
        ]);

        $adminRole = Role::create([
            'id' => 2,
            'name' => 'admin',
            'description' => 'Administrator dengan akses terbatas'
        ]);

        // Create users
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role_id' => null // No role assigned
        ]);
    }

    /** @test */
    public function super_admin_can_access_admin_users_index()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-users.index');
        $response->assertViewHas('admins');
    }

    /** @test */
    public function admin_cannot_access_admin_users_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_users_index()
    {
        $response = $this->get(route('admin.admin-users.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function super_admin_can_view_create_admin_user_form()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-users.create');
        $response->assertViewHas('roles');
    }

    /** @test */
    public function super_admin_can_create_new_admin_user()
    {
        $userData = [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 2
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.admin-users.store'), $userData);

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('success', 'Admin user created successfully.');

        $this->assertDatabaseHas('users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'role_id' => 2
        ]);

        // Verify password is hashed
        $user = User::where('email', 'newadmin@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function create_admin_user_validates_required_fields()
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.admin-users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role_id']);
    }

    /** @test */
    public function create_admin_user_validates_email_uniqueness()
    {
        $userData = [
            'name' => 'Test Admin',
            'email' => $this->admin->email, // Use existing email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => 2
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.admin-users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function create_admin_user_validates_password_confirmation()
    {
        $userData = [
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'role_id' => 2
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.admin-users.store'), $userData);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function super_admin_can_view_admin_user_details()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.show', $this->admin));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-users.show');
        $response->assertViewHas('adminUser', $this->admin);
    }

    /** @test */
    public function super_admin_can_view_edit_admin_user_form()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.edit', $this->admin));

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-users.edit');
        $response->assertViewHas('adminUser', $this->admin);
        $response->assertViewHas('roles');
    }

    /** @test */
    public function super_admin_can_update_admin_user()
    {
        $updateData = [
            'name' => 'Updated Admin Name',
            'email' => 'updated@example.com',
            'role_id' => 1
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.admin-users.update', $this->admin), $updateData);

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('success', 'Admin user updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Updated Admin Name',
            'email' => 'updated@example.com',
            'role_id' => 1
        ]);
    }

    /** @test */
    public function super_admin_can_update_admin_user_password()
    {
        $updateData = [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role_id' => $this->admin->role_id
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.admin-users.update', $this->admin), $updateData);

        $response->assertRedirect(route('admin.admin-users.index'));

        // Verify password was updated
        $updatedUser = User::find($this->admin->id);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }

    /** @test */
    public function update_admin_user_without_password_keeps_existing_password()
    {
        $originalPassword = $this->admin->password;
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => $this->admin->email,
            'role_id' => $this->admin->role_id
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.admin-users.update', $this->admin), $updateData);

        $response->assertRedirect(route('admin.admin-users.index'));

        // Verify password was not changed
        $updatedUser = User::find($this->admin->id);
        $this->assertEquals($originalPassword, $updatedUser->password);
    }

    /** @test */
    public function super_admin_can_delete_admin_user()
    {
        $adminToDelete = User::create([
            'name' => 'Admin to Delete',
            'email' => 'delete@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.admin-users.destroy', $adminToDelete));

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('success', 'Admin user deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $adminToDelete->id
        ]);
    }

    /** @test */
    public function super_admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.admin-users.destroy', $this->superAdmin));

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('error', 'Cannot delete the last Super Admin user.');

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id
        ]);
    }

    /** @test */
    public function cannot_delete_last_super_admin()
    {
        // Create another super admin first
        $anotherSuperAdmin = User::create([
            'name' => 'Another Super Admin',
            'email' => 'another@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1
        ]);

        // Now try to delete the original super admin (should show "cannot delete yourself" message)
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.admin-users.destroy', $this->superAdmin));

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id
        ]);

        // Delete the other super admin to make this the last one
        $anotherSuperAdmin->delete();

        // Now try again - should show "cannot delete last super admin" message
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.admin-users.destroy', $this->superAdmin));

        $response->assertRedirect(route('admin.admin-users.index'));
        $response->assertSessionHas('error', 'Cannot delete the last Super Admin user.');

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id
        ]);
    }

    /** @test */
    public function admin_cannot_access_any_admin_user_management_routes()
    {
        $routes = [
            ['GET', route('admin.admin-users.index')],
            ['GET', route('admin.admin-users.create')],
            ['POST', route('admin.admin-users.store')],
            ['GET', route('admin.admin-users.show', $this->admin)],
            ['GET', route('admin.admin-users.edit', $this->admin)],
            ['PUT', route('admin.admin-users.update', $this->admin)],
            ['DELETE', route('admin.admin-users.destroy', $this->admin)],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->actingAs($this->admin)->call($method, $url);
            $this->assertEquals(403, $response->getStatusCode(), "Failed for {$method} {$url}");
        }
    }

    /** @test */
    public function only_admin_users_are_shown_in_index()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.index'));

        $response->assertStatus(200);
        
        // Should see super admin and admin users
        $response->assertSee($this->superAdmin->name);
        $response->assertSee($this->admin->name);
        
        // Should not see regular user (no role or non-admin role)
        $response->assertDontSee($this->regularUser->name);
    }

    /** @test */
    public function cannot_view_non_admin_user_details()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.show', $this->regularUser));

        $response->assertStatus(404);
    }

    /** @test */
    public function cannot_edit_non_admin_user()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.admin-users.edit', $this->regularUser));

        $response->assertStatus(404);
    }

    /** @test */
    public function cannot_update_non_admin_user()
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.admin-users.update', $this->regularUser), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'role_id' => 2
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function cannot_delete_non_admin_user()
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.admin-users.destroy', $this->regularUser));

        $response->assertStatus(404);
    }
}