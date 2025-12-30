<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_track_by_order_code(): void
    {
        $order = Order::factory()->create([
            'order_code' => 'ORD-12345',
            'status' => 'processing',
        ]);

        $response = $this->getJson('/api/orders/track?order_code=ORD-12345');

        $response->assertStatus(200)
            ->assertJsonPath('data.order_code', 'ORD-12345')
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_can_track_by_phone(): void
    {
        $order = Order::factory()->create([
            'customer_phone' => '081234567890',
            'status' => 'processing',
        ]);

        $response = $this->getJson('/api/orders/track?phone=081234567890');

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_phone', '081234567890')
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_track_by_phone_returns_latest_order(): void
    {
        $oldOrder = Order::factory()->create([
            'customer_phone' => '081234567890',
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        $newOrder = Order::factory()->create([
            'customer_phone' => '081234567890',
            'status' => 'processing',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/orders/track?phone=081234567890');

        $response->assertStatus(200)
            ->assertJsonPath('data.order_code', $newOrder->order_code)
            ->assertJsonPath('data.status', 'processing');
    }
}
