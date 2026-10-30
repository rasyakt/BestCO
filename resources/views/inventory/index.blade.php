@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showRestockModal: false, selectedProductId: '', selectedProductName: '', selectedProductUnit: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Dual-Inventory & Restock</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pemisahan ketat stok barang etalase ritel dan persediaan bahan baku jasa dengan kalkulasi HPP Moving Average.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.mutations') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200">
                Mutasi Internal
            </a>
            <a href="{{ route('inventory.spoilage') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200">
                Log Spoilage
            </a>
            <button type="button" @click="showRestockModal = true; selectedProductId = '{{ $products->first()?->id }}'; selectedProductName = '{{ $products->first()?->name }}'; selectedProductUnit = '{{ $products->first()?->unit?->code }}'"
                class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs flex items-center gap-1.5">
                <span>+ Restock Pembelian</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-1.5">
            <span class="text-slate-500 font-medium mr-1">Tipe:</span>
            <a href="{{ route('inventory.index', ['type' => 'ALL', 'q' => $search]) }}"
                class="px-3 py-1.5 rounded font-semibold {{ $type === 'ALL' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
            </a>
            <a href="{{ route('inventory.index', ['type' => 'RETAIL', 'q' => $search]) }}"
                class="px-3 py-1.5 rounded font-semibold {{ $type === 'RETAIL' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                ATK Etalase (1-1301)
            </a>
            <a href="{{ route('inventory.index', ['type' => 'RAW_MATERIAL', 'q' => $search]) }}"
                class="px-3 py-1.5 rounded font-semibold {{ $type === 'RAW_MATERIAL' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Bahan Baku Jasa (1-1302)
            </a>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama produk / SKU..."
                class="px-3 py-1.5 rounded border border-slate-300 text-xs focus:outline-none focus:ring-1 focus:ring-brand-600">
            <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-semibold rounded hover:bg-slate-800">
                Cari
            </button>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">SKU</th>
                        <th class="py-2.5 px-3">Nama Produk / Bahan</th>
                        <th class="py-2.5 px-3">Domain Persediaan</th>
                        <th class="py-2.5 px-3 text-right">Stok Aktual</th>
                        <th class="py-2.5 px-3">Satuan</th>
                        <th class="py-2.5 px-3 text-right">HPP Rata-rata (Avg Cost)</th>
                        <th class="py-2.5 px-3 text-right">Harga Jual</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 font-mono font-medium text-slate-700">{{ $p->sku }}</td>
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-slate-900">{{ $p->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $p->category?->name }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded font-semibold text-[10px] {{ $p->inventory_type->value === 'RETAIL' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                    {{ $p->inventory_type->value === 'RETAIL' ? 'ATK Etalase' : 'Bahan Baku Jasa' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold num-tabular {{ (float) $p->current_stock <= (float) $p->min_stock ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $p->unit?->allow_decimal ? number_format((float) $p->current_stock, 4, ',', '.') : number_format((float) $p->current_stock, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 font-medium text-slate-600">{{ $p->unit?->code }}</td>
                            <td class="py-2.5 px-3 text-right num-tabular text-slate-800">
                                Rp {{ number_format((float) $p->average_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right num-tabular font-semibold text-slate-900">
                                {{ $p->selling_price ? 'Rp ' . number_format((float) $p->selling_price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                        @click="showRestockModal = true; selectedProductId = '{{ $p->id }}'; selectedProductName = '{{ $p->name }}'; selectedProductUnit = '{{ $p->unit?->code }}'"
                                        class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px]">
                                        + Restock
                                    </button>
                                    <a href="{{ route('inventory.ledger', $p->id) }}"
                                        class="px-2 py-1 rounded bg-brand-50 hover:bg-brand-100 text-brand-700 font-medium text-[11px]">
                                        Kartu Stok &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada produk ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL RESTOCK PEMBELIAN BARU -->
    <div x-show="showRestockModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-xl text-xs" @click.outside="showRestockModal = false">
            <div class="flex items-center justify-between border-b pb-2">
                <h3 class="font-bold text-sm text-slate-900">Form Pembelian & Restock Persediaan</h3>
                <button type="button" @click="showRestockModal = false" class="text-slate-400 text-lg">&times;</button>
            </div>

            <p class="text-[11px] text-slate-500">
                Pencatatan pembelian otomatis memperbarui nilai HPP Moving Average barang dan memposting Jurnal Pembelian (Dr. Persediaan / Cr. Kas Besar atau Utang).
            </p>

            <form method="POST" action="{{ route('inventory.restock') }}" class="space-y-3.5"
                x-data="{ qty: 1, price: 0, get total() { return (parseFloat(this.qty || 0) * parseFloat(this.price || 0)).toLocaleString('id-ID'); } }">
                @csrf

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Pilih Produk / Bahan Baku</label>
                    <select name="product_id" x-model="selectedProductId" class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white font-medium">
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">
                                [{{ $prod->inventory_type->value }}] {{ $prod->name }} (Stok saat ini: {{ (int) $prod->current_stock }} {{ $prod->unit?->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Kuantitas Pembelian</label>
                        <input type="number" step="0.0001" min="0.0001" name="qty_purchased" x-model="qty" required
                            class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Harga Beli Satuan (Rp)</label>
                        <input type="number" step="100" min="0" name="purchase_unit_price" x-model="price" required
                            placeholder="Contoh: 45000"
                            class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                    </div>
                </div>

                <!-- Total Invoice Display -->
                <div class="p-2.5 bg-slate-50 rounded border flex justify-between items-center text-xs">
                    <span class="text-slate-600 font-medium">Total Nilai Pembelian:</span>
                    <span class="font-bold text-sm text-slate-900 num-tabular">Rp <span x-text="total"></span></span>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Sumber Pendanaan / Pembayaran</label>
                    <select name="account_id" required class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white">
                        @foreach($fundingAccounts as $fa)
                            <option value="{{ $fa->id }}">{{ $fa->account_code }} &bull; {{ $fa->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="showRestockModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded font-bold shadow-xs">
                        Simpan Restock & Jurnal &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
