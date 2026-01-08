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
     * 
     * Note: Stock and price data are now managed via seed_lots table.
     * This seeder only creates base variety records.
     */
    public function run(): void
    {
        $varieties = [
            // Rice varieties
            'Rice' => [
                [
                    'name' => 'IR64',
                    'description' => 'High-yielding rice variety with good grain quality and disease resistance.',
                    'minimum_limit' => 50,
                ],
                [
                    'name' => 'Ciherang',
                    'description' => 'Popular Indonesian rice variety with excellent taste and adaptability.',
                    'minimum_limit' => 75,
                ],
                [
                    'name' => 'Inpari 32',
                    'description' => 'Modern rice variety with high productivity and pest resistance.',
                    'minimum_limit' => 40,
                ],
            ],
            // Corn varieties
            'Corn' => [
                [
                    'name' => 'Pioneer P21',
                    'description' => 'High-yielding hybrid corn variety suitable for various growing conditions.',
                    'minimum_limit' => 25,
                ],
                [
                    'name' => 'Bisi 18',
                    'description' => 'Premium hybrid corn with excellent grain quality and disease tolerance.',
                    'minimum_limit' => 30,
                ],
                [
                    'name' => 'NK 212',
                    'description' => 'Drought-tolerant corn variety with consistent performance.',
                    'minimum_limit' => 20,
                ],
            ],
            // Soybean varieties
            'Soybean' => [
                [
                    'name' => 'Grobogan',
                    'description' => 'High-yielding soybean variety with large seed size and good protein content.',
                    'minimum_limit' => 15,
                ],
                [
                    'name' => 'Anjasmoro',
                    'description' => 'Popular soybean variety with excellent adaptability and yield stability.',
                    'minimum_limit' => 12,
                ],
            ],
            // Peanut varieties
            'Peanut' => [
                [
                    'name' => 'Kancil',
                    'description' => 'Early maturing peanut variety with good oil content.',
                    'minimum_limit' => 10,
                ],
                [
                    'name' => 'Gajah',
                    'description' => 'Large-seeded peanut variety suitable for direct consumption.',
                    'minimum_limit' => 8,
                ],
            ],
            // Mung Bean varieties
            'Mung Bean' => [
                [
                    'name' => 'Vima 1',
                    'description' => 'High-yielding mung bean variety with uniform pod maturity.',
                    'minimum_limit' => 5,
                ],
                [
                    'name' => 'Sriti',
                    'description' => 'Early maturing mung bean variety with good disease resistance.',
                    'minimum_limit' => 5,
                ],
            ],
            // Chili varieties
            'Chili' => [
                [
                    'name' => 'Cabe Rawit',
                    'description' => 'Very hot small chili variety popular in Indonesian cuisine.',
                    'minimum_limit' => 2,
                ],
                [
                    'name' => 'Cabe Merah Besar',
                    'description' => 'Large red chili variety with moderate heat level.',
                    'minimum_limit' => 3,
                ],
                [
                    'name' => 'Cabe Keriting',
                    'description' => 'Curly chili variety with unique shape and good flavor.',
                    'minimum_limit' => 2,
                ],
            ],
            // Tomato varieties
            'Tomato' => [
                [
                    'name' => 'Permata',
                    'description' => 'High-quality tomato variety with excellent fruit characteristics.',
                    'minimum_limit' => 3,
                ],
                [
                    'name' => 'Intan',
                    'description' => 'Disease-resistant tomato variety with good shelf life.',
                    'minimum_limit' => 4,
                ],
            ],
            // Eggplant varieties
            'Eggplant' => [
                [
                    'name' => 'Terong Ungu',
                    'description' => 'Purple eggplant variety with tender flesh and mild flavor.',
                    'minimum_limit' => 2,
                ],
                [
                    'name' => 'Terong Hijau',
                    'description' => 'Green eggplant variety popular in traditional Indonesian dishes.',
                    'minimum_limit' => 2,
                ],
            ],
            // Cucumber varieties
            'Cucumber' => [
                [
                    'name' => 'Timun Suri',
                    'description' => 'Sweet cucumber variety commonly used for fresh consumption.',
                    'minimum_limit' => 3,
                ],
                [
                    'name' => 'Timun Hijau',
                    'description' => 'Green cucumber variety with crisp texture and refreshing taste.',
                    'minimum_limit' => 2,
                ],
            ],
            // Lettuce varieties
            'Lettuce' => [
                [
                    'name' => 'Selada Hijau',
                    'description' => 'Green lettuce variety with tender leaves and mild flavor.',
                    'minimum_limit' => 1,
                ],
                [
                    'name' => 'Selada Merah',
                    'description' => 'Red lettuce variety with attractive color and nutritional value.',
                    'minimum_limit' => 1,
                ],
            ],
        ];

        foreach ($varieties as $commodityName => $varietyList) {
            $commodity = Commodity::where('name', $commodityName)->first();
            
            if ($commodity) {
                foreach ($varietyList as $varietyData) {
                    Variety::firstOrCreate(
                        [
                            'name' => $varietyData['name'],
                            'commodity_id' => $commodity->id,
                        ],
                        [
                            'slug' => Str::slug($varietyData['name']),
                            'sku' => 'SKU-' . strtoupper(Str::random(8)),
                            'description' => $varietyData['description'],
                            'minimum_limit' => $varietyData['minimum_limit'],
                            'status' => 'available',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('VarietySeeder: Created varieties successfully.');
    }
}