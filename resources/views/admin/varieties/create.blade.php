@extends('layouts.vertical', ['title' => 'Create Variety', 'subTitle' => 'Management'])

@section('css')
    @vite(['node_modules/dropzone/dist/dropzone.css'])
@endsection

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">New Variety</h4>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.varieties.store') }}" method="POST" enctype="multipart/form-data" id="varietyImageForm">
                    @csrf
                    <input type="hidden" name="return" value="{{ request()->input('return', route('admin.varieties.index')) }}">

                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Name Field -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <!-- Commodity Field -->
                            <div class="mb-3">
                                <label for="commodity_id" class="form-label">Commodity <span class="text-danger">*</span></label>
                                <select class="form-select @error('commodity_id') is-invalid @enderror" 
                                        id="commodity_id" name="commodity_id" required>
                                    <option value="">Select Commodity</option>
                                    @foreach($commodities as $commodity)
                                        <option value="{{ $commodity->id }}" @selected(old('commodity_id', request('commodity_id')) == $commodity->id)>
                                            {{ $commodity->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('commodity_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <small class="text-muted d-block mb-2">SKU will be auto-generated when saved.</small>
                        </div>
                        <div class="col-lg-6">
                            <!-- Minimum Limit Field -->
                            <div class="mb-3">
                                <label for="minimum_limit" class="form-label">Minimum Stock Limit (kg)</label>
                                <input type="number" class="form-control @error('minimum_limit') is-invalid @enderror" 
                                       id="minimum_limit" name="minimum_limit" value="{{ old('minimum_limit') }}" step="1" min="0" inputmode="numeric">
                                @error('minimum_limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Status Field -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status">
                                    <option value="available" {{ old('status', 'available') == 'available' ? 'selected' : '' }}>Available</option>
                                    <option value="out_of_stock" {{ old('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                    <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Description Field -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
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

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="hidden" name="temp_image_path" id="tempImagePath" value="{{ old('temp_image_path') }}">
                                <div class="dropzone">
                                    <div class="fallback">
                                        <input id="varietyImageInput" name="image" type="file" accept="image/*">
                                    </div>
                                    <div class="dz-message needsclick">
                                        <i class="h1 bx bx-cloud-upload"></i>
                                        <h3>Drop files here or click to upload.</h3>
                                        <span class="text-muted fs-13">
                                            Only 1 image (jpg, jpeg, png, webp) maximum 10MB.
                                        </span>
                                    </div>
                                </div>
                                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                                <div id="imagePreviewContainer" class="mt-2 d-none">
                                    <div class="border rounded p-2 d-inline-block">
                                        <img id="imagePreview" class="img-fluid rounded d-block" src="#" alt="Image preview" style="width:120px;height:120px;object-fit:cover;" />
                                    </div>
                                </div>
                                <small class="text-muted">Pilih 1 gambar saja (jpg, jpeg, png, webp) maksimal 10MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ sanitizeReturnUrl(request()->input('return'), route('admin.varieties.index')) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Variety</button>
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
        const tempInput = document.getElementById('tempImagePath');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
        if (submitBtn && !submitBtn.dataset.originalLabel) {
            submitBtn.dataset.originalLabel = submitBtn.innerHTML;
        }

        const showAlert = function(message) {
            if (!form) return;
            const existingAlert = form.querySelector('.alert-danger');
            if (existingAlert) existingAlert.remove();
            const html = '<div class="alert alert-danger">' + message + '</div>';
            form.insertAdjacentHTML('afterbegin', html);
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        const resetButton = function() {
            if (!submitBtn) return;
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalLabel || submitBtn.innerHTML;
        };

        const setButtonLoading = function(label) {
            if (!submitBtn) return;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + label;
        };

        let submitInProgress = false;
        let submitWatchdogId = null;

        const armWatchdog = function() {
            if (submitWatchdogId) {
                clearTimeout(submitWatchdogId);
            }
            submitWatchdogId = setTimeout(function() {
                submitInProgress = false;
                resetButton();
            }, 20000);
        };

        const disarmWatchdog = function() {
            if (!submitWatchdogId) return;
            clearTimeout(submitWatchdogId);
            submitWatchdogId = null;
        };

        const setButtonLoadingSafe = function(label) {
            setButtonLoading(label);
            armWatchdog();
        };

        // Integer-only guard for numeric inputs (minimum_limit only)
        const integerIds = ['minimum_limit'];
        integerIds.forEach(function(id){
            const el = document.getElementById(id);
            if (!el) return;
            const sanitize = function(val){
                // Only digits (0-9), remove dots/commas/other characters
                const digits = String(val).replace(/[^0-9]/g,'');
                return digits;
            };
            const applySanitize = function(){
                const clean = sanitize(el.value);
                el.value = clean;
            };
            el.addEventListener('input', applySanitize);
            el.addEventListener('change', applySanitize);
            el.addEventListener('blur', applySanitize);
            // Prevent enter decimal separators
            el.addEventListener('keypress', function(e){
                const ch = e.key;
                if (!/[0-9]/.test(ch)) {
                    e.preventDefault();
                }
            });
        });

        let dz = null;
        if (typeof Dropzone !== 'undefined' && form) {
            Dropzone.autoDiscover = false;
            const token = document.querySelector('input[name="_token"]').value;
            dz = new Dropzone(form.querySelector('.dropzone'), {
                url: "{{ route('admin.varieties.temp-image') }}",
                maxFiles: 1,
                maxFilesize: 10,
                paramName: 'image',
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
                clickable: form.querySelector('.dropzone'),
                addRemoveLinks: true,
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                dictFileTooBig: 'File terlalu besar (Maks 10MB).'
            });

            dz.on('addedfile', function(file){
                if (tempInput) tempInput.value = '';
                if (submitBtn && !submitInProgress) {
                    submitBtn.disabled = true;
                }
                if (preview && container) {
                    preview.src = URL.createObjectURL(file);
                    container.classList.remove('d-none');
                }
            });

            dz.on('sending', function(){
                if (submitBtn && !submitInProgress) {
                    submitBtn.disabled = true;
                }
            });

            dz.on('success', function(file, response){
                if (response && response.path && tempInput) {
                    tempInput.value = response.path;
                }
                if (submitBtn && !submitInProgress) {
                    submitBtn.disabled = false;
                }
            });

            dz.on('error', function(file, message, xhr){
                let msg = 'Gagal upload gambar.';
                if (typeof message === 'string') {
                    msg = message;
                } else if (message && message.message) {
                    msg = message.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.message) msg = data.message;
                    } catch (e) {
                        msg = 'Gagal upload gambar.';
                    }
                }
                showAlert(msg);
                dz.removeFile(file);
                if (submitBtn && !submitInProgress) {
                    submitBtn.disabled = false;
                }
            });

            dz.on('removedfile', function(){
                if (tempInput) tempInput.value = '';
                if (preview && container) {
                    preview.src = '#';
                    container.classList.add('d-none');
                }
                if (submitBtn && !submitInProgress) {
                    submitBtn.disabled = false;
                }
            });
        }

        if (form) {
            form.addEventListener('invalid', function() {
                submitInProgress = false;
                disarmWatchdog();
                resetButton();
            }, true);
        }

        if (submitBtn && form) {
            submitBtn.addEventListener('click', function(ev) {
                if (submitInProgress) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    return;
                }

                if (dz && dz.getUploadingFiles().length > 0) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    showAlert('Upload gambar masih berjalan.');
                    return;
                }

                const tempPath = (tempInput ? String(tempInput.value || '').trim() : '');
                const hasFallbackFile = !!(input && input.files && input.files.length > 0);

                if (!tempPath && (dz || !hasFallbackFile)) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    showAlert('Gambar wajib diunggah.');
                    return;
                }

                if (!form.checkValidity()) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    form.reportValidity();
                }
            }, true);
        }

        window.addEventListener('error', function() {
            if (!submitInProgress) return;
            submitInProgress = false;
            disarmWatchdog();
            resetButton();
        });

        window.addEventListener('unhandledrejection', function() {
            if (!submitInProgress) return;
            submitInProgress = false;
            disarmWatchdog();
            resetButton();
        });

        if (input && preview && container) {
            input.addEventListener('change', function(e){
                const file = e.target.files && e.target.files[0];
                if (file && dz) {
                    dz.removeAllFiles(true);
                    dz.addFile(file);
                    input.value = '';
                    return;
                }
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

        if (form) {
            form.addEventListener('submit', async function(e){
                e.preventDefault();
                if (submitInProgress) {
                    return;
                }

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                if (dz && dz.getUploadingFiles().length > 0) {
                    showAlert('Upload gambar masih berjalan.');
                    return;
                }
                const tempPath = tempInput ? tempInput.value : '';
                if (dz && dz.getAcceptedFiles().length > 0 && tempPath === '') {
                    showAlert('Upload gambar belum selesai.');
                    return;
                }
                if (!tempPath && (!input || !input.files || input.files.length === 0)) {
                    showAlert('Gambar wajib diunggah.');
                    return;
                }

                setButtonLoadingSafe('Saving...');
                submitInProgress = true;

                const fd = new FormData(form);
                fd.delete('image');

                let controller;
                let timeoutId;
                try {
                    controller = new AbortController();
                    timeoutId = setTimeout(() => controller.abort(), 15000);
                    const res = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        signal: controller.signal,
                        redirect: 'follow'
                    });
                    if (res.redirected) {
                        window.location.href = res.url;
                        return;
                    }
                    if (!res.ok) {
                        const data = await res.json().catch(() => null);
                        if (data && data.errors) {
                            let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                            Object.values(data.errors).flat().forEach(err => {
                                errorHtml += '<li>' + err + '</li>';
                            });
                            errorHtml += '</ul></div>';
                            const existingAlert = form.querySelector('.alert-danger');
                            if (existingAlert) existingAlert.remove();
                            form.insertAdjacentHTML('afterbegin', errorHtml);
                        } else if (data && data.message) {
                            showAlert(data.message);
                        } else {
                            showAlert('Terjadi kesalahan. Silakan coba lagi.');
                        }
                    }
                } catch(err) {
                    if (err && err.name === 'AbortError') {
                        showAlert('Koneksi terlalu lama. Silakan coba lagi.');
                    } else {
                        showAlert('Network error. Silakan cek koneksi dan coba lagi.');
                    }
                } finally {
                    if (timeoutId) clearTimeout(timeoutId);
                    submitInProgress = false;
                    disarmWatchdog();
                    resetButton();
                }
            });
        }
    });
</script>
@vite(['node_modules/dropzone/dist/dropzone-min.js'])
@endpush

@endsection
