<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BESTCO — Teaching Factory MPLB' }}</title>

    <!-- Injeksi Tema Dinamis dari System Settings -->
    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-800 antialiased font-sans selection:bg-brand-500 selection:text-white">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14 items-center">
                    <!-- Brand & Context -->
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-brand-700 text-white font-bold text-sm tracking-wider">BC</span>
                            <div>
                                <span class="font-bold text-slate-900 text-base leading-tight">BESTCO</span>
                                <span class="hidden sm:inline-block text-xs text-slate-500 ml-1">TeFa MPLB</span>
                            </div>
                        </a>

                        @auth
                            @php
                                $currentShift = \App\Models\PosShift::where('status', 'OPEN')->latest('opened_at')->first();
                            @endphp
                            @if($currentShift)
                                <a href="{{ route('shift.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Shift: {{ $currentShift->shift_code }}
                                </a>
                            @else
                                <a href="{{ route('shift.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition">
                                    Shift Belum Buka
                                </a>
                            @endif
                        @endauth
                    </div>

                    <!-- Navigation Links & User Menu -->
                    <div class="flex items-center space-x-4">
                        @auth
                            <nav class="flex items-center space-x-1 overflow-x-auto text-xs font-semibold">
                                <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Dashboard
                                </a>
                                <a href="{{ route('pos.terminal') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('pos.terminal') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Kasir POS
                                </a>
                                <a href="{{ route('transactions.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('transactions.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Transaksi
                                </a>
                                <a href="{{ route('inventory.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('inventory.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Persediaan
                                </a>
                                <a href="{{ route('shift.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('shift.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Shift Piket
                                </a>
                                <a href="{{ route('piket.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('piket.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Presensi Siswa
                                </a>
                                @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                                    <a href="{{ route('reports.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('reports.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                        Laporan & BKU
                                    </a>
                                @endif
                                @if(auth()->user()->hasRole('superadmin'))
                                    <a href="{{ route('theme.index') }}" class="px-2.5 py-1.5 rounded {{ request()->routeIs('theme.*') ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                        Tema
                                    </a>
                                @endif
                            </nav>

                            <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

                            <div class="flex items-center space-x-3 text-xs">
                                <div class="text-right hidden md:block">
                                    <div class="font-medium text-slate-800">{{ auth()->user()->name }}</div>
                                    <div class="text-slate-500 text-[11px] uppercase tracking-wider">{{ auth()->user()->role_type->value }}</div>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded border border-rose-200 font-medium">
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                                Masuk Sistem &rarr;
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5">
            @if(session('success'))
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded text-emerald-800 text-xs flex items-center justify-between shadow-xs">
                    <span class="font-medium">{{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
                </div>
            @endif

            @if(session('error') || $errors->has('error'))
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded text-rose-800 text-xs flex items-center justify-between shadow-xs">
                    <span class="font-medium">{{ session('error') ?? $errors->first('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
                </div>
            @endif

            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
