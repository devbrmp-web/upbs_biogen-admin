<?php

namespace Database\Factories;

use App\Models\SeedLot;
use App\Models\Variety;
use App\Models\SeedClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeedLot>
 */
class SeedLotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeedLot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = ['kg', 'gram', 'ton', 'bottle', 'piece'];
        $unit = $this->faker->randomElement($units);
        
        // Adjust quantity based on unit
        $quantity = match($unit) {
            'kg' => $this->faker->numberBetween(10, 1000),
            'gram' => $this->faker->numberBetween(100, 5000),
            'ton' => $this->faker->numberBetween(1, 50),
            'bottle' => $this->faker->numberBetween(10, 500),
            'piece' => $this->faker->numberBetween(50, 1000),
            default => $this->faker->numberBetween(10, 1000),
        };

        // Adjust price based on unit
        $pricePerUnit = match($unit) {
            'kg' => $this->faker->numberBetween(1000, 10000),
            'gram' => $this->faker->numberBetween(10, 100),
            'ton' => $this->faker->numberBetween(500000, 2000000),
            'bottle' => $this->faker->numberBetween(5000, 25000),
            'piece' => $this->faker->numberBetween(100, 1000),
            default => $this->faker->numberBetween(1000, 10000),
        };

        return [
            'variety_id' => Variety::factory(),
            'seed_class_id' => SeedClass::factory(),
            'lot_code' => 'LOT-' . strtoupper($this->faker->unique()->bothify('??##')),
            'production_year' => $this->faker->numberBetween(2020, 2024),
            'quantity' => $quantity,
            'unit' => $unit,
            'price_per_unit' => $pricePerUnit,
            'is_sellable' => $this->faker->boolean(80), // 80% chance of being sellable
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a sellable seed lot.
     */
    public function sellable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sellable' => true,
        ]);
    }

    /**
     * Create a non-sellable seed lot.
     */
    public function notSellable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sellable' => false,
        ]);
    }

    /**
     * Create a seed lot with kg unit.
     */
    public function withKgUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => 'kg',
            'quantity' => $this->faker->numberBetween(10, 1000),
            'price_per_unit' => $this->faker->numberBetween(1000, 10000),
        ]);
    }

    /**
     * Create a seed lot with bottle unit.
     */
    public function withBottleUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => 'bottle',
            'quantity' => $this->faker->numberBetween(10, 500),
            'price_per_unit' => $this->faker->numberBetween(5000, 25000),
        ]);
    }

    /**
     * Create a seed lot for a specific variety.
     */
    public function forVariety(Variety $variety): static
    {
        return $this->state(fn (array $attributes) => [
            'variety_id' => $variety->id,
        ]);
    }

    /**
     * Create a seed lot for a specific seed class.
     */
    public function forSeedClass(SeedClass $seedClass): static
    {
        return $this->state(fn (array $attributes) => [
            'seed_class_id' => $seedClass->id,
        ]);
    }

    /**
     * Create a seed lot with specific production year.
     */
    public function productionYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'production_year' => $year,
        ]);
    }
}