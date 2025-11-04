@if ($paginator->hasPages())
<div class="d-flex justify-content-between align-items-center">
    {{-- Results info --}}
    <div class="pagination-info">
        <p class="text-muted mb-0 small">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </p>
    </div>

    {{-- Pagination Links --}}
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-rounded mb-0">
            {{-- First Page Link --}}
            @if ($paginator->currentPage() > 3)
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url(1) }}" aria-label="First">
                        <span aria-hidden="true">First</span>
                    </a>
                </li>
            @endif

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            @endif

            {{-- Page Numbers (Limited to 3-4 pages) --}}
            @php
                $start = max(1, $paginator->currentPage() - 1);
                $end = min($paginator->lastPage(), $paginator->currentPage() + 1);
                
                // Ensure we show at least 3 pages when possible
                if ($end - $start < 2) {
                    if ($start == 1) {
                        $end = min($paginator->lastPage(), $start + 2);
                    } else {
                        $start = max(1, $end - 2);
                    }
                }
            @endphp

            @if ($start > 1)
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active">
                        <span class="page-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endif
            @endfor

            @if ($end < $paginator->lastPage())
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </span>
                </li>
            @endif

            {{-- Last Page Link --}}
            @if ($paginator->currentPage() < $paginator->lastPage() - 2)
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Last">
                        <span aria-hidden="true">Last</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
</div>
@endif