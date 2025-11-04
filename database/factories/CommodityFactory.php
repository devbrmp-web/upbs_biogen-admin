<?php

namespace Database\Factories;

use App\Models\Commodity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commodity>
 */
class CommodityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Commodity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Rice',
            'Corn',
            'Wheat',
            'Soybean',
            'Peanut',
            'Mung Bean',
            'Chili',
            'Tomato',
            'Cabbage',
            'Spinach',
            'Carrot',
            'Potato',
            'Sweet Potato',
            'Cassava',
            'Onion',
            'Garlic',
            'Cucumber',
            'Eggplant',
            'Okra',
            'Green Bean',
            'Long Bean',
            'Bitter Gourd',
            'Bottle Gourd',
            'Pumpkin',
            'Watermelon',
            'Melon',
            'Papaya',
            'Banana',
            'Mango',
            'Avocado'
        ]);
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 1000),
        ];
    }

    /**
     * Indicate that the commodity is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the commodity is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}