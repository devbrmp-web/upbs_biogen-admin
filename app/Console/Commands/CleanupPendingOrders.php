<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPendingOrders extends Command
{
    protected $signature = 'orders:cleanup-pending';
    protected $description = 'Cleanup pending orders older than 25 hours';

    public function handle()
    {
        $threshold = now()->subHours(25);
        $this->info('Running schedule cleanup. Threshold: ' . $threshold);

        Order::query()
            ->with(['orderItems.seedLot', 'orderItems.variety'])
            ->where('status', Order::STATUS_AWAITING_PAYMENT)
            ->where('created_at', '<=', $threshold)
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
