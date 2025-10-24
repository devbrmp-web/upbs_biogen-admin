@extends('layouts.vertical', ['title' => 'Add Seed Lot', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Add New Seed Lot</h4>
                    <a href="{{ route('admin.seed-lots.index') }}" class="btn btn-light">
                        <i class="bx bx-arrow-back"></i> Back to List
                    </a>
                </div>

                <form action="{{ route('admin.seed-lots.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lot_code" class="form-label">Lot Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lot_code') is-invalid @enderror" 
                                       id="lot_code" name="lot_code" value="{{ old('lot_code') }}" 
                                       placeholder="e.g., LOT-2024-001" required>
                                @error('lot_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Unique identifier for this seed lot</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="production_year" class="form-label">Production Year <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('production_year') is-invalid @enderror" 
                                       id="production_year" name="production_year" value="{{ old('production_year', date('Y')) }}" 
                                       min="2020" max="{{ date('Y') + 1 }}" required>
                                @error('production_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="variety_id" class="form-label">Variety <span class="text-danger">*</span></label>
                                <select class="form-select @error('variety_id') is-invalid @enderror" 
                                        id="variety_id" name="variety_id" required>
                                    <option value="">Select Variety</option>
                                    @foreach($varieties as $variety)
                                        <option value="{{ $variety->id }}" 
                                            {{ (old('variety_id', $selectedVarietyId) == $variety->id) ? 'selected' : '' }}>
                                            {{ $variety->name }} ({{ $variety->commodity->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('variety_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="seed_class_id" class="form-label">Seed Class <span class="text-danger">*</span></label>
                                <select class="form-select @error('seed_class_id') is-invalid @enderror" 
                                        id="seed_class_id" name="seed_class_id" required>
                                    <option value="">Select Seed Class</option>
                                    @foreach($seedClasses as $seedClass)
                                        <option value="{{ $seedClass->id }}" {{ old('seed_class_id') == $seedClass->id ? 'selected' : '' }}>
                                            {{ $seedClass->name }} ({{ $seedClass->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('seed_class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity (bilangan bulat) <span class="text-danger">*</span></label>
                                <input type="number" step="1" inputmode="numeric" pattern="[0-9]*" class="form-control @error('quantity') is-invalid @enderror"
                                id="quantity" name="quantity" value="{{ old('quantity') }}"
                                placeholder="Masukkan jumlah sebagai bilangan bulat (tanpa desimal)" />
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                                <select class="form-select @error('unit') is-invalid @enderror" 
                                        id="unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                    <option value="gram" {{ old('unit') == 'gram' ? 'selected' : '' }}>Gram (g)</option>
                                    <option value="ton" {{ old('unit') == 'ton' ? 'selected' : '' }}>Ton</option>
                                    <option value="piece" {{ old('unit') == 'piece' ? 'selected' : '' }}>Piece</option>
                                    <option value="bottle" {{ old('unit') == 'bottle' ? 'selected' : '' }}>Bottle</option>
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price_per_unit" class="form-label">Price per Unit (IDR) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('price_per_unit') is-invalid @enderror" 
                                       id="price_per_unit" name="price_per_unit" value="{{ old('price_per_unit') }}" 
                                       min="0" required>
                                @error('price_per_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3" 
                                  placeholder="Enter description for this seed lot...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input @error('is_sellable') is-invalid @enderror" 
                                   type="checkbox" id="is_sellable" name="is_sellable" value="1" 
                                   {{ old('is_sellable', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_sellable">
                                Available for Sale
                            </label>
                            @error('is_sellable')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Check this if the seed lot is ready to be sold</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Save Seed Lot
                        </button>
                        <a href="{{ route('admin.seed-lots.index') }}" class="btn btn-secondary">
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
                    <h6><i class="bx bx-info-circle"></i> Seed Lot Information</h6>
                    <ul class="mb-0 small">
                        <li><strong>Lot Code:</strong> Unique identifier for tracking</li>
                        <li><strong>Variety:</strong> Type of seed/plant variety</li>
                        <li><strong>Seed Class:</strong> Classification (BS, FS, etc.)</li>
                        <li><strong>Quantity:</strong> Amount available</li>
                        <li><strong>Price:</strong> Cost per unit in IDR</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="bx bx-bulb"></i> Tips</h6>
                    <ul class="mb-0 small">
                        <li>Use consistent lot code format</li>
                        <li>Ensure accurate quantity measurements</li>
                        <li>Set competitive pricing</li>
                        <li>Only mark as sellable when ready</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/seed-lot-validation.js') }}"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate lot code based on variety and year
    const varietySelect = document.getElementById('variety_id');
    const yearInput = document.getElementById('production_year');
    const lotCodeInput = document.getElementById('lot_code');
    
    function generateLotCode() {
        const variety = varietySelect.options[varietySelect.selectedIndex];
        const year = yearInput.value;
        
        if (variety.value && year && !lotCodeInput.value) {
            const varietyName = variety.text.split(' ')[0].toUpperCase();
            const timestamp = Date.now().toString().slice(-4);
            lotCodeInput.value = `LOT-${year}-${varietyName}-${timestamp}`;
        }
    }
    
    varietySelect.addEventListener('change', generateLotCode);
    yearInput.addEventListener('change', generateLotCode);
    
    // Format price input
    const priceInput = document.getElementById('price_per_unit');
    priceInput.addEventListener('input', function() {
        // Remove non-numeric characters except decimal point
        this.value = this.value.replace(/[^0-9.]/g, '');
    });
});
</script>
@endpush