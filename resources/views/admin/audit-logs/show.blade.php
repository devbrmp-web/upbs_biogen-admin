@extends('layouts.vertical', ['title' => 'Audit Log Details', 'subTitle' => 'System'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Audit Log Details</h4>
                        <p class="text-muted mb-0">Detailed information about this audit log entry.</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Audit Logs
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Date & Time:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        {{ $auditLog->created_at->format('M d, Y H:i:s') }}
                                        <small class="text-muted d-block">{{ $auditLog->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>User:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        @if($auditLog->user)
                                            <div>{{ $auditLog->user->name }}</div>
                                            <small class="text-muted">{{ $auditLog->user->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Action:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <span class="badge 
                                            @switch($auditLog->action)
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
                                            {{ $auditLog->action_label }}
                                        </span>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Model:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <div>{{ $auditLog->model_name }}</div>
                                        @if($auditLog->model_id)
                                            <small class="text-muted">ID: {{ $auditLog->model_id }}</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Category:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        @if($auditLog->category)
                                            <span class="badge bg-light text-dark">{{ $auditLog->category_label }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </div>

                                @if($auditLog->description)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Description:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        {{ $auditLog->description }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Request Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Request Information</h5>
                            </div>
                            <div class="card-body">
                                @if($auditLog->route_name)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Route:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <code>{{ $auditLog->route_name }}</code>
                                    </div>
                                </div>
                                @endif

                                @if($auditLog->method)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>Method:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <span class="badge 
                                            @switch($auditLog->method)
                                                @case('GET')
                                                    bg-info
                                                    @break
                                                @case('POST')
                                                    bg-success
                                                    @break
                                                @case('PUT')
                                                @case('PATCH')
                                                    bg-warning
                                                    @break
                                                @case('DELETE')
                                                    bg-danger
                                                    @break
                                                @default
                                                    bg-secondary
                                            @endswitch
                                        ">
                                            {{ $auditLog->method }}
                                        </span>
                                    </div>
                                </div>
                                @endif

                                @if($auditLog->url)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>URL:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <small class="text-break">{{ $auditLog->url }}</small>
                                    </div>
                                </div>
                                @endif

                                @if($auditLog->ip_address)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>IP Address:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <code>{{ $auditLog->ip_address }}</code>
                                    </div>
                                </div>
                                @endif

                                @if($auditLog->user_agent)
                                <div class="row mb-3">
                                    <div class="col-sm-4">
                                        <strong>User Agent:</strong>
                                    </div>
                                    <div class="col-sm-8">
                                        <small class="text-muted text-break">{{ $auditLog->user_agent }}</small>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Changes -->
                @if($auditLog->old_values || $auditLog->new_values)
                <div class="row mt-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Data Changes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @if($auditLog->old_values)
                                    <div class="col-md-6">
                                        <h6 class="text-danger">Before Changes</h6>
                                        <div class="bg-light p-3 rounded">
                                            <pre class="mb-0"><code>{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</code></pre>
                                        </div>
                                    </div>
                                    @endif

                                    @if($auditLog->new_values)
                                    <div class="col-md-6">
                                        <h6 class="text-success">After Changes</h6>
                                        <div class="bg-light p-3 rounded">
                                            <pre class="mb-0"><code>{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</code></pre>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Metadata -->
                @if($auditLog->metadata)
                <div class="row mt-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Additional Metadata</h5>
                            </div>
                            <div class="card-body">
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0"><code>{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection