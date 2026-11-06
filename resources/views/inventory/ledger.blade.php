@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        :title="'Kartu stok: ' . $product->name"
        subtitle="Riwayat keluar-masuk stok barang ini.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="font-mono text-slate-500">{{ $product->sku }}</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <div class="card flex items-center gap-4 px-4 py-3">
                <div>
                    <p class="text-[10px] text-slate-400">Stok saat ini</p>
                    <p class="mt-0.5 text-lg font-semibold text-slate-900 num-tabular">
                        {{ $product->unit?->allow_decimal ? number_format((float) $product->current_stock, 4, ',', '.') : number_format((float) $product->current_stock, 0, ',', '.') }}
                        <span class="text-[10px] font-medium text-slate-400">{{ $product->unit?->code }}</span>
                    </p>
                </div>
                <span class="h-9 w-px bg-slate-200"></span>
                <div>
                    <p class="text-[10px] text-slate-400">HPP rata-rata</p>
                    <p class="mt-0.5 text-lg font-semibold text-brand-700 num-tabular">Rp {{ number_format((float) $product->average_cost, 0, ',', '.') }}</p>
                </div>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Riwayat -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat pergerakan stok</h2>
                <p class="card-subtitle">{{ $movements->total() }} catatan</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ $product->inventory_type->value === 'RETAIL' ? 'Barang etalase' : 'Bahan baku' }}</span>
        </div>

        <div class="w-full">
            <table class="data-table w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Waktu</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Jenis Transaksi</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5">Saldo Awal</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5">Perubahan</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5">Saldo Akhir</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5">HPP Rata-rata</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5">Total Nilai</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        @php
                            [$badgeStyle, $typeLabel] = match ($m->reference_type) {
                                'PURCHASE' => ['badge-success', 'Pembelian'],
                                'INTERNAL_MUTATION_IN' => ['badge-brand', 'Masuk dari etalase'],
                                'INTERNAL_MUTATION_OUT' => ['badge-warning', 'Keluar ke bahan baku'],
                                'POS_SALE' => ['badge-info', 'Penjualan'],
                                'SERVICE_BOM' => ['badge-purple', 'Pemakaian bahan'],
                                'SPOILAGE' => ['badge-danger', 'Barang rusak'],
                                'OPNAME' => ['badge-warning', 'Penyesuaian opname'],
                                'VOID_RESTORE' => ['badge-success', 'Pembatalan'],
                                default => ['badge-neutral', 'Lainnya'],
                            };
                            $isOut = str_starts_with((string) $m->qty_change, '-');
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-mono text-xs font-semibold text-slate-800 whitespace-nowrap block">{{ $m->created_at->format('d/m/Y') }}</span>
                                <span class="mt-0.5 block font-mono text-[10px] text-slate-400 whitespace-nowrap">{{ $m->created_at->format('H:i') }} WIB</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="badge {{ $badgeStyle }} whitespace-nowrap text-[11px]">{{ $typeLabel }}</span>
                            </td>
                            <td class="text-right num-tabular text-slate-600 text-xs font-mono whitespace-nowrap px-3 py-2.5">
                                {{ number_format((float) $m->balance_before, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="text-right font-semibold num-tabular text-xs font-mono whitespace-nowrap px-3 py-2.5 {{ $isOut ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $isOut ? '' : '+' }}{{ (string) $m->qty_change }}
                            </td>
                            <td class="text-right font-semibold num-tabular text-slate-900 text-xs font-mono whitespace-nowrap px-3 py-2.5">
                                {{ number_format((float) $m->balance_after, $product->unit?->allow_decimal ? 4 : 0, ',', '.') }}
                            </td>
                            <td class="text-right num-tabular text-slate-600 text-xs font-mono whitespace-nowrap px-3 py-2.5">Rp {{ number_format((float) $m->unit_cost, 0, ',', '.') }}</td>
                            <td class="text-right font-semibold num-tabular text-slate-800 text-xs font-mono whitespace-nowrap px-3 py-2.5">Rp {{ number_format((float) $m->total_value, 0, ',', '.') }}</td>
                            <td class="text-xs text-slate-600 whitespace-nowrap px-3 py-2.5">{{ $m->createdBy?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="list" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada pergerakan stok</p>
                                    <p class="mt-1 text-xs text-slate-400">Catatan muncul setelah ada pembelian, penjualan, atau pemakaian bahan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
