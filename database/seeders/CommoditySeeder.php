<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommoditySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Komoditas riil berdasarkan Buku Saku BSIP Biogen 2024 & Dokumen PNBP 2026.
     */
    public function run(): void
    {
        $commodities = [
            [
                'name'       => 'Padi',
                'slug'       => 'padi',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Kedelai',
                'slug'       => 'kedelai',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Sorgum',
                'slug'       => 'sorgum',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Cabai',
                'slug'       => 'cabai',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Kentang',
                'slug'       => 'kentang',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Rumput Gajah',
                'slug'       => 'rumput-gajah',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Anggrek',
                'slug'       => 'anggrek',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Jeruk',
                'slug'       => 'jeruk',
                'image_path' => null,
                'is_active'  => true,
            ],
            [
                'name'       => 'Aren',
                'slug'       => 'aren',
                'image_path' => null,
                'is_active'  => true,
            ],
        ];

        foreach ($commodities as $data) {
            Commodity::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('✅ CommoditySeeder: 7 komoditas riil BSIP Biogen berhasil di-seed.');
    }
}