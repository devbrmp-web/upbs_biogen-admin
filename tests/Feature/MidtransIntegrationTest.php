<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransIntegrationTest extends TestCase
{
    public function test_checkout_returns_snap_token()
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response(['token' => 'dummy-token', 'redirect_url' => 'https://sandbox.midtrans.com/snap/v2'], 200),
        ]);

        $variety = \App\Models\Variety::factory()->available()->create();
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Jl. Test',
            'customer_phone' => '081234567890',
            'customer_email' => 'tester@example.com',
            'shipping_method' => 'pickup',
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 1],
            ],
            'terms_accepted' => true,
        ];

        $res = $this->postJson('/api/orders/checkout', $payload);
        $res->assertStatus(200);
        $res->assertJsonStructure(['data' => ['order_code', 'snap_token']]);
    }

    public function test_webhook_updates_payment_status()
    {
        $order = Order::factory()->create();
        $payment = Payment::createForOrder($order, Payment::METHOD_BANK_TRANSFER);

        $sig = hash('sha512', $order->order_code . '200' . '10000' . config('midtrans.server_key'));
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

    public function test_checkout_handles_midtrans_error()
    {
        Http::fake([
            '*/snap/v1/transactions' => Http::response([], 500),
        ]);

        $variety2 = \App\Models\Variety::factory()->available()->create();
        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Jl. Test',
            'customer_phone' => '081234567890',
            'shipping_method' => 'pickup',
            'items' => [
                ['variety_id' => $variety2->id, 'quantity' => 1],
            ],
            'terms_accepted' => true,
        ];

        $res = $this->postJson('/api/orders/checkout', $payload);
        $res->assertStatus(502);
    }
}
