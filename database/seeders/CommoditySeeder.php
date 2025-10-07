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
                'description' => 'Rice is the staple food crop in Indonesia, providing the main source of carbohydrates for the majority of the population.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Corn',
                'slug' => 'corn',
                'description' => 'Corn is an important food crop and animal feed source, widely cultivated across Indonesia.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Soybean',
                'slug' => 'soybean',
                'description' => 'Soybean is a crucial protein source and raw material for various food products like tofu and tempeh.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Peanut',
                'slug' => 'peanut',
                'description' => 'Peanut is an important legume crop providing protein and oil, commonly used in Indonesian cuisine.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Mung Bean',
                'slug' => 'mung-bean',
                'description' => 'Mung bean is a legume crop used for food and green manure, important for crop rotation systems.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Chili',
                'slug' => 'chili',
                'description' => 'Chili is an essential spice crop in Indonesian cuisine, providing both flavor and economic value.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Tomato',
                'slug' => 'tomato',
                'description' => 'Tomato is a versatile vegetable crop used in various culinary applications and food processing.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Eggplant',
                'slug' => 'eggplant',
                'description' => 'Eggplant is a popular vegetable crop in Indonesian cuisine, known for its versatility in cooking.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Cucumber',
                'slug' => 'cucumber',
                'description' => 'Cucumber is a refreshing vegetable crop commonly used in salads and traditional Indonesian dishes.',
                'image_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Lettuce',
                'slug' => 'lettuce',
                'description' => 'Lettuce is a leafy green vegetable crop increasingly popular in modern Indonesian cuisine.',
                'image_url' => null,
                'is_active' => true,
            ],
        ];

        foreach ($commodities as $commodity) {
            Commodity::create($commodity);
        }
    }
}