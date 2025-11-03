<?php

namespace Database\Seeders;

use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanletSeedLotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plClass = SeedClass::where('code', 'PL')->first();
        if (!$plClass) {
            $this->command->warn('Seed class PL (Planlet) not found. Please run SeedClassSeeder first.');
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
                'seed_class_id' => $plClass->id,
                'lot_code' => 'PL-' . date('Y') . '-' . strtoupper(substr(Str::random(8), 0, 6)),
                'production_year' => (int) date('Y'),
                'quantity' => rand(10, 100), // number of bottles
                'unit' => 'botol',
                'price_per_unit' => rand(50000, 150000),
                'description' => 'Planlet seed lot (bottles) for ' . $variety->name,
                'is_sellable' => true,
            ]);
        }

        $this->command->info('PlanletSeedLotSeeder: Created ' . $varieties->count() . ' PL (planlet) seed lots.');
    }
}

