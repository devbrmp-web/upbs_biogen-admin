<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure config available
        config(['payment.midtrans.server_key' => 'dummy_server_key']);
    }

    /** @test */
    public function admin_can_sync_payment_status_from_midtrans_via_get()
    {
        // Create admin user and login
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Create order + payment
        $order = Order::factory()->create([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'total_amount' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100000,
            'status' => Payment::STATUS_PENDING,
            'gateway_reference' => 'REF-ORDER-001',
        ]);

        // Fake Midtrans response
        Http::fake([
            'https://api.sandbox.midtrans.com/*' => Http::response([
                'order_id' => 'REF-ORDER-001',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '100000.00',
            ], 200),
        ]);

        // Hit manual sync endpoint
        $response = $this->get(route('admin.orders.payments.sync-midtrans', ['order' => $order->id]));

        $response->assertStatus(302); // redirect back with success

        // Assert payment and order updated
        $payment->refresh();
        $order->refresh();

        $this->assertEquals(Payment::STATUS_PAID, $payment->status);
        $this->assertEquals('settlement', $payment->gateway_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
    }
}

