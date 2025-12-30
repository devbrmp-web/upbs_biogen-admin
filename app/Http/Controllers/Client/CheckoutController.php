<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Variety;
use App\Models\SeedLot;
use App\Models\AuditLog;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Show the checkout form
     */
    public function index(Request $request)
    {
        // Get cart items from session (or implement your cart logic)
        $cartItems = Session::get('cart', []);
        
        if (empty($cartItems)) {
            return redirect()->route('client.catalog')
                ->with('warning', 'Your cart is empty. Please add items before checkout.');
        }
        
        // Calculate totals
        $subtotal = 0;
        $processedItems = [];
        
        foreach ($cartItems as $item) {
            $variety = Variety::find($item['variety_id']);
            if (!$variety) {
                continue;
            }
            
            // Get price from SeedLot (required)
            $seedLot = null;
            $unitPrice = 0;
            
            if (!empty($item['seed_lot_id'])) {
                $seedLot = SeedLot::find($item['seed_lot_id']);
                if ($seedLot) {
                    $unitPrice = (int) $seedLot->price_per_unit;
                }
            }
            
            $itemTotal = $unitPrice * $item['quantity'];
            $subtotal += $itemTotal;
            
            $processedItems[] = [
                'variety_id' => $variety->id,
                'variety_name' => $variety->name,
                'variety_sku' => $variety->sku,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'total_price' => $itemTotal,
                'seed_lot_id' => $item['seed_lot_id'] ?? null,
                'seed_lot_code' => $seedLot?->lot_code,
                'seed_class' => $seedLot?->seedClass?->code ?? ($item['seed_class'] ?? null),
            ];
        }

        // Calculate fees
        $serviceFee = round($subtotal * 0.01);
        $appFee = 1000;
        $totalAmount = $subtotal + $serviceFee + $appFee;
        
        return view('client.checkout.index', [
            'title' => 'Checkout',
            'subTitle' => 'Order Details',
            'cartItems' => $processedItems,
            'subtotal' => $subtotal,
            'serviceFee' => $serviceFee,
            'appFee' => $appFee,
            'totalAmount' => $totalAmount,
        ]);
    }

    /**
     * Process the checkout and create order
     */
    public function process(CheckoutRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Create the order with price snapshots
            $order = $this->createOrder($request);
            
            // Create order items with price snapshots
            $this->createOrderItems($order, $request->items);
            
            // Create initial payment record
            $payment = $this->createPayment($order, $request);
            
            // Create shipment record
            $shipment = $this->createShipment($order);
            
            // Calculate final totals
            $order->calculateTotals();
            
            // Log the order creation
            AuditLog::logCreate(
                $order, 
                "Guest checkout order created: {$order->order_code}",
                AuditLog::CATEGORY_ORDER_MANAGEMENT
            );
            
            DB::commit();
            
            // Send order confirmation email if customer email is provided
            if ($order->customer_email) {
                try {
                    Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                } catch (\Exception $e) {
                    // Log email error but don't fail the order creation
                    Log::error('Failed to send order confirmation email', [
                        'order_id' => $order->id,
                        'email' => $order->customer_email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Clear cart
            Session::forget('cart');
            
            // Redirect to payment or confirmation page
            return redirect()->route('client.order.confirmation', $order->order_code)
                ->with('success', 'Order placed successfully! Order Code: ' . $order->order_code);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            Log::error('Checkout process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to process your order. Please try again.');
        }
    }

    /**
     * Create order with customer information and shipping method
     */
    private function createOrder(CheckoutRequest $request): Order
    {
        return Order::create([
            'customer_name' => $request->customer_name,
            'customer_address' => $request->customer_address,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'shipping_method' => $request->shipping_method,
            'courier_name' => $request->shipping_method === 'delivery' ? $request->courier_name : null,
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'subtotal' => 0, // Will be calculated after items are created
            'shipping_cost' => 0, // Always 0 for manual shipping coordination
            'total_amount' => 0, // Will be calculated after items are created
            'payment_deadline' => now()->addHours(24), // 24 hours to complete payment
            'notes' => [
                'shipping_method_selected' => $request->shipping_method,
                'courier_selected' => $request->courier_name,
                'delivery_coordination_acknowledged' => $request->has('delivery_coordination_acknowledged'),
                'created_via' => 'guest_checkout',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);
    }

    /**
     * Create order items with price snapshots from SeedLot
     */
    private function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $variety = Variety::findOrFail($item['variety_id']);
            $seedLot = SeedLot::findOrFail($item['seed_lot_id']);
            
            // Validate stock availability from SeedLot
            if ($seedLot->quantity < $item['quantity']) {
                throw new \Exception("Insufficient stock for {$variety->name} (Lot: {$seedLot->lot_code}). Available: {$seedLot->quantity}, Requested: {$item['quantity']}");
            }
            
            // Get price from SeedLot
            $unitPrice = (int) $seedLot->price_per_unit;
            
            // Create order item with price snapshot from SeedLot
            OrderItem::create([
                'order_id' => $order->id,
                'variety_id' => $variety->id,
                'variety_name' => $variety->name,
                'variety_sku' => $variety->sku,
                'unit_price' => $unitPrice,
                'price_at_order' => $unitPrice, // Price snapshot from SeedLot
                'quantity' => $item['quantity'],
                'total_price' => $unitPrice * $item['quantity'],
                'seed_lot_id' => $seedLot->id,
                'seed_class' => $seedLot->seedClass?->code ?? ($item['seed_class'] ?? null),
            ]);
            
            // Reserve stock by reducing SeedLot quantity
            $oldQuantity = $seedLot->quantity;
            $seedLot->decrement('quantity', $item['quantity']);
            
            // Log stock reduction on SeedLot
            AuditLog::logUpdate(
                $seedLot,
                ['quantity' => $oldQuantity],
                "Stock reduced for order {$order->order_code}: -{$item['quantity']} (Lot: {$seedLot->lot_code})",
                AuditLog::CATEGORY_INVENTORY_MANAGEMENT
            );
        }
    }

    /**
     * Create initial payment record
     */
    private function createPayment(Order $order, CheckoutRequest $request): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'payment_method' => $request->payment_method ?? 'pending_selection',
            'amount' => $order->total_amount,
            'status' => Payment::STATUS_PENDING,
            'expires_at' => $order->payment_deadline,
            'payment_ip' => $request->ip(),
            'notes' => 'Payment created during checkout process',
        ]);
    }

    /**
     * Create shipment record based on shipping method
     */
    private function createShipment(Order $order): Shipment
    {
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method' => $order->shipping_method,
            'courier_name' => $order->courier_name,
            'status' => Shipment::STATUS_PENDING,
        ]);
        
        // Set appropriate initial status based on shipping method
        if ($order->is_pickup) {
            // Pickup orders start as pending until payment is confirmed
            $shipment->update(['status' => Shipment::STATUS_PENDING]);
        } else {
            // Delivery orders will need call center coordination after payment
            $shipment->update(['status' => Shipment::STATUS_PENDING]);
        }
        
        return $shipment;
    }

    /**
     * Show order confirmation page
     */
    public function confirmation(string $orderCode)
    {
        $order = Order::where('order_code', $orderCode)
            ->with(['items.variety', 'items.seedLot', 'payment', 'shipment'])
            ->firstOrFail();
        
        return view('client.checkout.confirmation', compact('order'));
    }

    /**
     * Show order tracking page (guest access)
     */
    public function track(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'order_code' => 'required|string',
                'customer_phone' => 'required|string',
            ]);
            
            $order = Order::where('order_code', $request->order_code)
                ->where('customer_phone', $request->customer_phone)
                ->with(['items.variety', 'items.seedLot', 'payment', 'shipment'])
                ->first();
            
            if (!$order) {
                return back()->with('error', 'Order not found. Please check your order code and phone number.');
            }
            
            return view('client.checkout.tracking', compact('order'));
        }
        
        return view('client.checkout.track');
    }
}