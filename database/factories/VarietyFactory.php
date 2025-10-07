<?php

namespace Database\Factories;

use App\Models\Commodity;
use App\Models\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Variety>
 */
class VarietyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Variety::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $varietyNames = [
            'IR64', 'Ciherang', 'Inpari 32', 'Mekongga', 'Cisadane',
            'Bisi 18', 'Pioneer P27', 'NK 212', 'Pertiwi 3',
            'Varietas Unggul 1', 'Varietas Unggul 2', 'Varietas Unggul 3',
            'Pandan Wangi', 'Rojolele', 'Basmati', 'Jasmine', 'Arborio',
            'Calrose', 'Bomba', 'Carnaroli', 'Glutinous', 'Black Rice',
            'Red Rice', 'Brown Rice', 'Wild Rice', 'Forbidden Rice',
            'Sona Masoori', 'Ponni', 'Basmati 370', 'Super Basmati',
            'Koshihikari', 'Akitakomachi', 'Sasanishiki', 'Hitomebore'
        ];

        $baseName = $this->faker->unique()->randomElement($varietyNames);
        $uniqueName = $baseName . ' ' . $this->faker->numberBetween(1, 999);

        return [
            'commodity_id' => Commodity::factory(),
            'name' => $uniqueName,
            'sku' => strtoupper($this->faker->unique()->bothify('VAR-###-???')),
            'description' => $this->faker->sentence(8),
            'price' => $this->faker->numberBetween(5000, 50000),
            'stock_bs_kg' => $this->faker->numberBetween(0, 1000),
            'stock_fs_kg' => $this->faker->numberBetween(0, 500),
            'minimum_limit' => $this->faker->numberBetween(1, 50),
        ];
    }

    /**
     * Indicate that the variety has available stock (tersedia).
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_bs_kg' => $this->faker->numberBetween(100, 1000),
            'stock_fs_kg' => $this->faker->numberBetween(50, 500),
        ]);
    }

    /**
     * Indicate that the variety needs restocking (restock).
     */
    public function needsRestock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_bs_kg' => $this->faker->numberBetween(1, 49),
            'stock_fs_kg' => $this->faker->numberBetween(1, 24),
        ]);
    }

    /**
     * Indicate that the variety is out of stock (habis).
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_bs_kg' => 0,
            'stock_fs_kg' => 0,
        ]);
    }

    /**
     * Indicate that the variety has high stock.
     */
    public function highStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_bs_kg' => $this->faker->numberBetween(500, 1000),
            'stock_fs_kg' => $this->faker->numberBetween(300, 500),
        ]);
    }

    /**
     * Indicate that the variety has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_bs_kg' => $this->faker->numberBetween(0, 10),
            'stock_fs_kg' => $this->faker->numberBetween(0, 5),
        ]);
    }
}