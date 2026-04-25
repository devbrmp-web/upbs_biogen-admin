@extends('layouts.vertical', ['title' => 'Add Seed Class', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Add New Seed Class</h4>
                    <a href="{{ route('admin.seed-classes.index') }}" class="btn btn-light">
                        <i class="bx bx-arrow-back"></i> Back to List
                    </a>
                </div>

                <form action="{{ route('admin.seed-classes.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" 
                               placeholder="e.g., BS, FS, SS, ES" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unique code for the seed class (e.g., BS for Breeder Seed, FS for Foundation Seed)</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="e.g., Breeder Seed, Foundation Seed" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="2" 
                                  placeholder="Enter description...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_category" class="form-label">Stock Category <span class="text-danger">*</span></label>
                                <select class="form-select @error('stock_category') is-invalid @enderror" id="stock_category" name="stock_category" required>
                                    <option value="weight" {{ old('stock_category') == 'weight' ? 'selected' : '' }}>Weight (Kg/Gram)</option>
                                    <option value="unit" {{ old('stock_category') == 'unit' ? 'selected' : '' }}>Unit (Bottle/Piece)</option>
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
                                       id="default_unit" name="default_unit" value="{{ old('default_unit', 'kg') }}" 
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
                                       id="min_order_qty" name="min_order_qty" value="{{ old('min_order_qty', 1) }}" 
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
                                       id="step_increment" name="step_increment" value="{{ old('step_increment', 1) }}" 
                                       min="1" required>
                                @error('step_increment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Save Seed Class
                        </button>
                        <a href="{{ route('admin.seed-classes.index') }}" class="btn btn-secondary">
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
                <h5 class="card-title">Guidelines</h5>
                <div class="alert alert-info">
                    <h6><i class="bx bx-info-circle"></i> Seed Class Information</h6>
                    <ul class="mb-0 small">
                        <li><strong>Code:</strong> Short unique identifier (2-3 characters)</li>
                        <li><strong>Name:</strong> Full descriptive name</li>
                        <li><strong>Description:</strong> Optional detailed explanation</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="bx bx-bulb"></i> Common Seed Classes</h6>
                    <ul class="mb-0 small">
                        <li><strong>BS:</strong> Breeder Seed</li>
                        <li><strong>FS:</strong> Foundation Seed</li>
                        <li><strong>SS:</strong> Stock Seed</li>
                        <li><strong>ES:</strong> Extension Seed</li>
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
    // Auto-generate code from name if code is empty
    const nameInput = document.getElementById('name');
    const codeInput = document.getElementById('code');
    
    nameInput.addEventListener('input', function() {
        if (!codeInput.value) {
            // Generate code from first letters of words
            const words = this.value.trim().split(/\s+/);
            const code = words.map(word => word.charAt(0).toUpperCase()).join('').substring(0, 3);
            codeInput.value = code;
        }
    });
    
    // Convert code to uppercase
    codeInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>
@endpush