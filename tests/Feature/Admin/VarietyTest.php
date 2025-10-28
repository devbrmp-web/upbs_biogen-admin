<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\Variety;
use App\Models\User;
use App\Models\Role;
use App\Models\SeedClass;
use App\Models\SeedLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VarietyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Commodity $commodity;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;

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

        // Create test commodity
        $this->commodity = Commodity::create([
            'name' => 'Test Commodity',
            'description' => 'Test commodity description',
        ]);

        // Create seed classes
        $this->bsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'BS'],
            ['name' => 'Breeder Seed', 'description' => 'Breeder Seed']
        );
        
        $this->fsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'FS'],
            ['name' => 'Foundation Seed', 'description' => 'Foundation Seed']
        );
    }

    public function test_admin_can_view_varieties_index(): void
    {
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Variety');
    }

    public function test_admin_can_create_variety(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.create'));

        $response->assertStatus(200);
        $response->assertSee('New Variety');
    }

    public function test_admin_can_store_variety(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('variety.jpg');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'New Variety',
                'sku' => 'NEW-VAR-001',
                'description' => 'New variety description',
                'price' => 15000,
                'minimum_limit' => 20,
                'image' => $image,
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success', 'Variety created successfully.');

        $this->assertDatabaseHas('varieties', [
            'commodity_id' => $this->commodity->id,
            'name' => 'New Variety',
            'sku' => 'NEW-VAR-001',
            'price' => 15000,
            'minimum_limit' => 20,
        ]);

        $variety = Variety::where('name', 'New Variety')->first();
        $this->assertNotNull($variety->image_path);
        Storage::disk('public')->assertExists($variety->image_path);
    }

    public function test_admin_can_view_variety(): void
    {
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-002',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.show', $variety));

        $response->assertStatus(200);
        $response->assertSee('Test Variety');
    }

    public function test_admin_can_edit_variety(): void
    {
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-003',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.edit', $variety));

        $response->assertStatus(200);
        $response->assertSee('Edit Variety');
    }

    public function test_admin_can_update_variety(): void
    {
        Storage::fake('public');

        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-004',
            'description' => 'Test variety description',
            'price' => 10000,
            'minimum_limit' => 10,
        ]);

        $newImage = UploadedFile::fake()->image('new-variety.jpg');

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.varieties.update', $variety), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Updated Variety',
                'sku' => 'UPD-VAR-001',
                'description' => 'Updated variety description',
                'price' => 20000,
                'minimum_limit' => 15,
                'image' => $newImage,
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success', 'Variety updated successfully.');

        $this->assertDatabaseHas('varieties', [
            'id' => $variety->id,
            'name' => 'Updated Variety',
            'sku' => 'UPD-VAR-001',
            'price' => 20000,
            'minimum_limit' => 15,
        ]);

        $variety->refresh();
        $this->assertNotNull($variety->image_path);
        Storage::disk('public')->assertExists($variety->image_path);
    }

    public function test_admin_can_delete_variety(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('variety.jpg');
        $imagePath = $image->store('varieties', 'public');

        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-005',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
            'image_path' => $imagePath,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.varieties.destroy', $variety));

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success', 'Variety deleted successfully.');

        $this->assertDatabaseMissing('varieties', [
            'id' => $variety->id,
        ]);

        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_variety_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => '', // Required field empty
                'name' => '', // Required field empty
                'sku' => '', // Optional field (auto-generated when empty)
                'description' => '', // Required field empty
                'price' => -100, // Invalid price
                'minimum_limit' => -1, // Invalid minimum limit
            ]);

        $response->assertStatus(302); // Should redirect back with errors
        $response->assertSessionHasErrors(['commodity_id', 'name', 'description', 'price', 'minimum_limit']);
    }

    public function test_variety_filters_work(): void
    {
        $variety1 = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Apple Variety',
            'sku' => 'APPLE-VAR-001',
            'description' => 'Apple variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $variety2 = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Orange Variety',
            'sku' => 'ORANGE-VAR-001',
            'description' => 'Orange variety description',
            'price' => 15000,
            'stock_bs_kg' => 80,
            'stock_fs_kg' => 40,
            'minimum_limit' => 8,
        ]);

        // Test search filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['q' => 'Apple']));

        $response->assertStatus(200);
        $response->assertSee('Apple Variety');
        $response->assertDontSee('Orange Variety');

        // Test commodity filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['commodity_id' => $this->commodity->id]));

        $response->assertStatus(200);
        $response->assertSee('Apple Variety');
        $response->assertSee('Orange Variety');
    }

    public function test_guest_cannot_access_variety_routes(): void
    {
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety description',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $routes = [
            ['GET', route('admin.varieties.index')],
            ['GET', route('admin.varieties.create')],
            ['POST', route('admin.varieties.store')],
            ['GET', route('admin.varieties.show', $variety)],
            ['GET', route('admin.varieties.edit', $variety)],
            ['PUT', route('admin.varieties.update', $variety)],
            ['DELETE', route('admin.varieties.destroy', $variety)],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->call($method, $url);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_variety_stock_status_attribute(): void
    {
        // Test 'habis' status (out of stock)
        $outOfStockVariety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Out of Stock Variety',
            'sku' => 'OUT-STOCK-001',
            'description' => 'Out of stock variety',
            'price' => 10000,
            'stock_bs_kg' => 0,
            'stock_fs_kg' => 0,
            'minimum_limit' => 10,
        ]);

        // No seed lots created, so total_stock should be 0
        $this->assertEquals('Out of Stock', $outOfStockVariety->stock_status);

        // Test 'restock' status (low stock)
        $lowStockVariety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Low Stock Variety',
            'sku' => 'LOW-STOCK-001',
            'description' => 'Low stock variety',
            'price' => 10000,
            'stock_bs_kg' => 5,
            'stock_fs_kg' => 3,
            'minimum_limit' => 10,
        ]);

        // Create seed lots with total stock = 8 (below minimum_limit of 10)
        SeedLot::create([
            'variety_id' => $lowStockVariety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-LOW-001',
            'production_year' => 2024,
            'quantity' => 5,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $lowStockVariety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-LOW-001',
            'production_year' => 2024,
            'quantity' => 3,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        $this->assertEquals('Restock', $lowStockVariety->stock_status);

        // Test 'tersedia' status (available)
        $availableVariety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Available Variety',
            'sku' => 'AVAILABLE-001',
            'description' => 'Available variety',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        // Create seed lots with total stock = 150 (above minimum_limit of 10)
        SeedLot::create([
            'variety_id' => $availableVariety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-AVAIL-001',
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $availableVariety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-AVAIL-001',
            'production_year' => 2024,
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        $this->assertEquals('Available', $availableVariety->stock_status);
    }

    public function test_variety_total_stock_attribute(): void
    {
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Total Stock',
            'sku' => 'TOTAL-STOCK-001',
            'description' => 'Test total stock calculation',
            'price' => 10000,
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 75,
            'minimum_limit' => 10,
        ]);

        // Create seed lots that match the expected total
        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'BS-TOTAL-001',
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        SeedLot::create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'lot_code' => 'FS-TOTAL-001',
            'production_year' => 2024,
            'quantity' => 75,
            'unit' => 'kg',
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);

        $this->assertEquals(175, $variety->total_stock);
    }
}
