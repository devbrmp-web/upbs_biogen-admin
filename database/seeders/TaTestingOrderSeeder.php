<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SeedLot;
use App\Models\Shipment;
use App\Models\Variety;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ══════════════════════════════════════════════════════════════════════
 *  TA TESTING ORDER SEEDER — Pengujian Efisiensi Sistem (TA Fatih)
 * ══════════════════════════════════════════════════════════════════════
 *
 * Menyuntikkan 30 order COMPLETED yang logis untuk rentang Februari–Mei 2026.
 * Rasional: order lampau (beberapa bulan lalu) secara operasional pasti sudah
 * tuntas — tidak realistis bila masih menggantung di awaiting_payment/processing.
 * Maka SELURUH order berstatus akhir 'completed' (15 pickup + 15 delivery).
 *
 * STRATEGI: OPSI A — AUDIT BACKDATE MANUAL
 *   - Order::create() & calculateTotals() dibungkus Order::withoutEvents(...)
 *     agar trait Auditable TIDAK menembak audit_logs dengan timestamp "now".
 *   - audit_logs disuntik MANUAL & ter-backdate mengikuti rantai transisi penuh:
 *       awaiting_payment → pending_verification → paid → [shipped|ready_for_pickup] → completed
 *   - OrderItem TIDAK dibungkus withoutEvents (butuh event "saving" untuk total_price).
 *
 * Konvensi data mengikuti dump produksi (upbs_biogen_data.sql):
 *   pickup   : courier_name='Ambil di Tempat', courier_service='BRMP Biogen'
 *   delivery : courier_name='Pos Indonesia'|'Indah Cargo', courier_service='Regular'
 *   transfer : payment_type='Bank Transfer', transaction_id=order_code, transaction_status='pending'
 *
 * Idempoten: dilewati jika sudah ada >= 30 order bertanda seeder ini.
 *
 * Jalankan:  php artisan db:seed --class=TaTestingOrderSeeder
 */
class TaTestingOrderSeeder extends Seeder
{
    /** Penanda agar data TA mudah difilter & seeder idempoten. */
    private const MARKER = 'ta_testing_efficiency';

    /** Path bukti pembayaran statis (sesuai permintaan revisi LTA). */
    private const PROOF_PATH = 'storage/payment_proofs/WUB-20260603-SQBNDE_1780474805.png';

    /** ID admin yang memverifikasi pembayaran / mengubah status (users.id = 2 → admin@biogen.com). */
    private const ADMIN_ID = 2;

    public function run(): void
    {
        // ── Guard idempotensi ────────────────────────────────────────────
        $existing = Order::whereJsonContains('notes->seeder', self::MARKER)->count();
        if ($existing >= 30) {
            $this->command->info("TaTestingOrderSeeder: dilewati (sudah ada {$existing} order TA).");
            return;
        }

        // ── Sumber benih yang valid (FK aman + harga dari lot) ───────────
        $lots = SeedLot::with(['variety', 'seedClass'])
            ->where('is_sellable', true)
            ->where('quantity', '>', 0)
            ->get();

        if ($lots->isEmpty()) {
            $this->command->warn('Tidak ada seed_lots yang sellable & berstok. Jalankan SeedLotSeeder dulu.');
            return;
        }

        // ── Data responden RIIL kuesioner (guest checkout, melekat di orders) ──
        $customers = [
            ['name' => 'Syarifuddin',                   'phone' => '081271234567', 'email' => 'syarif.makmur@gmail.com',        'address' => 'Jl. WR Supratman No. 45, RT 012/RW 003, Muara Bangkahulu, Kota Bengkulu, Bengkulu, 38122'],
            ['name' => 'Hj. Marlina',                   'phone' => '081367891234', 'email' => 'hj.marlina.benih@gmail.com',     'address' => 'Jl. Raya Bengkulu-Padang, Kel. Betungan, Kec. Selebar, Kota Bengkulu, Bengkulu, 38215'],
            ['name' => 'Andika Pratama',                'phone' => '085273456789', 'email' => 'andika.jaya.benih@gmail.com',    'address' => 'Jl. Jend. Sudirman No. 45, Kel. Gading Cempaka, Kota Bengkulu, Bengkulu, 38221'],
            ['name' => 'Budi Ardiansyah',               'phone' => '081173001122', 'email' => 'budi.kepahiang.benih@gmail.com', 'address' => 'Jl. Lintas Kepahiang-Pagar Alam, Kel. Padang Lekat, RT 005/RW 002, Kec. Kepahiang, Kabupaten Kepahiang, Bengkulu, 39372'],
            ['name' => 'Parlindungan Togatorop',        'phone' => '082186754321', 'email' => 'togatorop.benih.jaya@gmail.com', 'address' => 'Jl. Kapuas Raya, Kel. Lingkar Barat, RT 015/RW 004, Kec. Gading Cempaka, Kota Bengkulu, Bengkulu, 38211'],
            ['name' => 'Jaka Sumarno, S.TP., M.Si',     'phone' => '081244556677', 'email' => 'jaka.sumarno@pertanian.go.id',   'address' => 'Jl. Mohamad Van Gobel No. 270, Desa Iloheluma, Kec. Tilongkabila, Kabupaten Bone Bolango, Gorontalo, 96119'],
            ['name' => 'Dr. Budi Santoso, S.TP., M.Si.','phone' => '081111223344', 'email' => 'budi.santoso@pertanian.go.id',   'address' => 'Kampus Penelitian Pertanian Cimanggu, Jl. Tentara Pelajar No.10, RT.01/RW.07, Ciwaringin, Kec. Bogor Tengah, Kota Bogor, Jawa Barat, 16124'],
            ['name' => 'Ir. Endang Lestari, M.P.',      'phone' => '081388990011', 'email' => 'endang.lestari@pertanian.go.id', 'address' => 'Balai Besar Perbenihan dan Proteksi Tanaman Perkebunan (BBPPTP), Jl. Perikanan Darat No.1, RT.02/RW.02, Kedung Waringin, Kec. Tanah Sareal, Kota Bogor, Jawa Barat, 16164'],
            ['name' => 'Ryan Hidayat, S.P.',            'phone' => '085777889900', 'email' => 'ryan.hidayat.asn@gmail.com',     'address' => 'Mess Pegawai BRMP, Jl. Sangkuriang No. 12, Kompleks Perumahan Dinas Pertanian, Kec. Bogor Barat, Kota Bogor, Jawa Barat, 16111'],
            ['name' => 'Annisa Permata, S.TP.',         'phone' => '085211223344', 'email' => 'annisa.permata.asn@gmail.com',   'address' => 'Jl. Irian KM 6,5, Kelurahan Semarang, Kecamatan Sungai Serut, Kota Bengkulu, Bengkulu, 38119'],
            ['name' => 'Iwan Setiawan',                 'phone' => '081369002233', 'email' => 'iwan.lampung.tani@gmail.com',    'address' => 'Jl. Lintas Pantai Timur, Desa Tulung Pasik, RT 004/RW 002, Kec. Mataram Baru, Kabupaten Lampung Timur, Lampung, 34196'],
            ['name' => 'Andi Baso Tenriajeng',          'phone' => '082194005566', 'email' => 'andi.baso.tenri@gmail.com',      'address' => 'Jl. Poros Bone-Sinjai, Desa Tibojong, RT 002/RW 001, Kec. Tanete Riattang Timur, Kabupaten Bone, Sulawesi Selatan, 92711'],
            ['name' => 'Pasya Syazani',                 'phone' => '085380004455', 'email' => 'pasya.seluma.tani@gmail.com',    'address' => 'Jl. Letjen Soeprapto, Kelurahan Napal, RT 003/RW 001, Kec. Seluma, Kabupaten Seluma, Bengkulu, 38876'],
            ['name' => 'Emanuel Dawan',                 'phone' => '082247008899', 'email' => 'emanuel.dawan.tani@gmail.com',   'address' => 'Jl. Trans Timor, Desa Oelolok, RT 008/RW 003, Kec. Insana, Kabupaten Timor Tengah Utara, Nusa Tenggara Timur, 85611'],
            ['name' => 'Sri Wahyuni',                   'phone' => '085267001122', 'email' => 'sri.wahyuni.tani@gmail.com',     'address' => 'Jl. Danau No. 24, RT 012/RW 003, Kel. Surabaya, Kec. Sungai Serut, Kota Bengkulu, Bengkulu, 38119'],
            ['name' => 'Aryasatya Panji',               'phone' => '082371008899', 'email' => 'aryasatya.jambi.hidro@gmail.com','address' => 'Perumahan Pinang Merah, Blok C No. 12, Kel. Bagan Pete, Kec. Alam Barajo, Kota Jambi, Jambi, 36129'],
            ['name' => 'Gede Abirama',                  'phone' => '081936001122', 'email' => 'gede.abirama.garden@gmail.com',  'address' => 'Perumahan Royal Aditya Residence, Blok B No. 8, Jl. Bikini, Padangsambian Klod, Kec. Denpasar Barat, Kota Denpasar, Bali, 80117'],
            ['name' => 'Meutia Zahra',                  'phone' => '085366991122', 'email' => 'meutia.zahra.tani@gmail.com',    'address' => 'Jl. Pangeran Natadirja No. 15, RT 002/RW 001, Kel. KM 6,5, Kec. Gading Cempaka, Kota Bengkulu, Bengkulu, 38224'],
            ['name' => 'Arinauli Syakira',              'phone' => '081275003344', 'email' => 'arinauli.garden@gmail.com',      'address' => 'Perumahan Lily Spring Garden, Blok C No. 22, Jl. Riau, Kel. Tampan, Kec. Payung Sekaki, Kota Pekanbaru, Riau, 28291'],
            ['name' => 'Gesang Koswara',                'phone' => '081322004455', 'email' => 'gesang.koswara.bdg@gmail.com',   'address' => 'Jl. Terusan Buah Batu No. 102, RT 04/RW 02, Kec. Bojongsoang, Kabupaten Bandung, Jawa Barat, 40287'],
        ];

        // ── Distribusi status: 100% COMPLETED, rasio penyerahan 50/50 ─────
        // [status, shipping_method, jumlah]
        $blueprint = [
            [Order::STATUS_COMPLETED, Order::SHIPPING_PICKUP,   15],
            [Order::STATUS_COMPLETED, Order::SHIPPING_DELIVERY, 15],
        ];

        // Ratakan blueprint menjadi daftar 30 skenario lalu acak urutannya.
        $scenarios = [];
        foreach ($blueprint as [$status, $method, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $scenarios[] = ['status' => $status, 'method' => $method];
            }
        }
        shuffle($scenarios);

        // ── HARD CLAMP: tidak boleh ada timestamp melewati "sekarang" ─────
        // Hari ini 3 Juni 2026; data diolah hari ini. Batas mutlak 03-06-2026 15:00:00.
        $hardLimit = Carbon::create(2026, 6, 3, 15, 0, 0);
        $clamp = fn (Carbon $t): Carbon => $t->greaterThan($hardLimit) ? $hardLimit->copy() : $t;

        $created = 0;

        foreach ($scenarios as $idx => $scenario) {
            $status     = $scenario['status'];
            $method     = $scenario['method'];
            $customer   = $customers[$idx % count($customers)];
            $isDelivery = $method === Order::SHIPPING_DELIVERY;

            // ── Rantai waktu transaksi (FLUKTUATIF ORGANIK) ───────────────
            // createdAt: 1 Feb .. 29 Mei 2026 (margin aman). Hard clamp tetap aktif.
            $createdAt = $clamp(
                Carbon::create(2026, 2, 1)
                    ->addDays(rand(0, 117))             // 1 Feb .. 29 Mei (margin aman)
                    ->setTime(rand(8, 15), rand(0, 59), rand(0, 59))
            );

            // Total durasi createdAt → completedAt, diacak per skenario (dalam menit):
            //   pickup   : 14–20 jam   |   delivery : 26–42 jam (1 hari lebih s/d ~2 hari)
            $totalMin = $isDelivery ? rand(26 * 60, 42 * 60) : rand(14 * 60, 20 * 60);

            // Bagi total menjadi 4 segmen proporsional-acak (bobot dinamis → offset natural):
            //   createdAt → pendingVerificationAt → paidAt → (shipped|ready) → completedAt
            $w  = [mt_rand(15, 45), mt_rand(15, 45), mt_rand(15, 45), mt_rand(15, 45)];
            $sw = array_sum($w);
            $o1 = (int) round($totalMin * $w[0] / $sw);                       // upload bukti
            $o2 = (int) round($totalMin * ($w[0] + $w[1]) / $sw);             // verifikasi admin
            $o3 = (int) round($totalMin * ($w[0] + $w[1] + $w[2]) / $sw);     // dikirim / siap diambil
            // Jamin urut menaik & tidak menabrak completedAt.
            $o1 = max(1, min($o1, $totalMin - 3));
            $o2 = max($o1 + 1, min($o2, $totalMin - 2));
            $o3 = max($o2 + 1, min($o3, $totalMin - 1));

            $pendingVerificationAt = $clamp((clone $createdAt)->addMinutes($o1));
            $paidAt                = $clamp((clone $createdAt)->addMinutes($o2));
            if ($isDelivery) {
                $shippedAt = $clamp((clone $createdAt)->addMinutes($o3));     // kurir mengirim
                $readyAt   = null;
            } else {
                $readyAt   = $clamp((clone $createdAt)->addMinutes($o3));     // siap diambil
                $shippedAt = null;
            }
            $completedAt = $clamp((clone $createdAt)->addMinutes($totalMin)); // diterima / diambil

            $lastEventAt = $completedAt;

            // ── Konvensi kurir REAL ───────────────────────────────────────
            if ($isDelivery) {
                $courierName    = fake()->randomElement([Shipment::COURIER_POS_INDONESIA, Shipment::COURIER_INDAH_CARGO]);
                $courierService = 'Regular';
            } else {
                $courierName    = 'Ambil di Tempat';
                $courierService = 'BRMP Biogen';
            }

            $orderCode = 'WUB-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(5));

            // ── 1. Order (events DIMATIKAN → trait Auditable tidak menembak log) ──
            $order = Order::withoutEvents(function () use (
                $orderCode, $customer, $status, $method, $courierName, $courierService,
                $createdAt, $paidAt, $completedAt, $lastEventAt
            ) {
                return Order::create([
                    'order_code'         => $orderCode,
                    'customer_name'      => $customer['name'],
                    'customer_address'   => $customer['address'],
                    'customer_phone'     => $customer['phone'],
                    'customer_email'     => $customer['email'],
                    'status'             => $status,
                    'shipping_method'    => $method,
                    'subtotal'           => 0,   // diisi calculateTotals()
                    'shipping_cost'      => 0,   // koordinasi manual → 0
                    'service_fee'        => 0,
                    'app_fee'            => 0,
                    'total_amount'       => 0,
                    'pnbp_receipt_no'    => 'PNBP-' . $createdAt->format('Ymd') . '-' . rand(1000, 9999),
                    'payment_type'       => 'Bank Transfer',
                    'transaction_id'     => $orderCode,
                    'transaction_status' => 'pending',
                    'paid_at'            => $paidAt,
                    'completed_at'       => $completedAt,
                    'courier_name'       => $courierName,
                    'courier_service'    => $courierService,
                    'notes'              => ['seeder' => self::MARKER, 'created_via' => 'ta_seeder'],
                    'payment_deadline'   => (clone $createdAt)->addHours(24),
                    'created_at'         => $createdAt,
                    'updated_at'         => $lastEventAt,
                ]);
            });

            // ── 2. Order items (events AKTIF → OrderItem menghitung total_price) ──
            $itemCount   = rand(1, 3);
            $usedVariety = [];

            foreach ($lots->shuffle() as $lot) {
                if (count($usedVariety) >= $itemCount) {
                    break;
                }
                if (! $lot->variety || in_array($lot->variety_id, $usedVariety, true)) {
                    continue;
                }
                $available = (int) $lot->quantity;
                if ($available <= 0) {
                    continue;
                }

                $qty = match ($lot->seedClass?->code) {
                    'BS', 'FS', 'SS' => rand(1, 25),
                    'ST'             => rand(5, 50),
                    'BSM'            => rand(1, 5),
                    default          => rand(1, 10),
                };
                $qty = min($available, $qty);
                if ($qty <= 0) {
                    continue;
                }

                $item = OrderItem::createFromVariety($order, $lot->variety, $qty, $lot);
                $item->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

                $lot->decrement('quantity', $qty);   // jaga konsistensi stok
                $usedVariety[] = $lot->variety_id;
            }

            // Jika tidak ada item (stok habis), batalkan order ini.
            if (empty($usedVariety)) {
                Order::withoutEvents(fn () => $order->delete());
                continue;
            }

            // ── 3. Hitung total via method resmi, lalu balikkan updated_at ──
            $order->load('items');
            Order::withoutEvents(function () use ($order, $lastEventAt) {
                $order->calculateTotals();                                   // 1% service_fee + Rp4.000 app_fee
                $order->forceFill(['updated_at' => $lastEventAt])->save();   // kembalikan ke timeline order
            });

            // ── 4. Payment (lunas + bukti pembayaran statis) ──────────────
            Payment::create([
                'order_id'           => $order->id,
                'payment_method'     => Payment::METHOD_BANK_TRANSFER,
                'pnbp_receipt_no'    => $order->pnbp_receipt_no,
                'amount'             => $order->total_amount,
                'status'             => Payment::STATUS_PAID,
                'transaction_status' => 'pending',
                'paid_at'            => $paidAt,
                'expires_at'         => (clone $createdAt)->addHours(24),
                'payment_proof_path' => self::PROOF_PATH,
                'proof_uploaded_at'  => $pendingVerificationAt,
                'payment_ip'         => '127.0.0.1',
                'notes'              => 'Pembayaran diverifikasi via bukti transfer (data TA)',
                'created_at'         => $createdAt,
                'updated_at'         => $lastEventAt,
            ]);

            // ── 5. Shipment (terkirim/terambil → delivered) ───────────────
            if ($isDelivery) {
                $shipment = Shipment::create([
                    'order_id'        => $order->id,
                    'shipping_method' => Shipment::SHIPPING_DELIVERY,
                    'courier_name'    => $courierName,
                    'tracking_number' => 'TRK-' . strtoupper(Str::random(10)),
                    'status'          => Shipment::STATUS_DELIVERED,
                    'shipped_at'      => $shippedAt,
                    'delivered_at'    => $completedAt,
                    'created_at'      => $createdAt,
                    'updated_at'      => $lastEventAt,
                ]);
            } else {
                $shipment = Shipment::create([
                    'order_id'            => $order->id,
                    'shipping_method'     => Shipment::SHIPPING_PICKUP,
                    'courier_name'        => 'Ambil di Tempat',
                    'status'              => Shipment::STATUS_DELIVERED,
                    'ready_for_pickup_at' => $readyAt,
                    'delivered_at'        => $completedAt,
                    'created_at'          => $createdAt,
                    'updated_at'          => $lastEventAt,
                ]);
            }

            // ── 6. AUDIT LOGS MANUAL — rantai penuh, ter-backdate ─────────
            $adminUrl = 'https://admin.upbsbiogen.my.id/admin/orders/' . $order->id;

            // (a) CREATE — order dibuat via guest checkout (user_id NULL).
            $this->writeAuditLog(
                'orders', $order->id, AuditLog::ACTION_CREATE, null,
                ['order_code' => $order->order_code, 'status' => Order::STATUS_AWAITING_PAYMENT, 'total_amount' => (string) $order->total_amount, 'shipping_method' => $method],
                "Order dibuat via guest checkout: {$order->order_code}",
                'https://upbsbiogen.my.id/checkout', 'POST', 'checkout.store', $createdAt
            );

            // (b) UPDATE → pending_verification (user upload bukti, user_id NULL).
            $this->writeAuditLog(
                'orders', $order->id, AuditLog::ACTION_UPDATE,
                ['status' => Order::STATUS_AWAITING_PAYMENT],
                ['status' => Order::STATUS_PENDING_VERIFICATION, 'payment_proof_path' => self::PROOF_PATH],
                "Bukti pembayaran diunggah, menunggu verifikasi admin: {$order->order_code}",
                'https://upbsbiogen.my.id/orders/' . $order->order_code . '/upload-proof', 'POST',
                'order.upload-proof', $pendingVerificationAt
            );

            // (c) UPDATE → paid (admin verifikasi, user_id = 2).
            $this->writeAuditLog(
                'orders', $order->id, AuditLog::ACTION_UPDATE,
                ['status' => Order::STATUS_PENDING_VERIFICATION],
                ['status' => Order::STATUS_PAID, 'paid_at' => $paidAt->toDateTimeString()],
                "Pembayaran diverifikasi admin: {$order->order_code}",
                $adminUrl, 'PATCH', 'admin.orders.updateStatus', $paidAt, self::ADMIN_ID
            );

            // (d) Tahap fulfilment (shipments) — admin, user_id = 2.
            if ($isDelivery) {
                $this->writeAuditLog(
                    'shipments', $shipment->id, AuditLog::ACTION_UPDATE,
                    ['status' => Shipment::STATUS_PENDING],
                    ['status' => Shipment::STATUS_SHIPPED, 'tracking_number' => $shipment->tracking_number],
                    "Paket dikirim via {$courierName} (resi {$shipment->tracking_number}): {$order->order_code}",
                    $adminUrl, 'PATCH', 'admin.orders.updateStatus', $shippedAt,
                    self::ADMIN_ID, AuditLog::CATEGORY_SHIPPING_FULFILLMENT
                );
            } else {
                $this->writeAuditLog(
                    'shipments', $shipment->id, AuditLog::ACTION_UPDATE,
                    ['status' => Shipment::STATUS_PENDING],
                    ['status' => Shipment::STATUS_READY_FOR_PICKUP],
                    "Pesanan siap diambil di kantor BRMP Biogen: {$order->order_code}",
                    $adminUrl, 'PATCH', 'admin.orders.updateStatus', $readyAt,
                    self::ADMIN_ID, AuditLog::CATEGORY_SHIPPING_FULFILLMENT
                );
            }

            // (e) UPDATE → completed (order tuntas, user_id = 2).
            $this->writeAuditLog(
                'orders', $order->id, AuditLog::ACTION_UPDATE,
                ['status' => Order::STATUS_PAID],
                ['status' => Order::STATUS_COMPLETED, 'completed_at' => $completedAt->toDateTimeString()],
                "Order diselesaikan: {$order->order_code}",
                $adminUrl, 'PATCH', 'admin.orders.updateStatus', $completedAt, self::ADMIN_ID
            );

            $created++;
            $this->command->line("   → {$order->order_code} [{$status} / {$method}] — Rp " . number_format($order->total_amount, 0, ',', '.'));
        }

        // Segarkan cache stok varietas (jika tersedia).
        Variety::all()->each(function ($v) {
            if (method_exists($v, 'clearStockCache')) {
                $v->clearStockCache();
            }
        });

        $this->command->info("✅ TaTestingOrderSeeder: {$created} order COMPLETED (Feb–Mei 2026) berhasil dibuat dengan audit trail penuh ter-backdate.");
    }

    /**
     * Sisipkan satu baris audit_logs manual dengan timestamp ter-backdate.
     */
    private function writeAuditLog(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldData,
        array $newData,
        string $description,
        string $url,
        string $method,
        string $routeName,
        Carbon $at,
        ?int $userId = null,
        string $category = AuditLog::CATEGORY_ORDER_MANAGEMENT
    ): void {
        AuditLog::create([
            'user_id'     => $userId,
            'action'      => $action,
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'route_name'  => $routeName,
            'url'         => $url,
            'method'      => $method,
            'ip_address'  => $userId ? '103.94.' . rand(0, 255) . '.' . rand(1, 254)   // admin (kantor)
                                     : '114.79.' . rand(0, 255) . '.' . rand(1, 254),  // pelanggan
            'user_agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'old_data'    => $oldData,
            'new_data'    => $newData,
            'description' => $description,
            'category'    => $category,
            'created_at'  => $at,
            'updated_at'  => $at,
        ]);
    }
}
