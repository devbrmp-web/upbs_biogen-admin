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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $order = Order::query()->create([
                'customer_name' => $validated['customer_name'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_method' => $validated['shipping_method'],
                'courier_name' => $validated['shipping_method'] === Order::SHIPPING_DELIVERY
                    ? ($validated['courier_name'] ?? null)
                    : null,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'shipping_cost' => 0,
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $variety = Variety::query()->findOrFail($item['variety_id']);
                $quantity = (int) $item['quantity'];
                $seedLot = null;
                if (!empty($item['seed_lot_id'])) {
                    // Lock the seed lot row to avoid race conditions during stock decrement
                    $seedLot = SeedLot::query()->lockForUpdate()->findOrFail($item['seed_lot_id']);

                    if (!$seedLot->is_sellable) {
                        throw new \RuntimeException('Selected seed lot is not sellable.');
                    }
                    if ($seedLot->quantity < $quantity) {
                        throw new \RuntimeException('Insufficient seed lot stock for checkout.');
                    }

                    // Decrement seed lot stock immediately as reservation
                    $seedLot->decrement('quantity', $quantity);
                } else {
                    // Fallback: validate variety stock and decrement if using variety-based stock
                    if ($variety->stock < $quantity) {
                        throw new \RuntimeException('Insufficient stock for checkout.');
                    }
                    $variety->decrement('stock', $quantity);
                }

                OrderItem::createFromVariety(
                    $order,
                    $variety,
                    $quantity,
                    $seedLot
                );
            }

            $order->load('items');
            $order->calculateTotals();

            $serviceFee = round($order->subtotal * 0.01, 2);
            $order->update(['total_amount' => $order->subtotal + $serviceFee]);

            // Auto-select courier based on total weight for delivery orders
            if ($order->shipping_method === Order::SHIPPING_DELIVERY) {
                $totalWeightKg = (int) $order->items->sum('quantity');
                $courier = $totalWeightKg > 10
                    ? Shipment::COURIER_INDAH_CARGO
                    : Shipment::COURIER_POS_INDONESIA;
                $order->update(['courier_name' => $courier]);
            }

            Shipment::createForOrder($order);

            $paymentMethod = $validated['payment_method'] ?? Payment::METHOD_BANK_TRANSFER;
            Payment::createForOrder($order, $paymentMethod);

            return $order->fresh(['items', 'payment', 'shipment']);
        });

        try {
            $service = new \App\Services\MidtransService();
            $snap = $service->createSnapToken($order);
            $payment = $order->payment;
            if ($payment) {
                $payment->gateway_reference = $order->order_code;
                $payment->gateway_status = 'pending';
                $payment->gateway_response = array_merge($payment->gateway_response ?? [], $snap);
                $payment->snap_token = $snap['token'] ?? null;
                $payment->redirect_url = $snap['redirect_url'] ?? null;
                $payment->save();
            }

            return response()->json([
                'data' => [
                    'order_code' => $order->order_code,
                    'snap_token' => $snap['token'] ?? null,
                    'payment_url' => $snap['redirect_url'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            // Rollback reserved stock
            try {
                DB::transaction(function() use ($order) {
                    foreach ($order->items as $it) {
                        if ($it->seed_lot_id) {
                            $lot = \App\Models\SeedLot::query()->lockForUpdate()->find($it->seed_lot_id);
                            if ($lot) { $lot->increment('quantity', $it->quantity); }
                        } else {
                            $var = \App\Models\Variety::query()->lockForUpdate()->find($it->variety_id);
                            if ($var) { $var->increment('stock', $it->quantity); }
                        }
                    }
                });
            } catch (\Throwable $re) {}
            return response()->json([
                'message' => 'Failed to initialize payment',
            ], 502);
        }
    }

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
            if (!$order) {
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
                'message' => 'Order not found'
            ], 404);
        }

        $payloadOrder = [
            'order_code' => $order->order_code,
            'status' => $order->status,
            'subtotal' => (float) $order->subtotal,
            'total_amount' => (float) $order->total_amount,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'courier_name' => $shipment?->courier_name ?: $order->courier_name,
            'courier_service' => $shipment?->courier_service ?: $order->courier_service,
            'shipment_status' => $shipment?->status,
            'tracking_number' => $shipment?->tracking_number ?: $order->tracking_number,
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

        return response()->json([
            'order' => $payloadOrder,
        ]);
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
            'courier_name' => $shipment?->courier_name ?: $order->courier_name,
            'courier_service' => $shipment?->courier_service ?: $order->courier_service,
            'shipment_status' => $shipment?->status,
            'tracking_number' => $shipment?->tracking_number ?: $order->tracking_number,
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
        $service = new \App\Services\MidtransService();
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
}
