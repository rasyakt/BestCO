@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="min-w-0">
        @if(isset($breadcrumb))
            <div class="mb-2 flex items-center gap-2 text-[11px] font-medium text-slate-400">
                {{ $breadcrumb }}
            </div>
        @endif

        @if($eyebrow)
            <p class="mb-1 text-[11px] font-medium text-slate-500">{{ $eyebrow }}</p>
        @endif

        <h1 class="page-title">{{ $title }}</h1>

        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            {{ $actions }}
        </div>
    @endif
</div>
