@extends('layouts.vertical', ['title' => 'Seed Lots', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Seed Lots</h4>
                    <a href="{{ route('admin.seed-lots.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Seed Lot
                    </a>
                </div>

                <!-- Search & Filter Form -->
                <form method="GET" action="{{ route('admin.seed-lots.index') }}" class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="q" class="form-control" placeholder="Search by lot code..." value="{{ request('q') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="variety_id" class="form-select">
                                <option value="">All Varieties</option>
                                @foreach($varieties as $variety)
                                    <option value="{{ $variety->id }}" {{ request('variety_id') == $variety->id ? 'selected' : '' }}>
                                        {{ $variety->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="seed_class_id" class="form-select">
                                <option value="">All Seed Classes</option>
                                @foreach($seedClasses as $seedClass)
                                    <option value="{{ $seedClass->id }}" {{ request('seed_class_id') == $seedClass->id ? 'selected' : '' }}>
                                        {{ $seedClass->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_sellable" class="form-select">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_sellable') === '1' ? 'selected' : '' }}>Sellable</option>
                                <option value="0" {{ request('is_sellable') === '0' ? 'selected' : '' }}>Not Sellable</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bx bx-search"></i> Search
                                </button>
                                @if(request()->hasAny(['q', 'variety_id', 'seed_class_id', 'is_sellable']))
                                    <a href="{{ route('admin.seed-lots.index') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-x"></i> Clear
                                    </a>
                                @endif
                            </div>
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
                                <th>Lot Code</th>
                                <th>Variety</th>
                                <th>Seed Class</th>
                                <th>Production Year</th>
                                <th>Quantity</th>
                                <th>Price/Unit</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($seedLots as $seedLot)
                            <tr>
                                <td><code>{{ $seedLot->lot_code }}</code></td>
                                <td>
                                    <a href="{{ route('admin.varieties.show', $seedLot->variety) }}" class="text-decoration-none fw-semibold">
                                        {{ $seedLot->variety->name ?? 'N/A' }}
                                    </a>
                                    @if($seedLot->variety && $seedLot->variety->commodity)
                                        <br><small class="text-muted">{{ $seedLot->variety->commodity->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $seedLot->seedClass->name ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $seedLot->production_year }}</td>
                                -    <td>{{ number_format($seedLot->quantity, 2) }} {{ $seedLot->unit }}</td>
                +    <td>{{ number_format($seedLot->quantity, 0) }} {{ $seedLot->unit }}</td>
                                <td>Rp {{ number_format($seedLot->price_per_unit, 0, ',', '.') }}</td>
                                <td>
                                    @if($seedLot->is_sellable)
                                        <span class="badge bg-success">Sellable</span>
                                    @else
                                        <span class="badge bg-warning">Not Sellable</span>
                                    @endif
                                </td>
                                <td>{{ $seedLot->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.seed-lots.show', $seedLot) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('admin.seed-lots.edit', $seedLot) }}" class="btn btn-sm btn-light" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                                onclick="confirmDelete('{{ $seedLot->id }}', '{{ $seedLot->lot_code }}')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bx bx-info-circle fs-1"></i>
                                        <p class="mt-2">No seed lots found.</p>
                                        <a href="{{ route('admin.seed-lots.create') }}" class="btn btn-primary">
                                            <i class="bx bx-plus"></i> Add First Seed Lot
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($seedLots instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-center mt-3">
                        {{ $seedLots->withQueryString()->links() }}
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
                <p>Are you sure you want to delete the seed lot "<span id="deleteItemName"></span>"?</p>
                <p class="text-danger"><small><i class="bx bx-info-circle"></i> This action cannot be undone.</small></p>
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
function confirmDelete(id, lotCode) {
    document.getElementById('deleteItemName').textContent = lotCode;
    document.getElementById('deleteForm').action = '{{ route("admin.seed-lots.destroy", ":id") }}'.replace(':id', id);
    
    // Try Bootstrap modal first
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    } else if (typeof $ !== 'undefined') {
        // Fallback to jQuery modal
        $('#deleteModal').modal('show');
    } else {
        // Fallback to native confirm
        if (confirm('Are you sure you want to delete the seed lot "' + lotCode + '"?')) {
            document.getElementById('deleteForm').submit();
        }
    }
}
</script>
@endpush