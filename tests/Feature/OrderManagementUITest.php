<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Variety;
use App\Models\SeedLot;
use App\Models\SeedClass;
use App\Models\Commodity;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSeedClasses;

class OrderManagementUITest extends TestCase
{
    use RefreshDatabase, CreatesSeedClasses;

    protected User $admin;
    protected Variety $variety;
    protected Order $order;
    protected SeedLot $seedLot;
    protected SeedClass $bsSeedClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Create seed classes first
        $this->createSeedClasses();
        $this->bsSeedClass = SeedClass::where('code', 'BS')->first();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role_id' => 2, // Admin role
        ]);

        // Create commodity and variety for testing
        $commodity = Commodity::factory()->create();
        $this->variety = Variety::factory()->create([
            'name' => 'Test Variety',
            'commodity_id' => $commodity->id,
        ]);

        // Create seed lot with stock
        $this->seedLot = SeedLot::factory()->create([
            'variety_id' => $this->variety->id,
            'seed_class_id' => $this->bsSeedClass->id,
            'quantity' => 100,
            'price_per_unit' => 50000,
            'is_sellable' => true,
        ]);

        // Create order
        $this->order = Order::factory()->create([
            'customer_name' => 'John Doe',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'customer_address' => 'Test Address',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'subtotal' => 50000,
            'total_amount' => 50000,
        ]);

        // Create order item linked to seed lot
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'seed_lot_id' => $this->seedLot->id,
            'variety_id' => $this->variety->id,
            'variety_name' => $this->variety->name,
            'unit_price' => 50000,
            'quantity' => 1,
            'total_price' => 50000,
        ]);

        // Create payment and shipment
        Payment::factory()->create(['order_id' => $this->order->id]);
        Shipment::factory()->create(['order_id' => $this->order->id]);
    }

    /** @test */
    public function admin_can_view_orders_list()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee($this->order->order_code);
        $response->assertSee($this->order->customer_name);
        $response->assertSee('Pickup at BRMP');
        $response->assertSee('Awaiting Payment');
    }

    /** @test */
    public function admin_can_filter_orders_by_status()
    {
        $this->actingAs($this->admin);

        // Create orders with different statuses
        $paidOrder = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_name' => 'Paid Customer',
        ]);

        $completedOrder = Order::factory()->create([
            'status' => Order::STATUS_COMPLETED,
            'customer_name' => 'Completed Customer',
        ]);

        // Filter by paid status
        $response = $this->get(route('admin.orders.index', ['status' => Order::STATUS_PAID]));

        $response->assertStatus(200);
        $response->assertSee('Paid Customer');
        $response->assertDontSee('John Doe'); // Original awaiting payment order
        $response->assertDontSee('Completed Customer');
    }

    /** @test */
    public function admin_can_filter_orders_by_shipping_method()
    {
        $this->actingAs($this->admin);

        // Create delivery order
        $deliveryOrder = Order::factory()->create([
            'shipping_method' => Order::SHIPPING_DELIVERY,
            'customer_name' => 'Delivery Customer',
        ]);

        // Filter by pickup method
        $response = $this->get(route('admin.orders.index', ['shipping_method' => Order::SHIPPING_PICKUP]));

        $response->assertStatus(200);
        $response->assertSee('John Doe'); // Original pickup order
        $response->assertDontSee('Delivery Customer');
    }

    /** @test */
    public function admin_can_search_orders_by_order_code()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index', ['search' => $this->order->order_code]));

        $response->assertStatus(200);
        $response->assertSee($this->order->order_code);
        $response->assertSee($this->order->customer_name);
    }

    /** @test */
    public function admin_can_search_orders_by_customer_name()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertSee($this->order->customer_name);
        $response->assertSee($this->order->order_code);
    }

    /** @test */
    public function admin_can_view_order_details()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.show', $this->order));

        $response->assertStatus(200);
        $response->assertSee($this->order->order_code);
        $response->assertSee($this->order->customer_name);
        $response->assertSee($this->order->customer_phone);
        $response->assertSee($this->order->customer_address);
        $response->assertSee($this->variety->name);
        $response->assertSee('Pickup at BRMP');
    }

    /** @test */
    public function admin_can_update_order_status()
    {
        $this->actingAs($this->admin);

        $response = $this->patch(route('admin.orders.update-status', $this->order), [
            'status' => Order::STATUS_PAID,
            'notes' => 'Payment confirmed by admin',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert order status was updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_PAID,
        ]);

        // Assert audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Order::class,
            'model_id' => $this->order->id,
            'action' => AuditLog::ACTION_UPDATE,
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
        ]);
    }

    /** @test */
    public function admin_cannot_update_to_invalid_status()
    {
        $this->actingAs($this->admin);

        // Try to update from awaiting_payment directly to shipped (invalid transition)
        $response = $this->patch(route('admin.orders.update-status', $this->order), [
            'status' => Order::STATUS_SHIPPED,
            'notes' => 'Invalid transition',
        ]);

        $response->assertSessionHasErrors(['status']);

        // Assert order status was not updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]);
    }

    /** @test */
    public function admin_can_cancel_order()
    {
        $this->actingAs($this->admin);

        $response = $this->patch(route('admin.orders.cancel', $this->order), [
            'cancellation_reason' => 'Customer requested cancellation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert order was cancelled
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);

        // Assert stock was restored
        $this->seedLot->refresh();
        $this->assertEquals(101, $this->seedLot->quantity); // Original 100 + 1 restored

        // Assert audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Order::class,
            'model_id' => $this->order->id,
            'action' => AuditLog::ACTION_UPDATE,
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
        ]);
    }

    /** @test */
    public function admin_can_delete_cancelled_order()
    {
        $this->actingAs($this->admin);

        // First cancel the order
        $this->order->update(['status' => Order::STATUS_CANCELLED]);

        $response = $this->delete(route('admin.orders.destroy', $this->order), [
            'deletion_reason' => 'Test deletion reason'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert order was soft deleted or removed
        $this->assertDatabaseMissing('orders', [
            'id' => $this->order->id,
        ]);
    }

    /** @test */
    public function admin_cannot_delete_non_cancelled_order()
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('admin.orders.destroy', $this->order));

        $response->assertSessionHasErrors();

        // Assert order still exists
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]);
    }

    /** @test */
    public function order_management_page_shows_correct_action_buttons()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
        
        // Should see action buttons
        $response->assertSee('View Details');
        $response->assertSee('Update Status');
        $response->assertSee('Cancel Order');
    }

    /** @test */
    public function order_status_update_modal_shows_valid_transitions()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
        
        // Should see status update modal elements
        $response->assertSee('Update Order Status');
        $response->assertSee('Notes (Optional)');
    }

    /** @test */
    public function order_cancellation_modal_shows_reason_field()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
        
        // Should see cancellation modal elements
        $response->assertSee('Cancel Order');
        $response->assertSee('Cancellation Reason');
        $response->assertSee('This will cancel the order and restore stock quantities');
    }

    /** @test */
    public function orders_are_paginated_correctly()
    {
        $this->actingAs($this->admin);

        // Create many orders to test pagination
        Order::factory()->count(25)->create();

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
        
        // Should see pagination links
        $response->assertSee('Next');
        $response->assertSee('Previous');
    }

    /** @test */
    public function guest_cannot_access_admin_order_management()
    {
        $response = $this->get(route('admin.orders.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function non_admin_user_cannot_access_order_management()
    {
        $regularUser = User::factory()->create([
            'role_id' => null, // No role assigned (not in allowed roles 1,2)
        ]);

        $this->actingAs($regularUser);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function order_filters_persist_in_url()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.orders.index', [
            'status' => Order::STATUS_PAID,
            'shipping_method' => Order::SHIPPING_PICKUP,
            'search' => 'test',
        ]));

        $response->assertStatus(200);
        
        // URL should contain filter parameters
        $this->assertEquals(Order::STATUS_PAID, request('status'));
        $this->assertEquals(Order::SHIPPING_PICKUP, request('shipping_method'));
        $this->assertEquals('test', request('search'));
    }
}
