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

        <div class="w-full">
            <table class="data-table w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">No. Bukti & Waktu</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Barang Asal</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Bahan Tujuan</th>
                        <th class="text-center whitespace-nowrap px-3 py-2.5 w-px">Rasio</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5 w-px">Nilai Mutasi</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Pemohon & Shift</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $m)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-mono text-xs font-semibold text-slate-900 whitespace-nowrap block">{{ $m->mutation_number }}</span>
                                <span class="font-mono text-[10px] text-slate-400 whitespace-nowrap block mt-0.5">{{ $m->mutated_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-semibold text-xs text-slate-800 whitespace-nowrap block">{{ $m->sourceProduct?->name }}</span>
                                <span class="font-mono text-[11px] font-semibold text-rose-600 num-tabular whitespace-nowrap block mt-0.5">
                                    -{{ number_format((float) $m->source_qty_deducted, 0, ',', '.') }} {{ $m->sourceProduct?->unit?->code }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-semibold text-xs text-slate-800 whitespace-nowrap block">{{ $m->targetProduct?->name }}</span>
                                <span class="font-mono text-[11px] font-semibold text-emerald-600 num-tabular whitespace-nowrap block mt-0.5">
                                    +{{ number_format((float) $m->target_qty_added, 0, ',', '.') }} {{ $m->targetProduct?->unit?->code }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap px-3 py-2.5">
                                <span class="chip text-[10px] whitespace-nowrap font-mono">1 : {{ number_format((float) $m->conversion_factor, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-right font-semibold font-mono text-xs text-slate-900 num-tabular whitespace-nowrap px-3 py-2.5">
                                Rp {{ number_format((float) $m->total_mutation_value, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <div class="flex items-center gap-1.5 whitespace-nowrap">
                                    <span class="font-medium text-xs text-slate-800 whitespace-nowrap">{{ $m->requestedBy?->name ?? '-' }}</span>
                                    @if($m->approvedBy)
                                        <span class="text-[10px] text-slate-400 font-normal whitespace-nowrap">(Guru: {{ $m->approvedBy->name }})</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="chip text-[9px] font-mono whitespace-nowrap">{{ $m->posShift?->shift_code ?? '-' }}</span>
                                    @if($m->journal_entry_id)
                                        <span class="badge badge-success text-[9px] py-0 px-1 whitespace-nowrap">Dijurnal</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
