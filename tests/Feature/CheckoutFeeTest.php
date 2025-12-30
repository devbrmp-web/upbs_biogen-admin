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
        $variety = Variety::factory()->create(['price' => 50000]);
        $cart = [
            $variety->id => [
                'variety_id' => $variety->id,
                'quantity' => 2,
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
        $variety = Variety::factory()->create(['price' => 100000, 'stock' => 10]);
        
        $cart = [
            $variety->id => [
                'variety_id' => $variety->id,
                'quantity' => 1,
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
        
        // Check fees
        // Subtotal: 100,000
        // Service Fee: 1% of 100,000 = 1,000
        // App Fee: 1,000
        // Total: 100,000 + 1,000 + 1,000 = 102,000
        
        $this->assertEquals(100000, $order->subtotal);
        $this->assertEquals(1000, $order->service_fee);
        $this->assertEquals(1000, $order->app_fee);
        $this->assertEquals(102000, $order->total_amount);
    }
}
