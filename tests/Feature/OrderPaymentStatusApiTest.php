<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderPaymentStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payment.midtrans.server_key' => 'dummy_server_key']);
    }

    public function test_verify_payment_status_updates_order_fields(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'total_amount' => 50000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 50000,
            'status' => Payment::STATUS_PENDING,
            'gateway_reference' => $order->order_code,
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/*' => Http::response([
                'order_id' => $order->order_code,
                'transaction_id' => 'tx-12345',
                'payment_type' => 'bank_transfer',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '50000.00',
                'settlement_time' => now()->format('Y-m-d H:i:s'),
            ], 200),
        ]);

        $resp = $this->getJson(route('api.orders.payment.status', ['order_code' => $order->order_code]));
        $resp->assertStatus(200);

        $order->refresh();
        $this->assertEquals(Order::STATUS_PAID, $order->status);
        $this->assertEquals('bank_transfer', $order->payment_type);
        $this->assertEquals('tx-12345', $order->transaction_id);
        $this->assertEquals('settlement', $order->transaction_status);
        $this->assertNotNull($order->settlement_time);
        $this->assertEquals(50000.00, (float) $order->gross_amount);
    }
}

