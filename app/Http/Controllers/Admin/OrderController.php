<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AuditLog;
use App\Mail\OrderStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = Order::with(['orderItems.variety', 'payment', 'shipment']);

        // Sorting (whitelist)
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'customer_name', 'status', 'total_amount', 'order_code'];
        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Filter by shipping method
        if ($request->filled('shipping_method')) {
            $query->where('shipping_method', $request->shipping_method);
        }

        // Filter by order status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order code or customer name/phone
        $search = $request->input('q', $request->input('search'));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        // Get filter options for dropdowns
        $shippingMethods = [
            'pickup' => 'Pickup at BRMP',
            'delivery' => 'Delivery'
        ];

        $statusOptions = [
            Order::STATUS_AWAITING_PAYMENT => 'Awaiting Payment',
            Order::STATUS_PENDING_VERIFICATION => 'Pending Verification',
            Order::STATUS_PAID => 'Paid',
            Order::STATUS_PROCESSING => 'Processing',
            Order::STATUS_PICKUP_READY => 'Ready for Pickup',
            Order::STATUS_COMPLETED => 'Completed',
            Order::STATUS_CANCELLED => 'Cancelled'
        ];

        if ($request->ajax()) {
            return view('admin.orders.partials.table-content', compact('orders'));
        }

        return view('admin.orders.index', compact('orders', 'shippingMethods', 'statusOptions'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $order->load(['orderItems.variety', 'payment', 'shipment']);
        
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update the order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', [
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_PICKUP_READY,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED
            ]),
            'notes' => 'nullable|string|max:500'
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Validate status transition
        if (!$order->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition from ' . $order->getStatusLabel() . ' to ' . $newStatus
            ]);
        }

        // Update order status
        $order->update([
            'status' => $newStatus,
            'notes' => $request->notes
        ]);

        // ============================================
        // SYNC PAYMENT RECORD FOR MANUAL VERIFICATION
        // When transitioning from pending_verification to paid,
        // ensure payment record reflects the verified state
        // ============================================
        if ($newStatus === Order::STATUS_PAID && 
            in_array($oldStatus, [Order::STATUS_PENDING_VERIFICATION, Order::STATUS_AWAITING_PAYMENT])) {
            
            $payment = $order->payment;
            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $payment->payment_method ?: 'bank_transfer',
                    // Use order_code as transaction reference for manual payments
                    'gateway_reference' => $payment->gateway_reference ?: $order->order_code,
                    'transaction_id' => $payment->transaction_id ?: $order->order_code,
                ]);
            }

            // Also update order paid_at if not set
            if (!$order->paid_at) {
                $order->update(['paid_at' => now()]);
            }
        }

        // ============================================
        // STOCK RESTORATION FOR CANCELLED ORDERS
        // Restore seed lot quantities when order is cancelled
        // ============================================
        if ($newStatus === Order::STATUS_CANCELLED && $oldStatus !== Order::STATUS_CANCELLED) {
            $order->load('orderItems.seedLot');
            foreach ($order->orderItems as $item) {
                if ($item->seedLot) {
                    $item->seedLot->increment('quantity', $item->quantity);
                }
            }
        }

        // Create audit log
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_UPDATE,
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
            'table_name' => 'orders',
            'record_id' => $order->id,
            'old_data' => ['status' => $oldStatus],
            'new_data' => ['status' => $newStatus, 'notes' => $request->notes],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Send email notification if customer email is available
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(
                    new OrderStatusUpdate($order, $oldStatus, $newStatus, $request->notes)
                );
            } catch (\Exception $e) {
                // Log email error but don't fail the status update
                \Log::error('Failed to send order status update email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return back()->with('success', 'Order status updated successfully.');
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        if (!$order->canTransitionTo(Order::STATUS_CANCELLED)) {
            return response()->json([
                'error' => 'Cannot cancel order in current status: ' . $order->getStatusLabel()
            ], 403);
        }

        $oldStatus = $order->status;

        // Transactional cancel + stock restore
        DB::transaction(function () use ($order, $request) {
            // Update order status to cancelled
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'notes' => 'Cancelled: ' . $request->cancellation_reason
            ]);

            // Restore stock for cancelled orders
            foreach ($order->orderItems as $item) {
                if ($item->seedLot) {
                    $item->seedLot->increment('quantity', $item->quantity);
                }
            }
        });

        // Create audit log
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => AuditLog::ACTION_UPDATE,
            'table_name' => 'orders',
            'record_id' => $order->id,
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
            'old_data' => ['status' => $oldStatus],
            'new_data' => [
                'status' => Order::STATUS_CANCELLED,
                'notes' => 'Cancelled: ' . $request->cancellation_reason
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Send cancellation email notification if customer email is available
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(
                    new OrderStatusUpdate($order, $oldStatus, Order::STATUS_CANCELLED, 'Cancelled: ' . $request->cancellation_reason)
                );
            } catch (\Exception $e) {
                // Log email error but don't fail the cancellation
                \Log::error('Failed to send order cancellation email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return back()->with('success', 'Order cancelled successfully and stock restored.');
    }

    /**
     * Bulk cancel selected orders via AJAX.
     */
    public function bulkCancel(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with(['orderItems.seedLot'])->whereIn('id', $validated['ids'])->get();
        $result = DB::transaction(function () use ($orders, $request) {
            $cancelled = [];
            $failed = [];

            foreach ($orders as $order) {
                if (!$order->canTransitionTo(Order::STATUS_CANCELLED)) {
                    $failed[] = [
                        'id' => $order->id,
                        'order_code' => $order->order_code,
                        'reason' => 'Invalid status (' . $order->status . ')',
                    ];
                    continue;
                }

                $oldStatus = $order->status;
                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'notes' => 'Cancelled via bulk action',
                ]);

                foreach ($order->orderItems as $item) {
                    if ($item->seedLot) {
                        $item->seedLot->increment('quantity', $item->quantity);
                    }
                }

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'UPDATE',
                    'table_name' => 'orders',
                    'record_id' => $order->id,
                    'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
                    'old_data' => ['status' => $oldStatus],
                    'new_data' => ['status' => Order::STATUS_CANCELLED, 'notes' => 'Cancelled via bulk action'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $cancelled[] = [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                ];
            }

            return compact('cancelled', 'failed');
        });

        return response()->json([
            'success' => true,
            'cancelled' => $result['cancelled'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * Remove the specified order from storage.
     * Only cancelled orders can be deleted.
     */
    public function destroy(Request $request, Order $order)
    {
        $request->validate([
            'deletion_reason' => 'required|string|max:500'
        ]);

        // Only allow deletion of cancelled orders
        if ($order->status !== Order::STATUS_CANCELLED) {
            abort(403, 'Only cancelled orders can be deleted.');
        }

        // Create audit log before deletion
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'DELETE',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
            'old_data' => array_merge($order->toArray(), [
                'deletion_reason' => $request->deletion_reason
            ]),
            'new_data' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Delete the order (cascade will handle order_items)
        $order->delete();

        return back()->with('success', 'Order deleted successfully.');
    }

    /**
     * Bulk update status for multiple orders
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:orders,id',
            'status' => 'required|in:' . implode(',', [
                Order::STATUS_AWAITING_PAYMENT,
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_PICKUP_READY,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED
            ]),
            'notes' => 'nullable|string|max:500'
        ]);

        $updatedCount = 0;
        $errors = [];

        DB::transaction(function () use ($request, &$updatedCount, &$errors) {
            $orders = Order::whereIn('id', $request->ids)->get();

            foreach ($orders as $order) {
                $oldStatus = $order->status;
                
                // Check if status transition is valid
                if (!$this->canTransitionToStatus($order, $request->status)) {
                    $errors[] = "Order {$order->order_code} cannot transition from {$oldStatus} to {$request->status}";
                    continue;
                }

                $order->update(['status' => $request->status]);

                // ============================================
                // STOCK RESTORATION FOR CANCELLED ORDERS  
                // ============================================
                if ($request->status === Order::STATUS_CANCELLED && $oldStatus !== Order::STATUS_CANCELLED) {
                    $order->load('orderItems.seedLot');
                    foreach ($order->orderItems as $item) {
                        if ($item->seedLot) {
                            $item->seedLot->increment('quantity', $item->quantity);
                        }
                    }
                }

                // Create audit log
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'UPDATE',
                    'table_name' => 'orders',
                    'record_id' => $order->id,
                    'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
                    'old_data' => ['status' => $oldStatus],
                    'new_data' => [
                        'status' => $request->status,
                        'notes' => $request->notes
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);

                // Send email notification if customer has email
                if ($order->customer_email) {
                    try {
                        Mail::to($order->customer_email)->send(new OrderStatusUpdate(
                            $order,
                            $oldStatus,
                            $request->status,
                            $request->notes
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send status update email', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $updatedCount++;
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'updated_count' => $updatedCount,
                'errors' => $errors,
                'message' => $updatedCount > 0 ? 
                    "{$updatedCount} orders updated successfully." : 
                    'No orders were updated.'
            ]);
        }

        $message = $updatedCount > 0 ? 
            "{$updatedCount} orders updated successfully." : 
            'No orders were updated.';
            
        if (!empty($errors)) {
            $message .= ' Some orders could not be updated: ' . implode(', ', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Export selected orders to CSV
     */
    public function export(Request $request)
    {
        $request->validate([
            'selected_orders' => 'required|array|min:1',
            'selected_orders.*' => 'exists:orders,id'
        ]);

        $orders = Order::with(['orderItems.seedLot.variety.commodity', 'payment', 'shipment'])
            ->whereIn('id', $request->selected_orders)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'orders_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        return response()->stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Headers
            fputcsv($handle, [
                'Order Code',
                'Customer Name',
                'Customer Phone',
                'Customer Email',
                'Customer Address',
                'Status',
                'Shipping Method',
                'Shipping Cost',
                'Total Amount',
                'Payment Status',
                'Payment Method',
                'PNBP Receipt No',
                'Tracking Number',
                'Order Date',
                'Items Count',
                'Items Detail'
            ]);

            foreach ($orders as $order) {
                $itemsDetail = $order->orderItems->map(function ($item) {
                    return "{$item->seedLot->variety->commodity->name} - {$item->seedLot->variety->name} ({$item->quantity} {$item->seedLot->unit}) @ Rp " . number_format($item->unit_price);
                })->implode('; ');

                fputcsv($handle, [
                    $order->order_code,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->customer_email ?? '',
                    $order->customer_address,
                    ucfirst(str_replace('_', ' ', $order->status)),
                    $order->shipping_method === 'pickup' ? 'Pickup at BRMP' : ucfirst($order->shipping_method),
                    $order->shipping_cost ? 'Rp ' . number_format($order->shipping_cost) : 'Free',
                    'Rp ' . number_format($order->total_amount),
                    $order->payment ? ucfirst($order->payment->status) : 'Pending',
                    $order->payment ? $order->payment->payment_method : '',
                    $order->payment ? $order->payment->pnbp_receipt_no : '',
                    $order->shipment ? $order->shipment->tracking_number : '',
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->orderItems->count(),
                    $itemsDetail
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Check if order can transition to the given status
     * Simplified flow:
     * - Delivery: Processing -> Completed
     * - Pickup: Ready for Pickup -> Completed
     */
    private function canTransitionToStatus(Order $order, string $newStatus): bool
    {
        $currentStatus = $order->status;

        // Define valid transitions — synced with Order model's canTransitionTo()
        $validTransitions = [
            Order::STATUS_AWAITING_PAYMENT => [
                Order::STATUS_PENDING_VERIFICATION,
                Order::STATUS_PAID,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_PENDING_VERIFICATION => [
                Order::STATUS_PAID,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_PAID => [
                Order::STATUS_PROCESSING,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_PROCESSING => $order->is_pickup 
                ? [Order::STATUS_PICKUP_READY, Order::STATUS_CANCELLED]
                : [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
            Order::STATUS_PICKUP_READY => [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED
            ],
            Order::STATUS_COMPLETED => [],
            Order::STATUS_CANCELLED => []
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * Download a single order invoice as PDF
     */
    public function downloadInvoice($id)
    {
        ini_set('memory_limit', '256M');
        $order = Order::with(['orderItems.seedLot.variety.commodity', 'orderItems.seedLot.seedClass', 'payment', 'shipment'])->findOrFail($id);
        
        // Calculate correct totals
        $order->computed_subtotal = $order->orderItems->sum(function($item) {
            return $item->quantity * $item->price_at_order;
        });
        $order->computed_biaya_layanan = $order->computed_subtotal * 0.01;
        $order->computed_biaya_aplikasi = 2500;
        $order->computed_total = $order->computed_subtotal + $order->computed_biaya_layanan + $order->computed_biaya_aplikasi;

        $logoPath = public_path('images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        
        $pdf = Pdf::loadView('admin.orders.pdf.invoice', compact('order', 'logoBase64'))
                  ->setPaper('A4')
                  ->setOption('margin-top', 20)
                  ->setOption('margin-right', 15)
                  ->setOption('margin-bottom', 20)
                  ->setOption('margin-left', 15)
                  ->setOption('default_font', 'DejaVu Sans')
                  ->setOption('isHtml5ParserEnabled', true);
        
        return $pdf->download('INVOICE-'.$order->order_code.'.pdf');
    }

    /**
     * Download multiple order invoices as ZIP
     */
    public function downloadBulkInvoices(Request $request)
    {
        ini_set('memory_limit', '512M');
        $orderIds = $request->input('selected_orders', []);
        if (empty($orderIds)) {
            $orderIds = $request->input('order_ids', []);
        }

        if (empty($orderIds)) {
            return back()->with('error', 'Tidak ada order yang dipilih untuk diunduh');
        }

        $zip = new \ZipArchive();
        $zipFileName = 'INVOICES-'.date('Ymd-His').'.zip';
        
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir . '/' . $zipFileName;
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $logoPath = public_path('images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }

            foreach ($orderIds as $id) {
                $order = Order::with(['orderItems.seedLot.variety.commodity', 'orderItems.seedLot.seedClass', 'payment', 'shipment'])->findOrFail($id);
                
                // Calculate correct totals
                $order->computed_subtotal = $order->orderItems->sum(function($item) {
                    return $item->quantity * $item->price_at_order;
                });
                $order->computed_biaya_layanan = $order->computed_subtotal * 0.01;
                $order->computed_biaya_aplikasi = 2500;
                $order->computed_total = $order->computed_subtotal + $order->computed_biaya_layanan + $order->computed_biaya_aplikasi;

                $pdf = Pdf::loadView('admin.orders.pdf.invoice', compact('order', 'logoBase64'))
                          ->setPaper('A4')
                          ->setOption('margin-top', 20)
                          ->setOption('margin-right', 15)
                          ->setOption('margin-bottom', 20)
                          ->setOption('margin-left', 15)
                          ->setOption('default_font', 'DejaVu Sans')
                          ->setOption('isHtml5ParserEnabled', true);
                $zip->addFromString('INVOICE-'.$order->order_code.'.pdf', $pdf->output());
            }
            $zip->close();
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }
        
        return back()->with('error', 'Gagal membuat archive PDF');
    }
}
