@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="min-w-0">
        @if(isset($breadcrumb))
            <div class="mb-3 flex items-center gap-2 text-[11px] font-semibold text-slate-400">
                {{ $breadcrumb }}
            </div>
        @endif

        @if($eyebrow)
            <div class="page-eyebrow">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                {{ $eyebrow }}
            </div>
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
