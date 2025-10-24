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
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;
    protected SeedClass $plSeedClass;

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

        // Use existing seed classes from base TestCase
        $this->bsSeedClass = SeedClass::where('code', 'BS')->first();
        $this->fsSeedClass = SeedClass::where('code', 'FS')->first();
        $this->plSeedClass = SeedClass::where('code', 'PL')->first();
    }

    public function test_admin_can_view_seed_lots_index(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
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
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 150,
                'unit' => 'kg',
                'price_per_unit' => 55000,
                'is_sellable' => true,
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-2024-002',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'quantity' => 150,
            'price_per_unit' => 55000,
        ]);
    }

    public function test_admin_can_view_seed_lot(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
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
            'seed_class_id' => $this->bsSeedClass->id,
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
            'seed_class_id' => $this->bsSeedClass->id,
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
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 200,
                'unit' => 'kg',
                'price_per_unit' => 60000,
                'is_sellable' => false, // Use correct field name
                
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
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
            'seed_class_id' => $this->bsSeedClass->id,
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
            'seed_class_id' => $this->bsSeedClass->id,
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
                'seed_class_id' => $this->bsSeedClass->id,
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
            'seed_class_id' => $this->bsSeedClass->id,
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $seedLot2 = SeedLot::create([
            'lot_code' => 'FS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
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

    public function test_admin_can_create_fs_seed_lot_with_valid_unit(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'FS-2024-001',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->fsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => 'kg', // Valid unit for FS
                'price_per_unit' => 50000,
                'is_sellable' => true,
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'FS-2024-001',
            'seed_class_id' => $this->fsSeedClass->id,
            'unit' => 'kg',
        ]);
    }

    public function test_admin_cannot_create_fs_seed_lot_with_invalid_unit(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'FS-2024-002',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->fsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => 'seeds', // Invalid unit for FS
                'price_per_unit' => 50000,
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['unit']);
        $this->assertDatabaseMissing('seed_lots', [
            'lot_code' => 'FS-2024-002',
        ]);
    }

    public function test_admin_can_create_pl_seed_lot_with_valid_unit(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'PL-2024-001',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->plSeedClass->id,
                'production_year' => 2024,
                'quantity' => 500,
                'unit' => 'bottle', // Valid unit for PL
                'price_per_unit' => 2000,
                'is_sellable' => true,
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'PL-2024-001',
            'seed_class_id' => $this->plSeedClass->id,
            'unit' => 'bottle',
        ]);
    }

    public function test_admin_cannot_create_pl_seed_lot_with_invalid_unit(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'PL-2024-002',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->plSeedClass->id,
                'production_year' => 2024,
                'quantity' => 500,
                'unit' => 'kg', // Invalid unit for PL
                'price_per_unit' => 2000,
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['unit']);
        $this->assertDatabaseMissing('seed_lots', [
            'lot_code' => 'PL-2024-002',
        ]);
    }

    public function test_admin_cannot_create_seed_lot_with_negative_quantity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2024-NEG',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => 2024,
                'quantity' => -10, // Negative quantity
                'unit' => 'kg',
                'price_per_unit' => 50000,
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseMissing('seed_lots', [
            'lot_code' => 'BS-2024-NEG',
        ]);
    }

    public function test_admin_can_create_seed_lot_with_zero_price(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2024-ZERO',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => 'kg',
                'price_per_unit' => 0, // Zero price is allowed based on validation rules
                'is_sellable' => true,
            ]);

        // Controller redirects to variety show page when variety_id is provided
        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-2024-ZERO',
            'price_per_unit' => 0,
        ]);
    }

    public function test_admin_cannot_create_seed_lot_with_future_production_year(): void
    {
        $futureYear = now()->year + 2;
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2026-FUTURE',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => $futureYear, // Future year
                'quantity' => 100,
                'unit' => 'kg',
                'price_per_unit' => 50000,
                'is_sellable' => true,
            ]);

        $response->assertSessionHasErrors(['production_year']);
        $this->assertDatabaseMissing('seed_lots', [
            'lot_code' => 'BS-2026-FUTURE',
        ]);
    }

    public function test_admin_can_create_seed_lot_with_current_year(): void
    {
        $currentYear = now()->year;
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-' . $currentYear . '-CURRENT',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => $currentYear,
                'quantity' => 100,
                'unit' => 'kg',
                'price_per_unit' => 50000,
                'is_sellable' => true,
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-' . $currentYear . '-CURRENT',
            'production_year' => $currentYear,
        ]);
    }

    public function test_admin_can_create_non_sellable_seed_lot(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.seed-lots.store'), [
                'lot_code' => 'BS-2024-NONSELL',
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->bsSeedClass->id,
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => 'kg',
                'price_per_unit' => 50000,
                'is_sellable' => false, // Non-sellable
            ]);

        $response->assertRedirect(route('admin.varieties.show', $this->variety));
        $response->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-2024-NONSELL',
            'is_sellable' => false,
        ]);
    }

    public function test_guest_cannot_access_seed_lot_routes(): void
    {
        $seedLot = SeedLot::create([
            'lot_code' => 'BS-2024-001',
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
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
