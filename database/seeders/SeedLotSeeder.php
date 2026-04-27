<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Database\Seeder;

class SeedLotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Stok Riil (Excel Rekapitulasi Pelayanan Publik 2026):
     *  - Bioni 63 Ciherang BS: 623 kg
     *  - Bioprima Agritan  BS: 1341 kg
     *  - Bioryza Agritan   BS: 1362 kg
     *  - Biomonas Agritan  BS: 1410 kg
     *  - Biosalin 1        BS: 304 kg
     *  - Biosalin 2        BS: 619 kg
     *
     * Stok Dummy (FS & SS) semua varietas Padi: 100-500 kg (random).
     * Stok lainnya (Hortikultura, Sorgum, Kedelai): acak 50-200 unit.
     *
     * Harga sesuai Penetapan PNBP PPHP BRMP Biogen 2026:
     *  Padi    BS: 40.000/kg | FS: 14.000/kg | SS: 12.000/kg
     *  Kedelai BS: 70.000/kg
     *  Sorgum  BS: 35.000/kg
     *  Cabai   BS: 3.000/gram
     *  Kentang ST: 50.000/botol | G0: 2.000/umbi
     *  Rumput  BSM: 500/stek
     *  Anggrek Phalaenopsis ST: 50.000 | Dendrobium ST: 33.000
     */
    public function run(): void
    {
        // ── Guard ────────────────────────────────────────────────────────────
        $sc = SeedClass::all()->keyBy('code');
        if ($sc->isEmpty()) {
            $this->command->error('SeedClassSeeder belum dijalankan!');
            return;
        }

        // ── Helper ───────────────────────────────────────────────────────────
        $year   = 2026;
        $lotSeq = 1;

        $makeLot = function (array $data) use (&$lotSeq, $year) {
            $prefix   = $data['class_code'];
            $lotCode  = "{$prefix}-{$year}-" . str_pad($lotSeq++, 4, '0', STR_PAD_LEFT);
            return array_merge([
                'lot_code'       => $lotCode,
                'production_year'=> $year,
                'is_sellable'    => true,
                'description'    => null,
                'harvest_date'   => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ], $data);
        };

        $lots = [];

        // ════════════════════════════════════════════════════════════════════
        // PADI
        // ════════════════════════════════════════════════════════════════════
        $padiCommodity = Commodity::where('slug', 'padi')->first();
        if ($padiCommodity && isset($sc['BS'], $sc['FS'], $sc['SS'])) {

            // Stok riil BS dari Excel 2026
            $realBsStock = [
                'Bioni 63 Ciherang' => 623,
                'Bioprima Agritan'  => 1341,
                'Bioryza Agritan'   => 1362,
                'Biomonas Agritan'  => 1410,
                'Biosalin 1'        => 304,
                'Biosalin 2'        => 619,
            ];

            $padiVarieties = Variety::where('commodity_id', $padiCommodity->id)->get();

            foreach ($padiVarieties as $variety) {
                // ── BS (Breeder Seed) ──────────────────────────────────────
                $bsQty = $realBsStock[$variety->name] ?? rand(100, 400); // fallback dummy untuk varietas lain
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['BS']->id,
                    'class_code'     => 'BS',
                    'quantity'       => (int) $bsQty,
                    'unit'           => 'kg',
                    'price_per_unit' => 40000,
                    'description'    => "Stok BS {$variety->name} tahun {$year}" .
                                       (isset($realBsStock[$variety->name]) ? ' (Data Real Excel 2026)' : ' (Estimasi)'),
                ]);

                // ── FS (Foundation Seed) — dummy 100–500 kg ───────────────
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['FS']->id,
                    'class_code'     => 'FS',
                    'quantity'       => rand(100, 500),
                    'unit'           => 'kg',
                    'price_per_unit' => 14000,
                    'description'    => "Stok FS {$variety->name} tahun {$year} (Stok Dummy)",
                ]);

                // ── SS (Stock Seed) — dummy 100–500 kg ───────────────────
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['SS']->id,
                    'class_code'     => 'SS',
                    'quantity'       => rand(100, 500),
                    'unit'           => 'kg',
                    'price_per_unit' => 12000,
                    'description'    => "Stok SS {$variety->name} tahun {$year} (Stok Dummy)",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // KEDELAI — hanya BS
        // ════════════════════════════════════════════════════════════════════
        $kedelaiCommodity = Commodity::where('slug', 'kedelai')->first();
        if ($kedelaiCommodity && isset($sc['BS'])) {
            $kedelaiVarieties = Variety::where('commodity_id', $kedelaiCommodity->id)->get();
            foreach ($kedelaiVarieties as $variety) {
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['BS']->id,
                    'class_code'     => 'BS',
                    'quantity'       => rand(50, 200),
                    'unit'           => 'kg',
                    'price_per_unit' => 70000,
                    'description'    => "Stok BS {$variety->name} tahun {$year}",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // SORGUM — hanya BS
        // ════════════════════════════════════════════════════════════════════
        $sorgumCommodity = Commodity::where('slug', 'sorgum')->first();
        if ($sorgumCommodity && isset($sc['BS'])) {
            $sorgumVarieties = Variety::where('commodity_id', $sorgumCommodity->id)->get();
            foreach ($sorgumVarieties as $variety) {
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['BS']->id,
                    'class_code'     => 'BS',
                    'quantity'       => rand(50, 150),
                    'unit'           => 'kg',
                    'price_per_unit' => 35000,
                    'description'    => "Stok BS {$variety->name} tahun {$year}",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // CABAI — BS, satuan gram
        // ════════════════════════════════════════════════════════════════════
        $cabaiCommodity = Commodity::where('slug', 'cabai')->first();
        if ($cabaiCommodity && isset($sc['BS'])) {
            $carvi = Variety::where('commodity_id', $cabaiCommodity->id)->where('name', 'Carvi Agrihorti')->first();
            if ($carvi) {
                $lots[] = $makeLot([
                    'variety_id'     => $carvi->id,
                    'seed_class_id'  => $sc['BS']->id,
                    'class_code'     => 'BS',
                    'quantity'       => rand(50, 200),  // dalam gram
                    'unit'           => 'gram',
                    'price_per_unit' => 3000,           // Rp 3.000/gram
                    'description'    => "Stok BS Carvi Agrihorti tahun {$year} (gram)",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // KENTANG — Starter (botol) + G0 (umbi)
        // ════════════════════════════════════════════════════════════════════
        $kentangCommodity = Commodity::where('slug', 'kentang')->first();
        if ($kentangCommodity) {
            $bioGranola = Variety::where('commodity_id', $kentangCommodity->id)->where('name', 'Bio Granola')->first();
            if ($bioGranola) {
                // Starter
                if (isset($sc['ST'])) {
                    $lots[] = $makeLot([
                        'variety_id'     => $bioGranola->id,
                        'seed_class_id'  => $sc['ST']->id,
                        'class_code'     => 'ST',
                        'quantity'       => rand(50, 200),
                        'unit'           => 'bottle',
                        'price_per_unit' => 50000,
                        'description'    => "Stok Starter Bio Granola (planlet botol) tahun {$year}",
                    ]);
                }
                // G0
                if (isset($sc['G0'])) {
                    $lots[] = $makeLot([
                        'variety_id'     => $bioGranola->id,
                        'seed_class_id'  => $sc['G0']->id,
                        'class_code'     => 'G0',
                        'quantity'       => rand(100, 500),
                        'unit'           => 'piece',
                        'price_per_unit' => 2000,
                        'description'    => "Stok G0 Bio Granola (umbi mini) tahun {$year}",
                    ]);
                }
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // RUMPUT GAJAH — BSM (stek)
        // ════════════════════════════════════════════════════════════════════
        $rumputCommodity = Commodity::where('slug', 'rumput-gajah')->first();
        if ($rumputCommodity && isset($sc['BSM'])) {
            $biograss = Variety::where('commodity_id', $rumputCommodity->id)->where('name', 'Biograss Agrinak')->first();
            if ($biograss) {
                $lots[] = $makeLot([
                    'variety_id'     => $biograss->id,
                    'seed_class_id'  => $sc['BSM']->id,
                    'class_code'     => 'BSM',
                    'quantity'       => rand(100, 300),
                    'unit'           => 'piece',
                    'price_per_unit' => 500,
                    'description'    => "Stok BSM Biograss Agrinak (stek) tahun {$year}",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // ANGGREK — Starter (botol planlet)
        // ════════════════════════════════════════════════════════════════════
        $anggrekCommodity = Commodity::where('slug', 'anggrek')->first();
        if ($anggrekCommodity && isset($sc['ST'])) {
            $anggrekPrices = [
                'Phalaenopsis' => 50000,
                'Dendrobium'   => 33000,
            ];

            $anggrekVarieties = Variety::where('commodity_id', $anggrekCommodity->id)->get();
            foreach ($anggrekVarieties as $variety) {
                $price = $anggrekPrices[$variety->name] ?? 40000;
                $lots[] = $makeLot([
                    'variety_id'     => $variety->id,
                    'seed_class_id'  => $sc['ST']->id,
                    'class_code'     => 'ST',
                    'quantity'       => rand(50, 200),
                    'unit'           => 'bottle',
                    'price_per_unit' => $price,
                    'description'    => "Stok Starter {$variety->name} (planlet botol) tahun {$year}",
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════════
        // JERUK & AREN — Starter (botol planlet)
        // ════════════════════════════════════════════════════════════════════
        $missingCommodities = Commodity::whereIn('slug', ['jeruk', 'aren'])->get();
        if ($missingCommodities->isNotEmpty() && isset($sc['ST'])) {
            foreach ($missingCommodities as $commodity) {
                $variety = Variety::where('commodity_id', $commodity->id)->first();
                if ($variety) {
                    $lots[] = $makeLot([
                        'variety_id'     => $variety->id,
                        'seed_class_id'  => $sc['ST']->id,
                        'class_code'     => 'ST',
                        'quantity'       => 50,
                        'unit'           => 'bottle',
                        'price_per_unit' => 10000,
                        'description'    => "Stok Starter {$variety->name} (planlet botol) - Data Injeksi Final",
                    ]);
                }
            }
        }

        // ── Bulk insert ──────────────────────────────────────────────────────
        // Hapus kolom sementara sebelum insert
        $insertData = array_map(function ($lot) {
            unset($lot['class_code']);
            return $lot;
        }, $lots);

        collect($insertData)->chunk(50)->each(function ($chunk) {
            SeedLot::insert($chunk->toArray());
        });

        // ── Update variety cache ─────────────────────────────────────────────
        Variety::all()->each(fn($v) => $v->clearStockCache());

        $this->command->info('✅ SeedLotSeeder: ' . count($insertData) . ' seed lots berhasil di-seed (riil + dummy).');
    }
}