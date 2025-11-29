<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Variety;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use WithFaker;

    public function test_checkout_endpoint_creates_order_and_related_records(): void
    {
        $variety = Variety::factory()->available()->create([
            'price' => 50000,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_address' => 'Jl. Contoh No. 1, Jakarta',
            'customer_phone' => '+628123456789',
            'customer_email' => 'john@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 2],
            ],
            'payment_method' => Payment::METHOD_QRIS,
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order_code', 'status', 'shipping_method',
                    'totals' => ['subtotal', 'shipping_cost', 'total_amount'],
                    'payment' => ['method', 'status'],
                    'shipment' => ['shipping_method', 'status'],
                ],
            ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertEquals(100000, (int) $order->subtotal);
        $this->assertEquals(0, (int) $order->shipping_cost);
        $this->assertEquals(100000, (int) $order->total_amount);

        $this->assertCount(1, $order->items);
        $this->assertEquals(2, (int) $order->items->first()->quantity);

        $this->assertNotNull($order->payment);
        $this->assertEquals(Payment::STATUS_PENDING, $order->payment->status);
        $this->assertEquals(Payment::METHOD_QRIS, $order->payment->payment_method);

        $this->assertNotNull($order->shipment);
        $this->assertEquals(Shipment::STATUS_PENDING, $order->shipment->status);
        $this->assertEquals(Order::SHIPPING_PICKUP, $order->shipment->shipping_method);
    }
}

