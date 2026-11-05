<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111827">
    @php
        $appIdentity = \App\Services\Setting\AppIdentityService::get();
    @endphp
    <title>{{ $title ?? ($appIdentity['app_name'] . ' — ' . $appIdentity['app_tagline']) }}</title>

    @include('partials.theme-script')

    <link rel="icon" type="image/png" href="{{ $appIdentity['has_custom_logo'] ? $appIdentity['app_logo_url'] : asset('img/best.png') }}">
    <link rel="apple-touch-icon" href="{{ $appIdentity['has_custom_logo'] ? $appIdentity['app_logo_url'] : asset('img/best.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Tema dinamis dimuat setelah CSS bawaan agar warnanya tidak tertimpa. -->
    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>
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

        // Halaman kasir memakai tata letak khusus: tinggi layar penuh,
        // hanya daftar produk & isi keranjang yang bergulir sendiri.
        $isPosLayout = request()->routeIs('pos.terminal');

        $pageLabel = match (true) {
            request()->routeIs('dashboard') => 'Dashboard',
            request()->routeIs('pos.terminal') => 'Kasir',
            request()->routeIs('transactions.*') => 'Riwayat Transaksi',
            request()->routeIs('promotions.*') => 'Diskon & Promo',
            request()->routeIs('inventory.inbound*') => 'Barang Masuk',
            request()->routeIs('inventory.index') => 'Persediaan',
            request()->routeIs('inventory.mutations') => 'Pindah Stok',
            request()->routeIs('inventory.spoilage') => 'Barang Rusak',
            request()->routeIs('inventory.ledger') => 'Kartu Stok',
            request()->routeIs('shift.*') => 'Shift Kasir',
            request()->routeIs('piket.*') => 'Presensi Piket',
            request()->routeIs('reports.*') => 'Laporan',
            request()->routeIs('settings.store*') => 'Informasi Toko',
            request()->routeIs('settings.qris*') => 'Pengaturan QRIS',
            request()->routeIs('settings.features*') => 'Pengaturan Fitur',
            request()->routeIs('dev.*') => 'Developer Console',
            request()->routeIs('theme.*') => 'Tema',
            request()->routeIs('users.*') => 'Kelola Pengguna',
            request()->routeIs('profile.*') => 'Profil Akun',
            default => $appIdentity['app_name'],
        };

        $featureManager = app(\App\Services\Feature\FeatureManager::class);

        $navGroups = [
            [
                'label' => 'Operasional',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => 'dashboard', 'feature' => 'dashboard'],
                    ['label' => 'Kasir', 'route' => 'pos.terminal', 'icon' => 'pos', 'active' => 'pos.terminal', 'feature' => 'pos'],
                    ['label' => 'Riwayat Transaksi', 'route' => 'transactions.index', 'icon' => 'receipt', 'active' => 'transactions.*', 'feature' => 'transactions'],
                    ['label' => 'Diskon & Promo', 'route' => 'promotions.index', 'icon' => 'tag', 'active' => 'promotions.*', 'feature' => 'promotions'],
                ],
            ],
            [
                'label' => 'Persediaan',
                'items' => [
                    ['label' => 'Persediaan', 'route' => 'inventory.index', 'icon' => 'package', 'active' => 'inventory.index', 'feature' => 'inventory'],
                    ['label' => 'Barang Masuk', 'route' => 'inventory.inbound', 'icon' => 'download', 'active' => 'inventory.inbound*', 'feature' => 'inventory_inbound'],
                    ['label' => 'Pindah Stok', 'route' => 'inventory.mutations', 'icon' => 'swap', 'active' => 'inventory.mutations', 'feature' => 'inventory_mutations'],
                    ['label' => 'Barang Rusak', 'route' => 'inventory.spoilage', 'icon' => 'spoilage', 'active' => 'inventory.spoilage', 'feature' => 'inventory_spoilage'],
                ],
            ],
            [
                'label' => 'Tim',
                'items' => [
                    ['label' => 'Shift Kasir', 'route' => 'shift.index', 'icon' => 'clock', 'active' => 'shift.*', 'feature' => 'shift'],
                    ['label' => 'Presensi Piket', 'route' => 'piket.index', 'icon' => 'users', 'active' => 'piket.*', 'feature' => 'piket'],
                ],
            ],
            [
                'label' => 'Keuangan',
                'items' => [
                    ['label' => 'Laporan', 'route' => 'reports.index', 'icon' => 'chart', 'active' => 'reports.*', 'feature' => 'reports'],
                ],
            ],
            [
                'label' => 'Sistem',
                'items' => [
                    ['label' => 'Kelola Pengguna', 'route' => 'users.index', 'icon' => 'users', 'active' => 'users.*', 'feature' => 'users'],
                    ['label' => 'Informasi Toko', 'route' => 'settings.store', 'icon' => 'store', 'active' => 'settings.store*', 'feature' => 'settings_store'],
                    ['label' => 'Pengaturan QRIS', 'route' => 'settings.qris', 'icon' => 'qr', 'active' => 'settings.qris*', 'feature' => 'settings_qris'],
                    ['label' => 'Tema', 'route' => 'theme.index', 'icon' => 'palette', 'active' => 'theme.*', 'feature' => 'theme'],
                    ['label' => 'Pengaturan Fitur', 'route' => 'settings.features', 'icon' => 'shield', 'active' => 'settings.features*', 'roles' => ['superadmin']],
                    ['label' => 'Developer', 'route' => 'dev.index', 'icon' => 'code', 'active' => 'dev.*', 'roles' => ['superadmin']],
                ],
            ],
        ];
    @endphp

    <div x-data="{
        sidebarOpen: false,
        userMenu: false,
        isDark: window.BestcoTheme?.isDark ?? document.documentElement.classList.contains('dark'),
        posFocus: localStorage.getItem('bestco_pos_focus') === 'true',
        isFullscreen: false,
        toggleTheme() {
            window.BestcoTheme?.toggle();
            this.isDark = window.BestcoTheme?.isDark ?? !this.isDark;
        },
        togglePosFocus() {
            this.posFocus = !this.posFocus;
            localStorage.setItem('bestco_pos_focus', this.posFocus);
        },
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    this.isFullscreen = true;
                }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => {
                    this.isFullscreen = false;
                }).catch(() => {});
            }
        },
        init() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });

            window.addEventListener('bestco-theme-changed', (event) => {
                this.isDark = event.detail?.isDark ?? window.BestcoTheme?.isDark ?? false;
            });
        }
    }" class="min-h-screen {{ $isPosLayout ? 'lg:h-screen lg:min-h-0 lg:overflow-hidden' : '' }}">
        <!-- Mobile & Focus drawer overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50"
            :class="(posFocus && {{ $isPosLayout ? 'true' : 'false' }}) ? '' : 'lg:hidden'"></div>

        <!-- Sidebar -->
        <aside class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-64 flex-col transition-transform duration-200 ease-out lg:z-30"
            :class="(posFocus && {{ $isPosLayout ? 'true' : 'false' }})
                ? (sidebarOpen ? 'translate-x-0 shadow-2xl z-50' : '-translate-x-full')
                : (sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0')">

            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 px-4">
                <a href="{{ $user ? ($user->hasRole('piket_siswa') ? route('pos.terminal') : route('dashboard')) : route('login') }}" class="flex items-center">
                    <x-bestco-logo theme="light" size="sm" />
                </a>

                <button type="button" @click="sidebarOpen = false"
                    class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            @if($user)
                <!-- Navigation -->
                <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
                    @foreach($navGroups as $group)
                        @php
                            $visibleItems = collect($group['items'])->filter(function ($item) use ($featureManager, $user) {
                                if (isset($item['feature'])) {
                                    return $featureManager->canAccess($item['feature'], $user);
                                }
                                if (isset($item['roles'])) {
                                    return $user->hasAnyRole($item['roles']);
                                }
                                return true;
                            });
                        @endphp

                        @if($visibleItems->isNotEmpty())
                            <div>
                                <p class="px-3 pb-1.5 text-[11px] font-medium text-slate-500">{{ $group['label'] }}</p>
                                <div class="space-y-0.5">
                                    @foreach($visibleItems as $item)
                                        @php $isActive = request()->routeIs($item['active']); @endphp
                                        <a href="{{ route($item['route']) }}"
                                            class="sidebar-link {{ $isActive ? 'sidebar-link-active' : '' }}">
                                            <x-icon :name="$item['icon']"
                                                class="h-[17px] w-[17px] shrink-0 {{ $isActive ? 'text-brand-600' : 'text-slate-400' }}" />
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>

                @unless($isPosLayout)
                <!-- Shift status -->
                <div class="mx-3 mb-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-medium text-slate-500">Shift kasir</span>
                        <span class="text-[11px] font-semibold {{ $currentShift ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $currentShift ? 'Aktif' : 'Tutup' }}
                        </span>
                    </div>

                    @if($currentShift)
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-xs font-medium text-slate-900">{{ $currentShift->shift_code }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">Kas Rp {{ number_format((float) $currentShift->system_expected_cash, 0, ',', '.') }}</p>
                            </div>
                            <a href="{{ route('shift.index') }}" class="flex h-7 w-7 shrink-0 items-center justify-center rounded text-slate-400 transition hover:bg-white hover:text-slate-900" title="Lihat shift">
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    @else
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Belum dibuka hari ini.</p>
                        <a href="{{ route('shift.index') }}" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-brand-700 transition hover:text-brand-800">
                            Buka shift
                            <x-icon name="arrow-right" class="h-3 w-3" />
                        </a>
                    @endif
                </div>
                @endunless
            @endif
        </aside>

        <!-- Main column -->
        <div class="flex min-h-screen flex-col transition-all duration-200 ease-out {{ $isPosLayout ? 'lg:h-screen lg:min-h-0' : '' }}"
            :class="(posFocus && {{ $isPosLayout ? 'true' : 'false' }}) ? 'lg:pl-0' : 'lg:pl-64'">
            <!-- Top bar -->
            <header class="app-topbar">
                <div class="mx-auto flex h-16 w-full max-w-[1700px] items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" @click="sidebarOpen = true"
                        class="h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50"
                        :class="(posFocus && {{ $isPosLayout ? 'true' : 'false' }}) ? 'flex' : 'flex lg:hidden'"
                        title="Buka menu navigasi">
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>

                    <!-- Breadcrumb -->
                    <div class="flex min-w-0 items-center gap-2 text-xs">
                        <span class="hidden font-medium text-slate-400 sm:inline">BESTCO</span>
                        <x-icon name="chevron-right" class="hidden h-3.5 w-3.5 text-slate-300 sm:block" />
                        <span class="truncate font-semibold text-slate-800">{{ $pageLabel }}</span>
                    </div>

                    <div class="ml-auto flex items-center gap-2 sm:gap-3">
                        <!-- Toggle tema terang / gelap -->
                        <button type="button" @click="toggleTheme()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            title="Ganti tema" aria-label="Ganti tema"
                            :title="isDark ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                            :aria-label="isDark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap'"
                            :aria-pressed="isDark ? 'true' : 'false'">
                            <span class="theme-icon-moon">
                                <x-icon name="moon" class="h-4 w-4" />
                            </span>
                            <span class="theme-icon-sun">
                                <x-icon name="sun" class="h-4 w-4" />
                            </span>
                        </button>

                        @if($isPosLayout)
                            <!-- Tombol Mode Bersih / Fokus Kasir (1 Tombol Toggle) -->
                            <button type="button" @click="togglePosFocus()"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition"
                                :class="posFocus ? 'border-brand-600 bg-brand-50 text-brand-700 shadow-sm' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'"
                                :title="posFocus ? 'Kembali ke tampilan standar' : 'Aktifkan mode fokus / tampilan bersih kasir'">
                                <x-icon name="focus" class="h-4 w-4" />
                                <span class="hidden sm:inline" x-text="posFocus ? 'Mode Standar' : 'Mode Bersih'"></span>
                            </button>

                            <!-- Tombol Fullscreen Browser -->
                            <button type="button" @click="toggleFullscreen()"
                                class="hidden sm:inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50"
                                :title="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh (Fullscreen)'">
                                <span x-show="!isFullscreen">
                                    <x-icon name="fullscreen" class="h-4 w-4" />
                                </span>
                                <span x-show="isFullscreen" x-cloak>
                                    <x-icon name="minimize" class="h-4 w-4" />
                                </span>
                            </button>

                            <div class="hidden h-4 w-px bg-slate-200 sm:block"></div>
                        @endif
                        @if($user)
                            <a href="{{ route('shift.index') }}"
                                class="hidden items-center gap-1.5 text-[11px] font-medium transition sm:inline-flex {{ $currentShift ? 'text-emerald-700 hover:text-emerald-800' : 'text-amber-700 hover:text-amber-800' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $currentShift ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ $currentShift ? 'Shift ' . $currentShift->shift_code : 'Shift belum dibuka' }}
                            </a>

                            <span class="hidden text-[11px] text-slate-400 xl:inline">
                                {{ now()->locale('id')->isoFormat('ddd, D MMM Y') }}
                            </span>

                            <!-- User menu -->
                            <div class="relative" @click.outside="userMenu = false">
                                <button type="button" @click="userMenu = !userMenu"
                                    class="flex items-center gap-2 rounded-md border border-transparent p-1.5 transition hover:border-slate-200 hover:bg-slate-50">
                                    <span class="avatar h-8 w-8">{{ $initials }}</span>
                                    <span class="hidden text-left sm:block">
                                        <span class="block max-w-[140px] truncate text-[12px] font-semibold leading-tight text-slate-800">{{ $user->name }}</span>
                                        <span class="mt-0.5 block text-[10px] text-slate-400">{{ $user->role_type->label() }}</span>
                                    </span>
                                    <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-slate-400 sm:block" />
                                </button>

                                <div x-show="userMenu" x-cloak x-transition.origin.top.right
                                    class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                                        <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $user->role_type->label() }} &bull; {{ $user->username }}</p>
                                    </div>
                                    <div class="p-1">
                                        @if($user->hasRole('piket_siswa'))
                                            <a href="{{ route('pos.terminal') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="pos" class="h-4 w-4 text-slate-400" />
                                                Kasir
                                            </a>
                                        @else
                                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="dashboard" class="h-4 w-4 text-slate-400" />
                                                Dashboard
                                            </a>
                                        @endif
                                        @if($user->canManageUsers())
                                            <a href="{{ route('users.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="users" class="h-4 w-4 text-slate-400" />
                                                Kelola Pengguna
                                            </a>
                                        @endif
                                        @if($user->hasRole('superadmin'))
                                            <a href="{{ route('settings.features') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="shield" class="h-4 w-4 text-slate-400" />
                                                Pengaturan Fitur
                                            </a>
                                            <a href="{{ route('dev.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-brand-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-brand-400 dark:hover:bg-slate-800">
                                                <x-icon name="code" class="h-4 w-4 text-brand-500" />
                                                Developer Console
                                            </a>
                                            <a href="{{ route('theme.index') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                                <x-icon name="palette" class="h-4 w-4 text-slate-400" />
                                                Tema
                                            </a>
                                        @endif
                                        <div class="my-1 h-px bg-slate-100"></div>
                                        <a href="{{ route('profile.password') }}" class="flex items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                            <x-icon name="shield" class="h-4 w-4 text-slate-400" />
                                            Ganti Password
                                        </a>
                                        <div class="my-1 h-px bg-slate-100"></div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-2.5 rounded px-3 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                <x-icon name="logout" class="h-4 w-4" />
                                                Keluar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                                Masuk
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="app-main mx-auto w-full max-w-[1700px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8
                {{ $isPosLayout ? 'lg:flex lg:min-h-0 lg:flex-col lg:overflow-hidden lg:py-4' : '' }}"
                :class="(posFocus && {{ $isPosLayout ? 'true' : 'false' }}) ? 'lg:px-4 lg:py-3.5' : ''">
                <div class="page-enter {{ $isPosLayout ? 'lg:flex lg:min-h-0 lg:flex-1 lg:flex-col' : '' }}">
                    @yield('content')
                    {{ $slot ?? '' }}
                </div>
            </main>

            @unless($isPosLayout)
            <footer class="no-print border-t border-slate-200 px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-[1600px] flex-col items-center justify-between gap-1 text-[11px] text-slate-400 sm:flex-row">
                    <span>&copy; {{ now()->year }} {{ $appIdentity['app_name'] }} — {{ $appIdentity['app_tagline'] }}</span>
                    <span>{{ $appIdentity['footer_text'] }}</span>
                </div>
            </footer>
            @endunless
        </div>
    </div>

    @livewireScripts

    <!-- Global Custom Confirmation & Alert Dialog -->
    <x-global-dialog />

    <!-- Global Floating Toast Notification System (Non-intrusive Popup) -->
    <x-toast-container />
</body>
</html>
