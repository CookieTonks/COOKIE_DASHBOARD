@if ($paginator->hasPages())
    <div class="pager">
        @if ($paginator->onFirstPage())
            <span></span>
        @else
            <a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">Anterior</a>
        @endif
        <small>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</small>
        @if ($paginator->hasMorePages())
            <a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>
        @else
            <span></span>
        @endif
    </div>
@endif
