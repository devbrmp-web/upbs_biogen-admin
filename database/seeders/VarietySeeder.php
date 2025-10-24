<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VarietySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $varieties = [
            // Rice varieties
            'Rice' => [
                [
                    'name' => 'IR64',
                    'description' => 'High-yielding rice variety with good grain quality and disease resistance.',
                    'stock' => 1500.000,
                    'stock_bs_kg' => 800.000,
                    'stock_fs_kg' => 700.000,
                    'minimum_limit' => 50.000,
                ],
                [
                    'name' => 'Ciherang',
                    'description' => 'Popular Indonesian rice variety with excellent taste and adaptability.',
                    'stock' => 2000.000,
                    'stock_bs_kg' => 1200.000,
                    'stock_fs_kg' => 800.000,
                    'minimum_limit' => 75.000,
                ],
                [
                    'name' => 'Inpari 32',
                    'description' => 'Modern rice variety with high productivity and pest resistance.',
                    'stock' => 1200.000,
                    'stock_bs_kg' => 600.000,
                    'stock_fs_kg' => 600.000,
                    'minimum_limit' => 40.000,
                ],
            ],
            // Corn varieties
            'Corn' => [
                [
                    'name' => 'Pioneer P21',
                    'description' => 'High-yielding hybrid corn variety suitable for various growing conditions.',
                    'stock' => 800.000,
                    'stock_bs_kg' => 400.000,
                    'stock_fs_kg' => 400.000,
                    'minimum_limit' => 25.000,
                ],
                [
                    'name' => 'Bisi 18',
                    'description' => 'Premium hybrid corn with excellent grain quality and disease tolerance.',
                    'stock' => 1000.000,
                    'stock_bs_kg' => 500.000,
                    'stock_fs_kg' => 500.000,
                    'minimum_limit' => 30.000,
                ],
                [
                    'name' => 'NK 212',
                    'description' => 'Drought-tolerant corn variety with consistent performance.',
                    'stock' => 600.000,
                    'stock_bs_kg' => 300.000,
                    'stock_fs_kg' => 300.000,
                    'minimum_limit' => 20.000,
                ],
            ],
            // Soybean varieties
            'Soybean' => [
                [
                    'name' => 'Grobogan',
                    'description' => 'High-yielding soybean variety with large seed size and good protein content.',
                    'stock' => 500.000,
                    'stock_bs_kg' => 250.000,
                    'stock_fs_kg' => 250.000,
                    'minimum_limit' => 15.000,
                ],
                [
                    'name' => 'Anjasmoro',
                    'description' => 'Popular soybean variety with excellent adaptability and yield stability.',
                    'stock' => 400.000,
                    'stock_bs_kg' => 200.000,
                    'stock_fs_kg' => 200.000,
                    'minimum_limit' => 12.000,
                ],
            ],
            // Peanut varieties
            'Peanut' => [
                [
                    'name' => 'Kancil',
                    'description' => 'Early maturing peanut variety with good oil content.',
                    'stock' => 300.000,
                    'stock_bs_kg' => 150.000,
                    'stock_fs_kg' => 150.000,
                    'minimum_limit' => 10.000,
                ],
                [
                    'name' => 'Gajah',
                    'description' => 'Large-seeded peanut variety suitable for direct consumption.',
                    'stock' => 250.000,
                    'stock_bs_kg' => 125.000,
                    'stock_fs_kg' => 125.000,
                    'minimum_limit' => 8.000,
                ],
            ],
            // Mung Bean varieties
            'Mung Bean' => [
                [
                    'name' => 'Vima 1',
                    'description' => 'High-yielding mung bean variety with uniform pod maturity.',
                    'stock' => 200.000,
                    'stock_bs_kg' => 100.000,
                    'stock_fs_kg' => 100.000,
                    'minimum_limit' => 5.000,
                ],
                [
                    'name' => 'Sriti',
                    'description' => 'Early maturing mung bean variety with good disease resistance.',
                    'stock' => 180.000,
                    'stock_bs_kg' => 90.000,
                    'stock_fs_kg' => 90.000,
                    'minimum_limit' => 5.000,
                ],
            ],
            // Chili varieties
            'Chili' => [
                [
                    'name' => 'Cabe Rawit',
                    'description' => 'Very hot small chili variety popular in Indonesian cuisine.',
                    'stock' => 50.000,
                    'stock_bs_kg' => 25.000,
                    'stock_fs_kg' => 25.000,
                    'minimum_limit' => 2.000,
                ],
                [
                    'name' => 'Cabe Merah Besar',
                    'description' => 'Large red chili variety with moderate heat level.',
                    'stock' => 75.000,
                    'stock_bs_kg' => 40.000,
                    'stock_fs_kg' => 35.000,
                    'minimum_limit' => 3.000,
                ],
                [
                    'name' => 'Cabe Keriting',
                    'description' => 'Curly chili variety with unique shape and good flavor.',
                    'stock' => 60.000,
                    'stock_bs_kg' => 30.000,
                    'stock_fs_kg' => 30.000,
                    'minimum_limit' => 2.500,
                ],
            ],
            // Tomato varieties
            'Tomato' => [
                [
                    'name' => 'Permata',
                    'description' => 'High-quality tomato variety with excellent fruit characteristics.',
                    'stock' => 100.000,
                    'stock_bs_kg' => 50.000,
                    'stock_fs_kg' => 50.000,
                    'minimum_limit' => 3.000,
                ],
                [
                    'name' => 'Intan',
                    'description' => 'Disease-resistant tomato variety with good shelf life.',
                    'stock' => 120.000,
                    'stock_bs_kg' => 60.000,
                    'stock_fs_kg' => 60.000,
                    'minimum_limit' => 4.000,
                ],
            ],
            // Eggplant varieties
            'Eggplant' => [
                [
                    'name' => 'Terong Ungu',
                    'description' => 'Purple eggplant variety with tender flesh and mild flavor.',
                    'stock' => 80.000,
                    'stock_bs_kg' => 40.000,
                    'stock_fs_kg' => 40.000,
                    'minimum_limit' => 2.500,
                ],
                [
                    'name' => 'Terong Hijau',
                    'description' => 'Green eggplant variety popular in traditional Indonesian dishes.',
                    'stock' => 70.000,
                    'stock_bs_kg' => 35.000,
                    'stock_fs_kg' => 35.000,
                    'minimum_limit' => 2.000,
                ],
            ],
            // Cucumber varieties
            'Cucumber' => [
                [
                    'name' => 'Timun Suri',
                    'description' => 'Sweet cucumber variety commonly used for fresh consumption.',
                    'stock' => 90.000,
                    'stock_bs_kg' => 45.000,
                    'stock_fs_kg' => 45.000,
                    'minimum_limit' => 3.000,
                ],
                [
                    'name' => 'Timun Hijau',
                    'description' => 'Green cucumber variety with crisp texture and refreshing taste.',
                    'stock' => 85.000,
                    'stock_bs_kg' => 42.500,
                    'stock_fs_kg' => 42.500,
                    'minimum_limit' => 2.500,
                ],
            ],
            // Lettuce varieties
            'Lettuce' => [
                [
                    'name' => 'Selada Hijau',
                    'description' => 'Green lettuce variety with tender leaves and mild flavor.',
                    'stock' => 40.000,
                    'stock_bs_kg' => 20.000,
                    'stock_fs_kg' => 20.000,
                    'minimum_limit' => 1.500,
                ],
                [
                    'name' => 'Selada Merah',
                    'description' => 'Red lettuce variety with attractive color and nutritional value.',
                    'stock' => 35.000,
                    'stock_bs_kg' => 17.500,
                    'stock_fs_kg' => 17.500,
                    'minimum_limit' => 1.000,
                ],
            ],
        ];

        foreach ($varieties as $commodityName => $varietyList) {
            $commodity = Commodity::where('name', $commodityName)->first();
            
            if ($commodity) {
                foreach ($varietyList as $varietyData) {
                    $varietyData['commodity_id'] = $commodity->id;
                    $varietyData['slug'] = Str::slug($varietyData['name']);
                    $varietyData['sku'] = 'SKU-' . strtoupper(Str::random(8));
                    $varietyData['price'] = rand(50000, 500000); // Random price between 50k-500k IDR
                    $varietyData['image_path'] = null; // No image initially
                    
                    Variety::create($varietyData);
                }
            }
        }
    }
}