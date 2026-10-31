@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="inventoryManager()" @keydown.escape.window="closeMenu()">
    <x-page-header
        title="Persediaan"
        subtitle="Stok barang etalase dan bahan baku jasa.">
        <x-slot:actions>
            <a href="{{ route('inventory.inbound') }}" class="btn btn-secondary">
                <x-icon name="download" class="h-4 w-4" />
                Barang masuk (Batch)
            </a>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah stok
            </a>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Barang rusak
            </a>
            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                <button type="button" @click="openAddProductModal()" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Produk
                </button>
                <button type="button" @click="openAddServiceModal()" class="btn btn-dark">
                    <x-icon name="sparkles" class="h-4 w-4" />
                    Tambah Layanan
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/90 p-4 text-emerald-900 shadow-xs">
            <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <div class="flex-1 text-xs">
                <p class="font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

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
                    <p class="card-subtitle">{{ $products->count() }} barang aktif</p>
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
                        @forelse($products as $p)
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
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            :class="isNearBottom ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                            class="absolute right-0 z-50 w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-lg ring-1 ring-black/5">

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
                @forelse($products as $p)
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
                                        x-transition.opacity
                                        class="absolute right-0 z-50 mt-1 w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-lg ring-1 ring-black/5">
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
        </div>
    @endif

    <!-- TABEL 2: DAFTAR LAYANAN JASA (SERVICE CATALOG) -->
    @if($type === 'SERVICE')
        <div class="table-shell">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Katalog Layanan Jasa</h2>
                    <p class="card-subtitle">{{ $services->count() }} layanan aktif dengan kalkulasi HPP bahan baku (BoM) otomatis.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-success">{{ $services->count() }} Layanan Terdaftar</span>
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
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            :class="isNearBottom ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                            class="absolute right-0 z-50 w-44 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-lg ring-1 ring-black/5">

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
                                <input type="radio" name="inventory_type" value="RETAIL" x-model="newProd.inventory_type" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Barang Etalase (Ritel)</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Dijual langsung di kasir POS (ATK, pulpen, buku, dll).</p>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                                :class="newProd.inventory_type === 'RAW_MATERIAL' ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <input type="radio" name="inventory_type" value="RAW_MATERIAL" x-model="newProd.inventory_type" class="mt-0.5 text-brand-600 focus:ring-brand-500">
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
                            <label for="prod_category_id" class="form-label flex items-center gap-1.5">
                                <span>Kategori</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="prod_category_id" name="category_id" class="select input w-full" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Satuan & SKU / Barcode -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="prod_unit_id" class="form-label flex items-center gap-1.5">
                                <span>Satuan</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="prod_unit_id" name="unit_id" class="select input w-full" required>
                                @foreach($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->code }} ({{ $u->name }})</option>
                                @endforeach
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
                                    <input type="number" id="prod_avg_cost" name="average_cost" step="1" min="0" required
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
                                    <input type="number" id="prod_selling_price" name="selling_price" step="1" min="0"
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
                            <input type="number" id="prod_min_alert" name="min_stock_alert" step="1" min="0"
                                x-model.number="newProd.min_stock_alert"
                                class="input w-full font-mono text-xs">
                            <p class="mt-1 text-[11px] text-slate-500">Sistem memberi notifikasi jika stok di bawah nilai ini.</p>
                        </div>

                        <div>
                            <label for="prod_initial_stock" class="form-label">Stok Awal Fisik (Opsional)</label>
                            <input type="number" id="prod_initial_stock" name="initial_stock" step="0.0001" min="0"
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
                                <input type="number" id="srv_price" name="base_price" step="1" min="0" required
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
                                            <input type="number" step="0.0001" min="0.0001"
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
                    <div>
                        <label class="form-label">Nama Produk</label>
                        <input type="text" name="name" x-model="editProd.name" required class="input w-full">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">HPP (Modal) Rp</label>
                            <input type="number" step="1" min="0" name="average_cost" x-model.number="editProd.average_cost" required
                                class="input w-full font-mono text-xs font-semibold">
                        </div>
                        <div>
                            <label class="form-label">Harga Jual Ritel Rp</label>
                            <input type="number" step="1" min="0" name="selling_price" x-model.number="editProd.selling_price" required
                                class="input w-full font-mono text-xs font-semibold text-brand-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Min Alert Stok</label>
                            <input type="number" step="1" min="0" name="min_stock_alert" x-model.number="editProd.min_stock_alert" required
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
                            <input type="number" step="1" min="0" name="base_price" x-model.number="editSrv.base_price" required
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
                                        <input type="number" step="0.0001" min="0.0001"
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
                            <input type="number" step="0.0001" min="0.0001" name="qty_purchased" x-model="qty" required
                                class="input font-mono w-full">
                        </div>
                        <div>
                            <label class="form-label">Harga Satuan (Rp)</label>
                            <input type="number" step="100" min="0" name="purchase_unit_price" x-model="price" required
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
</div>

<script>
function inventoryManager() {
    const rawMaterials = @js($rawMaterials);
    const productsData = @js($products);

    return {
        showRestockModal: false,
        selectedProductId: productsData[0]?.id || null,

        activeMenuId: null,
        isNearBottom: false,

        toggleMenu(id, event) {
            if (this.activeMenuId === id) {
                this.activeMenuId = null;
                return;
            }
            this.activeMenuId = id;
            if (event) {
                const rect = event.currentTarget.getBoundingClientRect();
                // Jika tombol berada di 200px terbawah dari layar, buka ke atas
                this.isNearBottom = (window.innerHeight - rect.bottom) < 200;
            }
        },

        closeMenu() {
            this.activeMenuId = null;
        },

        showAddProductModal: false,
        newProd: {
            name: '',
            sku: '',
            barcode: '',
            inventory_type: 'RETAIL',
            average_cost: 0,
            selling_price: 0,
            min_stock_alert: 5,
            initial_stock: 0
        },

        showEditProductModal: false,
        editProd: {
            id: null,
            name: '',
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
            this.newProd = {
                name: '',
                sku: '',
                barcode: '',
                inventory_type: 'RETAIL',
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
