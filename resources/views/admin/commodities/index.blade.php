@extends('layouts.vertical', ['title' => 'Commodities List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                    <div class="search-bar d-flex align-items-center gap-2" role="search">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search commodities..." value="{{ request('search', request('q')) }}" />
                        <a href="{{ route('admin.commodities.index') }}" id="clearFilters" class="btn btn-outline-secondary {{ request()->hasAny(['search','q']) ? '' : 'd-none' }}" aria-label="Clear filters">Clear</a>
                    </div>
                    <div>
                        <a href="{{ route('admin.commodities.create', ['return' => request()->fullUrl()]) }}" class="btn btn-primary">
                            + Add Commodity
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
                @include('admin.commodities.partials.table-content')
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Bootstrap Delete Modal Removed for SweetAlert2 -->

@push('modals')
<!-- Constraint Error Modal -->
<div class="modal fade" id="constraintErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Gagal Menghapus Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="constraintErrorMessage" class="mb-3">{{ session('constraint_message') ?? 'Data tidak dapat dihapus karena masih memiliki data terkait.' }}</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <a href="#" id="viewStockBtn" class="btn btn-primary">Lihat Detail</a>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show constraint error modal if session has error
    @if(session('constraint_error'))
        var constraintModal = new bootstrap.Modal(document.getElementById('constraintErrorModal'));
        document.getElementById('viewStockBtn').href = "{{ session('constraint_redirect') }}";
        constraintModal.show();
    @endif

    // Delete modal logic (delegated, survives AJAX)
    let targetFormId = null;
    const modalEl = document.getElementById('confirmDeleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    let bsModal = null;
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bsModal = new bootstrap.Modal(modalEl);
    }
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-delete-btn');
        if (!btn) return;
        e.preventDefault();
        targetFormId = btn.getAttribute('data-delete-form');
        
        window.confirmAction('Hapus Komoditas?', 'Apakah Anda yakin ingin menghapus komoditas ini? Tindakan ini tidak dapat dibatalkan.', 'error').then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById(targetFormId);
                if (form) form.submit();
            }
        });
    });

    // Progressive enhancement for search + pagination
    const root = document.getElementById('list-root');
    const overlay = document.getElementById('loading-overlay');
    const srStatus = document.getElementById('sr-status');
    const searchInput = document.getElementById('search');
    const clearBtn = document.getElementById('clearFilters');
    let debounceTimer = null;
    let currentController = null;

    function showOverlay() { if (overlay) { overlay.classList.remove('d-none'); overlay.setAttribute('aria-busy', 'true'); } }
    function hideOverlay() { if (overlay) { overlay.classList.add('d-none'); overlay.setAttribute('aria-busy', 'false'); } }
    function announce(msg) { if (srStatus) srStatus.textContent = msg; }

    function buildUrl(baseUrl) {
        const url = new URL(baseUrl || window.location.href);
        const q = searchInput ? searchInput.value.trim() : '';
        url.searchParams.delete('page');
        if (q) {
            url.searchParams.set('search', q);
            url.searchParams.delete('q');
        } else {
            url.searchParams.delete('search');
            url.searchParams.delete('q');
        }
        url.searchParams.set('ajax', '1');
        return url;
    }

    function updateClearVisibility(url) {
        if (!clearBtn) return;
        const has = (url.searchParams.get('search') || url.searchParams.get('q') || '').trim().length > 0;
        clearBtn.classList.toggle('d-none', !has);
    }

    async function fetchAndRender(url) {
        const bodyContainer = root ? root.querySelector('#list-body') : null;
        if (!bodyContainer) return window.location.assign(url.toString());
        showOverlay();
        announce('Loading, please wait...');
        try {
            if (currentController) currentController.abort();
            currentController = new AbortController();
            const resp = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: currentController.signal, credentials: 'same-origin' });
            if (!resp.ok) throw new Error('Network error');
            const html = await resp.text();
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newBody = tmp.querySelector('#list-body');
            const oldBody = document.getElementById('list-body');
            if (newBody && oldBody) {
                oldBody.replaceWith(newBody);
            } else {
                bodyContainer.innerHTML = html;
            }
            announce('List updated.');
            hideOverlay();
            // Update URL without ajax flag
            const clean = new URL(url.toString());
            clean.searchParams.delete('ajax');
            history.pushState({}, '', clean.toString());
            updateClearVisibility(clean);
            attachPaginationHandlers();
        } catch (err) {
            hideOverlay();
            announce('Failed to load.');
            console.error(err);
        }
    }

    function attachPaginationHandlers() {
        if (!root) return;
        root.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const pageUrl = new URL(a.href);
                // Keep current filters
                const url = buildUrl(window.location.href);
                const p = pageUrl.searchParams.get('page');
                if (p) url.searchParams.set('page', p);
                fetchAndRender(url);
                const clean = new URL(url.toString());
                clean.searchParams.delete('ajax');
                history.pushState({}, '', clean.toString());
                updateClearVisibility(clean);
            });
        });
    }

    function debounce(fn, delay = 400) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    const onSearchChange = debounce(() => {
        const url = buildUrl("{{ route('admin.commodities.index') }}");
        fetchAndRender(url);
    });

    if (searchInput) {
        searchInput.addEventListener('input', onSearchChange);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                onSearchChange();
            }
        });
        searchInput.addEventListener('blur', onSearchChange);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            const url = new URL("{{ route('admin.commodities.index') }}", window.location.origin);
            url.searchParams.set('ajax', '1');
            fetchAndRender(url);
            history.pushState({}, '', "{{ route('admin.commodities.index') }}");
            updateClearVisibility(new URL(window.location.href));
        });
    }

    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        const q = url.searchParams.get('search') || url.searchParams.get('q') || '';
        if (searchInput) searchInput.value = q;
        url.searchParams.set('ajax', '1');
        fetchAndRender(url);
        updateClearVisibility(new URL(window.location.href));
    });

    // Initial pagination handlers
    attachPaginationHandlers();
    // Ensure Clear button visibility on load
    updateClearVisibility(new URL(window.location.href));
});
</script>
@endpush
