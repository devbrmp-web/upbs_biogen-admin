<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\SeedLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class VarietyManagementTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected User $admin;
    protected Commodity $commodity;
    protected SeedClass $seedClass;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;
    protected Variety $variety;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with proper role
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAs($this->admin);
        
        // Create seed classes
        $this->createSeedClasses();
        
        $this->commodity = Commodity::factory()->create();
        // Initialize seed classes
        $this->seedClass = SeedClass::where('code', 'BS')->first();
        $this->bsSeedClass = SeedClass::where('code', 'BS')->first();
        $this->fsSeedClass = SeedClass::where('code', 'FS')->first();
        $this->variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);
    }

    #[Test]
    public function admin_can_view_variety_show_page_with_seed_lots()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TV-001',
            'price' => 50000,
            'minimum_limit' => 10,
            'status' => 'available',
        ]);

        $seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'LOT-2024-001',
            'quantity' => 25,
            'unit' => 'kg',
            'price_per_unit' => 2000,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.varieties.show', $variety));

        $response->assertOk()
            ->assertSee($variety->name)
            ->assertSee($variety->sku)
            ->assertSee('Rp 50.000')
            ->assertSee('25 kg') // Total stock from seed lot (calculated from sellable seed lots)
            ->assertSee($seedLot->lot_code)
            ->assertSee('25 kg') // Seed lot quantity formatted with 0 decimals
            ->assertSee('Rp 2.000');
    }

    #[Test]
    public function admin_can_create_seed_lot_from_variety_page()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.seed-lots.create', ['variety_id' => $variety->id]));

        $response->assertOk()
            ->assertSee('Add New Seed Lot')
            ->assertSee($variety->name);
    }

    #[Test]
    public function admin_can_store_seed_lot_and_redirect_to_variety()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);

        $seedLotData = [
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
            'lot_code' => 'LOT-2024-002',
            'production_year' => 2024,
            'quantity' => 30,
            'unit' => 'kg',
            'price_per_unit' => 2500,
            'is_sellable' => true,
            'description' => 'Test seed lot',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.seed-lots.store'), $seedLotData);

        $response->assertRedirect(route('admin.varieties.show', $variety))
            ->assertSessionHas('success', 'Seed lot created successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'variety_id' => $variety->id,
            'lot_code' => 'LOT-2024-002',
            'quantity' => 30,
            'unit' => 'kg',
        ]);
    }

    #[Test]
    public function admin_can_update_seed_lot_and_redirect_to_variety()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);

        $seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
            'lot_code' => 'LOT-2024-003',
            'quantity' => 20,
            'unit' => 'kg',
        ]);

        $updateData = [
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
            'lot_code' => 'LOT-2024-003-UPDATED',
            'production_year' => 2024,
            'quantity' => 35,
            'unit' => 'kg',
            'price_per_unit' => 3000,
            'is_sellable' => true,
            'description' => 'Updated seed lot',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.seed-lots.update', $seedLot), $updateData);

        $response->assertRedirect(route('admin.varieties.show', $variety))
            ->assertSessionHas('success', 'Seed lot updated successfully.');

        $this->assertDatabaseHas('seed_lots', [
            'id' => $seedLot->id,
            'lot_code' => 'LOT-2024-003-UPDATED',
            'quantity' => 35,
        ]);
    }

    #[Test]
    public function admin_can_delete_seed_lot_and_redirect_to_variety()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);

        $seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.seed-lots.destroy', $seedLot), [
                'variety_id' => $variety->id,
            ]);

        $response->assertRedirect(route('admin.varieties.show', $variety))
            ->assertSessionHas('success', 'Seed lot deleted successfully.');

        $this->assertDatabaseMissing('seed_lots', [
            'id' => $seedLot->id,
        ]);
    }

    #[Test]
    public function variety_show_page_displays_correct_stock_calculations()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'minimum_limit' => 20,
            'status' => 'available',
        ]);

        // Create seed lots to provide actual stock data
        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'quantity' => 75,
            'unit' => 'kg',
            'is_sellable' => true,
        ]);

        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'quantity' => 25,
            'unit' => 'kg',
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.varieties.show', $variety));

        $response->assertOk()
            ->assertSee('75 kg') // BS stock calculated from seed lots
            ->assertSee('25 kg') // FS stock calculated from seed lots
            ->assertSee('100 kg') // Total stock calculated from seed lots
            ->assertSee('20 kg'); // Minimum limit formatted with 0 decimals
    }

    #[Test]
    public function variety_show_page_displays_low_stock_warning()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'minimum_limit' => 20,
            'status' => 'available',
        ]);

        // Create seed lots with low stock (total 8 kg, below minimum of 20 kg)
        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'quantity' => 5,
            'unit' => 'kg',
            'is_sellable' => true,
        ]);

        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->fsSeedClass->id,
            'quantity' => 3,
            'unit' => 'kg',
            'is_sellable' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.varieties.show', $variety));

        $response->assertOk()
            ->assertSee('8 kg') // Total stock below minimum formatted with 0 decimals
            ->assertSee('Restock');
    }

    #[Test]
    public function variety_show_page_displays_no_seed_lots_message()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.varieties.show', $variety));

        $response->assertOk()
            ->assertSee('No Seed Lots Found')
            ->assertSee('Create First Seed Lot');
    }

    #[Test]
    public function variety_show_page_queries_are_optimized()
    {
        $variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'status' => 'available',
        ]);

        // Create multiple seed lots
        SeedLot::factory()->count(5)->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
        ]);

        // Enable query logging
        \DB::enableQueryLog();

        $this->actingAs($this->admin)
            ->get(route('admin.varieties.show', $variety));

        $queries = \DB::getQueryLog();
        
        // Should not have N+1 queries - verify reasonable query count
        $this->assertLessThan(10, count($queries), 'Too many queries executed, possible N+1 problem');
        
        // Verify eager loading is working
        $seedLotQueries = array_filter($queries, function($query) {
            return str_contains($query['query'], 'seed_lots') && 
                   str_contains($query['query'], 'seed_classes');
        });
        
        $this->assertNotEmpty($seedLotQueries, 'Seed lots should be eager loaded with seed classes');
    }
}
