@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span style="color:#bdc7d9;margin-right:12px;">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="margin-right:12px;">Previous</a>
        @endif

        <span style="color:#555f6f;">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="margin-left:12px;">Next</a>
        @else
            <span style="color:#bdc7d9;margin-left:12px;">Next</span>
        @endif
    </nav>
@endif
