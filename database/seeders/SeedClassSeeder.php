<?php

namespace Database\Seeders;

use App\Models\SeedClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeedClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedClasses = [
            [
                'code' => 'BS',
                'name' => 'Breeder Seed',
                'description' => 'Breeder Seed (BS) - Seed class for direct sowing, measured in kilograms',
                'is_active' => true,
            ],
            [
                'code' => 'FS',
                'name' => 'Foundation Seed',
                'description' => 'Foundation Seed (FS) - Foundation seed class, measured in kilograms',
                'is_active' => true,
            ],
            [
                'code' => 'ST',
                'name' => 'Starter',
                'description' => 'Starter - Tissue culture plantlets, measured in bottles',
                'is_active' => true,
            ],
        ];

        foreach ($seedClasses as $seedClass) {
            SeedClass::updateOrCreate(
                ['code' => $seedClass['code']],
                $seedClass
            );
        }
    }
}