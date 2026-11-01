@extends('layouts.app')

@section('content')
@php
    $tabs = [
        ['key' => 'bku', 'label' => 'Buku Kas', 'short' => 'Buku Kas', 'icon' => 'book'],
        ['key' => 'journal', 'label' => 'Jurnal', 'short' => 'Jurnal', 'icon' => 'list'],
        ['key' => 'trial_balance', 'label' => 'Neraca Saldo', 'short' => 'Neraca Saldo', 'icon' => 'chart'],
        ['key' => 'income_statement', 'label' => 'Laba Rugi', 'short' => 'Laba Rugi', 'icon' => 'trend-up'],
        ['key' => 'balance_sheet', 'label' => 'Neraca', 'short' => 'Neraca', 'icon' => 'bank'],
    ];
@endphp

<div class="space-y-6">
    <x-page-header
        title="Laporan Keuangan"
        subtitle="Buku kas, jurnal, dan laporan siap cetak.">
        <x-slot:actions>
            <button type="button" onclick="window.print()" class="btn btn-secondary no-print">
                <x-icon name="printer" class="h-4 w-4" />
                Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Periode -->
    <div class="card p-4 no-print">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex-1">
                <label for="start_date" class="form-label">Dari tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="input">
            </div>
            <div class="flex-1">
                <label for="end_date" class="form-label">Sampai tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="input">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Tampilkan</button>
                <a href="{{ route('reports.index', ['tab' => $tab]) }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tab -->
    <div class="no-print overflow-x-auto">
        <nav class="flex min-w-full gap-1 border-b border-slate-200">
            @foreach($tabs as $tabItem)
                <a href="{{ route('reports.index', ['tab' => $tabItem['key'], 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                    class="flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-[11px] font-medium transition-colors
                    {{ $tab === $tabItem['key'] ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
                    <span class="hidden sm:inline">{{ $tabItem['label'] }}</span>
                    <span class="sm:hidden">{{ $tabItem['short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Isi laporan -->
    <div class="table-shell">
        @if($tab === 'bku')
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Buku Kas</h2>
                    <p class="card-subtitle">{{ date('d/m/Y', strtotime($startDate)) }} &mdash; {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>
                <span class="text-[11px] text-slate-500">{{ count($data['entries']) }} catatan</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Akun kas</th>
                            <th>Keterangan</th>
                            <th class="text-right">Masuk</th>
                            <th class="text-right">Keluar</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['entries'] as $e)
                            <tr>
                                <td class="whitespace-nowrap font-mono text-[10px] font-medium text-slate-600">{{ $e->bku_number }}</td>
                                <td class="whitespace-nowrap text-[11px] text-slate-500">{{ $e->transaction_date->format('d/m/Y') }}</td>
                                <td class="text-[11px] font-medium text-slate-700">{{ $e->cashAccount->account_name }}</td>
                                <td class="max-w-[280px] text-[11px] text-slate-600">{{ $e->description }}</td>
                                <td class="text-right font-medium text-emerald-600 num-tabular">
                                    {{ bccomp((string) $e->debit_in, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->debit_in, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right font-medium text-rose-600 num-tabular">
                                    {{ bccomp((string) $e->credit_out, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->credit_out, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right font-semibold text-slate-900 num-tabular">Rp {{ number_format((float) $e->running_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <span class="empty-state-icon"><x-icon name="book" class="h-5 w-5" /></span>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada catatan kas</p>
                                        <p class="mt-1 text-xs text-slate-400">Tidak ada transaksi kas pada periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($tab === 'journal')
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Jurnal Umum</h2>
                    <p class="card-subtitle">Catatan transaksi berpasangan</p>
                </div>
                <span class="text-[11px] text-slate-500">{{ count($data['entries']) }} jurnal</span>
            </div>

            <div class="space-y-4 p-5">
                @forelse($data['entries'] as $jrn)
                    <div class="overflow-hidden rounded-md border border-slate-200">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="font-mono text-[11px] font-semibold text-slate-900">{{ $jrn->journal_number }}</span>
                                <span class="text-[10px] text-slate-400">{{ $jrn->entry_date->format('d/m/Y') }}</span>
                                <span class="chip">{{ $jrn->journal_type->value }}</span>
                            </div>
                            <span class="text-[11px] text-slate-500">{{ $jrn->description }}</span>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                    @foreach($jrn->items as $it)
                                        <tr>
                                            <td class="w-28 font-mono text-[10px] text-slate-400">{{ $it->chartOfAccount->account_code }}</td>
                                            <td class="{{ bccomp((string) $it->credit, '0.0000', 4) === 1 ? 'pl-8 text-slate-500' : 'font-medium text-slate-800' }}">
                                                {{ $it->chartOfAccount->account_name }}
                                            </td>
                                            <td class="w-36 text-right num-tabular">
                                                {{ bccomp((string) $it->debit, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $it->debit, 0, ',', '.') : '—' }}
                                            </td>
                                            <td class="w-36 text-right num-tabular">
                                                {{ bccomp((string) $it->credit, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $it->credit, 0, ',', '.') : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-right">Total</td>
                                        <td class="text-right num-tabular">Rp {{ number_format((float) $jrn->total_debit, 0, ',', '.') }}</td>
                                        <td class="text-right num-tabular">Rp {{ number_format((float) $jrn->total_credit, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon"><x-icon name="list" class="h-5 w-5" /></span>
                        <p class="text-sm font-semibold text-slate-700">Belum ada jurnal</p>
                        <p class="mt-1 text-xs text-slate-400">Jurnal muncul setelah ada transaksi kasir.</p>
                    </div>
                @endforelse
            </div>

        @elseif($tab === 'trial_balance')
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Neraca Saldo</h2>
                    <p class="card-subtitle">{{ date('d/m/Y', strtotime($startDate)) }} &mdash; {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>
                <span class="badge {{ $data['is_balanced'] ? 'badge-success' : 'badge-danger' }}">
                    {{ $data['is_balanced'] ? 'Seimbang' : 'Tidak seimbang' }}
                </span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-28">Kode akun</th>
                            <th>Nama akun</th>
                            <th class="w-28">Tipe</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['rows'] as $r)
                            <tr>
                                <td class="font-mono text-[10px] text-slate-500">{{ $r['account_code'] }}</td>
                                <td class="font-medium text-slate-800">{{ $r['account_name'] }}</td>
                                <td><span class="chip">{{ $r['account_type'] }}</span></td>
                                <td class="text-right num-tabular">
                                    {{ bccomp($r['debit'], '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $r['debit'], 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right num-tabular">
                                    {{ bccomp($r['credit'], '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $r['credit'], 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right">Total</td>
                            <td class="text-right num-tabular">Rp {{ number_format((float) $data['total_debit'], 0, ',', '.') }}</td>
                            <td class="text-right num-tabular">Rp {{ number_format((float) $data['total_credit'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @elseif($tab === 'income_statement')
            <div class="border-b border-slate-100 px-5 py-4 text-center">
                <h2 class="text-base font-semibold text-slate-900">Laba Rugi</h2>
                <p class="mt-1 text-[11px] text-slate-500">Teaching Factory &amp; Unit Bisnis MPLB &bull; s/d {{ date('d/m/Y', strtotime($endDate)) }}</p>
            </div>

            <div class="mx-auto max-w-4xl space-y-4 p-5 text-xs">
                <div class="rounded-md border border-slate-200 p-5">
                    <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Barang etalase</h3>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between py-0.5"><span class="text-slate-600">Pendapatan barang</span><span class="font-medium num-tabular">Rp {{ number_format((float) $data['retail_revenue_gross'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-slate-500"><span>Potongan penjualan</span><span class="num-tabular">(Rp {{ number_format((float) $data['retail_discount'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-slate-100 py-1.5 font-semibold"><span>Pendapatan bersih</span><span class="num-tabular">Rp {{ number_format((float) $data['retail_revenue_net'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-rose-600"><span>HPP barang</span><span class="font-medium num-tabular">(Rp {{ number_format((float) $data['retail_cogs'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-sm font-semibold text-slate-900"><span>Laba kotor barang</span><span class="num-tabular">Rp {{ number_format((float) $data['retail_gross_profit'], 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="rounded-md border border-slate-200 p-5">
                    <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Layanan jasa</h3>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between py-0.5"><span class="text-slate-600">Pendapatan layanan</span><span class="font-medium num-tabular">Rp {{ number_format((float) $data['service_revenue_gross'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-slate-500"><span>Potongan grosir</span><span class="num-tabular">(Rp {{ number_format((float) $data['service_discount'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-slate-100 py-1.5 font-semibold"><span>Pendapatan bersih</span><span class="num-tabular">Rp {{ number_format((float) $data['service_revenue_net'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-rose-600"><span>HPP bahan</span><span class="font-medium num-tabular">(Rp {{ number_format((float) $data['service_cogs'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-sm font-semibold text-slate-900"><span>Laba kotor layanan</span><span class="num-tabular">Rp {{ number_format((float) $data['service_gross_profit'], 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-md border border-brand-200 bg-brand-50 px-5 py-4 text-sm font-semibold text-brand-900">
                    <span>Total laba kotor</span>
                    <span class="num-tabular">Rp {{ number_format((float) $data['total_gross_profit'], 0, ',', '.') }}</span>
                </div>

                <div class="rounded-md border border-slate-200 p-5">
                    <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Beban operasional dan kerusakan</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($data['expenses'] as $exp)
                            <div class="flex justify-between py-0.5 text-slate-600">
                                <span>{{ $exp['account_code'] }} &bull; {{ $exp['account_name'] }}</span>
                                <span class="num-tabular">Rp {{ number_format((float) $exp['amount'], 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="py-1 text-slate-400">Tidak ada beban pada periode ini.</p>
                        @endforelse
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold text-rose-700">
                            <span>Total beban</span>
                            <span class="num-tabular">(Rp {{ number_format((float) $data['total_expenses'], 0, ',', '.') }})</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-md bg-slate-900 px-5 py-4 text-white">
                    <div>
                        <p class="text-[11px] text-slate-400">Laba bersih</p>
                        <p class="mt-0.5 text-[10px] text-slate-500">Periode berjalan</p>
                    </div>
                    <span class="text-lg font-semibold text-emerald-400 num-tabular">Rp {{ number_format((float) $data['net_profit'], 0, ',', '.') }}</span>
                </div>
            </div>

        @elseif($tab === 'balance_sheet')
            <div class="border-b border-slate-100 px-5 py-4 text-center">
                <h2 class="text-base font-semibold text-slate-900">Neraca</h2>
                <p class="mt-1 text-[11px] text-slate-500">Posisi per {{ date('d/m/Y', strtotime($endDate)) }}</p>
            </div>

            <div class="mx-auto max-w-5xl space-y-5 p-5 text-xs">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-md border border-slate-200 p-5">
                        <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Aset</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($data['assets'] as $ast)
                                <div class="flex justify-between py-0.5 text-slate-600">
                                    <span>{{ $ast['code'] }} &bull; {{ $ast['name'] }}</span>
                                    <span class="font-medium num-tabular">Rp {{ number_format((float) $ast['amount'], 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-slate-400">Tidak ada saldo aset.</p>
                            @endforelse
                        </div>
                        <div class="mt-3 flex justify-between border-t border-slate-200 pt-3 text-sm font-semibold text-slate-900">
                            <span>Total aset</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['total_assets'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-md border border-slate-200 p-5">
                            <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Kewajiban</h3>
                            <div class="mt-3 space-y-2">
                                @forelse($data['liabilities'] as $lia)
                                    <div class="flex justify-between py-0.5 text-slate-600">
                                        <span>{{ $lia['code'] }} &bull; {{ $lia['name'] }}</span>
                                        <span class="font-medium num-tabular">Rp {{ number_format((float) $lia['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-slate-400">Tidak ada saldo kewajiban.</p>
                                @endforelse
                            </div>
                            <div class="mt-3 flex justify-between border-t border-slate-200 pt-2 font-semibold text-slate-800">
                                <span>Total kewajiban</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_liabilities'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="rounded-md border border-slate-200 p-5">
                            <h3 class="border-b border-slate-100 pb-2 text-[11px] font-semibold text-slate-700">Modal</h3>
                            <div class="mt-3 space-y-2">
                                @forelse($data['equity'] as $eq)
                                    <div class="flex justify-between py-0.5 text-slate-600">
                                        <span>{{ $eq['code'] }} &bull; {{ $eq['name'] }}</span>
                                        <span class="font-medium num-tabular">Rp {{ number_format((float) $eq['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-slate-400">Tidak ada saldo modal.</p>
                                @endforelse
                            </div>
                            <div class="mt-3 flex justify-between border-t border-slate-200 pt-2 font-semibold text-slate-800">
                                <span>Total modal</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_equity'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-md bg-slate-900 px-5 py-4 text-white">
                            <span class="text-[11px] font-medium text-slate-300">Total kewajiban dan modal</span>
                            <span class="text-sm font-semibold num-tabular">Rp {{ number_format((float) $data['total_liabilities_and_equity'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tanda tangan -->
        <div class="border-t border-slate-200 px-5 py-6">
            <div class="mb-6 text-right text-[10px] text-slate-400">
                Dicetak {{ now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB
            </div>

            <div class="grid grid-cols-1 gap-6 text-center text-[11px] sm:grid-cols-3">
                <div>
                    <span class="block text-slate-500">Mengetahui,</span>
                    <span class="mt-0.5 block font-semibold text-slate-900">Penanggung jawab TeFa MPLB</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-medium text-slate-700">( ........................................ )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NIP. ........................................</span>
                </div>

                <div>
                    <span class="block text-slate-500">Diperiksa,</span>
                    <span class="mt-0.5 block font-semibold text-slate-900">Guru pembimbing piket</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-medium text-slate-700">( {{ auth()->user()->name }} )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NIP. {{ auth()->user()->nip_nisn ?? '........................................' }}</span>
                </div>

                <div>
                    <span class="block text-slate-500">Disusun,</span>
                    <span class="mt-0.5 block font-semibold text-slate-900">Petugas kasir</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-medium text-slate-700">( ........................................ )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NISN. ........................................</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
