@extends('layouts.vertical', ['title' => 'Orders', 'subTitle' => 'Sales'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-1">Orders</h4>
                        <p class="text-muted mb-0">Manage customer orders, status, and shipping methods.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false">
                            <i class="bx bx-filter me-1"></i>Filters
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="collapse mb-3" id="filterCollapse">
                    <div class="card card-body bg-light">
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3" id="ordersFilterForm">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="q" 
                                       value="{{ request('q', request('search')) }}" placeholder="Order code, customer name, phone...">
                            </div>
                            <div class="col-md-2">
                                <label for="shipping_method" class="form-label">Shipping Method</label>
                                <select class="form-select" id="shipping_method" name="shipping_method">
                                    <option value="">All Methods</option>
                                    @foreach($shippingMethods as $value => $label)
                                        <option value="{{ $value }}" {{ request('shipping_method') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-search"></i>
                                </button>
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Actions Toolbar -->
                <div id="bulkActionsToolbar" class="card mb-3" style="display: none;">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="text-muted me-3">
                                    <span id="selectedCount">0</span> orders selected
                                </span>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" id="bulkCancelBtn">
                                        <i class="bx bx-x-circle me-1"></i>Cancel Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-info" id="bulkUpdateStatusBtn">
                                        <i class="bx bx-edit me-1"></i>Update Status
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" id="bulkExportBtn">
                                        <i class="bx bx-download me-1"></i>Export Selected
                                    </button>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">
                                    <i class="bx bx-x me-1"></i>Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div id="ordersTableContainer" class="table-responsive" style="overflow: visible;">
                    @include('admin.orders.partials.table-content', ['orders' => $orders])
                </div>

                <!-- Bulk Cancel Confirm Modal -->
                <div class="modal fade" id="bulkCancelConfirmModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cancel Selected Orders</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Cancel selected orders? This will restore stock where applicable.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-warning" id="confirmBulkCancelBtn">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Selection Modal -->
                <div class="modal fade" id="noSelectionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">No Orders Selected</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please select at least one order to perform bulk actions.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Update Status Modal -->
                <div class="modal fade" id="bulkUpdateStatusModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Status for Selected Orders</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="bulkNewStatus" class="form-label">New Status</label>
                                    <select class="form-select" id="bulkNewStatus" name="status" required>
                                        <option value="">Select new status...</option>
                                        <option value="paid">Paid</option>
                                        <option value="processing">Processing</option>
                                        <option value="pickup_ready">Ready for Pickup</option>
                                        <option value="delivery_coordination">Delivery Coordination</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="picked_up">Picked Up</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="bulkStatusNotes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="bulkStatusNotes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    Only orders that can transition to the selected status will be updated.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmBulkUpdateBtn">Update Status</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toast Container -->
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
                    <div id="ordersToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body" id="ordersToastBody">Action completed.</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                </div>

                @push('scripts')
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('ordersFilterForm');
                    const container = document.getElementById('ordersTableContainer');
                    const bulkConfirmModalEl = document.getElementById('bulkCancelConfirmModal');
                    const bulkConfirmModal = bulkConfirmModalEl ? new bootstrap.Modal(bulkConfirmModalEl) : null;
                    const noSelectionModalEl = document.getElementById('noSelectionModal');
                    const noSelectionModal = noSelectionModalEl ? new bootstrap.Modal(noSelectionModalEl) : null;
                    const bulkUpdateModalEl = document.getElementById('bulkUpdateStatusModal');
                    const bulkUpdateModal = bulkUpdateModalEl ? new bootstrap.Modal(bulkUpdateModalEl) : null;
                    
                    // Bulk actions toolbar elements
                    const bulkToolbar = document.getElementById('bulkActionsToolbar');
                    const selectedCountEl = document.getElementById('selectedCount');
                    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
                    const toastEl = document.getElementById('ordersToast');
                    const toastBodyEl = document.getElementById('ordersToastBody');
                    const toast = toastEl ? new bootstrap.Toast(toastEl) : null;
                    const debounce = (fn, delay = 300) => {
                        let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
                    };

                    const updateUrl = (params) => {
                        const url = new URL(window.location.href);
                        // Clear existing params and set new ones
                        url.search = params.toString();
                        window.history.pushState({ url: url.toString() }, '', url.toString());
                    };

                    const fetchOrders = async (params) => {
                        const url = new URL('{{ route('admin.orders.index') }}', window.location.origin);
                        url.search = params.toString();
                        try {
                            const res = await fetch(url.toString(), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const html = await res.text();
                            container.innerHTML = html;
                        } catch (err) {
                            console.error('Failed to fetch orders:', err);
                        }
                    };

                    const buildParams = () => {
                        const formData = new FormData(form);
                        const params = new URLSearchParams();
                        ['q','shipping_method','status','date_from','date_to','sort','direction','page'].forEach(k => {
                            const v = formData.get(k);
                            if (v) params.set(k, v);
                        });
                        return params;
                    };

                    // Bulk actions functionality
                    function updateBulkToolbar() {
                        const checked = document.querySelectorAll('input[name="selected_orders[]"]:checked');
                        const toolbar = document.getElementById('bulkActionsToolbar');
                        const selectedCount = document.getElementById('selectedCount');
                        
                        if (checked.length > 0) {
                            toolbar.style.display = 'block';
                            selectedCount.textContent = checked.length;
                        } else {
                            toolbar.style.display = 'none';
                        }
                    }

                    // Handle Select All checkbox
                    document.addEventListener('change', (e) => {
                        if (e.target.id === 'selectAll') {
                            const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');
                            checkboxes.forEach(cb => cb.checked = e.target.checked);
                            updateBulkToolbar();
                        } else if (e.target.matches('input[name="selected_orders[]"]')) {
                            updateBulkToolbar();
                            // Update Select All checkbox state
                            const allCheckboxes = document.querySelectorAll('input[name="selected_orders[]"]');
                            const checkedCheckboxes = document.querySelectorAll('input[name="selected_orders[]"]:checked');
                            const selectAllCheckbox = document.getElementById('selectAll');
                            if (selectAllCheckbox) {
                                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                                selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                            }
                        }
                    });

                    // Clear selection
                    document.addEventListener('click', (e) => {
                        if (e.target.closest('#clearSelectionBtn')) {
                            document.querySelectorAll('input[name="selected_orders[]"]').forEach(cb => cb.checked = false);
                            const selectAllCheckbox = document.getElementById('selectAll');
                            if (selectAllCheckbox) {
                                selectAllCheckbox.checked = false;
                                selectAllCheckbox.indeterminate = false;
                            }
                            updateBulkToolbar();
                        }
                    });

                    // Debounced search
                    const searchInput = document.getElementById('search');
                    if (searchInput) {
                        searchInput.addEventListener('input', debounce(() => {
                            const params = buildParams();
                            params.delete('page'); // reset to first page on new search
                            updateUrl(params);
                            fetchOrders(params);
                        }, 300));
                    }

                    // Filter changes
                    form.querySelectorAll('select, input[type="date"]').forEach(el => {
                        el.addEventListener('change', () => {
                            const params = buildParams();
                            params.delete('page');
                            updateUrl(params);
                            fetchOrders(params);
                        });
                    });

                    // Sorting handlers
                    document.addEventListener('click', (e) => {
                        const sortBtn = e.target.closest('[data-sort]');
                        if (sortBtn) {
                            e.preventDefault();
                            const sort = sortBtn.getAttribute('data-sort');
                            const currentSort = new URLSearchParams(window.location.search).get('sort');
                            const currentDir = new URLSearchParams(window.location.search).get('direction') || 'desc';
                            const nextDir = (currentSort === sort && currentDir === 'desc') ? 'asc' : 'desc';
                            const params = buildParams();
                            params.set('sort', sort);
                            params.set('direction', nextDir);
                            params.delete('page');
                            updateUrl(params);
                            fetchOrders(params);
                        }
                    });

                    // Pagination (delegate clicks inside container)
                    container.addEventListener('click', (e) => {
                        const link = e.target.closest('a.page-link');
                        if (link) {
                            e.preventDefault();
                            const url = new URL(link.href);
                            const params = new URLSearchParams(url.search);
                            updateUrl(params);
                            fetchOrders(params);
                        }
                    });

                    // Popstate restore
                    window.addEventListener('popstate', (event) => {
                        const url = new URL(window.location.href);
                        const params = new URLSearchParams(url.search);
                        fetchOrders(params);
                    });

                    // Bulk cancel
                    document.addEventListener('click', async (e) => {
                        const btn = e.target.closest('#bulkCancelBtn');
                        if (!btn) return;
                        const checked = Array.from(document.querySelectorAll('input[name="selected_orders[]"]:checked'))
                            .map(cb => cb.value);
                        if (checked.length === 0) {
                            if (noSelectionModal) noSelectionModal.show();
                            return;
                        }
                        if (bulkConfirmModal) bulkConfirmModal.show();

                        const doCancel = async () => {
                            try {
                                const res = await fetch('{{ route('admin.orders.bulk-cancel') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ ids: checked })
                                });
                                const data = await res.json();
                                if (data.success) {
                                    const params = buildParams();
                                    fetchOrders(params);
                                    if (toast) {
                                        toastBodyEl.textContent = 'Selected orders have been cancelled.';
                                        toast.show();
                                    }
                                } else {
                                    if (toast) {
                                        toastBodyEl.textContent = data.message || 'Bulk cancel failed.';
                                        toast.show();
                                    }
                                }
                            } catch (err) {
                                console.error(err);
                                if (toast) {
                                    toastBodyEl.textContent = 'Network error.';
                                    toast.show();
                                }
                            }
                        };

                        const confirmBtn = document.getElementById('confirmBulkCancelBtn');
                        if (confirmBtn) {
                            const handler = () => {
                                confirmBtn.removeEventListener('click', handler);
                                bulkConfirmModal.hide();
                                doCancel();
                            };
                            confirmBtn.addEventListener('click', handler);
                        }
                    });

                    // Bulk Update Status handler
                    document.addEventListener('click', function(e) {
                        if (e.target.id === 'bulkUpdateStatusBtn') {
                            const checked = Array.from(document.querySelectorAll('input[name="selected_orders[]"]:checked')).map(cb => cb.value);
                            if (checked.length === 0) {
                                if (noSelectionModal) noSelectionModal.show();
                                return;
                            }
                            if (bulkUpdateModal) bulkUpdateModal.show();
                        }
                    });

                    // Confirm bulk update status
                    document.addEventListener('click', async function(e) {
                        if (e.target.id === 'confirmBulkUpdateBtn') {
                            const checked = Array.from(document.querySelectorAll('input[name="selected_orders[]"]:checked')).map(cb => cb.value);
                            const newStatus = document.getElementById('bulkNewStatus').value;
                            const notes = document.getElementById('bulkStatusNotes').value;

                            if (!newStatus) {
                                alert('Please select a status');
                                return;
                            }

                            try {
                                const res = await fetch('{{ route('admin.orders.bulk-update-status') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ 
                                        ids: checked, 
                                        status: newStatus,
                                        notes: notes 
                                    })
                                });
                                const data = await res.json();
                                
                                if (bulkUpdateModal) bulkUpdateModal.hide();
                                
                                if (data.success) {
                                    const params = buildParams();
                                    fetchOrders(params);
                                    updateBulkToolbar();
                                    if (toast) {
                                        toastBodyEl.textContent = `${data.updated_count} orders updated successfully.`;
                                        toast.show();
                                    }
                                } else {
                                    if (toast) {
                                        toastBodyEl.textContent = data.message || 'Bulk update failed.';
                                        toast.show();
                                    }
                                }
                            } catch (err) {
                                console.error(err);
                                if (toast) {
                                    toastBodyEl.textContent = 'Network error.';
                                    toast.show();
                                }
                            }
                        }
                    });

                    // Bulk Export handler
                    document.addEventListener('click', function(e) {
                        if (e.target.id === 'bulkExportBtn') {
                            const checked = Array.from(document.querySelectorAll('input[name="selected_orders[]"]:checked')).map(cb => cb.value);
                            if (checked.length === 0) {
                                if (noSelectionModal) noSelectionModal.show();
                                return;
                            }
                            
                            // Create form and submit for CSV export
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('admin.orders.export') }}';
                            form.style.display = 'none';
                            
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = '{{ csrf_token() }}';
                            form.appendChild(csrfInput);
                            
                            checked.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'selected_orders[]';
                                input.value = id;
                                form.appendChild(input);
                            });
                            
                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                            
                            if (toast) {
                                toastBodyEl.textContent = 'Export started. Download will begin shortly.';
                                toast.show();
                            }
                        }
                    });

                    // Copy order code action
                    document.addEventListener('click', async (e) => {
                        const btn = e.target.closest('.copy-order-code');
                        if (btn) {
                            e.preventDefault();
                            const code = btn.getAttribute('data-code');
                            try { await navigator.clipboard.writeText(code); } catch {}
                            if (toast) {
                                toastBodyEl.textContent = 'Order code copied!';
                                toast.show();
                            }
                        }
                    });

                    // Fix dropdown stacking context issue in table
                    // When a dropdown opens, we elevate the parent cell's z-index
                    // to ensure the menu appears above subsequent rows/cells.
                    document.addEventListener('show.bs.dropdown', function (e) {
                        const dropdown = e.target;
                        // Ensure we only target dropdowns inside the orders table
                        if (!dropdown.closest('#ordersTableContainer')) return;

                        const cell = dropdown.closest('td');
                        if (cell) {
                            cell.style.position = 'relative';
                            cell.style.zIndex = '1000';
                        }
                    });

                    document.addEventListener('hide.bs.dropdown', function (e) {
                        const dropdown = e.target;
                        if (!dropdown.closest('#ordersTableContainer')) return;

                        const cell = dropdown.closest('td');
                        if (cell) {
                            // Clean up styles
                            cell.style.position = '';
                            cell.style.zIndex = '';
                        }
                    });

                    // Ensure modals are moved to body to prevent stacking context issues
                    document.addEventListener('show.bs.modal', function (event) {
                        const modal = event.target;
                        if (modal.closest('#ordersTableContainer')) {
                            document.body.appendChild(modal);
                        }
                    });
                });
                </script>
                @endpush
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Improved pagination buttons for orders page */
.orders-pagination .pagination .page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    min-width: 2.5rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    margin: 0 0.125rem;
}

.orders-pagination .pagination-info {
    font-size: 0.875rem;
    color: #6c757d;
}

.orders-pagination .pagination {
    margin-bottom: 0;
    gap: 0.25rem;
}

.orders-pagination .pagination .page-item {
    margin: 0;
}

.orders-pagination .pagination .page-item.active .page-link {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
    font-weight: 500;
}

.orders-pagination .pagination .page-item.disabled .page-link {
    color: #adb5bd;
    background-color: transparent;
    border-color: #dee2e6;
}

.orders-pagination .pagination .page-link:hover:not(.disabled) {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: var(--bs-primary);
}

.orders-pagination .pagination .page-item.active .page-link:hover {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .orders-pagination .d-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .orders-pagination .pagination-info {
        text-align: center;
    }
    
    .orders-pagination .pagination .page-link {
        padding: 0.25rem 0.5rem;
        min-width: 2rem;
        font-size: 0.8rem;
    }
}
</style>
@endpush

{{-- Scripts moved above for AJAX handling --}}
