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
use Illuminate\Support\Str;
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
                'customer_name'     => $request->customer_name,
                'customer_address'  => $request->customer_address,
                'customer_phone'    => $request->customer_phone,
                'customer_email'    => $request->customer_email,
                'shipping_method'   => $request->shipping_method,
                'courier_name'      => $request->courier_name,
                'courier_service'   => $request->courier_service,
                'status'            => Order::STATUS_AWAITING_PAYMENT,
                'subtotal'          => 0,
                'shipping_cost'     => 0,
                'total_amount'      => 0,
            ]);

            $subtotal = 0;

            foreach ($request->items as $item) {

                // === Ambil data produk ===
                if (!empty($item['seed_lot_id'])) {

                    $seedLot = SeedLot::with('variety', 'seedClass')->findOrFail($item['seed_lot_id']);
                    $variety = $seedLot->variety;

                    // Jika seed lot punya price_per_unit → pakai
                    $unitPrice = (float) ($seedLot->price_per_unit ?? $seedLot->price ?? $variety->price);

                    $seedClassCode = $seedLot->seedClass?->code;

                } else {

                    $variety = Variety::findOrFail($item['variety_id']);
                    $unitPrice = (float) $variety->price;
                    $seedLot = null;
                    $seedClassCode = null;
                }

                $quantity = (int) $item['quantity'];

                // Snapshot variety wajib → SESUAI MIGRATION
                $itemData = [
                    'order_id'       => $order->id,
                    'variety_id'     => $variety->id,
                    'variety_name'   => $variety->name,
                    'variety_sku'    => $variety->sku,
                    'unit_price'     => $unitPrice,
                    'price_at_order' => $unitPrice,
                    'quantity'       => $quantity,
                    'seed_lot_id'    => $seedLot?->id,
                    'seed_class'     => $seedClassCode,
                ];

                // Create → total_price otomatis dihitung via boot() model
                $orderItem = OrderItem::create($itemData);

                // Tambah subtotal (pakai total_price yg sudah dihitung model)
                $subtotal += $orderItem->total_price;
            }

            // === Update order total ===
            $order->update([
                'subtotal'     => $subtotal,
                'total_amount' => $subtotal, // pickup → shipping_cost = 0
            ]);

            // === Midtrans ===
            Config::$serverKey     = config('services.midtrans.serverKey');
            Config::$isProduction  = config('services.midtrans.isProduction');
            Config::$isSanitized   = config('services.midtrans.isSanitized');
            Config::$is3ds         = config('services.midtrans.is3ds');

            $payload = [
                'transaction_details' => [
                    'order_id'     => $order->order_code,
                    'gross_amount' => (int) $subtotal,
                ],
                'customer_details' => [
                    'first_name' => $order->customer_name,
                    'email'      => $order->customer_email,
                    'phone'      => $order->customer_phone,
                ],
            ];

            $snapToken = Snap::getSnapToken($payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'snap_token' => $snapToken,
                    'order_code' => $order->order_code,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
