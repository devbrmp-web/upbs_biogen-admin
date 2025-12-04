<table class="table table-hover">
    <thead class="table-light">
        <tr>
            <th style="width:32px;">
                <input type="checkbox" id="selectAll" class="form-check-input" />
            </th>
            <th>
                <a href="#" data-sort="order_code" class="text-decoration-none">Order Code</a>
            </th>
            <th>
                <a href="#" data-sort="customer_name" class="text-decoration-none">Customer</a>
            </th>
            <th>Shipping Method</th>
            <th>
                <a href="#" data-sort="status" class="text-decoration-none">Status</a>
            </th>
            <th>
                <a href="#" data-sort="total_amount" class="text-decoration-none">Total</a>
            </th>
            <th>
                <a href="#" data-sort="created_at" class="text-decoration-none">Order Date</a>
            </th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
            <tr>
                <td>
                    <input type="checkbox" name="selected_orders[]" value="{{ $order->id }}" class="form-check-input order-checkbox" />
                </td>
                <td>
                    <a href="{{ route('admin.orders.show', $order) }}?return={{ urlencode(request()->fullUrl()) }}" class="text-decoration-none fw-semibold">
                        {{ $order->order_code }}
                    </a>
                    <button class="btn btn-sm btn-outline-secondary copy-order-code" data-code="{{ $order->order_code }}" title="Copy order code">
                        <i class="bx bx-copy"></i>
                    </button>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                        <small class="text-muted">{{ $order->customer_phone }}</small>
                    </div>
                </td>
                <td>
                    @if($order->shipping_method === 'pickup')
                        <span class="badge bg-info-subtle text-info">
                            <i class="bx bx-store me-1"></i>Pickup at BRMP
                        </span>
                    @else
                        <span class="badge bg-primary-subtle text-primary">
                            <i class="bx bx-package me-1"></i>Delivery
                        </span>
                    @endif
                </td>
                <td>
                    @php
                        $statusColors = [
                            'awaiting_payment' => 'warning',
                            'paid' => 'success',
                            'processing' => 'info',
                            'pickup_ready' => 'primary',
                            'delivery_coordination' => 'secondary',
                            'shipped' => 'primary',
                            'picked_up' => 'success',
                            'completed' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $color = $statusColors[$order->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                        {{ $order->getStatusLabel() }}
                    </span>
                </td>
                <td class="fw-semibold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                <td>
                    <div>{{ $order->created_at->format('M d, Y') }}</div>
                    <small class="text-muted">{{ $order->created_at->format('H:i') }}</small>
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-horizontal-rounded"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}?return={{ urlencode(request()->fullUrl()) }}">
                                    <i class="bx bx-show me-2"></i>View Details
                                </a>
                            </li>
                            @if($order->status !== 'cancelled' && $order->status !== 'completed')
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#statusModal{{ $order->id }}">
                                        <i class="bx bx-edit me-2"></i>Update Status
                                    </button>
                                </li>
                                @if($order->canTransitionTo('cancelled'))
                                    <li>
                                        <button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $order->id }}">
                                            <i class="bx bx-x-circle me-2"></i>Cancel Order
                                        </button>
                                    </li>
                                @endif
                            @endif
                            @if($order->status === 'cancelled')
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $order->id }}">
                                        <i class="bx bx-trash me-2"></i>Delete Order
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </td>
            </tr>

            <!-- Status Update Modal -->
            @if($order->status !== 'cancelled' && $order->status !== 'completed')
                <div class="modal fade" id="statusModal{{ $order->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Order Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="status{{ $order->id }}" class="form-label">New Status</label>
                                        <select class="form-select" id="status{{ $order->id }}" name="status" required>
                                            @foreach($order->getNextValidStatuses() as $status)
                                                <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes{{ $order->id }}" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes{{ $order->id }}" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Cancel Order Modal -->
            @if($order->canTransitionTo('cancelled'))
                <div class="modal fade" id="cancelModal{{ $order->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title">Cancel Order</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <i class="bx bx-warning me-2"></i>
                                        This will cancel the order and restore stock quantities. This action cannot be undone.
                                    </div>
                                    <div class="mb-3">
                                        <label for="cancellation_reason{{ $order->id }}" class="form-label">Cancellation Reason</label>
                                        <textarea class="form-control" id="cancellation_reason{{ $order->id }}" name="cancellation_reason" rows="3" required placeholder="Please provide a reason for cancelling this order..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                                    <button type="submit" class="btn btn-warning">Cancel Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Delete Order Modal -->
            @if($order->status === 'cancelled')
                <div class="modal fade" id="deleteModal{{ $order->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('admin.orders.destroy', $order) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Order</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-danger">
                                        <i class="bx bx-warning me-2"></i>
                                        <strong>Warning!</strong> This will permanently delete the order and all its data. This action cannot be undone.
                                    </div>
                                    <p class="mb-3">
                                        <strong>Order:</strong> {{ $order->order_code }}<br>
                                        <strong>Customer:</strong> {{ $order->customer_name }}<br>
                                        <strong>Total:</strong> {{ number_format($order->total_amount, 0, ',', '.') }} IDR
                                    </p>
                                    <div class="mb-3">
                                        <label for="deletion_reason{{ $order->id }}" class="form-label">Deletion Reason</label>
                                        <textarea class="form-control" id="deletion_reason{{ $order->id }}" name="deletion_reason" rows="3" required placeholder="Please provide a reason for deleting this order..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bx bx-package display-4 d-block mb-2"></i>
                        No orders found matching your criteria.
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($orders->hasPages())
    <div class="card-footer border-top">
        <div class="orders-pagination">
            {{ $orders->withQueryString()->links('custom.pagination') }}
        </div>
    </div>
@endif
