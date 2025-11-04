<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Variety;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Commodity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Mail\ShippingInstructions;

class OrderManagementEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Variety $variety;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user with minimal data
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2, // Admin role
        ]);

        // SeedClass data is already created in TestCase setUp

        // Create commodity first
        $commodity = Commodity::create([
            'name' => 'Test Commodity',
            'slug' => 'test-commodity',
            'is_active' => true,
        ]);

        // Create variety manually to avoid factory conflicts
        $this->variety = Variety::create([
            'commodity_id' => $commodity->id,
            'name' => 'Test Variety',
            'sku' => 'TEST-VAR-001',
            'description' => 'Test variety for email testing',
            'price' => 50000,
            'stock' => 100,
            'stock_bs_kg' => 50,
            'stock_fs_kg' => 50,
            'is_active' => true,
        ]);

        // Create existing order for setUp
        $this->createTestOrder();
    }

    private function createTestOrder(): void
    {
        $order = Order::create([
            'order_code' => 'TEST-ORDER-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'customer@test.com',
            'customer_phone' => '081234567890',
            'customer_address' => 'Test Address',
            'shipping_method' => Order::SHIPPING_PICKUP,
            'subtotal' => 50000,
            'shipping_cost' => 0,
            'total_amount' => 50000,
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'variety_id' => $this->variety->id,
            'variety_name' => $this->variety->name,
            'variety_sku' => $this->variety->sku,
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 50000,
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'status' => Payment::STATUS_PENDING,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'shipping_method' => Order::SHIPPING_PICKUP,
            'courier_name' => null, // No courier for pickup
            'courier_service' => 'pickup',
            'shipping_cost' => 0,
            'status' => Shipment::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function it_sends_order_confirmation_email_during_checkout(): void
    {
        Mail::fake();

        // Test checkout process with email
        try {
            $response = $this->post('/client/checkout/process', [
                'customer_name' => 'Jane Doe',
                'customer_address' => '123 Test Street, Test City',
                'customer_phone' => '081234567890',
                'customer_email' => 'jane@test.com',
                'shipping_method' => Order::SHIPPING_DELIVERY,
                'courier_name' => 'Pos Indonesia',
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
                'terms_accepted' => true,
                'delivery_coordination_acknowledged' => true,
                'items' => [
                    [
                        'variety_id' => $this->variety->id,
                        'quantity' => 2,
                        'seed_lot_id' => null,
                    ]
                ],
            ]);
            
            // Check if checkout was successful (should redirect)
            $response->assertStatus(302);
            $response->assertSessionHasNoErrors();
            
        } catch (\Exception $e) {
            // Re-throw exception without debug dumps to keep test output clean
            throw $e;
        }

        // Find the created order
        $order = Order::where('customer_email', 'jane@test.com')->first();

        $this->assertNotNull($order, 'Order should be created');
        $this->assertEquals('Jane Doe', $order->customer_name);
        $this->assertEquals('jane@test.com', $order->customer_email);
        $this->assertEquals(Order::SHIPPING_DELIVERY, $order->shipping_method);

        // Assert that the order confirmation email was queued (since OrderConfirmation implements ShouldQueue)
        Mail::assertQueued(OrderConfirmation::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    /** @test */
    public function it_sends_status_update_email_when_order_status_changes(): void
    {
        Mail::fake();

        $order = Order::first();
        // Ensure order has customer email for email notification and set to paid status
        $order->update([
            'customer_email' => 'customer@test.com',
            'status' => Order::STATUS_PAID
        ]);
        
        $this->actingAs($this->admin);

        // Update order status
        $response = $this->patch("/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING,
        ]);

        $response->assertRedirect();

        // Assert status update email was queued (since OrderStatusUpdate implements ShouldQueue)
        Mail::assertQueued(OrderStatusUpdate::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    /** @test */
    public function it_sends_shipping_instructions_when_order_is_shipped(): void
    {
        Mail::fake();

        $order = Order::first();
        // Set order to delivery_coordination status so it can transition to shipped
        $order->update([
            'customer_email' => 'customer@test.com',
            'status' => Order::STATUS_DELIVERY_COORDINATION
        ]);
        
        $this->actingAs($this->admin);

        // Update order to shipped status
        $response = $this->patch("/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertRedirect();

        // Assert shipping instructions email was queued
        Mail::assertQueued(ShippingInstructions::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }
}
