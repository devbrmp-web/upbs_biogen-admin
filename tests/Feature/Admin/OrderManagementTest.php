<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\SeedLot;
use App\Models\Commodity;
use App\Models\Variety;
use App\Models\SeedClass;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private Order $order;
    private SeedLot $seedLot;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles first
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create admin user with role_id = 2 (admin)
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role_id' => 2
        ]);

        // Create commodity, variety, seed class, and seed lot
        $commodity = Commodity::factory()->create();
        $variety = Variety::factory()->create([
            'commodity_id' => $commodity->id
        ]);
        
        // Use existing seed class or create one with unique code
        $this->seedClass = SeedClass::firstOrCreate(
            ['code' => 'TEST'],
            ['name' => 'Test Seed Class', 'code' => 'TEST']
        );
        
        $this->seedLot = SeedLot::factory()->create([
            'variety_id' => $variety->id,
            'seed_class_id' => $this->seedClass->id,
            'quantity' => 100,
            'price_per_unit' => 50000,
            'is_sellable' => true
        ]);

        // Create order with items
        $this->order = Order::factory()->create([
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'customer_email' => 'john@example.com',
            'customer_address' => 'Jakarta, Indonesia',
            'shipping_method' => 'delivery',
            'status' => 'awaiting_payment',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total_amount' => 115000
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'seed_lot_id' => $this->seedLot->id,
            'variety_id' => $this->seedLot->variety->id,
            'variety_name' => $this->seedLot->variety->name,
            'variety_sku' => 'VAR-TEST-001',
            'quantity' => 5,
            'unit_price' => 50000,
            'price_at_order' => 50000,
            'total_price' => 250000,
            'seed_class' => $this->seedClass->code,
        ]);

        Payment::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending'
        ]);

        Shipment::factory()->create([
            'order_id' => $this->order->id
        ]);
    }

    public function test_admin_can_access_orders_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.orders.index');
        $response->assertSee('Orders');
        $response->assertSee($this->order->order_code);
        $response->assertSee($this->order->customer_name);
    }

    public function test_guest_cannot_access_orders_index()
    {
        $response = $this->get(route('admin.orders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_order_details()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $this->order));

        $response->assertStatus(200);
        $response->assertViewIs('admin.orders.show');
        $response->assertSee($this->order->order_code);
        $response->assertSee($this->order->customer_name);
        $response->assertSee($this->order->customer_phone);
        $response->assertSee($this->seedLot->variety->name ?? 'Test Seed');
    }

    public function test_orders_index_filters_by_shipping_method()
    {
        // Create pickup order
        $pickupOrder = Order::factory()->create([
            'shipping_method' => 'pickup',
            'customer_name' => 'Jane Doe'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['shipping_method' => 'pickup']));

        $response->assertStatus(200);
        $response->assertSee($pickupOrder->customer_name);
        $response->assertDontSee($this->order->customer_name);
    }

    public function test_orders_index_filters_by_status()
    {
        // Create paid order
        $paidOrder = Order::factory()->create([
            'status' => 'paid',
            'customer_name' => 'Paid Customer'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'paid']));

        $response->assertStatus(200);
        $response->assertSee($paidOrder->customer_name);
        $response->assertDontSee($this->order->customer_name);
    }

    public function test_orders_index_searches_by_order_code()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['search' => $this->order->order_code]));

        $response->assertStatus(200);
        $response->assertSee($this->order->customer_name);
    }

    public function test_orders_index_searches_by_customer_name()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertSee($this->order->customer_name);
    }

    public function test_orders_index_filters_by_date_range()
    {
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', [
                'date_from' => $today,
                'date_to' => $tomorrow
            ]));

        $response->assertStatus(200);
        $response->assertSee($this->order->customer_name);
    }

    public function test_admin_can_update_order_status()
    {
        $this->order->update(['status' => 'paid']);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $this->order), [
                'status' => 'processing',
                'notes' => 'Order is being processed'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->order->refresh();
        $this->assertEquals('processing', $this->order->status);

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'table_name' => 'orders',
            'record_id' => $this->order->id,
            'action' => 'UPDATE',
            'user_id' => $this->admin->id
        ]);
    }

    public function test_admin_cannot_update_order_to_invalid_status()
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $this->order), [
                'status' => 'completed' // Invalid transition from awaiting_payment
            ]);

        $response->assertSessionHasErrors('status');
        
        $this->order->refresh();
        $this->assertEquals('awaiting_payment', $this->order->status);
    }

    public function test_admin_can_cancel_order()
    {
        $originalStock = $this->seedLot->quantity;

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $this->order), [
                'cancellation_reason' => 'Customer requested cancellation'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->status);

        // Check stock was restored
        $this->seedLot->refresh();
        $this->assertEquals($originalStock + 5, $this->seedLot->quantity); // +5 from order item quantity

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'table_name' => 'orders',
            'record_id' => $this->order->id,
            'action' => 'UPDATE',
            'user_id' => $this->admin->id
        ]);
    }

    public function test_admin_cannot_cancel_completed_order()
    {
        $this->order->update(['status' => 'completed']);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.cancel', $this->order), [
                'cancellation_reason' => 'Test cancellation'
            ]);

        $response->assertStatus(403);
        
        $this->order->refresh();
        $this->assertEquals('completed', $this->order->status);
    }

    public function test_admin_can_delete_cancelled_order()
    {
        $this->order->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $this->order), [
                'deletion_reason' => 'Test deletion for cancelled order'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', [
            'id' => $this->order->id
        ]);

        // Check audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'table_name' => 'orders',
            'record_id' => $this->order->id,
            'action' => 'DELETE',
            'user_id' => $this->admin->id
        ]);
    }

    public function test_admin_cannot_delete_non_cancelled_order()
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $this->order), [
                'deletion_reason' => 'Test deletion attempt for non-cancelled order'
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id
        ]);
    }

    public function test_orders_index_pagination_works()
    {
        // Create 15 more orders to test pagination
        Order::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        
        $orders = $response->viewData('orders');
        $this->assertTrue($orders->hasPages());
    }

    public function test_order_status_badges_display_correctly()
    {
        $statuses = [
            'awaiting_payment' => 'warning',
            'paid' => 'success',
            'processing' => 'info',
            'pickup_ready' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];

        foreach ($statuses as $status => $expectedColor) {
            $order = Order::factory()->create(['status' => $status]);
            
            $response = $this->actingAs($this->admin)
                ->get(route('admin.orders.index'));

            $response->assertSee("bg-{$expectedColor}-subtle");
        }
    }

    public function test_shipping_method_badges_display_correctly()
    {
        $pickupOrder = Order::factory()->create(['shipping_method' => 'pickup']);
        $deliveryOrder = Order::factory()->create(['shipping_method' => 'delivery']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index'));

        $response->assertSee('Pickup at BRMP');
        $response->assertSee('Delivery');
        $response->assertSee('bg-info-subtle'); // pickup badge
        $response->assertSee('bg-primary-subtle'); // delivery badge
    }
}


