<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MidtransIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_returns_snap_token()
    {
        $this->travelTo(Carbon::parse('2025-01-01 10:00:00'));

        $variety = Variety::factory()->available()->create();
        $seedClass = \App\Models\SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);
        $seedLot = \App\Models\SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 10,
            'price_per_unit' => 10000,
            'is_sellable' => true,
        ]);
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Jl. Test',
            'customer_phone' => '081234567890',
            'customer_email' => 'tester@example.com',
            'shipping_method' => 'pickup',
            'items' => [
                ['variety_id' => $variety->id, 'seed_lot_id' => $seedLot->id, 'quantity' => 1],
            ],
            'terms_accepted' => true,
        ];

        $res = $this->postJson('/api/orders/checkout', $payload);
        $res->assertStatus(200);
        $res->assertJsonStructure(['data' => ['order_code', 'snap_token']]);

        $orderCode = (string) $res->json('data.order_code');
        $this->assertEquals('test-snap-token', $res->json('data.snap_token'));

        $order = Order::query()->where('order_code', $orderCode)->first();
        $this->assertNotNull($order);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);

        $this->assertEquals(Payment::STATUS_PENDING, $payment->status);
        $this->assertEquals('test-snap-token', $payment->snap_token);
        $this->assertEquals(Carbon::now()->addHours(25), $payment->expires_at);
        $this->assertEquals(Carbon::now()->addHours(25), $order->payment_deadline);

        $this->travelBack();
    }

    public function test_webhook_updates_payment_status()
    {
        config([
            'midtrans.server_key' => 'dummy_server_key',
            'services.midtrans.serverKey' => 'dummy_server_key',
        ]);

        $order = Order::factory()->create();
        $payment = Payment::createForOrder($order, Payment::METHOD_BANK_TRANSFER);

        $sig = hash('sha512', $order->order_code.'200'.'10000'.config('midtrans.server_key'));
        $payload = [
            'order_id' => $order->order_code,
            'status_code' => '200',
            'gross_amount' => '10000',
            'transaction_status' => 'settlement',
            'signature_key' => $sig,
        ];

        $res = $this->postJson('/api/webhooks/midtrans', $payload);
        $res->assertStatus(200);
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_PAID, $payment->status);
    }

    public function test_can_get_snap_token_for_pending_order(): void
    {
        $this->travelTo(Carbon::parse('2025-01-01 10:00:00'));

        $order = Order::factory()->awaitingPayment()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => Payment::STATUS_PENDING,
            'snap_token' => 'snap-abc',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        $res = $this->getJson('/api/orders/'.$order->order_code.'/payment/snap-token');
        $res->assertOk();
        $this->assertEquals('snap-abc', $res->json('data.snap_token'));

        $this->travelBack();
    }

    public function test_snap_token_endpoint_returns_410_when_expired(): void
    {
        $this->travelTo(Carbon::parse('2025-01-01 10:00:00'));

        $order = Order::factory()->awaitingPayment()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => Payment::STATUS_PENDING,
            'snap_token' => 'snap-expired',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $res = $this->getJson('/api/orders/'.$order->order_code.'/payment/snap-token');
        $res->assertStatus(410);

        $this->travelBack();
    }

    public function test_schedule_deletes_old_awaiting_payment_orders_and_restores_stock(): void
    {
        $this->travelTo(Carbon::parse('2025-01-01 10:00:00'));

        $variety = Variety::factory()->create();
        $seedClass = \App\Models\SeedClass::factory()->create(['code' => 'BS', 'name' => 'Breeder Seed']);
        $seedLot = \App\Models\SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $seedClass->id,
            'quantity' => 10,
            'price_per_unit' => 1000,
            'is_sellable' => true,
        ]);

        $order = Order::factory()->awaitingPayment()->create([
            'created_at' => Carbon::now()->subHours(26),
            'updated_at' => Carbon::now()->subHours(26),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'variety_id' => $variety->id,
            'variety_name' => $variety->name,
            'variety_sku' => $variety->sku,
            'unit_price' => 1000,
            'price_at_order' => 1000,
            'quantity' => 3,
            'seed_lot_id' => $seedLot->id,
            'seed_class' => 'BS',
        ]);

        $seedLot->decrement('quantity', 3);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => Payment::STATUS_PENDING,
        ]);
        
        // Ensure created_at is strictly in the past, as create() might rely on Carbon::now() which is mocked
        // But since we use travelTo, Carbon::now() is fixed. 
        // We need to ensure the DB record actually has the past timestamp.
        $order->timestamps = false;
        $order->created_at = Carbon::now()->subHours(26);
        $order->save();

        \Illuminate\Support\Facades\Cache::flush();
        Artisan::call('orders:cleanup-pending');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertEquals(10, (int) $seedLot->fresh()->quantity);

        $this->travelBack();
    }
}
