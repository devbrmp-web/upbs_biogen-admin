<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Mail\OrderStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    /**
     * Handle payment webhook from payment gateway
     * SKPL-WUB-PR-022: Payment Processing
     */
    public function handlePayment(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook for debugging
            Log::channel('webhooks')->info('Payment webhook received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
            ]);

            // Verify webhook signature (implement based on your payment gateway)
            if (!$this->verifyWebhookSignature($request)) {
                Log::channel('webhooks')->warning('Invalid webhook signature', [
                    'payload' => $request->all(),
                    'ip' => $request->ip(),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Extract payment data from webhook
            $paymentData = $this->extractPaymentData($request);
            
            if (!$paymentData) {
                Log::channel('webhooks')->error('Invalid payment data in webhook', [
                    'payload' => $request->all(),
                ]);
                
                return response()->json(['error' => 'Invalid payment data'], 400);
            }

            // Find the payment record
            $payment = Payment::where('gateway_transaction_id', $paymentData['transaction_id'])
                ->orWhere('gateway_reference', $paymentData['reference'])
                ->first();

            if (!$payment) {
                Log::channel('webhooks')->error('Payment not found for webhook', [
                    'transaction_id' => $paymentData['transaction_id'],
                    'reference' => $paymentData['reference'],
                ]);
                
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Prevent duplicate processing (idempotency)
            if ($payment->status === Payment::STATUS_PAID) {
                Log::channel('webhooks')->info('Payment already processed', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                ]);
                
                return response()->json(['message' => 'Payment already processed'], 200);
            }

            DB::transaction(function () use ($payment, $paymentData) {
                $payment->applyMidtransStatus($paymentData['gateway_response'] ?? []);

                if ($paymentData['status'] === 'success') {
                    $this->processSuccessfulPayment($payment, $paymentData);
                } elseif ($paymentData['status'] === 'failed') {
                    $this->processFailedPayment($payment, $paymentData);
                } elseif ($paymentData['status'] === 'expired') {
                    $this->processExpiredPayment($payment, $paymentData);
                }
            });

            Log::channel('webhooks')->info('Payment webhook processed successfully', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'status' => $paymentData['status'],
            ]);

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            Log::channel('webhooks')->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function handleMidtransNotification(Request $request): JsonResponse
    {
        $service = new \App\Services\MidtransService();
        $signature = (string) ($request->input('signature_key') ?? '');
        $params = [
            'order_id' => $request->input('order_id'),
            'status_code' => $request->input('status_code'),
            'gross_amount' => $request->input('gross_amount'),
        ];
        if (!$service->verifySignature($signature, $params)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $order = Order::query()->where('order_code', (string) $request->input('order_id'))->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $payment = Payment::query()->where('order_id', $order->id)->first();
        if (!$payment) {
            $payment = Payment::createForOrder($order, Payment::METHOD_BANK_TRANSFER);
        }

        $payment->applyMidtransStatus($request->all());

        if (in_array($request->input('transaction_status'), ['settlement','capture'])) {
            $order->markAsPaid();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(Payment $payment, array $paymentData): void
    {
        // Mark payment as paid
        $payment->markAsPaid(
            $paymentData['pnbp_receipt_no'] ?? null,
            $paymentData['gateway_response'] ?? []
        );

        $order = $payment->order;
        $previousStatus = $order->status;

        // Update order status to paid
        $order->markAsPaid($paymentData['pnbp_receipt_no'] ?? null);

        // Log the payment confirmation
        AuditLog::logUpdate(
            $order,
            ['previous_status' => $previousStatus, 'new_status' => $order->status],
            "Payment confirmed via webhook - Order status changed from {$previousStatus} to {$order->status}",
            AuditLog::CATEGORY_ORDER_MANAGEMENT
        );

        // Send payment confirmation email
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(new OrderStatusUpdate(
                    $order,
                    $previousStatus,
                    $order->status,
                    'Payment has been confirmed. Your order is now being processed.'
                ));

                Log::channel('webhooks')->info('Payment confirmation email sent', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                ]);
            } catch (\Exception $e) {
                Log::channel('webhooks')->error('Failed to send payment confirmation email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process failed payment
     */
    private function processFailedPayment(Payment $payment, array $paymentData): void
    {
        $payment->markAsFailed($paymentData['gateway_response'] ?? []);

        // Log the payment failure
        AuditLog::logUpdate(
            $payment->order,
            ['payment_status' => 'failed', 'reason' => $paymentData['failure_reason'] ?? 'Unknown'],
            "Payment failed via webhook - Transaction ID: {$paymentData['transaction_id']}",
            AuditLog::CATEGORY_ORDER_MANAGEMENT
        );
    }

    /**
     * Process expired payment
     */
    private function processExpiredPayment(Payment $payment, array $paymentData): void
    {
        $payment->markAsExpired();

        // Log the payment expiration
        AuditLog::logUpdate(
            $payment->order,
            ['payment_status' => 'expired'],
            "Payment expired via webhook - Transaction ID: {$paymentData['transaction_id']}",
            AuditLog::CATEGORY_ORDER_MANAGEMENT
        );
    }

    /**
     * Verify webhook signature for security
     * Implement based on your payment gateway's signature verification
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        // Example implementation for Midtrans
        $serverKey = config('payment.gateway_secret');
        $signatureKey = $request->header('X-Signature') ?? $request->input('signature_key');
        
        if (!$signatureKey || !$serverKey) {
            return false;
        }

        // Generate expected signature
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Extract payment data from webhook payload
     * Adapt based on your payment gateway's webhook format
     */
    private function extractPaymentData(Request $request): ?array
    {
        // Example implementation for Midtrans
        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');
        
        // Determine final status
        $status = 'pending';
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            if ($fraudStatus === 'accept' || !$fraudStatus) {
                $status = 'success';
            }
        } elseif ($transactionStatus === 'deny' || $transactionStatus === 'cancel') {
            $status = 'failed';
        } elseif ($transactionStatus === 'expire') {
            $status = 'expired';
        }

        return [
            'transaction_id' => $request->input('transaction_id'),
            'reference' => $request->input('order_id'),
            'status' => $status,
            'pnbp_receipt_no' => $request->input('pnbp_receipt_no'),
            'failure_reason' => $request->input('failure_reason'),
            'gateway_response' => $request->all(),
        ];
    }
}
