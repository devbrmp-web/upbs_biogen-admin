<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_checkout_endpoint_creates_order_and_related_records(): void
    {
        $variety = Variety::factory()->available()->create([
            'price' => 50000,
        ]);

        $seedClass = SeedClass::factory()->create([
            'code' => 'BS',
            'name' => 'Breeder Seed',
            'is_active' => true,
        ]);

        $seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_address' => 'Jl. Contoh No. 1, Jakarta',
            'customer_phone' => '+628123456789',
            'customer_email' => 'john@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 2, 'seed_lot_id' => $seedLot->id],
            ],
            'payment_method' => Payment::METHOD_QRIS,
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);
        dump($response->json());
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'snap_token',
                    'order_code',
                    'order' => [
                        'order_code',
                        'status',
                        'shipping_method',
                        'totals' => ['subtotal', 'shipping_cost', 'service_fee', 'app_fee', 'total_amount'],
                    ],
                ],
            ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertEquals(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertEquals(100000, (int) $order->subtotal);
        $this->assertEquals(0, (int) $order->shipping_cost);
        $this->assertEquals(1000, (int) $order->service_fee);
        $this->assertEquals(4000, (int) $order->app_fee);
        $this->assertEquals(105000, (int) $order->total_amount);

        $this->assertCount(1, $order->items);
        $this->assertEquals(2, (int) $order->items->first()->quantity);

        $this->assertNotNull($order->payment);
        $this->assertEquals(Payment::STATUS_PENDING, $order->payment->status);
        $this->assertEquals(Payment::METHOD_BANK_TRANSFER, $order->payment->payment_method);

        $this->assertNotNull($order->shipment);
        $this->assertEquals(Order::SHIPPING_PICKUP, $order->shipment->shipping_method);
    }
    public function test_checkout_rejects_decimal_quantity(): void
    {
        $variety = Variety::factory()->available()->create();
        $seedClass = SeedClass::factory()->create(['code' => 'BS', 'is_active' => true]);
        $seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 100,
            'is_sellable' => true,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_address' => 'Jl. Contoh No. 1, Jakarta',
            'customer_phone' => '+628123456789',
            'customer_email' => 'john@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 2.5, 'seed_lot_id' => $seedLot->id],
            ],
            'payment_method' => Payment::METHOD_QRIS,
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
        
        $this->assertEquals('Jumlah harus berupa angka bulat (tidak boleh desimal).', $response->json('errors')['items.0.quantity'][0]);
    }
}
