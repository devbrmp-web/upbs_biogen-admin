<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingWebAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_track_via_web_alias_with_query_param(): void
    {
        $order = Order::factory()->create([
            'order_code' => 'ORD-WEB-123',
            'status' => 'processing',
        ]);

        // Note: calling /orders/track (without /api)
        $response = $this->getJson('/orders/track?order_code=ORD-WEB-123');

        $response->assertStatus(200)
            ->assertJsonPath('data.order_code', 'ORD-WEB-123')
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_can_track_via_web_alias_with_path_param(): void
    {
        $order = Order::factory()->create([
            'tracking_number' => 'TRACK-WEB-456',
            'status' => 'shipped',
        ]);

        // Note: calling /orders/track/{tracking_number} (without /api)
        $response = $this->getJson('/orders/track/TRACK-WEB-456');

        $response->assertStatus(200)
            ->assertJsonPath('data.tracking_number', 'TRACK-WEB-456')
            ->assertJsonPath('data.status', 'shipped');
    }
}
