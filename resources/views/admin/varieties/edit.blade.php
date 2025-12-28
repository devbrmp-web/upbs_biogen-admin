@extends('layouts.vertical', ['title' => 'Edit Variety', 'subTitle' => 'Management'])

@section('css')
    @vite(['node_modules/dropzone/dist/dropzone.css'])
@endsection

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Edit Variety</h4>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.varieties.update', $variety) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return" value="{{ request()->input('return', route('admin.varieties.index')) }}">

                        <!-- Name Field -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $variety->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Commodity Field -->
                        <div class="mb-3">
                            <label for="commodity_id" class="form-label">Commodity <span class="text-danger">*</span></label>
                            <select class="form-select @error('commodity_id') is-invalid @enderror" 
                                    id="commodity_id" name="commodity_id" required>
                                <option value="">Select Commodity</option>
                                @foreach($commodities as $commodity)
                                    <option value="{{ $commodity->id }}" {{ old('commodity_id', $variety->commodity_id) == $commodity->id ? 'selected' : '' }}>
                                        {{ $commodity->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('commodity_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SKU Info (autogenerate) -->
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <div class="form-text">SKU will be auto-generated based on commodity and name. Current value: <code>{{ $variety->sku }}</code></div>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $variety->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Price Field -->
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (IDR) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('price') is-invalid @enderror" 
                                   id="price" name="price" value="{{ old('price', (int) $variety->price) }}" step="1" min="0" inputmode="numeric" pattern="[0-9]*" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Minimum Limit Field -->
                        <div class="mb-3">
                            <label for="minimum_limit" class="form-label">Minimum Stock Limit (kg)</label>
                            <input type="number" class="form-control @error('minimum_limit') is-invalid @enderror" 
                                   id="minimum_limit" name="minimum_limit" value="{{ old('minimum_limit', $variety->minimum_limit ?? '') }}" step="1" min="0" inputmode="numeric">
                            @error('minimum_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status Field -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status">
                                <option value="available" {{ old('status', $variety->status) == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="out_of_stock" {{ old('status', $variety->status) == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                <option value="discontinued" {{ old('status', $variety->status) == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Help Text -->
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Stock Management:</strong> Total stock (kg) is automatically calculated from sellable Seed Lots with kg units. Planlets are not counted in total kg as they are measured per bottle.
                        </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ sanitizeReturnUrl(request()->input('return'), route('admin.varieties.index')) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Variety Details</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Gallery Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Image Gallery</h4>
                <p class="text-muted mb-4">Upload multiple images for this variety. Drag and drop files or click the area below.</p>

                <!-- Dropzone for Gallery -->
                <form action="{{ route('admin.varieties.images.store', $variety) }}" method="post" enctype="multipart/form-data" class="dropzone" id="galleryDropzone">
                    @csrf
                    <div class="fallback">
                        <input name="images[]" type="file" multiple />
                    </div>
                    <div class="dz-message needsclick">
                        <i class="h1 bx bx-cloud-upload"></i>
                        <h3>Drop files here or click to upload.</h3>
                        <span class="text-muted fs-13">Maximum 6 images allowed. Max 4MB per image.</span>
                    </div>
                </form>

                <!-- Existing Images Grid -->
                <div class="row mt-4" id="gallery-grid">
                    @foreach($variety->images->sortBy('order') as $image)
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card border h-100 {{ $image->is_primary ? 'border-primary' : '' }}">
                                <div class="card-img-top-wrapper" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <img src="{{ asset('storage/' . $image->image_path) }}" class="img-fluid" style="max-height: 100%; object-fit: contain;" alt="Variety Image">
                                </div>
                                <div class="card-body p-2 text-center">
                                    @if($image->is_primary)
                                        <span class="badge bg-primary mb-2">Primary Image</span>
                                    @else
                                        <form action="{{ route('admin.varieties.images.primary', [$variety, $image]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary mb-2">Jadikan gambar thumbnail</button>
                                        </form>
                                    @endif
                                    
                                    <form action="{{ route('admin.varieties.images.destroy', [$variety, $image]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger mb-2"><i class="bx bx-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    @if($variety->images->isEmpty() && $variety->image_path)
                         <!-- Legacy Image Fallback -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card border h-100 border-warning">
                                <div class="card-header bg-warning text-white p-1 text-center small">Legacy Image</div>
                                <div class="card-img-top-wrapper" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <img src="{{ asset('storage/' . $variety->image_path) }}" class="img-fluid" style="max-height: 100%; object-fit: contain;" alt="Legacy Image">
                                </div>
                                <div class="card-body p-2 text-center">
                                    <p class="small text-muted">Please upload new images to migrate to the gallery system.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        // Price input filtering - only allow digits
        const priceEl = document.getElementById('price');
        if (priceEl) {
            priceEl.addEventListener('input', () => {
                priceEl.value = priceEl.value.replace(/[^0-9]/g, '');
            });
        }
        
        // Integer-only guard for numeric fields
        const intFields = ['price', 'minimum_limit'];
        intFields.forEach(function(id){
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('keypress', function(e){
                const char = String.fromCharCode(e.which);
                if (!/[0-9]/.test(char)) {
                    e.preventDefault();
                }
            });
            el.addEventListener('input', function(e){
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        });

        // Dropzone Configuration
        if (document.querySelector('#galleryDropzone')) {
            Dropzone.autoDiscover = false;
            const csrfToken = document.querySelector('input[name="_token"]').value;
            
            const galleryDz = new Dropzone("#galleryDropzone", {
                paramName: "images", // The name that will be used to transfer the file
                maxFilesize: 4, // MB
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
                addRemoveLinks: true,
                uploadMultiple: true,
                parallelUploads: 5,
                maxFiles: 6,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                init: function() {
                    this.on("successmultiple", function(files, response) {
                        // Reload page to show new images
                        window.location.reload();
                    });
                    this.on("error", function(file, message) {
                        console.error(message);
                        alert("Error uploading: " + (message.message || message));
                    });
                }
            });
        }
    });
</script>
@vite(['node_modules/dropzone/dist/dropzone-min.js'])
@endpush

@endsection
