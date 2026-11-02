@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <p class="text-[11px] text-slate-500">
            Menampilkan
            @if ($paginator->firstItem())
                <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
                &ndash;
                <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-semibold text-slate-700">0</span>
            @endif
            dari
            <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
            data
        </p>

        <div class="flex items-center gap-1.5">
            {{-- Previous Page Link --}}
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

            {{-- Page Numbers --}}
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="pagination-btn pagination-btn-disabled" aria-hidden="true">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-btn pagination-btn-active" aria-current="page">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="pagination-btn"
                                    aria-label="Ke halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Mobile page indicator --}}
            <span class="pagination-btn pagination-btn-active sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Next Page Link --}}
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
