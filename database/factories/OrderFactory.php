<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(50000, 500000);
        $shippingCost = $this->faker->numberBetween(10000, 50000);
        $totalAmount = $subtotal + $shippingCost;
        
        return [
            'order_code' => 'ORD-' . strtoupper($this->faker->bothify('??####')),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->optional(0.8)->safeEmail(),
            'customer_address' => $this->faker->address(),
            'shipping_method' => $this->faker->randomElement(['pickup', 'delivery']),
            'shipping_cost' => $shippingCost,
            'courier_name' => $this->faker->optional()->randomElement(['Pos Indonesia', 'Indah Cargo']),
            'courier_service' => $this->faker->optional()->word(),
            'status' => 'awaiting_payment',
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the order is awaiting payment.
     */
    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'awaiting_payment',
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is for pickup.
     */
    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_method' => 'pickup',
            'shipping_cost' => 0,
            'courier_name' => null,
            'courier_service' => null,
        ]);
    }

    /**
     * Indicate that the order is for delivery.
     */
    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_method' => 'delivery',
            'shipping_cost' => $this->faker->numberBetween(10000, 50000),
            'courier_name' => $this->faker->randomElement(['Pos Indonesia', 'Indah Cargo']),
            'courier_service' => $this->faker->randomElement(['Regular', 'Express', 'Same Day']),
        ]);
    }
}
