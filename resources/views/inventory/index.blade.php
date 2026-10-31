@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showRestockModal: false, selectedProductId: @js($products->first()?->id) }">
    <x-page-header
        title="Persediaan"
        subtitle="Stok barang etalase dan bahan baku jasa.">
        <x-slot:actions>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah stok
            </a>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Barang rusak
            </a>
            <button type="button" @click="showRestockModal = true" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah stok
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter -->
    <div class="card flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[11px] font-medium text-slate-500">Jenis stok</span>
            <div class="segmented">
                <a href="{{ route('inventory.index', ['type' => 'ALL', 'q' => $search]) }}"
                    class="{{ $type === 'ALL' ? 'is-active' : '' }}">Semua</a>
                <a href="{{ route('inventory.index', ['type' => 'RETAIL', 'q' => $search]) }}"
                    class="{{ $type === 'RETAIL' ? 'is-active' : '' }}">Barang etalase</a>
                <a href="{{ route('inventory.index', ['type' => 'RAW_MATERIAL', 'q' => $search]) }}"
                    class="{{ $type === 'RAW_MATERIAL' ? 'is-active' : '' }}">Bahan baku</a>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="flex w-full items-center gap-2 lg:w-auto">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="search-field w-full lg:w-72">
                <x-icon name="search" />
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau kode barang..."
                    class="input">
            </div>
            <button type="submit" class="btn btn-dark btn-sm">Cari</button>
        </form>
    </div>

    <!-- Tabel -->
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

        <div class="table-wrap hidden md:block">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Barang / bahan</th>
                        <th>Jenis</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">Harga jual</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $isLow = (float) $p->current_stock <= (float) ($p->min_stock_alert ?? 0);
                            $isRetail = $p->inventory_type->value === 'RETAIL';
                        @endphp
                        <tr>
                            <td>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800">{{ $p->name }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $p->sku }} &bull; {{ $p->category?->name ?? 'Tanpa kategori' }}</p>
                                </div>
                            </td>
                            <td class="text-[11px] text-slate-500">{{ $isRetail ? 'Barang etalase' : 'Bahan baku' }}</td>
                            <td class="text-right">
                                <span class="font-semibold num-tabular {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                    {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                                </span>
                                <span class="ml-1 text-[10px] text-slate-400">{{ $p->unit?->code }}</span>
                                @if($isLow)
                                    <span class="mt-1 block text-[10px] font-medium text-rose-500">Menipis</span>
                                @endif
                            </td>
                            <td class="text-right text-slate-600 num-tabular">
                                Rp {{ number_format((float) $p->average_cost, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-semibold text-slate-900 num-tabular">
                                {{ (float) $p->selling_price > 0 ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '—' }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                        @click="showRestockModal = true; selectedProductId = @js($p->id)"
                                        class="btn btn-sm border border-slate-300 bg-white text-slate-600 hover:bg-slate-50">
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        Tambah
                                    </button>
                                    <a href="{{ route('inventory.ledger', $p->id) }}"
                                        class="btn btn-sm bg-brand-50 text-brand-700 hover:bg-brand-100">
                                        Kartu stok
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="package" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Barang tidak ditemukan</p>
                                    <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau jenis stok.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Kartu mobile -->
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
                        @if($isLow)
                            <span class="shrink-0 text-[10px] font-medium text-rose-600">Menipis</span>
                        @endif
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
                            <p class="text-[10px] text-slate-400">Harga jual</p>
                            <p class="mt-0.5 text-[12px] font-medium text-slate-900 num-tabular">
                                {{ (float) $p->selling_price > 0 ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button type="button"
                            @click="showRestockModal = true; selectedProductId = @js($p->id)"
                            class="btn btn-secondary btn-sm flex-1">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Tambah stok
                        </button>
                        <a href="{{ route('inventory.ledger', $p->id) }}"
                            class="btn btn-sm flex-1 bg-brand-50 text-brand-700 hover:bg-brand-100">
                            Kartu stok
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="empty-state-icon"><x-icon name="package" class="h-5 w-5" /></span>
                    <p class="text-sm font-semibold text-slate-700">Barang tidak ditemukan</p>
                    <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau jenis stok.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal tambah stok -->
    <div x-show="showRestockModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-lg" @click.outside="showRestockModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Tambah stok</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perbarui stok dan HPP rata-rata</p>
                </div>
                <button type="button" @click="showRestockModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('inventory.restock') }}"
                x-data="{ qty: 1, price: 0, get total() { return (parseFloat(this.qty || 0) * parseFloat(this.price || 0)).toLocaleString('id-ID'); } }">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Stok dan HPP otomatis diperbarui. Pembelian dicatat sebagai kas keluar atau utang.</span>
                    </div>

                    <div>
                        <label class="form-label">Barang / bahan</label>
                        <select name="product_id" x-model="selectedProductId" class="select">
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
                                class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Harga satuan (Rp)</label>
                            <input type="number" step="100" min="0" name="purchase_unit_price" x-model="price" required
                                placeholder="Contoh: 45000"
                                class="input font-mono">
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-brand-100 bg-brand-50 px-4 py-3">
                        <div>
                            <p class="text-[11px] font-medium text-brand-700">Total pembelian</p>
                            <p class="mt-0.5 text-lg font-semibold text-brand-900 num-tabular">Rp <span x-text="total"></span></p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Sumber dana</label>
                        <select name="account_id" required class="select">
                            @foreach($fundingAccounts as $fa)
                                <option value="{{ $fa->id }}">{{ $fa->account_code }} &bull; {{ $fa->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showRestockModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
