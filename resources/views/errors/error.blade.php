@php
    $appIdentity = \App\Services\Setting\AppIdentityService::get();
    $title = $title ?? 'Terjadi Kesalahan';
    $message = $message ?? 'Terjadi kesalahan pada sistem.';
    $illustration = $illustration ?? 'error-generic';
    $primaryColor = $primaryColor ?? 'brand-600';
    $accentGradient = $accentGradient ?? 'from-brand-500 to-brand-700';
    $actionLabel = $actionLabel ?? null;
    $actionUrl = $actionUrl ?? null;
    $statusCode = $statusCode ?? 500;
    $primary = $primaryColor;
    $gradient = $accentGradient;
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111827">
    <title>{{ $title }} — {{ $appIdentity['app_name'] }}</title>

    @include('partials.theme-script')

    <link rel="icon" type="image/png" href="{{ $appIdentity['has_custom_logo'] ? $appIdentity['app_logo_url'] : asset('img/best.png') }}">
    <link rel="apple-touch-icon" href="{{ $appIdentity['has_custom_logo'] ? $appIdentity['app_logo_url'] : asset('img/best.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .error-container {
            animation: fadeInUp 0.5s ease-out;
        }
        
        .error-code {
            animation: scaleIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.1s both;
        }
        
        .error-illustration {
            animation: float 3s ease-in-out infinite;
        }
        
        .dot-pulse {
            animation: pulse 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 p-4 font-sans antialiased">
    <!-- Animated background elements -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-40 -top-40 h-80 w-80 rounded-full bg-gradient-to-br from-blue-400/30 to-purple-400/30 blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 h-80 w-80 rounded-full bg-gradient-to-br from-purple-400/30 to-pink-400/30 blur-3xl"></div>
    </div>

    <div class="error-container relative z-10 w-full max-w-2xl">
        <!-- Error Card -->
        <div class="overflow-hidden rounded-3xl border border-white/60 bg-white/80 shadow-2xl backdrop-blur-xl">
            <!-- Illustration Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 px-8 py-10">
                <!-- Decorative circles -->
                <div class="absolute left-10 top-10 h-20 w-20 rounded-full bg-purple-500/20 blur-2xl"></div>
                <div class="absolute bottom-10 right-10 h-24 w-24 rounded-full bg-blue-500/20 blur-2xl"></div>
                
                @if (View::exists('errors.illustrations.' . $illustration))
                    <div class="error-illustration relative z-10 mx-auto max-w-xs">
                        @include('errors.illustrations.' . $illustration)
                    </div>
                @else
                    <div class="error-illustration relative z-10 mx-auto max-w-xs">
                        @include('errors.illustrations.error-generic')
                    </div>
                @endif
            </div>

            <!-- Content Section -->
            <div class="px-6 py-8 text-center sm:px-10">
                <!-- Error Code - Large & Bold -->
                <div class="error-code mb-3">
                    <div class="relative inline-block">
                        <div class="absolute inset-0 translate-x-2 translate-y-2 rounded-2xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 blur-xl"></div>
                        <div class="gradient-text relative font-mono text-7xl font-black tracking-tighter sm:text-8xl">
                            {{ $statusCode }}
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="mb-2 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 bg-clip-text text-2xl font-extrabold tracking-tight text-transparent sm:text-3xl">
                    {{ $title }}
                </h1>

                <!-- Message -->
                <p class="mx-auto mb-6 max-w-md text-sm leading-relaxed text-slate-600">
                    {{ $message }}
                </p>

                <!-- Status Badge -->
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-purple-200 bg-gradient-to-r from-purple-50 to-blue-50 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-purple-700">
                    <span class="dot-pulse h-2 w-2 rounded-full bg-purple-500"></span>
                    HTTP {{ $statusCode }}
                </div>

                <!-- Actions -->
                <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if($actionUrl && $actionLabel)
                        <a href="{{ $actionUrl }}" class="group relative inline-flex w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purple-500/30 transition-all hover:scale-105 hover:shadow-xl hover:shadow-purple-500/40 sm:w-auto">
                            <span class="absolute inset-0 -z-10 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 blur-xl transition-opacity group-hover:opacity-100"></span>
                            {{ $actionLabel }}
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @else
                        <a href="{{ url('/') }}" class="group relative inline-flex w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purple-500/30 transition-all hover:scale-105 hover:shadow-xl hover:shadow-purple-500/40 sm:w-auto">
                            <span class="absolute inset-0 -z-10 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 blur-xl transition-opacity group-hover:opacity-100"></span>
                            Kembali ke Beranda
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endif

                    @if($actionUrl && $actionLabel)
                        <a href="{{ url('/') }}" class="group inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow-md sm:w-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Beranda
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 text-center">
            <p class="text-xs font-medium text-slate-600/80">
                &copy; {{ now()->year }} <span class="font-bold text-slate-700">{{ $appIdentity['app_name'] }}</span>
            </p>
            <p class="mt-0.5 text-xs text-slate-500">
                Sistem kasir dan persediaan TeFa MPLB
            </p>
        </div>
    </div>
</body>
</html>
