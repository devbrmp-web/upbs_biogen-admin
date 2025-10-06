@extends('layouts.vertical', ['title' => 'Add Product', 'subTitle' => 'Ecommerce'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Create New Product</h5>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="name">Product Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control" placeholder="Enter product name" required>
                                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="sku">SKU</label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="form-control" placeholder="Enter SKU (optional)">
                                @error('sku')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="category_id">Category</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="price">Price (Rp)</label>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0" class="form-control" placeholder="Enter price" required>
                                @error('price')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="minimum_limit">Minimum Limit</label>
                                <input type="number" name="minimum_limit" id="minimum_limit" value="{{ old('minimum_limit') }}" step="0.01" min="1" class="form-control" placeholder="Enter minimum limit (>= 1)" required>
                                <small class="text-muted">Nilai minimum limit harus >= 1. Digunakan untuk menentukan status stok.</small>
                                @error('minimum_limit')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="stock">Stock (units)</label>
                                <input type="number" name="stock" id="stock" value="{{ old('stock') }}" min="0" class="form-control" placeholder="Enter stock">
                                @error('stock')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="stock_bs_kg">Stock BS (kg)</label>
                                <input type="number" name="stock_bs_kg" id="stock_bs_kg" value="{{ old('stock_bs_kg') }}" step="0.01" min="0" class="form-control" placeholder="Enter stock BS in kg">
                                @error('stock_bs_kg')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="stock_fs_kg">Stock FS (kg)</label>
                                <input type="number" name="stock_fs_kg" id="stock_fs_kg" value="{{ old('stock_fs_kg') }}" step="0.01" min="0" class="form-control" placeholder="Enter stock FS in kg">
                                @error('stock_fs_kg')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" rows="5" class="form-control" placeholder="Product description">{{ old('description') }}</textarea>
                        @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="image">Image</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*">
                        @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection