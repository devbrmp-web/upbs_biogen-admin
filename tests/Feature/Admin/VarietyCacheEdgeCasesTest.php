<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\Role;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VarietyCacheEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Commodity $commodity;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role_id' => $adminRole->id,
        ]);

        // Create test commodity
        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
            'slug' => 'test-commodity',
        ]);

        // Create seed classes
        $this->bsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'BS'],
            ['name' => 'BS', 'description' => 'Breeder Seed']
        );
        $this->fsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'FS'],
            ['name' => 'FS', 'description' => 'Benih Sumber']
        );
    }

    #[Test]
    public function cache_handles_variety_with_no_seed_lots()
    {
        $variety = Variety::create([
            'name' => 'Empty Variety',
            'sku' => 'EMPTY-VAR-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        // Clear any existing cache
        Cache::flush();

        // Test total stock for variety with no seed lots
        $totalStock = $variety->total_stock;
        $this->assertEquals(0, $totalStock);

        // Test stock status for variety with no seed lots
        $stockStatus = $variety->stock_status;
        $this->assertEquals('Out of Stock', $stockStatus);

        // Verify cache is created even for empty results
        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";
        
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));
        $this->assertEquals(0, Cache::get($totalStockCacheKey));
        $this->assertEquals('Out of Stock', Cache::get($stockStatusCacheKey));
    }

    #[Test]
    public function cache_handles_variety_with_only_non_sellable_seed_lots()
    {
        $variety = Variety::create([
            'name' => 'Non-Sellable Variety',
            'sku' => 'NON-SELL-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        // Create non-sellable seed lots
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-NON-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => false, // Not sellable
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-NON-001',
            'production_year' => 2024,
            'quantity' => 30,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => false, // Not sellable
        ]);

        // Clear any existing cache
        Cache::flush();

        // Test total stock (should be 0 for non-sellable lots)
        $totalStock = $variety->total_stock;
        $this->assertEquals(0, $totalStock);

        // Test stock status (should be 'Out of Stock' for non-sellable lots)
        $stockStatus = $variety->stock_status;
        $this->assertEquals('Out of Stock', $stockStatus);
    }

    #[Test]
    public function cache_handles_variety_with_mixed_sellable_and_non_sellable_lots()
    {
        $variety = Variety::create([
            'name' => 'Mixed Variety',
            'sku' => 'MIXED-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        // Create sellable seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-SELL-001',
            'production_year' => 2024,
            'quantity' => 25,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Create non-sellable seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-NON-001',
            'production_year' => 2024,
            'quantity' => 75,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => false,
        ]);

        // Clear any existing cache
        Cache::flush();

        // Test total stock (should only count sellable lots)
        $totalStock = $variety->total_stock;
        $this->assertEquals(25, $totalStock);

        // Test stock status (should be 'Available' as sellable stock > minimum)
        $stockStatus = $variety->stock_status;
        $this->assertEquals('Available', $stockStatus);
    }

    #[Test]
    public function cache_handles_variety_at_exact_minimum_limit()
    {
        $variety = Variety::create([
            'name' => 'Exact Minimum Variety',
            'sku' => 'EXACT-MIN-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 50, // Set minimum to exactly match stock
        ]);

        // Create seed lot with exact minimum quantity
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-EXACT-001',
            'production_year' => 2024,
            'quantity' => 50, // Exactly at minimum
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        // Test stock status (should be 'Restock' when exactly at minimum)
        $stockStatus = $variety->stock_status;
        $this->assertEquals('Restock', $stockStatus);

        // Verify cache is created
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";
        $this->assertTrue(Cache::has($stockStatusCacheKey));
        $this->assertEquals('Restock', Cache::get($stockStatusCacheKey));
    }

    #[Test]
    public function cache_handles_variety_just_below_minimum_limit()
    {
        $variety = Variety::create([
            'name' => 'Below Minimum Variety',
            'sku' => 'BELOW-MIN-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 50,
        ]);

        // Create seed lot with quantity just below minimum
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-BELOW-001',
            'production_year' => 2024,
            'quantity' => 49, // Just below minimum
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        // Test stock status (should be 'Restock' when below minimum)
        $stockStatus = $variety->stock_status;
        $this->assertEquals('Restock', $stockStatus);
    }

    #[Test]
    public function cache_is_cleared_when_seed_lot_sellability_changes()
    {
        $variety = Variety::create([
            'name' => 'Sellability Change Variety',
            'sku' => 'SELL-CHANGE-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        $seedLot = SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-CHANGE-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Cache the values when sellable
        $totalStock1 = $variety->total_stock;
        $stockStatus1 = $variety->stock_status;
        $this->assertEquals(50, $totalStock1);
        $this->assertEquals('Available', $stockStatus1);

        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";

        // Verify cache exists
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));

        // Change sellability to false
        $seedLot->update(['is_sellable' => false]);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($totalStockCacheKey));
        $this->assertFalse(Cache::has($stockStatusCacheKey));

        // Verify new values are correct
        $totalStock2 = $variety->fresh()->total_stock;
        $stockStatus2 = $variety->fresh()->stock_status;
        $this->assertEquals(0, $totalStock2);
        $this->assertEquals('Out of Stock', $stockStatus2);
    }

    #[Test]
    public function cache_handles_concurrent_access_gracefully()
    {
        $variety = Variety::create([
            'name' => 'Concurrent Access Variety',
            'sku' => 'CONCURRENT-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000,
            'minimum_limit' => 10,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-CONCURRENT-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        // Simulate concurrent access by calling multiple times rapidly
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $variety->total_stock;
        }

        // All results should be consistent
        foreach ($results as $result) {
            $this->assertEquals(50, $result);
        }

        // Cache should exist and be consistent
        $cacheKey = "variety_total_stock_{$variety->id}";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals(50, Cache::get($cacheKey));
    }
}
