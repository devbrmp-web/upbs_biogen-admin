@extends('layouts.vertical', ['title' => 'Seed Lots', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Seed Lots</h4>
                    @php
                        // Build sanitized return URL (remove ajax flags, keep allowed filters) and ensure it's within admin area
                        $returnUrl = request()->fullUrl();
                        $parts = parse_url($returnUrl);
                        $path = $parts['path'] ?? '';
                        $q = [];
                        if (!empty($parts['query'])) { parse_str($parts['query'], $q); }
                        unset($q['ajax'], $q['X-Requested-With']);
                        $allowed = ['q','search','variety_id','seed_class_id','is_sellable','page'];
                        $q = array_intersect_key($q, array_flip($allowed));
                        $rawSanitizedReturn = url($path) . (count($q) ? ('?' . http_build_query($q)) : '');
                        $sanitizedReturn = sanitizeReturnUrl($rawSanitizedReturn, route('admin.seed-lots.index'));
                    @endphp
                    <a href="{{ route('admin.seed-lots.create', array_merge(request()->query(), ['return' => $sanitizedReturn])) }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Seed Lot
                    </a>
                </div>

                <!-- Search & Filter Form -->
                <form method="GET" action="{{ route('admin.seed-lots.index') }}" class="mb-3" id="searchForm" data-index-url="{{ route('admin.seed-lots.index') }}" role="search">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="q" class="form-control" placeholder="Search by lot code..." value="{{ request('q') }}" id="searchInput">
                        </div>
                        <div class="col-md-2">
                            <select name="variety_id" class="form-select" id="varietyFilter">
                                <option value="">All Varieties</option>
                                @foreach($varieties as $variety)
                                    <option value="{{ $variety->id }}" {{ request('variety_id') == $variety->id ? 'selected' : '' }}>
                                        {{ $variety->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="seed_class_id" class="form-select" id="seedClassFilter">
                                <option value="">All Seed Classes</option>
                                @foreach($seedClasses as $seedClass)
                                    <option value="{{ $seedClass->id }}" {{ request('seed_class_id') == $seedClass->id ? 'selected' : '' }}>
                                        {{ $seedClass->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_sellable" class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_sellable') === '1' ? 'selected' : '' }}>Sellable</option>
                                <option value="0" {{ request('is_sellable') === '0' ? 'selected' : '' }}>Not Sellable</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bx bx-search"></i> Search
                                </button>
                                @php
                                    $hasFilter = filled(request('q')) || filled(request('variety_id')) || filled(request('seed_class_id')) || (request()->has('is_sellable') && request('is_sellable') !== '');
                                @endphp
                                <a href="{{ route('admin.seed-lots.index') }}" id="clearFiltersBtn" class="btn btn-outline-secondary {{ $hasFilter ? '' : 'd-none' }}" title="Clear all filters" aria-label="Clear filters">
                                    <i class="bx bx-x"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div id="list-root" class="position-relative">
                    <div id="sr-status" class="visually-hidden" aria-live="polite"></div>
                    <div id="loadingOverlay" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75" style="z-index: 10;" aria-busy="false">
                        <div class="spinner-border text-primary" role="status" aria-label="Loading seed lots">
                            <span class="ms-2">Loading list...</span>
                        </div>
                    </div>
                    @include('admin.seed-lots.partials.table-content')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the seed lot "<span id="deleteItemName"></span>"?</p>
                <p class="text-danger"><small><i class="bx bx-info-circle"></i> This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="return" value="{{ $sanitizedReturn }}">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// AJAX Progressive Enhancement for Seed Lots (consistent with other list pages)
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    let currentController = null;
    const searchInput = document.getElementById('searchInput');
    const varietyFilter = document.getElementById('varietyFilter');
    const seedClassFilter = document.getElementById('seedClassFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchForm = document.getElementById('searchForm');
    const listRoot = document.getElementById('list-root');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const srStatus = document.getElementById('sr-status');
    const clearBtn = document.getElementById('clearFiltersBtn');

    // Debounced search function
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performAjaxSearch();
        }, 500);
    }

    // Show/hide loading state
    function setLoading(isLoading) {
        if (!loadingOverlay) return;
        if (isLoading) {
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.setAttribute('aria-busy', 'true');
            srStatus && (srStatus.textContent = 'Loading, please wait...');
        } else {
            loadingOverlay.classList.add('d-none');
            loadingOverlay.setAttribute('aria-busy', 'false');
            srStatus && (srStatus.textContent = 'List updated.');
        }
    }

    function hasActiveFilters(urlObj) {
        const q = urlObj.searchParams.get('q') || urlObj.searchParams.get('search') || '';
        const variety = urlObj.searchParams.get('variety_id') || '';
        const seedClass = urlObj.searchParams.get('seed_class_id') || '';
        const sellable = urlObj.searchParams.get('is_sellable');
        return (q && q.trim().length) || variety || seedClass || (sellable !== null && sellable !== '');
    }

    function updateClearVisibility(urlObj) {
        if (!clearBtn) return;
        const visible = hasActiveFilters(urlObj);
        clearBtn.classList.toggle('d-none', !visible);
    }

    // Perform AJAX search
    function performAjaxSearch() {
        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');

        setLoading(true);

        const indexUrl = searchForm.getAttribute('data-index-url') || `{{ route('admin.seed-lots.index') }}`;
        // Abort previous request if any
        if (currentController) currentController.abort();
        currentController = new AbortController();
        fetch(`${indexUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: currentController.signal,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Parse the response and replace list-body
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newBody = tmp.querySelector('#list-body');
            const oldBody = listRoot.querySelector('#list-body');
            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
                attachPaginationHandlers();
            }

            // Update URL without page reload; reset page on filter change
            const url = new URL(window.location);
            // Remove page param when filters/search changed
            url.searchParams.delete('page');
            for (const [key, value] of params.entries()) {
                if (key === 'ajax') continue;
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            window.history.pushState({}, '', url);
            updateClearVisibility(url);
        })
        .catch(error => {
            console.error('AJAX search failed:', error);
            // Fallback to normal form submission
            searchForm.submit();
        })
        .finally(() => {
            setLoading(false);
        });
    }

    // Attach pagination click handlers
    function attachPaginationHandlers() {
        const paginationContainer = listRoot.querySelector('#paginationContainer');
        if (!paginationContainer) return;
        const paginationLinks = paginationContainer.querySelectorAll('a[href]');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                url.searchParams.append('ajax', '1');
                setLoading(true);
                // Abort previous request
                if (currentController) currentController.abort();
                currentController = new AbortController();
                fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    signal: currentController.signal,
                    credentials: 'same-origin'
                })
                .then(resp => resp.text())
                .then(html => {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = html;
                    const newBody = tmp.querySelector('#list-body');
                    const oldBody = listRoot.querySelector('#list-body');
                    if (newBody && oldBody) {
                        oldBody.replaceWith(newBody);
                        attachPaginationHandlers();
                    }
                    // Update URL without ajax flag
                    try {
                        const clean = new URL(this.href);
                        clean.searchParams.delete('ajax');
                        window.history.pushState({}, '', clean.toString());
                        updateClearVisibility(clean);
                    } catch {
                        window.history.pushState({}, '', this.href);
                        updateClearVisibility(new URL(window.location.href));
                    }
                })
                .catch(error => {
                    console.error('AJAX pagination failed:', error);
                    window.location.href = this.href;
                })
                .finally(() => setLoading(false));
            });
        });
    }

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
    }

    if (varietyFilter) {
        varietyFilter.addEventListener('change', performAjaxSearch);
    }

    if (seedClassFilter) {
        seedClassFilter.addEventListener('change', performAjaxSearch);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', performAjaxSearch);
    }

    // Initial pagination handlers
    attachPaginationHandlers();

    // Prevent default form submission when AJAX is available
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performAjaxSearch();
    });

    // Handle Delete Form via AJAX
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const url = this.action;
            const token = this.querySelector('input[name="_token"]').value;
            
            // Show loading state on button
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modalEl = document.getElementById('deleteModal');
                    if (typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    } else if (typeof $ !== 'undefined') {
                        $(modalEl).modal('hide');
                    }
                    
                    // Show success message
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    const listRoot = document.getElementById('list-root');
                    
                    // Remove existing alerts
                    const container = listRoot.parentElement;
                    container.querySelectorAll('.alert').forEach(el => el.remove());
                    
                    const tmp = document.createElement('div');
                    tmp.innerHTML = alertHtml;
                    listRoot.before(tmp.firstElementChild);

                    // Refresh list
                    performAjaxSearch();
                }
            })
            .catch(error => {
                console.error('Delete failed:', error);
                alert('Failed to delete seed lot. Please try again.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
    }

    // Clear filters (AJAX mode)
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Reset form fields
            if (searchInput) searchInput.value = '';
            if (varietyFilter) varietyFilter.value = '';
            if (seedClassFilter) seedClassFilter.value = '';
            if (statusFilter) statusFilter.value = '';

            const url = new URL(`{{ route('admin.seed-lots.index') }}`, window.location.origin);
            url.searchParams.set('ajax', '1');
            setLoading(true);
            // Abort previous request
            if (currentController) currentController.abort();
            currentController = new AbortController();
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                signal: currentController.signal,
                credentials: 'same-origin'
            })
            .then(resp => resp.text())
            .then(html => {
                const tmp = document.createElement('div');
                tmp.innerHTML = html;
                const newBody = tmp.querySelector('#list-body');
                const oldBody = listRoot.querySelector('#list-body');
                if (newBody && oldBody) {
                    oldBody.replaceWith(newBody);
                    attachPaginationHandlers();
                }
                // Push clean URL
                window.history.pushState({}, '', `{{ route('admin.seed-lots.index') }}`);
                updateClearVisibility(new URL(window.location.href));
            })
            .catch(() => {
                window.location.href = `{{ route('admin.seed-lots.index') }}`;
            })
            .finally(() => setLoading(false));
        });
    }

    // Handle back/forward navigation
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        // Sync form values with URL
        if (searchInput) searchInput.value = url.searchParams.get('q') || url.searchParams.get('search') || '';
        if (varietyFilter) varietyFilter.value = url.searchParams.get('variety_id') || '';
        if (seedClassFilter) seedClassFilter.value = url.searchParams.get('seed_class_id') || '';
        if (statusFilter) statusFilter.value = url.searchParams.get('is_sellable') ?? '';
        // Fetch current state
        url.searchParams.set('ajax', '1');
        setLoading(true);
        if (currentController) currentController.abort();
        currentController = new AbortController();
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            signal: currentController.signal,
            credentials: 'same-origin'
        })
        .then(resp => resp.text())
        .then(html => {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newBody = tmp.querySelector('#list-body');
            const oldBody = listRoot.querySelector('#list-body');
            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
                attachPaginationHandlers();
            }
            updateClearVisibility(new URL(window.location.href));
        })
        .catch(err => console.error(err))
        .finally(() => setLoading(false));
    });
});

function confirmDelete(lotCode) {
    document.getElementById('deleteItemName').textContent = lotCode;
    document.getElementById('deleteForm').action = '{{ route("admin.seed-lots.destroy", ":lotCode") }}'.replace(':lotCode', lotCode);
    
    // Try Bootstrap modal first
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    } else if (typeof $ !== 'undefined') {
        // Fallback to jQuery modal
        $('#deleteModal').modal('show');
    } else {
        // If no modal library available, show error message instead of browser confirm
        alert('Modal library not available. Please refresh the page and try again.');
    }
}
</script>
@endpush

@push('styles')
<style>
/* scoped pagination size fix for Seed Lots page only */
.seed-lots-pagination .pagination .page-link {
    font-size: .875rem;
    padding: .375rem .75rem;
}
</style>
@endpush
