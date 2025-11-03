<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => $this->faker->randomElement([
                Payment::METHOD_VA_BCA,
                Payment::METHOD_VA_BNI,
                Payment::METHOD_QRIS,
                Payment::METHOD_BANK_TRANSFER
            ]),
            'gateway_transaction_id' => 'TXN-' . strtoupper($this->faker->bothify('??########')),
            'gateway_reference' => 'REF-' . strtoupper($this->faker->bothify('??########')),
            'pnbp_receipt_no' => null,
            'amount' => $this->faker->numberBetween(50000, 500000),
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
            'expires_at' => now()->addHours(24),
            'gateway_response' => null,
            'signature_verification' => true,
            'payment_ip' => $this->faker->ipv4(),
            'notes' => $this->faker->optional()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
            'pnbp_receipt_no' => 'PNBP-' . strtoupper($this->faker->bothify('??########')),
        ]);
    }

    /**
     * Indicate that the payment is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
        ]);
    }

    /**
     * Indicate that the payment is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_EXPIRED,
            'expires_at' => now()->subHours(1),
        ]);
    }
}
