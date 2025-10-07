<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedLotTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Commodity $commodity;
    protected Variety $variety;
    protected SeedClass $seedClass;

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

        // Create test data
        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
        ]);

        $this->variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'is_active' => true,
        ]);

        $this->seedClass = SeedClass::create([
            'name' => 'Breeder Seed (BS)',
            'code' => 'BS',
            
        ]);
    }

    public function test_admin_can_view_seed_lots_index(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.index'));

        $response->assertStatus(200);
        $response->assertSee('BS-2024-001');
    }

    public function test_admin_can_create_seed_lot(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.create'));

        $response->assertStatus(200);
        $response->assertSee('Add New Seed Lot');
    }

    public function test_admin_can_store_seed_lot(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2024-002',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->seedClass->id,
                'production_year' => 2024,
                'quantity' => 150,
                'unit' => 'kg',
                'price_per_unit' => 55000,
                'is_sellable' => true,
                
            ]);

        $response->assertRedirect(route('admin.seed-lots.index'));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-2024-002',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'quantity' => 150,
            'price_per_unit' => 55000,
        ]);
    }

    public function test_admin_can_view_seed_lot(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.show', $seedLot));

        $response->assertStatus(200);
        $response->assertSee('BS-2024-001');
    }

    public function test_admin_can_edit_seed_lot(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.edit', $seedLot));

        $response->assertStatus(200);
        $response->assertSee('Edit Seed Lot');
    }

    public function test_admin_can_update_seed_lot(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.seed-lots.update', $seedLot), [
                'lot_code' => 'BS-2024-001-UPDATED',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->seedClass->id,
                'production_year' => 2024,
                'quantity' => 200,
                'unit' => 'kg',
                'price_per_unit' => 60000,
                'is_sellable' => false, // Use correct field name
                
            ]);

        $response->assertRedirect(route('admin.seed-lots.index'));
        $response->assertSessionHas('success', 'Seed lot updated successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'id' => $seedLot->id,
            'lot_code' => 'BS-2024-001-UPDATED',
            'quantity' => 200,
            'price_per_unit' => 60000,
            'is_sellable' => 0, // Use correct field name and 0 for false
        ]);
    }

    public function test_admin_can_delete_seed_lot(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.seed-lots.destroy', $seedLot));

        $response->assertRedirect(route('admin.seed-lots.index'));
        $response->assertSessionHas('success', 'Seed lot deleted successfully.');

        $this->assertDatabaseMissing('seed_lots', [
            'id' => $seedLot->id,
        ]);
    }

    public function test_seed_lot_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => '', // Required field empty
                'variety_id' => '', // Required field empty
                'seed_class_id' => '', // Required field empty
                'production_year' => 'invalid', // Invalid year
                'quantity' => -10, // Invalid quantity
                'price_per_unit' => -100, // Invalid price
            ]);

        $response->assertSessionHasErrors([
            'lot_code', 
            'variety_id', 
            'seed_class_id', 
            'production_year', 
            'quantity', 
            'price_per_unit'
        ]);
    }

    public function test_seed_lot_unique_lot_code_validation(): void
    {
        SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2024-001', // Duplicate lot code
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->seedClass->id,
                'production_year' => 2024,
                'quantity' => 150,
                'unit' => 'kg',
                'price_per_unit' => 55000,
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['lot_code']);
    }

    public function test_seed_lot_filters_work(): void
    {
        $seedLot1 = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $seedLot2 = SeedLot::create([
            'lot_code' => 'FS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2023,
            'quantity' => 200,
            'unit' => 'kg',
            'price_per_unit' => 45000,
            'is_sellable' => false,
        ]);

        // Test search filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.index', ['q' => 'BS-2024']));

        $response->assertStatus(200);
        $response->assertSee('BS-2024-001');
        $response->assertDontSee('FS-2024-001');

        // Test variety filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.seed-lots.index', ['variety_id' => $this->variety->id]));

        $response->assertStatus(200);
        $response->assertSee('BS-2024-001');
        $response->assertSee('FS-2024-001');
    }

    public function test_guest_cannot_access_seed_lot_routes(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->seedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $routes = [
            ['GET', route('admin.seed-lots.index')],
            ['GET', route('admin.seed-lots.create')],
            ['POST', route('admin.seed-lots.store')],
            ['GET', route('admin.seed-lots.show', $seedLot)],
            ['GET', route('admin.seed-lots.edit', $seedLot)],
            ['PUT', route('admin.seed-lots.update', $seedLot)],
            ['DELETE', route('admin.seed-lots.destroy', $seedLot)],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->call($method, $url);
            $response->assertRedirect(route('login'));
        }
    }
}
