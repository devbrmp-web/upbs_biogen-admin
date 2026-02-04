@extends('layouts.vertical', ['title' => 'Order Details', 'subTitle' => 'Order #' . $order->order_code])

@section('content')

<div class="row">
    <div class="col">
        <!-- Order Header -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h4 class="card-title mb-1">Order #{{ $order->order_code }}</h4>
                        <p class="text-muted mb-2">Placed on {{ $order->created_at->format('F d, Y \a\t H:i') }}</p>
                        <div class="d-flex gap-2 align-items-center">
                            @php
                                $statusColors = [
                                    'awaiting_payment' => 'warning',
                                    'pending_verification' => 'warning', // Use warning as base, styled inline
                                    'paid' => 'success',
                                    'processing' => 'info',
                                    'pickup_ready' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $color = $statusColors[$order->status] ?? 'secondary';
                            @endphp
                            @if($order->status === 'pending_verification')
                                <span class="badge fs-6" style="background-color: rgba(249, 115, 22, 0.15); color: #ea580c;">
                                    {{ $order->getStatusLabel() }}
                                </span>
                            @else
                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} fs-6">
                                    {{ $order->getStatusLabel() }}
                                </span>
                            @endif
                            @if($order->shipping_method === 'pickup')
                                <span class="badge bg-info-subtle text-info">
                                    <i class="bx bx-store me-1"></i>Pickup at BRMP
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="bx bx-package me-1"></i>Delivery
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ sanitizeReturnUrl(request()->input('return'), route('admin.orders.index')) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Orders
                        </a>
                        @if($order->status !== 'cancelled' && $order->status !== 'completed')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                                <i class="bx bx-edit me-1"></i>Update Status
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Customer Information -->
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Name</label>
                            <div class="fw-semibold">{{ $order->customer_name }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Phone</label>
                            <div>{{ $order->customer_phone }}</div>
                        </div>
                        @if($order->customer_email)
                            <div class="mb-3">
                                <label class="form-label text-muted">Email</label>
                                <div>{{ $order->customer_email }}</div>
                            </div>
                        @endif
                        <div class="mb-0">
                            <label class="form-label text-muted">Address</label>
                            <div>{{ $order->customer_address }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        @if($order->payment)
                            <div class="mb-3">
                                <label class="form-label text-muted">Status</label>
                                <div>
                                    @if($order->payment->status === 'paid')
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bx bx-check-circle me-1"></i>Paid
                                        </span>
                                    @elseif($order->payment->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="bx bx-time me-1"></i>Pending
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="bx bx-x-circle me-1"></i>{{ ucfirst($order->payment->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($order->payment->payment_method)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Method</label>
                                    <div>{{ ucwords(str_replace('_', ' ', $order->payment->payment_method)) }}</div>
                                </div>
                            @endif
                            @if($order->payment->pnbp_receipt_no)
                                <div class="mb-3">
                                    <label class="form-label text-muted">PNBP Receipt</label>
                                    <div class="font-monospace">{{ $order->payment->pnbp_receipt_no }}</div>
                                </div>
                            @endif
                            @if($order->payment->paid_at)
                                <div class="mb-0">
                                    <label class="form-label text-muted">Paid At</label>
                                    <div>{{ $order->payment->paid_at->format('F d, Y \a\t H:i') }}</div>
                                </div>
                            @endif
                            {{-- Payment Proof Section --}}
                            @if($order->payment->payment_proof_path)
                                <div class="mt-3 pt-3 border-top">
                                    <label class="form-label text-muted">Bukti Transfer</label>
                                    <div>
                                        @if(Str::endsWith($order->payment->payment_proof_path, ['.jpg', '.jpeg', '.png']))
                                            <a href="{{ asset($order->payment->payment_proof_path) }}" target="_blank">
                                                <img src="{{ asset($order->payment->payment_proof_path) }}" 
                                                     alt="Bukti Transfer" 
                                                     class="img-thumbnail" 
                                                     style="max-height: 200px; cursor: pointer;">
                                            </a>
                                        @else
                                            <a href="{{ asset($order->payment->payment_proof_path) }}" 
                                               target="_blank" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bx bx-file me-1"></i>Lihat Dokumen
                                            </a>
                                        @endif
                                        @if($order->payment->proof_uploaded_at)
                                            <small class="d-block text-muted mt-1">
                                                Diunggah: {{ $order->payment->proof_uploaded_at->format('d M Y, H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            {{-- Quick Verify Button for pending_verification --}}
                            @if($order->status === 'pending_verification')
                                <div class="mt-3 pt-3 border-top">
                                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="paid">
                                        <input type="hidden" name="notes" value="Pembayaran diverifikasi via bukti transfer">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bx bx-check-circle me-1"></i>Verifikasi Pembayaran
                                        </button>
                                    </form>
                                </div>
                            @endif
                            @if($order->payment_type)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Payment Type</label>
                                    <div>{{ ucwords(str_replace('_', ' ', $order->payment_type)) }}</div>
                                </div>
                            @endif
                            @if($order->transaction_id)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Transaction ID</label>
                                    <div class="font-monospace">{{ $order->transaction_id }}</div>
                                </div>
                            @endif
                            @if($order->transaction_status)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Transaction Status</label>
                                    <div>{{ ucfirst($order->transaction_status) }}</div>
                                </div>
                            @endif
                            @if($order->settlement_time)
                                <div class="mb-0">
                                    <label class="form-label text-muted">Settlement Time</label>
                                    <div>{{ $order->settlement_time->format('F d, Y \a\t H:i') }}</div>
                                </div>
                            @endif
                        @else
                            <div class="text-muted">No payment information available.</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Method</label>
                            <div>
                                @if($order->shipping_method === 'pickup')
                                    <i class="bx bx-store me-1"></i>Pickup at BRMP
                                @else
                                    <i class="bx bx-package me-1"></i>Delivery
                                @endif
                            </div>
                        </div>
                        @if($order->courier_name)
                            <div class="mb-3">
                                <label class="form-label text-muted">Selected Courier</label>
                                <div>{{ $order->courier_name }}</div>
                            </div>
                        @endif
                        @if($order->shipping_cost > 0)
                            <div class="mb-3">
                                <label class="form-label text-muted">Shipping Cost</label>
                                <div>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                            </div>
                        @endif
                        @if($order->shipment)
                            @if($order->shipment->courier_name && $order->shipment->courier_name !== $order->courier_name)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Actual Courier</label>
                                    <div>{{ $order->shipment->courier_name }}</div>
                                </div>
                            @endif
                            @if($order->shipment->tracking_number)
                                <div class="mb-3">
                                    <label class="form-label text-muted">Tracking Number</label>
                                    <div class="font-monospace">{{ $order->shipment->tracking_number }}</div>
                                </div>
                            @endif
                            @if($order->shipment->shipped_at)
                                <div class="mb-0">
                                    <label class="form-label text-muted">Shipped At</label>
                                    <div>{{ $order->shipment->shipped_at->format('F d, Y \a\t H:i') }}</div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        @if($item->product_sku)
                                            <small class="text-muted">SKU: {{ $item->product_sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rp {{ number_format($item->price_at_order, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($item->quantity * $item->price_at_order, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</th>
                            </tr>
                            @if($order->shipping_cost > 0)
                                <tr>
                                    <th colspan="3" class="text-end">Shipping:</th>
                                    <th class="text-end">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</th>
                                </tr>
                            @endif
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Timeline -->
        @if($order->auditLogs->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($order->auditLogs->sortByDesc('created_at') as $log)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">{{ $log->action }}</h6>
                                    <p class="timeline-text text-muted mb-1">{{ $log->description }}</p>
                                    <small class="text-muted">
                                        {{ $log->created_at->format('F d, Y \a\t H:i') }}
                                        @if($log->user)
                                            by {{ $log->user->name }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Status Update Modal -->
@if($order->status !== 'cancelled' && $order->status !== 'completed')
    <div class="modal fade" id="statusModal" tabindex="-1">
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
                            <label for="status" class="form-label">New Status</label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach($order->getNextValidStatuses() as $status)
                                    <option value="{{ $status }}">
                                        {{ ucwords(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Add any notes about this status change..."></textarea>
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

@endsection

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #0d6efd;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    font-size: 13px;
    margin-bottom: 5px;
}
</style>
@endpush
