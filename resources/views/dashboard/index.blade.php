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
        eyebrow="Executive Overview"
        :title="$greeting . ', ' . $firstName"
        subtitle="Ringkasan performa operasional, kas, dan persediaan Teaching Factory MPLB hari ini.">
        <x-slot:actions>
            <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-[11px] font-semibold text-slate-500 sm:flex">
                <x-icon name="calendar" class="h-4 w-4 text-slate-400" />
                {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </div>
            <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                <x-icon name="pos" class="h-4 w-4" />
                Buka Terminal Kasir
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Key metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Omzet -->
        <div class="card card-hover flex flex-col p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Omzet Hari Ini</p>
                    <p class="stat-value">Rp {{ number_format((float) $todaySalesTotal, 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-brand-50 text-brand-700 ring-brand-100">
                    <x-icon name="wallet" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-auto flex items-center gap-2 border-t border-slate-100 pt-3 text-[11px]">
                <span class="badge badge-success">
                    <x-icon name="trend-up" class="h-3 w-3" />
                    Live
                </span>
                <span class="text-slate-500">dari <strong class="font-bold text-slate-700">{{ $todayTrxCount }}</strong> transaksi selesai</span>
            </div>
        </div>

        <!-- Shift -->
        <div class="card card-hover flex flex-col p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Shift Operasional</p>
                    @if($activeShift)
                        <p class="mt-3 flex items-center gap-2 whitespace-nowrap text-xl font-extrabold tracking-tight text-slate-900">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            </span>
                            {{ $activeShift->shift_code }}
                        </p>
                    @else
                        <p class="mt-3 text-xl font-extrabold tracking-tight text-amber-600">Belum Dibuka</p>
                    @endif
                </div>
                <span class="stat-icon {{ $activeShift ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
                    <x-icon name="clock" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3 text-[11px]">
                @if($activeShift)
                    <span class="truncate text-slate-500">Guru: <strong class="font-bold text-slate-700">{{ $activeShift->supervisorTeacher->name }}</strong></span>
                    <a href="{{ route('shift.index') }}" class="link shrink-0">Detail</a>
                @else
                    <span class="text-slate-500">Mulai operasional kasir hari ini</span>
                    <a href="{{ route('shift.index') }}" class="link shrink-0">Buka &rarr;</a>
                @endif
            </div>
        </div>

        <!-- Kas laci -->
        <div class="card card-hover flex flex-col p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Ekspektasi Kas Laci</p>
                    <p class="stat-value text-brand-700">Rp {{ number_format((float) ($activeShift?->system_expected_cash ?? 0), 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-indigo-50 text-indigo-700 ring-indigo-100">
                    <x-icon name="cash" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-auto border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Saldo awal + penjualan tunai shift aktif
            </div>
        </div>

        <!-- Stok kritis -->
        <div class="card card-hover flex flex-col p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Stok Menipis</p>
                    <p class="stat-value {{ $lowStockProducts->count() > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                        {{ $lowStockProducts->count() }} <span class="text-sm font-bold text-slate-400">item</span>
                    </p>
                </div>
                <span class="stat-icon {{ $lowStockProducts->count() > 0 ? 'bg-rose-50 text-rose-600 ring-rose-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }}">
                    <x-icon name="spoilage" class="h-5 w-5" />
                </span>
            </div>
            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3 text-[11px]">
                <span class="text-slate-500">{{ $lowStockProducts->count() > 0 ? 'Perlu restock segera' : 'Seluruh stok aman' }}</span>
                <a href="{{ route('inventory.index') }}" class="link shrink-0">Persediaan</a>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <!-- Sales trend -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Tren Penjualan</h2>
                        <p class="card-subtitle">Omzet transaksi selesai dalam 7 hari terakhir</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Total 7 hari</p>
                            <p class="mt-0.5 text-sm font-extrabold text-slate-900 num-tabular">Rp {{ number_format((float) $weeklyTotal, 0, ',', '.') }}</p>
                        </div>
                        <span class="badge badge-brand">Auto-sync</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="relative h-60">
                        <!-- Grid lines -->
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
                                        <div class="relative w-full max-w-[44px] rounded-t-lg transition-all duration-300
                                            {{ $isToday ? 'bg-gradient-to-t from-brand-700 to-brand-400' : 'bg-gradient-to-t from-brand-200 to-brand-100 group-hover:from-brand-600 group-hover:to-brand-400' }}"
                                            style="height: {{ $heightPercent }}%;">
                                            <span class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2 py-1 text-[9px] font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 num-tabular">
                                                Rp {{ number_format((float) $day['total'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="mt-2 flex h-7 flex-col items-center justify-center leading-tight">
                                        <span class="text-[9px] font-bold {{ $isToday ? 'text-brand-700' : 'text-slate-500' }} sm:text-[10px]">{{ $day['day'] }}</span>
                                        <span class="text-[8px] font-semibold text-slate-400 sm:text-[9px]">{{ $day['date_short'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-100 pt-3 text-[10px] font-semibold text-slate-400">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-600"></span> Hari ini</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-200"></span> Hari sebelumnya</span>
                        <span class="ml-auto inline-flex items-center gap-1.5"><x-icon name="info" class="h-3 w-3" /> Arahkan kursor ke batang untuk melihat nominal</span>
                    </div>
                </div>
            </div>

            <!-- Recent transactions -->
            <div class="table-shell">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="card-title">Transaksi Terakhir</h2>
                        <p class="card-subtitle">Aktivitas kasir terbaru di seluruh shift</p>
                    </div>
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">
                        Lihat semua
                        <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Pelanggan</th>
                                <th>Kasir</th>
                                <th>Metode</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td>
                                        <span class="font-mono text-[11px] font-bold text-slate-800">{{ $tx->invoice_number }}</span>
                                        <span class="mt-0.5 block text-[10px] text-slate-400">{{ $tx->transaction_date->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="font-semibold text-slate-700">{{ $tx->customer_name }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[9px] font-extrabold text-slate-500">
                                                {{ mb_strtoupper(mb_substr($tx->cashier->name, 0, 1)) }}
                                            </span>
                                            <span class="text-slate-600">{{ $tx->cashier->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral font-mono">{{ $tx->payment_method->value }}</span>
                                    </td>
                                    <td class="text-right font-bold text-slate-900 num-tabular">
                                        Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $tx->status === 'COMPLETED' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $tx->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <span class="empty-state-icon"><x-icon name="receipt" class="h-5 w-5" /></span>
                                            <p class="text-xs font-bold text-slate-600">Belum ada transaksi</p>
                                            <p class="mt-1 text-[11px] text-slate-400">Transaksi kasir akan tampil di sini secara otomatis.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6 xl:col-span-4">
            <!-- Low stock -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Peringatan Stok</h2>
                        <p class="card-subtitle">Item di bawah ambang minimum</p>
                    </div>
                    <span class="badge {{ $lowStockProducts->count() > 0 ? 'badge-danger' : 'badge-success' }}">
                        {{ $lowStockProducts->count() > 0 ? 'Kritis' : 'Aman' }}
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
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-slate-800">{{ $lp->name }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $lp->sku }} &bull; {{ $lp->inventory_type->value }}</p>
                                </div>
                                <span class="badge shrink-0 {{ $isCritical ? 'badge-danger' : 'badge-warning' }}">
                                    {{ number_format($stock, 0, ',', '.') }} {{ $lp->unit?->code }}
                                </span>
                            </div>
                            <div class="mt-2.5 progress-track">
                                <div class="progress-bar {{ $isCritical ? 'bg-rose-500' : 'bg-amber-400' }}" style="width: {{ $percent }}%"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between text-[10px] text-slate-400">
                                <span>Minimum {{ number_format($min, 0, ',', '.') }} {{ $lp->unit?->code }}</span>
                                <a href="{{ route('inventory.index') }}" class="font-bold text-brand-700 hover:underline">Restock &rarr;</a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-8">
                            <span class="empty-state-icon bg-emerald-50 text-emerald-600"><x-icon name="check-circle" class="h-5 w-5" /></span>
                            <p class="text-xs font-bold text-slate-600">Stok dalam kondisi aman</p>
                            <p class="mt-1 text-[11px] text-slate-400">Tidak ada item yang berada di bawah ambang minimum.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Attendance -->
            @if($activeShift)
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Petugas Piket</h2>
                            <p class="card-subtitle">Operator aktif pada shift ini</p>
                        </div>
                        <span class="badge badge-brand">{{ $activeShift->shift_code }}</span>
                    </div>

                    <div class="card-body space-y-2.5">
                        @foreach($activeShift->attendances as $att)
                            <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-2.5">
                                <span class="avatar h-8 w-8">{{ mb_strtoupper(mb_substr($att->activeOperator->name, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-slate-800">{{ $att->activeOperator->name }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">Slot {{ $att->slot_number }} &bull; Operator</p>
                                </div>
                                <span class="badge badge-success">Aktif</span>
                            </div>
                        @endforeach

                        <a href="{{ route('piket.index') }}" class="btn btn-secondary btn-sm mt-1 w-full">
                            Lihat presensi lengkap
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            @else
                <div class="card overflow-hidden">
                    <div class="relative bg-gradient-to-br from-brand-900 to-brand-950 p-5 text-white">
                        <div class="absolute -right-6 -top-8 h-28 w-28 rounded-full border border-white/10"></div>
                        <div class="absolute -bottom-10 -left-4 h-24 w-24 rounded-full bg-white/[0.04]"></div>
                        <span class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-brand-200">
                            <x-icon name="clock" class="h-5 w-5" />
                        </span>
                        <h2 class="text-sm font-extrabold">Shift belum dibuka</h2>
                        <p class="mt-1 text-[11px] leading-relaxed text-white/50">Buka shift terlebih dahulu untuk mengaktifkan terminal kasir dan presensi petugas.</p>
                        <a href="{{ route('shift.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-[11px] font-bold text-brand-900 transition hover:bg-brand-50">
                            Buka shift sekarang
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            @endif

            <!-- Quick actions -->
            <div class="card p-5">
                <h2 class="card-title">Akses Cepat</h2>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('transactions.index') }}" class="group rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50">
                        <x-icon name="receipt" class="h-4 w-4 text-slate-400 transition group-hover:text-brand-600" />
                        <span class="mt-2 block text-[11px] font-bold text-slate-700">Riwayat</span>
                    </a>
                    <a href="{{ route('inventory.index') }}" class="group rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50">
                        <x-icon name="package" class="h-4 w-4 text-slate-400 transition group-hover:text-brand-600" />
                        <span class="mt-2 block text-[11px] font-bold text-slate-700">Persediaan</span>
                    </a>
                    @if(auth()->user()->hasAnyRole(['superadmin', 'admin', 'piket_guru']))
                        <a href="{{ route('reports.index') }}" class="group rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50">
                            <x-icon name="chart" class="h-4 w-4 text-slate-400 transition group-hover:text-brand-600" />
                            <span class="mt-2 block text-[11px] font-bold text-slate-700">Laporan</span>
                        </a>
                    @endif
                    <a href="{{ route('piket.index') }}" class="group rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-brand-200 hover:bg-brand-50">
                        <x-icon name="users" class="h-4 w-4 text-slate-400 transition group-hover:text-brand-600" />
                        <span class="mt-2 block text-[11px] font-bold text-slate-700">Presensi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
