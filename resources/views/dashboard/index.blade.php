@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Executive Dashboard TeFa MPLB</h1>
            <p class="text-xs text-slate-500 mt-0.5">Monitoring operasional kasir, dual-inventory, dan pembukuan Teaching Factory.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.terminal') }}" class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
                Buka Terminal Kasir [F2] &rarr;
            </a>
        </div>
    </div>

    <!-- 4 Key Stat Cards (Anti-AI Slop: clean 1px borders, subtle backgrounds, tabular numbers) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Penjualan Hari Ini -->
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Omzet Penjualan Hari Ini</span>
            <div class="text-xl font-bold text-slate-900 num-tabular">
                Rp {{ number_format((float) $todaySalesTotal, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Dari {{ $todayTrxCount }} transaksi kasir selesai
            </div>
        </div>

        <!-- 2. Shift Status -->
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Status Shift Operasional</span>
            @if($activeShift)
                <div class="flex items-center gap-1.5 text-sm font-bold text-emerald-700 mt-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Shift Aktif: {{ $activeShift->shift_code }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Guru: {{ $activeShift->supervisorTeacher->name }}
                </div>
            @else
                <div class="text-sm font-bold text-amber-700 mt-1">
                    Belum Ada Shift Buka
                </div>
                <a href="{{ route('shift.index') }}" class="text-[11px] text-brand-700 hover:underline block mt-1">
                    Buka shift sekarang &rarr;
                </a>
            @endif
        </div>

        <!-- 3. Ekspektasi Kas Laci -->
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Ekspektasi Kas di Laci Kasir</span>
            <div class="text-xl font-bold text-brand-700 num-tabular">
                Rp {{ number_format((float) ($activeShift?->system_expected_cash ?? 0), 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Saldo awal + penjualan tunai shift aktif
            </div>
        </div>

        <!-- 4. Peringatan Stok Kritis -->
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Peringatan Stok Menipis</span>
            <div class="text-xl font-bold {{ $lowStockProducts->count() > 0 ? 'text-rose-600' : 'text-emerald-700' }} num-tabular">
                {{ $lowStockProducts->count() }} Item
            </div>
            <a href="{{ route('inventory.index') }}" class="text-[11px] text-slate-500 hover:text-slate-800 block mt-1">
                Periksa persediaan & restock &rarr;
            </a>
        </div>
    </div>

    <!-- Main Content 2 Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left 8 Cols: Sales Trend & Recent Transactions -->
        <div class="lg:col-span-8 space-y-6">
            <!-- 7-Day Sales Trend Bar Chart (Clean Pure SVG/HTML, Fast & Lightweight) -->
            <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Tren Penjualan 7 Hari Terakhir</h2>
                    <span class="text-[11px] text-slate-400">Pembaruan otomatis dari POS</span>
                </div>

                @php
                    $maxTotal = max(array_column($salesTrend, 'total')) ?: 1;
                @endphp

                <div class="grid grid-cols-7 gap-2 items-end h-36 pt-4 px-2">
                    @foreach($salesTrend as $day)
                        @php
                            $heightPercent = max(8, min(100, (int)(($day['total'] / $maxTotal) * 100)));
                        @endphp
                        <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                            <span class="text-[10px] font-mono text-slate-600 num-tabular opacity-0 group-hover:opacity-100 transition">
                                {{ $day['total'] > 0 ? number_format($day['total'] / 1000, 0) . 'k' : '0' }}
                            </span>
                            <div class="w-full bg-brand-100 group-hover:bg-brand-600 rounded-t transition" style="height: {{ $heightPercent }}%;"></div>
                            <span class="text-[10px] text-slate-500 font-medium whitespace-nowrap">{{ $day['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Transaksi Kasir Terakhir</h2>
                    <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b">
                            <tr>
                                <th class="py-2 px-3">No. Invoice</th>
                                <th class="py-2 px-3">Pelanggan</th>
                                <th class="py-2 px-3">Kasir</th>
                                <th class="py-2 px-3">Metode</th>
                                <th class="py-2 px-3 text-right">Total</th>
                                <th class="py-2 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td class="py-2 px-3 font-mono font-semibold text-slate-800">{{ $tx->invoice_number }}</td>
                                    <td class="py-2 px-3 text-slate-700">{{ $tx->customer_name }}</td>
                                    <td class="py-2 px-3 text-slate-600">{{ $tx->cashier->name }}</td>
                                    <td class="py-2 px-3">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-700">
                                            {{ $tx->payment_method->value }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-right font-bold num-tabular text-slate-900">
                                        Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $tx->status === 'COMPLETED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            {{ $tx->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">Belum ada transaksi hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right 4 Cols: Low Stock Alert & Shift Attendance -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Low Stock Warning Box -->
            <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Peringatan Stok Menipis</h2>
                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-rose-50 text-rose-700 font-bold">Kritis</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($lowStockProducts as $lp)
                        <div class="p-2.5 rounded bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-semibold text-slate-800 block">{{ $lp->name }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ $lp->sku }} &bull; {{ $lp->inventory_type->value }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-rose-600 num-tabular">{{ (int) $lp->current_stock }} {{ $lp->unit?->code }}</span>
                                <a href="{{ route('inventory.index') }}" class="text-[10px] text-brand-700 block hover:underline">Restock &rarr;</a>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-xs text-slate-400">Seluruh stok barang & bahan dalam kondisi aman.</div>
                    @endforelse
                </div>
            </div>

            <!-- Petugas Piket Hari Ini Widget -->
            @if($activeShift)
                <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between border-b pb-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Petugas Piket Siswa</h2>
                        <span class="text-[11px] text-slate-500">Shift {{ $activeShift->shift_code }}</span>
                    </div>

                    <div class="space-y-2 text-xs">
                        @foreach($activeShift->attendances as $att)
                            <div class="flex items-center justify-between p-2 rounded bg-slate-50 border border-slate-100">
                                <div>
                                    <span class="font-semibold text-slate-800">Slot {{ $att->slot_number }}</span>
                                    <div class="text-[11px] text-slate-600">{{ $att->activeOperator->name }}</div>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Operator Aktif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
