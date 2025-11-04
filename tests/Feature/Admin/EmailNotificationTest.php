<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        Mail::fake();
    }

    public function test_order_confirmation_email_can_be_queued(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'customer@example.com'
        ]);

        // Simulate order confirmation email sending
        Mail::to($order->customer_email)->send(new OrderConfirmation($order));

        Mail::assertQueued(OrderConfirmation::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    public function test_order_status_update_email_is_queued_on_status_change(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_email' => 'customer@example.com'
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'Order is being processed'
            ]);

        $response->assertRedirect();

        Mail::assertQueued(OrderStatusUpdate::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id &&
                   $mail->previousStatus === Order::STATUS_PAID &&
                   $mail->newStatus === Order::STATUS_PROCESSING &&
                   $mail->notes === 'Order is being processed';
        });
    }

    public function test_order_status_update_email_is_not_queued_when_order_has_no_email(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_email' => null
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'Order is being processed'
            ]);

        $response->assertRedirect();

        Mail::assertNothingQueued();
    }

    public function test_bulk_status_update_queues_emails_to_orders_with_email(): void
    {
        $orderWithEmail = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_email' => 'customer1@example.com'
        ]);

        $orderWithoutEmail = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_email' => null
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-update-status'), [
                'ids' => [$orderWithEmail->id, $orderWithoutEmail->id],
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'Bulk processing'
            ]);

        $response->assertRedirect();

        // Verify orders were updated
        $this->assertEquals(Order::STATUS_PROCESSING, $orderWithEmail->fresh()->status);
        $this->assertEquals(Order::STATUS_PROCESSING, $orderWithoutEmail->fresh()->status);

        // Only one email should be queued (for the order with email)
        Mail::assertQueued(OrderStatusUpdate::class, 1);
        
        Mail::assertQueued(OrderStatusUpdate::class, function ($mail) use ($orderWithEmail) {
            return $mail->order->id === $orderWithEmail->id;
        });
    }

    public function test_order_confirmation_email_contains_correct_data(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'customer@example.com',
            'customer_name' => 'John Doe',
            'order_code' => 'ORD-2024-001'
        ]);

        $mailable = new OrderConfirmation($order);

        $this->assertEquals($order->id, $mailable->order->id);
        $this->assertEquals('Order Confirmation - ' . $order->order_code, $mailable->envelope()->subject);
    }

    public function test_order_status_update_email_contains_correct_data(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'customer@example.com',
            'order_code' => 'ORD-2024-001'
        ]);

        $mailable = new OrderStatusUpdate($order, 'paid', 'processing', 'Your order is being processed');

        $this->assertEquals($order->id, $mailable->order->id);
        $this->assertEquals('paid', $mailable->previousStatus);
        $this->assertEquals('processing', $mailable->newStatus);
        $this->assertEquals('Your order is being processed', $mailable->notes);
        $this->assertEquals('Order Status Update - ' . $order->order_code, $mailable->envelope()->subject);
    }

    public function test_order_status_is_updated_regardless_of_email(): void
    {
        // This test ensures that order status updates work even without email
        $order = Order::factory()->create([
            'status' => Order::STATUS_PAID,
            'customer_email' => null
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PROCESSING,
                'notes' => 'Order is being processed'
            ]);

        // The request should succeed and status should be updated
        $response->assertRedirect();
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);
        
        // No email should be queued
        Mail::assertNothingQueued();
    }
}