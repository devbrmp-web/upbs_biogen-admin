@extends('layouts.vertical', ['title' => 'Commodities List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="search-bar">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search commodities..." />
                    </div>
                    <div>
                        <a href="{{ route('admin.commodities.create') }}" class="btn btn-primary">
                            + Add Commodity
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
                                <th>Slug</th>
                                <th>Varieties Count</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @forelse($commodities as $commodity)
                            <tr>
                                <td>
                                    @if($commodity->image_url)
                                            <img src="{{ asset('storage/' . $commodity->image_url) }}" alt="{{ $commodity->name }}" class="img-fluid" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" />
                                    @else
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light text-secondary rounded">
                                                <i class="bx bx-image"></i>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $commodity->name }}</td>
                                <td>
                                    <code class="text-muted">{{ $commodity->slug }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $commodity->varieties_count ?? 0 }}</span>
                                </td>
                                <td>{{ $commodity->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $commodity->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.commodities.show', $commodity) }}" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>
                                        <a href="{{ route('admin.commodities.edit', $commodity) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                                        <form id="delete-form-{{ $commodity->id }}" action="{{ route('admin.commodities.destroy', $commodity) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" data-delete-form="delete-form-{{ $commodity->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bx bx-package fs-1 d-block mb-2"></i>
                                        No commodities found
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($commodities) && method_exists($commodities, 'links'))
                <div class="card-footer">
                    {{ $commodities->links('custom.pagination') }}
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
        <h5 class="modal-title">Delete Commodity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this commodity? This action cannot be undone.</p>
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
                if (confirm('Are you sure you want to delete this commodity?')) {
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
});
</script>
@endpush