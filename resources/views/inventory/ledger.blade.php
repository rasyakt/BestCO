@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-brand-700 hover:underline">&larr; Kembali ke Daftar Persediaan</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500 font-mono">{{ $product->sku }}</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kartu Stok: {{ $product->name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Buku pembantu persediaan barang yang mencatat setiap peristiwa pergerakan stok secara kronologis.</p>
        </div>

        <!-- Stat Badge -->
        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs flex items-center gap-4 text-xs">
            <div>
                <span class="text-slate-500 block text-[11px]">Stok Aktual Saat Ini</span>
                <span class="font-bold text-base text-slate-900 num-tabular">
                    {{ $product->unit?->allow_decimal ? number_format((float) $product->current_stock, 4, ',', '.') : number_format((float) $product->current_stock, 0, ',', '.') }} {{ $product->unit?->code }}
                </span>
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div>
                <span class="text-slate-500 block text-[11px]">HPP Rata-rata (Avg Cost)</span>
                <span class="font-bold text-base text-brand-700 num-tabular">
                    Rp {{ number_format((float) $product->average_cost, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">Tanggal & Waktu</th>
                        <th class="py-2.5 px-3">Tipe Peristiwa</th>
                        <th class="py-2.5 px-3 text-right">Saldo Awal</th>
                        <th class="py-2.5 px-3 text-right">Perubahan Qty</th>
                        <th class="py-2.5 px-3 text-right">Saldo Akhir</th>
                        <th class="py-2.5 px-3 text-right">Harga Pokok (HPP)</th>
                        <th class="py-2.5 px-3 text-right">Total Nilai</th>
                        <th class="py-2.5 px-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 text-slate-600 num-tabular whitespace-nowrap">
                                {{ $m->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2.5 px-3">
                                @php
                                    $badgeStyle = match($m->reference_type) {
                                        'PURCHASE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'INTERNAL_MUTATION_IN' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'INTERNAL_MUTATION_OUT' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'POS_SALE' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'SERVICE_BOM' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'SPOILAGE' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'VOID_RESTORE' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold border {{ $badgeStyle }}">
                                    {{ $m->reference_type }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right num-tabular text-slate-600">
                                {{ number_format((float) $m->balance_before, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold num-tabular {{ str_starts_with((string)$m->qty_change, '-') ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ (string) $m->qty_change }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-semibold num-tabular text-slate-900">
                                {{ number_format((float) $m->balance_after, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right num-tabular text-slate-600">
                                Rp {{ number_format((float) $m->unit_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-medium num-tabular text-slate-800">
                                Rp {{ number_format((float) $m->total_value, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">
                                {{ $m->createdByUser?->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada catatan mutasi kartu stok untuk produk ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-3 border-t bg-slate-50">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
