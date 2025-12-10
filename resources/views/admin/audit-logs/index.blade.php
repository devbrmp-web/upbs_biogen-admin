@extends('layouts.vertical', ['title' => 'Audit Logs', 'subTitle' => 'System'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-1">Audit Logs</h4>
                        <p class="text-muted mb-0">Track all system activities and user actions for security and compliance.</p>
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
                        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3" id="auditLogsFilterForm">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="q" 
                                       value="{{ request('q', request('search')) }}" placeholder="Description, IP address, route...">
                            </div>
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">User</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="action" class="form-label">Action</label>
                                <select class="form-select" id="action" name="action">
                                    <option value="">All Actions</option>
                                    @foreach($actions as $value => $label)
                                        <option value="{{ $value }}" {{ request('action') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $value => $label)
                                        <option value="{{ $value }}" {{ request('category') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="table_name" class="form-label">Table Name</label>
                                <select class="form-select" id="table_name" name="table_name">
                                    <option value="">All Tables</option>
                                    @foreach($tableNames as $value => $label)
                                        <option value="{{ $value }}" {{ request('table_name') == $value ? 'selected' : '' }}>
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
                                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Summary -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">
                        Showing {{ $auditLogs->firstItem() ?? 0 }} to {{ $auditLogs->lastItem() ?? 0 }} 
                        of {{ $auditLogs->total() }} audit logs
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Model</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $log->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <div class="fw-medium">{{ $log->user->name }}</div>
                                            <small class="text-muted">{{ $log->user->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @switch($log->action)
                                                @case('CREATE')
                                                    bg-success
                                                    @break
                                                @case('UPDATE')
                                                    bg-warning
                                                    @break
                                                @case('DELETE')
                                                    bg-danger
                                                    @break
                                                @case('LOGIN')
                                                    bg-info
                                                    @break
                                                @case('LOGOUT')
                                                    bg-secondary
                                                    @break
                                                @default
                                                    bg-primary
                                            @endswitch
                                        ">
                                            {{ $log->action_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $log->model_name }}</div>
                                        @if($log->model_id)
                                            <small class="text-muted">ID: {{ $log->model_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->category)
                                            <span class="badge bg-light text-dark">{{ $log->category_label }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $log->description }}">
                                            {{ $log->description ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $log->ip_address ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('admin.audit-logs.show', $log) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Details">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bx bx-history fs-1 d-block mb-2"></i>
                                            No audit logs found matching your criteria.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($auditLogs->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $auditLogs->links('custom.pagination') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filter changes
    const filterForm = document.getElementById('auditLogsFilterForm');
    const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search input with debounce
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 500);
    });
});
</script>
@endpush