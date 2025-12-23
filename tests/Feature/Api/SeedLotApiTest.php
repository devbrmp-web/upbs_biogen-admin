<?php

namespace Tests\Feature\Api;

use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedLotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_sellable_only_by_default(): void
    {
        $variety = Variety::factory()->create(['name' => 'Var A', 'slug' => 'var-a']);
        $bs = SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);

        SeedLot::factory()->forVariety($variety)->forSeedClass($bs)->sellable()->create(['quantity' => 10]);
        SeedLot::factory()->forVariety($variety)->forSeedClass($bs)->notSellable()->create(['quantity' => 10]);

        $response = $this->getJson('/api/seed-lots');

        $response->assertStatus(200)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonStructure(['data' => [[
                'id', 'lot_code', 'quantity', 'unit', 'price_per_unit_cents', 'price_idr', 'is_sellable', 'production_year', 'variety', 'seed_class', 'description'
            ]]]);
    }

    public function test_filter_by_seed_class_code(): void
    {
        $variety = Variety::factory()->create(['name' => 'Var B', 'slug' => 'var-b']);
        $fs = SeedClass::factory()->create(['code' => 'FS', 'name' => 'Foundation Seed']);
        $bs = SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);

        SeedLot::factory()->forVariety($variety)->forSeedClass($fs)->sellable()->create(['quantity' => 20]);
        SeedLot::factory()->forVariety($variety)->forSeedClass($bs)->sellable()->create(['quantity' => 30]);

        $response = $this->getJson('/api/seed-lots?seed_class_code=FS');

        $response->assertStatus(200)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.seed_class.code', 'FS');
    }

    public function test_show_returns_lot_by_code(): void
    {
        $variety = Variety::factory()->create(['name' => 'Var C', 'slug' => 'var-c']);
        $fs = SeedClass::factory()->create(['code' => 'FS', 'name' => 'Foundation Seed']);
        $lot = SeedLot::factory()->forVariety($variety)->forSeedClass($fs)->sellable()->create(['lot_code' => 'LOT-XY12']);

        $response = $this->getJson('/api/seed-lots/LOT-XY12');

        $response->assertStatus(200)
            ->assertJsonPath('data.lot_code', 'LOT-XY12')
            ->assertJsonPath('data.seed_class.code', 'FS')
            ->assertJsonPath('data.variety.slug', 'var-c');
    }
}

