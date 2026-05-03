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

                        <div class="row">
                            <div class="col-lg-6">
                                <!-- Minimum Limit Field -->
                                <div class="mb-3">
                                    <label for="minimum_limit" class="form-label">Minimum Stock Limit (kg)</label>
                                    <input type="number" class="form-control @error('minimum_limit') is-invalid @enderror" 
                                        id="minimum_limit" name="minimum_limit" value="{{ old('minimum_limit', $variety->minimum_limit ?? '') }}" step="1" min="0" inputmode="numeric">
                                    @error('minimum_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
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
                            </div>
                        </div>

                        <!-- Help Text -->
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Stock Management:</strong> Total stock (kg) is automatically calculated from sellable Seed Lots with kg units. Starters are not counted in total kg as they are measured per bottle.
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3"><i class="bx bx-leaf text-success me-1"></i> Karakteristik & Pelepasan Varietas</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="decree_number" class="form-label">Nomor SK Pelepasan</label>
                                <input type="text" class="form-control @error('decree_number') is-invalid @enderror" 
                                       id="decree_number" name="decree_number" value="{{ old('decree_number', $variety->decree_number) }}">
                                @error('decree_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="decree_date" class="form-label">Tanggal Pelepasan</label>
                                <input type="text" class="form-control datepicker @error('decree_date') is-invalid @enderror" 
                                       id="decree_date" name="decree_date" value="{{ old('decree_date', $variety->decree_date) }}">
                                @error('decree_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="origin" class="form-label">Asal/Silsilah</label>
                                <input type="text" class="form-control @error('origin') is-invalid @enderror" 
                                       id="origin" name="origin" value="{{ old('origin', $variety->origin) }}">
                                @error('origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="planting_age" class="form-label">Umur Panen</label>
                                <input type="text" class="form-control @error('planting_age') is-invalid @enderror" 
                                       id="planting_age" name="planting_age" value="{{ old('planting_age', $variety->planting_age) }}">
                                @error('planting_age') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="yield_potential" class="form-label">Potensi Hasil</label>
                                <input type="text" class="form-control @error('yield_potential') is-invalid @enderror" 
                                       id="yield_potential" name="yield_potential" value="{{ old('yield_potential', $variety->yield_potential) }}">
                                @error('yield_potential') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="average_yield" class="form-label">Rata-rata Hasil</label>
                                <input type="text" class="form-control @error('average_yield') is-invalid @enderror" 
                                       id="average_yield" name="average_yield" value="{{ old('average_yield', $variety->average_yield) }}">
                                @error('average_yield') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="primary_trait" class="form-label">Karakteristik Utama</label>
                                <input type="text" class="form-control @error('primary_trait') is-invalid @enderror" 
                                       id="primary_trait" name="primary_trait" value="{{ old('primary_trait', $variety->primary_trait) }}">
                                <small class="form-text text-muted">Contoh: 'Pulen' untuk Padi, 'Biji Besar' untuk Kedelai.</small>
                                @error('primary_trait') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="pest_resistance" class="form-label">Ketahanan Hama</label>
                                <textarea class="form-control @error('pest_resistance') is-invalid @enderror" 
                                          id="pest_resistance" name="pest_resistance" rows="3">{{ old('pest_resistance', $variety->pest_resistance) }}</textarea>
                                @error('pest_resistance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="disease_resistance" class="form-label">Ketahanan Penyakit</label>
                                <textarea class="form-control @error('disease_resistance') is-invalid @enderror" 
                                          id="disease_resistance" name="disease_resistance" rows="3">{{ old('disease_resistance', $variety->disease_resistance) }}</textarea>
                                @error('disease_resistance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="description_summary" class="form-label">Ringkasan Deskripsi/Keunggulan</label>
                                <textarea class="form-control @error('description_summary') is-invalid @enderror" 
                                          id="description_summary" name="description_summary" rows="3">{{ old('description_summary', $variety->description_summary) }}</textarea>
                                @error('description_summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">

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
                        <span class="text-muted fs-13">Maximum 6 images. Maximum 10MB per image.</span>
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
        const form = document.getElementById('varietyImageForm');
        const input = document.getElementById('varietyImageInput');
        const preview = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');
        const dzElement = document.querySelector('.dropzone');
        const indexUrl = "{{ route('admin.varieties.index') }}";

        // Integer-only guard for numeric fields (minimum_limit only)
        const intFields = ['minimum_limit'];
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
                maxFilesize: 10, // MB
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
                        window.toast('Error', "Gagal mengunggah: " + (message.message || message), 'error');
                    });
                }
            });
        }

        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }
    });
</script>
@vite(['node_modules/dropzone/dist/dropzone-min.js'])
@endpush

@endsection
