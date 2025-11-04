<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Mail\OrderStatusUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    private Order $order;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Set payment configuration for testing
        config(['payment.gateway_secret' => 'test_secret_key']);

        // Create minimal test data without complex relationships
        $this->order = Order::factory()->create([
            'customer_email' => 'customer@test.com',
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]);

        $this->payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'gateway_transaction_id' => 'TXN123456789',
            'gateway_reference' => 'REF123456789',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function it_processes_successful_payment_webhook()
    {
        $webhookData = [
            'transaction_id' => 'TXN123456789',
            'order_id' => 'REF123456789',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => $this->generateValidSignature('REF123456789', '200', '100000.00'),
        ];

        $response = $this->postJson(route('webhook.payment'), $webhookData, [
            'X-Signature' => $webhookData['signature_key'],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_processes_failed_payment_webhook()
    {
        $webhookData = [
            'transaction_id' => 'TXN123456789',
            'order_id' => 'REF123456789',
            'transaction_status' => 'deny',
            'status_code' => '400',
            'gross_amount' => '100000.00',
            'signature_key' => $this->generateValidSignature('REF123456789', '400', '100000.00'),
        ];

        $response = $this->postJson(route('webhook.payment'), $webhookData, [
            'X-Signature' => $webhookData['signature_key'],
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        $webhookData = [
            'transaction_id' => 'TXN123456789',
            'order_id' => 'REF123456789',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'invalid_signature',
        ];

        $response = $this->postJson(route('webhook.payment'), $webhookData, [
            'X-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_processes_webhook_successfully()
    {
        Mail::fake();

        $webhookData = [
            'transaction_id' => 'TXN123456789',
            'order_id' => 'REF123456789',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'pnbp_receipt_no' => 'PNBP-2024-001',
            'signature_key' => $this->generateValidSignature('REF123456789', '200', '100000.00'),
        ];

        $response = $this->postJson(route('webhook.payment'), $webhookData, [
            'X-Signature' => $webhookData['signature_key'],
        ]);

        $response->assertStatus(200);

        // Assert payment was updated
        $this->payment->refresh();
        $this->assertEquals(Payment::STATUS_PAID, $this->payment->status);
    }

    /**
     * Generate valid signature for testing
     */
    private function generateValidSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        $serverKey = config('payment.gateway_secret', 'test_secret_key');
        return hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
    }
}