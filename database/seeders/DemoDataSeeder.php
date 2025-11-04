<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\SeedLot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1) Create at least 5 commodities
        $commodities = [
            'Rice',
            'Corn',
            'Soybean',
            'Peanut',
            'Cassava',
        ];

        $commodityModels = [];
        foreach ($commodities as $name) {
            $commodityModels[$name] = Commodity::firstOrCreate([
                'name' => $name,
            ], [
                'is_active' => true,
            ]);
        }

        // 2) Create 5 varieties linked to commodities
        $varietySpecs = [
            ['name' => 'IR64 Premium', 'commodity' => 'Rice', 'price' => 65000, 'min_limit' => 10],
            ['name' => 'Bisi 18 Hybrid', 'commodity' => 'Corn', 'price' => 75000, 'min_limit' => 12],
            ['name' => 'Grobogan High Yield', 'commodity' => 'Soybean', 'price' => 55000, 'min_limit' => 8],
            ['name' => 'Kancil Early', 'commodity' => 'Peanut', 'price' => 45000, 'min_limit' => 6],
            ['name' => 'Ketan Wangi', 'commodity' => 'Rice', 'price' => 70000, 'min_limit' => 10],
        ];

        $varieties = [];
        foreach ($varietySpecs as $spec) {
            $commodity = $commodityModels[$spec['commodity']];
            $bs = rand(100, 500);
            $fs = rand(50, 300);
            $varieties[] = Variety::firstOrCreate([
                'name' => $spec['name'],
                'commodity_id' => $commodity->id,
            ], [
                'sku' => strtoupper('VAR-' . substr(md5($spec['name']), 0, 8)),
                'description' => $spec['name'] . ' — demo data variety created from DemoDataSeeder.',
                'price' => $spec['price'],
                'stock_bs_kg' => $bs,
                'stock_fs_kg' => $fs,
                'stock' => $bs + $fs,
                'minimum_limit' => $spec['min_limit'],
                'status' => 'available',
                'planlet' => rand(0, 50),
            ]);
        }

        // 3) Create at least 10 seed lots across these varieties (BS/FS classes)
        $bsClass = SeedClass::where('code', 'BS')->first();
        $fsClass = SeedClass::where('code', 'FS')->first();

        if (!$bsClass || !$fsClass) {
            $this->command->warn('Seed classes BS/FS not found. Please run SeedClassSeeder first.');
            return;
        }

        $seedLotsToCreate = 10;
        $created = 0;
        while ($created < $seedLotsToCreate) {
            foreach ($varieties as $variety) {
                if ($created >= $seedLotsToCreate) break;

                // Create BS lot
                SeedLot::create([
                    'variety_id' => $variety->id,
                    'seed_class_id' => $bsClass->id,
                    'lot_code' => 'BS-' . date('Y') . '-' . strtoupper(substr(Str::random(8), 0, 6)),
                    'production_year' => (int) date('Y'),
                    'quantity' => rand(20, 150),
                    'unit' => 'kg',
                    'price_per_unit' => rand(40000, 80000),
                    'description' => 'Demo BS seed lot for ' . $variety->name,
                    'is_sellable' => true,
                ]);
                $created++;
                if ($created >= $seedLotsToCreate) break;

                // Create FS lot
                SeedLot::create([
                    'variety_id' => $variety->id,
                    'seed_class_id' => $fsClass->id,
                    'lot_code' => 'FS-' . date('Y') . '-' . strtoupper(substr(Str::random(8), 0, 6)),
                    'production_year' => (int) date('Y'),
                    'quantity' => rand(30, 200),
                    'unit' => 'kg',
                    'price_per_unit' => rand(30000, 60000),
                    'description' => 'Demo FS seed lot for ' . $variety->name,
                    'is_sellable' => true,
                ]);
                $created++;
            }
        }

        $this->command->info('DemoDataSeeder: Created ' . count($commodityModels) . ' commodities, ' . count($varieties) . ' varieties, and at least 10 seed lots.');

        // Optional: Import CSV if present
        $csvPath = '/mnt/data/Rekapitulasi Pelayanan Publik 2025 - Benih Terstandar.csv';
        if (File::exists($csvPath)) {
            $this->command->info('CSV file detected. Running importer...');
            Artisan::call('wub:import:seed-stock', ['--file' => $csvPath]);
            $this->command->info(Artisan::output());
        } else {
            $this->command->warn('CSV file not found at ' . $csvPath . '. Skipping import.');
        }
    }
}
