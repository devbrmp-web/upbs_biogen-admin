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
                            'pending_verification' => 'warning', // Use warning as base, styled differently
                            'paid' => 'success',
                            'processing' => 'info',
                            'pickup_ready' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $color = $statusColors[$order->status] ?? 'secondary';
                    @endphp

                    @if($order->status === 'pending_verification')
                        <span class="badge" style="background-color: rgba(249, 115, 22, 0.15); color: #ea580c;">
                            {{ $order->getStatusLabel() }}
                        </span>
                    @else
                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                            {{ $order->getStatusLabel() }}
                        </span>
                    @endif
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
                                    <button type="button" class="dropdown-item" 
                                            onclick="updateOrderStatus('{{ $order->id }}', '{{ $order->order_code }}', {{ json_encode($order->getNextValidStatuses()) }})">
                                        <i class="bx bx-edit me-2"></i>Update Status
                                    </button>
                                </li>
                                @if($order->canTransitionTo('cancelled'))
                                    <li>
                                        <button type="button" class="dropdown-item text-warning" 
                                                onclick="cancelOrder('{{ $order->id }}', '{{ $order->order_code }}')">
                                            <i class="bx bx-x-circle me-2"></i>Cancel Order
                                        </button>
                                    </li>
                                @endif
                            @endif
                            @if($order->status === 'cancelled')
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger" 
                                            onclick="deleteOrder('{{ $order->id }}', '{{ $order->order_code }}')">
                                        <i class="bx bx-trash me-2"></i>Delete Order
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </td>
            </tr>

<!-- Bootstrap Modals Removed for SweetAlert2 -->
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

{{-- Pagination for AJAX responses --}}
@if($orders->hasPages())
    <div id="ordersPaginationContainer" class="card-footer border-top" style="position: relative; z-index: 1;">
        <div class="orders-pagination">
            {{ $orders->withQueryString()->links('custom.pagination') }}
        </div>
    </div>
@endif
