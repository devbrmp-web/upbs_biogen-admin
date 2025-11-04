<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\SeedLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(10000, 100000);
        $totalPrice = $quantity * $unitPrice;
        
        return [
            'order_id' => Order::factory(),
            'seed_lot_id' => SeedLot::factory(),
            'variety_id' => null, // Will be set by relationship
            'variety_name' => $this->faker->words(3, true),
            'variety_sku' => 'VAR-' . strtoupper($this->faker->bothify('??##')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'price_at_order' => $unitPrice,
            'total_price' => $totalPrice,
            'seed_class' => $this->faker->randomElement(['BS', 'FS', 'CS']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
