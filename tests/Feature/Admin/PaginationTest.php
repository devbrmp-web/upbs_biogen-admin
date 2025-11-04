<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\Variety;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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
    }

    public function test_commodities_pagination_works(): void
    {
        // Create 25 commodities to test pagination (10 per page)
        Commodity::factory(25)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index'));

        $response->assertStatus(200);
        
        // Should show 10 items on first page
        $response->assertViewHas('commodities');
        $commodities = $response->viewData('commodities');
        $this->assertEquals(10, $commodities->count());
        $this->assertEquals(25, $commodities->total());
        
        // Test second page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index', ['page' => 2]));

        $response->assertStatus(200);
        $commodities = $response->viewData('commodities');
        $this->assertEquals(10, $commodities->count());
        
        // Test third page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index', ['page' => 3]));

        $response->assertStatus(200);
        $commodities = $response->viewData('commodities');
        $this->assertEquals(5, $commodities->count()); // Remaining 5 items
    }

    public function test_varieties_pagination_works(): void
    {
        // Create a commodity first
        $commodity = Commodity::factory()->create();
        
        // Create 25 varieties to test pagination (10 per page)
        Variety::factory(25)->create(['commodity_id' => $commodity->id]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index'));

        $response->assertStatus(200);
        
        // Should show 10 items on first page
        $response->assertViewHas('varieties');
        $varieties = $response->viewData('varieties');
        $this->assertEquals(10, $varieties->count());
        $this->assertEquals(25, $varieties->total());
        
        // Test second page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['page' => 2]));

        $response->assertStatus(200);
        $varieties = $response->viewData('varieties');
        $this->assertEquals(10, $varieties->count());
        
        // Test third page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['page' => 3]));

        $response->assertStatus(200);
        $varieties = $response->viewData('varieties');
        $this->assertEquals(5, $varieties->count()); // Remaining 5 items
    }

    public function test_varieties_pagination_with_filters(): void
    {
        $commodity1 = Commodity::factory()->create(['name' => 'Rice']);
        $commodity2 = Commodity::factory()->create(['name' => 'Corn']);
        
        // Create 15 rice varieties and 10 corn varieties
        Variety::factory(15)->create(['commodity_id' => $commodity1->id]);
        Variety::factory(10)->create(['commodity_id' => $commodity2->id]);

        // Test pagination with commodity filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['commodity' => $commodity1->id]));

        $response->assertStatus(200);
        $varieties = $response->viewData('varieties');
        $this->assertEquals(10, $varieties->count()); // First page of rice varieties
        $this->assertEquals(15, $varieties->total()); // Total rice varieties
        
        // Test second page of filtered results
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['commodity' => $commodity1->id, 'page' => 2]));

        $response->assertStatus(200);
        $varieties = $response->viewData('varieties');
        $this->assertEquals(5, $varieties->count()); // Remaining 5 rice varieties
    }

    public function test_pagination_links_preserve_filters(): void
    {
        $commodity = Commodity::factory()->create(['name' => 'Test Commodity']);
        Variety::factory(25)->create(['commodity_id' => $commodity->id]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.index', ['commodity' => $commodity->id, 'q' => 'test']));

        $response->assertStatus(200);
        
        // Check that pagination links contain the filter parameters
        $content = $response->getContent();
        $this->assertStringContainsString('commodity=' . $commodity->id, $content);
        $this->assertStringContainsString('q=test', $content);
    }
}