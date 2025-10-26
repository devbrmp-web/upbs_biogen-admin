@extends('layouts.vertical', ['title' => 'Seed Classes', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Seed Classes</h4>
                    <a href="{{ route('admin.seed-classes.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Seed Class
                    </a>
                </div>

                <!-- Search Form -->
                <form method="GET" action="{{ route('admin.seed-classes.index') }}" class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') ?: request('q') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bx bx-search"></i> Search
                            </button>
                            @if(request()->hasAny(['search', 'q']))
                                <a href="{{ route('admin.seed-classes.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x"></i> Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Seed Lots</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seedClasses as $seedClass)
                            <tr>
                                <td><code>{{ $seedClass->code }}</code></td>
                                <td class="fw-semibold">{{ $seedClass->name }}</td>
                                <td>{{ Str::limit($seedClass->description, 50) ?: 'No description' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $seedClass->seed_lots_count ?? $seedClass->seedLots->count() }}</span>
                                </td>
                                <td>{{ $seedClass->created_at?->format('d M Y') }}</td>
                                <td>{{ $seedClass->updated_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.seed-classes.show', $seedClass) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('admin.seed-classes.edit', $seedClass) }}" class="btn btn-sm btn-light" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                                onclick="confirmDelete('{{ $seedClass->code }}', '{{ $seedClass->name }}')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bx bx-info-circle fs-1"></i>
                                        <p class="mt-2">No seed classes found.</p>
                                        <a href="{{ route('admin.seed-classes.create') }}" class="btn btn-primary">
                                            <i class="bx bx-plus"></i> Add First Seed Class
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($seedClasses instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center mt-3">
                        {{ $seedClasses->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the seed class "<span id="deleteItemName"></span>"?</p>
                <p class="text-danger"><small><i class="bx bx-info-circle"></i> This action cannot be undone. All related seed lots will also be affected.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(code, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteForm').action = '{{ route("admin.seed-classes.destroy", ":code") }}'.replace(':code', code);
    
    // Try Bootstrap modal first
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    } else if (typeof $ !== 'undefined') {
        // Fallback to jQuery modal
        $('#deleteModal').modal('show');
    } else {
        // Fallback to native confirm
        if (confirm('Are you sure you want to delete the seed class "' + name + '"?')) {
            document.getElementById('deleteForm').submit();
        }
    }
}
</script>
@endpush