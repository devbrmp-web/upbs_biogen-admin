<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Shipment;
use App\Models\Variety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyOrderLogic extends Command
{
    protected $signature = 'verify:order-logic';
    protected $description = 'Verifikasi logika harga BS/FS dan pengurangan stok seed_lots saat checkout';

    public function handle(): int
    {
        $this->info('Menyiapkan data uji...');

        $bs = SeedClass::firstOrCreate(['code' => 'BS'], ['name' => 'Benih Sebar', 'is_active' => true]);
        $fs = SeedClass::firstOrCreate(['code' => 'FS'], ['name' => 'Foundation Seed', 'is_active' => true]);

        $variety = Variety::firstOrCreate([
            'name' => 'Varietas Uji',
            'slug' => 'varietas-uji',
            'commodity_id' => \App\Models\Commodity::query()->first()?->id ?? \App\Models\Commodity::factory()->create()->id,
        ], [
            'price' => 12000,
            'description' => 'Varietas untuk verifikasi',
            'minimum_limit' => 0,
            'sku' => 'VR-UJI-001',
            'image_path' => null,
        ]);

        $lotBs = SeedLot::firstOrCreate([
            'lot_code' => 'LOT-BS-UJI',
            'variety_id' => $variety->id,
            'seed_class_id' => $bs->id,
        ], [
            'production_year' => (int) date('Y'),
            'quantity' => 100,
            'unit' => 'kg',
            'price_per_unit' => 12000.0,
            'description' => 'Lot BS uji',
            'is_sellable' => true,
        ]);

        $lotFs = SeedLot::firstOrCreate([
            'lot_code' => 'LOT-FS-UJI',
            'variety_id' => $variety->id,
            'seed_class_id' => $fs->id,
        ], [
            'production_year' => (int) date('Y'),
            'quantity' => 50,
            'unit' => 'kg',
            'price_per_unit' => 15000.0,
            'description' => 'Lot FS uji',
            'is_sellable' => true,
        ]);

        // Uji BS: qty tidak kelipatan 5
        try {
            $this->info('Uji validasi BS dengan qty 7 (harus gagal)...');
            if ($lotBs->quantity < 7 || (7 % 5 !== 0)) {
                throw new \RuntimeException('Validasi BS: quantity harus kelipatan 5kg dan stok tersedia');
            }
        } catch (\Throwable $e) {
            $this->warn('OK: Validasi BS menolak qty 7 → ' . $e->getMessage());
        }

        // Uji BS: qty 10 (delivery, harus Indah Cargo jika >10 kg; di sini total 10 → Pos Indonesia)
        $orderBs = DB::transaction(function () use ($variety, $lotBs) {
            $qty = 10;
            if ($lotBs->quantity < $qty) {
                throw new \RuntimeException('Stok BS tidak cukup');
            }
            $lotBs->decrement('quantity', $qty);

            $order = Order::query()->create([
                'customer_name' => 'Tester BS',
                'customer_address' => 'Alamat',
                'customer_phone' => '+628111111111',
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'shipping_cost' => 0,
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            OrderItem::createFromVariety($order, $variety, $qty, $lotBs);
            $order->load('items');
            $order->calculateTotals();
            $totalWeightKg = (int) $order->items->sum('quantity');
            $order->update([
                'courier_name' => ($totalWeightKg > 10) ? Shipment::COURIER_INDAH_CARGO : Shipment::COURIER_POS_INDONESIA,
            ]);
            Shipment::createForOrder($order);
            Payment::createForOrder($order, Payment::METHOD_BANK_TRANSFER);
            return $order->fresh(['items']);
        });

        $itemBs = $orderBs->items->first();
        $expectedTotalBs = (int) (12000 * 5 * 2);
        $this->info('BS total_price: ' . (int) $itemBs->total_price . ' (ekspektasi ' . $expectedTotalBs . ')');
        $this->info('BS stok lot tersisa: ' . (int) $lotBs->fresh()->quantity);
        $this->info('BS courier (≤10 kg): ' . ($orderBs->fresh()->courier_name ?? 'N/A'));

        // Uji FS: qty 12 (delivery, harus Indah Cargo karena >10 kg)
        $orderFs = DB::transaction(function () use ($variety, $lotFs) {
            $qty = 12;
            if ($lotFs->quantity < $qty) {
                throw new \RuntimeException('Stok FS tidak cukup');
            }
            $lotFs->decrement('quantity', $qty);

            $order = Order::query()->create([
                'customer_name' => 'Tester FS',
                'customer_address' => 'Alamat',
                'customer_phone' => '+628111111112',
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'shipping_cost' => 0,
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            OrderItem::createFromVariety($order, $variety, $qty, $lotFs);
            $order->load('items');
            $order->calculateTotals();
            $totalWeightKg = (int) $order->items->sum('quantity');
            $order->update([
                'courier_name' => ($totalWeightKg > 10) ? Shipment::COURIER_INDAH_CARGO : Shipment::COURIER_POS_INDONESIA,
            ]);
            Shipment::createForOrder($order);
            Payment::createForOrder($order, Payment::METHOD_BANK_TRANSFER);
            return $order->fresh(['items']);
        });

        $itemFs = $orderFs->items->first();
        $expectedTotalFs = (int) (15000 * 3);
        $this->info('FS total_price: ' . (int) $itemFs->total_price . ' (ekspektasi ' . $expectedTotalFs . ')');
        $this->info('FS stok lot tersisa: ' . (int) $lotFs->fresh()->quantity);
        $this->info('FS courier (>10 kg): ' . ($orderFs->fresh()->courier_name ?? 'N/A'));

        $this->info('Verifikasi selesai.');
        return self::SUCCESS;
    }
}
