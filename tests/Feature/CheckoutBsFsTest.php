<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutBsFsTest extends TestCase
{
    use RefreshDatabase;

    private function createSeedClass(string $code, string $name): SeedClass
    {
        return SeedClass::factory()->create([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createVariety(int $price = 10000): Variety
    {
        return Variety::factory()->available()->create([
            'price' => $price,
        ]);
    }

    private function createSeedLot(Variety $variety, SeedClass $class, int $quantityKg, float $pricePerKg): SeedLot
    {
        return SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $class->id,
            'quantity' => $quantityKg,
            'unit' => 'kg',
            'price_per_unit' => $pricePerKg,
            'is_sellable' => true,
        ]);
    }

    public function test_fs_requires_quantity_multiple_of_5(): void
    {
        $fs = $this->createSeedClass('FS', 'Benih Sebar');
        $variety = $this->createVariety(12000);
        $lot = $this->createSeedLot($variety, $fs, 100, 12000.0);

        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 7, 'seed_lot_id' => $lot->id],
            ],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_fs_total_price_and_stock_decrement(): void
    {
        $fs = $this->createSeedClass('FS', 'Benih Sebar');
        $variety = $this->createVariety(12000);
        $lot = $this->createSeedLot($variety, $fs, 100, 12000.0);

        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 10, 'seed_lot_id' => $lot->id], // 2 kelompok 5kg
            ],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);
        $response->assertStatus(200);

        $order = \App\Models\Order::query()->first();
        $item = $order->items->first();

        $this->assertEquals(10, (int) $item->quantity);
        $expectedTotal = (int) (12000 * 5 * 2); // harga per kg * 5kg * kelompok
        $this->assertEquals($expectedTotal, (int) $item->total_price);

        $lot->refresh();
        $this->assertEquals(90, (int) $lot->quantity); // 100 - 10
    }

    public function test_bs_total_price_and_stock_decrement(): void
    {
        $bs = $this->createSeedClass('BS', 'Breeder Seed');
        $variety = $this->createVariety(15000);
        $lot = $this->createSeedLot($variety, $bs, 50, 15000.0);

        $payload = [
            'customer_name' => 'Tester',
            'customer_address' => 'Alamat',
            'customer_phone' => '+628111111111',
            'customer_email' => 'tester@example.com',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'items' => [
                ['variety_id' => $variety->id, 'quantity' => 3, 'seed_lot_id' => $lot->id],
            ],
            'payment_method' => 'bank_transfer',
            'terms_accepted' => true,
        ];

        $response = $this->postJson('/api/orders/checkout', $payload);
        $response->assertStatus(200);

        $order = \App\Models\Order::query()->first();
        $item = $order->items->first();

        $this->assertEquals(3, (int) $item->quantity);
        $expectedTotal = (int) (15000 * 3);
        $this->assertEquals($expectedTotal, (int) $item->total_price);

        $lot->refresh();
        $this->assertEquals(47, (int) $lot->quantity); // 50 - 3
    }
}
