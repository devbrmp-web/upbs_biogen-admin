<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\SeedClass;
use App\Models\Variety;
use App\Models\SeedLot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VarietySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Data varietas & harga riil bersumber dari:
     *  - Buku Saku BSIP Biogen 2024
     *  - Penetapan PNBP PPHP BRMP Biogen (PNBP 2026)
     *  - Rekapitulasi Pelayanan Publik 2026
     *
     * Harga = price_per_unit pada seed_lots (bukan di varieties).
     * image = null (produksi-ready, tanpa gambar dummy).
     */
    public function run(): void
    {
        // ────────────────────────────────────────────────────────────────────
        // Ambil kelas benih sekali
        // ────────────────────────────────────────────────────────────────────
        $sc = SeedClass::all()->keyBy('code');

        // ─── PADI ────────────────────────────────────────────────────────────
        // Harga BS: 40.000/kg | FS: 14.000/kg | SS: 12.000/kg
        $padi = Commodity::where('slug', 'padi')->first();
        if ($padi) {
            $padiVarieties = [
                [
                    'name'        => 'Bioni 63 Ciherang',
                    'description' => 'Varietas padi Bioni 63 Ciherang unggul BSIP Biogen — adaptif sawah irigasi, produktivitas tinggi, rasa nasi pulen.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Bioprima Agritan',
                    'description' => 'Varietas padi Bioprima unggul BSIP Biogen — tahan wereng batang coklat biotipe 1, 2, dan 3.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Bioryza Agritan',
                    'description' => 'Varietas padi Bioryza unggul BSIP Biogen — tahan penyakit hawar daun bakteri, potensi hasil tinggi.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Biomonas Agritan',
                    'description' => 'Varietas padi Biomonas unggul BSIP Biogen — tahan blast, adaptif lahan pasang surut.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Inpari Blas',
                    'description' => 'Inpari Blas — varietas padi inbrida sawah irigasi tahan penyakit blas dengan produktivitas optimal.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Inpari HDB',
                    'description' => 'Inpari HDB — varietas padi inbrida sawah irigasi tahan hawar daun bakteri (HDB).',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Inpari 40',
                    'description' => 'Inpari 40 — varietas padi inbrida sawah irigasi adaptif dengan umur panen genjah.',
                    'min_limit'   => 50,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Biosalin 1',
                    'description' => 'Biosalin 1 — varietas padi toleran salinitas untuk lahan rawa/pasang surut pesisir.',
                    'min_limit'   => 30,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Biosalin 2',
                    'description' => 'Biosalin 2 — varietas padi toleran salinitas generasi kedua, lebih adaptif pada berbagai tingkat salinitas.',
                    'min_limit'   => 30,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Bio Patenggang',
                    'description' => 'Bio Patenggang — varietas padi unggul toleran kekeringan, cocok untuk lahan sawah tadah hujan.',
                    'min_limit'   => 40,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Biobestari',
                    'description' => 'Biobestari — varietas padi premium BSIP Biogen dengan penampilan gabah bening dan rasa nasi istimewa.',
                    'min_limit'   => 40,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
                [
                    'name'        => 'Bioemas',
                    'description' => 'Bioemas — varietas padi aromatik unggul dengan tekstur nasi pulen dan aroma harum khas.',
                    'min_limit'   => 40,
                    'prices'      => ['BS' => 40000, 'FS' => 14000, 'SS' => 12000],
                ],
            ];

            foreach ($padiVarieties as $vd) {
                $variety = Variety::updateOrCreate(
                    ['name' => $vd['name'], 'commodity_id' => $padi->id],
                    [
                        'slug'          => Str::slug($vd['name']),
                        'description'   => $vd['description'],
                        'minimum_limit' => $vd['min_limit'],
                        'status'        => 'available',
                        'is_active'     => true,
                        'image_path'    => null,
                    ]
                );
                // Simpan harga pada variety data untuk digunakan SeedLotSeeder
                $variety->_prices = $vd['prices'];
            }
        }

        // ─── KEDELAI / EDAMAME ───────────────────────────────────────────────
        // Harga BS: 70.000/kg
        $kedelai = Commodity::where('slug', 'kedelai')->first();
        if ($kedelai) {
            $kedelaiVarieties = [
                [
                    'name'        => 'Biosoy 1',
                    'description' => 'Biosoy 1 — varietas kedelai/edamame unggul BSIP Biogen, biji besar, protein tinggi.',
                    'min_limit'   => 15,
                    'prices'      => ['BS' => 70000],
                ],
                [
                    'name'        => 'Biosoy 2',
                    'description' => 'Biosoy 2 — varietas kedelai unggul BSIP Biogen, tahan penyakit karat daun, produktif.',
                    'min_limit'   => 15,
                    'prices'      => ['BS' => 70000],
                ],
                [
                    'name'        => 'Biomax 1',
                    'description' => 'Biomax 1 — varietas edamame premium BSIP Biogen, biji hijau besar, kadar gula tinggi.',
                    'min_limit'   => 15,
                    'prices'      => ['BS' => 70000],
                ],
                [
                    'name'        => 'Biomax 2',
                    'description' => 'Biomax 2 — varietas edamame unggul BSIP Biogen generasi kedua, adaptif dataran menengah.',
                    'min_limit'   => 15,
                    'prices'      => ['BS' => 70000],
                ],
            ];

            foreach ($kedelaiVarieties as $vd) {
                Variety::updateOrCreate(
                    ['name' => $vd['name'], 'commodity_id' => $kedelai->id],
                    [
                        'slug'          => Str::slug($vd['name']),
                        'description'   => $vd['description'],
                        'minimum_limit' => $vd['min_limit'],
                        'status'        => 'available',
                        'is_active'     => true,
                        'image_path'    => null,
                    ]
                );
            }
        }

        // ─── SORGUM ──────────────────────────────────────────────────────────
        // Harga BS: 35.000/kg
        $sorgum = Commodity::where('slug', 'sorgum')->first();
        if ($sorgum) {
            $sorgumVarieties = [
                [
                    'name'        => 'Bioguma 1',
                    'description' => 'Bioguma 1 — varietas sorgum unggul BSIP Biogen, tinggi produksi biomassa untuk pakan ternak.',
                    'min_limit'   => 20,
                    'prices'      => ['BS' => 35000],
                ],
                [
                    'name'        => 'Bioguma 2',
                    'description' => 'Bioguma 2 — varietas sorgum manis BSIP Biogen, kadar brix tinggi, cocok untuk bioetanol.',
                    'min_limit'   => 20,
                    'prices'      => ['BS' => 35000],
                ],
                [
                    'name'        => 'Bioguma 3',
                    'description' => 'Bioguma 3 — varietas sorgum biji putih unggul, potensi hasil biji tinggi, toleran kekeringan.',
                    'min_limit'   => 20,
                    'prices'      => ['BS' => 35000],
                ],
            ];

            foreach ($sorgumVarieties as $vd) {
                Variety::updateOrCreate(
                    ['name' => $vd['name'], 'commodity_id' => $sorgum->id],
                    [
                        'slug'          => Str::slug($vd['name']),
                        'description'   => $vd['description'],
                        'minimum_limit' => $vd['min_limit'],
                        'status'        => 'available',
                        'is_active'     => true,
                        'image_path'    => null,
                    ]
                );
            }
        }

        // ─── CABAI ───────────────────────────────────────────────────────────
        // Harga BS: 3.000/gram (disimpan sebagai 3000, unit = gram)
        $cabai = Commodity::where('slug', 'cabai')->first();
        if ($cabai) {
            Variety::updateOrCreate(
                ['name' => 'Carvi Agrihorti', 'commodity_id' => $cabai->id],
                [
                    'slug'          => 'carvi-agrihorti',
                    'description'   => 'Carvi Agrihorti — varietas cabai merah keriting unggul Balithorti, buah panjang, produktif, tahan layu Fusarium.',
                    'minimum_limit' => 10,
                    'status'        => 'available',
                    'is_active'     => true,
                    'image_path'    => null,
                ]
            );
        }

        // ─── KENTANG ─────────────────────────────────────────────────────────
        // Harga Starter: 50.000/botol | G0: 2.000/umbi
        $kentang = Commodity::where('slug', 'kentang')->first();
        if ($kentang) {
            Variety::updateOrCreate(
                ['name' => 'Bio Granola', 'commodity_id' => $kentang->id],
                [
                    'slug'          => 'bio-granola',
                    'description'   => 'Bio Granola — varietas kentang unggul BSIP Biogen adaptif dataran tinggi, umbi besar seragam, tahan penyakit hawar daun (P. infestans).',
                    'minimum_limit' => 10,
                    'status'        => 'available',
                    'is_active'     => true,
                    'image_path'    => null,
                ]
            );
        }

        // ─── RUMPUT GAJAH ────────────────────────────────────────────────────
        // Harga BSM (Stek): 500/stek
        $rumputGajah = Commodity::where('slug', 'rumput-gajah')->first();
        if ($rumputGajah) {
            Variety::updateOrCreate(
                ['name' => 'Biograss Agrinak', 'commodity_id' => $rumputGajah->id],
                [
                    'slug'          => 'biograss-agrinak',
                    'description'   => 'Biograss Agrinak — varietas rumput gajah unggul BSIP Agrinak, produksi biomassa super tinggi, cocok untuk silase pakan ternak.',
                    'minimum_limit' => 50,
                    'status'        => 'available',
                    'is_active'     => true,
                    'image_path'    => null,
                ]
            );
        }

        // ─── ANGGREK ─────────────────────────────────────────────────────────
        // Phalaenopsis: Starter 50.000/botol | Dendrobium: Starter 33.000/botol
        $anggrek = Commodity::where('slug', 'anggrek')->first();
        if ($anggrek) {
            $anggrekVarieties = [
                [
                    'name'        => 'Phalaenopsis',
                    'description' => 'Phalaenopsis (Anggrek Bulan) — planlet anggrek bulan unggul hasil kultur jaringan BSIP Biogen, bunga besar, tahan lama.',
                    'min_limit'   => 5,
                ],
                [
                    'name'        => 'Dendrobium',
                    'description' => 'Dendrobium — planlet anggrek Dendrobium unggul hasil kultur jaringan BSIP Biogen, bunga lebat, siklus berbunga pendek.',
                    'min_limit'   => 5,
                ],
            ];

            foreach ($anggrekVarieties as $vd) {
                Variety::updateOrCreate(
                    ['name' => $vd['name'], 'commodity_id' => $anggrek->id],
                    [
                        'slug'          => 'anggrek-' . Str::slug($vd['name']),
                        'description'   => $vd['description'],
                        'minimum_limit' => $vd['min_limit'],
                        'status'        => 'available',
                        'is_active'     => true,
                        'image_path'    => null,
                    ]
                );
            }
        }

        // ─── JERUK ───────────────────────────────────────────────────────────
        $jeruk = Commodity::where('slug', 'jeruk')->first();
        if ($jeruk) {
            Variety::updateOrCreate(
                ['name' => 'Jeruk Keprok SoE', 'commodity_id' => $jeruk->id],
                [
                    'slug'          => 'jeruk-keprok-soe',
                    'description'   => 'Jeruk Keprok SoE — varietas jeruk unggul nasional asal NTT, rasa manis segar dengan aroma khas, warna oranye menarik.',
                    'minimum_limit' => 10,
                    'status'        => 'available',
                    'is_active'     => true,
                    'image_path'    => null,
                ]
            );
        }

        // ─── AREN ────────────────────────────────────────────────────────────
        $aren = Commodity::where('slug', 'aren')->first();
        if ($aren) {
            Variety::updateOrCreate(
                ['name' => 'Aren Akel', 'commodity_id' => $aren->id],
                [
                    'slug'          => 'aren-akel',
                    'description'   => 'Aren Akel — varietas aren unggul dengan potensi produksi nira tinggi, adaptif di berbagai ekosistem lahan kering.',
                    'minimum_limit' => 10,
                    'status'        => 'available',
                    'is_active'     => true,
                    'image_path'    => null,
                ]
            );
        }

        $total = Variety::count();
        $this->command->info("✅ VarietySeeder: {$total} varietas riil BSIP Biogen berhasil di-seed.");
    }
}