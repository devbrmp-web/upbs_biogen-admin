<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Shipment;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderTransactionSeeder extends Seeder
{
    /**
     * Buat 5-10 sampel transaksi Completed & Pending menggunakan varietas Padi
     * untuk mengisi grafik Dashboard. Semua perhitungan dilakukan via Order::calculateTotals()
     * untuk menjaga konsistensi kalkulasi (service_fee 1%, app_fee Rp 4.000).
     */
    public function run(): void
    {
        // ── Guard: hanya jalankan jika belum ada order ────────────────────────
        if (Order::count() >= 8) {
            $this->command->info('OrderTransactionSeeder: Sudah ada order, dilewati.');
            return;
        }

        // ── Ambil data padi BS dan FS ─────────────────────────────────────────
        $padiCommodity = Commodity::where('slug', 'padi')->first();
        if (! $padiCommodity) {
            $this->command->warn('Komoditas Padi tidak ditemukan!');
            return;
        }

        $bsClass = SeedClass::where('code', 'BS')->first();
        $fsClass = SeedClass::where('code', 'FS')->first();

        $bsLots = SeedLot::whereHas('variety', fn($q) => $q->where('commodity_id', $padiCommodity->id))
            ->where('seed_class_id', $bsClass?->id)
            ->where('is_sellable', true)
            ->where('quantity', '>', 0)
            ->with(['variety', 'seedClass'])
            ->get();

        $fsLots = SeedLot::whereHas('variety', fn($q) => $q->where('commodity_id', $padiCommodity->id))
            ->where('seed_class_id', $fsClass?->id)
            ->where('is_sellable', true)
            ->where('quantity', '>', 0)
            ->with(['variety', 'seedClass'])
            ->get();

        $allLots = $bsLots->merge($fsLots);

        if ($allLots->isEmpty()) {
            $this->command->warn('Tidak ada seed lots Padi yang bisa digunakan untuk demo order.');
            return;
        }

        // ── Data pelanggan demo (realistis) ───────────────────────────────────
        $customers = [
            [
                'name'    => 'Dinas Pertanian Kab. Bogor',
                'phone'   => '0251-8382222',
                'email'   => 'distan@bogorkab.go.id',
                'address' => 'Jl. Tegar Beriman, Cibinong, Kab. Bogor, Jawa Barat 16914',
                'type'    => 'institution',
            ],
            [
                'name'    => 'Gapoktan Maju Bersama',
                'phone'   => '08123456789',
                'email'   => 'gapoktan.maju@gmail.com',
                'address' => 'Desa Sukajaya, Kec. Tamansari, Kab. Bogor, Jawa Barat',
                'type'    => 'cooperative',
            ],
            [
                'name'    => 'Budi Santoso',
                'phone'   => '08567890123',
                'email'   => null,
                'address' => 'Dusun Ciawi RT 03/RW 01, Desa Cibodas, Kec. Lembang, Kab. Bandung Barat',
                'type'    => 'individual',
            ],
            [
                'name'    => 'BPTP Jawa Barat',
                'phone'   => '022-7000011',
                'email'   => 'bptp.jabar@pertanian.go.id',
                'address' => 'Jl. Kayuambon No. 80, Lembang, Kab. Bandung Barat, Jawa Barat 40391',
                'type'    => 'institution',
            ],
            [
                'name'    => 'Koperasi Tani Sejahtera',
                'phone'   => '08765432100',
                'email'   => 'koptanisejahtera@gmail.com',
                'address' => 'Jl. Raya Sukabumi No. 12, Kec. Cikembar, Kab. Sukabumi, Jawa Barat',
                'type'    => 'cooperative',
            ],
            [
                'name'    => 'Siti Rahayu',
                'phone'   => '08213456789',
                'email'   => null,
                'address' => 'Kampung Pasir RT 02/RW 03, Desa Cileles, Kec. Jatinangor, Kab. Sumedang',
                'type'    => 'individual',
            ],
            [
                'name'    => 'Balai Penyuluhan Pertanian Cianjur',
                'phone'   => '0263-261441',
                'email'   => 'bpp.cianjur@pertanian.go.id',
                'address' => 'Jl. Raya Cianjur-Bandung Km 5, Cianjur, Jawa Barat 43217',
                'type'    => 'institution',
            ],
            [
                'name'    => 'Ahmad Wijaya',
                'phone'   => '08987654321',
                'email'   => 'ahmad.wijaya@mail.com',
                'address' => 'Jl. Tani Makmur No. 8, Desa Sidodadi, Kec. Lampung Tengah',
                'type'    => 'individual',
            ],
        ];

        // ── Definisi skenario transaksi ───────────────────────────────────────
        $scenarios = [
            // ─── Completed (sudah selesai) → mengisi grafik revenue ──────────
            [
                'status'          => Order::STATUS_COMPLETED,
                'shipping_method' => Order::SHIPPING_PICKUP,
                'qty'             => 5,
                'class_filter'    => 'BS',
                'days_ago'        => 90,
            ],
            [
                'status'          => Order::STATUS_COMPLETED,
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'qty'             => 10,
                'class_filter'    => 'FS',
                'days_ago'        => 75,
            ],
            [
                'status'          => Order::STATUS_COMPLETED,
                'shipping_method' => Order::SHIPPING_PICKUP,
                'qty'             => 20,
                'class_filter'    => 'BS',
                'days_ago'        => 60,
            ],
            [
                'status'          => Order::STATUS_COMPLETED,
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'qty'             => 15,
                'class_filter'    => 'FS',
                'days_ago'        => 45,
            ],
            [
                'status'          => Order::STATUS_COMPLETED,
                'shipping_method' => Order::SHIPPING_PICKUP,
                'qty'             => 8,
                'class_filter'    => 'BS',
                'days_ago'        => 30,
            ],
            // ─── Pending (masih proses) ───────────────────────────────────────
            [
                'status'          => Order::STATUS_AWAITING_PAYMENT,
                'shipping_method' => Order::SHIPPING_PICKUP,
                'qty'             => 5,
                'class_filter'    => 'BS',
                'days_ago'        => 3,
            ],
            [
                'status'          => Order::STATUS_PAID,
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'qty'             => 15,
                'class_filter'    => 'FS',
                'days_ago'        => 5,
            ],
            [
                'status'          => Order::STATUS_PROCESSING,
                'shipping_method' => Order::SHIPPING_PICKUP,
                'qty'             => 10,
                'class_filter'    => 'BS',
                'days_ago'        => 7,
            ],
        ];

        $created = 0;

        foreach ($scenarios as $i => $scenario) {
            $customer     = $customers[$i % count($customers)];
            $createdAt    = now()->subDays($scenario['days_ago'])->subHours(rand(1, 6));
            $shippingCost = $scenario['shipping_method'] === Order::SHIPPING_DELIVERY
                ? fake()->randomElement([15000, 20000, 25000, 30000, 35000])
                : 0;

            // Cari lot sesuai kelas
            $eligibleLots = $allLots->filter(fn($lot) => $lot->seedClass?->code === $scenario['class_filter']
                && $lot->quantity >= $scenario['qty']);

            if ($eligibleLots->isEmpty()) {
                // Fallback ke lot mana saja yang cukup stok
                $eligibleLots = $allLots->filter(fn($lot) => $lot->quantity >= $scenario['qty']);
            }
            if ($eligibleLots->isEmpty()) {
                $this->command->warn("Tidak ada lot dengan stok cukup untuk skenario #{$i}, dilewati.");
                continue;
            }

            $seedLot = $eligibleLots->first();
            $variety  = $seedLot->variety;

            // ── Buat Order ────────────────────────────────────────────────────
            $order = Order::create([
                'order_code'      => 'WUB-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'customer_name'   => $customer['name'],
                'customer_phone'  => $customer['phone'],
                'customer_email'  => $customer['email'],
                'customer_address'=> $customer['address'],
                'shipping_method' => $scenario['shipping_method'],
                'status'          => $scenario['status'],
                'shipping_cost'   => $shippingCost,
                'courier_name'    => $scenario['shipping_method'] === Order::SHIPPING_DELIVERY
                    ? fake()->randomElement([Shipment::COURIER_POS_INDONESIA, Shipment::COURIER_INDAH_CARGO])
                    : null,
                'courier_service' => $scenario['shipping_method'] === Order::SHIPPING_DELIVERY ? 'REG' : null,
                'subtotal'        => 0,
                'total_amount'    => 0,
                'notes'           => [],
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);

            // ── Buat Order Item ───────────────────────────────────────────────
            OrderItem::createFromVariety($order, $variety, (int) $scenario['qty'], $seedLot);

            // Kurangi stok lot
            $seedLot->decrement('quantity', (int) $scenario['qty']);

            // ── Hitung total (pakai method resmi) ─────────────────────────────
            $order->load('items');
            $order->calculateTotals();

            // ── Payment record ────────────────────────────────────────────────
            $paymentStatus = match ($scenario['status']) {
                Order::STATUS_AWAITING_PAYMENT => Payment::STATUS_PENDING,
                Order::STATUS_CANCELLED        => Payment::STATUS_CANCELLED,
                default                        => Payment::STATUS_PAID,
            };

            $paidAt = $paymentStatus === Payment::STATUS_PAID
                ? (clone $createdAt)->modify('+2 hours')
                : null;

            Payment::create([
                'order_id'               => $order->id,
                'payment_method'         => fake()->randomElement([
                    Payment::METHOD_BANK_TRANSFER,
                    Payment::METHOD_VA_BRI,
                    Payment::METHOD_VA_BNI,
                    Payment::METHOD_QRIS,
                ]),
                'gateway_transaction_id' => strtoupper(Str::random(10)),
                'gateway_reference'      => strtoupper(Str::random(8)),
                'pnbp_receipt_no'        => $paymentStatus === Payment::STATUS_PAID
                    ? 'PNBP-' . $createdAt->format('Ymd') . '-' . rand(1000, 9999)
                    : null,
                'amount'                 => $order->total_amount,
                'status'                 => $paymentStatus,
                'paid_at'                => $paidAt,
                'expires_at'             => (clone $createdAt)->modify('+1 day'),
                'payment_ip'             => '192.168.1.1',
                'notes'                  => $paymentStatus === Payment::STATUS_PAID
                    ? 'Seeded payment — ' . $customer['type']
                    : 'Menunggu pembayaran',
            ]);

            // ── Shipment ──────────────────────────────────────────────────────
            if ($scenario['shipping_method'] === Order::SHIPPING_DELIVERY) {
                $shipmentStatus = $scenario['status'] === Order::STATUS_COMPLETED
                    ? Shipment::STATUS_DELIVERED
                    : Shipment::STATUS_PENDING;

                $shippedAt   = $scenario['status'] === Order::STATUS_COMPLETED
                    ? (clone $createdAt)->modify('+3 days') : null;
                $deliveredAt = $shippedAt ? (clone $shippedAt)->modify('+2 days') : null;

                Shipment::create([
                    'order_id'        => $order->id,
                    'shipping_method' => Shipment::SHIPPING_DELIVERY,
                    'courier_name'    => $order->courier_name,
                    'tracking_number' => $shippedAt ? strtoupper('TRK-' . Str::random(10)) : null,
                    'status'          => $shipmentStatus,
                    'shipped_at'      => $shippedAt,
                    'delivered_at'    => $deliveredAt,
                ]);
            } else {
                // Pickup
                $shipmentStatus = match ($scenario['status']) {
                    Order::STATUS_PICKUP_READY => Shipment::STATUS_READY_FOR_PICKUP,
                    Order::STATUS_COMPLETED    => Shipment::STATUS_DELIVERED,
                    default                    => Shipment::STATUS_PENDING,
                };

                $readyAt     = in_array($scenario['status'], [Order::STATUS_PICKUP_READY, Order::STATUS_COMPLETED])
                    ? (clone $createdAt)->modify('+1 day') : null;
                $deliveredAt = $scenario['status'] === Order::STATUS_COMPLETED && $readyAt
                    ? (clone $readyAt)->modify('+1 day') : null;

                Shipment::create([
                    'order_id'             => $order->id,
                    'shipping_method'      => Shipment::SHIPPING_PICKUP,
                    'status'               => $shipmentStatus,
                    'ready_for_pickup_at'  => $readyAt,
                    'delivered_at'         => $deliveredAt,
                ]);
            }

            $created++;
            $this->command->line("   → Order #{$order->order_code} [{$scenario['status']}] dibuat.");
        }

        // Refresh cache stok semua variety
        Variety::all()->each(fn($v) => $v->clearStockCache());

        $this->command->info("✅ OrderTransactionSeeder: {$created} transaksi demo berhasil dibuat.");
    }
}
