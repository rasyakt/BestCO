@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ showVoidModal: false, voidTxId: '', voidTxInvoice: '' }">
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

    <!-- Filter -->
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

    <!-- Daftar transaksi -->
    <div class="space-y-3">
        @forelse($transactions as $tx)
            <div class="card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="font-mono text-sm font-semibold text-slate-900">{{ $tx->invoice_number }}</span>
                        <span class="badge {{ $tx->status === 'COMPLETED' ? 'badge-success' : 'badge-danger' }}">
                            {{ $tx->status === 'COMPLETED' ? 'Selesai' : 'Dibatalkan' }}
                        </span>
                        <span class="text-[11px] text-slate-500">{{ $tx->payment_method->value === 'CASH' ? 'Tunai' : 'QRIS' }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                        <span>{{ $tx->transaction_date->format('d/m/Y H:i') }} WIB</span>
                        <span class="hidden h-3 w-px bg-slate-200 sm:block"></span>
                        <span>Kasir: <strong class="font-medium text-slate-700">{{ $tx->cashier->name }}</strong></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 px-5 py-4 lg:grid-cols-2">
                    <div>
                        <p class="section-label mb-2">Rincian barang</p>
                        <div class="space-y-1.5">
                            @foreach($tx->items as $it)
                                <div class="flex items-start justify-between gap-4 text-xs">
                                    <span class="text-slate-600">{{ $it->item_name_snapshot }} <span class="font-medium text-slate-400">&times; {{ (int) $it->qty }}</span></span>
                                    <span class="shrink-0 font-semibold text-slate-800 num-tabular">Rp {{ number_format((float) $it->line_net_total, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Pelanggan</span>
                                <span class="font-medium text-slate-800">{{ $tx->customer_name }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500">Total</span>
                                <span class="text-base font-semibold text-slate-900 num-tabular">Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                                <span class="text-slate-500">Bayar / kembali</span>
                                <span class="font-medium text-slate-700 num-tabular">
                                    Rp {{ number_format((float) $tx->amount_paid, 0, ',', '.') }} / Rp {{ number_format((float) $tx->change_amount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($tx->status === 'VOIDED')
                    <div class="mx-5 mb-4 flex flex-col gap-2 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-[11px] text-rose-800 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            <strong class="font-semibold">Dibatalkan oleh {{ $tx->voidedByTeacher?->name ?? 'guru' }}.</strong>
                            @if($tx->void_reason)
                                <span class="text-rose-600">Alasan: {{ $tx->void_reason }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 text-[10px] text-rose-500">Stok dan jurnal sudah dikembalikan</span>
                    </div>
                @else
                    <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                        <button type="button"
                            @click="showVoidModal = true; voidTxId = '{{ $tx->id }}'; voidTxInvoice = '{{ $tx->invoice_number }}'"
                            class="btn btn-sm border border-rose-200 bg-white text-rose-600 hover:bg-rose-50">
                            <x-icon name="x-circle" class="h-3.5 w-3.5" />
                            Batalkan transaksi
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="card">
                <div class="empty-state">
                    <span class="empty-state-icon"><x-icon name="receipt" class="h-5 w-5" /></span>
                    <p class="text-sm font-semibold text-slate-700">Transaksi tidak ditemukan</p>
                    <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-400">Ubah kata kunci, status, atau tanggal.</p>
                    <a href="{{ route('transactions.index', ['date' => '']) }}" class="btn btn-secondary btn-sm mt-4">Reset filter</a>
                </div>
            </div>
        @endforelse

        @if($transactions->hasPages())
            <div class="card px-4 py-3">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal pembatalan -->
    <div x-show="showVoidModal" x-cloak x-transition.opacity class="modal-backdrop">
        <div class="modal-card max-w-md" @click.outside="showVoidModal = false">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Batalkan transaksi</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perlu persetujuan guru</p>
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
                        <label class="form-label">Guru <span class="text-rose-600">*</span></label>
                        <select name="supervisor_teacher_id" required class="select">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">PIN guru <span class="text-rose-600">*</span></label>
                        <input type="password" name="supervisor_pin" maxlength="6" required placeholder="6 digit"
                            class="input font-mono tracking-[0.3em]">
                    </div>

                    <div>
                        <label class="form-label">Alasan <span class="text-rose-600">*</span></label>
                        <input type="text" name="void_reason" required placeholder="Contoh: Salah input jumlah lembar"
                            class="input">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" @click="showVoidModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" onclick="return confirm('Batalkan transaksi ini secara permanen?')"
                        class="btn btn-danger">
                        Batalkan transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
