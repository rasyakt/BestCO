@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Stok Opname"
        subtitle="Hitung stok fisik, analisis selisih, lalu setujui penyesuaian stok dan jurnalnya.">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Stok Opname</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.spoilage') }}" class="btn btn-secondary">
                <x-icon name="spoilage" class="h-4 w-4" />
                Barang Rusak
            </a>
            @if(auth()->user()?->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                <a href="{{ route('inventory.opname.create') }}" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Opname
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="stat-label">Total sesi opname</p>
            <p class="stat-value">{{ number_format((float) $totalSessions, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Seluruh riwayat penghitungan</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Menunggu persetujuan</p>
            <p class="stat-value text-amber-600">{{ number_format((float) $draftSessions, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Draf yang belum mengubah stok</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Nilai selisih kurang</p>
            <p class="stat-value text-rose-600">Rp {{ number_format((float) $totalShortage, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Beban selisih dari opname disetujui</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Nilai selisih lebih</p>
            <p class="stat-value text-emerald-600">Rp {{ number_format((float) $totalOverage, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Pendapatan selisih dari opname disetujui</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
        <form method="GET" action="{{ route('inventory.opname') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Cari</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Cari nomor opname atau catatan..."
                        class="input">
                </div>
            </div>
            <div class="sm:w-48">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="select input">
                    <option value="ALL" @selected($status === 'ALL')>Semua status</option>
                    <option value="DRAFT" @selected($status === 'DRAFT')>Draf</option>
                    <option value="APPROVED" @selected($status === 'APPROVED')>Disetujui</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filter</button>
                <a href="{{ route('inventory.opname') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Daftar sesi -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Riwayat stok opname</h2>
                <p class="card-subtitle">Setiap sesi menyimpan snapshot stok sistem dan hasil hitung fisik</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ $opnames->total() }} sesi</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">No. Opname &amp; Tanggal</th>
                        <th class="whitespace-nowrap text-center">Status</th>
                        <th class="whitespace-nowrap text-center">Item</th>
                        <th class="whitespace-nowrap text-right">Selisih Kurang</th>
                        <th class="whitespace-nowrap text-right">Selisih Lebih</th>
                        <th class="whitespace-nowrap">Petugas</th>
                        <th class="whitespace-nowrap">Persetujuan</th>
                        <th class="whitespace-nowrap text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $opname)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('inventory.opname.show', $opname) }}"
                                    class="block font-mono text-xs font-semibold text-slate-900 hover:text-brand-700">
                                    {{ $opname->opname_number }}
                                </a>
                                <span class="mt-0.5 block font-mono text-[10px] text-slate-400">
                                    {{ $opname->opname_date?->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-center">
                                @if($opname->status === \App\Enums\StockOpnameStatus::APPROVED)
                                    <span class="badge badge-success">Disetujui</span>
                                @else
                                    <span class="badge badge-warning">Draf</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-center font-mono text-xs text-slate-600">
                                {{ number_format((float) $opname->items_count, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs font-semibold text-rose-600 num-tabular">
                                {{ bccomp((string) $opname->total_shortage_value, '0.0000', 4) === 1
                                    ? 'Rp ' . number_format((float) $opname->total_shortage_value, 0, ',', '.')
                                    : '—' }}
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs font-semibold text-emerald-600 num-tabular">
                                {{ bccomp((string) $opname->total_overage_value, '0.0000', 4) === 1
                                    ? 'Rp ' . number_format((float) $opname->total_overage_value, 0, ',', '.')
                                    : '—' }}
                            </td>
                            <td class="whitespace-nowrap text-xs text-slate-600">
                                {{ $opname->createdBy?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap">
                                @if($opname->approvedBy)
                                    <span class="block text-xs font-medium text-slate-800">{{ $opname->approvedBy->name }}</span>
                                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">
                                        {{ $opname->approved_at?->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="badge badge-warning text-[10px]">Belum disetujui</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-center">
                                <a href="{{ route('inventory.opname.show', $opname) }}" class="btn btn-sm btn-secondary">
                                    <x-icon name="eye" class="h-3.5 w-3.5" />
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="scale" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada sesi stok opname</p>
                                    <p class="mt-1 text-xs text-slate-400">Buat sesi baru untuk mulai menghitung stok fisik.</p>
                                    @if(auth()->user()?->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                                        <a href="{{ route('inventory.opname.create') }}" class="btn btn-primary btn-sm mt-4">
                                            <x-icon name="plus" class="h-3.5 w-3.5" />
                                            Buat Opname Pertama
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opnames->hasPages())
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
