<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Order;
use App\Models\SeedLot;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\Role;
use App\Models\Commodity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        Cache::flush();
        
        // Ensure roles exist or bypass by setting ID directly
        // Create Admin User
        $this->adminUser = User::factory()->create([
            'role_id' => 2, // 2 is Admin
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function dashboard_shell_loads_successfully()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Executive Overview'); // New Title
        $response->assertSee('skeleton'); // Should see skeleton loaders initially
    }

    /** @test */
    public function dashboard_stats_api_returns_correct_data()
    {
        Commodity::factory()->create();

        // Create Order Today
        Order::factory()->create([
            'created_at' => now(), 
            'total_amount' => 100000, 
            'status' => 'paid'
        ]);

        // Create Order Yesterday (for comparison growth)
        Order::factory()->create([
            'created_at' => now()->subDay(), 
            'total_amount' => 50000, 
            'status' => 'paid'
        ]);

        // Test API with "today" period
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.stats', ['period' => 'today']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'revenue' => ['value', 'growth'],
                'orders' => ['value', 'growth'],
                'aov' => ['value', 'growth']
            ]);
            
        // Revenue Today should be 100,000
        $this->assertEquals(100000, $response->json('revenue.value'));
        
        // Growth should be calculated vs Yesterday (50,000) => +100%
        $this->assertEquals(100, $response->json('revenue.growth'));
    }

    /** @test */
    public function dashboard_charts_api_returns_trend_and_forecast()
    {
        Commodity::factory()->create();
        
        // Create orders over last 3 days
        Order::factory()->create(['created_at' => now()->subDays(2), 'total_amount' => 100, 'status' => 'paid']);
        Order::factory()->create(['created_at' => now()->subDays(1), 'total_amount' => 120, 'status' => 'paid']);
        Order::factory()->create(['created_at' => now(), 'total_amount' => 140, 'status' => 'paid']);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.charts', ['period' => 'last_7_days']));

        $response->assertStatus(200)
            ->assertJsonStructure(['trend', 'forecast']);
            
        $trend = $response->json('trend');
        $this->assertCount(7, $trend); // Last 7 days including gaps
        
        // Forecast should be present
        $forecast = $response->json('forecast');
        $this->assertNotEmpty($forecast);
    }

    /** @test */
    public function dashboard_stock_api_returns_critical_seed_lots()
    {
        // Setup dependencies
        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create(['commodity_id' => $commodity->id]);
        $seedClass = SeedClass::factory()->create(['code' => 'SS']);

        // Create Critical Seed Lot
        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 5, // Critical < 10
            'is_sellable' => true
        ]);

        // Create Healthy Seed Lot
        SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 100,
            'is_sellable' => true
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.stock'));

        $response->assertStatus(200);
        $data = $response->json();
        
        // Should only see the critical one (quantity < 50 based on controller logic, but sorted asc)
        // Controller limit is 10. Both < 50 and 100? No, controller logic says < 50.
        // So Healthy (100) should NOT be there.
        
        $this->assertCount(1, $data);
        $this->assertEquals(5, $data[0]['quantity']);
        $this->assertEquals('critical', $data[0]['status']);
    }

    /** @test */
    public function dashboard_api_handles_errors_gracefully()
    {
        // Mock Cache to throw exception
        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('DB Error'));

        Log::shouldReceive('error')->atLeast()->once();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.stats'));

        $response->assertStatus(500)
            ->assertJson(['error' => 'Failed to fetch stats']);
    }
}
