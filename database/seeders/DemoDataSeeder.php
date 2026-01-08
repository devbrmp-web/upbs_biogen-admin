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
     * 
     * Note: Stock and price data are now managed via seed_lots table.
     * This seeder creates demo varieties and their corresponding seed lots.
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

        // 2) Create 5 demo varieties linked to commodities
        // Note: price/stock data is now in seed_lots, not varieties
        $varietySpecs = [
            ['name' => 'IR64 Premium', 'commodity' => 'Rice', 'min_limit' => 10, 'bs_price' => 65000, 'fs_price' => 55000],
            ['name' => 'Bisi 18 Hybrid', 'commodity' => 'Corn', 'min_limit' => 12, 'bs_price' => 75000, 'fs_price' => 60000],
            ['name' => 'Grobogan High Yield', 'commodity' => 'Soybean', 'min_limit' => 8, 'bs_price' => 55000, 'fs_price' => 45000],
            ['name' => 'Kancil Early', 'commodity' => 'Peanut', 'min_limit' => 6, 'bs_price' => 45000, 'fs_price' => 35000],
            ['name' => 'Ketan Wangi', 'commodity' => 'Rice', 'min_limit' => 10, 'bs_price' => 70000, 'fs_price' => 58000],
        ];

        $varieties = [];
        foreach ($varietySpecs as $spec) {
            $commodity = $commodityModels[$spec['commodity']];
            $varieties[] = [
                'model' => Variety::firstOrCreate([
                    'name' => $spec['name'],
                    'commodity_id' => $commodity->id,
                ], [
                    'slug' => Str::slug($spec['name']),
                    'sku' => strtoupper('VAR-' . substr(md5($spec['name']), 0, 8)),
                    'description' => $spec['name'] . ' — demo data variety created from DemoDataSeeder.',
                    'minimum_limit' => $spec['min_limit'],
                    'status' => 'available',
                    'is_active' => true,
                ]),
                'bs_price' => $spec['bs_price'],
                'fs_price' => $spec['fs_price'],
            ];
        }

        // 3) Create seed lots for demo varieties (BS/FS classes)
        $bsClass = SeedClass::where('code', 'BS')->first();
        $fsClass = SeedClass::where('code', 'FS')->first();

        if (!$bsClass || !$fsClass) {
            $this->command->warn('Seed classes BS/FS not found. Please run SeedClassSeeder first.');
            return;
        }

        $currentYear = (int) date('Y');
        $lotCounter = 1;

        foreach ($varieties as $index => $varietyData) {
            $variety = $varietyData['model'];
            
            // Skip if this variety already has seed lots (avoid duplicates)
            if ($variety->seedLots()->count() > 0) {
                continue;
            }

            // Create BS lot with specified price
            SeedLot::create([
                'variety_id' => $variety->id,
                'seed_class_id' => $bsClass->id,
                'lot_code' => 'LOT/' . $currentYear . '/' . str_pad($lotCounter++, 3, '0', STR_PAD_LEFT),
                'production_year' => $currentYear,
                'quantity' => rand(100, 500), // Integer quantity
                'unit' => 'kg',
                'price_per_unit' => $varietyData['bs_price'], // Integer price in Rupiah
                'description' => 'Demo BS seed lot for ' . $variety->name,
                'is_sellable' => true,
            ]);

            // Create FS lot with specified price
            SeedLot::create([
                'variety_id' => $variety->id,
                'seed_class_id' => $fsClass->id,
                'lot_code' => 'LOT/' . $currentYear . '/' . str_pad($lotCounter++, 3, '0', STR_PAD_LEFT),
                'production_year' => $currentYear,
                'quantity' => rand(150, 500), // Integer quantity
                'unit' => 'kg',
                'price_per_unit' => $varietyData['fs_price'], // Integer price in Rupiah
                'description' => 'Demo FS seed lot for ' . $variety->name,
                'is_sellable' => true,
            ]);
        }

        $this->command->info('DemoDataSeeder: Created ' . count($commodityModels) . ' commodities, ' . count($varieties) . ' varieties with seed lots.');

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
