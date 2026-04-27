@extends('layouts.vertical', ['title' => 'Admin Users', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                    <div class="search-bar d-flex align-items-center gap-2" role="search">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search admin users..." value="{{ request('search', request('q')) }}" />
                        <a href="{{ route('admin.admin-users.index') }}" id="clearFilters" class="btn btn-outline-secondary {{ request()->hasAny(['search','q']) ? '' : 'd-none' }}" aria-label="Clear filters">Clear</a>
                    </div>
                    <div>
                        <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                            + Add Admin User
                        </a>
                    </div>
                </div>
                <!-- end row -->
            </div>
            <div id="list-root" class="position-relative">
                <div id="sr-status" class="visually-hidden" aria-live="polite"></div>
                <div id="loading-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="backdrop-filter: blur(1px); background-color: rgba(255,255,255,0.6);">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                        <span class="ms-2">Loading...</span>
                    </div>
                </div>
                @include('admin.admin-users.partials.table-content')
            </div>
        </div>
        <!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

<!-- Bootstrap Delete Modal Removed for SweetAlert2 -->

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const root = document.getElementById('list-root');
    const overlay = document.getElementById('loading-overlay');
    const srStatus = document.getElementById('sr-status');
    const searchInput = document.getElementById('search');
    const clearBtn = document.getElementById('clearFilters');
    const indexUrl = '{{ route("admin.admin-users.index") }}';
    let currentController = null;

    function showOverlay() { if (overlay) overlay.classList.remove('d-none'); }
    function hideOverlay() { if (overlay) overlay.classList.add('d-none'); }
    function announce(msg) { if (srStatus) srStatus.textContent = msg; }

    function buildUrl(baseUrl, extraParams = {}) {
        const url = new URL(baseUrl || window.location.href, window.location.origin);
        const q = searchInput ? searchInput.value.trim() : '';
        // Normalize params
        url.searchParams.delete('q');
        url.searchParams.delete('page');
        if (q) url.searchParams.set('search', q); else url.searchParams.delete('search');
        Object.entries(extraParams).forEach(([k,v]) => {
            if (v !== undefined && v !== null && String(v).length) url.searchParams.set(k, v); else url.searchParams.delete(k);
        });
        url.searchParams.set('ajax', '1');
        return url.toString();
    }

    async function fetchAndRender(url) {
        showOverlay();
        announce('Loading, please wait...');
        try {
            if (currentController) currentController.abort();
            currentController = new AbortController();
            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin',
                signal: currentController.signal,
            });
            if (!resp.ok) throw new Error('Network error');
            const html = await resp.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newBody = tmp.querySelector('#list-body');
            const oldBody = root.querySelector('#list-body');
            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
            } else {
                root.innerHTML = html; // fallback
            }
            hideOverlay();
            announce('List updated.');
            // Clean history URL (remove ajax)
            const clean = new URL(url);
            clean.searchParams.delete('ajax');
            history.pushState({}, '', clean.toString());
            updateClearVisibility(clean);
        } catch (err) {
            hideOverlay();
            announce('Failed to load.');
            console.error(err);
        }
    }

    function updateClearVisibility(urlObj) {
        if (!clearBtn) return;
        const hasSearch = (urlObj.searchParams.get('search') || urlObj.searchParams.get('q') || '').trim().length > 0;
        clearBtn.classList.toggle('d-none', !hasSearch);
    }

    let debounceTimer = null;
    function debounce(fn, delay = 400) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fn.apply(this, args), delay);
        };
    }
    const onSearchChange = debounce(() => {
        const url = buildUrl(indexUrl);
        fetchAndRender(url);
    });

    if (searchInput) {
        searchInput.addEventListener('input', onSearchChange);
        searchInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); onSearchChange(); } });
        searchInput.addEventListener('blur', onSearchChange);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            const url = buildUrl(indexUrl, {});
            fetchAndRender(url);
        });
    }

    // Intercept pagination via event delegation
    if (root) {
        root.addEventListener('click', function(e) {
            const a = e.target.closest('a.page-link');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href) return;
            e.preventDefault();
            const urlObj = new URL(href, window.location.origin);
            // Preserve search
            const q = searchInput ? searchInput.value.trim() : '';
            if (q) urlObj.searchParams.set('search', q); else urlObj.searchParams.delete('search');
            urlObj.searchParams.set('ajax', '1');
            fetchAndRender(urlObj.toString());
        });
    }

    // Handle back/forward
    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        if (searchInput) searchInput.value = url.searchParams.get('search') || url.searchParams.get('q') || '';
        url.searchParams.set('ajax', '1');
        fetchAndRender(url.toString());
        updateClearVisibility(new URL(window.location.href));
    });

    // Initialize clear visibility
    updateClearVisibility(new URL(window.location.href));
});

// Delete confirmation function using SweetAlert2
function confirmDelete(adminName, adminId) {
    window.confirmAction('Hapus Admin?', `Apakah Anda yakin ingin menghapus admin "${adminName}"? Tindakan ini tidak dapat dibatalkan.`, 'error').then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ route('admin.admin-users.index') }}/${adminId}`;
            form.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
