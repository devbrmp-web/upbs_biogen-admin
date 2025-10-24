<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommoditySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commodities = [
            [
                'name' => 'Rice',
                'slug' => 'rice',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Corn',
                'slug' => 'corn',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Soybean',
                'slug' => 'soybean',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Peanut',
                'slug' => 'peanut',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Mung Bean',
                'slug' => 'mung-bean',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Chili',
                'slug' => 'chili',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Tomato',
                'slug' => 'tomato',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Eggplant',
                'slug' => 'eggplant',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Cucumber',
                'slug' => 'cucumber',
                'image_path' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Lettuce',
                'slug' => 'lettuce',
                'image_path' => null,
                'is_active' => true,
            ],
        ];

        foreach ($commodities as $commodity) {
            Commodity::create($commodity);
        }
    }
}