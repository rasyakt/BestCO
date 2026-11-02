@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="inboundManager()">
    <x-page-header
        title="Barang Masuk"
        subtitle="Pengadaan dan restock multi-produk secara sekaligus dalam satu transaksi pengadaan.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Barang Masuk</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                <x-icon name="package" class="h-4 w-4" />
                Lihat Stok Persediaan
            </a>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah Stok
            </a>
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
                <p class="font-semibold">Terjadi kesalahan pada data input:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
        <!-- Formulir Input Barang Masuk Batch -->
        <form method="POST" action="{{ route('inventory.inbound.store') }}" @submit="handleSubmit($event)">
            @csrf
            <div class="card overflow-hidden">
                <!-- Header Pengadaan -->
                <div class="border-b border-slate-100 bg-slate-50/60 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="account_id" class="form-label flex items-center gap-1.5">
                                <span>Sumber Dana Pembelian</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="account_id" name="account_id" class="select input w-full" required>
                                @foreach($fundingAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Kas di Tangan</p>
                        </div>

                        <div>
                            <label for="invoice_number" class="form-label">No. Faktur / Surat Jalan</label>
                            <input type="text" id="invoice_number" name="invoice_number"
                                value="{{ old('invoice_number') }}"
                                placeholder="Contoh: INV-{{ now()->format('Ymd') }}-01"
                                class="input w-full">
                            <p class="mt-1 text-[11px] text-slate-500">Opsional, nomor referensi dari pemasok</p>
                        </div>

                        <div>
                            <label for="notes" class="form-label">Catatan / Nama Vendor</label>
                            <input type="text" id="notes" name="notes"
                                value="{{ old('notes') }}"
                                placeholder="Contoh: Pembelian ATK dari Toko Sahabat"
                                class="input w-full">
                            <p class="mt-1 text-[11px] text-slate-500">Opsional, keterangan pengadaan</p>
                        </div>

                        <div>
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="text" readonly
                                value="{{ now()->translatedFormat('l, d F Y') }}"
                                class="input w-full bg-slate-100 text-slate-500 cursor-not-allowed">
                            <p class="mt-1 text-[11px] text-slate-500">Dicatat sesuai waktu transaksi sistem</p>
                        </div>
                    </div>
                </div>

                <!-- Tabel Item Produk -->
                <div class="p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Daftar Barang yang Masuk</h3>
                            <p class="text-xs text-slate-500">Pilih produk, masukkan jumlah kuantitas dan harga beli satuan.</p>
                        </div>
                        <button type="button" @click="addRow()" class="btn btn-secondary btn-sm">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Tambah Baris
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full min-w-[760px] text-left text-xs">
                            <thead class="border-b border-slate-200 bg-slate-50 font-semibold text-slate-700">
                                <tr>
                                    <th class="w-12 px-3 py-2.5 text-center">#</th>
                                    <th class="px-3 py-2.5">Produk / Barang</th>
                                    <th class="w-28 px-3 py-2.5 text-center">Stok Kini</th>
                                    <th class="w-32 px-3 py-2.5">Jumlah Masuk</th>
                                    <th class="w-24 px-3 py-2.5">Satuan</th>
                                    <th class="w-44 px-3 py-2.5">Harga Satuan (Rp)</th>
                                    <th class="w-44 px-3 py-2.5 text-right">Subtotal (Rp)</th>
                                    <th class="w-12 px-3 py-2.5 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(row, index) in rows" :key="index">
                                    <tr class="hover:bg-slate-50/50">
                                        <!-- Index -->
                                        <td class="px-3 py-2 text-center font-medium text-slate-400" x-text="index + 1"></td>

                                        <!-- Product Dropdown -->
                                        <td class="px-3 py-2">
                                            <input type="hidden" :name="'items[' + index + '][product_id]'" :value="row.product_id">
                                            <select class="select input w-full py-1.5 text-xs"
                                                x-model="row.product_id"
                                                @change="onProductChange(index)"
                                                required>
                                                <option value="" disabled>-- Pilih Produk --</option>
                                                <template x-for="p in products" :key="p.id">
                                                    <option :value="p.id"
                                                        x-text="p.name + ' (' + (p.sku || '-') + ') - ' + (p.inventory_type === 'RETAIL' ? 'Etalase' : 'Bahan Baku')">
                                                    </option>
                                                </template>
                                            </select>
                                        </td>

                                        <!-- Current Stock -->
                                        <td class="px-3 py-2 text-center">
                                            <span class="badge badge-info text-[11px]" x-text="getCurrentStockText(row.product_id)"></span>
                                        </td>

                                        <!-- Quantity -->
                                        <td class="px-3 py-2">
                                            <input type="text" inputmode="decimal"
                                                data-quantity="true" step="0.0001"
                                                :name="'items[' + index + '][qty]'"
                                                x-model.number="row.qty"
                                                class="input w-full py-1.5 text-right text-xs font-mono font-medium"
                                                placeholder="1"
                                                required>
                                        </td>

                                        <!-- Unit -->
                                        <td class="px-3 py-2 font-mono text-[11px] font-semibold text-slate-600"
                                            x-text="getUnitCode(row.product_id)">
                                        </td>

                                        <!-- Unit Purchase Price -->
                                        <td class="px-3 py-2">
                                            <div class="relative">
                                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-400">Rp</span>
                                                <input type="text" inputmode="numeric"
                                                    data-currency="true"
                                                    :name="'items[' + index + '][price]'"
                                                    x-model.number="row.price"
                                                    class="input w-full py-1.5 pl-8 text-right text-xs font-mono font-medium"
                                                    placeholder="0"
                                                    required>
                                            </div>
                                        </td>

                                        <!-- Line Subtotal -->
                                        <td class="px-3 py-2 text-right font-mono text-xs font-bold text-slate-900">
                                            Rp <span x-text="formatRp(getSubtotal(row))"></span>
                                        </td>

                                        <!-- Remove Row Action -->
                                        <td class="px-2 py-2 text-center">
                                            <button type="button"
                                                @click="removeRow(index)"
                                                :disabled="rows.length <= 1"
                                                title="Hapus baris"
                                                class="rounded p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-slate-400">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Bar Inside Card -->
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                        <button type="button" @click="addRow()" class="btn btn-secondary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah Produk Lain
                        </button>

                        <div class="flex flex-wrap items-center gap-6">
                            <div class="text-right">
                                <p class="text-[11px] font-medium text-slate-500">Total Kuantitas</p>
                                <p class="text-sm font-bold text-slate-800">
                                    <span x-text="getTotalQty().toLocaleString('id-ID', { maximumFractionDigits: 2 })"></span>
                                    <span class="text-xs font-normal text-slate-400">unit</span>
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-[11px] font-medium text-slate-500">Total Nilai Pengadaan</p>
                                <p class="text-lg font-extrabold text-brand-700">
                                    Rp <span x-text="formatRp(getGrandTotal())"></span>
                                </p>
                            </div>

                            <button type="submit" class="btn btn-primary px-6 py-2.5 shadow-sm">
                                <x-icon name="download" class="h-4 w-4" />
                                Simpan & Masukkan Stok
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="card p-5 text-center">
            <x-icon name="info" class="mx-auto h-8 w-8 text-slate-400" />
            <h3 class="mt-2 text-sm font-semibold text-slate-800">Akses Terbatas</h3>
            <p class="mt-1 text-xs text-slate-500">Hanya Superadmin dan Admin yang memiliki otorisasi untuk melakukan input pengadaan barang masuk.</p>
        </div>
    @endif

    <!-- Riwayat Pengadaan / Transaksi Pembelian -->
    <div class="table-shell mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat Pengadaan Barang Masuk</h2>
                <p class="card-subtitle">Semua transaksi pengadaan barang masuk tercatat otomatis ke jurnal akuntansi dan kartu stok.</p>
            </div>
            <span class="badge badge-info">{{ $recentPurchases->total() }} Transaksi Tercatat</span>
        </div>

        <div class="w-full">
            <table class="data-table w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">No. Jurnal</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Keterangan / Faktur</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Sumber Dana</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5 w-px">Total Nilai</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Dicatat Oleh</th>
                        <th class="text-center whitespace-nowrap px-3 py-2.5 w-px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPurchases as $purchase)
                        @php
                            $creditItem = $purchase->items->where('credit', '>', 0)->first();
                            $fundingCoa = $creditItem?->chartOfAccount;
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-mono text-xs font-semibold text-slate-900 whitespace-nowrap block">{{ $purchase->journal_number }}</span>
                                <span class="mt-0.5 block font-mono text-[10px] text-slate-400 whitespace-nowrap">{{ $purchase->entry_date->format('d/m/Y') }}</span>
                            </td>
                            <td class="max-w-xs whitespace-nowrap px-3 py-2.5">
                                <span class="font-medium text-xs text-slate-800 line-clamp-1 block whitespace-nowrap" title="{{ $purchase->description }}">{{ $purchase->description }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400 whitespace-nowrap">Ref: {{ $purchase->reference_type ?? 'PURCHASE' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                @if($fundingCoa)
                                    <span class="badge whitespace-nowrap text-[11px] {{ str_starts_with($fundingCoa->account_code, '1-') ? 'badge-success' : 'badge-warning' }}">
                                        {{ $fundingCoa->account_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold text-xs text-slate-900 num-tabular whitespace-nowrap px-3 py-2.5">
                                Rp {{ number_format((float) $purchase->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="text-xs text-slate-600 whitespace-nowrap px-3 py-2.5">
                                {{ $purchase->createdBy?->name ?? 'Sistem' }}
                            </td>
                            <td class="text-center whitespace-nowrap px-3 py-2.5">
                                <span class="badge badge-success whitespace-nowrap text-[10px]">Selesai</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                Belum ada riwayat pengadaan barang masuk yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentPurchases->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $recentPurchases->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function inboundManager() {
    const productsData = @js($products);

    return {
        products: productsData,
        rows: [
            {
                product_id: productsData[0]?.id || '',
                qty: 1,
                price: productsData[0] ? (parseFloat(productsData[0].last_purchase_price) || parseFloat(productsData[0].average_cost) || 0) : 0
            }
        ],
        addRow() {
            // Pilih produk yang belum ada di daftar jika memungkinkan
            const usedIds = this.rows.map(r => Number(r.product_id));
            const available = this.products.find(p => !usedIds.includes(Number(p.id))) || this.products[0];

            this.rows.push({
                product_id: available ? available.id : '',
                qty: 1,
                price: available ? (parseFloat(available.last_purchase_price) || parseFloat(available.average_cost) || 0) : 0
            });
        },
        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
            }
        },
        onProductChange(index) {
            const row = this.rows[index];
            const p = this.products.find(item => item.id == row.product_id);
            if (p) {
                row.price = parseFloat(p.last_purchase_price) || parseFloat(p.average_cost) || 0;
            }
        },
        getProduct(id) {
            return this.products.find(p => p.id == id) || null;
        },
        getCurrentStockText(id) {
            const p = this.getProduct(id);
            if (!p) return '-';
            const stock = parseFloat(p.current_stock) || 0;
            return stock.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' ' + (p.unit ? p.unit.code : '');
        },
        getUnitCode(id) {
            const p = this.getProduct(id);
            return p && p.unit ? p.unit.code : 'UNIT';
        },
        getSubtotal(row) {
            const q = parseFloat(row.qty) || 0;
            const p = parseFloat(row.price) || 0;
            return q * p;
        },
        getTotalQty() {
            return this.rows.reduce((sum, r) => sum + (parseFloat(r.qty) || 0), 0);
        },
        getGrandTotal() {
            return this.rows.reduce((sum, r) => sum + this.getSubtotal(r), 0);
        },
        formatRp(val) {
            return new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
        },
        handleSubmit(event) {
            if (this.rows.length === 0) {
                event.preventDefault();
                window.bestcoDialog.alert({
                    title: 'Daftar Barang Kosong',
                    message: 'Silakan tambahkan minimal satu produk untuk dicatat pada pengadaan barang masuk.',
                    type: 'warning'
                });
                return;
            }
            for (let i = 0; i < this.rows.length; i++) {
                if (!this.rows[i].product_id) {
                    event.preventDefault();
                    window.bestcoDialog.alert({
                        title: 'Produk Belum Dipilih',
                        message: 'Pastikan semua produk telah dipilih pada baris ke-' + (i + 1) + '.',
                        type: 'warning'
                    });
                    return;
                }
                if (parseFloat(this.rows[i].qty) <= 0) {
                    event.preventDefault();
                    window.bestcoDialog.alert({
                        title: 'Kuantitas Tidak Valid',
                        message: 'Kuantitas pada baris ke-' + (i + 1) + ' harus lebih besar dari 0.',
                        type: 'warning'
                    });
                    return;
                }
            }
        }
    };
}
</script>
@endsection

