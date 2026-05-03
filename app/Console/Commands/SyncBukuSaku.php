<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Variety;
use App\Models\Commodity;
use App\Models\SeedLot;
use App\Models\SeedClass;
use Illuminate\Support\Str;

class SyncBukuSaku extends Command
{
    protected $signature = 'upbs:sync-agri';
    protected $description = 'Sync variety metadata from buku_saku.txt to the database';

    // Map the text file names to their database equivalents
    protected $nameMapping = [
        'Bioni 63 Ciherang Agritan' => 'Bioni 63 Ciherang',
        'Biosalin 1 Agritan' => 'Biosalin 1',
        'Biosalin 2 Agritan' => 'Biosalin 2',
        'Bio Patenggang Agritan' => 'Bio Patenggang',
        'Biobestari Agritan' => 'Biobestari',
        'Bioemas Agritan' => 'Bioemas',
        'Bioguma 1 Agritan' => 'Bioguma 1',
        'Bioguma 2 Agritan' => 'Bioguma 2',
        'Bioguma 3 Agritan' => 'Bioguma 3',
    ];

    public function handle()
    {
        $file = 'C:\\laragon\\www\\WUB\\upbs_biogen-client\\storage\\app\\public\\buku_saku.txt';
        
        if (!file_exists($file)) {
            $this->error("File buku_saku.txt not found at {$file}");
            return Command::FAILURE;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $headers = str_getcsv(array_shift($lines));
        
        $this->info("Starting sync process...");
        
        $cabaiCommodity = Commodity::firstOrCreate(
            ['name' => 'Cabai Merah Besar'],
            ['slug' => 'cabai-merah-besar', 'is_active' => true]
        );

        $bsClass = SeedClass::where('code', 'BS')->first();

        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), '-');
            } elseif (count($data) > count($headers)) {
                $data = array_slice($data, 0, count($headers));
            }
            $row = array_combine($headers, $data);
            
            // Normalize name
            $textName = $row['nama_varietas'];
            $dbName = $this->nameMapping[$textName] ?? $textName;
            
            $commodityId = null;
            if (Str::contains($dbName, 'Agrihorti', true)) {
                $commodityId = $cabaiCommodity->id;
            } else {
                $komoditas = strtolower($row['komoditas']);
                $commodity = Commodity::where('slug', $komoditas)->first();
                if ($commodity) {
                    $commodityId = $commodity->id;
                }
            }
            
            $keunggulan = $row['keunggulan'] !== '-' ? $row['keunggulan'] : '';
            $anjuran = $row['anjuran_tanam'] !== '-' ? $row['anjuran_tanam'] : '';
            $descriptionSummary = trim($keunggulan . "\n" . $anjuran);
            if (empty($descriptionSummary)) {
                $descriptionSummary = null;
            }

            $updateData = [
                'decree_number' => $row['nomor_keputusan'] !== '-' ? $row['nomor_keputusan'] : null,
                'decree_date' => $row['tanggal_keputusan'] !== '-' ? $row['tanggal_keputusan'] : null,
                'origin' => $row['asal'] !== '-' ? $row['asal'] : null,
                'planting_age' => $row['umur_tanaman_hari'] !== '-' ? $row['umur_tanaman_hari'] : null,
                'yield_potential' => $row['potensi_hasil'] !== '-' ? $row['potensi_hasil'] : null,
                'average_yield' => $row['rata_rata_hasil'] !== '-' ? $row['rata_rata_hasil'] : null,
                'primary_trait' => $row['tekstur_nasi'] !== '-' ? $row['tekstur_nasi'] : null,
                'pest_resistance' => $row['ketahanan_hama'] !== '-' ? $row['ketahanan_hama'] : null,
                'disease_resistance' => $row['ketahanan_penyakit'] !== '-' ? $row['ketahanan_penyakit'] : null,
                'description_summary' => $descriptionSummary,
            ];

            if ($commodityId) {
                $updateData['commodity_id'] = $commodityId;
            }

            $variety = Variety::where('name', $dbName)->first();
            
            if (!$variety) {
                // It's a new variety
                $updateData['slug'] = Str::slug($dbName);
                $updateData['status'] = 'available';
                $updateData['is_active'] = true;
                $variety = Variety::create(array_merge(['name' => $dbName], $updateData));
                $this->info("Created new Variety: {$dbName}");
            } else {
                // Existing variety
                $variety->update($updateData);
                $this->info("Updated Variety: {$dbName}");
            }

            // SeedLot & Pricing Logic
            if (Str::contains($dbName, 'Agrihorti', true)) {
                SeedLot::updateOrCreate(
                    [
                        'variety_id' => $variety->id,
                        'seed_class_id' => $bsClass?->id ?? 1,
                    ],
                    [
                        'lot_code' => 'LOT-' . strtoupper(Str::slug($dbName)) . '-BS-' . time(),
                        'price_per_unit' => 1500,
                        'unit' => 'gram',
                        'quantity' => 0,
                        'is_sellable' => true,
                        'production_year' => date('Y'),
                        'harvest_date' => date('Y-m-d'),
                    ]
                );
                $this->line("  -> Upserted SeedLot (Agrihorti logic)");
            } else {
                if ($variety->seedLots()->count() === 0) {
                    SeedLot::create([
                        'variety_id' => $variety->id,
                        'seed_class_id' => $bsClass?->id ?? 1,
                        'lot_code' => 'LOT-' . strtoupper(Str::slug($dbName)) . '-BS-' . time(),
                        'price_per_unit' => 0,
                        'unit' => 'kg',
                        'quantity' => 0,
                        'is_sellable' => true,
                        'production_year' => date('Y'),
                        'harvest_date' => date('Y-m-d'),
                    ]);
                    $this->line("  -> Created Default SeedLot");
                }
            }
        }

        $this->info("Sync complete!");
        return Command::SUCCESS;
    }
}
