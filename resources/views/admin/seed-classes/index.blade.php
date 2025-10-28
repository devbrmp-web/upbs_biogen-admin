@extends('layouts.vertical', ['title' => 'Seed Classes', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Seed Classes</h4>
                    <a href="{{ route('admin.seed-classes.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Seed Class
                    </a>
                </div>

                <!-- Search Form -->
                <form method="GET" action="{{ route('admin.seed-classes.index') }}" class="mb-3" role="search">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') ?: request('q') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bx bx-search"></i> Search
                            </button>
                            <a href="{{ route('admin.seed-classes.index') }}" id="clearFilters" class="btn btn-outline-secondary {{ request()->hasAny(['search','q']) ? '' : 'd-none' }}" aria-label="Clear filters">
                                <i class="bx bx-x"></i> Clear
                            </a>
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
                    <div id="loading-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="backdrop-filter: blur(1px); background-color: rgba(255,255,255,0.6);">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                            <span class="ms-2">Loading...</span>
                        </div>
                    </div>
                    @include('admin.seed-classes.partials.table-content')
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
                <p>Are you sure you want to delete the seed class "<span id="deleteItemName"></span>"?</p>
                <p class="text-danger"><small><i class="bx bx-info-circle"></i> This action cannot be undone. All related seed lots will also be affected.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(code, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteForm').action = '{{ route("admin.seed-classes.destroy", ":code") }}'.replace(':code', code);
    
    // Try Bootstrap modal first
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    } else if (typeof $ !== 'undefined') {
        // Fallback to jQuery modal
        $('#deleteModal').modal('show');
    } else {
        // Fallback to native confirm
        if (confirm('Are you sure you want to delete the seed class "' + name + '"?')) {
            document.getElementById('deleteForm').submit();
        }
    }
}

// Progressive enhancement for AJAX list updates
document.addEventListener('DOMContentLoaded', () => {
    const listRoot = document.getElementById('list-root');
    const srStatus = document.getElementById('sr-status');
    const loadingOverlay = document.getElementById('loading-overlay');
    const searchInput = document.getElementById('search');
    const clearBtn = document.getElementById('clearFilters');
    const indexUrl = '{{ route("admin.seed-classes.index") }}';

    let debounceTimer = null;
    let currentController = null;

    function setLoading(isLoading, msg = '') {
        if (!loadingOverlay) return;
        loadingOverlay.classList.toggle('d-none', !isLoading);
        if (srStatus) {
            srStatus.textContent = msg || (isLoading ? 'Loading results…' : 'List updated');
        }
        listRoot?.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function buildUrl(baseUrl, params) {
        const url = new URL(baseUrl, window.location.origin);
        Object.entries(params || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null && String(value).length) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        url.searchParams.set('ajax', '1');
        return url.toString();
    }

    async function updateList(url) {
        try {
            setLoading(true, 'Loading results…');
            if (currentController) currentController.abort();
            currentController = new AbortController();
            const resp = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
                signal: currentController.signal,
            });
            const html = await resp.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newListBody = tmp.querySelector('#list-body');
            const oldListBody = listRoot.querySelector('#list-body');
            if (newListBody && oldListBody) {
                oldListBody.replaceWith(newListBody);
                setLoading(false, 'List updated');
            } else {
                // Fallback: replace entire listRoot contents
                listRoot.innerHTML = html;
                setLoading(false, 'List updated');
            }
            // Clean URL in history (remove ajax)
            try {
                const clean = new URL(url);
                clean.searchParams.delete('ajax');
                window.history.pushState({}, '', clean.toString());
                updateClearVisibility(clean);
            } catch {}
        } catch (err) {
            console.error(err);
            setLoading(false, 'Failed to load results');
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger mt-2';
            alert.textContent = 'Failed to load results. Please try again.';
            listRoot.appendChild(alert);
        }
    }

    function updateClearVisibility(urlObj) {
        if (!clearBtn) return;
        const hasSearch = (urlObj.searchParams.get('search') || urlObj.searchParams.get('q') || '').trim().length > 0;
        clearBtn.classList.toggle('d-none', !hasSearch);
    }

    function onSearchChange() {
        const q = searchInput?.value?.trim() || '';
        const url = buildUrl(indexUrl, { search: q });
        updateList(url);
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(onSearchChange, 300);
        });
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                onSearchChange();
            }
        });
    }

    // Intercept form submission as progressive enhancement
    const searchForm = document.querySelector('form[role="search"]');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            onSearchChange();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            const url = buildUrl(indexUrl, {});
            updateList(url);
        });
    }

    // Handle pagination links
    listRoot.addEventListener('click', (e) => {
        const target = e.target.closest('a');
        if (!target) return;
        // Only intercept pagination links inside list-root
        const href = target.getAttribute('href');
        const isPagination = target.closest('.card-footer') || (href && href.includes('page='));
        if (isPagination) {
            e.preventDefault();
            // Preserve current search
            const q = searchInput?.value?.trim() || '';
            const urlObj = new URL(href, window.location.origin);
            if (q) urlObj.searchParams.set('search', q);
            urlObj.searchParams.set('ajax', '1');
            const finalUrl = urlObj.toString();
            updateList(finalUrl);
        }
    });

    // Restore state on back/forward
    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        if (searchInput) searchInput.value = url.searchParams.get('search') || url.searchParams.get('q') || '';
        url.searchParams.set('ajax', '1');
        updateList(url.toString());
        updateClearVisibility(new URL(window.location.href));
    });

    // Initialize clear visibility
    updateClearVisibility(new URL(window.location.href));
});
</script>
@endpush
