<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingLogicTest extends TestCase
{
    use RefreshDatabase;

    private function createVarietyAndLot(): array
    {
        $variety = Variety::factory()->available()->create(['price' => 10000]);
        $class = SeedClass::factory()->create(['code' => 'SS', 'name' => 'Stock Seed']);
        $lot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $class->id,
            'quantity' => 100,
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);
        return [$variety, $lot];
    }

    public function test_pickup_sets_courier_correctly()
    {
        $this->withoutExceptionHandling();
        [$variety, $lot] = $this->createVarietyAndLot();
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [['variety_id' => $variety->id, 'quantity' => 5, 'seed_lot_id' => $lot->id]],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);
        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);

        $order = Order::first();
        $this->assertEquals('Ambil di Tempat', $order->courier_name);
        $this->assertEquals('BRMP Biogen', $order->courier_service);
        $this->assertEquals(0, $order->shipping_cost);
    }

    public function test_delivery_small_weight_sets_pos_indonesia()
    {
        [$variety, $lot] = $this->createVarietyAndLot();
        // 5kg <= 10kg -> Pos Indonesia
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_DELIVERY,
            'items' => [['variety_id' => $variety->id, 'quantity' => 5, 'seed_lot_id' => $lot->id]],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $this->postJson('/api/orders/checkout', $payload)->assertStatus(200);

        $order = Order::first();
        $this->assertEquals('Pos Indonesia', $order->courier_name);
        $this->assertEquals('Regular', $order->courier_service);
    }

    public function test_delivery_large_weight_sets_indah_cargo()
    {
        [$variety, $lot] = $this->createVarietyAndLot();
        // 11kg > 10kg -> Indah Cargo
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_DELIVERY,
            'items' => [['variety_id' => $variety->id, 'quantity' => 11, 'seed_lot_id' => $lot->id]],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $this->postJson('/api/orders/checkout', $payload)->assertStatus(200);

        $order = Order::first();
        $this->assertEquals('Indah Cargo', $order->courier_name);
        $this->assertEquals('Regular', $order->courier_service);
    }

    public function test_track_order_response_includes_shipping_method()
    {
        [$variety, $lot] = $this->createVarietyAndLot();
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [['variety_id' => $variety->id, 'quantity' => 5, 'seed_lot_id' => $lot->id]],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $this->postJson('/api/orders/checkout', $payload)->assertStatus(200);
        $order = Order::first();

        // Test track endpoint
        $response = $this->getJson('/api/orders/track?order_code=' . $order->order_code);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['shipping_method', 'shipping_method_label']]);
        $this->assertEquals('pickup', $response->json('data.shipping_method'));
        $this->assertEquals('Pickup at BRMP', $response->json('data.shipping_method_label'));

        // Test public order endpoint
        $response = $this->getJson("/api/orders/{$order->order_code}");
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['shipping_method', 'shipping_method_label']]);
        $this->assertEquals('pickup', $response->json('data.shipping_method'));
        $this->assertEquals('Pickup at BRMP', $response->json('data.shipping_method_label'));
    }
}
