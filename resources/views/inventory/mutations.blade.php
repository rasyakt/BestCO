@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Pindah Stok"
        subtitle="Riwayat pemindahan stok etalase menjadi bahan baku jasa.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Pindah Stok</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Barang rusak
            </a>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah stok di kasir
                <span class="kbd">F7</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="stat-label">Nilai stok dipindah</p>
            <p class="stat-value">Rp {{ number_format((float) $totalMutationValue, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Dari etalase ke bahan baku</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Jumlah pemindahan</p>
            <p class="stat-value">{{ number_format((float) $totalMutations, 0, ',', '.') }} <span class="text-sm font-medium text-slate-400">kali</span></p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Disetujui guru</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Lembar dihasilkan</p>
            <p class="stat-value text-violet-700">{{ number_format((float) $totalSheetsConverted, 0, ',', '.') }} <span class="text-sm font-medium text-slate-400">lembar</span></p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Bahan baku kertas untuk layanan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
        <form method="GET" action="{{ route('inventory.mutations') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Cari</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Cari nomor atau catatan..."
                        class="input">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Cari</button>
                <a href="{{ route('inventory.mutations') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat pemindahan</h2>
                <p class="card-subtitle">Stok dan nilai yang dipindahkan</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ $mutations->total() }} catatan</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. bukti</th>
                        <th>Barang asal</th>
                        <th>Bahan tujuan</th>
                        <th class="text-right">Konversi</th>
                        <th class="text-right">Nilai</th>
                        <th>Pemohon</th>
                        <th>Shift</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $m)
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="font-mono text-[11px] font-medium text-slate-900">{{ $m->mutation_number }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $m->mutated_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $m->sourceProduct?->name }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-rose-600 num-tabular">
                                    -{{ number_format((float) $m->source_qty_deducted, 0, ',', '.') }} {{ $m->sourceProduct?->unit?->code }}
                                    <span class="font-normal text-slate-400">(@ Rp {{ number_format((float) $m->source_unit_cost, 0, ',', '.') }})</span>
                                </p>
                            </td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $m->targetProduct?->name }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-emerald-600 num-tabular">
                                    +{{ number_format((float) $m->target_qty_added, 0, ',', '.') }} {{ $m->targetProduct?->unit?->code }}
                                    <span class="font-normal text-slate-400">(@ Rp {{ number_format((float) $m->target_unit_cost, 0, ',', '.') }})</span>
                                </p>
                            </td>
                            <td class="text-right">
                                <span class="chip">1 : {{ number_format((float) $m->conversion_factor, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-right font-semibold text-slate-900 num-tabular">
                                Rp {{ number_format((float) $m->total_mutation_value, 0, ',', '.') }}
                            </td>
                            <td>
                                <p class="text-[11px] font-medium text-slate-700">{{ $m->requestedBy?->name ?? '-' }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">Guru: {{ $m->approvedBy?->name ?? '-' }}</p>
                            </td>
                            <td>
                                <span class="chip">{{ $m->posShift?->shift_code ?? '-' }}</span>
                                @if($m->journal_entry_id)
                                    <span class="mt-1 block text-[10px] text-slate-400">Sudah dijurnal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="swap" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada pemindahan stok</p>
                                    <p class="mt-1 text-xs text-slate-400">Lakukan dari menu Kasir dengan tombol F7.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                {{ $mutations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
