<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function createVarietyAndLot(int $price = 100000): array
    {
        $variety = Variety::factory()->available()->create();
        $class = SeedClass::factory()->create(['code' => 'SS', 'name' => 'Stock Seed']);
        $lot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $class->id,
            'quantity' => 100,
            'price_per_unit' => $price,
            'is_sellable' => true,
        ]);
        return [$variety, $lot];
    }

    public function test_fee_calculation_is_correct()
    {
        $this->withoutExceptionHandling();
        [$variety, $lot] = $this->createVarietyAndLot(100000); // 1 item @ 100k
        
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [['variety_id' => $variety->id, 'quantity' => 1, 'seed_lot_id' => $lot->id]],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);
        $response->assertStatus(200);

        $order = Order::first();
        
        // Perhitungan:
        // Subtotal: 100.000
        // Service Fee (1%): 1.000
        // App Fee: 4.000
        // Total: 105.000
        
        $this->assertEquals(100000, (int) $order->subtotal);
        $this->assertEquals(1000, (int) $order->service_fee);
        $this->assertEquals(4000, (int) $order->app_fee);
        $this->assertEquals(105000, (int) $order->total_amount);
        
        $json = $response->json();
        
        // Structure is data.data.order.totals based on dump
        // But previously I saw OrderController returning data => [snap_token, order_code, order => [...]]
        // And $orderData has 'totals'
        
        $data = $json['data']['order']['totals'] ?? null;
        
        $this->assertNotNull($data, 'Totals data missing from response');
        $this->assertEquals(105000, (int) $data['total_amount']);
    }

    public function test_fee_calculation_with_zero_quantity_should_fail()
    {
        // Validasi ini dihandle oleh Request validation, bukan calculation logic, tapi bagus untuk edge case
        [$variety, $lot] = $this->createVarietyAndLot(100000);
        
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [['variety_id' => $variety->id, 'quantity' => 0, 'seed_lot_id' => $lot->id]], // Qty 0
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $this->postJson('/api/orders/checkout', $payload)->assertStatus(422);
    }
}
