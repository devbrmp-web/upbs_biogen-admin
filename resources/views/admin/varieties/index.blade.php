@extends('layouts.vertical', ['title' => 'Varieties List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                    <div class="search-bar d-flex align-items-center gap-2" role="search">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search varieties..." value="{{ request('search', request('q')) }}" />
                        <a href="{{ route('admin.varieties.index') }}" id="clearFilters" class="btn btn-outline-secondary {{ request()->hasAny(['search','q','commodity','stock_status']) ? '' : 'd-none' }}" aria-label="Clear filters">Clear</a>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select" id="commodityFilter" style="width: auto;">
                            <option value="">All Commodities</option>
                            @foreach($commodities as $commodity)
                                <option value="{{ $commodity->id }}" @selected(request('commodity') == $commodity->id)>
                                    {{ $commodity->name }}
                                </option>
                            @endforeach
                        </select>
                        <select class="form-select" id="stockFilter" style="width: auto;">
                            <option value="">All Stock Status</option>
                            <option value="available" @selected(in_array(strtolower((string)request('stock_status')), ['available']))>Available</option>
                            <option value="restock" @selected(in_array(strtolower((string)request('stock_status')), ['restock']))>Restock</option>
                            <option value="out-of-stock" @selected(in_array(strtolower((string)request('stock_status')), ['out-of-stock','out of stock','out_of_stock']))>Out of Stock</option>
                        </select>
                        <a href="{{ route('admin.varieties.create', ['return' => request()->fullUrl()]) }}" class="btn btn-primary">
                            + Add Variety
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
                @include('admin.varieties.partials.table-content')
            </div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<!-- Confirmation modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Variety</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this variety? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete modal logic (delegated)
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
        if (bsModal) {
            bsModal.show();
        } else if (typeof $ !== 'undefined') {
            $('#confirmDeleteModal').modal('show');
        } else {
            if (confirm('Are you sure you want to delete this variety?')) {
                const form = document.getElementById(targetFormId);
                if (form) form.submit();
            }
        }
    });
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (!targetFormId) return;
            const form = document.getElementById(targetFormId);
            if (form) form.submit();
            if (bsModal) {
                bsModal.hide();
            } else if (typeof $ !== 'undefined') {
                $('#confirmDeleteModal').modal('hide');
            }
        });
    }

    // Progressive enhancement for filters + pagination
    const root = document.getElementById('list-root');
    const bodyContainer = root ? root.querySelector('#list-body') : null;
    const overlay = document.getElementById('loading-overlay');
    const srStatus = document.getElementById('sr-status');
    const commodityFilter = document.getElementById('commodityFilter');
    const stockFilter = document.getElementById('stockFilter');
    const searchInput = document.getElementById('search');
    const clearBtn = document.getElementById('clearFilters');

    function showOverlay() { if (overlay) overlay.classList.remove('d-none'); }
    function hideOverlay() { if (overlay) overlay.classList.add('d-none'); }
    function announce(msg) { if (srStatus) srStatus.textContent = msg; }

    let currentController = null;

    function buildUrl(baseUrl) {
        const url = new URL(baseUrl || window.location.href);
        const commodity = commodityFilter ? commodityFilter.value : '';
        const stockStatus = stockFilter ? stockFilter.value : '';
        const q = searchInput ? searchInput.value.trim() : '';
        url.searchParams.delete('page');
        if (commodity) {
            url.searchParams.set('commodity', commodity);
        } else {
            url.searchParams.delete('commodity');
        }
        if (stockStatus !== '') {
            url.searchParams.set('stock_status', stockStatus);
        } else {
            url.searchParams.delete('stock_status');
        }
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
        const hasSearch = (url.searchParams.get('search') || url.searchParams.get('q') || '').trim().length > 0;
        const hasCommodity = !!url.searchParams.get('commodity');
        const hasStock = url.searchParams.has('stock_status') && (url.searchParams.get('stock_status') || '').length > 0;
        const show = hasSearch || hasCommodity || hasStock;
        clearBtn.classList.toggle('d-none', !show);
    }

    async function fetchAndRender(url) {
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
            const clean = new URL(url.toString());
            clean.searchParams.delete('ajax');
            history.pushState({}, '', clean.toString());
            updateClearVisibility(clean);
            attachPaginationHandlers();
        } catch (err) {
            hideOverlay();
            announce('Failed to load.');
            console.error(err);
            // Do not force full reload here; keep UX consistent
        }
    }

    function attachPaginationHandlers() {
        if (!root) return;
        root.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const pageUrl = new URL(a.href);
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

    let debounceTimer = null;
    function debounce(fn, delay = 400) {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    const onFilterChange = debounce(() => {
        const url = buildUrl('{{ route('admin.varieties.index') }}');
        fetchAndRender(url);
    });

    [commodityFilter, stockFilter].forEach(el => {
        if (el && bodyContainer) {
            el.addEventListener('change', onFilterChange);
        }
    });

    if (searchInput && bodyContainer) {
        searchInput.addEventListener('input', onFilterChange);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); onFilterChange(); }
        });
        searchInput.addEventListener('blur', onFilterChange);
    }

    if (clearBtn && bodyContainer) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (commodityFilter) commodityFilter.value = '';
            if (stockFilter) stockFilter.value = '';
            if (searchInput) searchInput.value = '';
            const url = new URL('{{ route('admin.varieties.index') }}', window.location.origin);
            url.searchParams.set('ajax', '1');
            fetchAndRender(url);
            history.pushState({}, '', '{{ route('admin.varieties.index') }}');
            updateClearVisibility(new URL(window.location.href));
        });
    }

    // Back/forward restore state
    window.addEventListener('popstate', function() {
        const url = new URL(window.location.href);
        if (commodityFilter) commodityFilter.value = url.searchParams.get('commodity') || '';
        if (stockFilter) {
            const raw = (url.searchParams.get('stock_status') || '').toLowerCase();
            const normalized = (function(s){
                switch(s){
                    case 'available':
                        return 'available';
                    case 'out of stock':
                    case 'out-of-stock':
                    case 'out_of_stock':
                        return 'out-of-stock';
                    case 'restock':
                        return 'restock';
                    default:
                        return '';
                }
            })(raw);
            stockFilter.value = normalized;
        }
        if (searchInput) searchInput.value = url.searchParams.get('search') || url.searchParams.get('q') || '';
        url.searchParams.set('ajax', '1');
        fetchAndRender(url);
        updateClearVisibility(new URL(window.location.href));
    });

    attachPaginationHandlers();
    updateClearVisibility(new URL(window.location.href));
});
</script>
@endpush
