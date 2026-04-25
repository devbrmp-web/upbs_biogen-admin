<?php

namespace Database\Seeders;

use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StarterSeedLotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stClass = SeedClass::where('code', 'ST')->first();
        if (!$stClass) {
            $this->command->warn('Seed class ST (Starter) not found. Please run SeedClassSeeder first.');
            return;
        }

        $varieties = Variety::inRandomOrder()->take(5)->get();
        if ($varieties->isEmpty()) {
            $this->command->warn('No varieties available. Please run DemoDataSeeder or VarietySeeder first.');
            return;
        }

        foreach ($varieties as $variety) {
            SeedLot::create([
                'variety_id' => $variety->id,
                'seed_class_id' => $stClass->id,
                'lot_code' => 'ST-' . date('Y') . '-' . strtoupper(substr(Str::random(8), 0, 6)),
                'production_year' => (int) date('Y'),
                'quantity' => rand(10, 100), // number of bottles
                'unit' => 'bottle',
                'price_per_unit' => rand(50000, 150000),
                'description' => 'Starter seed lot (bottles) for ' . $variety->name,
                'is_sellable' => true,
            ]);
        }

        $this->command->info('StarterSeedLotSeeder: Created ' . $varieties->count() . ' ST (starter) seed lots.');
    }
}

