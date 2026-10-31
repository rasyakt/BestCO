@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        eyebrow="Kartu Stok"
        :title="'Kartu Stok: ' . $product->name"
        subtitle="Buku pembantu persediaan yang mencatat setiap pergerakan stok secara kronologis, termasuk nilai HPP dan petugas yang mencatat.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-bold text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="font-mono text-slate-500">{{ $product->sku }}</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <div class="card flex items-center gap-4 px-4 py-3">
                <div>
                    <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400">Stok Saat Ini</p>
                    <p class="mt-0.5 text-lg font-extrabold text-slate-900 num-tabular">
                        {{ $product->unit?->allow_decimal ? number_format((float) $product->current_stock, 4, ',', '.') : number_format((float) $product->current_stock, 0, ',', '.') }}
                        <span class="text-[10px] font-bold text-slate-400">{{ $product->unit?->code }}</span>
                    </p>
                </div>
                <span class="h-9 w-px bg-slate-200"></span>
                <div>
                    <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400">HPP Rata-rata</p>
                    <p class="mt-0.5 text-lg font-extrabold text-brand-700 num-tabular">Rp {{ number_format((float) $product->average_cost, 0, ',', '.') }}</p>
                </div>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Movement table -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat Pergerakan Stok</h2>
                <p class="card-subtitle">{{ $movements->total() }} peristiwa tercatat</p>
            </div>
            <span class="badge {{ $product->inventory_type->value === 'RETAIL' ? 'badge-info' : 'badge-purple' }}">
                {{ $product->inventory_type->value === 'RETAIL' ? 'ATK Etalase' : 'Bahan Baku Jasa' }}
            </span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal &amp; Waktu</th>
                        <th>Tipe Peristiwa</th>
                        <th class="text-right">Saldo Awal</th>
                        <th class="text-right">Perubahan</th>
                        <th class="text-right">Saldo Akhir</th>
                        <th class="text-right">HPP</th>
                        <th class="text-right">Total Nilai</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        @php
                            $badgeStyle = match ($m->reference_type) {
                                'PURCHASE' => 'badge-success',
                                'INTERNAL_MUTATION_IN' => 'badge-brand',
                                'INTERNAL_MUTATION_OUT' => 'badge-warning',
                                'POS_SALE' => 'badge-info',
                                'SERVICE_BOM' => 'badge-purple',
                                'SPOILAGE' => 'badge-danger',
                                'VOID_RESTORE' => 'badge-success',
                                default => 'badge-neutral',
                            };
                            $isOut = str_starts_with((string) $m->qty_change, '-');
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="font-mono text-[11px] font-semibold text-slate-700">{{ $m->created_at->format('d/m/Y') }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $m->created_at->format('H:i') }} WIB</span>
                            </td>
                            <td>
                                <span class="badge {{ $badgeStyle }} font-mono">{{ $m->reference_type }}</span>
                            </td>
                            <td class="text-right num-tabular text-slate-500">
                                {{ number_format((float) $m->balance_before, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="text-right font-extrabold num-tabular {{ $isOut ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $isOut ? '' : '+' }}{{ (string) $m->qty_change }}
                            </td>
                            <td class="text-right font-bold num-tabular text-slate-900">
                                {{ number_format((float) $m->balance_after, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="text-right num-tabular text-slate-600">Rp {{ number_format((float) $m->unit_cost, 0, ',', '.') }}</td>
                            <td class="text-right font-bold num-tabular text-slate-800">Rp {{ number_format((float) $m->total_value, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[9px] font-extrabold text-slate-500">
                                        {{ mb_strtoupper(mb_substr($m->createdBy?->name ?? 'S', 0, 1)) }}
                                    </span>
                                    <span class="text-[11px] text-slate-500">{{ $m->createdBy?->name ?? 'Sistem' }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="list" class="h-6 w-6" /></span>
                                    <p class="text-sm font-bold text-slate-700">Belum ada pergerakan stok</p>
                                    <p class="mt-1 text-xs text-slate-400">Kartu stok akan terisi setelah ada restock, penjualan, atau mutasi.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
