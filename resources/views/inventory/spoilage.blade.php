@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-brand-700 hover:underline">&larr; Persediaan</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500">Log Spoilage</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Audit Barang Rusak & Gagal Produksi (Spoilage)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Monitoring pemotongan bahan baku (kertas & toner) akibat paper jam, tinta cacat, dan laminating kusut beserta posting otomatis ke Beban Kerusakan Jasa.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.mutations') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200">
                Mutasi Internal (Rim &rarr; Lembar)
            </a>
            <a href="{{ route('pos.terminal') }}" class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
                Catat Spoilage Baru di POS [F6] &rarr;
            </a>
        </div>
    </div>

    <!-- 3 Key Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Beban Kerugian Produksi (HPP)</span>
            <div class="text-2xl font-bold text-rose-600 num-tabular">
                Rp {{ number_format((float) $totalLossRupiah, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Terjurnal ke Akun Beban Kerusakan (5-2101)
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Insiden Gagal Produksi</span>
            <div class="text-2xl font-bold text-slate-900 num-tabular">
                {{ $totalIncidents }} Kali
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Kertas macet, cacat toner, plastik kusut
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Lembar / Unit Gagal</span>
            <div class="text-2xl font-bold text-amber-600 num-tabular">
                {{ number_format((float) $totalFailedOutput, 0, ',', '.') }} Lembar
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Akumulasi produk rusak yang diaudit
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <form method="GET" action="{{ route('inventory.spoilage') }}" class="flex flex-wrap items-center gap-2.5 w-full">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Bukti / Alasan..."
                class="px-3 py-1.5 rounded border border-slate-300 text-xs focus:outline-none focus:ring-1 focus:ring-brand-600">

            <select name="category" class="px-3 py-1.5 rounded border border-slate-300 bg-white text-xs">
                <option value="ALL" {{ $category === 'ALL' ? 'selected' : '' }}>Semua Kategori</option>
                <option value="PAPER_JAM" {{ $category === 'PAPER_JAM' ? 'selected' : '' }}>Paper Jam / Kertas Nyangkut</option>
                <option value="INK_SMUDGE" {{ $category === 'INK_SMUDGE' ? 'selected' : '' }}>Tinta Bergaris / Cacat</option>
                <option value="MACHINE_ERROR" {{ $category === 'MACHINE_ERROR' ? 'selected' : '' }}>Kerusakan Mesin</option>
                <option value="OPERATOR_ERROR" {{ $category === 'OPERATOR_ERROR' ? 'selected' : '' }}>Kesalahan Operator Siswa</option>
                <option value="OTHER" {{ $category === 'OTHER' ? 'selected' : '' }}>Lainnya</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white font-semibold rounded hover:bg-slate-800">
                Filter
            </button>
            <a href="{{ route('inventory.spoilage') }}" class="text-slate-500 hover:underline px-2">Reset</a>
        </form>
    </div>

    <!-- Spoilage Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">No. Bukti & Waktu</th>
                        <th class="py-2.5 px-3">Layanan / Sumber</th>
                        <th class="py-2.5 px-3">Kategori & Alasan</th>
                        <th class="py-2.5 px-3 text-right">Lembar Gagal</th>
                        <th class="py-2.5 px-3">Bahan Terbuang (BoM)</th>
                        <th class="py-2.5 px-3 text-right">Beban Kerugian</th>
                        <th class="py-2.5 px-3">Pelapor / Approval</th>
                        <th class="py-2.5 px-3">Shift</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3">
                                <span class="font-mono font-semibold text-slate-900">{{ $log->spoilage_number }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $log->logged_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="font-medium text-slate-800">{{ $log->service?->name ?? 'Etalase / Lainnya' }}</span>
                                <span class="block text-[10px] text-slate-400 font-mono">{{ $log->spoilage_source }}</span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ $log->reason_category }}
                                </span>
                                @if($log->reason_description)
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $log->reason_description }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold num-tabular text-slate-800">
                                {{ number_format((float) $log->failed_output_qty, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="space-y-0.5">
                                    @foreach($log->items as $item)
                                        <div class="text-[11px] text-slate-700 flex items-center gap-1.5">
                                            <span class="font-semibold text-rose-600 num-tabular">
                                                -{{ $item->product?->unit?->allow_decimal ? number_format((float) $item->qty_wasted, 4, ',', '.') : number_format((float) $item->qty_wasted, 0, ',', '.') }} {{ $item->product?->unit?->code }}
                                            </span>
                                            <span class="text-slate-500">({{ $item->product?->name }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-rose-600 num-tabular">
                                Rp {{ number_format((float) $log->total_spoilage_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                <div>{{ $log->reportedBy?->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">Verif: {{ $log->verifiedBy?->name ?? 'Guru Piket' }}</div>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                {{ $log->posShift?->shift_code ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada catatan kerusakan atau kegagalan produksi (spoilage).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-t bg-slate-50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
