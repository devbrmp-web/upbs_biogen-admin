<?php

namespace Tests\Feature\Api;

use App\Models\SeedClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedClassApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_active_by_default(): void
    {
        SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed', 'is_active' => true]);
        SeedClass::factory()->create(['code' => 'FS', 'name' => 'Foundation Seed', 'is_active' => true]);
        SeedClass::factory()->create(['code' => 'CS', 'name' => 'Certified Seed', 'is_active' => false]);

        $response = $this->getJson('/api/seed-classes');

        $response->assertStatus(200)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'description', 'is_active']]]);
    }

    public function test_index_supports_search_q(): void
    {
        SeedClass::factory()->create(['code' => 'ST', 'name' => 'Starter', 'is_active' => true]);
        SeedClass::factory()->create(['code' => 'FS', 'name' => 'Foundation Seed', 'is_active' => true]);

        $response = $this->getJson('/api/seed-classes?q=Plan');

        $response->assertStatus(200)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.code', 'ST');
    }

    public function test_show_returns_class_by_code(): void
    {
        SeedClass::factory()->create(['code' => 'FS', 'name' => 'Foundation Seed', 'is_active' => true]);

        $response = $this->getJson('/api/seed-classes/FS');

        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'FS')
            ->assertJsonPath('data.name', 'Foundation Seed');
    }
}

