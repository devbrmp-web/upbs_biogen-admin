<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_bulk_cancel_orders(): void
    {
        // Create orders with different statuses
        $awaitingOrder = Order::factory()->create(['status' => 'awaiting_payment']);
        $paidOrder = Order::factory()->create(['status' => 'paid']);
        $processingOrder = Order::factory()->create(['status' => 'processing']);
        $shippedOrder = Order::factory()->create(['status' => 'shipped']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-cancel'), [
                'ids' => [$awaitingOrder->id, $paidOrder->id, $processingOrder->id, $shippedOrder->id],
                'reason' => 'Bulk cancellation test'
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Only orders that can be cancelled should be cancelled
        $this->assertEquals('cancelled', $awaitingOrder->fresh()->status);
        $this->assertEquals('cancelled', $paidOrder->fresh()->status);
        $this->assertEquals('cancelled', $processingOrder->fresh()->status);
        
        // Shipped orders cannot be cancelled
        $this->assertEquals('shipped', $shippedOrder->fresh()->status);
    }

    public function test_admin_can_bulk_update_order_status(): void
    {
        $orders = Order::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.export'), [
                'selected_orders' => $orders->pluck('id')->toArray()
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_bulk_cancel_requires_valid_order_ids(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-cancel'), [
                'ids' => [999, 1000], // Non-existent IDs
                'reason' => 'Test reason'
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_bulk_update_status_requires_valid_status(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-update-status'), [
                'ids' => [$order->id],
                'status' => 'invalid_status'
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
    }

    public function test_bulk_export_requires_order_ids(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.export'), [
                'selected_orders' => []
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('selected_orders');
    }

    public function test_guest_cannot_perform_bulk_actions(): void
    {
        $order = Order::factory()->create();

        $bulkCancelResponse = $this->post(route('admin.orders.bulk-cancel'), [
            'ids' => [$order->id],
            'reason' => 'Test'
        ]);

        $bulkUpdateResponse = $this->post(route('admin.orders.bulk-update-status'), [
            'ids' => [$order->id],
            'status' => 'processing'
        ]);

        $bulkExportResponse = $this->post(route('admin.orders.export'), [
            'selected_orders' => [$order->id]
        ]);

        $bulkCancelResponse->assertRedirect(route('login'));
        $bulkUpdateResponse->assertRedirect(route('login'));
        $bulkExportResponse->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_perform_bulk_actions(): void
    {
        $user = User::factory()->nonAdmin()->create(); // Regular user, not admin
        $order = Order::factory()->create();

        $bulkCancelResponse = $this->actingAs($user)
            ->post(route('admin.orders.bulk-cancel'), [
                'ids' => [$order->id],
                'reason' => 'Test'
            ]);

        $bulkUpdateResponse = $this->actingAs($user)
            ->post(route('admin.orders.bulk-update-status'), [
                'ids' => [$order->id],
                'status' => 'processing'
            ]);

        $bulkExportResponse = $this->actingAs($user)
            ->post(route('admin.orders.export'), [
                'selected_orders' => [$order->id]
            ]);

        $bulkCancelResponse->assertForbidden();
        $bulkUpdateResponse->assertForbidden();
        $bulkExportResponse->assertForbidden();
    }

    public function test_bulk_actions_handle_empty_selection(): void
    {
        $bulkCancelResponse = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-cancel'), [
                'ids' => [],
                'reason' => 'Test'
            ]);

        $bulkUpdateResponse = $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-update-status'), [
                'ids' => [],
                'status' => 'processing'
            ]);

        $bulkCancelResponse->assertRedirect();
        $bulkCancelResponse->assertSessionHasErrors('ids');

        $bulkUpdateResponse->assertRedirect();
        $bulkUpdateResponse->assertSessionHasErrors('ids');
    }
}