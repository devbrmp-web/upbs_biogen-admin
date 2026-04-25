@extends('layouts.vertical', ['title' => 'Edit Seed Class', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Edit Seed Class: {{ $seedClass->name }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.seed-classes.show', $seedClass) }}" class="btn btn-info">
                            <i class="bx bx-show"></i> View
                        </a>
                        <a href="{{ route('admin.seed-classes.index') }}" class="btn btn-light">
                            <i class="bx bx-arrow-back"></i> Back to List
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.seed-classes.update', $seedClass) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $seedClass->code) }}" 
                               placeholder="e.g., BS, FS, SS, ES" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unique code for the seed class (e.g., BS for Breeder Seed, FS for Foundation Seed)</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $seedClass->name) }}" 
                               placeholder="e.g., Breeder Seed, Foundation Seed" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="2" 
                                  placeholder="Enter description...">{{ old('description', $seedClass->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_category" class="form-label">Stock Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('stock_category') is-invalid @enderror" id="stock_category" name="stock_category" required>
                                    <option value="weight" {{ old('stock_category', $seedClass->stock_category) == 'weight' ? 'selected' : '' }}>Weight (Kg/Gram)</option>
                                    <option value="unit" {{ old('stock_category', $seedClass->stock_category) == 'unit' ? 'selected' : '' }}>Unit (Bottle/Piece)</option>
                                </select>
                                @error('stock_category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_unit" class="form-label">Default Unit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('default_unit') is-invalid @enderror" 
                                       id="default_unit" name="default_unit" value="{{ old('default_unit', $seedClass->default_unit) }}" 
                                       placeholder="e.g., kg, bottle" required>
                                @error('default_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_order_qty" class="form-label">Min. Order Qty <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_order_qty') is-invalid @enderror" 
                                       id="min_order_qty" name="min_order_qty" value="{{ old('min_order_qty', $seedClass->min_order_qty) }}" 
                                       min="1" required>
                                @error('min_order_qty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="step_increment" class="form-label">Step Increment <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('step_increment') is-invalid @enderror" 
                                       id="step_increment" name="step_increment" value="{{ old('step_increment', $seedClass->step_increment) }}" 
                                       min="1" required>
                                @error('step_increment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Update Seed Class
                        </button>
                        <a href="{{ route('admin.seed-classes.show', $seedClass) }}" class="btn btn-secondary">
                            <i class="bx bx-x"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Seed Class Info</h5>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-semibold">Current Code:</td>
                        <td><code>{{ $seedClass->code }}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Seed Lots:</td>
                        <td>
                            <span class="badge bg-info">{{ $seedClass->seed_lots_count ?? $seedClass->seedLots->count() }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Created:</td>
                        <td>{{ $seedClass->created_at?->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Updated:</td>
                        <td>{{ $seedClass->updated_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
                @if($seedClass->seedLots && $seedClass->seedLots->count() > 0)
                    <div class="alert alert-warning">
                        <h6><i class="bx bx-info-circle"></i> Warning</h6>
                        <p class="mb-0 small">This seed class has {{ $seedClass->seedLots->count() }} associated seed lots. Changes to the code may affect related records.</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Guidelines</h5>
                <div class="alert alert-info">
                    <h6><i class="bx bx-info-circle"></i> Seed Class Information</h6>
                    <ul class="mb-0 small">
                        <li><strong>Code:</strong> Short unique identifier (2-3 characters)</li>
                        <li><strong>Name:</strong> Full descriptive name</li>
                        <li><strong>Description:</strong> Optional detailed explanation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Convert code to uppercase
    const codeInput = document.getElementById('code');
    codeInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>
@endpush