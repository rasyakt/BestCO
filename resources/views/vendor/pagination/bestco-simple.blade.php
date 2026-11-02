@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman"
        class="flex items-center justify-between gap-3">
        <p class="text-[11px] text-slate-500">Halaman {{ $paginator->currentPage() }}</p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn"
                    aria-label="Halaman sebelumnya">
                    <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn"
                    aria-label="Halaman berikutnya">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                </a>
            @else
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                </span>
            @endif
        </div>
    </nav>
@endif
