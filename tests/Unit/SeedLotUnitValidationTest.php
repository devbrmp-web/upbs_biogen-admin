<?php

namespace Tests\Unit;

use App\Http\Requests\StoreSeedLotRequest;
use App\Http\Requests\UpdateSeedLotRequest;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use App\Models\Commodity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedLotUnitValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Variety $variety;
    protected SeedClass $bsSeedClass;
    protected SeedClass $fsSeedClass;
    protected SeedClass $plSeedClass;

    protected function setUp(): void
    {
        parent::setUp();
        
        $commodity = Commodity::factory()->create();
        $this->variety = Variety::factory()->create([
            'commodity_id' => $commodity->id,
        ]);
        
        // Use firstOrCreate to avoid unique constraint violations
        $this->bsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'BS'],
            ['name' => 'Breeder Seed']
        );
        
        $this->fsSeedClass = SeedClass::firstOrCreate(
            ['code' => 'FS'],
            ['name' => 'Foundation Seed']
        );
        
        $this->plSeedClass = SeedClass::firstOrCreate(
            ['code' => 'PL'],
            ['name' => 'Planlet']
        );
    }

    #[Test]
    public function store_request_validates_bs_seed_class_units_correctly()
    {
        $request = new StoreSeedLotRequest();
        
        // Valid units for BS
        $validUnits = ['kg', 'ton'];
        foreach ($validUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->bsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertFalse($validator->fails(), 
                "Unit '{$unit}' should be valid for BS seed class. Errors: " . json_encode($validator->errors()));
        }
        
        // Invalid units for BS
        $invalidUnits = ['bottle', 'piece'];
        foreach ($invalidUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->bsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertTrue($validator->fails(), 
                "Unit '{$unit}' should be invalid for BS seed class");
            $this->assertTrue($validator->errors()->has('unit'));
        }
    }

    #[Test]
    public function store_request_validates_fs_seed_class_units_correctly()
    {
        $request = new StoreSeedLotRequest();
        
        // Valid units for FS
        $validUnits = ['kg', 'ton'];
        foreach ($validUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->fsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertFalse($validator->fails(), 
                "Unit '{$unit}' should be valid for FS seed class. Errors: " . json_encode($validator->errors()));
        }
        
        // Invalid units for FS
        $invalidUnits = ['bottle', 'piece'];
        foreach ($invalidUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->fsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertTrue($validator->fails(), 
                "Unit '{$unit}' should be invalid for FS seed class");
            $this->assertTrue($validator->errors()->has('unit'));
        }
    }

    #[Test]
    public function store_request_validates_planlet_seed_class_units_correctly()
    {
        $request = new StoreSeedLotRequest();
        
        // Valid units for Planlet
        $validUnits = ['bottle', 'piece'];
        foreach ($validUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->plSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertFalse($validator->fails(), 
                "Unit '{$unit}' should be valid for Planlet seed class. Errors: " . json_encode($validator->errors()));
        }
        
        // Invalid units for Planlet
        $invalidUnits = ['kg', 'ton'];
        foreach ($invalidUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->plSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertTrue($validator->fails(), 
                "Unit '{$unit}' should be invalid for Planlet seed class");
            $this->assertTrue($validator->errors()->has('unit'));
        }
    }

    #[Test]
    public function update_request_validates_bs_seed_class_units_correctly()
    {
        // Create a seed lot to simulate updating
        $seedLot = SeedLot::factory()->create([
            'seed_class_id' => $this->bsSeedClass->id,
            'variety_id' => $this->variety->id,
        ]);
        
        $request = new UpdateSeedLotRequest();
        $request->setRouteResolver(function () use ($seedLot) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->andReturn($seedLot);
            return $route;
        });
        
        // Valid units for BS
        $validUnits = ['kg', 'ton'];
        foreach ($validUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->bsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertFalse($validator->fails(), 
                "Unit '{$unit}' should be valid for BS seed class in update. Errors: " . json_encode($validator->errors()));
        }
        
        // Invalid units for BS
        $invalidUnits = ['bottle', 'piece'];
        foreach ($invalidUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $this->bsSeedClass->id,
                'unit' => $unit,
            ]);
            
            $request->merge($data);
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertTrue($validator->fails(),
                "Unit '{$unit}' should be invalid for BS seed class in update");
            $this->assertTrue($validator->errors()->has('unit'));
        }
    }

    #[Test]
    public function validation_messages_contain_helpful_information()
    {
        $data = $this->getBaseSeedLotData([
            'seed_class_id' => $this->bsSeedClass->id,
            'unit' => 'bottle', // Invalid for BS
        ]);
        
        // Create request with data to trigger conditional logic
        $request = new StoreSeedLotRequest();
        $request->merge($data);
        
        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        
        $unitError = $validator->errors()->first('unit');
        $this->assertStringContainsString('Breeder Seed (BS) and Foundation Seed (FS)', $unitError);
        $this->assertStringContainsString('kg or ton', $unitError);
    }

    #[Test]
    public function unknown_seed_class_accepts_all_units()
    {
        $unknownSeedClass = SeedClass::firstOrCreate(
            ['code' => 'UK'],
            ['name' => 'Unknown Class']
        );
        
        $request = new StoreSeedLotRequest();
        $allUnits = ['kg', 'ton', 'piece', 'bottle'];
        
        foreach ($allUnits as $unit) {
            $data = $this->getBaseSeedLotData([
                'seed_class_id' => $unknownSeedClass->id,
                'unit' => $unit,
            ]);
            
            $validator = Validator::make($data, $request->rules(), $request->messages());
            $this->assertFalse($validator->fails(), 
                "Unit '{$unit}' should be valid for unknown seed class. Errors: " . json_encode($validator->errors()));
        }
    }

    #[Test]
    public function required_fields_are_validated()
    {
        $request = new StoreSeedLotRequest();
        
        $data = []; // Empty data
        
        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        
        $requiredFields = ['variety_id', 'seed_class_id', 'lot_code', 'production_year', 'quantity', 'unit', 'price_per_unit'];
        
        foreach ($requiredFields as $field) {
            $this->assertTrue($validator->errors()->has($field), 
                "Field '{$field}' should be required");
        }
    }

    #[Test]
    public function numeric_fields_are_validated()
    {
        $request = new StoreSeedLotRequest();
        
        $data = $this->getBaseSeedLotData([
            'production_year' => 'not-a-year',
            'quantity' => 'not-a-number',
            'price_per_unit' => 'not-a-price',
        ]);
        
        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        
        $this->assertTrue($validator->errors()->has('production_year'));
        $this->assertTrue($validator->errors()->has('quantity'));
        $this->assertTrue($validator->errors()->has('price_per_unit'));
    }

    #[Test]
    public function lot_code_uniqueness_is_validated()
    {
        $request = new StoreSeedLotRequest();
        
        // Create existing seed lot with specific seed_class_id to avoid factory creating new SeedClass
        \App\Models\SeedLot::factory()->create([
            'lot_code' => 'EXISTING-LOT-001',
            'seed_class_id' => $this->bsSeedClass->id,
            'variety_id' => $this->variety->id,
        ]);
        
        $data = $this->getBaseSeedLotData([
            'lot_code' => 'EXISTING-LOT-001', // Duplicate
        ]);
        
        $validator = Validator::make($data, $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lot_code'));
    }

    /**
     * Get base seed lot data for testing
     */
    private function getBaseSeedLotData(array $overrides = []): array
    {
        return array_merge([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'lot_code' => 'TEST-LOT-' . uniqid(),
            'production_year' => 2024,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 2000,
            'is_sellable' => true,
            'description' => 'Test description',
        ], $overrides);
    }
}
