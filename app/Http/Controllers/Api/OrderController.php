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

            // === Midtrans ===
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
                    $order->orderItems->map(function($item) {
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

            $expiresAt = now()->addHours(25);

            $order->update([
                'payment_deadline' => $expiresAt,
            ]);

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
                'items' => $order->orderItems->map(function ($it) {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'snap_token' => $snapToken,
                    'order_code' => $order->order_code,
                    'order' => $orderData,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function createOrderItemAndDeductStock($order, $seedLot, $quantity)
    {
        $variety = $seedLot->variety;
        $unitPrice = (float) ($seedLot->price_per_unit ?? $seedLot->price ?? $variety->price);

        $itemData = [
            'order_id' => $order->id,
            'variety_id' => $variety->id,
            'variety_name' => $variety->name,
            'variety_sku' => $variety->sku,
            'unit_price' => $unitPrice,
            'price_at_order' => $unitPrice,
            'quantity' => $quantity,
            'seed_lot_id' => $seedLot->id,
            'seed_class' => $seedLot->seedClass?->code,
        ];

        OrderItem::create($itemData);

        // Decrement stock
        $seedLot->decrement('quantity', $quantity);
        
        // Audit log logic is handled by SeedLot model traits if configured, 
        // or we can explicitly log here if needed. 
        // Since we use Auditable trait on SeedLot, update events are logged.
    }

    private function prepareItemDetails($order)
    {
        // Merge identical items for clean display in Midtrans/Invoice if split occurred
        // However, Midtrans requires unique IDs usually, or just a list.
        // Let's send detailed items to be safe and accurate.
        
        $items = $order->orderItems->map(function($item) {
            return [
                'id' => $item->variety_id . '-' . $item->seed_lot_id, // Unique-ish ID
                'price' => (int) round($item->unit_price),
                'quantity' => (int) $item->quantity,
                'name' => substr($item->variety_name . ' (' . $item->seed_class . ')', 0, 50),
                'category' => optional($item->variety->commodity)->name,
            ];
        })->toArray();

        return array_merge($items, [
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
        $data = [
            'order_code' => $order->order_code,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'total_amount' => (float) $order->total_amount,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'shipping_method' => $order->shipping_method,
            'shipping_method_label' => $order->shipping_method_label,
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

        return response()->json(['data' => $data]);
    }

    public function verifyPaymentStatus(string $order_code): JsonResponse
    {
        $order = Order::query()->with('payment')->where('order_code', $order_code)->first();
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $payment = $order->payment;
        $orderId = $payment?->gateway_reference ?: $order->order_code;
        $service = new \App\Services\MidtransService;
        try {
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
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
