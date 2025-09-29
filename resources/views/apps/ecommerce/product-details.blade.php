@extends('layouts.vertical', ['title' => $product->name, 'subTitle' => 'Product Details'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="product-1-item">
                                @if($product->image_path)
                                    <img src="/{{ $product->image_path }}" alt="{{ $product->name }}" class="img-fluid mx-auto d-block rounded" />
                                @else
                                    <img src="/images/products/product-1(2).png" alt="{{ $product->name }}" class="img-fluid mx-auto d-block rounded" />
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                    <div class="col-lg-8">
                        <div class="ps-xl-3 mt-3 mt-xl-0">
                            <span class="text-primary mb-2 d-inline-block">{{ $product->category->name }}</span>
                            <h4 class="mb-3">
                                {{ $product->name }}
                            </h4>
                            
                            <h4 class="mb-3">
                                Price :
                                <b>{{ number_format($product->price) }}</b>
                            </h4>
                            <h4>
                                @if($product->stock > 0)
                                    <span class="badge badge-soft-success mb-3">In Stock ({{ $product->stock }})</span>
                                @else
                                    <span class="badge badge-soft-danger mb-3">Out of Stock</span>
                                @endif
                            </h4>

                            <div class="mb-3 pb-3 border-bottom">
                                <h5>
                                    SKU :
                                    <span class="text-muted me-2"></span>
                                    <b>{{ $product->sku }}</b>
                                </h5>
                                <h5>
                                    Category :
                                    <span class="text-muted me-2"></span>
                                    <b>{{ $product->category->name }}</b>
                                </h5>
                                <h5>
                                    Status :
                                    <span class="text-muted me-2"></span>
                                    <b>{{ ucfirst($product->status) }}</b>
                                </h5>
                            </div>
                            
                            @if($product->description)
                            <div class="mb-3">
                                <h5>Description:</h5>
                                <div class="text-muted">
                                    {!! nl2br(e($product->description)) !!}
                                </div>
                            </div>
                            @endif

                            <div>
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back fs-18 me-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end card body -->
        </div>
        <!-- end card -->
    </div>
    <!-- end col -->
</div>

@endsection