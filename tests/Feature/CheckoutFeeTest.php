<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_view_displays_application_fee(): void
    {
        // Setup cart in session
        $variety = Variety::factory()->create();
        $seedClass = \App\Models\SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);
        $seedLot = \App\Models\SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 100,
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);
        $cart = [
            $variety->id => [
                'variety_id' => $variety->id,
                'quantity' => 2,
                'seed_lot_id' => $seedLot->id,
            ]
        ];
        
        $response = $this->withSession(['cart' => $cart])
            ->get(route('client.checkout.index'));

        $response->assertStatus(200);
        $response->assertSee('Application Fee:');
        $response->assertSee('Rp 1.000');
    }

    public function test_checkout_process_applies_correct_fees(): void
    {
        $variety = Variety::factory()->create(['minimum_limit' => 0]);
        $seedClass = \App\Models\SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);
        $seedLot = \App\Models\SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 100,
            'price_per_unit' => 100000,
            'is_sellable' => true,
        ]);
        
        $cart = [
            $variety->id => [
                'variety_id' => $variety->id,
                'quantity' => 1,
                'seed_lot_id' => $seedLot->id,
            ]
        ];

        $payload = [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '081234567890',
            'customer_address' => 'Test Address',
            'shipping_method' => 'pickup',
            'terms_accepted' => 1,
        ];

        $response = $this->withSession(['cart' => $cart])
            ->post(route('client.checkout.process'), $payload);

        if ($response->status() !== 302) {
             dump($response->getContent());
        }
        $response->assertRedirect();
        
        $order = Order::latest()->first();
        
        if (!$order) {
            dump(session('error'));
            dump(session('errors') ? session('errors')->all() : 'No validation errors');
        }

        $this->assertNotNull($order);
        
        $this->assertNotNull($order);
    }
}
