@if($auditLogs->count() > 0)
    @foreach($auditLogs as $auditLog)
    <tr>
        <td>
            <div>{{ $auditLog->created_at->format('M d, Y') }}</div>
            <small class="text-muted">{{ $auditLog->created_at->format('H:i:s') }}</small>
        </td>
        <td>
            @if($auditLog->user)
                <div>{{ $auditLog->user->name }}</div>
                <small class="text-muted">{{ $auditLog->user->email }}</small>
            @else
                <span class="text-muted">System</span>
            @endif
        </td>
        <td>
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
        </td>
        <td>
            <div>{{ $auditLog->model_name }}</div>
            @if($auditLog->model_id)
                <small class="text-muted">ID: {{ $auditLog->model_id }}</small>
            @endif
        </td>
        <td>
            @if($auditLog->category)
                <span class="badge bg-light text-dark">{{ $auditLog->category_label }}</span>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td>
            @if($auditLog->description)
                <div class="text-truncate" style="max-width: 200px;" title="{{ $auditLog->description }}">
                    {{ $auditLog->description }}
                </div>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td>
            @if($auditLog->ip_address)
                <code>{{ $auditLog->ip_address }}</code>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td>
            <div class="d-flex gap-1">
                <a href="{{ route('admin.audit-logs.show', $auditLog) }}" 
                   class="btn btn-sm btn-outline-primary" 
                   title="View Details">
                    <i class="bx bx-show"></i>
                </a>
            </div>
        </td>
    </tr>
    @endforeach
@else
    <tr>
        <td colspan="8" class="text-center py-4">
            <div class="d-flex flex-column align-items-center">
                <i class="bx bx-search-alt-2 fs-1 text-muted mb-2"></i>
                <h5 class="text-muted">No audit logs found</h5>
                <p class="text-muted mb-0">Try adjusting your search criteria or filters.</p>
            </div>
        </td>
    </tr>
@endif