@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="opnameManager()">
    <x-page-header
        title="Buat Stok Opname"
        subtitle="Masukkan hasil hitung fisik. Selisih dihitung otomatis dan stok baru berubah setelah disetujui admin.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.opname') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Stok Opname
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Buat Baru</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.opname') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50/90 p-4 text-rose-900 shadow-xs">
            <x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <div class="flex-1 text-xs">
                <p class="font-semibold">Periksa kembali data opname:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('inventory.opname.store') }}" @submit="submitForm($event)">
        @csrf

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/60 p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div>
                        <label for="opname_date" class="form-label">Tanggal Opname <span class="text-rose-500">*</span></label>
                        <x-date-picker
                            name="opname_date"
                            :value="old('opname_date', now()->format('Y-m-d'))"
                            :max="now()->format('Y-m-d')"
                        />
                        <p class="mt-1 text-[11px] text-slate-500">Tanggal pelaksanaan hitung fisik, maksimal hari ini</p>
                        @error('opname_date')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label for="notes" class="form-label">Catatan Sesi</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes') }}"
                            placeholder="Contoh: Opname akhir bulan periode November"
                            class="input w-full">
                        <p class="mt-1 text-[11px] text-slate-500">Opsional, menjelaskan tujuan atau kondisi penghitungan</p>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                        <div class="flex-1">
                            <label for="product_search" class="form-label">Cari Barang</label>
                            <div class="search-field">
                                <x-icon name="search" />
                                <input type="text" id="product_search" x-model="search"
                                    placeholder="Cari nama, SKU, atau kategori..." class="input">
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <label for="type_filter" class="form-label">Tipe</label>
                            <select id="type_filter" x-model="typeFilter" class="select input">
                                <option value="ALL">Semua tipe</option>
                                <option value="RETAIL">Barang Etalase</option>
                                <option value="RAW_MATERIAL">Bahan Baku Jasa</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="fillSystem()" class="btn btn-secondary btn-sm">
                            <x-icon name="check-circle" class="h-3.5 w-3.5" />
                            Isi Sesuai Stok Sistem
                        </button>
                        <button type="button" @click="clearAll()" class="btn btn-ghost btn-sm">
                            <x-icon name="refresh" class="h-3.5 w-3.5" />
                            Kosongkan
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full min-w-[900px] text-left text-xs">
                        <thead class="border-b border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <tr>
                                <th class="w-12 px-3 py-2.5 text-center">#</th>
                                <th class="px-3 py-2.5">Produk / Barang</th>
                                <th class="w-28 px-3 py-2.5 text-center">Stok Sistem</th>
                                <th class="w-36 px-3 py-2.5">Qty Fisik</th>
                                <th class="w-28 px-3 py-2.5 text-center">Selisih</th>
                                <th class="w-40 px-3 py-2.5 text-right">Nilai Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(product, index) in products" :key="product.id">
                                <tr class="hover:bg-slate-50/50" x-show="matchesFilter(product)">
                                    <td class="px-3 py-2 text-center font-medium text-slate-400" x-text="index + 1"></td>

                                    <td class="px-3 py-2">
                                        <input type="hidden" :name="'items[' + index + '][product_id]'" :value="product.id">
                                        <span class="block font-semibold text-xs text-slate-800" x-text="product.name"></span>
                                        <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400">
                                            <span class="font-mono" x-text="product.sku || '-'"></span>
                                            <span>&bull;</span>
                                            <span x-text="product.category"></span>
                                            <span class="chip text-[9px] py-0 px-1" x-text="product.type === 'RETAIL' ? 'Etalase' : 'Bahan Baku'"></span>
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-center whitespace-nowrap">
                                        <span class="font-mono text-xs font-semibold text-slate-700" x-text="formatQty(product.stock)"></span>
                                        <span class="ml-1 text-[10px] text-slate-400" x-text="product.unit"></span>
                                    </td>

                                    <td class="px-3 py-2">
                                        <input type="text" inputmode="decimal" data-quantity="true" step="0.0001"
                                            :name="'items[' + index + '][counted_qty]'"
                                            x-model="counts[product.id]"
                                            class="input w-full py-1.5 text-right text-xs font-mono font-medium"
                                            placeholder="0">
                                        <p class="mt-0.5 text-right text-[10px] text-slate-400" x-text="product.unit"></p>
                                    </td>

                                    <td class="px-3 py-2 text-center whitespace-nowrap">
                                        <span x-show="isFilled(product)" x-cloak
                                            class="font-mono text-xs font-bold"
                                            :class="differenceClass(product)"
                                            x-text="formatSignedQty(difference(product))"></span>
                                        <span x-show="!isFilled(product)" class="text-[10px] text-slate-400">—</span>
                                    </td>

                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <span x-show="isFilled(product)" x-cloak
                                            class="font-mono text-xs font-semibold"
                                            :class="differenceClass(product)"
                                            x-text="formatSignedRp(differenceValue(product))"></span>
                                        <span x-show="!isFilled(product)" class="text-[10px] text-slate-400">—</span>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="visibleCount === 0">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                                    Tidak ada barang yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap items-center gap-6">
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Item dihitung</p>
                            <p class="text-sm font-bold text-slate-800">
                                <span x-text="filledCount"></span>
                                <span class="text-xs font-normal text-slate-400">barang</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Nilai selisih kurang</p>
                            <p class="text-sm font-bold text-rose-600">
                                Rp <span x-text="formatRp(totalShortage)"></span>
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Nilai selisih lebih</p>
                            <p class="text-sm font-bold text-emerald-600">
                                Rp <span x-text="formatRp(totalOverage)"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('inventory.opname') }}" class="btn btn-ghost">Batal</a>
                        <button type="submit" class="btn btn-primary px-6 py-2.5 shadow-sm">
                            <x-icon name="check" class="h-4 w-4" />
                            Simpan Draf Opname
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2.5 text-[11px] text-amber-800">
                    <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <p>Menyimpan draf belum mengubah stok. Penyesuaian stok dan jurnal baru dibuat setelah sesi disetujui oleh Superadmin atau Admin.</p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function opnameManager() {
    const products = @js($productRows);

    return {
        products,
        counts: Object.fromEntries(products.map((product) => [String(product.id), ''])),
        search: '',
        typeFilter: 'ALL',

        get filteredProducts() {
            return this.products.filter((product) => this.matchesFilter(product));
        },

        get visibleCount() {
            return this.filteredProducts.length;
        },

        matchesFilter(product) {
            const keyword = this.search.trim().toLowerCase();
            const matchesType = this.typeFilter === 'ALL' || product.type === this.typeFilter;
            const haystack = [product.name, product.sku, product.category]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return matchesType && (keyword === '' || haystack.includes(keyword));
        },

        get filledRows() {
            return this.products.filter((product) => this.isFilled(product));
        },

        get filledCount() {
            return this.filledRows.length;
        },

        get totalShortage() {
            return this.products.reduce((total, product) => {
                const value = this.differenceValue(product);

                return this.isFilled(product) && value < 0 ? total + Math.abs(value) : total;
            }, 0);
        },

        get totalOverage() {
            return this.products.reduce((total, product) => {
                const value = this.differenceValue(product);

                return this.isFilled(product) && value > 0 ? total + value : total;
            }, 0);
        },

        rawValue(product) {
            return (this.counts[product.id] ?? '').toString().trim();
        },

        isFilled(product) {
            return this.rawValue(product) !== '';
        },

        countedQty(product) {
            const value = Number.parseFloat(this.rawValue(product).replace(',', '.'));

            return Number.isFinite(value) ? value : null;
        },

        difference(product) {
            const counted = this.countedQty(product);

            return counted === null ? 0 : counted - Number(product.stock || 0);
        },

        differenceValue(product) {
            return this.difference(product) * Number(product.cost || 0);
        },

        differenceClass(product) {
            const difference = this.difference(product);

            if (difference < 0) {
                return 'text-rose-600';
            }

            if (difference > 0) {
                return 'text-emerald-600';
            }

            return 'text-slate-500';
        },

        fillSystem() {
            this.products.forEach((product) => {
                this.counts[product.id] = this.formatQtyInput(product.stock);
            });
        },

        clearAll() {
            this.products.forEach((product) => {
                this.counts[product.id] = '';
            });
        },

        formatQty(value) {
            return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 });
        },

        formatQtyInput(value) {
            const fixed = Number(value || 0).toFixed(4);

            return fixed.replace(/\.?0+$/, '') || '0';
        },

        formatSignedQty(value) {
            const prefix = value > 0 ? '+' : '';

            return prefix + this.formatQty(value);
        },

        formatRp(value) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Math.abs(value || 0)));
        },

        formatSignedRp(value) {
            const prefix = value > 0 ? '+' : value < 0 ? '-' : '';

            return prefix + 'Rp ' + this.formatRp(value);
        },

        submitForm(event) {
            if (this.filledCount === 0) {
                event.preventDefault();

                if (window.bestcoDialog?.alert) {
                    window.bestcoDialog.alert({
                        title: 'Belum Ada Hasil Hitung',
                        message: 'Isi minimal satu kuantitas fisik barang sebelum menyimpan draf opname.',
                        type: 'warning'
                    });
                }

                return;
            }

            const invalidProduct = this.filledRows.find((product) => {
                const raw = this.rawValue(product);

                return !/^\d{1,9}(\.\d{1,4})?$/.test(raw);
            });

            if (invalidProduct) {
                event.preventDefault();

                if (window.bestcoDialog?.alert) {
                    window.bestcoDialog.alert({
                        title: 'Kuantitas Tidak Valid',
                        message: 'Kuantitas fisik untuk ' + invalidProduct.name + ' harus berupa angka 0 atau lebih dengan maksimal 4 desimal.',
                        type: 'warning'
                    });
                }
            }
        }
    };
}
</script>
@endsection
