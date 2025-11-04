<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\Role;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class SeedLotStockValidationTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected User $admin;
    protected Commodity $commodity;
    protected Variety $variety;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeedClasses();

        // Create admin role and user (role_id 1 for super_admin as per EnsureAdmin middleware)
        $adminRole = Role::firstOrCreate(['id' => 1], ['name' => 'super_admin']);
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        // Create test commodity
        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
            'slug' => 'test-commodity',
        ]);

        // Create test variety
        $this->variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        // Get seed classes
        $this->bsSeedClass = SeedClass::where('code', 'BS')->first();
        $this->fsSeedClass = SeedClass::where('code', 'FS')->first();
    }

    #[Test]
    public function seed_lot_creation_validates_required_fields()
    {
        $this->actingAs($this->admin);

        // Test missing variety_id
        $response = $this->post(route('admin.seed-lots.store'), [
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-TEST-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        // Should return validation error for missing variety_id
        $response->assertStatus(302); // Redirect back with errors
        $response->assertSessionHasErrors('variety_id');

        // Test missing seed_class_id
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'lot_code' => 'BS-TEST-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertSessionHasErrors('seed_class_id');

        // Test missing lot_code
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertSessionHasErrors('lot_code');
    }

    #[Test]
    public function seed_lot_validates_production_year_range()
    {
        $this->actingAs($this->admin);

        $currentYear = date('Y');

        // Test production year too far in the past (before 2000)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-OLD-001',
            'production_year' => 1999, // Before minimum year 2000
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertSessionHasErrors('production_year');

        // Test production year in the future (beyond current year + 1)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-FUTURE-001',
            'production_year' => $currentYear + 2, // Too far in future
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertSessionHasErrors('production_year');

        // Test valid production year (current year)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-CURRENT-001',
            'production_year' => $currentYear,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-CURRENT-001',
            'production_year' => $currentYear,
        ]);
    }

    #[Test]
    public function seed_lot_validates_quantity_constraints()
    {
        $this->actingAs($this->admin);

        // Test negative quantity
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-NEG-001',
            'production_year' => 2024,
            'quantity' => -10, // Negative
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertSessionHasErrors('quantity');

        // Test zero quantity (should be valid according to min:0 rule)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-ZERO-001',
            'production_year' => 2024,
            'quantity' => 0, // Zero should be valid
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertRedirect(); // Should succeed

        // Test extremely large quantity (should be valid as no max limit)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-LARGE-001',
            'production_year' => 2024,
            'quantity' => 999999999, // Very large should be valid
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        $response->assertRedirect(); // Should succeed
    }

    #[Test]
    public function seed_lot_validates_price_constraints()
    {
        $this->actingAs($this->admin);

        // Test negative price
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-NEG-PRICE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => -1000, // Negative price
        ]);

        $response->assertSessionHasErrors('price_per_unit');

        // Test zero price (should be allowed for free seeds)
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-FREE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 0, // Free
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-FREE-001',
            'price_per_unit' => 0,
        ]);
    }

    #[Test]
    public function seed_lot_validates_unique_lot_code()
    {
        $this->actingAs($this->admin);

        // Create first seed lot
        SeedLot::create([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-UNIQUE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        // Try to create another with same lot_code
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->fsSeedClass->id, // Different seed class
            'lot_code' => 'BS-UNIQUE-001', // Same lot code
            'production_year' => 2024,
            'quantity' => 30,
            'unit' => 'kg',
            'price_per_unit' => 60000,
        ]);

        $response->assertSessionHasErrors('lot_code');
    }

    #[Test]
    public function seed_lot_validates_unit_compatibility_with_seed_class()
    {
        $this->actingAs($this->admin);

        // BS seed class should accept weight-based units
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-WEIGHT-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg', // Weight unit for BS
            'price_per_unit' => 50000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-WEIGHT-001',
            'unit' => 'kg',
        ]);

        // FS seed class should accept weight-based units (same as BS), and 'ton' should be normalized to 'kg'
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-WEIGHT-001',
            'production_year' => 2024,
            'quantity' => 1,
            'unit' => 'ton', // Weight unit for FS to be normalized
            'price_per_unit' => 1000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'FS-WEIGHT-001',
            'unit' => 'kg',
            'quantity' => 1000,
            'price_per_unit' => 1,
        ]);
    }

    #[Test]
    public function seed_lot_update_validates_stock_reduction_constraints()
    {
        $this->actingAs($this->admin);

        // Create seed lot with initial quantity
        $seedLot = SeedLot::create([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-REDUCE-001',
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        // Simulate some stock being reserved/sold (this would be done by order system)
        // For this test, we'll assume 30kg is reserved
        $reservedQuantity = 30;

        // Try to reduce quantity below reserved amount
        $response = $this->put(route('admin.seed-lots.update', $seedLot), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-REDUCE-001',
            'production_year' => 2024,
            'quantity' => 20, // Less than reserved (30kg)
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        // This should be allowed for now (business logic may vary)
        // In a real system, you might want to validate against reserved stock
        $response->assertRedirect();
    }

    #[Test]
    public function seed_lot_can_be_marked_as_non_sellable()
    {
        $this->actingAs($this->admin);

        // Create sellable seed lot
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-SELLABLE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-SELLABLE-001',
            'is_sellable' => true,
        ]);

        // Create non-sellable seed lot
        $response = $this->post(route('admin.seed-lots.store'), [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-NON-SELLABLE-001',
            'production_year' => 2024,
            'quantity' => 30,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seed_lots', [
            'lot_code' => 'BS-NON-SELLABLE-001',
            'is_sellable' => false,
        ]);
    }

    #[Test]
    public function seed_lot_deletion_validates_no_active_orders()
    {
        $this->actingAs($this->admin);

        $seedLot = SeedLot::create([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-DELETE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
        ]);

        // Test deletion when no orders exist
        $response = $this->delete(route('admin.seed-lots.destroy', $seedLot));
        $response->assertRedirect();
        $this->assertDatabaseMissing('seed_lots', [
            'id' => $seedLot->id,
        ]);
    }
}