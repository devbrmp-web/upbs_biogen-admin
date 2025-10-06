@extends('layouts.vertical', ['title' => 'Categories List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="search-bar">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search ..." />
                    </div>
                    <div>
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('admin.categories.create') }}" class="btn btn-primary">
                            + Add Category
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
                                <th>Created</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @foreach($categories as $c)
                            <tr>
                                <td>
                                    @if($c->image_path)
                                        <img src="{{ asset($c->image_path) }}" alt="{{ $c->name }}" class="img-fluid" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" />
                                    @else
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light text-secondary rounded">
                                                <i class="bx bx-image"></i>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->slug }}</td>
                                <td>{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $c->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                                        <form id="delete-form-{{ $c->id }}" action="{{ route('admin.categories.destroy', $c) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" data-delete-form="delete-form-{{ $c->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<!-- Reback styled confirmation modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this category? This action cannot be undone.</p>
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
    
    console.log('Modal elements found:', {modalEl, confirmBtn, bootstrap: typeof bootstrap});
    
    if (!modalEl || !confirmBtn) {
        console.error('Modal elements not found');
        return;
    }
    
    // Initialize Bootstrap modal with fallback
    let bsModal = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bsModal = new bootstrap.Modal(modalEl);
        console.log('Bootstrap modal initialized');
    } else {
        console.error('Bootstrap not available');
    }
    
    // Handle delete button clicks
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-delete-btn');
        if (btn) {
            e.preventDefault();
            console.log('Delete button clicked:', btn);
            
            targetFormId = btn.getAttribute('data-delete-form');
            console.log('Target form ID:', targetFormId);
            
            if (bsModal) {
                bsModal.show();
            } else {
                // Fallback: use jQuery if Bootstrap modal fails
                if (typeof $ !== 'undefined') {
                    $('#confirmDeleteModal').modal('show');
                } else {
                    // Last resort: direct confirmation
                    if (confirm('Are you sure you want to delete this category?')) {
                        const form = document.getElementById(targetFormId);
                        if (form) form.submit();
                    }
                }
            }
        }
    });
    
    // Handle confirm button click
    confirmBtn.addEventListener('click', function() {
        console.log('Confirm delete clicked, target form:', targetFormId);
        
        if (targetFormId) {
            const form = document.getElementById(targetFormId);
            console.log('Form found:', form);
            
            if (form) {
                form.submit();
            } else {
                console.error('Form not found:', targetFormId);
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