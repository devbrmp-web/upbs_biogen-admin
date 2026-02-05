<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPendingOrders extends Command
{
    protected $signature = 'orders:cleanup-pending';
    protected $description = 'Cleanup expired unpaid orders based on payment_deadline';

    public function handle()
    {
        $now = now();
        $this->info('Running schedule cleanup. Current time: ' . $now);

        // Query orders that are expired based on payment_deadline
        // Only cleanup orders with status awaiting_payment (NOT pending_verification)
        // Orders with pending_verification have uploaded proof and await admin review
        Order::query()
            ->with(['orderItems.seedLot', 'orderItems.variety'])
            ->where('status', Order::STATUS_AWAITING_PAYMENT)
            ->where(function ($query) use ($now) {
                // Primary: use payment_deadline if set
                $query->where('payment_deadline', '<=', $now)
                    // Fallback: if no payment_deadline, use created_at + 24 hours
                    ->orWhere(function ($q) use ($now) {
                        $q->whereNull('payment_deadline')
                          ->where('created_at', '<=', $now->copy()->subHours(24));
                    });
            })
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $this->info('Deleting order: ' . $order->id);
                    DB::transaction(function () use ($order) {
                        $order->loadMissing(['orderItems.seedLot', 'orderItems.variety']);

                        foreach ($order->orderItems as $item) {
                            if ($item->seedLot) {
                                $item->seedLot->increment('quantity', (int) $item->quantity);
                            } elseif ($item->variety) {
                                $item->variety->increment('stock', (int) $item->quantity);
                            }
                        }

                        $order->payment()?->delete();
                        $order->shipment()?->delete();
                        $order->orderItems()->delete();
                        $order->delete();
                    });
                }
            });
    }
}
