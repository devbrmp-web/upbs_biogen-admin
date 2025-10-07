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

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $variety->name) }}" required maxlength="100" />
                                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="commodity_id" class="form-label">Commodity</label>
                                <select class="form-select" id="commodity_id" name="commodity_id" required>
                                    <option value="">Select Commodity</option>
                                    @foreach($commodities as $commodity)
                                        <option value="{{ $commodity->id }}" @selected(old('commodity_id', $variety->commodity_id) == $commodity->id)>
                                            {{ $commodity->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('commodity_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000">{{ old('description', $variety->description) }}</textarea>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
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
                                @error('file')<div class="text-danger small">{{ $message }}</div>@enderror
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
                    fd.set('file', files[0]);
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