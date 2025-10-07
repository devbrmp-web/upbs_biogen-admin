@extends('layouts.vertical', ['title' => 'Edit Seed Lot', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Edit Seed Lot: {{ $seedLot->lot_code }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.seed-lots.show', $seedLot) }}" class="btn btn-info">
                            <i class="bx bx-show"></i> View
                        </a>
                        <a href="{{ route('admin.seed-lots.index') }}" class="btn btn-light">
                            <i class="bx bx-arrow-back"></i> Back to List
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.seed-lots.update', $seedLot) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lot_code" class="form-label">Lot Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lot_code') is-invalid @enderror" 
                                       id="lot_code" name="lot_code" value="{{ old('lot_code', $seedLot->lot_code) }}" 
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
                                       id="production_year" name="production_year" value="{{ old('production_year', $seedLot->production_year) }}" 
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
                                        <option value="{{ $variety->id }}" {{ old('variety_id', $seedLot->variety_id) == $variety->id ? 'selected' : '' }}>
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
                                        <option value="{{ $seedClass->id }}" {{ old('seed_class_id', $seedLot->seed_class_id) == $seedClass->id ? 'selected' : '' }}>
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
                                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror" 
                                       id="quantity" name="quantity" value="{{ old('quantity', $seedLot->quantity) }}" 
                                       min="0" required>
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
                                    <option value="kg" {{ old('unit', $seedLot->unit) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                    <option value="g" {{ old('unit', $seedLot->unit) == 'g' ? 'selected' : '' }}>Gram (g)</option>
                                    <option value="ton" {{ old('unit', $seedLot->unit) == 'ton' ? 'selected' : '' }}>Ton</option>
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
                                       id="price_per_unit" name="price_per_unit" value="{{ old('price_per_unit', $seedLot->price_per_unit) }}" 
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
                                  placeholder="Enter description for this seed lot...">{{ old('description', $seedLot->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input @error('is_sellable') is-invalid @enderror" 
                                   type="checkbox" id="is_sellable" name="is_sellable" value="1" 
                                   {{ old('is_sellable', $seedLot->is_sellable) ? 'checked' : '' }}>
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
                            <i class="bx bx-save"></i> Update Seed Lot
                        </button>
                        <a href="{{ route('admin.seed-lots.show', $seedLot) }}" class="btn btn-secondary">
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
                <h5 class="card-title">Seed Lot Info</h5>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-semibold">Current Code:</td>
                        <td><code>{{ $seedLot->lot_code }}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Variety:</td>
                        <td>{{ $seedLot->variety->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Seed Class:</td>
                        <td>{{ $seedLot->seedClass->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Current Status:</td>
                        <td>
                            @if($seedLot->is_sellable)
                                <span class="badge bg-success">Sellable</span>
                            @else
                                <span class="badge bg-warning">Not Sellable</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Created:</td>
                        <td>{{ $seedLot->created_at?->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Updated:</td>
                        <td>{{ $seedLot->updated_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Guidelines</h5>
                <div class="alert alert-info">
                    <h6><i class="bx bx-info-circle"></i> Editing Tips</h6>
                    <ul class="mb-0 small">
                        <li>Ensure lot code remains unique</li>
                        <li>Update quantity if stock changes</li>
                        <li>Adjust pricing as needed</li>
                        <li>Toggle sellable status carefully</li>
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
    // Format price input
    const priceInput = document.getElementById('price_per_unit');
    priceInput.addEventListener('input', function() {
        // Remove non-numeric characters except decimal point
        this.value = this.value.replace(/[^0-9.]/g, '');
    });
});
</script>
@endpush