@extends('layouts.app')

@section('content')
@php
    $tabs = [
        ['key' => 'bku', 'label' => 'Buku Kas Umum', 'short' => 'BKU', 'icon' => 'book'],
        ['key' => 'journal', 'label' => 'Jurnal Umum', 'short' => 'Jurnal', 'icon' => 'list'],
        ['key' => 'trial_balance', 'label' => 'Neraca Saldo', 'short' => 'Neraca Saldo', 'icon' => 'chart'],
        ['key' => 'income_statement', 'label' => 'Laba Rugi', 'short' => 'Laba Rugi', 'icon' => 'trend-up'],
        ['key' => 'balance_sheet', 'label' => 'Neraca Keuangan', 'short' => 'Neraca', 'icon' => 'bank'],
    ];
@endphp

<div class="space-y-6">
    <x-page-header
        eyebrow="Akuntansi & Pelaporan"
        title="Laporan Keuangan & BKU"
        subtitle="Siklus akuntansi double-entry otomatis untuk Teaching Factory MPLB — dari buku kas umum hingga neraca keuangan, siap cetak untuk keperluan resmi sekolah.">
        <x-slot:actions>
            <button type="button" onclick="window.print()" class="btn btn-secondary no-print">
                <x-icon name="printer" class="h-4 w-4" />
                Cetak Laporan
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- Period filter -->
    <div class="card p-4 no-print">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex-1">
                <label for="start_date" class="form-label">Periode Mulai</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="input">
            </div>
            <div class="flex-1">
                <label for="end_date" class="form-label">Periode Selesai</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="input">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan
                </button>
                <a href="{{ route('reports.index', ['tab' => $tab]) }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tabs -->
    <div class="no-print overflow-x-auto pb-1">
        <nav class="inline-flex min-w-full items-center gap-1 rounded-2xl border border-slate-200/80 bg-white p-1.5 shadow-sm">
            @foreach($tabs as $tabItem)
                <a href="{{ route('reports.index', ['tab' => $tabItem['key'], 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                    class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-[11px] font-bold transition
                    {{ $tab === $tabItem['key'] ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                    <x-icon :name="$tabItem['icon']" class="h-3.5 w-3.5 {{ $tab === $tabItem['key'] ? 'text-brand-200' : 'text-slate-400' }}" />
                    <span class="hidden sm:inline">{{ $tabItem['label'] }}</span>
                    <span class="sm:hidden">{{ $tabItem['short'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Report content -->
    <div class="table-shell">
        @if($tab === 'bku')
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Buku Kas Umum (BKU)</h2>
                    <p class="card-subtitle">Periode {{ date('d/m/Y', strtotime($startDate)) }} &mdash; {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>
                <span class="badge badge-brand">{{ count($data['entries']) }} entri kas</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. BKU</th>
                            <th>Tanggal</th>
                            <th>Akun Kas</th>
                            <th>Uraian Transaksi</th>
                            <th class="text-right">Penerimaan</th>
                            <th class="text-right">Pengeluaran</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['entries'] as $e)
                            <tr>
                                <td class="whitespace-nowrap font-mono text-[10px] font-semibold text-slate-600">{{ $e->bku_number }}</td>
                                <td class="whitespace-nowrap text-[11px] text-slate-500">{{ $e->transaction_date->format('d/m/Y') }}</td>
                                <td class="text-[11px] font-semibold text-slate-700">{{ $e->cashAccount->name }}</td>
                                <td class="max-w-[280px] text-[11px] text-slate-600">{{ $e->description }}</td>
                                <td class="text-right font-semibold text-emerald-600 num-tabular">
                                    {{ bccomp((string) $e->debit_in, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->debit_in, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right font-semibold text-rose-600 num-tabular">
                                    {{ bccomp((string) $e->credit_out, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->credit_out, 0, ',', '.') : '—' }}
                                </td>
                                <td class="text-right font-extrabold text-slate-900 num-tabular">Rp {{ number_format((float) $e->running_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <span class="empty-state-icon"><x-icon name="book" class="h-6 w-6" /></span>
                                        <p class="text-sm font-bold text-slate-700">Belum ada mutasi kas</p>
                                        <p class="mt-1 text-xs text-slate-400">Tidak ada transaksi kas pada periode yang dipilih.</p>
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
                    <p class="card-subtitle">Posting double-entry bersifat immutable</p>
                </div>
                <span class="badge badge-neutral">{{ count($data['entries']) }} jurnal</span>
            </div>

            <div class="space-y-4 p-5">
                @forelse($data['entries'] as $jrn)
                    <div class="overflow-hidden rounded-xl border border-slate-200/80">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="font-mono text-[11px] font-extrabold text-slate-900">{{ $jrn->journal_number }}</span>
                                <span class="text-[10px] text-slate-400">{{ $jrn->entry_date->format('d/m/Y') }}</span>
                                <span class="badge badge-neutral font-mono">{{ $jrn->journal_type->value }}</span>
                            </div>
                            <span class="text-[11px] font-medium text-slate-500">{{ $jrn->description }}</span>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <tbody>
                                    @foreach($jrn->items as $it)
                                        <tr>
                                            <td class="w-28 font-mono text-[10px] text-slate-400">{{ $it->chartOfAccount->account_code }}</td>
                                            <td class="{{ bccomp((string) $it->credit, '0.0000', 4) === 1 ? 'pl-8 text-slate-500' : 'font-semibold text-slate-800' }}">
                                                {{ $it->chartOfAccount->name }}
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
                        <span class="empty-state-icon"><x-icon name="list" class="h-6 w-6" /></span>
                        <p class="text-sm font-bold text-slate-700">Belum ada jurnal</p>
                        <p class="mt-1 text-xs text-slate-400">Jurnal transaksi akan muncul setelah ada aktivitas kasir.</p>
                    </div>
                @endforelse
            </div>

        @elseif($tab === 'trial_balance')
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Neraca Saldo</h2>
                    <p class="card-subtitle">Trial balance periode {{ date('d/m/Y', strtotime($startDate)) }} &mdash; {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>
                <span class="badge {{ $data['is_balanced'] ? 'badge-success' : 'badge-danger' }}">
                    <x-icon :name="$data['is_balanced'] ? 'check-circle' : 'x-circle'" class="h-3 w-3" />
                    {{ $data['is_balanced'] ? 'Seimbang' : 'Tidak Seimbang' }}
                </span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-28">Kode Akun</th>
                            <th>Nama Akun</th>
                            <th class="w-28">Tipe</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['rows'] as $r)
                            <tr>
                                <td class="font-mono text-[10px] text-slate-500">{{ $r['account_code'] }}</td>
                                <td class="font-semibold text-slate-800">{{ $r['account_name'] }}</td>
                                <td><span class="badge badge-neutral">{{ $r['account_type'] }}</span></td>
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
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Laporan Laba Rugi Komprehensif</h2>
                <p class="mt-1 text-[11px] text-slate-500">Teaching Factory &amp; Unit Bisnis MPLB &bull; Periode s/d {{ date('d/m/Y', strtotime($endDate)) }}</p>
            </div>

            <div class="mx-auto max-w-4xl space-y-4 p-5 text-xs">
                <div class="rounded-2xl border border-sky-200/70 bg-sky-50/50 p-5">
                    <div class="flex items-center gap-2 border-b border-sky-200/60 pb-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 text-[10px] font-extrabold text-sky-700">A</span>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Segmen Ritel — ATK Etalase</h3>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between py-0.5"><span class="text-slate-600">Pendapatan Penjualan Ritel (Bruto)</span><span class="font-semibold num-tabular">Rp {{ number_format((float) $data['retail_revenue_gross'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-slate-500"><span>Potongan Penjualan Ritel</span><span class="num-tabular">(Rp {{ number_format((float) $data['retail_discount'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-sky-200/60 py-1.5 font-bold"><span>Pendapatan Bersih Ritel</span><span class="num-tabular">Rp {{ number_format((float) $data['retail_revenue_net'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-rose-600"><span>Harga Pokok Penjualan (HPP) Ritel</span><span class="font-semibold num-tabular">(Rp {{ number_format((float) $data['retail_cogs'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-sky-200/60 pt-2 text-sm font-extrabold text-slate-900"><span>Laba Kotor Ritel</span><span class="num-tabular">Rp {{ number_format((float) $data['retail_gross_profit'], 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-violet-200/70 bg-violet-50/40 p-5">
                    <div class="flex items-center gap-2 border-b border-violet-200/60 pb-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-violet-100 text-[10px] font-extrabold text-violet-700">B</span>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Segmen Layanan Jasa — Fotocopy, Print, Laminating</h3>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between py-0.5"><span class="text-slate-600">Pendapatan Layanan Jasa (Bruto)</span><span class="font-semibold num-tabular">Rp {{ number_format((float) $data['service_revenue_gross'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-slate-500"><span>Potongan Grosir Layanan Jasa (Tier)</span><span class="num-tabular">(Rp {{ number_format((float) $data['service_discount'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-violet-200/60 py-1.5 font-bold"><span>Pendapatan Bersih Layanan Jasa</span><span class="num-tabular">Rp {{ number_format((float) $data['service_revenue_net'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between py-0.5 text-rose-600"><span>HPP Bahan Baku Jasa (Kertas + Tinta BoM)</span><span class="font-semibold num-tabular">(Rp {{ number_format((float) $data['service_cogs'], 0, ',', '.') }})</span></div>
                        <div class="flex justify-between border-t border-violet-200/60 pt-2 text-sm font-extrabold text-slate-900"><span>Laba Kotor Layanan Jasa</span><span class="num-tabular">Rp {{ number_format((float) $data['service_gross_profit'], 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm font-extrabold text-brand-900">
                    <span>Total Laba Kotor Gabungan (A + B)</span>
                    <span class="num-tabular">Rp {{ number_format((float) $data['total_gross_profit'], 0, ',', '.') }}</span>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-extrabold text-slate-600">C</span>
                        <h3 class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Beban Operasional &amp; Kerusakan Produksi</h3>
                    </div>
                    <div class="mt-3 space-y-2">
                        @forelse($data['expenses'] as $exp)
                            <div class="flex justify-between py-0.5 text-slate-600">
                                <span>{{ $exp['account_code'] }} &bull; {{ $exp['account_name'] }}</span>
                                <span class="num-tabular">Rp {{ number_format((float) $exp['amount'], 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="py-1 text-slate-400">Tidak ada beban operasional tercatat pada periode ini.</p>
                        @endforelse
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-rose-700">
                            <span>Total Beban Operasional</span>
                            <span class="num-tabular">(Rp {{ number_format((float) $data['total_expenses'], 0, ',', '.') }})</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-slate-900 px-5 py-4 text-white">
                    <div>
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.16em] text-white/40">Laba Bersih Operasional</p>
                        <p class="mt-0.5 text-[11px] text-white/50">Net profit periode berjalan</p>
                    </div>
                    <span class="text-lg font-extrabold text-emerald-400 num-tabular">Rp {{ number_format((float) $data['net_profit'], 0, ',', '.') }}</span>
                </div>
            </div>

        @elseif($tab === 'balance_sheet')
            <div class="border-b border-slate-100 px-5 py-4 text-center">
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Neraca Keuangan</h2>
                <p class="mt-1 text-[11px] text-slate-500">Posisi per tanggal {{ date('d/m/Y', strtotime($endDate)) }}</p>
            </div>

            <div class="mx-auto max-w-5xl space-y-5 p-5 text-xs">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/40 p-5">
                        <h3 class="border-b border-emerald-200/60 pb-2 text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Aset (Aktiva)</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($data['assets'] as $ast)
                                <div class="flex justify-between py-0.5 text-slate-600">
                                    <span>{{ $ast['code'] }} &bull; {{ $ast['name'] }}</span>
                                    <span class="font-semibold num-tabular">Rp {{ number_format((float) $ast['amount'], 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-slate-400">Tidak ada saldo aset.</p>
                            @endforelse
                        </div>
                        <div class="mt-3 flex justify-between border-t border-emerald-200/60 pt-3 text-sm font-extrabold text-slate-900">
                            <span>Total Aset</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['total_assets'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-rose-200/70 bg-rose-50/40 p-5">
                            <h3 class="border-b border-rose-200/60 pb-2 text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Kewajiban (Liabilitas)</h3>
                            <div class="mt-3 space-y-2">
                                @forelse($data['liabilities'] as $lia)
                                    <div class="flex justify-between py-0.5 text-slate-600">
                                        <span>{{ $lia['code'] }} &bull; {{ $lia['name'] }}</span>
                                        <span class="font-semibold num-tabular">Rp {{ number_format((float) $lia['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-slate-400">Tidak ada saldo liabilitas.</p>
                                @endforelse
                            </div>
                            <div class="mt-3 flex justify-between border-t border-rose-200/60 pt-2 font-bold text-slate-800">
                                <span>Total Kewajiban</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_liabilities'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-indigo-200/70 bg-indigo-50/40 p-5">
                            <h3 class="border-b border-indigo-200/60 pb-2 text-[11px] font-extrabold uppercase tracking-[0.1em] text-slate-700">Ekuitas (Modal)</h3>
                            <div class="mt-3 space-y-2">
                                @forelse($data['equity'] as $eq)
                                    <div class="flex justify-between py-0.5 text-slate-600">
                                        <span>{{ $eq['code'] }} &bull; {{ $eq['name'] }}</span>
                                        <span class="font-semibold num-tabular">Rp {{ number_format((float) $eq['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-slate-400">Tidak ada saldo ekuitas.</p>
                                @endforelse
                            </div>
                            <div class="mt-3 flex justify-between border-t border-indigo-200/60 pt-2 font-bold text-slate-800">
                                <span>Total Ekuitas</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_equity'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl bg-slate-900 px-5 py-4 text-white">
                            <span class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-white/60">Total Kewajiban &amp; Ekuitas</span>
                            <span class="text-sm font-extrabold num-tabular">Rp {{ number_format((float) $data['total_liabilities_and_equity'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Signature -->
        <div class="border-t border-slate-200 px-5 py-6">
            <div class="mb-6 text-right text-[10px] text-slate-400">
                Dicetak pada {{ now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB
            </div>

            <div class="grid grid-cols-1 gap-6 text-center text-[11px] sm:grid-cols-3">
                <div>
                    <span class="block text-slate-500">Mengetahui,</span>
                    <span class="mt-0.5 block font-bold text-slate-900">Penanggung Jawab TeFa MPLB</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-semibold text-slate-700">( ........................................ )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NIP. ........................................</span>
                </div>

                <div>
                    <span class="block text-slate-500">Diverifikasi Oleh,</span>
                    <span class="mt-0.5 block font-bold text-slate-900">Guru Pembimbing Piket</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-semibold text-slate-700">( {{ auth()->user()->name }} )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NIP. {{ auth()->user()->nip_nisn ?? '........................................' }}</span>
                </div>

                <div>
                    <span class="block text-slate-500">Disusun Oleh,</span>
                    <span class="mt-0.5 block font-bold text-slate-900">Petugas Kasir / Pembukuan</span>
                    <div class="h-16"></div>
                    <span class="block border-t border-slate-300 pt-1 font-semibold text-slate-700">( ........................................ )</span>
                    <span class="mt-0.5 block font-mono text-[10px] text-slate-400">NISN. ........................................</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
