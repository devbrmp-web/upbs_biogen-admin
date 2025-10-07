@extends('layouts.vertical', ['title' => 'Varieties List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="search-bar">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search varieties..." />
                    </div>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="commodityFilter" style="width: auto;">
                            <option value="">All Commodities</option>
                            @foreach($commodities as $commodity)
                                <option value="{{ $commodity->id }}" @selected(request('commodity') == $commodity->id)>
                                    {{ $commodity->name }}
                                </option>
                            @endforeach
                        </select>
                        <select class="form-select" id="stockFilter" style="width: auto;">
                            <option value="">All Stock Status</option>
                            <option value="tersedia" @selected(request('stock_status') === 'tersedia')>Tersedia</option>
                            <option value="restock" @selected(request('stock_status') === 'restock')>Restock</option>
                            <option value="habis" @selected(request('stock_status') === 'habis')>Habis</option>
                        </select>
                        <a href="{{ route('admin.varieties.create') }}" class="btn btn-primary">
                            + Add Variety
                        </a>
                    </div>
                </div>
                <!-- end row -->
            </div>
            <div>
                <div class="table-responsive table-centered">
                    <table class="table text-nowrap mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Commodity</th>
                                <th>Stock Status</th>
                                <th>Seed Lots</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @forelse($varieties as $variety)
                            <tr>
                                <td>
                                    @if($variety->image_path)
                                            <img src="{{ asset('storage/' . $variety->image_path) }}" alt="{{ $variety->name }}" class="img-fluid" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" />
                                    @else
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light text-secondary rounded">
                                                <i class="bx bx-image"></i>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-0">{{ $variety->name }}</h6>
                                        <small class="text-muted">{{ $variety->slug }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $variety->commodity->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @php
                                        $stockStatus = $variety->stock_status;
                                        $badgeClass = match($stockStatus) {
                                            'tersedia' => 'bg-success',
                                            'restock' => 'bg-warning',
                                            'habis' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($stockStatus) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $variety->seed_lots_count ?? 0 }}</span>
                                </td>
                                <td>{{ $variety->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $variety->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.varieties.show', $variety) }}" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>
                                        <a href="{{ route('admin.varieties.edit', $variety) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                                        <form id="delete-form-{{ $variety->id }}" action="{{ route('admin.varieties.destroy', $variety) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" data-delete-form="delete-form-{{ $variety->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bx bx-package fs-1 d-block mb-2"></i>
                                        No varieties found
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($varieties) && method_exists($varieties, 'links'))
                <div class="card-footer">
                    {{ $varieties->links('custom.pagination') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<!-- Confirmation modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Variety</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this variety? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let targetFormId = null;
    const modalEl = document.getElementById('confirmDeleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    if (!modalEl || !confirmBtn) {
        console.error('Modal elements not found');
        return;
    }
    
    // Initialize Bootstrap modal
    let bsModal = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bsModal = new bootstrap.Modal(modalEl);
    }
    
    // Handle delete button clicks
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-delete-btn');
        if (btn) {
            e.preventDefault();
            
            targetFormId = btn.getAttribute('data-delete-form');
            
            if (bsModal) {
                bsModal.show();
            } else if (typeof $ !== 'undefined') {
                $('#confirmDeleteModal').modal('show');
            } else {
                if (confirm('Are you sure you want to delete this variety?')) {
                    const form = document.getElementById(targetFormId);
                    if (form) form.submit();
                }
            }
        }
    });
    
    // Handle confirm button click
    confirmBtn.addEventListener('click', function() {
        if (targetFormId) {
            const form = document.getElementById(targetFormId);
            if (form) {
                form.submit();
            }
            
            if (bsModal) {
                bsModal.hide();
            } else if (typeof $ !== 'undefined') {
                $('#confirmDeleteModal').modal('hide');
            }
        }
    });

    // Handle filter changes
    const commodityFilter = document.getElementById('commodityFilter');
    const stockFilter = document.getElementById('stockFilter');
    
    function updateFilters() {
        const url = new URL(window.location);
        const commodity = commodityFilter.value;
        const stockStatus = stockFilter.value;
        
        if (commodity) {
            url.searchParams.set('commodity', commodity);
        } else {
            url.searchParams.delete('commodity');
        }
        
        if (stockStatus !== '') {
            url.searchParams.set('stock_status', stockStatus);
        } else {
            url.searchParams.delete('stock_status');
        }
        
        window.location.href = url.toString();
    }
    
    if (commodityFilter) {
        commodityFilter.addEventListener('change', updateFilters);
    }
    
    if (stockFilter) {
        stockFilter.addEventListener('change', updateFilters);
    }
});
</script>
@endpush