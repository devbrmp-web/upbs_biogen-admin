@extends('layouts.vertical', ['title' => 'Products List', 'subTitle' => 'Ecommerce'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <form class="d-flex flex-wrap gap-2 align-items-center" method="GET" action="{{ route('admin.products.index') }}">
                        <div class="search-bar">
                            <span><i class="bx bx-search-alt"></i></span>
                            <input type="search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Search ..." />
                        </div>
                        <div>
                            <select class="form-select" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Status filter removed; status is computed and displayed only -->
                        <button type="submit" class="btn btn-outline-primary">Filter</button>
                    </form>
                    <div>
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('admin.products.create') }}" class="btn btn-primary">
                            + Add Product
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
                                <th>Product Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Categories</th>
                                <th>Image</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.products.show', $product) }}" class="text-reset">{{ $product->name }}</a>
                                </td>
                                <td>
                                    @if($product->description)
                                        <span class="fs-13">{{ Str::limit($product->description, 80) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>
                                    @if($product->image_path)
                                        <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="img-fluid avatar-sm" />
                                    @else
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light text-secondary rounded">
                                                <i class="bx bx-image"></i>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ number_format($product->total_stock, 2) }}</td>
                                <td>
                                    @php($status = $product->stock_status)
                                    @if($status === 'Tersedia')
                                        <span class="text-success">{{ $status }}</span>
                                    @elseif($status === 'Restock')
                                        <span class="text-warning">{{ $status }}</span>
                                    @else
                                        <span class="text-danger">{{ $status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-light" title="View"><i class="bx bx-show"></i></a>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                                        <form id="product-delete-form-{{ $product->id }}" action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" data-delete-form="product-delete-form-{{ $product->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No products found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<!-- Reback styled confirmation modal for Products -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this product? This action cannot be undone.</p>
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
                    if (confirm('Are you sure you want to delete this product?')) {
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