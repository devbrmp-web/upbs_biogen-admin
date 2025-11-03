<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'shipping_method' => $this->faker->randomElement(['pickup', 'delivery']),
            'status' => Shipment::STATUS_PENDING,
            'courier_name' => $this->faker->randomElement([
                Shipment::COURIER_POS_INDONESIA,
                Shipment::COURIER_INDAH_CARGO,
                null
            ]),
            'tracking_number' => null,
            'ready_for_pickup_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the shipment is ready for pickup.
     */
    public function readyForPickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Shipment::STATUS_READY_FOR_PICKUP,
            'ready_for_pickup_at' => now(),
        ]);
    }

    /**
     * Indicate that the shipment has been shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Shipment::STATUS_SHIPPED,
            'shipped_at' => now(),
            'tracking_number' => 'TRK-' . strtoupper($this->faker->bothify('??########')),
        ]);
    }

    /**
     * Indicate that the shipment has been delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Shipment::STATUS_DELIVERED,
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
            'tracking_number' => 'TRK-' . strtoupper($this->faker->bothify('??########')),
        ]);
    }
}
