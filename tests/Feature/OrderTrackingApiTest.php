<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_200_with_valid_tracking_in_orders(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'tracking_number' => 'TRACK-ORD-123',
            'courier_name' => 'Pos Indonesia',
        ]);

        Shipment::factory()->create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'tracking_number' => 'TRACK-ORD-123',
            'courier_name' => 'Pos Indonesia',
        ]);

        $response = $this->getJson('/api/orders/track/TRACK-ORD-123');

        $response->assertStatus(200)
            ->assertJsonPath('data.tracking_number', 'TRACK-ORD-123')
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.shipment_status', 'shipped');
    }

    public function test_returns_200_with_tracking_only_in_shipments(): void
    {
        $order = Order::factory()->create([
            'status' => 'shipped',
            'tracking_number' => null,
            'courier_name' => 'Indah Cargo',
        ]);

        Shipment::factory()->create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'tracking_number' => 'TRACK-SHP-456',
            'courier_name' => 'Indah Cargo',
        ]);

        $response = $this->getJson('/api/orders/track/TRACK-SHP-456');

        $response->assertStatus(200)
            ->assertJsonPath('data.tracking_number', 'TRACK-SHP-456')
            ->assertJsonPath('data.order_code', $order->order_code)
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.shipment_status', 'delivered');
    }

    public function test_returns_404_when_tracking_not_found(): void
    {
        $response = $this->getJson('/api/orders/track/NOT-FOUND-999');
        $response->assertStatus(404)
            ->assertJsonPath('message', 'Tracking number not found');
    }

    public function test_returns_422_on_invalid_tracking_format(): void
    {
        $response = $this->getJson('/api/orders/track/INVALID*FORMAT');
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['tracking_number']]);
    }
}
