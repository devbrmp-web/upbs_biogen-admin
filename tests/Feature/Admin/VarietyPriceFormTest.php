<?php

namespace Tests\Feature\Admin;

use App\Models\Commodity;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VarietyPriceFormTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Commodity $commodity;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a commodity for testing
        $this->commodity = Commodity::factory()->create();

        // Create an admin user with proper role_id
        $this->adminUser = User::factory()->create([
            'role_id' => 2, // Admin role
        ]);

        // Create a test variety
        $this->variety = Variety::factory()->create([
            'commodity_id' => $this->commodity->id,
            'price' => 15000,
        ]);
    }

    public function test_edit_form_displays_integer_price_without_decimals(): void
    {
        // Create variety with integer price
        $variety = Variety::create([
            'commodity_id' => $this->commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety description',
            'price' => 13003, // Integer price
            'stock_bs_kg' => 100,
            'stock_fs_kg' => 50,
            'minimum_limit' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.varieties.edit', $variety));

        $response->assertStatus(200);
        // Assert that the price is displayed as integer without decimals
        $response->assertSee('value="13003"', false);
        // Assert that decimal format is NOT present
        $response->assertDontSee('13003.00');
        $response->assertDontSee('13003,00');
    }

    public function test_create_form_accepts_integer_price(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'New Test Variety',
                'sku' => 'NEW-TEST-001',
                'description' => 'New test variety description',
                'price' => '25000', // String integer
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '15',
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success');

        // Verify the variety was created with integer price
        $variety = Variety::where('name', 'New Test Variety')->first();
        $this->assertNotNull($variety);
        $this->assertSame(25000, $variety->price);
        $this->assertIsInt($variety->price);
    }

    public function test_update_form_accepts_integer_price(): void
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
            ->put(route('admin.varieties.update', $variety), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Updated Test Variety',
                'sku' => 'TEST-VAR-002',
                'description' => 'Updated test variety description',
                'price' => '35000', // String integer
                'stock_bs_kg' => 100,
                'stock_fs_kg' => 50,
                'minimum_limit' => '20',
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success');

        // Verify the variety was updated with integer price
        $variety->refresh();
        $this->assertSame(35000, $variety->price);
        $this->assertIsInt($variety->price);
    }

    public function test_price_validation_rejects_decimal_input(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Decimal Test Variety',
                'sku' => 'TEST-DEC-001',
                'description' => 'Test variety with decimal price',
                'price' => '13003.50', // Decimal price should be rejected
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '10',
            ]);

        $response->assertSessionHasErrors('price');
        $response->assertSessionHasErrorsIn('default', [
            'price' => 'The price field must be an integer.'
        ]);
    }

    public function test_price_validation_rejects_non_numeric_input(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Invalid Price Test Variety',
                'sku' => 'TEST-NON-001',
                'description' => 'Test variety with invalid price',
                'price' => 'not-a-number', // Non-numeric price should be rejected
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '10',
            ]);

        $response->assertSessionHasErrors('price');
    }

    public function test_create_form_rejects_negative_price(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Test Variety with Negative Price',
                'sku' => 'TEST-NEG-001',
                'description' => 'Test variety with negative price',
                'price' => '-1000', // Negative price
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '15',
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    public function test_price_validation_accepts_zero_value(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Zero Price Test Variety',
                'sku' => 'TEST-ZERO-001',
                'description' => 'Test variety with zero price',
                'price' => '0', // Zero price should be accepted
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '10',
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success');

        // Verify the variety was created with zero price
        $variety = Variety::where('name', 'Zero Price Test Variety')->first();
        $this->assertNotNull($variety);
        $this->assertSame(0, $variety->price);
        $this->assertIsInt($variety->price);
    }

    public function test_update_price_validation_rejects_decimal_input(): void
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
            ->put(route('admin.varieties.update', $variety), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Updated Test Variety',
                'description' => 'Updated test variety description',
                'price' => '25000.75', // Decimal price should be rejected
                'minimum_limit' => '15',
            ]);

        $response->assertSessionHasErrors('price');
        $response->assertSessionHasErrorsIn('default', [
            'price' => 'The price field must be an integer.'
        ]);

        // Verify the variety price was not updated
        $variety->refresh();
        $this->assertSame(10000, $variety->price);
    }

    public function test_large_integer_price_is_handled_correctly(): void
    {
        $largePrice = 999999999; // Large but valid integer

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.varieties.store'), [
                'commodity_id' => $this->commodity->id,
                'name' => 'Large Price Test Variety',
                'sku' => 'TEST-LARGE-001',
                'description' => 'Test variety with large price',
                'price' => (string) $largePrice,
                'stock_bs_kg' => '100',
                'stock_fs_kg' => '50',
                'minimum_limit' => '10',
            ]);

        $response->assertRedirect(route('admin.varieties.index'));
        $response->assertSessionHas('success');

        // Verify the variety was created with large integer price
        $variety = Variety::where('name', 'Large Price Test Variety')->first();
        $this->assertNotNull($variety);
        $this->assertSame($largePrice, $variety->price);
        $this->assertIsInt($variety->price);
    }
}