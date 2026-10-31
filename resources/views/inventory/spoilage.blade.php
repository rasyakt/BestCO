@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        eyebrow="Audit Produksi"
        title="Barang Rusak & Gagal Produksi"
        subtitle="Monitoring pemotongan bahan baku akibat paper jam, tinta cacat, dan laminating kusut, beserta posting otomatis ke beban kerusakan jasa.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-bold text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Spoilage</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.mutations') }}" class="btn btn-secondary">
                <x-icon name="swap" class="h-4 w-4" />
                Mutasi Internal
            </a>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Catat Spoilage
                <span class="kbd">F6</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Beban Kerugian Produksi</p>
                    <p class="stat-value text-rose-600">Rp {{ number_format((float) $totalLossRupiah, 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-rose-50 text-rose-600 ring-rose-100"><x-icon name="trend-down" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Terjurnal ke akun Beban Kerusakan (5-2101)</p>
        </div>

        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Insiden Gagal Produksi</p>
                    <p class="stat-value">{{ number_format((float) $totalIncidents, 0, ',', '.') }} <span class="text-sm font-bold text-slate-400">kali</span></p>
                </div>
                <span class="stat-icon bg-amber-50 text-amber-700 ring-amber-100"><x-icon name="spoilage" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Kertas macet, cacat toner, plastik kusut</p>
        </div>

        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Output Gagal</p>
                    <p class="stat-value text-amber-600">{{ number_format((float) $totalFailedOutput, 0, ',', '.') }} <span class="text-sm font-bold text-slate-400">lbr</span></p>
                </div>
                <span class="stat-icon bg-slate-100 text-slate-600 ring-slate-200"><x-icon name="list" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Akumulasi produk rusak yang diaudit</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
        <form method="GET" action="{{ route('inventory.spoilage') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Pencarian</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Cari nomor bukti atau alasan kerusakan..."
                        class="input">
                </div>
            </div>

            <div class="w-full lg:w-56">
                <label for="category" class="form-label">Kategori</label>
                <select id="category" name="category" class="select">
                    <option value="ALL" {{ $category === 'ALL' ? 'selected' : '' }}>Semua Kategori</option>
                    <option value="PAPER_JAM" {{ $category === 'PAPER_JAM' ? 'selected' : '' }}>Paper Jam</option>
                    <option value="INK_SMUDGE" {{ $category === 'INK_SMUDGE' ? 'selected' : '' }}>Tinta Bergaris</option>
                    <option value="MACHINE_ERROR" {{ $category === 'MACHINE_ERROR' ? 'selected' : '' }}>Kerusakan Mesin</option>
                    <option value="OPERATOR_ERROR" {{ $category === 'OPERATOR_ERROR' ? 'selected' : '' }}>Kesalahan Operator</option>
                    <option value="OTHER" {{ $category === 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filter</button>
                <a href="{{ route('inventory.spoilage') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Log Spoilage</h2>
                <p class="card-subtitle">{{ $logs->total() }} insiden tercatat</p>
            </div>
            <span class="badge badge-danger">Audit Produksi</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Bukti &amp; Waktu</th>
                        <th>Layanan / Sumber</th>
                        <th>Kategori &amp; Alasan</th>
                        <th class="text-right">Gagal</th>
                        <th>Bahan Terbuang</th>
                        <th class="text-right">Beban Kerugian</th>
                        <th>Pelapor / Approval</th>
                        <th>Shift</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="font-mono text-[11px] font-bold text-slate-900">{{ $log->spoilage_number }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $log->logged_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $log->service?->name ?? 'Etalase / Lainnya' }}</p>
                                <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $log->spoilage_source }}</p>
                            </td>
                            <td>
                                <span class="badge badge-danger font-mono">{{ $log->reason_category }}</span>
                                @if($log->reason_description)
                                    <p class="mt-1 max-w-[220px] text-[11px] leading-relaxed text-slate-500">{{ $log->reason_description }}</p>
                                @endif
                            </td>
                            <td class="text-right font-extrabold text-slate-800 num-tabular">{{ number_format((float) $log->failed_output_qty, 0, ',', '.') }}</td>
                            <td>
                                <div class="space-y-1">
                                    @foreach($log->items as $item)
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="font-bold text-rose-600 num-tabular">
                                                -{{ $item->product?->unit?->allow_decimal ? number_format((float) $item->qty_wasted, 4, ',', '.') : number_format((float) $item->qty_wasted, 0, ',', '.') }}
                                                {{ $item->product?->unit?->code }}
                                            </span>
                                            <span class="text-slate-400">{{ $item->product?->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right font-extrabold text-rose-600 num-tabular">Rp {{ number_format((float) $log->total_spoilage_cost, 0, ',', '.') }}</td>
                            <td>
                                <p class="text-[11px] font-semibold text-slate-700">{{ $log->reportedBy?->name ?? '-' }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">Verif: {{ $log->verifiedBy?->name ?? 'Guru Piket' }}</p>
                            </td>
                            <td><span class="chip">{{ $log->posShift?->shift_code ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="spoilage" class="h-6 w-6" /></span>
                                    <p class="text-sm font-bold text-slate-700">Belum ada catatan spoilage</p>
                                    <p class="mt-1 text-xs text-slate-400">Catat kegagalan produksi dari terminal POS dengan pintasan F6.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
