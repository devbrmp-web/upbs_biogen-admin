<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentSyncController extends Controller
{
    /**
     * Manual sync payment status from Midtrans using GET Status API
     * SKPL-WUB-PRO-022 (Payment Processing)
     */
    public function syncMidtransStatus(Request $request, Order $order)
    {
        $payment = $order->payment;
        if (!$payment) {
            return back()->withErrors(['payment' => 'Payment record not found for this order']);
        }

        // Use gateway_reference as merchant order_id when available; fallback to order_code
        $orderId = $payment->gateway_reference ?: $order->order_code;

        $service = new MidtransService();
        try {
            $status = $service->getStatus($orderId);

            // Apply and persist mapping
            $payment->applyMidtransStatus($status);

            // Audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => AuditLog::ACTION_UPDATE,
                'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
                'table_name' => 'payments',
                'record_id' => $payment->id,
                'model_type' => Payment::class,
                'model_id' => $payment->id,
                'old_values' => json_encode(['sync' => 'manual']),
                'new_values' => json_encode([
                    'status' => $payment->status,
                    'gateway_status' => $payment->gateway_status,
                    'fraud_status' => $payment->fraud_status,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $message = 'Payment status synced from Midtrans: ' . ucfirst($payment->status);

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message, 'payment' => $payment->fresh()])
                : back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Failed to sync Midtrans status', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return $request->expectsJson()
                ? response()->json(['success' => false, 'error' => $e->getMessage()], 500)
                : back()->withErrors(['midtrans' => $e->getMessage()]);
        }
    }
}

