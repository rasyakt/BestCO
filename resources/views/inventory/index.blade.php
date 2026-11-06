@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="inventoryManager()" @keydown.escape.window="closeMenu()" @scroll.window="closeMenu()" @resize.window="closeMenu()">
    <x-page-header
        title="Persediaan"
        subtitle="Stok barang etalase dan bahan baku jasa.">
        <x-slot:actions>
            <a href="{{ route('inventory.inbound') }}" class="btn btn-secondary">
                <x-icon name="download" class="h-4 w-4" />
                Barang Masuk
            </a>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah Stok
            </a>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Barang Rusak
            </a>
            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                <a href="{{ route('inventory.opname') }}" class="btn btn-secondary">
                    <x-icon name="scale" class="h-4 w-4" />
                    Stok Opname
                </a>
            @endif
            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                <div class="relative inline-block text-left" x-data="{ addMenuOpen: false }" @click.outside="addMenuOpen = false">
                    <button type="button" @click="addMenuOpen = !addMenuOpen" class="btn btn-primary inline-flex items-center gap-2">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span>Tambah Baru</span>
                        <span class="inline-flex transition-transform duration-150" :class="addMenuOpen ? 'rotate-180' : ''">
                            <x-icon name="chevron-down" class="h-3.5 w-3.5 opacity-70" />
                        </span>
                    </button>

                    <div x-show="addMenuOpen" x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-1.5 w-64 rounded-xl border border-slate-200/90 bg-white p-1.5 text-left shadow-xl ring-1 ring-slate-900/5">

                        <div class="px-2.5 pt-1.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Pendaftaran Baru
                        </div>

                        <button type="button" @click="addMenuOpen = false; openAddProductModal()"
                            class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-slate-50 text-slate-700 transition group-hover:border-slate-300 group-hover:bg-white group-hover:text-slate-900">
                                <x-icon name="package" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 transition group-hover:text-slate-900">Tambah Produk</p>
                                <p class="text-[11px] text-slate-500">Barang etalase & bahan baku</p>
                            </div>
                        </button>

                        <button type="button" @click="addMenuOpen = false; openAddServiceModal()"
                            class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-slate-50 text-slate-700 transition group-hover:border-slate-300 group-hover:bg-white group-hover:text-slate-900">
                                <x-icon name="printer" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 transition group-hover:text-slate-900">Tambah Layanan</p>
                                <p class="text-[11px] text-slate-500">Fotocopy, print, jilid, laminating</p>
                            </div>
                        </button>

                        <div class="my-1.5 border-t border-slate-100"></div>

                        <div class="px-2.5 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Master Data
                        </div>

                        <button type="button" @click="addMenuOpen = false; openManageCategoriesModal()"
                            class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-slate-50 text-slate-700 transition group-hover:border-slate-300 group-hover:bg-white group-hover:text-slate-900">
                                <x-icon name="layers" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 transition group-hover:text-slate-900">Kelola Kategori</p>
                                <p class="text-[11px] text-slate-500">Struktur grup & kategori master</p>
                            </div>
                        </button>

                        <button type="button" @click="addMenuOpen = false; openManageUnitsModal()"
                            class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-slate-50 text-slate-700 transition group-hover:border-slate-300 group-hover:bg-white group-hover:text-slate-900">
                                <x-icon name="scale" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 transition group-hover:text-slate-900">Kelola Satuan</p>
                                <p class="text-[11px] text-slate-500">Standar satuan & nilai desimal</p>
                            </div>
                        </button>
                    </div>
                </div>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50/90 p-4 text-rose-900 shadow-xs">
            <x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <div class="flex-1 text-xs">
                <p class="font-semibold">Terjadi kesalahan validasi:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Filter & Pencarian -->
    <div class="card flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[11px] font-medium text-slate-500">Katalog</span>
            <div class="segmented">
                <a href="{{ route('inventory.index', ['type' => 'ALL', 'q' => $search]) }}"
                    class="{{ $type === 'ALL' ? 'is-active' : '' }}">Semua barang</a>
                <a href="{{ route('inventory.index', ['type' => 'RETAIL', 'q' => $search]) }}"
                    class="{{ $type === 'RETAIL' ? 'is-active' : '' }}">Barang etalase</a>
                <a href="{{ route('inventory.index', ['type' => 'RAW_MATERIAL', 'q' => $search]) }}"
                    class="{{ $type === 'RAW_MATERIAL' ? 'is-active' : '' }}">Bahan baku</a>
                <a href="{{ route('inventory.index', ['type' => 'SERVICE', 'q' => $search]) }}"
                    class="{{ $type === 'SERVICE' ? 'is-active' : '' }}">
                    <span class="inline-flex items-center gap-1">
                        <x-icon name="sparkles" class="h-3 w-3" />
                        Layanan jasa
                    </span>
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="flex w-full items-center gap-2 lg:w-auto">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="search-field w-full lg:w-72">
                <x-icon name="search" />
                <input type="text" name="q" value="{{ $search }}"
                    placeholder="{{ $type === 'SERVICE' ? 'Cari nama atau kode layanan...' : 'Cari nama, SKU, atau barcode...' }}"
                    class="input">
            </div>
            <button type="submit" class="btn btn-dark btn-sm">Cari</button>
        </form>
    </div>

    <!-- TABEL 1: DAFTAR PRODUK (BARANG ETALASE / BAHAN BAKU) -->
    @if($type !== 'SERVICE')
        <div class="table-shell">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Daftar stok</h2>
                    <p class="card-subtitle">{{ $productPage->total() }} barang aktif</p>
                </div>
                <div class="flex items-center gap-4 text-[11px] text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-sky-500"></span> Barang etalase</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-violet-500"></span> Bahan baku</span>
                </div>
            </div>

            <!-- Tampilan Desktop -->
            <div class="w-full hidden md:block">
                <table class="data-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left whitespace-nowrap px-2.5 py-2.5">Barang / Bahan</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Stok Fisik</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">HPP (Modal)</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Harga Jual</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Margin Laba</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-[44px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productPage as $p)
                            @php
                                $isLow = (float) $p->current_stock <= (float) ($p->min_stock_alert ?? 0);
                                $isRetail = $p->inventory_type->value === 'RETAIL';
                                $cost = (float) $p->average_cost;
                                $price = (float) $p->selling_price;
                                $margin = $isRetail && $cost > 0 && $price > $cost ? round((($price - $cost) / $cost) * 100, 1) : null;
                            @endphp
                            <tr :class="activeMenuId === 'p-{{ $p->id }}' ? 'relative z-30 bg-slate-50/80' : ''">
                                <td class="whitespace-nowrap px-2.5 py-2.5">
                                    <div class="flex items-center gap-1.5 whitespace-nowrap" title="{{ $p->name }} ({{ $p->sku }})">
                                        <span class="badge {{ $isRetail ? 'badge-info' : 'badge-warning' }} text-[9px] px-1.5 py-0.5 whitespace-nowrap">
                                            {{ $isRetail ? 'Etalase' : 'Bahan' }}
                                        </span>
                                        <span class="font-semibold text-slate-800 text-xs">{{ $p->name }}</span>
                                        <span class="font-mono text-[10px] text-slate-400">({{ $p->sku }})</span>
                                    </div>
                                </td>
                                <td class="text-right whitespace-nowrap px-2.5 py-2.5">
                                    <span class="font-semibold num-tabular {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                                    </span>
                                    <span class="ml-0.5 text-[10px] text-slate-400">{{ $p->unit?->code }}</span>
                                    @if($isLow)
                                        <span class="badge badge-danger text-[9px] py-0 px-1 ml-1 whitespace-nowrap">Menipis</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap text-slate-600 font-mono text-xs num-tabular px-2.5 py-2.5">
                                    Rp {{ number_format($cost, 0, ',', '.') }}
                                </td>
                                <td class="text-right whitespace-nowrap font-semibold text-slate-900 font-mono text-xs num-tabular px-2.5 py-2.5">
                                    {{ $price > 0 ? 'Rp ' . number_format($price, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right whitespace-nowrap font-mono text-xs num-tabular px-2.5 py-2.5">
                                    @if($margin !== null)
                                        <span class="font-semibold text-emerald-600">+{{ $margin }}%</span>
                                        <span class="text-[10px] text-slate-400 font-normal ml-0.5">(Rp {{ number_format($price - $cost, 0, ',', '.') }})</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap px-2.5 py-2.5">
                                    <div class="relative inline-block text-left" @click.outside="if (activeMenuId === 'p-{{ $p->id }}') closeMenu()">
                                        <button type="button"
                                            @click="toggleMenu('p-{{ $p->id }}', $event)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                            title="Menu Aksi">
                                            <x-icon name="more-vertical" class="h-3.5 w-3.5" />
                                        </button>

                                        <div x-show="activeMenuId === 'p-{{ $p->id }}'"
                                            x-cloak
                                            x-transition.opacity.duration.100ms
                                            :style="{
                                                position: 'fixed',
                                                top: menuPos.top,
                                                bottom: menuPos.bottom,
                                                right: menuPos.right,
                                                zIndex: 9999
                                            }"
                                            class="w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-800">

                                            <button type="button"
                                                @click="closeMenu(); openRestockModal(@js($p->id))"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">
                                                <x-icon name="plus" class="h-3.5 w-3.5 text-emerald-600" />
                                                Tambah Stok
                                            </button>

                                            <a href="{{ route('inventory.ledger', $p->id) }}"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">
                                                <x-icon name="list" class="h-3.5 w-3.5 text-brand-600" />
                                                Kartu Stok
                                            </a>

                                            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                                                <div class="my-1 border-t border-slate-100"></div>
                                                <button type="button"
                                                    @click="closeMenu(); openEditProductModal(@js($p))"
                                                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">
                                                    <x-icon name="edit" class="h-3.5 w-3.5 text-slate-500" />
                                                    Edit Produk
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state py-8">
                                        <span class="empty-state-icon"><x-icon name="package" class="h-5 w-5" /></span>
                                        <p class="text-sm font-semibold text-slate-700">Barang tidak ditemukan</p>
                                        <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau klik "Tambah Produk" di atas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Tampilan Mobile -->
            <div class="divide-y divide-slate-100 md:hidden">
                @forelse($productPage as $p)
                    @php
                        $isLow = (float) $p->current_stock <= (float) ($p->min_stock_alert ?? 0);
                        $isRetail = $p->inventory_type->value === 'RETAIL';
                    @endphp
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold leading-snug text-slate-800">{{ $p->name }}</p>
                                <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $p->sku }} &bull; {{ $isRetail ? 'Etalase' : 'Bahan baku' }}</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($isLow)
                                    <span class="badge badge-danger text-[10px]">Stok Menipis</span>
                                @endif
                                <div class="relative inline-block text-left" @click.outside="if (activeMenuId === 'pm-{{ $p->id }}') closeMenu()">
                                    <button type="button"
                                        @click="toggleMenu('pm-{{ $p->id }}', $event)"
                                        class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition shadow-2xs"
                                        title="Menu Aksi">
                                        <x-icon name="more-vertical" class="h-4 w-4" />
                                    </button>
                                    <div x-show="activeMenuId === 'pm-{{ $p->id }}'"
                                        x-cloak
                                        x-transition.opacity.duration.100ms
                                        :style="{
                                            position: 'fixed',
                                            top: menuPos.top,
                                            bottom: menuPos.bottom,
                                            right: menuPos.right,
                                            zIndex: 9999
                                        }"
                                        class="w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-800">
                                        <button type="button"
                                            @click="closeMenu(); openRestockModal(@js($p->id))"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-700">
                                            <x-icon name="plus" class="h-3.5 w-3.5 text-emerald-600" />
                                            Tambah Stok
                                        </button>
                                        <a href="{{ route('inventory.ledger', $p->id) }}"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-700">
                                            <x-icon name="list" class="h-3.5 w-3.5 text-brand-600" />
                                            Kartu Stok
                                        </a>
                                        @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button type="button"
                                                @click="closeMenu(); openEditProductModal(@js($p))"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-700">
                                                <x-icon name="edit" class="h-3.5 w-3.5 text-slate-500" />
                                                Edit Produk
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2 rounded-md border border-slate-200 bg-slate-50 p-3">
                            <div>
                                <p class="text-[10px] text-slate-400">Stok</p>
                                <p class="mt-0.5 text-[12px] font-semibold num-tabular {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                    {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                                    <span class="text-[9px] font-normal text-slate-400">{{ $p->unit?->code }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400">HPP</p>
                                <p class="mt-0.5 text-[12px] font-medium text-slate-700 num-tabular">Rp {{ number_format((float) $p->average_cost, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400">Harga Jual</p>
                                <p class="mt-0.5 text-[12px] font-semibold text-slate-900 num-tabular">
                                    {{ (float) $p->selling_price > 0 ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <button type="button"
                                @click="openRestockModal(@js($p->id))"
                                class="btn btn-secondary btn-sm flex-1">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                Tambah Stok
                            </button>
                            <a href="{{ route('inventory.ledger', $p->id) }}"
                                class="btn btn-sm flex-1 bg-brand-50 text-brand-700 hover:bg-brand-100 text-center">
                                Kartu stok
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-8">
                        <span class="empty-state-icon"><x-icon name="package" class="h-5 w-5" /></span>
                        <p class="text-sm font-semibold text-slate-700">Barang tidak ditemukan</p>
                        <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau klik "Tambah Produk" di atas.</p>
                    </div>
                @endforelse
            </div>

            @if($productPage->hasPages())
                <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                    {{ $productPage->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TABEL 2: DAFTAR LAYANAN JASA (SERVICE CATALOG) -->
    @if($type === 'SERVICE')
        <div class="table-shell">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Katalog Layanan Jasa</h2>
                    <p class="card-subtitle">{{ $services->total() }} layanan aktif dengan kalkulasi HPP bahan baku (BoM) otomatis.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-success">{{ $services->total() }} Layanan Terdaftar</span>
                    @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                        <button type="button" @click="openAddServiceModal()" class="btn btn-primary btn-sm">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Tambah Layanan
                        </button>
                    @endif
                </div>
            </div>

            <div class="w-full">
                <table class="data-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left whitespace-nowrap px-2.5 py-2.5">Kode & Nama Layanan</th>
                            <th class="text-left whitespace-nowrap px-2.5 py-2.5">Resep Bahan Baku (BoM)</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Estimasi HPP</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Tarif / Satuan</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-px">Margin Laba</th>
                            <th class="text-right whitespace-nowrap px-2.5 py-2.5 w-[44px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $srv)
                            @php
                                $bomCost = 0;
                                foreach($srv->boms as $bom) {
                                    $rawCost = (float) ($bom->rawMaterialProduct?->average_cost ?? 0);
                                    $bomCost += (float) $bom->consumption_qty * $rawCost;
                                }
                                $price = (float) $srv->base_price;
                                $margin = $bomCost > 0 && $price > $bomCost ? round((($price - $bomCost) / $bomCost) * 100, 1) : null;
                                $unitShort = match(strtolower($srv->base_unit_name)) {
                                    'lembar' => 'lbr',
                                    'rim' => 'rim',
                                    'pcs' => 'pcs',
                                    'pack' => 'pack',
                                    default => strtolower($srv->base_unit_name),
                                };

                                $fullBomText = $srv->boms->map(function ($bom) {
                                    $q = rtrim(rtrim((string) $bom->consumption_qty, '0'), '.');
                                    $u = $bom->rawMaterialProduct?->unit?->code ?? '';
                                    $rawName = $bom->rawMaterialProduct?->name ?? '';
                                    $cleanName = trim(preg_replace('/\[.*?\]/', '', $rawName));
                                    return "{$q} {$u} {$cleanName}";
                                })->implode(' + ');

                                $shortBomText = $srv->boms->map(function ($bom) {
                                    $q = rtrim(rtrim((string) $bom->consumption_qty, '0'), '.');
                                    $u = $bom->rawMaterialProduct?->unit?->code ?? '';
                                    $rawName = $bom->rawMaterialProduct?->name ?? '';
                                    $cleanName = trim(preg_replace('/\[.*?\]/', '', $rawName));
                                    $shortName = str_ireplace(
                                        ['Kertas HVS', 'Tinta Toner Fotocopy', 'Plastik Laminating'],
                                        ['HVS', 'Toner', 'Laminating'],
                                        $cleanName
                                    );
                                    return "{$q} {$u} {$shortName}";
                                })->implode(' + ');
                            @endphp
                            <tr :class="activeMenuId === 's-{{ $srv->id }}' ? 'relative z-30 bg-slate-50/80' : ''">
                                <td class="whitespace-nowrap px-2.5 py-2.5">
                                    <div class="flex items-center gap-1.5 whitespace-nowrap" title="{{ $srv->name }} ({{ $srv->code }})">
                                        <span class="badge badge-info text-[9px] px-1.5 py-0.5 whitespace-nowrap">{{ $srv->service_group->label() }}</span>
                                        <span class="font-semibold text-slate-800 text-xs">{{ $srv->name }}</span>
                                        <span class="font-mono text-[10px] text-slate-400">({{ $srv->code }})</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-2.5 py-2.5">
                                    @if($srv->boms->count() > 0)
                                        <div class="flex items-center gap-1.5 text-xs text-slate-600 whitespace-nowrap" title="{{ $fullBomText }}">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                            <span class="whitespace-nowrap font-medium text-[11px]">{{ $shortBomText }}</span>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic whitespace-nowrap">Jasa murni</span>
                                    @endif
                                </td>
                                <td class="text-right font-mono text-xs text-slate-600 num-tabular whitespace-nowrap px-2.5 py-2.5">
                                    Rp {{ number_format($bomCost, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-semibold font-mono text-xs text-brand-700 num-tabular whitespace-nowrap px-2.5 py-2.5">
                                    Rp {{ number_format($price, 0, ',', '.') }}<span class="text-[10px] font-normal text-slate-400 font-sans ml-0.5">/{{ $unitShort }}</span>
                                </td>
                                <td class="text-right font-mono text-xs num-tabular whitespace-nowrap px-2.5 py-2.5">
                                    @if($margin !== null)
                                        <span class="font-semibold text-emerald-600">+{{ $margin }}%</span>
                                        <span class="text-[10px] text-slate-400 font-normal ml-0.5">(Rp {{ number_format($price - $bomCost, 0, ',', '.') }})</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap px-2.5 py-2.5">
                                    <div class="relative inline-block text-left" @click.outside="if (activeMenuId === 's-{{ $srv->id }}') closeMenu()">
                                        <button type="button"
                                            @click="toggleMenu('s-{{ $srv->id }}', $event)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                            title="Menu Aksi">
                                            <x-icon name="more-vertical" class="h-3.5 w-3.5" />
                                        </button>

                                        <div x-show="activeMenuId === 's-{{ $srv->id }}'"
                                            x-cloak
                                            x-transition.opacity.duration.100ms
                                            :style="{
                                                position: 'fixed',
                                                top: menuPos.top,
                                                bottom: menuPos.bottom,
                                                right: menuPos.right,
                                                zIndex: 9999
                                            }"
                                            class="w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-800">

                                            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                                                <button type="button"
                                                    @click="closeMenu(); openEditServiceModal(@js($srv))"
                                                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700">
                                                    <x-icon name="edit" class="h-3.5 w-3.5 text-slate-500" />
                                                    Edit Tarif Layanan
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state py-8">
                                        <span class="empty-state-icon"><x-icon name="sparkles" class="h-5 w-5" /></span>
                                        <p class="text-sm font-semibold text-slate-700">Layanan tidak ditemukan</p>
                                        <p class="mt-1 text-xs text-slate-400">Klik "+ Tambah Layanan" untuk mendaftarkan layanan baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($services->hasPages())
                <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                    {{ $services->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- ====================================================================== -->
    <!-- MODAL 1: TAMBAH PRODUK BARU (BARANG ETALASE / BAHAN BAKU)               -->
    <!-- ====================================================================== -->
    <div x-show="showAddProductModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-2xl" @click.outside="showAddProductModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Tambah Produk Baru</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Daftarkan master barang etalase atau bahan baku beserta HPP dan harga jual.</p>
                </div>
                <button type="button" @click="showAddProductModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('inventory.products.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="modal-body space-y-4">
                    <!-- Jenis Persediaan -->
                    <div>
                        <label class="form-label flex items-center gap-1.5">
                            <span>Jenis Persediaan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                                :class="newProd.inventory_type === 'RETAIL' ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <input type="radio" name="inventory_type" value="RETAIL"
                                <input type="radio" name="inventory_type" value="RETAIL"
                                    x-model="newProd.inventory_type"
                                    @change="onInventoryTypeChange('RETAIL')"
                                    class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Barang Etalase (Ritel)</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Dijual langsung di kasir POS (ATK, pulpen, buku, dll).</p>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                                :class="newProd.inventory_type === 'RAW_MATERIAL' ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <input type="radio" name="inventory_type" value="RAW_MATERIAL"
                                    x-model="newProd.inventory_type"
                                    @change="onInventoryTypeChange('RAW_MATERIAL')"
                                    class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Bahan Baku Jasa</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Dipakai operasional jasa (kertas fotocopy, toner, plastik laminating).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Nama Produk & Kategori -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="prod_name" class="form-label flex items-center gap-1.5">
                                <span>Nama Produk</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="prod_name" name="name" x-model="newProd.name" required
                                placeholder="Contoh: Penghapus Joyko B40"
                                class="input w-full">
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="prod_category_id" class="form-label flex items-center gap-1.5 mb-0">
                                    <span>Kategori</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="openQuickAddCategory(newProd.inventory_type)"
                                    class="text-[11px] font-semibold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                                    <x-icon name="plus" class="h-3 w-3" />
                                    <span>Kategori Baru</span>
                                </button>
                            </div>
                            <select id="prod_category_id" name="category_id" x-model="newProd.category_id" class="select input w-full mt-1" required>
                                <template x-for="cat in categoriesList" :key="cat.id">
                                    <option :value="cat.id"
                                        x-text="cat.name"
                                        :hidden="newProd.inventory_type !== cat.category_scope && cat.category_scope !== 'SERVICE'"
                                        :disabled="newProd.inventory_type !== cat.category_scope">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Satuan & SKU / Barcode -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="prod_unit_id" class="form-label flex items-center gap-1.5 mb-0">
                                    <span>Satuan</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="openQuickAddUnit()"
                                    class="text-[11px] font-semibold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                                    <x-icon name="plus" class="h-3 w-3" />
                                    <span>Satuan Baru</span>
                                </button>
                            </div>
                            <select id="prod_unit_id" name="unit_id" x-model="newProd.unit_id" class="select input w-full mt-1" required>
                                <template x-for="u in unitsList" :key="u.id">
                                    <option :value="u.id" x-text="u.code + ' (' + u.name + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label for="prod_sku" class="form-label">SKU / Kode Barang</label>
                            <input type="text" id="prod_sku" name="sku" x-model="newProd.sku"
                                placeholder="Otomatis jika kosong"
                                class="input w-full font-mono text-xs">
                        </div>

                        <div>
                            <label for="prod_barcode" class="form-label">Barcode (Opsional)</label>
                            <input type="text" id="prod_barcode" name="barcode" x-model="newProd.barcode"
                                placeholder="Scan barcode"
                                class="input w-full font-mono text-xs">
                        </div>
                    </div>

                    <!-- HPP dan Harga Jual -->
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="prod_avg_cost" class="form-label flex items-center gap-1.5">
                                    <span>HPP Awal / Biaya Modal (Rp)</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric" id="prod_avg_cost" name="average_cost"
                                        data-currency="true" required
                                        x-model.number="newProd.average_cost"
                                        placeholder="0"
                                        class="input w-full pl-9 font-mono text-sm font-semibold">
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Harga pokok modal per 1 satuan dari pemasok.</p>
                            </div>

                            <div x-show="newProd.inventory_type === 'RETAIL'">
                                <label for="prod_selling_price" class="form-label flex items-center gap-1.5">
                                    <span>Harga Jual Ritel (Rp)</span>
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric" id="prod_selling_price" name="selling_price"
                                        data-currency="true"
                                        :required="newProd.inventory_type === 'RETAIL'"
                                        x-model.number="newProd.selling_price"
                                        placeholder="0"
                                        class="input w-full pl-9 font-mono text-sm font-semibold text-brand-700">
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Tarif jual yang ditagihkan ke pelanggan di POS.</p>
                            </div>
                        </div>

                        <!-- Live Margin Preview untuk Retail -->
                        <div x-show="newProd.inventory_type === 'RETAIL' && newProd.selling_price > 0 && newProd.average_cost > 0"
                            class="mt-3 flex items-center justify-between rounded-md border border-emerald-100 bg-emerald-50/80 px-3 py-2 text-xs text-emerald-800">
                            <span class="font-medium">Margin Keuntungan Bersih:</span>
                            <span class="font-mono font-bold">
                                Rp <span x-text="formatRp(newProd.selling_price - newProd.average_cost)"></span>
                                (<span x-text="calculateProductMargin()"></span>%)
                            </span>
                        </div>
                    </div>

                    <!-- Batas Alert Stok & Saldo Awal -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="prod_min_alert" class="form-label">Batas Peringatan Stok Menipis</label>
                            <input type="text" inputmode="numeric" id="prod_min_alert" name="min_stock_alert"
                                data-quantity="true" step="1"
                                x-model.number="newProd.min_stock_alert"
                                class="input w-full font-mono text-xs">
                            <p class="mt-1 text-[11px] text-slate-500">Sistem memberi notifikasi jika stok di bawah nilai ini.</p>
                        </div>

                        <div>
                            <label for="prod_initial_stock" class="form-label">Stok Awal Fisik (Opsional)</label>
                            <input type="text" inputmode="decimal" id="prod_initial_stock" name="initial_stock"
                                data-quantity="true" step="0.0001"
                                x-model.number="newProd.initial_stock"
                                placeholder="0"
                                class="input w-full font-mono text-xs">
                            <p class="mt-1 text-[11px] text-slate-500">Jika stok sudah ada, masukkan saldo awalnya (default 0).</p>
                        </div>
                    </div>

                    <!-- Pilihan Sumber Dana jika Stok Awal > 0 -->
                    <div x-show="newProd.initial_stock > 0" class="rounded-lg border border-brand-100 bg-brand-50/50 p-3">
                        <label for="prod_funding_account_id" class="form-label">Akun Sumber Dana / Saldo Awal</label>
                        <select id="prod_funding_account_id" name="funding_account_id" class="select input w-full">
                            @foreach($fundingAccounts as $fa)
                                <option value="{{ $fa->id }}">{{ $fa->account_code }} &bull; {{ $fa->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-500">Total nilai stok awal akan otomatis dicatat ke jurnal akuntansi.</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showAddProductModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan Produk Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 2: TAMBAH LAYANAN JASA BARU (SERVICE WITH BOM & ESTIMATED HPP)     -->
    <!-- ====================================================================== -->
    <div x-show="showAddServiceModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-2xl" @click.outside="showAddServiceModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Tambah Layanan Jasa Baru</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Daftarkan layanan fotocopy, print, penjilidan, laminating, dsb dengan resep bahan baku (BoM).</p>
                </div>
                <button type="button" @click="showAddServiceModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('inventory.services.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="modal-body space-y-4">
                    <!-- Kelompok Jasa & Nama Layanan -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="srv_group" class="form-label flex items-center gap-1.5">
                                <span>Kelompok Layanan</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="srv_group" name="service_group" x-model="newSrv.service_group" class="select input w-full" required>
                                <option value="FOTOCOPY">Fotocopy</option>
                                <option value="PRINT">Print</option>
                                <option value="LAMINATING">Laminating</option>
                                <option value="BINDING">Penjilidan</option>
                                <option value="SCAN">Scan</option>
                                <option value="OTHER">Lainnya</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="srv_name" class="form-label flex items-center gap-1.5">
                                <span>Nama Layanan Jasa</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="srv_name" name="name" x-model="newSrv.name" required
                                placeholder="Contoh: Jilid Spiral Kawat F4"
                                class="input w-full">
                        </div>
                    </div>

                    <!-- Kode Layanan & Satuan Basis & Tarif Jual -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="srv_code" class="form-label">Kode Layanan</label>
                            <input type="text" id="srv_code" name="code" x-model="newSrv.code"
                                placeholder="Otomatis jika kosong"
                                class="input w-full font-mono text-xs">
                        </div>

                        <div>
                            <label for="srv_unit" class="form-label flex items-center gap-1.5">
                                <span>Satuan Basis</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="srv_unit" name="base_unit_name" x-model="newSrv.base_unit_name" required
                                placeholder="Contoh: Lembar, Buku, Pcs"
                                class="input w-full text-xs">
                        </div>

                        <div>
                            <label for="srv_price" class="form-label flex items-center gap-1.5">
                                <span>Tarif / Harga Jual (Rp)</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">Rp</span>
                                <input type="text" inputmode="numeric" id="srv_price" name="base_price"
                                    data-currency="true" required
                                    x-model.number="newSrv.base_price"
                                    placeholder="0"
                                    class="input w-full pl-9 font-mono text-sm font-semibold text-brand-700">
                            </div>
                        </div>
                    </div>

                    <!-- Komponen Bahan Baku / Bill of Materials (BoM) -->
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-semibold text-slate-900">Resep Konsumsi Bahan Baku (BoM)</h4>
                                <p class="text-[11px] text-slate-500">Bahan baku yang otomatis dipotong persediaannya saat kasir melayani transaksi ini.</p>
                            </div>
                            <button type="button" @click="addBomRow()" class="btn btn-secondary btn-sm">
                                <x-icon name="plus" class="h-3 w-3" />
                                Tambah Bahan
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(bom, bIdx) in newSrv.boms" :key="bIdx">
                                <div class="flex items-center gap-2 rounded-md bg-white p-2 border border-slate-200">
                                    <div class="flex-1">
                                        <select :name="'boms[' + bIdx + '][raw_material_product_id]'"
                                            x-model="bom.raw_material_product_id"
                                            class="select input w-full py-1 text-xs">
                                            <option value="">-- Pilih Bahan Baku --</option>
                                            <template x-for="rm in rawMaterialsList" :key="rm.id">
                                                <option :value="rm.id"
                                                    x-text="rm.name + ' (HPP: Rp ' + formatRp(rm.average_cost) + '/' + (rm.unit ? rm.unit.code : 'unit') + ')'">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <div class="relative">
                                            <input type="text" inputmode="decimal"
                                                data-quantity="true" step="0.0001"
                                                :name="'boms[' + bIdx + '][consumption_qty]'"
                                                x-model.number="bom.consumption_qty"
                                                placeholder="Qty konsumsi"
                                                class="input w-full py-1 text-right font-mono text-xs">
                                        </div>
                                    </div>
                                    <button type="button" @click="removeBomRow(bIdx)"
                                        class="rounded p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Kalkulasi Estimasi HPP Layanan & Margin -->
                        <div class="mt-4 grid grid-cols-1 gap-3 rounded-md border border-slate-200 bg-white p-3 sm:grid-cols-3">
                            <div>
                                <p class="text-[10px] text-slate-500">Estimasi HPP (Modal Bahan)</p>
                                <p class="mt-0.5 font-mono text-sm font-bold text-slate-800">
                                    Rp <span x-text="formatRp(getEstimatedServiceHpp())"></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500">Tarif Jual Layanan</p>
                                <p class="mt-0.5 font-mono text-sm font-bold text-brand-700">
                                    Rp <span x-text="formatRp(newSrv.base_price || 0)"></span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500">Estimasi Laba per Satuan</p>
                                <p class="mt-0.5 font-mono text-sm font-bold"
                                    :class="(newSrv.base_price - getEstimatedServiceHpp()) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                    Rp <span x-text="formatRp((newSrv.base_price || 0) - getEstimatedServiceHpp())"></span>
                                    <span class="text-xs font-normal" x-text="'(' + getServiceMarginPercent() + '%)'"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showAddServiceModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan Layanan Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 3: EDIT PRODUK (HARGA JUAL, HPP, MIN ALERT, STATUS)              -->
    <!-- ====================================================================== -->
    <div x-show="showEditProductModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-lg" @click.outside="showEditProductModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Edit Data Produk</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perbarui harga jual, HPP, dan batas stok menipis.</p>
                </div>
                <button type="button" @click="showEditProductModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" :action="'/inventory/products/' + editProd.id" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')
                <div class="modal-body space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" x-model="editProd.name" required class="input w-full">
                        </div>
                        <div>
                            <label class="form-label">Satuan</label>
                            <select name="unit_id" x-model="editProd.unit_id" class="select input w-full" required>
                                <template x-for="u in unitsList" :key="u.id">
                                    <option :value="u.id" x-text="u.code + ' (' + u.name + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">HPP (Modal) Rp</label>
                            <input type="text" inputmode="numeric" data-currency="true" name="average_cost"
                                x-model.number="editProd.average_cost" required
                                class="input w-full font-mono text-xs font-semibold">
                        </div>
                        <div>
                            <label class="form-label">Harga Jual Ritel Rp</label>
                            <input type="text" inputmode="numeric" data-currency="true" name="selling_price"
                                x-model.number="editProd.selling_price" required
                                class="input w-full font-mono text-xs font-semibold text-brand-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Min Alert Stok</label>
                            <input type="text" inputmode="numeric" data-quantity="true" step="1"
                                name="min_stock_alert" x-model.number="editProd.min_stock_alert" required
                                class="input w-full font-mono text-xs">
                        </div>
                        <div>
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" x-model="editProd.barcode" class="input w-full font-mono text-xs">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="edit_prod_active" name="is_active" value="1"
                            :checked="editProd.is_active" class="checkbox">
                        <label for="edit_prod_active" class="text-xs font-medium text-slate-700">Produk Aktif (Dapat dijual di POS)</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showEditProductModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 4: EDIT LAYANAN JASA (TARIF HARGA JUAL, SATUAN, BOM & STATUS)   -->
    <!-- ====================================================================== -->
    <div x-show="showEditServiceModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-xl" @click.outside="showEditServiceModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Edit Layanan Jasa</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perbarui tarif harga jual, satuan basis, atau resep bahan baku (BoM).</p>
                </div>
                <button type="button" @click="showEditServiceModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" :action="'/inventory/services/' + editSrv.id" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')
                <div class="modal-body space-y-4">
                    <div>
                        <label class="form-label">Nama Layanan</label>
                        <input type="text" name="name" x-model="editSrv.name" required class="input w-full">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Tarif / Harga Jual Rp</label>
                            <input type="text" inputmode="numeric" data-currency="true" name="base_price"
                                x-model.number="editSrv.base_price" required
                                class="input w-full font-mono text-xs font-semibold text-brand-700">
                        </div>
                        <div>
                            <label class="form-label">Satuan Basis</label>
                            <input type="text" name="base_unit_name" x-model="editSrv.base_unit_name" required
                                class="input w-full text-xs">
                        </div>
                    </div>

                    <!-- Resep Konsumsi Bahan Baku (BoM) -->
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-semibold text-slate-900">Resep Konsumsi Bahan Baku (BoM)</h4>
                                <p class="text-[10px] text-slate-500">Pilih bahan baku yang dipotong saat kasir melayani transaksi (A4, F4, Toner, dsb).</p>
                            </div>
                            <button type="button" @click="addEditBomRow()" class="btn btn-secondary btn-xs">
                                <x-icon name="plus" class="h-3 w-3" />
                                Tambah Bahan
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(bom, bIdx) in editSrv.boms" :key="bIdx">
                                <div class="flex items-center gap-2 rounded-md bg-white p-2 border border-slate-200">
                                    <div class="flex-1">
                                        <select :name="'boms[' + bIdx + '][raw_material_product_id]'"
                                            x-model="bom.raw_material_product_id"
                                            class="select input w-full py-1 text-xs">
                                            <option value="">-- Pilih Bahan Baku --</option>
                                            <template x-for="rm in rawMaterialsList" :key="rm.id">
                                                <option :value="rm.id"
                                                    x-text="rm.name + ' (HPP: Rp ' + formatRp(rm.average_cost) + '/' + (rm.unit ? rm.unit.code : 'unit') + ')'">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="w-28">
                                        <input type="text" inputmode="decimal"
                                            data-quantity="true" step="0.0001"
                                            :name="'boms[' + bIdx + '][consumption_qty]'"
                                            x-model.number="bom.consumption_qty"
                                            placeholder="Qty"
                                            class="input w-full py-1 text-right font-mono text-xs">
                                    </div>
                                    <button type="button" @click="removeEditBomRow(bIdx)"
                                        class="rounded p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            </template>
                            <template x-if="!editSrv.boms || editSrv.boms.length === 0">
                                <p class="text-[11px] text-slate-400 italic py-1 text-center">Tidak ada bahan baku (jasa murni). Klik "Tambah Bahan" di atas jika menggunakan bahan.</p>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="edit_srv_active" name="is_active" value="1"
                            :checked="editSrv.is_active" class="checkbox">
                        <label for="edit_srv_active" class="text-xs font-medium text-slate-700">Layanan Aktif (Tampil di POS)</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showEditServiceModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL 5: RESTOCK SATUAN                                                -->
    <!-- ====================================================================== -->
    <div x-show="showRestockModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-lg" @click.outside="showRestockModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Tambah Stok Barang</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perbarui stok dan HPP rata-rata berjalan</p>
                </div>
                <button type="button" @click="showRestockModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('inventory.restock') }}"
                class="flex min-h-0 flex-1 flex-col"
                x-data="{ qty: 1, price: 0, get total() { return (parseFloat(this.qty || 0) * parseFloat(this.price || 0)).toLocaleString('id-ID'); } }">
                @csrf
                <div class="modal-body space-y-4">
                    <div class="alert alert-info">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Stok dan HPP otomatis diperbarui. Pembelian dicatat sebagai kas keluar atau utang.</span>
                    </div>

                    <div>
                        <label class="form-label">Barang / Bahan</label>
                        <select name="product_id" x-model="selectedProductId" class="select input w-full">
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->name }} — stok {{ (int) $prod->current_stock }} {{ $prod->unit?->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jumlah</label>
                            <input type="text" inputmode="decimal" data-quantity="true" step="0.0001"
                                name="qty_purchased" x-model="qty" required
                                class="input font-mono w-full">
                        </div>
                        <div>
                            <label class="form-label">Harga Satuan (Rp)</label>
                            <input type="text" inputmode="numeric" data-currency="true"
                                name="purchase_unit_price" x-model="price" required
                                placeholder="Contoh: 45000"
                                class="input font-mono w-full">
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-brand-100 bg-brand-50 px-4 py-3">
                        <div>
                            <p class="text-[11px] font-medium text-brand-700">Total Pembelian</p>
                            <p class="mt-0.5 text-lg font-semibold text-brand-900 num-tabular">Rp <span x-text="total"></span></p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Sumber Dana</label>
                        <select name="account_id" required class="select input w-full">
                            @foreach($fundingAccounts as $fa)
                                <option value="{{ $fa->id }}">{{ $fa->account_code }} &bull; {{ $fa->account_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showRestockModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL: KELOLA KATEGORI MASTER                                           -->
    <!-- ====================================================================== -->
    <div x-show="showManageCategoriesModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-2xl" @click.outside="showManageCategoriesModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-icon name="layers" class="h-4 w-4 text-slate-700" />
                        <span>Kelola Kategori Master</span>
                    </h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Tambah kategori baru atau hapus kategori yang belum digunakan.</p>
                </div>
                <button type="button" @click="showManageCategoriesModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="modal-body space-y-4">
                <div x-show="catForm.error" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                    <span x-text="catForm.error"></span>
                </div>

                <!-- Form Tambah Kategori -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5">
                    <h4 class="text-xs font-semibold text-slate-800 mb-2">Tambah Kategori Baru</h4>
                    <form @submit.prevent="saveCategory(false)" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1">
                            <input type="text" x-model="catForm.name" placeholder="Nama kategori baru..." required
                                class="input w-full text-xs">
                        </div>
                        <div class="w-full sm:w-48">
                            <select x-model="catForm.category_scope" class="select input w-full text-xs" required>
                                <option value="RETAIL">Barang Etalase (Ritel)</option>
                                <option value="RAW_MATERIAL">Bahan Baku Jasa</option>
                                <option value="SERVICE">Layanan Jasa</option>
                            </select>
                        </div>
                        <button type="submit" :disabled="catForm.loading" class="btn btn-primary text-xs shrink-0 inline-flex items-center gap-1.5">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            <span x-text="catForm.loading ? 'Menyimpan...' : 'Tambah'"></span>
                        </button>
                    </form>
                </div>

                <!-- Tabel Daftar Kategori -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-800 mb-2 flex items-center justify-between">
                        <span>Daftar Kategori</span>
                        <span class="text-[11px] font-normal text-slate-500" x-text="categoriesList.length + ' kategori terdaftar'"></span>
                    </h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200 max-h-64 overflow-y-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-2">Nama Kategori</th>
                                    <th class="px-3 py-2">Jenis / Scope</th>
                                    <th class="px-3 py-2 text-center">Penggunaan</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="cat in categoriesList" :key="cat.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-3 py-2.5 font-medium text-slate-900" x-text="cat.name"></td>
                                        <td class="px-3 py-2.5">
                                            <span x-show="cat.category_scope === 'RETAIL'" class="badge badge-brand text-[10px]">Ritel</span>
                                            <span x-show="cat.category_scope === 'RAW_MATERIAL'" class="badge bg-purple-50 text-purple-700 border-purple-200 text-[10px]">Bahan Baku</span>
                                            <span x-show="cat.category_scope === 'SERVICE'" class="badge bg-amber-50 text-amber-700 border-amber-200 text-[10px]">Layanan</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-[11px] text-slate-500">
                                            <span x-text="(cat.products_count || 0) + ' produk' + (cat.category_scope === 'SERVICE' ? ', ' + (cat.services_count || 0) + ' layanan' : '')"></span>
                                        </td>
                                        <td class="px-3 py-2.5 text-right">
                                            <template x-if="(cat.products_count || 0) === 0 && (cat.services_count || 0) === 0">
                                                <button type="button" @click="deleteCategory(cat)"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline">
                                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    <span>Hapus</span>
                                                </button>
                                            </template>
                                            <template x-if="(cat.products_count || 0) > 0 || (cat.services_count || 0) > 0">
                                                <span class="text-[10px] text-slate-400 italic">Digunakan</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" @click="showManageCategoriesModal = false" class="btn btn-secondary">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL: KELOLA SATUAN MASTER                                             -->
    <!-- ====================================================================== -->
    <div x-show="showManageUnitsModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-2xl" @click.outside="showManageUnitsModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-icon name="scale" class="h-4 w-4 text-slate-700" />
                        <span>Kelola Satuan (Unit) Master</span>
                    </h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Tambah satuan baru atau hapus satuan yang belum digunakan dalam inventaris.</p>
                </div>
                <button type="button" @click="showManageUnitsModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="modal-body space-y-4">
                <div x-show="unitForm.error" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                    <span x-text="unitForm.error"></span>
                </div>

                <!-- Form Tambah Satuan -->
                <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5">
                    <h4 class="text-xs font-semibold text-slate-800 mb-2">Tambah Satuan Baru</h4>
                    <form @submit.prevent="saveUnit(false)" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="text-[11px] font-medium text-slate-600">Kode Satuan (Singkatan)</label>
                                <input type="text" x-model="unitForm.code" placeholder="Contoh: BUKU, PACK, KG" required
                                    class="input w-full text-xs font-mono uppercase mt-0.5">
                            </div>
                            <div>
                                <label class="text-[11px] font-medium text-slate-600">Nama Satuan (Lengkap)</label>
                                <input type="text" x-model="unitForm.name" placeholder="Contoh: Buku, Pack, Kilogram" required
                                    class="input w-full text-xs mt-0.5">
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input type="checkbox" x-model="unitForm.allow_decimal" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span>Izinkan angka desimal / pecahan (contoh: Kilogram, Meter, Liter)</span>
                            </label>
                            <button type="submit" :disabled="unitForm.loading" class="btn btn-primary text-xs shrink-0 inline-flex items-center gap-1.5">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span x-text="unitForm.loading ? 'Menyimpan...' : 'Tambah Satuan'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Daftar Satuan -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-800 mb-2 flex items-center justify-between">
                        <span>Daftar Satuan</span>
                        <span class="text-[11px] font-normal text-slate-500" x-text="unitsList.length + ' satuan terdaftar'"></span>
                    </h4>
                    <div class="overflow-x-auto rounded-lg border border-slate-200 max-h-64 overflow-y-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-2">Kode</th>
                                    <th class="px-3 py-2">Nama Satuan</th>
                                    <th class="px-3 py-2 text-center">Desimal?</th>
                                    <th class="px-3 py-2 text-center">Penggunaan</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="u in unitsList" :key="u.id">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-3 py-2.5 font-mono font-bold text-slate-900" x-text="u.code"></td>
                                        <td class="px-3 py-2.5 font-medium text-slate-700" x-text="u.name"></td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span x-show="u.allow_decimal" class="badge badge-success text-[10px]">Ya</span>
                                            <span x-show="!u.allow_decimal" class="badge badge-slate text-[10px]">Tidak</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-[11px] text-slate-500">
                                            <span x-text="(u.products_count || 0) + ' produk'"></span>
                                        </td>
                                        <td class="px-3 py-2.5 text-right">
                                            <template x-if="(u.products_count || 0) === 0">
                                                <button type="button" @click="deleteUnit(u)"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline">
                                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                                    <span>Hapus</span>
                                                </button>
                                            </template>
                                            <template x-if="(u.products_count || 0) > 0">
                                                <span class="text-[10px] text-slate-400 italic">Digunakan</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" @click="showManageUnitsModal = false" class="btn btn-secondary">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL: QUICK ADD KATEGORI                                               -->
    <!-- ====================================================================== -->
    <div x-show="showQuickAddCategoryModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-md" @click.outside="showQuickAddCategoryModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-icon name="layers" class="h-4 w-4 text-slate-700" />
                        <span>Tambah Kategori Baru</span>
                    </h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Kategori baru akan otomatis terpilih pada produk ini.</p>
                </div>
                <button type="button" @click="showQuickAddCategoryModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form @submit.prevent="saveCategory(true)" class="flex min-h-0 flex-1 flex-col">
                <div class="modal-body space-y-3">
                    <div x-show="quickCat.error" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
                        <span x-text="quickCat.error"></span>
                    </div>

                    <div>
                        <label class="form-label flex items-center gap-1">
                            <span>Nama Kategori</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="quickCat.name" placeholder="Contoh: Alat Tulis Kantor, Kertas Cetak..." required
                            class="input w-full">
                    </div>

                    <div>
                        <label class="form-label flex items-center gap-1">
                            <span>Jenis Persediaan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="quickCat.category_scope" class="select input w-full" required>
                            <option value="RETAIL">Barang Etalase (Ritel)</option>
                            <option value="RAW_MATERIAL">Bahan Baku Jasa</option>
                            <option value="SERVICE">Layanan Jasa</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showQuickAddCategoryModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" :disabled="quickCat.loading" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        <span x-text="quickCat.loading ? 'Menyimpan...' : 'Simpan & Pilih Kategori'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- MODAL: QUICK ADD SATUAN                                                 -->
    <!-- ====================================================================== -->
    <div x-show="showQuickAddUnitModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-md" @click.outside="showQuickAddUnitModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-icon name="scale" class="h-4 w-4 text-slate-700" />
                        <span>Tambah Satuan Baru</span>
                    </h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Satuan baru akan langsung terdaftar dan dapat dipilih.</p>
                </div>
                <button type="button" @click="showQuickAddUnitModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form @submit.prevent="saveUnit(true)" class="flex min-h-0 flex-1 flex-col">
                <div class="modal-body space-y-3">
                    <div x-show="quickUnit.error" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
                        <span x-text="quickUnit.error"></span>
                    </div>

                    <div>
                        <label class="form-label flex items-center gap-1">
                            <span>Kode Satuan (Singkatan)</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="quickUnit.code" placeholder="Contoh: BUKU, PACK, ROLL" required
                            class="input w-full font-mono uppercase">
                    </div>

                    <div>
                        <label class="form-label flex items-center gap-1">
                            <span>Nama Satuan (Lengkap)</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="quickUnit.name" placeholder="Contoh: Buku, Pack, Roll" required
                            class="input w-full">
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer pt-1">
                        <input type="checkbox" x-model="quickUnit.allow_decimal" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>Izinkan angka desimal / pecahan (misal: Meter, Kg)</span>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showQuickAddUnitModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" :disabled="quickUnit.loading" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" />
                        <span x-text="quickUnit.loading ? 'Menyimpan...' : 'Simpan & Pilih Satuan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function inventoryManager() {
    const rawMaterials = @js($rawMaterials);
    const productsData = @js($products);
    const categoriesData = @js($categories);
    const unitsData = @js($units);

    return {
        csrfToken: '{{ csrf_token() }}',
        showRestockModal: false,
        selectedProductId: productsData[0]?.id || null,

        activeMenuId: null,
        menuPos: { top: '0px', right: '0px', bottom: 'auto' },

        toggleMenu(id, event) {
            if (event) event.stopPropagation();
            if (this.activeMenuId === id) {
                this.activeMenuId = null;
                return;
            }
            this.activeMenuId = id;
            if (event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const openUp = spaceBelow < 170 && rect.top > 170;
                this.menuPos = {
                    top: openUp ? 'auto' : (rect.bottom + 4) + 'px',
                    bottom: openUp ? (window.innerHeight - rect.top + 4) + 'px' : 'auto',
                    right: Math.max(12, window.innerWidth - rect.right) + 'px',
                };
            }
        },

        closeMenu() {
            this.activeMenuId = null;
        },

        categoriesList: categoriesData,
        unitsList: unitsData,

        showManageCategoriesModal: false,
        showManageUnitsModal: false,
        showQuickAddCategoryModal: false,
        showQuickAddUnitModal: false,

        catForm: { name: '', category_scope: 'RETAIL', loading: false, error: null },
        unitForm: { code: '', name: '', allow_decimal: false, loading: false, error: null },

        quickCat: { name: '', category_scope: 'RETAIL', loading: false, error: null },
        quickUnit: { code: '', name: '', allow_decimal: false, loading: false, error: null },

        openManageCategoriesModal() {
            this.catForm = { name: '', category_scope: 'RETAIL', loading: false, error: null };
            this.showManageCategoriesModal = true;
        },

        openManageUnitsModal() {
            this.unitForm = { code: '', name: '', allow_decimal: false, loading: false, error: null };
            this.showManageUnitsModal = true;
        },

        openQuickAddCategory(defaultScope = 'RETAIL') {
            this.quickCat = { name: '', category_scope: defaultScope || 'RETAIL', loading: false, error: null };
            this.showQuickAddCategoryModal = true;
        },

        openQuickAddUnit() {
            this.quickUnit = { code: '', name: '', allow_decimal: false, loading: false, error: null };
            this.showQuickAddUnitModal = true;
        },

        async saveCategory(isQuick = false) {
            const form = isQuick ? this.quickCat : this.catForm;
            if (!form.name.trim()) {
                form.error = 'Nama kategori wajib diisi.';
                return;
            }
            form.loading = true;
            form.error = null;
            try {
                const res = await fetch('{{ route('inventory.categories.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        name: form.name.trim(),
                        category_scope: form.category_scope
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    form.error = data.message || 'Gagal menyimpan kategori.';
                    return;
                }
                this.categoriesList.push(data.category);
                if (isQuick) {
                    this.newProd.category_id = data.category.id;
                    this.showQuickAddCategoryModal = false;
                } else {
                    form.name = '';
                }
            } catch (e) {
                form.error = 'Terjadi kesalahan koneksi server.';
            } finally {
                form.loading = false;
            }
        },

        async deleteCategory(cat) {
            const confirmed = await window.bestcoDialog.confirm({
                title: 'Hapus Kategori?',
                message: `Kategori "${cat.name}" akan dihapus permanen.`,
                detail: 'Kategori yang masih digunakan oleh produk tidak dapat dihapus.',
                type: 'danger',
                confirmText: 'Hapus Kategori',
                cancelText: 'Batal'
            });

            if (!confirmed) return;

            try {
                const res = await fetch(`/inventory/categories/${cat.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                });
                const data = await res.json();
                if (!res.ok) {
                    window.bestcoDialog.alert({
                        title: 'Gagal Menghapus Kategori',
                        message: data.message || 'Gagal menghapus kategori.',
                        type: 'danger'
                    });
                    return;
                }
                this.categoriesList = this.categoriesList.filter(c => c.id !== cat.id);
                if (this.newProd.category_id == cat.id) {
                    const fallback = this.categoriesList.find(c => c.category_scope === this.newProd.inventory_type);
                    this.newProd.category_id = fallback ? fallback.id : '';
                }
            } catch (e) {
                window.bestcoDialog.alert({
                    title: 'Kesalahan Jaringan',
                    message: 'Terjadi kesalahan jaringan saat menghapus kategori.',
                    type: 'danger'
                });
            }
        },

        async saveUnit(isQuick = false) {
            const form = isQuick ? this.quickUnit : this.unitForm;
            if (!form.code.trim() || !form.name.trim()) {
                form.error = 'Kode dan nama satuan wajib diisi.';
                return;
            }
            form.loading = true;
            form.error = null;
            try {
                const res = await fetch('{{ route('inventory.units.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        code: form.code.trim().toUpperCase(),
                        name: form.name.trim(),
                        allow_decimal: !!form.allow_decimal
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    form.error = data.message || 'Gagal menyimpan satuan.';
                    return;
                }
                this.unitsList.push(data.unit);
                if (isQuick) {
                    this.newProd.unit_id = data.unit.id;
                    this.showQuickAddUnitModal = false;
                } else {
                    form.code = '';
                    form.name = '';
                    form.allow_decimal = false;
                }
            } catch (e) {
                form.error = 'Terjadi kesalahan koneksi server.';
            } finally {
                form.loading = false;
            }
        },

        async deleteUnit(u) {
            const confirmed = await window.bestcoDialog.confirm({
                title: 'Hapus Satuan?',
                message: `Satuan "${u.code} (${u.name})" akan dihapus permanen.`,
                detail: 'Satuan yang masih digunakan oleh produk tidak dapat dihapus.',
                type: 'danger',
                confirmText: 'Hapus Satuan',
                cancelText: 'Batal'
            });

            if (!confirmed) return;

            try {
                const res = await fetch(`/inventory/units/${u.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                });
                const data = await res.json();
                if (!res.ok) {
                    window.bestcoDialog.alert({
                        title: 'Gagal Menghapus Satuan',
                        message: data.message || 'Gagal menghapus satuan.',
                        type: 'danger'
                    });
                    return;
                }
                this.unitsList = this.unitsList.filter(item => item.id !== u.id);
                if (this.newProd.unit_id == u.id) {
                    this.newProd.unit_id = this.unitsList[0]?.id || '';
                }
            } catch (e) {
                window.bestcoDialog.alert({
                    title: 'Kesalahan Jaringan',
                    message: 'Terjadi kesalahan jaringan saat menghapus satuan.',
                    type: 'danger'
                });
            }
        },

        showAddProductModal: false,
        newProd: {
            name: '',
            sku: '',
            barcode: '',
            inventory_type: 'RETAIL',
            category_id: categoriesData.find(c => c.category_scope === 'RETAIL')?.id || '',
            unit_id: unitsData[0]?.id || '',
            average_cost: 0,
            selling_price: 0,
            min_stock_alert: 5,
            initial_stock: 0
        },

        onInventoryTypeChange(type) {
            this.newProd.inventory_type = type;
            const match = this.categoriesList.find(c => c.category_scope === type);
            if (match) {
                this.newProd.category_id = match.id;
            }
        },

        showEditProductModal: false,
        editProd: {
            id: null,
            name: '',
            unit_id: null,
            selling_price: 0,
            average_cost: 0,
            min_stock_alert: 5,
            barcode: '',
            is_active: true
        },

        showAddServiceModal: false,
        rawMaterialsList: rawMaterials,
        newSrv: {
            service_group: 'FOTOCOPY',
            name: '',
            code: '',
            base_unit_name: 'Lembar',
            base_price: 0,
            boms: [
                { raw_material_product_id: rawMaterials[0]?.id || '', consumption_qty: 1 }
            ]
        },

        showEditServiceModal: false,
        editSrv: {
            id: null,
            name: '',
            base_price: 0,
            base_unit_name: 'Lembar',
            is_active: true
        },

        openRestockModal(productId) {
            this.selectedProductId = productId;
            this.showRestockModal = true;
        },

        openAddProductModal() {
            const defaultCat = this.categoriesList.find(c => c.category_scope === 'RETAIL')?.id || '';
            const defaultUnit = this.unitsList[0]?.id || '';
            this.newProd = {
                name: '',
                sku: '',
                barcode: '',
                inventory_type: 'RETAIL',
                category_id: defaultCat,
                unit_id: defaultUnit,
                average_cost: 0,
                selling_price: 0,
                min_stock_alert: 5,
                initial_stock: 0
            };
            this.showAddProductModal = true;
        },

        openEditProductModal(p) {
            this.editProd = {
                id: p.id,
                name: p.name,
                unit_id: p.unit_id,
                selling_price: parseFloat(p.selling_price) || 0,
                average_cost: parseFloat(p.average_cost) || 0,
                min_stock_alert: parseFloat(p.min_stock_alert) || 5,
                barcode: p.barcode || '',
                is_active: !!p.is_active
            };
            this.showEditProductModal = true;
        },

        openAddServiceModal() {
            this.newSrv = {
                service_group: 'FOTOCOPY',
                name: '',
                code: '',
                base_unit_name: 'Lembar',
                base_price: 0,
                boms: [
                    { raw_material_product_id: this.rawMaterialsList[0]?.id || '', consumption_qty: 1 }
                ]
            };
            this.showAddServiceModal = true;
        },

        openEditServiceModal(s) {
            this.editSrv = {
                id: s.id,
                name: s.name,
                base_price: parseFloat(s.base_price) || 0,
                base_unit_name: s.base_unit_name,
                is_active: !!s.is_active,
                boms: (s.boms || []).map(b => ({
                    raw_material_product_id: b.raw_material_product_id,
                    consumption_qty: parseFloat(b.consumption_qty) || 1
                }))
            };
            this.showEditServiceModal = true;
        },

        addEditBomRow() {
            if (!this.editSrv.boms) {
                this.editSrv.boms = [];
            }
            this.editSrv.boms.push({
                raw_material_product_id: this.rawMaterialsList[0]?.id || '',
                consumption_qty: 1
            });
        },

        removeEditBomRow(idx) {
            this.editSrv.boms.splice(idx, 1);
        },

        addBomRow() {
            this.newSrv.boms.push({
                raw_material_product_id: this.rawMaterialsList[0]?.id || '',
                consumption_qty: 1
            });
        },

        removeBomRow(idx) {
            this.newSrv.boms.splice(idx, 1);
        },

        getEstimatedServiceHpp() {
            let total = 0;
            this.newSrv.boms.forEach(b => {
                const rm = this.rawMaterialsList.find(r => r.id == b.raw_material_product_id);
                if (rm) {
                    const cost = parseFloat(rm.average_cost) || 0;
                    const qty = parseFloat(b.consumption_qty) || 0;
                    total += (cost * qty);
                }
            });
            return total;
        },

        getServiceMarginPercent() {
            const hpp = this.getEstimatedServiceHpp();
            const price = parseFloat(this.newSrv.base_price) || 0;
            if (hpp <= 0) return price > 0 ? '100' : '0';
            return (((price - hpp) / hpp) * 100).toFixed(1);
        },

        calculateProductMargin() {
            const cost = parseFloat(this.newProd.average_cost) || 0;
            const price = parseFloat(this.newProd.selling_price) || 0;
            if (cost <= 0) return '0';
            return (((price - cost) / cost) * 100).toFixed(1);
        },

        formatRp(val) {
            return new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
        }
    };
}
</script>
@endsection
