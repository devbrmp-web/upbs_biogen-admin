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

        return response()->json([
            'data' => [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'shipping_method' => $order->shipping_method,
                'totals' => [
                    'subtotal' => (int) $order->subtotal,
                    'shipping_cost' => (int) $order->shipping_cost,
                    'total_amount' => (int) $order->total_amount,
                ],
                'payment' => [
                    'method' => $order->payment?->payment_method,
                    'status' => $order->payment?->status ?? Payment::STATUS_PENDING,
                    'expires_at' => optional($order->payment?->expires_at)->toIso8601String(),
                ],
                'shipment' => [
                    'shipping_method' => $order->shipment?->shipping_method ?? $order->shipping_method,
                    'status' => $order->shipment?->status ?? Shipment::STATUS_PENDING,
                    'tracking_number' => $order->tracking_number,
                ],
                'instructions' => $order->getShippingInstructions(),
            ],
        ]);
    }

    public function track(TrackOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $trackingNumber = (string) ($validated['tracking_number'] ?? '');

        $order = Order::query()
            ->with(['shipment', 'payment', 'orderItems'])
            ->where('tracking_number', $trackingNumber)
            ->first();

        $shipment = $order?->shipment;

        if (! $order) {
            $shipment = Shipment::query()
                ->with(['order.payment', 'order.orderItems'])
                ->where('tracking_number', $trackingNumber)
                ->first();

            $order = $shipment?->order;
        }

        if (! $order) {
            return response()->json([
                'message' => 'Tracking number not found'
            ], 404);
        }

        $courierName = $shipment?->courier_name ?: $order->courier_name;
        $courierService = $shipment?->courier_service ?: $order->courier_service;

        return response()->json([
            'tracking_number' => $trackingNumber,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'shipment_status' => $shipment?->status,
            'courier' => [
                'name' => $courierName,
                'service' => $courierService,
            ],
            'timestamps' => [
                'shipped_at' => optional($order->shipped_at)->toIso8601String(),
                'delivered_at' => optional($shipment?->delivered_at)->toIso8601String(),
            ],
        ]);
    }
}
