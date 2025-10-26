<?php

namespace Tests\Feature\Admin;

use App\Models\SeedClass;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedClassTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use existing admin role or create if not exists
        $adminRole = Role::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'admin',
                'description' => 'Administrator dengan akses terbatas'
            ]
        );
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_admin_can_view_seed_classes_index(): void
    {
        // Use existing BS seed class from TestCase setUp
        $seedClass = SeedClass::where('code', 'BS')->first();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.index'));

        $response->assertStatus(200);
        $response->assertSee($seedClass->name);
    }

    public function test_admin_can_create_seed_class(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.create'));

        $response->assertStatus(200);
        $response->assertSee('Add New Seed Class');
    }

    public function test_admin_can_store_seed_class(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-classes.store'), [
                'name' => 'Test New Seed Class',
                'code' => 'TST',
                
            ]);

        $response->assertRedirect(route('admin.seed-classes.index'));
        $response->assertSessionHas('success', 'Seed class created successfully.');

        $this->assertDatabaseHas('seed_classes', [
            'name' => 'Test New Seed Class',
            'code' => 'TST',
            
        ]);
    }

    public function test_admin_can_view_seed_class(): void
    {
        // Use existing BS seed class from TestCase setUp
        $seedClass = SeedClass::where('code', 'BS')->first();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.show', $seedClass));

        $response->assertStatus(200);
        $response->assertSee($seedClass->name);
    }

    public function test_admin_can_edit_seed_class(): void
    {
        // Use existing BS seed class from TestCase setUp
        $seedClass = SeedClass::where('code', 'BS')->first();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.edit', $seedClass));

        $response->assertStatus(200);
        $response->assertSee('Edit Seed Class');
    }

    public function test_admin_can_update_seed_class(): void
    {
        // Use existing BS seed class from TestCase setUp
        $seedClass = SeedClass::where('code', 'BS')->first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.seed-classes.update', $seedClass), [
                'name' => 'Updated Breeder Seed',
                'code' => 'BS',
                
            ]);

        $response->assertRedirect(route('admin.seed-classes.index'));
        $response->assertSessionHas('success', 'Seed class updated successfully.');

        $this->assertDatabaseHas('seed_classes', [
            'id' => $seedClass->id,
            'name' => 'Updated Breeder Seed',
            
        ]);
    }

    public function test_admin_can_delete_seed_class(): void
    {
        // Create a unique seed class for deletion test
        $seedClass = SeedClass::firstOrCreate(
            ['code' => 'DEL'],
            ['name' => 'Test Delete Seed Class']
        );

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.seed-classes.destroy', $seedClass));

        $response->assertRedirect(route('admin.seed-classes.index'));
        $response->assertSessionHas('success', 'Seed class deleted successfully.');

        $this->assertDatabaseMissing('seed_classes', [
            'id' => $seedClass->id,
        ]);
    }

    public function test_seed_class_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-classes.store'), [
                'name' => '', // Required field empty
                'code' => '', // Required field empty
            ]);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_seed_class_unique_code_validation(): void
    {
        // Use existing BS seed class from TestCase setUp
        $existingSeedClass = SeedClass::where('code', 'BS')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-classes.store'), [
                'name' => 'Another Breeder Seed',
                'code' => 'BS', // Duplicate code
                
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_seed_class_filters_work(): void
    {
        // Use existing seed classes from TestCase setUp
        $bsSeedClass = SeedClass::where('code', 'BS')->first();
        $fsSeedClass = SeedClass::where('code', 'FS')->first();

        // Test search filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.index', ['q' => 'Breeder']));

        $response->assertStatus(200);
        $response->assertSee($bsSeedClass->name);

        // Test code search filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-classes.index', ['q' => 'FS']));

        $response->assertStatus(200);
        $response->assertSee($fsSeedClass->name);
    }

    public function test_guest_cannot_access_seed_class_routes(): void
    {
        // Use existing BS seed class from TestCase setUp
        $seedClass = SeedClass::where('code', 'BS')->first();

        $routes = [
            ['GET', route('admin.seed-classes.index')],
            ['GET', route('admin.seed-classes.create')],
            ['POST', route('admin.seed-classes.store')],
            ['GET', route('admin.seed-classes.show', $seedClass)],
            ['GET', route('admin.seed-classes.edit', $seedClass)],
            ['PUT', route('admin.seed-classes.update', $seedClass)],
            ['DELETE', route('admin.seed-classes.destroy', $seedClass)],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->call($method, $url);
            $response->assertRedirect(route('login'));
        }
    }
}