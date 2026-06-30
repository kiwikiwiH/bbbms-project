@if ($paginator->hasPages())
    <nav class="admin-pagination-nav" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="admin-pagination-btn disabled">Previous</span>
        @else
            <a class="admin-pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        <span class="admin-pagination-info">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="admin-pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="admin-pagination-btn disabled">Next</span>
        @endif
    </nav>
@endif
