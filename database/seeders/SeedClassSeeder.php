<?php

namespace Database\Seeders;

use App\Models\SeedClass;
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
                'name' => 'Benih Sebar',
                'description' => 'Benih yang diproduksi dari benih pokok (FS) dan digunakan untuk produksi komersial.',
                'is_active' => true,
            ],
            [
                'code' => 'FS',
                'name' => 'Benih Pokok',
                'description' => 'Benih yang diproduksi dari benih sumber (NS) dan digunakan untuk memproduksi benih sebar (BS).',
                'is_active' => true,
            ],
            [
                'code' => 'NS',
                'name' => 'Benih Sumber',
                'description' => 'Benih yang diproduksi langsung dari varietas unggul dan digunakan untuk memproduksi benih pokok (FS).',
                'is_active' => true,
            ],
        ];

        foreach ($seedClasses as $seedClass) {
            SeedClass::create($seedClass);
        }
    }
}