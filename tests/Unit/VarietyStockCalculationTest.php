<?php

namespace Tests\Unit;

use App\Models\Commodity;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VarietyStockCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected Commodity $commodity;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test commodity
        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
            'slug' => 'test-commodity',
        ]);

        // Create seed classes
        $this->bsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'BS'],
            ['name' => 'BS', 'description' => 'Benih Sebar']
        );
        $this->fsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'FS'],
            ['name' => 'FS', 'description' => 'Benih Sumber']
        );
    }

    #[Test]
    public function total_stock_calculation_sums_only_sellable_seed_lots()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // Create sellable seed lots
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-001',
            'production_year' => 2024,
            'quantity' => 30.0,
            'unit' => 'kg',
            'price_per_unit' => 60000.00,
            'is_sellable' => true,
        ]);

        // Create non-sellable seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-002',
            'production_year' => 2024,
            'quantity' => 20.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => false, // Not sellable
        ]);

        // Total should only include sellable lots: 50 + 30 = 80
        $this->assertEquals(80.0, $variety->total_stock);
    }

    #[Test]
    public function total_stock_calculation_handles_empty_seed_lots()
    {
        $variety = Variety::create([
            'name' => 'Empty Variety',
            'sku' => 'EMPTY-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // No seed lots created
        $this->assertEquals(0.0, $variety->total_stock);
    }

    #[Test]
    public function total_stock_calculation_handles_zero_quantity_lots()
    {
        $variety = Variety::create([
            'name' => 'Zero Quantity Variety',
            'sku' => 'ZERO-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // Create seed lot with zero quantity
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-ZERO-001',
            'production_year' => 2024,
            'quantity' => 0.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        $this->assertEquals(0.0, $variety->total_stock);
    }

    #[Test]
    public function stock_status_returns_habis_when_no_sellable_stock()
    {
        $variety = Variety::create([
            'name' => 'No Stock Variety',
            'sku' => 'NO-STOCK-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // No seed lots
        $this->assertEquals('Habis', $variety->stock_status);

        // Only non-sellable seed lots
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-NON-SELL-001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => false,
        ]);

        // Refresh the variety to clear any cached values
        $variety = $variety->fresh();
        $this->assertEquals('Habis', $variety->stock_status);
    }

    #[Test]
    public function stock_status_returns_restock_when_below_minimum()
    {
        $variety = Variety::create([
            'name' => 'Limited Stock Variety',
            'sku' => 'LIMITED-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 50.0, // High minimum
        ]);

        // Create seed lot with quantity below minimum
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-LIMITED-001',
            'production_year' => 2024,
            'quantity' => 30.0, // Below minimum of 50
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        $this->assertEquals('Restock', $variety->stock_status);
    }

    #[Test]
    public function stock_status_returns_restock_when_exactly_at_minimum()
    {
        $variety = Variety::create([
            'name' => 'Exact Minimum Variety',
            'sku' => 'EXACT-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 50.0,
        ]);

        // Create seed lot with quantity exactly at minimum
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-EXACT-001',
            'production_year' => 2024,
            'quantity' => 50.0, // Exactly at minimum
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        $this->assertEquals('Restock', $variety->stock_status);
    }

    #[Test]
    public function stock_status_returns_tersedia_when_above_minimum()
    {
        $variety = Variety::create([
            'name' => 'Available Stock Variety',
            'sku' => 'AVAILABLE-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 30.0,
        ]);

        // Create seed lot with quantity above minimum
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-AVAILABLE-001',
            'production_year' => 2024,
            'quantity' => 50.0, // Above minimum of 30
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        $this->assertEquals('Tersedia', $variety->stock_status);
    }

    #[Test]
    public function stock_calculations_handle_decimal_quantities()
    {
        $variety = Variety::create([
            'name' => 'Decimal Variety',
            'sku' => 'DEC-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 25, // integer-only policy
        ]);

        // Create BS seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-DEC-001',
            'production_year' => 2024,
            'quantity' => 16, // rounded from 15.75 to integer
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Create FS seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-DEC-001',
            'production_year' => 2024,
            'quantity' => 12, // rounded from 12.25 to integer
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Total: 16 + 12 = 28
        // 28 > 25 (minimum), so should be 'Tersedia'
        $this->assertEquals(28, $variety->total_stock);
        $this->assertEquals('Tersedia', $variety->stock_status);
    }





    #[Test]
    public function stock_calculations_handle_large_quantities()
    {
        $variety = Variety::create([
            'name' => 'Large Quantity Variety',
            'sku' => 'LARGE-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 1000.0,
        ]);

        // Create seed lot with large quantity
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-LARGE-001',
            'production_year' => 2024,
            'quantity' => 50000.0, // Large quantity
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        $this->assertEquals(50000.0, $variety->total_stock);
        $this->assertEquals('Tersedia', $variety->stock_status);
    }

    #[Test]
    public function stock_calculations_are_consistent_across_multiple_calls()
    {
        $variety = Variety::create([
            'name' => 'Consistent Variety',
            'sku' => 'CONSISTENT-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 20.0,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-CONSISTENT-001',
            'production_year' => 2024,
            'quantity' => 35.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Call multiple times and ensure consistency
        $stock1 = $variety->total_stock;
        $stock2 = $variety->total_stock;
        $stock3 = $variety->total_stock;

        $status1 = $variety->stock_status;
        $status2 = $variety->stock_status;
        $status3 = $variety->stock_status;

        $this->assertEquals($stock1, $stock2);
        $this->assertEquals($stock2, $stock3);
        $this->assertEquals(35.0, $stock1);

        $this->assertEquals($status1, $status2);
        $this->assertEquals($status2, $status3);
        $this->assertEquals('Tersedia', $status1);
    }

    #[Test]
    public function stock_calculations_update_when_seed_lots_change()
    {
        $variety = Variety::create([
            'name' => 'Dynamic Variety',
            'sku' => 'DYNAMIC-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 25.0,
        ]);

        $seedLot = SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-DYNAMIC-001',
            'production_year' => 2024,
            'quantity' => 30.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Initial state
        $this->assertEquals(30.0, $variety->total_stock);
        $this->assertEquals('Tersedia', $variety->stock_status);

        // Update quantity
        $seedLot->update(['quantity' => 20.0]);
        
        // Clear cache to ensure fresh calculation
        Cache::flush();
        $variety = $variety->fresh();

        // Updated state
        $this->assertEquals(20.0, $variety->total_stock);
        $this->assertEquals('Restock', $variety->stock_status); // 20 <= 25 (minimum)

        // Make non-sellable
        $seedLot->update(['is_sellable' => false]);
        
        // Clear cache to ensure fresh calculation
        Cache::flush();
        $variety = $variety->fresh();

        // Final state
        $this->assertEquals(0.0, $variety->total_stock);
        $this->assertEquals('Habis', $variety->stock_status);
    }

    #[Test]
    public function total_stock_ignores_non_kg_units()
    {
        $variety = Variety::create([
            'name' => 'Unit Filter Variety',
            'sku' => 'UNIT-FILTER-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 5.0,
        ]);

        // Ensure PL class exists
        $plSeedClass = SeedClass::firstOrCreate(
            ['code' => 'PL'],
            ['name' => 'Planlet']
        );

        // KG lots (should be counted)
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-KG-001',
            'production_year' => 2024,
            'quantity' => 10.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-KG-001',
            'production_year' => 2024,
            'quantity' => 5.0,
            'unit' => 'kg',
            'price_per_unit' => 60000.00,
            'is_sellable' => true,
        ]);

        // Non-kg weight units (should be ignored)
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-GRAM-001',
            'production_year' => 2024,
            'quantity' => 3000.0, // grams
            'unit' => 'gram',
            'price_per_unit' => 100.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-TON-001',
            'production_year' => 2024,
            'quantity' => 0.4, // tons
            'unit' => 'ton',
            'price_per_unit' => 1000000.00,
            'is_sellable' => true,
        ]);

        // Non-weight units for Planlet (should be ignored)
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $plSeedClass->id,
            'lot_code' => 'PL-BOTTLE-001',
            'production_year' => 2024,
            'quantity' => 10.0,
            'unit' => 'bottle',
            'price_per_unit' => 75000.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $plSeedClass->id,
            'lot_code' => 'PL-PIECE-001',
            'production_year' => 2024,
            'quantity' => 25.0,
            'unit' => 'piece',
            'price_per_unit' => 15000.00,
            'is_sellable' => true,
        ]);

        // Only KG units should be counted: 10 (BS) + 5 (FS) = 15
        $this->assertEquals(15.0, $variety->total_stock);

        // Also verify subquery calculations
        $varWithCalc = Variety::withStockCalculations()->find($variety->id);
        $this->assertEquals(15.0, (float) $varWithCalc->total_stock_calculated);
        $this->assertEquals(10.0, (float) $varWithCalc->bs_stock_calculated);
        $this->assertEquals(5.0, (float) $varWithCalc->fs_stock_calculated);
    }
}