<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VarietyStockStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Commodity $commodity;
    protected SeedClass $bs;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles so we can act as admin (role_id = 2)
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
            'slug' => 'test-commodity',
        ]);

        $this->bs = SeedClass::firstOrCreate(
            ['code' => 'BS'],
            ['name' => 'BS', 'description' => 'Breeder Seed']
        );
    }

    public function test_restock_filter_returns_only_varieties_with_stock_between_0_and_min_limit(): void
    {
        $this->actingAs(User::factory()->create(['role_id' => 2]));

        // Out of Stock: no sellable > 0
        $out = Variety::create([
            'name' => 'Out Variety',
            'sku' => 'OUT-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 30,
        ]);
        // Zero quantity (sellable) should be counted as out of stock for queries requiring > 0
        SeedLot::create([
            'variety_id' => $out->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'OUT-BS-001',
            'production_year' => 2024,
            'quantity' => 0,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Restock: > 0 and <= min limit
        $restock = Variety::create([
            'name' => 'Restock Variety',
            'sku' => 'REST-001',
            'commodity_id' => $this->commodity->id,
            'price' => 60000,
            'minimum_limit' => 50,
        ]);
        SeedLot::create([
            'variety_id' => $restock->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'REST-BS-001',
            'production_year' => 2024,
            'quantity' => 30, // <= minimum_limit
            'unit' => 'kg',
            'price_per_unit' => 60000,
            'is_sellable' => true,
        ]);

        // Available: > minimum_limit
        $avail = Variety::create([
            'name' => 'Available Variety',
            'sku' => 'AVAIL-001',
            'commodity_id' => $this->commodity->id,
            'price' => 70000,
            'minimum_limit' => 30,
        ]);
        SeedLot::create([
            'variety_id' => $avail->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'AVAIL-BS-001',
            'production_year' => 2024,
            'quantity' => 50, // > minimum_limit
            'unit' => 'kg',
            'price_per_unit' => 70000,
            'is_sellable' => true,
        ]);

        // Call index with restock filter
        $response = $this->get(route('admin.varieties.index', ['stock_status' => 'restock']));
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('Restock Variety', $html);
        $this->assertStringNotContainsString('Out Variety', $html);
        $this->assertStringNotContainsString('Available Variety', $html);
    }

    public function test_out_of_stock_filter_returns_only_varieties_without_sellable_stock(): void
    {
        $this->actingAs(User::factory()->create(['role_id' => 2]));

        // Out of Stock
        $out = Variety::create([
            'name' => 'Out Variety',
            'sku' => 'OUT-002',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 30,
        ]);
        // Non-sellable lot shouldn't count towards stock
        SeedLot::create([
            'variety_id' => $out->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'OUT-BS-002',
            'production_year' => 2024,
            'quantity' => 40,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => false,
        ]);

        // Restock
        $restock = Variety::create([
            'name' => 'Restock Variety',
            'sku' => 'REST-002',
            'commodity_id' => $this->commodity->id,
            'price' => 60000,
            'minimum_limit' => 50,
        ]);
        SeedLot::create([
            'variety_id' => $restock->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'REST-BS-002',
            'production_year' => 2024,
            'quantity' => 20,
            'unit' => 'kg',
            'price_per_unit' => 60000,
            'is_sellable' => true,
        ]);

        $response = $this->get(route('admin.varieties.index', ['stock_status' => 'out_of_stock']));
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('Out Variety', $html);
        $this->assertStringNotContainsString('Restock Variety', $html);
    }

    public function test_available_filter_returns_only_varieties_above_minimum_limit(): void
    {
        $this->actingAs(User::factory()->create(['role_id' => 2]));

        // Available
        $avail = Variety::create([
            'name' => 'Available Variety',
            'sku' => 'AVAIL-003',
            'commodity_id' => $this->commodity->id,
            'price' => 70000,
            'minimum_limit' => 30,
        ]);
        SeedLot::create([
            'variety_id' => $avail->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'AVAIL-BS-003',
            'production_year' => 2024,
            'quantity' => 45, // > min limit
            'unit' => 'kg',
            'price_per_unit' => 70000,
            'is_sellable' => true,
        ]);

        // Restock
        $restock = Variety::create([
            'name' => 'Restock Variety',
            'sku' => 'REST-003',
            'commodity_id' => $this->commodity->id,
            'price' => 60000,
            'minimum_limit' => 50,
        ]);
        SeedLot::create([
            'variety_id' => $restock->id,
            'seed_class_id' => $this->bs->id,
            'lot_code' => 'REST-BS-003',
            'production_year' => 2024,
            'quantity' => 10, // <= min limit
            'unit' => 'kg',
            'price_per_unit' => 60000,
            'is_sellable' => true,
        ]);

        $response = $this->get(route('admin.varieties.index', ['stock_status' => 'available']));
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('Available Variety', $html);
        $this->assertStringNotContainsString('Restock Variety', $html);
    }
}
