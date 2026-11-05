@props([
    'theme' => 'dark', // 'dark' | 'light'
    'size' => 'md',    // 'sm' | 'md' | 'lg'
    'withSubtitle' => true,
])

@php
    $identity = \App\Services\Setting\AppIdentityService::get();
    $appName = $identity['app_name'];
    $appTagline = $identity['app_tagline'];
    $hasCustomLogo = $identity['has_custom_logo'];
    $logoUrl = $identity['app_logo_url'];

    $iconSize = match($size) {
        'sm' => 'h-8 w-8',
        'lg' => 'h-13 w-13',
        default => 'h-10 w-10',
    };

    $bestTextSize = match($size) {
        'sm' => 'text-lg',
        'lg' => 'text-2xl',
        default => 'text-xl',
    };

    $subtitleSize = match($size) {
        'sm' => 'text-[8.5px]',
        'lg' => 'text-[11px]',
        default => 'text-[9.5px]',
    };

    $textColor = $theme === 'dark' ? 'text-white' : 'text-slate-900';
    $subtitleColor = $theme === 'dark' ? 'text-slate-400' : 'text-slate-500';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none']) }}>
    @if($hasCustomLogo)
        <div class="{{ $iconSize }} shrink-0 relative drop-shadow-md flex items-center justify-center overflow-hidden rounded-lg bg-white/10 p-0.5">
            <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-full w-full object-contain rounded-md">
        </div>
    @else
        <!-- Modern Geometric B+C Monogram Emblem Default -->
        <div class="{{ $iconSize }} shrink-0 relative drop-shadow-md">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                <defs>
                    <linearGradient id="bestcoEmblemGrad" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#2563eb"/>
                        <stop offset="100%" stop-color="#1e1b4b"/>
                    </linearGradient>
                    <linearGradient id="bestcoTopLoop" x1="12" y1="10" x2="34" y2="23" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#38bdf8"/>
                        <stop offset="100%" stop-color="#2563eb"/>
                    </linearGradient>
                    <linearGradient id="bestcoBottomLoop" x1="12" y1="23" x2="36" y2="38" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#60a5fa"/>
                        <stop offset="100%" stop-color="#3b82f6"/>
                    </linearGradient>
                </defs>
                <!-- Squircle Container -->
                <rect width="48" height="48" rx="14" fill="url(#bestcoEmblemGrad)"/>
                <rect x="0.75" y="0.75" width="46.5" height="46.5" rx="13.25" stroke="white" stroke-opacity="0.25" stroke-width="1.5"/>
                <!-- Stem Pillar -->
                <rect x="12" y="11" width="5.5" height="26" rx="2.75" fill="white"/>
                <!-- Top Loop of 'B' -->
                <path d="M15 11 H25 C29.5 11, 32.5 13.5, 32.5 17.5 C 32.5 21.5, 29.5 24, 25 24 H15" fill="none" stroke="url(#bestcoTopLoop)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Bottom Loop of 'B' / sweeping into 'C' -->
                <path d="M15 24 H27 C31.5 24, 35 27, 35 31 C 35 35, 31.5 37, 26 37 H15" fill="none" stroke="url(#bestcoBottomLoop)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Golden Excellence Sparkle -->
                <circle cx="30.5" cy="13.5" r="1.8" fill="#fbbf24"/>
            </svg>
        </div>
    @endif

    <!-- Wordmark Typography -->
    <div class="flex flex-col justify-center leading-none">
        <div class="flex items-baseline font-extrabold tracking-tight {{ $bestTextSize }}">
            @if(mb_strtoupper($appName) === 'BESTCO')
                <span class="{{ $textColor }}">BEST</span>
                <span class="bg-gradient-to-r from-sky-400 to-brand-500 bg-clip-text text-transparent">CO</span>
                <span class="text-[8px] font-black text-amber-400 ml-0.5 -translate-y-1 inline-block">●</span>
            @else
                <span class="{{ $textColor }} truncate max-w-[170px]">{{ $appName }}</span>
            @endif
        </div>
        @if($withSubtitle)
            <span class="block mt-1 font-bold uppercase tracking-[0.2em] {{ $subtitleSize }} {{ $subtitleColor }} truncate max-w-[180px]">
                {{ $appTagline }}
            </span>
        @endif
    </div>
</div>
