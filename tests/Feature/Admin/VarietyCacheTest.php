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

class VarietyCacheTest extends TestCase
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
            ['name' => 'BS', 'description' => 'Benih Sebar']
        );
        $this->fsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'FS'],
            ['name' => 'FS', 'description' => 'Benih Sumber']
        );
    }

    #[Test]
    public function variety_total_stock_is_cached()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // Create seed lots
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS001',
            'production_year' => 2024,
            'quantity' => 30.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should cache the result
        $totalStock1 = $variety->total_stock;
        $this->assertEquals(80.0, $totalStock1);

        // Verify cache exists
        $cacheKey = "variety_total_stock_{$variety->id}";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals(80.0, Cache::get($cacheKey));

        // Second call should use cached result
        $totalStock2 = $variety->total_stock;
        $this->assertEquals(80.0, $totalStock2);
    }

    #[Test]
    public function variety_stock_status_is_cached()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-002',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // Create seed lot with stock above minimum
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS001',
            'production_year' => 2024,
            'quantity' => 30.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        // First call should cache the result
        $stockStatus1 = $variety->stock_status;
        $this->assertEquals('Tersedia', $stockStatus1);

        // Verify cache exists
        $cacheKey = "variety_stock_status_{$variety->id}";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals('Tersedia', Cache::get($cacheKey));

        // Second call should use cached result
        $stockStatus2 = $variety->stock_status;
        $this->assertEquals('Tersedia', $stockStatus2);
    }

    #[Test]
    public function cache_is_cleared_when_variety_is_updated()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-003',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Cache the values
        $variety->total_stock;
        $variety->stock_status;

        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";

        // Verify cache exists
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));

        // Update variety
        $variety->update(['minimum_limit' => 20.0]);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($totalStockCacheKey));
        $this->assertFalse(Cache::has($stockStatusCacheKey));
    }

    #[Test]
    public function cache_is_cleared_when_seed_lot_is_created()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-004',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        // Cache the values (should be 0 initially)
        $variety->total_stock;
        $variety->stock_status;

        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";

        // Verify cache exists
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));

        // Create new seed lot
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($totalStockCacheKey));
        $this->assertFalse(Cache::has($stockStatusCacheKey));
    }

    #[Test]
    public function cache_is_cleared_when_seed_lot_is_updated()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-005',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        $seedLot = SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Cache the values
        $variety->total_stock;
        $variety->stock_status;

        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";

        // Verify cache exists
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));

        // Update seed lot
        $seedLot->update(['quantity' => 100.0]);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($totalStockCacheKey));
        $this->assertFalse(Cache::has($stockStatusCacheKey));
    }

    #[Test]
    public function cache_is_cleared_when_seed_lot_is_deleted()
    {
        $variety = Variety::create([
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-006',
            'commodity_id' => $this->commodity->id,
            'price' => 50000.00,
            'minimum_limit' => 10.0,
        ]);

        $seedLot = SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS001',
            'production_year' => 2024,
            'quantity' => 50.0,
            'unit' => 'kg',
            'price_per_unit' => 50000.00,
            'is_sellable' => true,
        ]);

        // Cache the values
        $variety->total_stock;
        $variety->stock_status;

        $totalStockCacheKey = "variety_total_stock_{$variety->id}";
        $stockStatusCacheKey = "variety_stock_status_{$variety->id}";

        // Verify cache exists
        $this->assertTrue(Cache::has($totalStockCacheKey));
        $this->assertTrue(Cache::has($stockStatusCacheKey));

        // Delete seed lot
        $seedLot->delete();

        // Verify cache is cleared
        $this->assertFalse(Cache::has($totalStockCacheKey));
        $this->assertFalse(Cache::has($stockStatusCacheKey));
    }
}