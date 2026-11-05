@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    showVoidModal: false,
    voidTxId: '',
    voidTxInvoice: '',
    showReceiptModal: false,
    activeReceipt: null,
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
    formatRp(num) {
        return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    },
    openReceipt(data) {
        this.activeReceipt = data;
        this.showReceiptModal = true;
    },
    printReceipt() {
        window.print();
    }
}" @keydown.escape.window="closeMenu()" @scroll.window="closeMenu()" @resize.window="closeMenu()">
    <x-page-header
        title="Riwayat Transaksi"
        subtitle="Cari transaksi, cetak ulang struk, atau batalkan transaksi.">
        <x-slot:actions>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Transaksi baru
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter Bar -->
    <div class="card p-4">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Cari</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Cari nomor invoice atau nama pelanggan..."
                        class="input">
                </div>
            </div>

            <div class="w-full lg:w-44">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="select">
                    <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>Semua status</option>
                    <option value="COMPLETED" {{ $status === 'COMPLETED' ? 'selected' : '' }}>Selesai</option>
                    <option value="VOIDED" {{ $status === 'VOIDED' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div class="w-full lg:w-44">
                <label for="date" class="form-label">Tanggal</label>
                <input type="date" id="date" name="date" value="{{ $date }}" class="input">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Tampilkan</button>
                <a href="{{ route('transactions.index', ['date' => '']) }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel Data Transaksi Konsisten -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Daftar Riwayat Transaksi</h2>
                <p class="card-subtitle">Menampilkan {{ $transactions->count() }} dari total {{ $transactions->total() }} transaksi tercatat.</p>
            </div>
            @if($transactions->total() > 0)
                <div class="flex items-center gap-2">
                    <span class="badge badge-success text-[11px] font-medium">{{ $transactions->total() }} Transaksi</span>
                </div>
            @endif
        </div>

        <div class="w-full overflow-x-auto" @scroll.passive="closeMenu()">
            <table class="data-table w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left whitespace-nowrap px-4 py-3">No. Bukti / Invoice</th>
                        <th class="text-left whitespace-nowrap px-4 py-3">Pelanggan & Shift</th>
                        <th class="text-left whitespace-nowrap px-4 py-3">Item Terjual</th>
                        <th class="text-left whitespace-nowrap px-4 py-3">Pembayaran & Kasir</th>
                        <th class="text-right whitespace-nowrap px-4 py-3">Total Nilai</th>
                        <th class="text-center whitespace-nowrap px-4 py-3">Status</th>
                        <th class="text-right whitespace-nowrap px-4 py-3 w-[56px]">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $itemCount = $tx->items->count();
                            $totalQty = (int) $tx->items->sum('qty');
                            
                            $cleanNames = $tx->items->map(function ($it) {
                                $name = trim(preg_replace('/\[.*?\]/', '', $it->item_name_snapshot));
                                $qty = (int) $it->qty;
                                return "{$name} ({$qty})";
                            })->toArray();
                            
                            $fullSummary = implode(', ', $cleanNames);
                            
                            $displaySummary = implode(', ', array_slice($cleanNames, 0, 2));
                            if ($itemCount > 2) {
                                $displaySummary .= ' +' . ($itemCount - 2) . ' lainnya';
                            }

                            $txReceiptData = [
                                'id' => $tx->id,
                                'invoice' => $tx->invoice_number,
                                'date' => $tx->transaction_date->format('d/m/Y H:i'),
                                'cashier' => $tx->cashier->name,
                                'customer' => $tx->customer_name,
                                'shift' => $tx->posShift?->shift_number ?? '-',
                                'payment_method' => $tx->payment_method->value === 'CASH' ? 'Tunai' : 'QRIS',
                                'status' => $tx->status,
                                'voided_by' => $tx->voidedByTeacher?->name ?? '',
                                'void_reason' => $tx->void_reason ?? '',
                                'subtotal_gross' => (float) ($tx->subtotal_retail + $tx->subtotal_service),
                                'tier_discount' => (float) $tx->tier_discount_total,
                                'promo_discount' => (float) $tx->promo_discount_total,
                                'promo_name' => $tx->promo_name_snapshot ?? '',
                                'manual_discount' => (float) $tx->manual_discount_total,
                                'total_discount' => (float) ($tx->tier_discount_total + $tx->manual_discount_total + $tx->promo_discount_total),
                                'grand_total' => (float) $tx->grand_total,
                                'amount_paid' => (float) $tx->amount_paid,
                                'change_amount' => (float) $tx->change_amount,
                                'items' => $tx->items->map(function ($it) {
                                    return [
                                        'name' => $it->item_name_snapshot,
                                        'qty' => (int) $it->qty,
                                        'unit_price' => (float) $it->base_unit_price,
                                        'subtotal_gross' => (float) $it->line_subtotal_gross,
                                        'discount' => (float) $it->line_total_discount,
                                        'net_total' => (float) $it->line_net_total,
                                    ];
                                })->values()->all(),
                            ];
                        @endphp
                        <tr :class="activeMenuId === 'tx-{{ $tx->id }}' ? 'relative z-30 bg-slate-50/80 dark:bg-slate-800/80' : ''">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="font-mono text-xs font-bold text-slate-800">{{ $tx->invoice_number }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $tx->transaction_date->format('d/m/Y H:i') }} WIB</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="text-xs font-semibold text-slate-800 truncate max-w-[140px]" title="{{ $tx->customer_name }}">
                                    {{ $tx->customer_name }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    {{ $tx->posShift?->shift_number ?? 'Non-Shift' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-xs text-slate-700 max-w-[280px]" title="{{ $fullSummary }}">
                                    <span class="badge badge-neutral text-[10px] px-1.5 py-0.5 shrink-0">{{ $itemCount }} item</span>
                                    <span class="truncate font-medium text-slate-800 text-[11px]">{{ $displaySummary }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Total: <span class="font-medium text-slate-600">{{ $totalQty }} unit/lbr</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div>
                                    @if($tx->payment_method->value === 'CASH')
                                        <span class="badge badge-neutral text-[10px] px-1.5 py-0.5">Tunai</span>
                                    @else
                                        <span class="badge badge-info text-[10px] px-1.5 py-0.5">QRIS</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 font-medium truncate max-w-[130px]" title="Kasir: {{ $tx->cashier->name }}">
                                    {{ $tx->cashier->name }}
                                </div>
                            </td>
                            <td class="text-right whitespace-nowrap px-4 py-3">
                                <div class="font-bold text-xs text-slate-900 num-tabular">
                                    Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}
                                </div>
                                <div class="text-[10px] text-slate-400 num-tabular mt-0.5">
                                    Bayar: {{ number_format((float) $tx->amount_paid, 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="text-center whitespace-nowrap px-4 py-3">
                                @if($tx->status === 'COMPLETED')
                                    <span class="badge badge-success text-[10px] px-2 py-0.5">Selesai</span>
                                @else
                                    <span class="badge badge-danger text-[10px] px-2 py-0.5">Dibatalkan</span>
                                    @if($tx->voidedByTeacher)
                                        <div class="text-[9px] text-rose-500 mt-0.5 truncate max-w-[100px]" title="Dibatalkan oleh {{ $tx->voidedByTeacher->name }}: {{ $tx->void_reason }}">
                                            {{ $tx->voidedByTeacher->name }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap px-4 py-3">
                                <div class="relative inline-block text-left" @click.outside="if (activeMenuId === 'tx-{{ $tx->id }}') closeMenu()">
                                    <button type="button"
                                        @click="toggleMenu('tx-{{ $tx->id }}', $event)"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                        title="Menu Aksi">
                                        <x-icon name="more-vertical" class="h-3.5 w-3.5" />
                                    </button>

                                    <div x-show="activeMenuId === 'tx-{{ $tx->id }}'"
                                        x-cloak
                                        x-transition.opacity.duration.100ms
                                        :style="{
                                            position: 'fixed',
                                            top: menuPos.top,
                                            bottom: menuPos.bottom,
                                            right: menuPos.right,
                                            zIndex: 9999
                                        }"
                                        class="w-48 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-800">

                                        <button type="button"
                                            @click="closeMenu(); openReceipt(@js($txReceiptData))"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-brand-400">
                                            <x-icon name="receipt" class="h-3.5 w-3.5 text-slate-400" />
                                            <span>Lihat & Cetak Struk</span>
                                        </button>

                                        @if($tx->status === 'COMPLETED')
                                            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>
                                            <button type="button"
                                                @click="closeMenu(); showVoidModal = true; voidTxId = '{{ $tx->id }}'; voidTxInvoice = '{{ $tx->invoice_number }}'"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40">
                                                <x-icon name="x-circle" class="h-3.5 w-3.5 text-rose-500" />
                                                <span>Batalkan Transaksi</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state py-10">
                                    <span class="empty-state-icon"><x-icon name="receipt" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Transaksi tidak ditemukan</p>
                                    <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-400">Tidak ada riwayat transaksi yang cocok dengan filter pencarian.</p>
                                    <a href="{{ route('transactions.index', ['date' => '']) }}" class="btn btn-secondary btn-sm mt-4">Reset filter</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t border-slate-100 px-5 py-3.5 bg-slate-50/50">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- ============================================================= -->
    <!-- MODAL PREVIEW STRUK THERMAL -->
    <!-- ============================================================= -->
    <div x-show="showReceiptModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-sm" @click.outside="showReceiptModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Preview Struk Transaksi</h3>
                    <p class="mt-0.5 font-mono text-[11px] text-slate-500" x-text="activeReceipt?.invoice"></p>
                </div>
                <button type="button" @click="showReceiptModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="modal-body max-h-[72vh] overflow-y-auto">
                <template x-if="activeReceipt">
                    <div>
                        <!-- Alert jika transaksi dibatalkan (VOID) -->
                        <template x-if="activeReceipt.status === 'VOIDED'">
                            <div class="mb-3 rounded-lg border border-rose-300 bg-rose-50 p-2.5 text-center text-xs text-rose-800">
                                <p class="font-bold tracking-wide text-rose-700">*** TRANSAKSI TELAH DIBATALKAN ***</p>
                                <p class="mt-0.5 text-[10px]" x-show="activeReceipt.void_reason" x-text="'Alasan: ' + activeReceipt.void_reason"></p>
                                <p class="text-[10px] text-rose-600" x-show="activeReceipt.voided_by" x-text="'Oleh: ' + activeReceipt.voided_by"></p>
                            </div>
                        </template>

                        <!-- Thermal Receipt Container -->
                        <div id="thermal-receipt" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 font-mono text-[10px] leading-relaxed text-slate-900 shadow-sm">
                            <!-- Toko Info -->
                            <div class="text-center">
                                <p class="text-[13px] font-bold tracking-[0.08em]">{{ $storeInfo['store_name'] }}</p>
                                @if(!empty($storeInfo['store_tagline']))
                                    <p class="text-[9px] text-slate-500">{{ $storeInfo['store_tagline'] }}</p>
                                @endif
                                @if(!empty($storeInfo['store_address_line1']))
                                    <p class="text-[9px] text-slate-500">{{ $storeInfo['store_address_line1'] }}</p>
                                @endif
                                @if(!empty($storeInfo['store_address_line2']))
                                    <p class="text-[9px] text-slate-500">{{ $storeInfo['store_address_line2'] }}</p>
                                @endif
                                @if(!empty($storeInfo['store_phone']) || !empty($storeInfo['store_email']))
                                    <p class="text-[9px] text-slate-500">
                                        {{ $storeInfo['store_phone'] }}
                                        @if(!empty($storeInfo['store_phone']) && !empty($storeInfo['store_email'])) • @endif
                                        {{ $storeInfo['store_email'] }}
                                    </p>
                                @endif
                                <p class="mt-2 border-y border-dashed border-slate-300 py-1 text-[9px]">
                                    No: <span class="font-semibold" x-text="activeReceipt.invoice"></span>
                                </p>
                            </div>

                            <!-- Metadata Transaksi -->
                            <div class="mt-2 space-y-0.5 text-[9px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Tgl</span>
                                    <span class="font-medium" x-text="activeReceipt.date + ' WIB'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Kasir</span>
                                    <span class="font-medium" x-text="activeReceipt.cashier"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pelanggan</span>
                                    <span class="font-medium" x-text="activeReceipt.customer"></span>
                                </div>
                                <div class="flex justify-between" x-show="activeReceipt.shift && activeReceipt.shift !== '-'">
                                    <span class="text-slate-500">Shift</span>
                                    <span class="font-medium" x-text="activeReceipt.shift"></span>
                                </div>
                            </div>

                            <!-- Daftar Item Transaksi -->
                            <div class="mt-2 space-y-1.5 border-y border-dashed border-slate-300 py-2">
                                <template x-for="(item, idx) in activeReceipt.items" :key="idx">
                                    <div>
                                        <p class="font-bold text-slate-900" x-text="item.name"></p>
                                        <div class="flex justify-between text-[9px] text-slate-500">
                                            <span x-text="item.qty + ' x ' + Number(item.unit_price).toLocaleString('id-ID')"></span>
                                            <span class="font-medium text-slate-800" x-text="Number(item.subtotal_gross).toLocaleString('id-ID')"></span>
                                        </div>
                                        <template x-if="item.discount > 0">
                                            <div class="flex justify-between text-[8px] text-slate-500">
                                                <span>Diskon</span>
                                                <span class="text-slate-600" x-text="'-' + Number(item.discount).toLocaleString('id-ID')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <!-- Rincian Subtotal & Diskon -->
                            <div class="mt-2 space-y-0.5 text-[9px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Subtotal</span>
                                    <span class="font-medium" x-text="formatRp(activeReceipt.subtotal_gross)"></span>
                                </div>
                                <template x-if="activeReceipt.total_discount > 0">
                                    <div class="space-y-0.5">
                                        <template x-if="activeReceipt.tier_discount > 0">
                                            <div class="flex justify-between text-slate-500">
                                                <span>Diskon Grosir</span>
                                                <span x-text="'-' + formatRp(activeReceipt.tier_discount)"></span>
                                            </div>
                                        </template>
                                        <template x-if="activeReceipt.promo_discount > 0">
                                            <div class="flex justify-between text-slate-500">
                                                <span x-text="'Promo (' + (activeReceipt.promo_name || 'Diskon') + ')'"></span>
                                                <span x-text="'-' + formatRp(activeReceipt.promo_discount)"></span>
                                            </div>
                                        </template>
                                        <template x-if="activeReceipt.manual_discount > 0">
                                            <div class="flex justify-between text-slate-500">
                                                <span>Diskon Kasir</span>
                                                <span x-text="'-' + formatRp(activeReceipt.manual_discount)"></span>
                                            </div>
                                        </template>
                                        <div class="flex justify-between border-t border-dashed border-slate-300 pt-0.5 font-semibold">
                                            <span>Total Diskon</span>
                                            <span x-text="'-' + formatRp(activeReceipt.total_discount)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Total, Pembayaran, dan Kembalian -->
                            <div class="mt-1.5 space-y-0.5 border-t border-dashed border-slate-300 pt-1.5">
                                <div class="flex justify-between text-[11px] font-bold">
                                    <span>TOTAL</span>
                                    <span x-text="formatRp(activeReceipt.grand_total)"></span>
                                </div>
                                <div class="flex justify-between text-[9px]">
                                    <span class="text-slate-500" x-text="'Bayar (' + activeReceipt.payment_method + ')'"></span>
                                    <span class="font-medium" x-text="formatRp(activeReceipt.amount_paid)"></span>
                                </div>
                                <div class="flex justify-between text-[9px]">
                                    <span class="text-slate-500">Kembalian</span>
                                    <span class="font-medium" x-text="formatRp(activeReceipt.change_amount)"></span>
                                </div>
                            </div>

                            <!-- Footer Struk -->
                            @if(!empty($storeInfo['receipt_footer_line1']) || !empty($storeInfo['receipt_footer_line2']))
                                <div class="mt-3 border-t border-dashed border-slate-300 pt-2 text-center text-[9px] text-slate-500">
                                    @if(!empty($storeInfo['receipt_footer_line1']))
                                        {{ $storeInfo['receipt_footer_line1'] }}<br>
                                    @endif
                                    @if(!empty($storeInfo['receipt_footer_line2']))
                                        {{ $storeInfo['receipt_footer_line2'] }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </template>
            </div>

            <div class="modal-footer">
                <button type="button" @click="showReceiptModal = false" class="btn btn-secondary">Tutup</button>
                <button type="button" @click="printReceipt()" class="btn btn-dark">
                    <x-icon name="printer" class="h-4 w-4" />
                    Cetak Struk
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL PEMBATALAN TRANSAKSI (VOID) -->
    <!-- ============================================================= -->
    <div x-show="showVoidModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-md" @click.outside="showVoidModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Batalkan transaksi</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perlu otorisasi & PIN guru piket</p>
                </div>
                <button type="button" @click="showVoidModal = false" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form method="POST" :action="'/transactions/' + voidTxId + '/void'" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Invoice <strong class="font-mono" x-text="voidTxInvoice"></strong> akan dibatalkan. Stok dikembalikan dan jurnal pembalik dibuat otomatis.</span>
                    </div>

                    <div>
                        <label class="form-label">Guru Piket <span class="text-rose-600">*</span></label>
                        <select name="supervisor_teacher_id" required class="select">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">PIN Guru <span class="text-rose-600">*</span></label>
                        <input type="password" name="supervisor_pin" maxlength="6" required placeholder="6 digit"
                            class="input font-mono tracking-[0.3em]">
                    </div>

                    <div>
                        <label class="form-label">Alasan Pembatalan <span class="text-rose-600">*</span></label>
                        <input type="text" name="void_reason" required placeholder="Contoh: Salah input jumlah lembar / salah produk"
                            class="input">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showVoidModal = false" class="btn btn-secondary">Batal</button>
                    <button type="button"
                        @click="window.bestcoDialog.confirm({
                            title: 'Batalkan Transaksi (Void)',
                            message: 'Batalkan transaksi ini secara permanen?',
                            detail: 'Stok barang akan dikembalikan ke persediaan dan jurnal pembalik akan dicatat otomatis.',
                            type: 'danger',
                            confirmText: 'Ya, Batalkan Transaksi',
                            cancelText: 'Batal',
                            onConfirm: () => $el.closest('form').submit()
                        })"
                        class="btn btn-danger">
                        Batalkan transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
