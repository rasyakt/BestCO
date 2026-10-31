@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showRestockModal: false, selectedProductId: @js($products->first()?->id) }">
    <x-page-header
        eyebrow="Dual-Inventory"
        title="Persediaan"
        subtitle="Kelola stok barang etalase dan bahan baku jasa secara terpisah, lengkap dengan nilai HPP moving average dan jejak audit setiap pergerakan.">
        <x-slot:actions>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Mutasi Internal
            </a>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Log Spoilage
            </a>
            <button type="button" @click="showRestockModal = true"
                class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Restock Pembelian
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter bar -->
    <div class="card flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-slate-400">Tipe Stok</span>
            <div class="segmented">
                <a href="{{ route('inventory.index', ['type' => 'ALL', 'q' => $search]) }}"
                    class="{{ $type === 'ALL' ? 'is-active' : '' }}">Semua</a>
                <a href="{{ route('inventory.index', ['type' => 'RETAIL', 'q' => $search]) }}"
                    class="{{ $type === 'RETAIL' ? 'is-active' : '' }}">ATK Etalase</a>
                <a href="{{ route('inventory.index', ['type' => 'RAW_MATERIAL', 'q' => $search]) }}"
                    class="{{ $type === 'RAW_MATERIAL' ? 'is-active' : '' }}">Bahan Baku Jasa</a>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="flex w-full items-center gap-2 lg:w-auto">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="search-field w-full lg:w-72">
                <x-icon name="search" />
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama produk atau SKU..."
                    class="input">
            </div>
            <button type="submit" class="btn btn-dark btn-sm">Cari</button>
        </form>
    </div>

    <!-- Inventory table -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Daftar Persediaan</h2>
                <p class="card-subtitle">{{ $products->count() }} produk aktif terdaftar</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-info"><span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span> Etalase</span>
                <span class="badge badge-purple"><span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span> Bahan Baku</span>
            </div>
        </div>

        <div class="table-wrap hidden md:block">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk / Bahan</th>
                        <th>Domain</th>
                        <th class="text-right">Stok Aktual</th>
                        <th class="text-right">HPP Rata-rata</th>
                        <th class="text-right">Harga Jual</th>
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
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isRetail ? 'bg-sky-50 text-sky-600' : 'bg-violet-50 text-violet-600' }}">
                                        <x-icon name="package" class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800">{{ $p->name }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $p->sku }} &bull; {{ $p->category?->name ?? 'Tanpa kategori' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $isRetail ? 'badge-info' : 'badge-purple' }}">
                                    {{ $isRetail ? 'ATK Etalase' : 'Bahan Baku Jasa' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <span class="font-extrabold num-tabular {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                    {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                                </span>
                                <span class="ml-1 text-[10px] font-semibold text-slate-400">{{ $p->unit?->code }}</span>
                                @if($isLow)
                                    <span class="mt-1 flex items-center justify-end gap-1 text-[10px] font-bold text-rose-500">
                                        <x-icon name="spoilage" class="h-3 w-3" /> Menipis
                                    </span>
                                @endif
                            </td>
                            <td class="text-right num-tabular text-slate-600">
                                Rp {{ number_format((float) $p->average_cost, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-bold text-slate-900 num-tabular">
                                {{ (float) $p->selling_price > 0 ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '—' }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                        @click="showRestockModal = true; selectedProductId = @js($p->id)"
                                        class="btn btn-sm border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        Restock
                                    </button>
                                    <a href="{{ route('inventory.ledger', $p->id) }}"
                                        class="btn btn-sm bg-brand-50 text-brand-700 hover:bg-brand-100">
                                        Kartu Stok
                                        <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="package" class="h-6 w-6" /></span>
                                    <p class="text-sm font-bold text-slate-700">Tidak ada produk ditemukan</p>
                                    <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau filter tipe persediaan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile product cards -->
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($products as $p)
                @php
                    $isLow = (float) $p->current_stock <= (float) ($p->min_stock_alert ?? 0);
                    $isRetail = $p->inventory_type->value === 'RETAIL';
                @endphp
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isRetail ? 'bg-sky-50 text-sky-600' : 'bg-violet-50 text-violet-600' }}">
                            <x-icon name="package" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-bold leading-snug text-slate-800">{{ $p->name }}</p>
                                <span class="badge shrink-0 {{ $isRetail ? 'badge-info' : 'badge-purple' }}">
                                    {{ $isRetail ? 'ATK' : 'Bahan Baku' }}
                                </span>
                            </div>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $p->sku }} &bull; {{ $p->category?->name ?? 'Tanpa kategori' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2 rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-slate-400">Stok</p>
                            <p class="mt-0.5 text-[12px] font-extrabold num-tabular {{ $isLow ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                                <span class="text-[9px] font-semibold text-slate-400">{{ $p->unit?->code }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-slate-400">HPP</p>
                            <p class="mt-0.5 text-[12px] font-bold text-slate-700 num-tabular">Rp {{ number_format((float) $p->average_cost, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-slate-400">Harga Jual</p>
                            <p class="mt-0.5 text-[12px] font-bold text-slate-900 num-tabular">
                                {{ (float) $p->selling_price > 0 ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '—' }}
                            </p>
                        </div>
                    </div>

                    @if($isLow)
                        <p class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-rose-500">
                            <x-icon name="spoilage" class="h-3 w-3" /> Stok menipis, segera restock
                        </p>
                    @endif

                    <div class="mt-3 flex items-center gap-2">
                        <button type="button"
                            @click="showRestockModal = true; selectedProductId = @js($p->id)"
                            class="btn btn-secondary btn-sm flex-1">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Restock
                        </button>
                        <a href="{{ route('inventory.ledger', $p->id) }}"
                            class="btn btn-sm flex-1 bg-brand-50 text-brand-700 hover:bg-brand-100">
                            Kartu Stok
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="empty-state-icon"><x-icon name="package" class="h-6 w-6" /></span>
                    <p class="text-sm font-bold text-slate-700">Tidak ada produk ditemukan</p>
                    <p class="mt-1 text-xs text-slate-400">Ubah kata kunci atau filter tipe persediaan.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Restock modal -->
    <div x-show="showRestockModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-lg" @click.outside="showRestockModal = false">
            <div class="modal-header">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <x-icon name="package" class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Restock Pembelian</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">Perbarui stok dan nilai HPP moving average</p>
                    </div>
                </div>
                <button type="button" @click="showRestockModal = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" action="{{ route('inventory.restock') }}"
                x-data="{ qty: 1, price: 0, get total() { return (parseFloat(this.qty || 0) * parseFloat(this.price || 0)).toLocaleString('id-ID'); } }">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Pembelian otomatis memperbarui HPP moving average dan memposting jurnal <strong>Dr. Persediaan / Cr. Kas Besar atau Utang</strong>.</span>
                    </div>

                    <div>
                        <label class="form-label">Produk / Bahan Baku</label>
                        <select name="product_id" x-model="selectedProductId" class="select">
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">
                                    [{{ $prod->inventory_type->value }}] {{ $prod->name }} &mdash; stok {{ (int) $prod->current_stock }} {{ $prod->unit?->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Kuantitas Pembelian</label>
                            <input type="number" step="0.0001" min="0.0001" name="qty_purchased" x-model="qty" required
                                class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Harga Beli Satuan (Rp)</label>
                            <input type="number" step="100" min="0" name="purchase_unit_price" x-model="price" required
                                placeholder="Contoh: 45000"
                                class="input font-mono">
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-brand-100 bg-brand-50/70 px-4 py-3">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-brand-600">Total Nilai Pembelian</p>
                            <p class="mt-0.5 text-lg font-extrabold text-brand-900 num-tabular">Rp <span x-text="total"></span></p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm">
                            <x-icon name="cash" class="h-5 w-5" />
                        </span>
                    </div>

                    <div>
                        <label class="form-label">Sumber Pendanaan / Pembayaran</label>
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
                        Simpan Restock
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
