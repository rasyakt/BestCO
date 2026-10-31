<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>{{ $title ?? 'BESTCO — Teaching Factory MPLB' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Injeksi Tema Dinamis dari System Settings -->
    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full font-sans text-slate-800 antialiased">
    @php
        $user = auth()->user();
        $currentShift = $user
            ? \App\Models\PosShift::where('status', 'OPEN')->latest('opened_at')->first()
            : null;

        $initials = $user
            ? collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('')
            : 'BC';

        $pageLabel = match (true) {
            request()->routeIs('dashboard') => 'Dashboard',
            request()->routeIs('pos.terminal') => 'Kasir POS',
            request()->routeIs('transactions.*') => 'Riwayat Transaksi',
            request()->routeIs('inventory.index') => 'Persediaan',
            request()->routeIs('inventory.mutations') => 'Mutasi Internal',
            request()->routeIs('inventory.spoilage') => 'Spoilage',
            request()->routeIs('inventory.ledger') => 'Kartu Stok',
            request()->routeIs('shift.*') => 'Shift Kasir',
            request()->routeIs('piket.*') => 'Presensi Siswa',
            request()->routeIs('reports.*') => 'Laporan & BKU',
            request()->routeIs('theme.*') => 'Kustomisasi Tema',
            default => 'Workspace',
        };

        $navGroups = [
            [
                'label' => 'Operasional',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => 'dashboard'],
                    ['label' => 'Kasir POS', 'route' => 'pos.terminal', 'icon' => 'pos', 'active' => 'pos.terminal'],
                    ['label' => 'Riwayat Transaksi', 'route' => 'transactions.index', 'icon' => 'receipt', 'active' => 'transactions.*'],
                ],
            ],
            [
                'label' => 'Persediaan',
                'items' => [
                    ['label' => 'Persediaan', 'route' => 'inventory.index', 'icon' => 'package', 'active' => 'inventory.index'],
                    ['label' => 'Mutasi Internal', 'route' => 'inventory.mutations', 'icon' => 'swap', 'active' => 'inventory.mutations'],
                    ['label' => 'Spoilage', 'route' => 'inventory.spoilage', 'icon' => 'spoilage', 'active' => 'inventory.spoilage'],
                ],
            ],
            [
                'label' => 'SDM & Shift',
                'items' => [
                    ['label' => 'Shift Kasir', 'route' => 'shift.index', 'icon' => 'clock', 'active' => 'shift.*'],
                    ['label' => 'Presensi Siswa', 'route' => 'piket.index', 'icon' => 'users', 'active' => 'piket.*'],
                ],
            ],
            [
                'label' => 'Keuangan',
                'items' => [
                    ['label' => 'Laporan & BKU', 'route' => 'reports.index', 'icon' => 'chart', 'active' => 'reports.*', 'roles' => ['superadmin', 'admin', 'piket_guru']],
                ],
            ],
            [
                'label' => 'Sistem',
                'items' => [
                    ['label' => 'Kustomisasi Tema', 'route' => 'theme.index', 'icon' => 'palette', 'active' => 'theme.*', 'roles' => ['superadmin']],
                ],
            ],
        ];
    @endphp

    <div x-data="{ sidebarOpen: false, userMenu: false }" class="min-h-screen">
        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

        <!-- Sidebar -->
        <aside class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-[272px] flex-col shadow-2xl transition-transform duration-200 ease-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

            <!-- Brand -->
            <div class="flex h-[68px] shrink-0 items-center justify-between border-b border-white/[0.08] px-5">
                <a href="{{ $user ? route('dashboard') : route('login') }}" class="flex items-center gap-3">
                    <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[13px] font-extrabold tracking-wider text-brand-900 shadow-lg shadow-black/20">
                        BC
                        <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-brand-950 bg-emerald-400"></span>
                    </span>
                    <div class="leading-tight">
                        <span class="block text-[15px] font-extrabold tracking-[0.08em] text-white">BESTCO</span>
                        <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-[0.2em] text-white/40">Teaching Factory</span>
                    </div>
                </a>

                <button type="button" @click="sidebarOpen = false"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-white/50 transition hover:bg-white/10 hover:text-white lg:hidden">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            @if($user)
                <!-- Navigation -->
                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    @foreach($navGroups as $group)
                        @php
                            $visibleItems = collect($group['items'])->filter(fn ($item) => ! isset($item['roles']) || $user->hasAnyRole($item['roles']));
                        @endphp

                        @if($visibleItems->isNotEmpty())
                            <div>
                                <p class="px-3 pb-2 text-[9px] font-extrabold uppercase tracking-[0.18em] text-white/30">{{ $group['label'] }}</p>
                                <div class="space-y-1">
                                    @foreach($visibleItems as $item)
                                        @php $isActive = request()->routeIs($item['active']); @endphp
                                        <a href="{{ route($item['route']) }}"
                                            class="sidebar-link group {{ $isActive ? 'sidebar-link-active' : '' }}">
                                            <x-icon :name="$item['icon']"
                                                class="h-[18px] w-[18px] shrink-0 {{ $isActive ? 'text-brand-300' : 'text-white/40 transition group-hover:text-white/70' }}" />
                                            <span>{{ $item['label'] }}</span>
                                            @if($isActive)
                                                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-300"></span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>

                <!-- Shift status -->
                <div class="mx-3 mb-3 rounded-2xl border border-white/10 bg-white/[0.06] p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-extrabold uppercase tracking-[0.16em] text-white/35">Status Shift</span>
                        @if($currentShift)
                            <span class="inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wide text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wide text-amber-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Tutup
                            </span>
                        @endif
                    </div>

                    @if($currentShift)
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-xs font-bold text-white">{{ $currentShift->shift_code }}</p>
                                <p class="mt-0.5 text-[10px] text-white/40">Kas laci: Rp {{ number_format((float) $currentShift->system_expected_cash, 0, ',', '.') }}</p>
                            </div>
                            <a href="{{ route('shift.index') }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white/70 transition hover:bg-white/20 hover:text-white" title="Kelola shift">
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    @else
                        <p class="mt-2 text-[11px] leading-relaxed text-white/45">Shift kasir belum dibuka hari ini.</p>
                        <a href="{{ route('shift.index') }}" class="mt-2.5 inline-flex items-center gap-1.5 text-[11px] font-bold text-brand-300 transition hover:text-white">
                            Buka shift sekarang
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    @endif
                </div>
            @endif

            <!-- Footer -->
            <div class="shrink-0 border-t border-white/[0.08] px-5 py-4">
                <div class="flex items-center justify-between text-[9px] font-bold uppercase tracking-[0.16em] text-white/25">
                    <span>TeFa MPLB</span>
                    <span>v1.0</span>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-h-screen flex-col lg:pl-[272px]">
            <!-- Top bar -->
            <header class="app-topbar">
                <div class="mx-auto flex h-[68px] w-full max-w-[1600px] items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" @click="sidebarOpen = true"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden">
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>

                    <!-- Breadcrumb -->
                    <div class="flex min-w-0 items-center gap-2 text-xs">
                        <span class="hidden h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 sm:flex">
                            <x-icon name="home" class="h-4 w-4" />
                        </span>
                        <span class="hidden font-semibold text-slate-400 sm:inline">Workspace</span>
                        <x-icon name="chevron-right" class="hidden h-3.5 w-3.5 text-slate-300 sm:block" />
                        <span class="truncate font-bold text-slate-800">{{ $pageLabel }}</span>
                    </div>

                    <div class="ml-auto flex items-center gap-2 sm:gap-3">
                        @if($user)
                            <a href="{{ route('shift.index') }}"
                                class="hidden items-center gap-2 rounded-full border px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide transition sm:inline-flex
                                {{ $currentShift ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                <span class="relative flex h-2 w-2">
                                    @if($currentShift)
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                    @endif
                                    <span class="relative inline-flex h-2 w-2 rounded-full {{ $currentShift ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                </span>
                                {{ $currentShift ? 'Shift ' . $currentShift->shift_code : 'Shift Belum Buka' }}
                            </a>

                            <div class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-500 xl:flex">
                                <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                                {{ now()->locale('id')->isoFormat('ddd, D MMM Y') }}
                            </div>

                            <!-- User menu -->
                            <div class="relative" @click.outside="userMenu = false">
                                <button type="button" @click="userMenu = !userMenu"
                                    class="flex items-center gap-2 rounded-xl border border-transparent p-1.5 transition hover:border-slate-200 hover:bg-white">
                                    <span class="avatar h-9 w-9">{{ $initials }}</span>
                                    <span class="hidden text-left sm:block">
                                        <span class="block max-w-[140px] truncate text-[12px] font-bold leading-tight text-slate-800">{{ $user->name }}</span>
                                        <span class="mt-0.5 block text-[10px] font-semibold text-slate-400">{{ $user->role_type->label() }}</span>
                                    </span>
                                    <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-slate-400 sm:block" />
                                </button>

                                <div x-show="userMenu" x-cloak x-transition.origin.top.right
                                    class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                                    <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3.5">
                                        <p class="text-sm font-bold text-slate-900">{{ $user->name }}</p>
                                        <p class="mt-0.5 text-[11px] font-medium text-slate-500">{{ $user->role_type->label() }} &bull; {{ $user->username }}</p>
                                    </div>
                                    <div class="p-1.5">
                                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                            <x-icon name="dashboard" class="h-4 w-4 text-slate-400" />
                                            Dashboard
                                        </a>
                                        @if($user->hasRole('superadmin'))
                                            <a href="{{ route('theme.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="palette" class="h-4 w-4 text-slate-400" />
                                                Kustomisasi Tema
                                            </a>
                                        @endif
                                        <div class="my-1 h-px bg-slate-100"></div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                                <x-icon name="logout" class="h-4 w-4" />
                                                Keluar dari sistem
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                                Masuk Sistem
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="app-main mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-transition
                        class="alert alert-success mb-5 shadow-sm">
                        <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                        <span class="flex-1 leading-relaxed">{{ session('success') }}</span>
                        <button type="button" @click="show = false" class="rounded-lg p-0.5 text-emerald-600/70 transition hover:bg-emerald-100 hover:text-emerald-800">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                @endif

                @if(session('error') || $errors->has('error'))
                    <div x-data="{ show: true }" x-show="show" x-transition
                        class="alert alert-danger mb-5 shadow-sm">
                        <x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
                        <span class="flex-1 leading-relaxed">{{ session('error') ?? $errors->first('error') }}</span>
                        <button type="button" @click="show = false" class="rounded-lg p-0.5 text-rose-600/70 transition hover:bg-rose-100 hover:text-rose-800">
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                @endif

                <div class="page-enter">
                    @yield('content')
                    {{ $slot ?? '' }}
                </div>
            </main>

            <footer class="no-print border-t border-slate-200/60 px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-[1600px] flex-col items-center justify-between gap-1 text-[10px] font-semibold text-slate-400 sm:flex-row">
                    <span>&copy; {{ now()->year }} BESTCO &mdash; Teaching Factory MPLB</span>
                    <span class="font-mono">Sistem Informasi Operasional &amp; Akuntansi</span>
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts
</body>
</html>
