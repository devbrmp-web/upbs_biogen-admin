<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class OrderBulkActionsTest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->createSeedClasses();
    }

    /** @test */
    public function admin_can_bulk_cancel_orders_and_restore_stock()
    {
        $this->actingAs($this->admin);

        $variety = Variety::factory()->create();
        $bs = SeedClass::where('code', 'BS')->first();
        $lot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $bs->id,
            'quantity' => 100,
            'unit' => 'kg'
        ]);

        // Create two orders in processing (eligible to cancel)
        $order1 = Order::factory()->create(['status' => Order::STATUS_PROCESSING]);
        $order2 = Order::factory()->create(['status' => Order::STATUS_PROCESSING]);

        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'variety_id' => $variety->id,
            'seed_lot_id' => $lot->id,
            'quantity' => 10,
            'unit_price' => 50000,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'variety_id' => $variety->id,
            'seed_lot_id' => $lot->id,
            'quantity' => 15,
            'unit_price' => 50000,
        ]);

        // Decrement lot to simulate reservation
        $lot->decrement('quantity', 25);

        $response = $this->postJson(route('admin.orders.bulk-cancel'), [
            'ids' => [$order1->id, $order2->id]
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(Order::STATUS_CANCELLED, $order1->fresh()->status);
        $this->assertEquals(Order::STATUS_CANCELLED, $order2->fresh()->status);
        $this->assertEquals(100, $lot->fresh()->quantity, 'Stock should be restored to original total');
    }
}
