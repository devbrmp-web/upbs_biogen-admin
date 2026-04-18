<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TrackOrderRequest;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SeedLot;
use App\Models\Shipment;
use App\Models\Variety;
use App\Models\SeedClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderNotificationMail;

class OrderController extends Controller
{
    public function store(CheckoutRequest $request)
    {
        DB::beginTransaction();

        try {

            // Buat order (order_code auto by model)
            $order = Order::create([
                'customer_name' => $request->customer_name,
                'customer_address' => $request->customer_address,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'shipping_method' => $request->shipping_method,
                'courier_name' => $request->courier_name,
                'courier_service' => $request->courier_service,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'subtotal' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0,
            ]);

            $subtotal = 0;

            foreach ($request->items as $item) {

                // === Seed lot is now required ===
                $seedLot = SeedLot::with('variety', 'seedClass')->findOrFail($item['seed_lot_id']);
                $variety = $seedLot->variety;

                // Get price from seed lot
                $unitPrice = (int) $seedLot->price_per_unit;
                $seedClassCode = $seedLot->seedClass?->code;

                $quantity = (int) $item['quantity'];

                // Snapshot variety wajib → SESUAI MIGRATION
                $itemData = [
                    'order_id' => $order->id,
                    'variety_id' => $variety->id,
                    'variety_name' => $variety->name,
                    'variety_sku' => $variety->sku,
                    'unit_price' => $unitPrice,
                    'price_at_order' => $unitPrice,
                    'quantity' => $quantity,
                    'seed_lot_id' => $seedLot->id,
                    'seed_class' => $seedClassCode,
                ];

                // Create → total_price otomatis dihitung via boot() model
                $orderItem = OrderItem::create($itemData);

                // Decrement stock from SeedLot only
                $seedLot->decrement('quantity', $quantity);

                // Tambah subtotal (pakai total_price yg sudah dihitung model)
                $subtotal += $orderItem->total_price;
            }

            // === Update order total ===
            // Reload items untuk kalkulasi akurat
            $order->load('orderItems');
            $order->calculateTotals(); // Ini sudah menghitung service_fee dan app_fee

            // === Shipping Logic ===
            $totalWeight = $order->orderItems->sum('quantity');

            if ($order->shipping_method === Order::SHIPPING_PICKUP) {
                $order->update([
                    'courier_name' => 'Ambil di Tempat',
                    'courier_service' => 'BRMP Biogen',
                    'shipping_cost' => 0,
                ]);
            } else {
                // Delivery logic: > 10kg -> Indah Cargo, else Pos Indonesia
                $courierName = $totalWeight > 10 ? 'Indah Cargo' : 'Pos Indonesia';
                $order->update([
                    'courier_name' => $courierName,
                    'courier_service' => 'Regular', // Default service
                ]);
            }
            
            // Reload order agar total_amount terbaru terbaca (setelah calculateTotals)
            $order->refresh();

            // Create Shipment
            Shipment::createForOrder($order);

            // === Payment Processing ===
            $paymentMethod = config('payment.method', 'manual');
            $expiresAt = now()->addHours(24);
            $snapToken = null;

            $order->update([
                'payment_deadline' => $expiresAt,
            ]);

            if ($paymentMethod === 'midtrans') {
                // === Midtrans Mode ===
                Config::$serverKey = config('services.midtrans.serverKey');
                Config::$isProduction = config('services.midtrans.isProduction');
                Config::$isSanitized = config('services.midtrans.isSanitized');
                Config::$is3ds = config('services.midtrans.is3ds');

                $payload = [
                    'transaction_details' => [
                        'order_id' => $order->order_code,
                        'gross_amount' => (int) round($order->total_amount),
                    ],
                    'customer_details' => [
                        'first_name' => $order->customer_name,
                        'email' => $order->customer_email,
                        'phone' => $order->customer_phone,
                    ],
                    'item_details' => array_merge(
                        $order->items->map(function($item) {
                            return [
                                'id' => $item->variety_id,
                                'price' => (int) round($item->unit_price),
                                'quantity' => (int) $item->quantity,
                                'name' => substr($item->variety_name, 0, 50),
                                'category' => optional($item->variety->commodity)->name,
                            ];
                        })->toArray(),
                        [
                            [
                                'id' => 'SERVICE-FEE',
                                'price' => (int) $order->service_fee,
                                'quantity' => 1,
                                'name' => 'Biaya Layanan (1%)',
                            ],
                            [
                                'id' => 'APP-FEE',
                                'price' => (int) $order->app_fee,
                                'quantity' => 1,
                                'name' => 'Biaya Aplikasi',
                            ]
                        ]
                    ),
                ];

                $snapToken = app()->environment('testing')
                    ? 'test-snap-token'
                    : Snap::getSnapToken($payload);

                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'payment_method' => Payment::METHOD_BANK_TRANSFER,
                        'amount' => $order->total_amount,
                        'status' => Payment::STATUS_PENDING,
                        'gateway_reference' => $order->order_code,
                        'snap_token' => $snapToken,
                        'expires_at' => $expiresAt,
                        'payment_ip' => $request->ip(),
                    ]
                );
            } else {
                // === Manual Transfer Mode ===
                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'payment_method' => Payment::METHOD_BANK_TRANSFER,
                        'amount' => $order->total_amount,
                        'status' => Payment::STATUS_PENDING,
                        'gateway_reference' => $order->order_code,
                        'expires_at' => $expiresAt,
                        'payment_ip' => $request->ip(),
                    ]
                );
            }

            // Ambil data lengkap pesanan (termasuk item)
            $orderData = [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'shipping_method' => $order->shipping_method,
                'totals' => [
                    'subtotal' => (float) $order->subtotal,
                    'shipping_cost' => (float) $order->shipping_cost,
                    'service_fee' => (float) $order->service_fee,
                    'app_fee' => (float) $order->app_fee,
                    'total_amount' => (float) $order->total_amount,
                ],
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_address' => $order->customer_address,
                'courier_name' => $order->courier_name,
                'courier_service' => $order->courier_service,
                'payment_deadline' => $expiresAt->toIso8601String(),
                'items' => $order->items->map(function ($it) {
                    return [
                        'variety_id' => $it->variety_id,
                        'name' => $it->variety_name,
                        'quantity' => (int) $it->quantity,
                        'unit_price' => (float) $it->unit_price,
                        'seed_lot_id' => $it->seed_lot_id,
                        'seed_class_code' => $it->seed_class,
                    ];
                })->toArray(),
            ];

            DB::commit();

            // Send order confirmation email if customer email is provided
            if ($order->customer_email) {
                $recipient = trim($order->customer_email);
                try {
                    Log::info('[EMAIL-DEBUG-API] Triggering email for Order: ' . $order->order_code . ' to: ' . $recipient . ' | Type: awaiting_payment');
                    Mail::to($recipient)->send(new OrderNotificationMail($order, 'awaiting_payment'));
                    Log::info('[EMAIL-DEBUG-API] Email sent SUCCESSFULLY for Order: ' . $order->order_code);
                } catch (\Exception $e) {
                    // Log email error but don't fail the order creation response
                    Log::error('[EMAIL-DEBUG-API] Failed to send order confirmation email', [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                        'email' => $order->customer_email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Return response based on payment method
            $responseData = [
                'order_code' => $order->order_code,
                'order' => $orderData,
                'payment_method' => $paymentMethod,
            ];

            if ($paymentMethod === 'midtrans') {
                $responseData['snap_token'] = $snapToken;
            } else {
                $responseData['banks'] = config('payment.banks', []);
                $responseData['instructions'] = config('payment.instructions', []);
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm payment with proof upload (Manual Transfer)
     */
    public function confirmPayment(Request $request, string $code): JsonResponse
    {
        try {
            $order = Order::with('payment')->where('order_code', $code)->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            if (!in_array($order->status, [Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PENDING_VERIFICATION])) {
                return response()->json(['success' => false, 'message' => 'Order sudah dibayar atau tidak dapat dikonfirmasi'], 409);
            }

            $request->validate([
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            ]);

            $file = $request->file('payment_proof');
            $filename = $order->order_code . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/payment_proofs', $filename);

            $payment = $order->payment;
            if (!$payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'amount' => $order->total_amount,
                    'status' => Payment::STATUS_PENDING,
                ]);
            }

            $payment->update([
                'payment_proof_path' => str_replace('public/', 'storage/', $path),
                'proof_uploaded_at' => now(),
            ]);

            // Update order status to pending verification
            $order->markAsPendingVerification();

            \Log::info('Payment proof uploaded successfully', [
                'order_code' => $order->order_code,
                'new_status' => $order->fresh()->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
                'data' => [
                    'order_code' => $order->order_code,
                    'status' => $order->fresh()->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Payment proof upload failed', [
                'order_code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengunggah bukti pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get payment info for manual transfer
     */
    public function getPaymentInfo(string $code): JsonResponse
    {
        $order = Order::with(['items', 'payment'])->where('order_code', $code)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'total_amount' => (int) round($order->total_amount),
                'payment_deadline' => $order->payment_deadline?->toIso8601String(),
                'customer_name' => $order->customer_name,
                'banks' => config('payment.banks', []),
                'instructions' => config('payment.instructions', []),
                'has_proof' => $order->payment?->payment_proof_path !== null,
            ],
        ]);
    }

    // ... existing methods ...
    public function track(TrackOrderRequest $request): JsonResponse

    {
        $validated = $request->validated();
        $trackingNumber = (string) ($validated['tracking_number'] ?? '');
        $orderCode = (string) ($validated['order_code'] ?? '');
        $phone = (string) ($validated['phone'] ?? '');

        $query = Order::query()->with(['shipment', 'payment', 'orderItems']);
        $order = null;
        $shipment = null;

        if ($trackingNumber !== '') {
            $order = $query->where('tracking_number', $trackingNumber)->first();
            $shipment = $order?->shipment;
            if (! $order) {
                $shipment = Shipment::query()
                    ->with(['order.payment', 'order.orderItems'])
                    ->where('tracking_number', $trackingNumber)
                    ->first();
                $order = $shipment?->order;
            }
        } elseif ($orderCode !== '') {
            $order = $query->where('order_code', $orderCode)->first();
            $shipment = $order?->shipment;
        } elseif ($phone !== '') {
            $order = $query->where('customer_phone', $phone)->orderByDesc('created_at')->first();
            $shipment = $order?->shipment;
        }

        if (! $order) {
            return response()->json([
                'message' => $trackingNumber !== '' ? 'Tracking number not found' : 'Order not found',
            ], 404);
        }

        $payloadOrder = [
            'order_code' => $order->order_code,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'total_amount' => (float) $order->total_amount,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'shipping_method' => $order->shipping_method,
            'shipping_method_label' => $order->shipping_method_label,
            'customer_address' => $order->customer_address,
            'courier_name' => $shipment?->courier_name ?: $order->courier_name,
            'courier_service' => $shipment?->courier_service ?: $order->courier_service,
            'shipment_status' => $shipment?->status,
            'tracking_number' => $shipment?->tracking_number ?: $order->tracking_number,
            'signature_path' => $order->signature_path,
            'items' => $order->orderItems->map(function ($it) {
                return [
                    'variety_id' => $it->variety_id,
                    'name' => $it->name,
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'seed_lot_id' => $it->seed_lot_id,
                    'seed_class_code' => $it->seed_class_code,
                ];
            })->toArray(),
        ];

        return response()->json(['data' => $payloadOrder]);
    }

    public function getPublicOrder(string $order_code): JsonResponse
    {
        $order = Order::query()->with(['shipment', 'payment', 'orderItems'])->where('order_code', $order_code)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $shipment = $order->shipment;
        $payment = $order->payment;
        
        $data = [
            'order_code' => $order->order_code,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'total_amount' => (float) $order->total_amount,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'customer_email' => $order->customer_email,
            'shipping_method' => $order->shipping_method,
            'shipping_method_label' => $order->shipping_method_label,
            'courier_name' => $shipment?->courier_name ?: $order->courier_name,
            'courier_service' => $shipment?->courier_service ?: $order->courier_service,
            'shipment_status' => $shipment?->status,
            'tracking_number' => $shipment?->tracking_number ?: $order->tracking_number,
            'signature_path' => $order->signature_path,
            // Payment info addition
            'payment_type' => $order->payment_type,
            'transaction_id' => $order->transaction_id,
            'transaction_status' => $order->transaction_status,
            'paid_at' => $order->paid_at,
            'settlement_time' => $order->settlement_time,
            'payment' => $payment ? [
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'transaction_id' => $payment->transaction_id,
                'gateway_reference' => $payment->gateway_reference,
                'pnbp_receipt_no' => $payment->pnbp_receipt_no,
            ] : null,
            'items' => $order->orderItems->map(function ($it) {
                return [
                    'variety_id' => $it->variety_id,
                    'name' => $it->name,
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'seed_lot_id' => $it->seed_lot_id,
                    'seed_class_code' => $it->seed_class_code,
                    'resolved_variety_name' => $it->variety_name // Ensure variety name is available
                ];
            })->toArray(),
        ];

        return response()->json(['data' => $data]);
    }

    public function verifyPaymentStatus(string $order_code): JsonResponse
    {
        $order = Order::query()->with('payment')->where('order_code', $order_code)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $payment = $order->payment;
        
        // Skip Midtrans check for manual payments or if paid/verified
        if ($payment && ($payment->payment_method === 'bank_transfer' || $payment->status === 'paid')) {
             return response()->json(['success' => true, 'order' => $order->fresh(['payment'])]);
        }

        $orderId = $payment?->gateway_reference ?: $order->order_code;
        $service = new \App\Services\MidtransService;
        try {
            // Only call Midtrans if we have a valid midtrans-like orderId or no payment record yet
            // Assuming manual order codes start with WUB... wait, all do.
            // Check config if midtrans is enabled for this order?
            // Safer: only call if payment method is NOT manual/bank_transfer
            if ($payment && $payment->payment_method === 'bank_transfer') {
                 // Should be caught by above check, but double safety
                 return response()->json(['success' => true, 'order' => $order->fresh(['payment'])]);
            }

            $status = $service->getStatus($orderId);
            if ($payment) {
                $payment->applyMidtransStatus($status);
            } else {
                $mapped = match ($status['transaction_status'] ?? null) {
                    'settlement', 'capture' => Payment::STATUS_PAID,
                    'pending' => Payment::STATUS_PENDING,
                    'expire' => Payment::STATUS_EXPIRED,
                    'deny', 'cancel' => Payment::STATUS_FAILED,
                    default => null,
                };
                $order->payment_type = $status['payment_type'] ?? $order->payment_type;
                $order->transaction_id = $status['transaction_id'] ?? $order->transaction_id;
                $order->transaction_status = $status['transaction_status'] ?? $order->transaction_status;
                $order->settlement_time = isset($status['settlement_time']) ? \Carbon\Carbon::parse($status['settlement_time']) : $order->settlement_time;
                $order->gross_amount = isset($status['gross_amount']) ? (float) $status['gross_amount'] : $order->gross_amount;
                if ($mapped === Payment::STATUS_PAID) {
                    $order->markAsPaid();
                } else {
                    $order->save();
                }
            }

            return response()->json(['success' => true, 'order' => $order->fresh(['payment'])]);
        } catch (\Throwable $e) {
            // If Midtrans fails (e.g. order not found there), just return local order data
            // This acts as fallback for any sync issues
            return response()->json(['success' => true, 'order' => $order->fresh(['payment'])]); 
        }
    }

    public function getSnapToken(string $order_code): JsonResponse
    {
        $order = Order::query()->with('payment')->where('order_code', $order_code)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $payment = $order->payment;
        if (! $payment) {
            return response()->json(['message' => 'Payment record not found'], 404);
        }

        if ($order->status !== Order::STATUS_AWAITING_PAYMENT || $payment->status !== Payment::STATUS_PENDING) {
            return response()->json(['message' => 'Payment is not pending'], 409);
        }

        if ($payment->expires_at && $payment->expires_at->isPast()) {
            return response()->json(['message' => 'Payment expired'], 410);
        }

        if (! $payment->snap_token) {
            return response()->json(['message' => 'Snap token not available'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->order_code,
                'snap_token' => $payment->snap_token,
                'expires_at' => $payment->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function syncPaymentByOrderId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:191'],
        ]);

        $orderId = (string) $validated['order_id'];

        $order = Order::query()->with('payment')->where('order_code', $orderId)->first();
        if (! $order) {
            $payment = Payment::query()->with('order.payment')->where('gateway_reference', $orderId)->first();
            $order = $payment?->order;
        }

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $payment = $order->payment;
        $orderIdForMidtrans = $payment?->gateway_reference ?: $orderId;

        $service = new \App\Services\MidtransService;

        try {
            $status = $service->getStatus($orderIdForMidtrans);

            if ($payment) {
                $payment->applyMidtransStatus($status);
            } else {
                $mapped = match ($status['transaction_status'] ?? null) {
                    'settlement', 'capture' => Payment::STATUS_PAID,
                    'pending' => Payment::STATUS_PENDING,
                    'expire' => Payment::STATUS_EXPIRED,
                    'deny', 'cancel' => Payment::STATUS_FAILED,
                    default => null,
                };

                $order->payment_type = $status['payment_type'] ?? $order->payment_type;
                $order->transaction_id = $status['transaction_id'] ?? $order->transaction_id;
                $order->transaction_status = $status['transaction_status'] ?? $order->transaction_status;
                $order->settlement_time = isset($status['settlement_time']) ? \Carbon\Carbon::parse($status['settlement_time']) : $order->settlement_time;
                $order->gross_amount = isset($status['gross_amount']) ? (float) $status['gross_amount'] : $order->gross_amount;

                if ($mapped === Payment::STATUS_PAID) {
                    $order->markAsPaid();
                } else {
                    $order->save();
                }
            }

            return response()->json([
                'success' => true,
                'order' => $order->fresh(['payment']),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSignature(Request $request, string $order_code): JsonResponse
    {
        $validated = $request->validate([
            'signature_path' => 'required|string',
        ]);

        $order = Order::where('order_code', $order_code)->firstOrFail();
        $order->signature_path = $validated['signature_path'];
        $order->save();

        return response()->json(['success' => true, 'message' => 'Signature updated']);
    }
}
