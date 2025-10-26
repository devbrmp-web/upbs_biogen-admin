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

class SeedLotValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Commodity $commodity;
    protected Variety $variety;
    protected SeedClass $basicSeedClass;
    protected SeedClass $fsSeedClass;
    protected SeedClass $planletSeedClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user with proper role
        $this->admin = User::factory()->superAdmin()->create();
        
        $this->commodity = Commodity::factory()->create();
        $this->variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
        ]);
        
        // Use existing seed classes from base TestCase
        $this->basicSeedClass = SeedClass::where('code', 'BS')->first();
        $this->fsSeedClass = SeedClass::where('code', 'FS')->first();
        $this->planletSeedClass = SeedClass::where('code', 'PL')->first();
    }

    #[Test]
    public function bs_seed_class_accepts_weight_based_units()
    {
        $validUnits = ['kg', 'gram', 'ton'];
        
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
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('seed_lots', [
                'lot_code' => 'LOT-BS-' . strtoupper($unit),
                'unit' => $unit,
            ]);
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
        $validUnits = ['kg', 'gram', 'ton'];
        
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
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('seed_lots', [
                'lot_code' => 'LOT-FS-' . strtoupper($unit),
                'unit' => $unit,
            ]);
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
    public function planlet_seed_class_accepts_bottle_and_piece_units()
    {
        $validUnits = ['bottle', 'piece'];
        
        foreach ($validUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->planletSeedClass->id,
                'lot_code' => 'LOT-PL-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => $unit,
                'price_per_unit' => 5000,
                'is_sellable' => true,
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('seed_lots', [
                'lot_code' => 'LOT-PL-' . strtoupper($unit),
                'unit' => $unit,
            ]);
        }
    }

    #[Test]
    public function planlet_seed_class_rejects_weight_units()
    {
        $invalidUnits = ['kg', 'gram', 'ton'];
        
        foreach ($invalidUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $this->planletSeedClass->id,
                'lot_code' => 'LOT-PL-INVALID-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 100,
                'unit' => $unit,
                'price_per_unit' => 5000,
                'is_sellable' => true,
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertSessionHasErrors('unit');
            
            $this->assertDatabaseMissing('seed_lots', [
                'lot_code' => 'LOT-PL-INVALID-' . strtoupper($unit),
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
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.seed-lots.store'), $seedLotData);

        $response->assertSessionHasErrors('unit');
        
        $errors = session('errors');
        $unitError = $errors->get('unit')[0];
        
        $this->assertStringContainsString('Breeder Seed (BS) and Foundation Seed (FS)', $unitError);
    }

    #[Test]
    public function unknown_seed_class_accepts_all_units()
    {
        $unknownSeedClass = SeedClass::firstOrCreate(
            ['code' => 'UK'],
            ['name' => 'Unknown Class']
        );

        $allUnits = ['kg', 'gram', 'ton', 'piece', 'bottle'];
        
        foreach ($allUnits as $unit) {
            $seedLotData = [
                'variety_id' => $this->variety->id,
                'seed_class_id' => $unknownSeedClass->id,
                'lot_code' => 'LOT-UK-' . strtoupper($unit),
                'production_year' => 2024,
                'quantity' => 10,
                'unit' => $unit,
                'price_per_unit' => 1000,
                'is_sellable' => true,
            ];

            $response = $this->actingAs($this->admin)
                ->post(route('admin.seed-lots.store'), $seedLotData);

            $response->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('seed_lots', [
                'lot_code' => 'LOT-UK-' . strtoupper($unit),
                'unit' => $unit,
            ]);
        }
    }
}