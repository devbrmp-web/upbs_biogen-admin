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

                <form action="{{ route('admin.varieties.update', $variety) }}" method="POST" enctype="multipart/form-data" id="varietyImageForm">
                    @csrf
                    @method('PUT')

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
                            <div class="form-text">SKU akan digenerate otomatis berdasarkan komoditas dan nama. Nilai saat ini: <code>{{ $variety->sku }}</code></div>
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
                                   id="price" name="price" value="{{ old('price', $variety->price) }}" step="0.01" min="0" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Stock Management Section -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Stock Management</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- BS Stock -->
                                    <div class="col-md-3 mb-3">
                                        <label for="stock_bs_kg" class="form-label">BS Stock (kg)</label>
                                        <input type="number" class="form-control @error('stock_bs_kg') is-invalid @enderror" 
                                               id="stock_bs_kg" name="stock_bs_kg" value="{{ old('stock_bs_kg', $variety->stock_bs_kg ?? '') }}" 
                                               step="1" min="0" inputmode="numeric" placeholder="Masukkan bilangan bulat">
                                        @error('stock_bs_kg')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- FS Stock -->
                                    <div class="col-md-3 mb-3">
                                        <label for="stock_fs_kg" class="form-label">FS Stock (kg)</label>
                                        <input type="number" class="form-control @error('stock_fs_kg') is-invalid @enderror" 
                                               id="stock_fs_kg" name="stock_fs_kg" value="{{ old('stock_fs_kg', $variety->stock_fs_kg ?? '') }}" 
                                               step="1" min="0" inputmode="numeric" placeholder="Masukkan bilangan bulat">
                                        @error('stock_fs_kg')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Planlet (per bottle) -->
                                    <div class="col-md-3 mb-3">
                                        <label for="planlet" class="form-label">Planlet (per botol)</label>
                                        <input type="number" class="form-control @error('planlet') is-invalid @enderror" 
                                               id="planlet" name="planlet" value="{{ old('planlet', $variety->planlet ?? '') }}" 
                                               step="1" min="0" inputmode="numeric" placeholder="Masukkan bilangan bulat">
                                        @error('planlet')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Minimum Limit -->
                                    <div class="col-md-3 mb-3">
                                        <label for="minimum_limit" class="form-label">Minimum Stock Limit (kg)</label>
                                        <input type="number" class="form-control @error('minimum_limit') is-invalid @enderror" 
                                               id="minimum_limit" name="minimum_limit" value="{{ old('minimum_limit', $variety->minimum_limit ?? '') }}" 
                                               step="1" min="0" inputmode="numeric" placeholder="Masukkan bilangan bulat">
                                        @error('minimum_limit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <small class="text-muted d-block">Catatan: BS, FS, Planlet, dan Minimum Limit dicatat sebagai bilangan bulat. Total Stock akan dihitung otomatis dari BS + FS.</small>

                                <!-- Status Field -->
                                <div class="mb-3 mt-3">
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

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <div class="dropzone">
                                    <div class="fallback">
                                        <input id="varietyImageInput" name="image" type="file" accept="image/*">
                                    </div>
                                    <div class="dz-message needsclick">
                                        <i class="h1 bx bx-cloud-upload"></i>
                                        <h3>Drop files here or click to upload.</h3>
                                        <span class="text-muted fs-13">Select one image only (jpg, jpeg, png, webp) maximum 2MB.</span>
                                    </div>
                                </div>
                                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div id="imagePreviewContainer" class="mt-2 d-none">
                                    <div class="border rounded p-2 d-inline-block">
                                        <img id="imagePreview" class="img-fluid rounded d-block" src="#" alt="Image preview" style="width:120px;height:120px;object-fit:cover;" />
                                    </div>
                                </div>
                                @if($variety->image)
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Current Image:</small>
                                        <div class="border rounded p-2 d-inline-block">
                                            <img src="{{ asset('storage/' . $variety->image) }}" alt="{{ $variety->name }}" class="img-fluid rounded d-block" style="width:120px;height:120px;object-fit:cover;" />
                                        </div>
                                    </div>
                                @endif
                                <small class="text-muted">Only 1 image (jpg, jpeg, png, webp) maximum 2MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('admin.varieties.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Variety</button>
                    </div>
                </form>
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

        // Integer-only guard for numeric fields
        const intFields = ['stock_bs_kg', 'stock_fs_kg', 'planlet', 'minimum_limit'];
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
                // Remove non-digit and prevent decimals
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        });

        // Fallback input preview
        if (input && preview && container) {
            input.addEventListener('change', function(e){
                const file = e.target.files && e.target.files[0];
                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    container.classList.remove('d-none');
                } else {
                    preview.src = '#';
                    container.classList.add('d-none');
                }
            });
        }

        // Enable Dropzone drag-and-drop with preview and manual submit
        if (typeof Dropzone !== 'undefined' && form) {
            Dropzone.autoDiscover = false;
            const dz = new Dropzone(form.querySelector('.dropzone'), {
                url: form.action,
                autoProcessQueue: false,
                maxFiles: 1,
                acceptedFiles: 'image/*',
                clickable: form.querySelector('.dropzone'),
            });
            dz.on('addedfile', function(file){
                if (preview && container) {
                    preview.src = URL.createObjectURL(file);
                    container.classList.remove('d-none');
                }
            });
            dz.on('removedfile', function(){
                if (preview && container) {
                    preview.src = '#';
                    container.classList.add('d-none');
                }
            });

            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(form);
                // If a file is added via Dropzone, include it
                const files = dz.getAcceptedFiles();
                if (files && files[0]) {
                    fd.set('image', files[0]);
                }
                try {
                    const res = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        redirect: 'follow'
                    });
                    if (res.redirected) {
                        window.location.href = res.url;
                        return;
                    }
                    if (!res.ok) {
                        console.error('Failed to submit form');
                    }
                } catch(err) {
                    console.error(err);
                }
            });
        }
    });
</script>
@vite(['node_modules/dropzone/dist/dropzone-min.js'])
@endpush

@endsection