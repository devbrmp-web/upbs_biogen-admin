@extends('layouts.vertical', ['title' => 'Products List', 'subTitle' => 'Ecommerce'])

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
                    <!-- Removed Add Product button to keep read-only list -->
                </div>
                <!-- end row -->
            </div>
            <div>
                <div class="table-responsive table-centered">
                    <table class="table text-nowrap mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Inventory</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            @if($product->image_path)
                                                <a href="{{ route('admin.products.show', $product) }}"><img src="/{{ $product->image_path }}" alt="{{ $product->name }}" class="img-fluid avatar-sm" /></a>
                                            @else
                                                <a href="{{ route('admin.products.show', $product) }}">
                                                    <div class="avatar-sm">
                                                        <span class="avatar-title bg-light text-secondary rounded">
                                                            <i class="bx bx-image"></i>
                                                        </span>
                                                    </div>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mt-0 mb-1">
                                                <a href="{{ route('admin.products.show', $product) }}" class="text-reset">{{ $product->name }}</a>
                                            </h5>
                                            @if($product->description)
                                                <span class="fs-13">{{ Str::limit($product->description, 80) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name }}</td>
                                <td>{{ number_format($product->price) }}</td>
                                <td class="{{ $product->stock > 0 ? 'text-success' : 'text-danger' }}">
                                    @if($product->stock > 0)
                                        <i class="bx bxs-circle text-success me-1"></i>In Stock ({{ $product->stock }})
                                    @else
                                        <i class="bx bxs-circle text-danger me-1"></i>Out of Stock
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-soft-primary" title="View">
                                        <i class="bx bx-show fs-18"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No products found</td>
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