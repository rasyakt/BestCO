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

        <div class="w-full">
            <table class="data-table w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">No. Bukti & Waktu</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Layanan & Hasil Gagal</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Penyebab</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Bahan Terbuang</th>
                        <th class="text-right whitespace-nowrap px-3 py-2.5 w-px">Kerugian</th>
                        <th class="text-left whitespace-nowrap px-3 py-2.5">Pelapor & Shift</th>
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

                            $cleanDesc = trim($log->reason_description ?? '');
                            if ($cleanDesc && stripos($cleanDesc, $reasonLabel) === 0) {
                                $cleanDesc = trim(preg_replace('/^' . preg_quote($reasonLabel, '/') . '\s*[\/\-:]?\s*/i', '', $cleanDesc));
                            }
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-mono text-xs font-semibold text-slate-900 whitespace-nowrap block">{{ $log->spoilage_number }}</span>
                                <span class="font-mono text-[10px] text-slate-400 whitespace-nowrap block mt-0.5">{{ $log->logged_at?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="font-semibold text-xs text-slate-900 whitespace-nowrap block">{{ $log->service?->name ?? 'Etalase / lainnya' }}</span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 whitespace-nowrap mt-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    {{ number_format((float) $log->failed_output_qty, 0, ',', '.') }} lembar gagal
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="badge badge-danger text-[10px] whitespace-nowrap px-1.5 py-0.5">{{ $reasonLabel }}</span>
                                @if($cleanDesc)
                                    <span class="text-[11px] text-slate-500 whitespace-nowrap block mt-0.5" title="{{ $log->reason_description }}">
                                        {{ $cleanDesc }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <div class="space-y-0.5">
                                    @foreach($log->items as $item)
                                        @php
                                            $q = rtrim(rtrim((string) $item->qty_wasted, '0'), '.');
                                            $u = $item->product?->unit?->code ?? '';
                                            $name = trim(preg_replace('/\[.*?\]/', '', $item->product?->name ?? ''));
                                            $name = str_ireplace(['Kertas HVS', 'Tinta Toner Fotocopy', 'Plastik Laminating'], ['HVS', 'Toner', 'Laminating'], $name);
                                        @endphp
                                        <div class="flex items-center gap-1.5 text-xs whitespace-nowrap">
                                            <span class="font-mono text-[11px] font-semibold text-rose-600 whitespace-nowrap">-{{ $q }} {{ $u }}</span>
                                            <span class="text-[11px] text-slate-600 whitespace-nowrap">{{ $name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-right whitespace-nowrap px-3 py-2.5">
                                <span class="font-mono text-xs font-semibold text-rose-600 whitespace-nowrap block num-tabular">
                                    Rp {{ number_format((float) $log->total_spoilage_cost, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-slate-400 whitespace-nowrap block mt-0.5">Beban rugi</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">
                                <div class="flex items-center gap-1.5 whitespace-nowrap">
                                    <span class="font-medium text-xs text-slate-800 whitespace-nowrap">{{ $log->reportedBy?->name ?? '-' }}</span>
                                    @if($log->verifiedBy)
                                        <span class="text-[10px] text-slate-400 font-normal whitespace-nowrap">(Guru: {{ $log->verifiedBy->name }})</span>
                                    @endif
                                </div>
                                <div class="mt-0.5">
                                    <span class="chip text-[9px] font-mono whitespace-nowrap">{{ $log->posShift?->shift_code ?? '-' }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
