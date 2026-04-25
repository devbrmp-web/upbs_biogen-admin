<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\SeedLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class SeedLotValidationTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected User $admin;
    protected Commodity $commodity;
    protected Variety $variety;
    protected SeedClass $basicSeedClass;
    protected SeedClass $fsSeedClass;
    protected SeedClass $starterSeedClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with proper role
        $this->admin = User::factory()->superAdmin()->create();
        
        // Create seed classes
        $this->createSeedClasses();
        
        $this->commodity = Commodity::factory()->create();
        $this->variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);
        
        // Initialize seed classes
        $this->basicSeedClass = SeedClass::where('code', 'BS')->first();
        $this->fsSeedClass = SeedClass::where('code', 'FS')->first();
        $this->starterSeedClass = SeedClass::where('code', 'ST')->first();
    }

    #[Test]
    public function bs_seed_class_accepts_weight_based_units()
    {
        $validUnits = ['kg', 'ton'];
        
        foreach ($validUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->basicSeedClass->id,
                'lot_code' => 'LOT-BS-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 25,
                'unit' => $unit,
                'price_per_unit' => 2000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            // For BS, 'ton' input is normalized to stored 'kg' with quantity multiplied by 1000 and price_per_unit divided by 1000
            $seedLot = SeedLot::where('lot_code', 'LOT-BS-' . strtoupper($unit))->first();
            $this->assertNotNull($seedLot);
            $this->assertEquals('kg', $seedLot->unit);
            $this->assertEquals($unit === 'ton' ? 25 * 1000 : 25, $seedLot->quantity);
            $this->assertEquals($unit === 'ton' ? 2000 / 1000 : 2000, $seedLot->price_per_unit);
        }
    }

    #[Test]
    public function bs_seed_class_rejects_non_weight_units()
    {
        $invalidUnits = ['bottle', 'piece'];
        
        foreach ($invalidUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->basicSeedClass->id,
                'lot_code' => 'LOT-BS-INVALID-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 25,
                'unit' => $unit,
                'price_per_unit' => 2000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertSessionHasErrors('unit');
            
            $this->assertDatabaseMissing('seed_lots', [
                'lot_code' => 'LOT-BS-INVALID-' . strtoupper($unit),
            ]);
        }
    }

    #[Test]
    public function fs_seed_class_accepts_weight_based_units()
    {
        $validUnits = ['kg', 'ton'];
        
        foreach ($validUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->fsSeedClass->id,
                'lot_code' => 'LOT-FS-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 15,
                'unit' => $unit,
                'price_per_unit' => 3000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            // For FS, 'ton' input is normalized to stored 'kg' with quantity multiplied by 1000 and price_per_unit divided by 1000
            $seedLot = SeedLot::where('lot_code', 'LOT-FS-' . strtoupper($unit))->first();
            $this->assertNotNull($seedLot);
            $this->assertEquals('kg', $seedLot->unit);
            $this->assertEquals($unit === 'ton' ? 15 * 1000 : 15, $seedLot->quantity);
            $this->assertEquals($unit === 'ton' ? 3000 / 1000 : 3000, $seedLot->price_per_unit);
        }
    }

    #[Test]
    public function fs_seed_class_rejects_non_weight_units()
    {
        $invalidUnits = ['bottle', 'piece'];
        
        foreach ($invalidUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->fsSeedClass->id,
                'lot_code' => 'LOT-FS-INVALID-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 15,
                'unit' => $unit,
                'price_per_unit' => 3000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertSessionHasErrors('unit');
            
            $this->assertDatabaseMissing('seed_lots', [
                'lot_code' => 'LOT-FS-INVALID-' . strtoupper($unit),
            ]);
        }
    }

    #[Test]
    public function starter_seed_class_accepts_bottle_and_piece_units()
    {
        $validUnits = ['bottle', 'piece'];
        
        foreach ($validUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->starterSeedClass->id,
                'lot_code' => 'LOT-ST-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => $unit,
                'price_per_unit' => 5000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('seed_lots', [
                'lot_code' => 'LOT-ST-' . strtoupper($unit),
                'unit' => $unit,
            ]);
        }
    }

    #[Test]
    public function starter_seed_class_rejects_weight_units()
    {
        $invalidUnits = ['kg', 'ton'];
        
        foreach ($invalidUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->starterSeedClass->id,
                'lot_code' => 'LOT-ST-INVALID-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => $unit,
                'price_per_unit' => 5000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertSessionHasErrors('unit');
            
            $this->assertDatabaseMissing('seed_lots', [
                'lot_code' => 'LOT-ST-INVALID-' . strtoupper($unit),
            ]);
        }
    }

    #[Test]
    public function update_validation_works_for_seed_class_unit_combinations()
    {
        // Create a BS seed lot with kg unit
        $seedLot = SeedLot::factory()->create([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->basicSeedClass->id,
            'lot_code' => 'LOT-UPDATE-TEST',
            'unit' => 'kg',
        ]);

        // Try to update to invalid unit for BS
        $updateData = [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->basicSeedClass->id,
            'lot_code' => 'LOT-UPDATE-TEST',
            'production_year' => 2024,
            'quantity' => 30,
            'unit' => 'bottle', // Invalid for BS
            'price_per_unit' => 2500,
            'is_sellable' => true,
            'harvest_date' => '2024-01-01',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.seed-lots.update', $seedLot), $updateData);

        $response->assertSessionHasErrors('unit');
        
        // Verify the unit wasn't changed
        $this->assertDatabaseHas('seed_lots', [
            'id' => $seedLot->id,
            'unit' => 'kg', // Should remain unchanged
        ]);
    }

    #[Test]
    public function validation_messages_are_descriptive()
    {
        $seedLotData = [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->basicSeedClass->id,
            'lot_code' => 'LOT-MESSAGE-TEST',
            'production_year' => 2024,
            'quantity' => 25,
            'unit' => 'bottle', // Invalid for BS
            'price_per_unit' => 2000,
            'is_sellable' => true,
            'harvest_date' => '2024-01-01',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.seed-lots.store'), $seedLotData);

        $response->assertSessionHasErrors('unit');
        
        $errors = session('errors');
        $unitError = $errors->get('unit')[0];
        
        $this->assertStringContainsString('The unit is invalid for Breeder Seed', $unitError);
    }

    #[Test]
    public function custom_seed_classes_respect_their_categories()
    {
        // 1. Test Custom Weight Class
        $weightClass = SeedClass::create([
            'code' => 'CW',
            'name' => 'Custom Weight',
            'stock_category' => 'weight',
            'default_unit' => 'gram'
        ]);

        $weightUnits = ['kg', 'ton', 'gram'];
        foreach ($weightUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $weightClass->id,
                'lot_code' => 'LOT-CW-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 10,
                'unit' => $unit,
                'price_per_unit' => 1000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()->assertSessionHasNoErrors();
        }

        // 2. Test Custom Unit Class
        $unitClass = SeedClass::create([
            'code' => 'CU',
            'name' => 'Custom Unit',
            'stock_category' => 'unit',
            'default_unit' => 'knol'
        ]);

        $unitUnits = ['bottle', 'piece', 'knol'];
        foreach ($unitUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $unitClass->id,
                'lot_code' => 'LOT-CU-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 10,
                'unit' => $unit,
                'price_per_unit' => 1000,
                'is_sellable' => true,
                'harvest_date' => '2024-01-01',
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()->assertSessionHasNoErrors();
        }
    }
    #[Test]
    public function quantity_must_be_an_integer()
    {
        $seedLotData = [
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->basicSeedClass->id,
            'lot_code' => 'LOT-DECIMAL-TEST',
            'production_year' => 2024,
            'quantity' => 25.5, // Decimal quantity
            'unit' => 'kg',
            'price_per_unit' => 2000,
            'is_sellable' => true,
            'harvest_date' => '2024-01-01',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.seed-lots.store'), $seedLotData);

        $response->assertSessionHasErrors('quantity');
        
        $errors = session('errors');
        $this->assertEquals('Jumlah harus berupa angka bulat (tidak boleh desimal).', $errors->get('quantity')[0]);
        
        $this->assertDatabaseMissing('seed_lots', [
            'lot_code' => 'LOT-DECIMAL-TEST',
        ]);
    }
}