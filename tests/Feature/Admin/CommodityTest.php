<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommodityTest extends TestCase
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

    public function test_admin_can_view_commodities_index(): void
    {
        $commodity = Commodity::factory()->create([
            'name' => 'Test Commodity',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Commodity');
    }

    public function test_admin_can_search_commodities_by_name(): void
    {
        Commodity::factory()->create(['name' => 'Alpha Rice']);
        Commodity::factory()->create(['name' => 'Beta Corn']);

        // Search using 'q' parameter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index', ['q' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Alpha Rice');
        $response->assertDontSee('Beta Corn');

        // Search using 'search' parameter alias
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.index', ['search' => 'Beta']));

        $response->assertStatus(200);
        $response->assertSee('Beta Corn');
        $response->assertDontSee('Alpha Rice');
    }

    public function test_admin_can_create_commodity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.create'));

        $response->assertStatus(200);
        $response->assertSee('New Commodity');
    }

    public function test_admin_can_store_commodity(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('commodity.jpg');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.commodities.store'), [
                'name' => 'New Commodity',
                'is_active' => true,
                'image' => $image,
            ]);

        $response->assertRedirect(route('admin.commodities.index'));
        $response->assertSessionHas('success', 'Commodity created successfully.');

        $this->assertDatabaseHas('commodities', [
            'name' => 'New Commodity',
            'is_active' => true,
        ]);

        $commodity = Commodity::where('name', 'New Commodity')->first();
        $this->assertNotNull($commodity->image_path);
        Storage::disk('public')->assertExists($commodity->image_path);
    }

    public function test_admin_can_view_commodity(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.show', $commodity));

        $response->assertStatus(200);
        $response->assertSee('Test Commodity');
    }

    public function test_admin_can_edit_commodity(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.commodities.edit', $commodity));

        $response->assertStatus(200);
        $response->assertSee('Edit Commodity');
    }

    public function test_admin_can_update_commodity(): void
    {
        Storage::fake('public');

        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'is_active' => true,
        ]);

        $newImage = UploadedFile::fake()->image('new-commodity.jpg');

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.commodities.update', $commodity), [
                'name' => 'Updated Commodity',
                'is_active' => false,
                'image' => $newImage,
            ]);

        $response->assertRedirect(route('admin.commodities.index'));
        $response->assertSessionHas('success', 'Commodity updated successfully.');

        $this->assertDatabaseHas('commodities', [
            'id' => $commodity->id,
            'name' => 'Updated Commodity',
            'is_active' => false,
        ]);

        $commodity->refresh();
        $this->assertNotNull($commodity->image_path);
        Storage::disk('public')->assertExists($commodity->image_path);
    }

    public function test_admin_can_delete_commodity(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('commodity.jpg');
        $imagePath = $image->store('commodities', 'public');

        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'is_active' => true,
            'image_path' => $imagePath,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.commodities.destroy', $commodity));

        $response->assertRedirect(route('admin.commodities.index'));
        $response->assertSessionHas('success', 'Commodity deleted successfully.');

        $this->assertDatabaseMissing('commodities', [
            'id' => $commodity->id,
        ]);

        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_commodity_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.commodities.store'), [
                'name' => '', // Required field empty
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_guest_cannot_access_commodity_routes(): void
    {
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'is_active' => true,
        ]);

        $routes = [
            ['GET', route('admin.commodities.index')],
            ['GET', route('admin.commodities.create')],
            ['POST', route('admin.commodities.store')],
            ['GET', route('admin.commodities.show', $commodity)],
            ['GET', route('admin.commodities.edit', $commodity)],
            ['PUT', route('admin.commodities.update', $commodity)],
            ['DELETE', route('admin.commodities.destroy', $commodity)],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->call($method, $url);
            $response->assertRedirect(route('login'));
        }
    }
}