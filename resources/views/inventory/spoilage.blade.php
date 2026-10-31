@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Barang Rusak"
        subtitle="Catatan bahan yang terbuang saat produksi.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Barang Rusak</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Pindah stok
            </a>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Catat barang rusak
                <span class="kbd">F6</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="stat-label">Nilai kerugian</p>
            <p class="stat-value text-rose-600">Rp {{ number_format((float) $totalLossRupiah, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Dicatat sebagai beban kerusakan</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Jumlah kejadian</p>
            <p class="stat-value">{{ number_format((float) $totalIncidents, 0, ',', '.') }} <span class="text-sm font-medium text-slate-400">kali</span></p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Kertas macet, hasil cacat, laminating kusut</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Hasil gagal</p>
            <p class="stat-value text-amber-600">{{ number_format((float) $totalFailedOutput, 0, ',', '.') }} <span class="text-sm font-medium text-slate-400">lembar</span></p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Total produk yang tidak jadi</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
        <form method="GET" action="{{ route('inventory.spoilage') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Cari</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Cari nomor atau keterangan..."
                        class="input">
                </div>
            </div>

            <div class="w-full lg:w-56">
                <label for="category" class="form-label">Penyebab</label>
                <select id="category" name="category" class="select">
                    <option value="ALL" {{ $category === 'ALL' ? 'selected' : '' }}>Semua penyebab</option>
                    <option value="PAPER_JAM" {{ $category === 'PAPER_JAM' ? 'selected' : '' }}>Kertas macet</option>
                    <option value="INK_SMUDGE" {{ $category === 'INK_SMUDGE' ? 'selected' : '' }}>Tinta bergaris</option>
                    <option value="MACHINE_ERROR" {{ $category === 'MACHINE_ERROR' ? 'selected' : '' }}>Mesin rusak</option>
                    <option value="OPERATOR_ERROR" {{ $category === 'OPERATOR_ERROR' ? 'selected' : '' }}>Salah operator</option>
                    <option value="OTHER" {{ $category === 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filter</button>
                <a href="{{ route('inventory.spoilage') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat barang rusak</h2>
                <p class="card-subtitle">{{ $logs->total() }} kejadian</p>
            </div>
            <span class="text-[11px] text-slate-500">Bahan yang terbuang</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. bukti</th>
                        <th>Layanan</th>
                        <th>Penyebab</th>
                        <th class="text-right">Gagal</th>
                        <th>Bahan terbuang</th>
                        <th class="text-right">Kerugian</th>
                        <th>Pelapor</th>
                        <th>Shift</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $reasonLabel = match ($log->reason_category) {
                                'PAPER_JAM' => 'Kertas macet',
                                'INK_SMUDGE' => 'Tinta bergaris',
                                'PRINT_STREAK' => 'Hasil bergaris',
                                'MACHINE_ERROR' => 'Mesin rusak',
                                'LAMINATION_WRINKLE' => 'Laminating kusut',
                                'OPERATOR_ERROR' => 'Salah operator',
                                default => 'Lainnya',
                            };
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="font-mono text-[11px] font-medium text-slate-900">{{ $log->spoilage_number }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $log->logged_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="font-medium text-slate-800">{{ $log->service?->name ?? 'Etalase / lainnya' }}</td>
                            <td>
                                <span class="text-[11px] font-medium text-rose-600">{{ $reasonLabel }}</span>
                                @if($log->reason_description)
                                    <p class="mt-1 max-w-[220px] text-[11px] leading-relaxed text-slate-500">{{ $log->reason_description }}</p>
                                @endif
                            </td>
                            <td class="text-right font-semibold text-slate-800 num-tabular">{{ number_format((float) $log->failed_output_qty, 0, ',', '.') }}</td>
                            <td>
                                <div class="space-y-1">
                                    @foreach($log->items as $item)
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="font-medium text-rose-600 num-tabular">
                                                -{{ $item->product?->unit?->allow_decimal ? number_format((float) $item->qty_wasted, 4, ',', '.') : number_format((float) $item->qty_wasted, 0, ',', '.') }}
                                                {{ $item->product?->unit?->code }}
                                            </span>
                                            <span class="text-slate-400">{{ $item->product?->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right font-semibold text-rose-600 num-tabular">Rp {{ number_format((float) $log->total_spoilage_cost, 0, ',', '.') }}</td>
                            <td>
                                <p class="text-[11px] font-medium text-slate-700">{{ $log->reportedBy?->name ?? '-' }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">Guru: {{ $log->verifiedBy?->name ?? '-' }}</p>
                            </td>
                            <td><span class="chip">{{ $log->posShift?->shift_code ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="spoilage" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada barang rusak</p>
                                    <p class="mt-1 text-xs text-slate-400">Catat dari menu Kasir dengan tombol F6.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
