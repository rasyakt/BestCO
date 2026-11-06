@extends('layouts.app')

@section('content')
@php
    $formatQty = static function ($value): string {
        return rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',') ?: '0';
    };
    $formatSignedValue = static function ($value): string {
        $amount = (float) $value;

        if ($amount < 0) {
            return '-(Rp ' . number_format(abs($amount), 0, ',', '.') . ')';
        }

        return 'Rp ' . number_format($amount, 0, ',', '.');
    };
    $isDraft = $opname->status === \App\Enums\StockOpnameStatus::DRAFT;
    $canApprove = auth()->user()?->hasAnyRole(['superadmin', 'admin']) ?? false;
    $canDelete = $isDraft
        && ($canApprove || (int) auth()->id() === (int) $opname->created_by_user_id);
@endphp

<div class="space-y-6">
    <x-page-header
        title="Detail Stok Opname"
        subtitle="{{ $opname->opname_number }} &bull; {{ $opname->opname_date?->format('d/m/Y') }}">
        <x-slot:breadcrumb>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 font-medium text-brand-700 transition hover:text-brand-800">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                Persediaan
            </a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <a href="{{ route('inventory.opname') }}" class="font-medium text-brand-700 transition hover:text-brand-800">Stok Opname</a>
            <x-icon name="chevron-right" class="h-3 w-3 text-slate-300" />
            <span class="text-slate-500">Detail</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            <a href="{{ route('inventory.opname') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>

            @if($canDelete)
                <form method="POST" action="{{ route('inventory.opname.destroy', $opname) }}"
                    x-data
                    @submit.prevent="window.bestcoDialog.confirm({
                        title: 'Hapus Draf Opname?',
                        message: 'Draf opname {{ $opname->opname_number }} akan dihapus permanen.',
                        detail: 'Draf belum mengubah stok, sehingga tidak ada penyesuaian yang perlu dibatalkan.',
                        type: 'danger',
                        confirmText: 'Ya, Hapus',
                        cancelText: 'Batal',
                        onConfirm: () => $el.submit()
                    })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <x-icon name="trash" class="h-4 w-4" />
                        Hapus Draf
                    </button>
                </form>
            @endif

            @if($isDraft && $canApprove)
                <form method="POST" action="{{ route('inventory.opname.approve', $opname) }}"
                    x-data
                    @submit.prevent="window.bestcoDialog.confirm({
                        title: 'Setujui Stok Opname?',
                        message: 'Stok akan langsung disesuaikan dengan hasil hitung fisik dan jurnal penyesuaian dibuat otomatis.',
                        detail: 'Pastikan hasil hitung sudah final. Tindakan ini tidak dapat dibatalkan.',
                        type: 'warning',
                        confirmText: 'Ya, Setujui',
                        cancelText: 'Batal',
                        onConfirm: () => $el.submit()
                    })">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Setujui &amp; Sesuaikan Stok
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($isDraft)
        <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/80 p-4 text-amber-900">
            <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div class="text-xs">
                <p class="font-semibold">Sesi ini masih berupa draf.</p>
                <p class="mt-0.5 text-amber-800">
                    Stok belum berubah. Setelah disetujui {{ $canApprove ? 'oleh Anda' : 'oleh Superadmin atau Admin' }},
                    sistem akan menerapkan selisih ke stok, mencatat kartu stok, dan membuat jurnal penyesuaian otomatis.
                </p>
            </div>
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="stat-label">Status</p>
            <p class="mt-2">
                @if($opname->status === \App\Enums\StockOpnameStatus::APPROVED)
                    <span class="badge badge-success">Disetujui</span>
                @else
                    <span class="badge badge-warning">Draf</span>
                @endif
            </p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                {{ $opname->approved_at ? 'Disetujui ' . $opname->approved_at->format('d/m/Y H:i') : 'Belum mengubah stok' }}
            </p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Item dihitung</p>
            <p class="stat-value">{{ number_format((float) $opname->items_count, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Barang dengan hasil hitung fisik</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Nilai selisih kurang</p>
            <p class="stat-value text-rose-600">Rp {{ number_format((float) $opname->total_shortage_value, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Dibebankan ke Beban Selisih Persediaan</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Nilai selisih lebih</p>
            <p class="stat-value text-emerald-600">Rp {{ number_format((float) $opname->total_overage_value, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Diakui sebagai Pendapatan Selisih Persediaan</p>
        </div>
    </div>

    <!-- Informasi sesi -->
    <div class="card p-5">
        <div class="grid grid-cols-1 gap-5 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Dibuat oleh</p>
                <p class="mt-1 font-medium text-slate-800">{{ $opname->createdBy?->name ?? '-' }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ $opname->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Disetujui oleh</p>
                <p class="mt-1 font-medium text-slate-800">{{ $opname->approvedBy?->name ?? 'Belum disetujui' }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    {{ $opname->approved_at?->format('d/m/Y H:i') ?? 'Menunggu persetujuan admin' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Jurnal penyesuaian</p>
                <p class="mt-1 font-mono text-xs font-semibold text-slate-800">
                    {{ $opname->journalEntry?->journal_number ?? '—' }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    {{ $opname->journal_entry_id ? 'Terposting otomatis' : 'Belum ada jurnal' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Catatan</p>
                <p class="mt-1 text-slate-700">{{ $opname->notes ?: '—' }}</p>
            </div>
        </div>
    </div>

    <!-- Item opname -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Rincian hasil hitung</h2>
                <p class="card-subtitle">Perbandingan stok sistem saat dihitung dengan hasil hitung fisik</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ $items->count() }} barang</span>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap">Produk / Barang</th>
                        <th class="whitespace-nowrap text-center">Tipe</th>
                        <th class="whitespace-nowrap text-right">Stok Sistem</th>
                        <th class="whitespace-nowrap text-right">Qty Fisik</th>
                        <th class="whitespace-nowrap text-right">Selisih</th>
                        <th class="whitespace-nowrap text-right">Harga Pokok</th>
                        <th class="whitespace-nowrap text-right">Nilai Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <span class="block text-xs font-semibold text-slate-800">{{ $item->product?->name ?? 'Produk dihapus' }}</span>
                                <span class="mt-0.5 block font-mono text-[10px] text-slate-400">
                                    {{ $item->product?->sku ?? '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-center">
                                <span class="chip text-[10px]">
                                    {{ $item->product?->inventory_type?->value === 'RETAIL' ? 'Etalase' : 'Bahan Baku' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs text-slate-600 num-tabular">
                                {{ $formatQty($item->system_qty) }}
                                <span class="ml-1 text-[10px] text-slate-400">{{ $item->product?->unit?->code }}</span>
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs font-semibold text-slate-800 num-tabular">
                                {{ $formatQty($item->counted_qty) }}
                                <span class="ml-1 text-[10px] font-normal text-slate-400">{{ $item->product?->unit?->code }}</span>
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs font-bold num-tabular
                                {{ $item->isShortage() ? 'text-rose-600' : ($item->isOverage() ? 'text-emerald-600' : 'text-slate-500') }}">
                                {{ bccomp($item->difference_qty, '0.0000', 4) === 1 ? '+' : '' }}{{ $formatQty($item->difference_qty) }}
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs text-slate-600 num-tabular">
                                Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right font-mono text-xs font-semibold num-tabular
                                {{ bccomp($item->difference_value, '0.0000', 4) === -1 ? 'text-rose-600' : (bccomp($item->difference_value, '0.0000', 4) === 1 ? 'text-emerald-600' : 'text-slate-500') }}">
                                {{ $formatSignedValue($item->difference_value) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="scale" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Tidak ada item opname</p>
                                    <p class="mt-1 text-xs text-slate-400">Sesi ini belum memiliki hasil hitung fisik.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right">Total nilai selisih</td>
                        <td class="text-right font-mono num-tabular">
                            {{ $formatSignedValue(bcsub((string) $opname->total_overage_value, (string) $opname->total_shortage_value, 4)) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
