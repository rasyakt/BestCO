@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showVoidModal: false, voidTxId: '', voidTxInvoice: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Riwayat Transaksi Kasir POS</h1>
            <p class="text-xs text-slate-500 mt-0.5">Daftar seluruh penjualan ritel dan jasa, cetak ulang struk thermal, dan pembatalan transaksi (VOID) terotorisasi.</p>
        </div>

        <a href="{{ route('pos.terminal') }}" class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
            + Transaksi Baru Kasir &rarr;
        </a>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap items-center gap-2.5 w-full">
            <div>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Invoice / Pelanggan..."
                    class="px-3 py-1.5 rounded border border-slate-300 text-xs focus:outline-none focus:ring-1 focus:ring-brand-600">
            </div>

            <div>
                <select name="status" class="px-3 py-1.5 rounded border border-slate-300 bg-white">
                    <option value="ALL" {{ $status === 'ALL' ? 'selected' : '' }}>Semua Status</option>
                    <option value="COMPLETED" {{ $status === 'COMPLETED' ? 'selected' : '' }}>Selesai (COMPLETED)</option>
                    <option value="VOIDED" {{ $status === 'VOIDED' ? 'selected' : '' }}>Dibatalkan (VOIDED)</option>
                </select>
            </div>

            <div>
                <input type="date" name="date" value="{{ $date }}"
                    class="px-3 py-1.5 rounded border border-slate-300 bg-white">
            </div>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded">
                Terapkan Filter
            </button>
            <a href="{{ route('transactions.index', ['date' => '']) }}" class="text-slate-500 hover:underline px-2">
                Reset
            </a>
        </form>
    </div>

    <!-- Transactions List -->
    <div class="space-y-3">
        @forelse($transactions as $tx)
            <div class="bg-white rounded-lg border border-slate-200 shadow-xs p-4 space-y-3 text-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-2.5">
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono font-bold text-slate-900 text-sm">{{ $tx->invoice_number }}</span>
                        <span class="px-2 py-0.5 rounded font-semibold text-[10px] {{ $tx->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                            {{ $tx->status }}
                        </span>
                        <span class="px-1.5 py-0.5 rounded font-mono text-[10px] bg-slate-100 text-slate-700 font-medium">
                            {{ $tx->payment_method->value }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3 text-slate-500">
                        <span>{{ $tx->transaction_date->format('d/m/Y H:i') }} WIB</span>
                        <span>&bull;</span>
                        <span>Kasir: <strong class="text-slate-700">{{ $tx->cashier->name }}</strong></span>
                    </div>
                </div>

                <!-- Items Detail -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <span class="text-[11px] text-slate-400 font-medium block">Rincian Item Belanja:</span>
                        @foreach($tx->items as $it)
                            <div class="flex justify-between text-slate-700 text-[11px] py-0.5">
                                <span>{{ $it->item_name_snapshot }} &times; {{ (int) $it->qty }}</span>
                                <span class="num-tabular font-medium">Rp {{ number_format((float) $it->line_net_total, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1 bg-slate-50 p-3 rounded border border-slate-100 text-right">
                        <div class="flex justify-between text-slate-500">
                            <span>Pelanggan:</span>
                            <span class="font-medium text-slate-800">{{ $tx->customer_name }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Grand Total:</span>
                            <span class="font-bold text-sm text-slate-900 num-tabular">Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Bayar / Kembali:</span>
                            <span class="num-tabular">
                                Rp {{ number_format((float) $tx->amount_paid, 0, ',', '.') }} / 
                                Rp {{ number_format((float) $tx->change_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($tx->status === 'VOIDED')
                    <div class="p-2.5 rounded bg-rose-50 border border-rose-200 text-rose-800 text-[11px] flex items-center justify-between">
                        <span><strong>Dibatalkan oleh:</strong> {{ $tx->voidedByTeacher?->name ?? 'Guru Supervisor' }} &bull; <em>"{{ $tx->void_reason }}"</em></span>
                        <span class="font-mono text-[10px]">Stok & Jurnal Dibalikkan</span>
                    </div>
                @else
                    <div class="pt-2 border-t flex justify-end gap-2">
                        <button type="button"
                            @click="showVoidModal = true; voidTxId = '{{ $tx->id }}'; voidTxInvoice = '{{ $tx->invoice_number }}'"
                            class="px-2.5 py-1 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded border border-rose-200 font-medium text-[11px]">
                            ⚠️ Batalkan Transaksi (VOID)
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg border border-slate-200 p-8 text-center text-xs text-slate-400">
                Tidak ada data transaksi yang sesuai dengan filter.
            </div>
        @endforelse

        @if($transactions->hasPages())
            <div class="p-3 bg-white rounded-lg border border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL VOID TRANSAKSI -->
    <div x-show="showVoidModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-xl text-xs" @click.outside="showVoidModal = false">
            <div class="flex items-center justify-between border-b pb-2">
                <h3 class="font-bold text-sm text-slate-900">Pembatalan Transaksi Kasir (VOID)</h3>
                <button type="button" @click="showVoidModal = false" class="text-slate-400 text-lg">&times;</button>
            </div>

            <p class="text-[11px] text-slate-500">
                Membatalkan invoice <strong class="font-mono text-slate-800" x-text="voidTxInvoice"></strong>. Stok produk etalase dan bahan baku jasa BoM akan dikembalikan secara atomik, dan Jurnal Pembalik otomatis diposting.
            </p>

            <form method="POST" :action="'/transactions/' + voidTxId + '/void'" class="space-y-3.5">
                @csrf

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Guru Piket Supervisor Pemberi Approval <span class="text-rose-600">*</span></label>
                    <select name="supervisor_teacher_id" required class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white font-medium">
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">PIN Approval Guru Supervisor (6 Digit) <span class="text-rose-600">*</span></label>
                    <input type="password" name="supervisor_pin" maxlength="6" required placeholder="PIN Guru"
                        class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                </div>

                <div>
                    <label class="block font-medium text-slate-700 mb-1">Alasan Pembatalan Transaksi <span class="text-rose-600">*</span></label>
                    <input type="text" name="void_reason" required placeholder="Contoh: Salah input kuantitas lembar fotocopy"
                        class="w-full px-3 py-1.5 rounded border border-slate-300">
                </div>

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="showVoidModal = false" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold">
                        Batal
                    </button>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini secara permanen?')"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold shadow-xs">
                        Konfirmasi VOID & Balik Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
