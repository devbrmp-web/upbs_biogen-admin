<?php

namespace Database\Factories;

use App\Models\SeedClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeedClass>
 */
class SeedClassFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeedClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seedClasses = [
            ['name' => 'Basic Seed', 'code' => 'BS'],
            ['name' => 'Foundation Seed', 'code' => 'FS'],
            ['name' => 'Planlet', 'code' => 'PL'],
            ['name' => 'Certified Seed', 'code' => 'CS'],
        ];

        $seedClass = $this->faker->randomElement($seedClasses);

        return [
            'name' => $seedClass['name'],
            'code' => $seedClass['code'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a Basic Seed class.
     */
    public function basicSeed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic Seed',
            'code' => 'BS',
        ]);
    }

    /**
     * Create a Foundation Seed class.
     */
    public function foundationSeed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Foundation Seed',
            'code' => 'FS',
        ]);
    }

    /**
     * Create a Planlet class.
     */
    public function planlet(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Planlet',
            'code' => 'PL',
        ]);
    }

    /**
     * Create a Certified Seed class.
     */
    public function certifiedSeed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Certified Seed',
            'code' => 'CS',
        ]);
    }
}