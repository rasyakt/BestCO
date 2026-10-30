@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-brand-700 hover:underline">&larr; Persediaan</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500">Mutasi Internal</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Audit Mutasi Internal Stok (Rim &rarr; Lembar)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Riwayat konversi otomatis barang etalase ritel menjadi bahan baku jasa (1 Rim Kertas HVS &rarr; 500 Lembar) beserta pemindahbukuan nilai HPP antar akun persediaan.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.spoilage') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200">
                Log Kerusakan (Spoilage)
            </a>
            <a href="{{ route('pos.terminal') }}" class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
                Mutasi Baru di POS [F7] &rarr;
            </a>
        </div>
    </div>

    <!-- 3 Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Nilai HPP Dipindahbukukan</span>
            <div class="text-2xl font-bold text-slate-900 num-tabular">
                Rp {{ number_format((float) $totalMutationValue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">
                Dr. Persediaan Bahan Jasa / Cr. Persediaan Ritel
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Insiden Mutasi Internal</span>
            <div class="text-2xl font-bold text-brand-700 num-tabular">
                {{ $totalMutations }} Kali
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Disetujui Guru Supervisor
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Lembar Hasil Konversi</span>
            <div class="text-2xl font-bold text-purple-700 num-tabular">
                {{ number_format((float) $totalSheetsConverted, 0, ',', '.') }} Lembar
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Stok bahan baku kertas yang dialirkan ke jasa
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <form method="GET" action="{{ route('inventory.mutations') }}" class="flex items-center gap-2.5 w-full sm:w-auto">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Bukti / Catatan..."
                class="px-3 py-1.5 rounded border border-slate-300 text-xs focus:outline-none focus:ring-1 focus:ring-brand-600 w-64">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white font-semibold rounded hover:bg-slate-800">
                Cari
            </button>
            <a href="{{ route('inventory.mutations') }}" class="text-slate-500 hover:underline px-2">Reset</a>
        </form>
    </div>

    <!-- Mutations Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">No. Bukti Mutasi</th>
                        <th class="py-2.5 px-3">Produk Etalase (Asal)</th>
                        <th class="py-2.5 px-3">Bahan Baku Jasa (Tujuan)</th>
                        <th class="py-2.5 px-3 text-right">Rasio Konversi</th>
                        <th class="py-2.5 px-3 text-right">Nilai Mutasi (HPP)</th>
                        <th class="py-2.5 px-3">Pemohon / Disetujui</th>
                        <th class="py-2.5 px-3">Shift & Jurnal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mutations as $m)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3">
                                <span class="font-mono font-semibold text-slate-900">{{ $m->mutation_number }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $m->mutated_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-slate-900">{{ $m->sourceProduct?->name }}</div>
                                <div class="text-[11px] text-rose-600 num-tabular font-semibold">
                                    -{{ number_format((float) $m->source_qty_deducted, 0, ',', '.') }} {{ $m->sourceProduct?->unit?->code }}
                                    <span class="text-slate-400 font-normal">(@ Rp {{ number_format((float) $m->source_unit_cost, 0, ',', '.') }})</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-slate-900">{{ $m->targetProduct?->name }}</div>
                                <div class="text-[11px] text-emerald-600 num-tabular font-semibold">
                                    +{{ number_format((float) $m->target_qty_added, 0, ',', '.') }} {{ $m->targetProduct?->unit?->code }}
                                    <span class="text-slate-400 font-normal">(@ Rp {{ number_format((float) $m->target_unit_cost, 0, ',', '.') }})</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-medium text-slate-700">
                                1 : {{ number_format((float) $m->conversion_factor, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-900 num-tabular">
                                Rp {{ number_format((float) $m->total_mutation_value, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                <div>{{ $m->requestedBy?->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">Verif: {{ $m->approvedBy?->name ?? 'Guru Supervisor' }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="font-mono text-[11px] text-slate-600 block">{{ $m->posShift?->shift_code ?? '-' }}</span>
                                @if($m->journal_entry_id)
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                        Jurnal #{{ $m->journal_entry_id }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada catatan mutasi internal persediaan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="p-3 border-t bg-slate-50">
                {{ $mutations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
