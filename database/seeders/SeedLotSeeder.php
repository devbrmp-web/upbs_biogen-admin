<?php

namespace Database\Seeders;

use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Database\Seeder;

class SeedLotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bsSeedClass = SeedClass::where('code', 'BS')->first();
        $fsSeedClass = SeedClass::where('code', 'FS')->first();

        if (!$bsSeedClass || !$fsSeedClass) {
            $this->command->warn('Seed classes not found. Please run SeedClassSeeder first.');
            return;
        }

        $varieties = Variety::all();

        if ($varieties->isEmpty()) {
            $this->command->warn('No varieties found. Please run VarietySeeder first.');
            return;
        }

        $seedLots = [];
        $currentYear = date('Y');
        $previousYear = $currentYear - 1;

        foreach ($varieties->take(20) as $index => $variety) {
            // Create BS (Breeder Seed) lot
            $seedLots[] = [
                'variety_id' => $variety->id,
                'seed_class_id' => $bsSeedClass->id,
                'lot_code' => 'BS-' . $currentYear . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'production_year' => $currentYear,
                'quantity' => rand(50, 500),
                'unit' => 'kg',
                'price_per_unit' => rand(40000, 80000),
                'is_sellable' => true,
                'description' => 'High quality breeder seed lot for ' . $variety->name,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Create FS (Foundation Seed) lot
            $seedLots[] = [
                'variety_id' => $variety->id,
                'seed_class_id' => $fsSeedClass->id,
                'lot_code' => 'FS-' . $currentYear . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'production_year' => $currentYear,
                'quantity' => rand(100, 800),
                'unit' => 'kg',
                'price_per_unit' => rand(30000, 60000),
                'is_sellable' => true,
                'description' => 'Foundation seed lot for ' . $variety->name,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Create some previous year lots for variety
            if ($index < 10) {
                $seedLots[] = [
                    'variety_id' => $variety->id,
                    'seed_class_id' => $bsSeedClass->id,
                    'lot_code' => 'BS-' . $previousYear . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'production_year' => $previousYear,
                    'quantity' => rand(20, 200),
                    'unit' => 'kg',
                    'price_per_unit' => rand(35000, 70000),
                    'is_sellable' => rand(0, 1) == 1,
                    'description' => 'Previous year breeder seed lot for ' . $variety->name,
                    'created_at' => now()->subYear(),
                    'updated_at' => now()->subYear(),
                ];
            }
        }

        // Insert in chunks for better performance
        collect($seedLots)->chunk(50)->each(function ($chunk) {
            SeedLot::insert($chunk->toArray());
        });

        $this->command->info('Created ' . count($seedLots) . ' seed lots successfully.');
    }
}