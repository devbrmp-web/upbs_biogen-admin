<?php

namespace Database\Seeders;

use App\Models\SeedClass;
use Illuminate\Database\Seeder;

class SeedClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Production-ready seed classes based on Buku Saku BSIP Biogen 2024.
     */
    public function run(): void
    {
        $seedClasses = [
            // ─── Kelas Berat (kg) ─────────────────────────────────────────
            [
                'code'           => 'BS',
                'name'           => 'Breeder Seed',
                'description'    => 'Benih Penjenis (BS) — Kelas benih tertinggi, diproduksi langsung oleh pemulia. Satuan: kilogram.',
                'stock_category' => 'weight',
                'default_unit'   => 'kg',
                'step_increment' => 1,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
            [
                'code'           => 'FS',
                'name'           => 'Foundation Seed',
                'description'    => 'Benih Dasar (FS) — Hasil perbanyakan dari BS. Pembelian minimum kelipatan 5 kg. Satuan: kilogram.',
                'stock_category' => 'weight',
                'default_unit'   => 'kg',
                'step_increment' => 5,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
            [
                'code'           => 'SS',
                'name'           => 'Stock Seed',
                'description'    => 'Benih Pokok (SS) — Hasil perbanyakan dari FS. Satuan: kilogram.',
                'stock_category' => 'weight',
                'default_unit'   => 'kg',
                'step_increment' => 1,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
            // ─── Kelas Unit ───────────────────────────────────────────────
            [
                'code'           => 'ST',
                'name'           => 'Starter',
                'description'    => 'Planlet/benih starter hasil kultur jaringan. Satuan: botol.',
                'stock_category' => 'unit',
                'default_unit'   => 'bottle',
                'step_increment' => 1,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
            [
                'code'           => 'G0',
                'name'           => 'G0 (Kentang)',
                'description'    => 'Benih kentang kelas G0 — umbi mini hasil kultur in-vitro. Satuan: umbi (piece).',
                'stock_category' => 'unit',
                'default_unit'   => 'piece',
                'step_increment' => 1,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
            [
                'code'           => 'BSM',
                'name'           => 'Benih Sumber (Stek)',
                'description'    => 'Benih sumber berupa stek vegetatif (rumput gajah, dll). Satuan: stek (piece).',
                'stock_category' => 'unit',
                'default_unit'   => 'piece',
                'step_increment' => 1,
                'min_order_qty'  => 1,
                'is_active'      => true,
            ],
        ];

        foreach ($seedClasses as $data) {
            SeedClass::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('✅ SeedClassSeeder: 6 kelas benih berhasil di-seed (BS, FS, SS, ST, G0, BSM).');
    }
}