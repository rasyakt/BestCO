@extends('layouts.app')

@section('content')
@php
    $greeting = match (true) {
        now()->hour < 11 => 'Selamat pagi',
        now()->hour < 15 => 'Selamat siang',
        now()->hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = explode(' ', auth()->user()->name)[0];
    $weeklyTotal = array_sum(array_column($salesTrend, 'total'));
    $maxTotal = max(array_column($salesTrend, 'total')) ?: 1;
@endphp

<div class="space-y-6">
    <x-page-header
        :title="$greeting . ', ' . $firstName"
        subtitle="Ringkasan kasir, kas, dan stok hari ini.">
        <x-slot:actions>
            <span class="hidden text-[11px] text-slate-500 sm:inline">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </span>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="pos" class="h-4 w-4" />
                Buka kasir
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="stat-label">Penjualan hari ini</p>
            <p class="stat-value">Rp {{ number_format((float) $todaySalesTotal, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Dari <strong class="font-semibold text-slate-700">{{ $todayTrxCount }}</strong> transaksi selesai
            </p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Shift kasir</p>
            @if($activeShift)
                <p class="mt-1.5 font-mono text-lg font-semibold tracking-tight text-slate-900">{{ $activeShift->shift_code }}</p>
                <p class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                    <span class="truncate">Guru: <strong class="font-semibold text-slate-700">{{ $activeShift->supervisorTeacher->name }}</strong></span>
                    <a href="{{ route('shift.index') }}" class="link shrink-0">Lihat</a>
                </p>
            @else
                <p class="mt-1.5 text-lg font-semibold text-amber-600">Belum dibuka</p>
                <p class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                    <span>Kasir belum bisa dipakai.</span>
                    <a href="{{ route('shift.index') }}" class="link shrink-0">Buka</a>
                </p>
            @endif
        </div>

        <div class="card p-5">
            <p class="stat-label">Kas di laci</p>
            <p class="stat-value text-brand-700">Rp {{ number_format((float) ($activeShift?->system_expected_cash ?? 0), 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Kas awal ditambah penjualan tunai
            </p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Stok menipis</p>
            <p class="stat-value {{ $lowStockProducts->count() > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ $lowStockProducts->count() }} <span class="text-sm font-medium text-slate-400">barang</span>
            </p>
            <p class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                <span>{{ $lowStockProducts->count() > 0 ? 'Perlu ditambah stoknya' : 'Semua stok aman' }}</span>
                <a href="{{ route('inventory.index') }}" class="link shrink-0">Lihat</a>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <!-- Grafik penjualan -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Penjualan 7 hari terakhir</h2>
                        <p class="card-subtitle">Transaksi yang sudah selesai</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] text-slate-500">Total</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-900 num-tabular">Rp {{ number_format((float) $weeklyTotal, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="relative h-56">
                        <div class="pointer-events-none absolute inset-x-0 bottom-9 top-1 flex flex-col justify-between">
                            @for($i = 0; $i < 4; $i++)
                                <div class="border-t border-dashed border-slate-100"></div>
                            @endfor
                        </div>

                        <div class="relative z-10 grid h-full grid-cols-7 gap-1.5 sm:gap-3">
                            @foreach($salesTrend as $day)
                                @php
                                    $heightPercent = max(4, min(100, (int) (($day['total'] / $maxTotal) * 100)));
                                    $isToday = $loop->last;
                                @endphp
                                <div class="group flex h-full flex-col items-center justify-end">
                                    <div class="relative flex w-full flex-1 items-end justify-center">
                                        <div class="relative w-full max-w-[40px] rounded-t transition-colors
                                            {{ $isToday ? 'bg-brand-600' : 'bg-brand-200 group-hover:bg-brand-300' }}"
                                            style="height: {{ $heightPercent }}%;">
                                            <span class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 transition group-hover:opacity-100 num-tabular">
                                                Rp {{ number_format((float) $day['total'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="mt-2 flex h-7 flex-col items-center justify-center leading-tight">
                                        <span class="text-[10px] font-medium {{ $isToday ? 'text-brand-700' : 'text-slate-500' }}">{{ $day['day'] }}</span>
                                        <span class="text-[9px] text-slate-400">{{ $day['date_short'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-brand-600"></span> Hari ini</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-brand-200"></span> Hari sebelumnya</span>
                        <span class="ml-auto">Arahkan kursor ke batang untuk melihat nominal</span>
                    </div>
                </div>
            </div>

            <!-- Transaksi terakhir -->
            <div class="table-shell">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="card-title">Transaksi terakhir</h2>
                        <p class="card-subtitle">Penjualan terbaru dari semua shift</p>
                    </div>
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">
                        Lihat semua
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Kasir</th>
                                <th>Bayar</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>
                                        <span class="font-mono text-[11px] font-medium text-slate-800">{{ $tx->invoice_number }}</span>
                                        <span class="mt-0.5 block text-[10px] text-slate-400">{{ $tx->transaction_date->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="font-medium text-slate-700">{{ $tx->customer_name }}</td>
                                    <td class="text-slate-600">{{ $tx->cashier->name }}</td>
                                    <td class="text-[11px] text-slate-500">{{ $tx->payment_method->value === 'CASH' ? 'Tunai' : 'QRIS' }}</td>
                                    <td class="text-right font-semibold text-slate-900 num-tabular">
                                        Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $tx->status === 'COMPLETED' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $tx->status === 'COMPLETED' ? 'Selesai' : 'Dibatalkan' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><x-icon name="receipt" class="h-5 w-5" /></span>
                                            <p class="text-xs font-semibold text-slate-600">Belum ada transaksi</p>
                                            <p class="mt-1 text-[11px] text-slate-400">Transaksi kasir akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <!-- Stok menipis -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Stok menipis</h2>
                        <p class="card-subtitle">Di bawah batas minimum</p>
                    </div>
                    <span class="text-[11px] font-medium {{ $lowStockProducts->count() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $lowStockProducts->count() }} barang
                    </span>
                </div>

                <div class="card-body space-y-3">
                    @forelse($lowStockProducts as $lp)
                        @php
                            $stock = (float) $lp->current_stock;
                            $min = max((float) ($lp->min_stock_alert ?? 10), 1);
                            $percent = min(100, max(4, ($stock / $min) * 100));
                            $isCritical = $stock <= 0 || $stock <= $min * 0.5;
                        @endphp
                        <div class="rounded-md border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-800">{{ $lp->name }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $lp->sku }}</p>
                                </div>
                                <span class="shrink-0 text-[11px] font-semibold {{ $isCritical ? 'text-rose-600' : 'text-amber-600' }} num-tabular">
                                    {{ number_format($stock, 0, ',', '.') }} {{ $lp->unit?->code }}
                                </span>
                            </div>
                            <div class="mt-2.5 progress-track">
                                <div class="progress-bar {{ $isCritical ? 'bg-rose-500' : 'bg-amber-400' }}" style="width: {{ $percent }}%"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between text-[10px] text-slate-400">
                                <span>Minimum {{ number_format($min, 0, ',', '.') }} {{ $lp->unit?->code }}</span>
                                <a href="{{ route('inventory.index') }}" class="font-semibold text-brand-700 hover:underline">Tambah stok</a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-8">
                            <span class="empty-state-icon bg-emerald-50 text-emerald-600"><x-icon name="check-circle" class="h-5 w-5" /></span>
                            <p class="text-xs font-semibold text-slate-600">Stok aman</p>
                            <p class="mt-1 text-[11px] text-slate-400">Tidak ada barang di bawah batas minimum.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Petugas piket -->
            @if($activeShift)
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Petugas piket</h2>
                            <p class="card-subtitle">Sedang bertugas di shift ini</p>
                        </div>
                        <span class="font-mono text-[11px] text-slate-400">{{ $activeShift->shift_code }}</span>
                    </div>

                    <div class="card-body space-y-2">
                        @foreach($activeShift->attendances as $att)
                            <div class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2.5">
                                <span class="avatar h-7 w-7">{{ mb_strtoupper(mb_substr($att->activeOperator->name, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-800">{{ $att->activeOperator->name }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">Slot {{ $att->slot_number }}</p>
                                </div>
                                <span class="text-[10px] font-medium text-emerald-600">Aktif</span>
                            </div>
                        @endforeach

                        <a href="{{ route('piket.index') }}" class="btn btn-secondary btn-sm mt-1 w-full">
                            Lihat semua presensi
                        </a>
                    </div>
                </div>
            @else
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Shift belum dibuka</h2>
                    <p class="mt-1 text-[12px] leading-relaxed text-slate-500">Buka shift dulu agar kasir dan presensi petugas bisa dipakai.</p>
                    <a href="{{ route('shift.index') }}" class="btn btn-primary btn-sm mt-4">
                        Buka shift
                        <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                </div>
            @endif

            <!-- Menu cepat -->
            <div class="card p-5">
                <h2 class="card-title">Menu cepat</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    <a href="{{ route('transactions.index') }}" class="flex items-center justify-between py-2.5 text-xs font-medium text-slate-600 transition hover:text-brand-700">
                        Riwayat transaksi
                        <x-icon name="arrow-right" class="h-3.5 w-3.5 text-slate-400" />
                    </a>
                    <a href="{{ route('inventory.index') }}" class="flex items-center justify-between py-2.5 text-xs font-medium text-slate-600 transition hover:text-brand-700">
                        Persediaan
                        <x-icon name="arrow-right" class="h-3.5 w-3.5 text-slate-400" />
                    </a>
                    @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                        <a href="{{ route('reports.index') }}" class="flex items-center justify-between py-2.5 text-xs font-medium text-slate-600 transition hover:text-brand-700">
                            Laporan
                            <x-icon name="arrow-right" class="h-3.5 w-3.5 text-slate-400" />
                        </a>
                    @endif
                    <a href="{{ route('piket.index') }}" class="flex items-center justify-between py-2.5 text-xs font-medium text-slate-600 transition hover:text-brand-700">
                        Presensi piket
                        <x-icon name="arrow-right" class="h-3.5 w-3.5 text-slate-400" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
