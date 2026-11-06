<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan</title>
    <style>
        @page {
            margin: 16mm 13mm 20mm 13mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.6px;
            line-height: 1.45;
            color: #1f2937;
        }

        table {
            border-collapse: collapse;
        }

        .doc-head {
            width: 100%;
        }

        .doc-head td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 52px;
            padding-right: 10px;
        }

        .logo-cell img {
            height: 44px;
            width: auto;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.06em;
            color: #0f172a;
        }

        .company-tagline {
            margin-top: 1px;
            font-size: 9px;
            font-weight: bold;
            color: #1d4ed8;
        }

        .company-meta {
            margin-top: 2px;
            font-size: 7.8px;
            color: #64748b;
            line-height: 1.4;
        }

        .mark-cell {
            width: 96px;
            text-align: right;
            font-size: 7.5px;
            letter-spacing: 0.16em;
            color: #94a3b8;
        }

        .mark-cell strong {
            display: block;
            font-size: 11px;
            letter-spacing: 0.12em;
            color: #0f172a;
        }

        .head-rule {
            margin: 7px 0 12px;
            border-top: 2px solid #0f172a;
            border-bottom: 0.6px solid #cbd5e1;
            height: 2px;
        }

        .title-block {
            margin-bottom: 11px;
            text-align: center;
        }

        .title-block h1 {
            margin: 0;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0f172a;
        }

        .title-block .period {
            margin: 2px 0 0;
            font-size: 8.5px;
            color: #475569;
        }

        .section-title {
            margin: 0 0 4px;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #334155;
        }

        table.data {
            width: 100%;
            margin-bottom: 8px;
        }

        table.data thead {
            display: table-header-group;
        }

        table.data th {
            padding: 4px 5px;
            border: 0.6px solid #94a3b8;
            background: #e8edf5;
            font-size: 7.4px;
            font-weight: bold;
            letter-spacing: 0.04em;
            text-align: left;
            text-transform: uppercase;
            color: #1e293b;
        }

        table.data td {
            padding: 3.6px 5px;
            border: 0.5px solid #d5dce6;
            vertical-align: top;
        }

        table.data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        table.data tfoot td {
            padding: 4px 5px;
            border: 0.6px solid #94a3b8;
            background: #eef2f7;
            font-weight: bold;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .mono {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.3px;
        }

        .nowrap {
            white-space: nowrap;
        }

        .muted {
            color: #64748b;
        }

        .positive {
            color: #047857;
        }

        .negative {
            color: #be123c;
        }

        .empty {
            padding: 12px 6px;
            text-align: center;
            color: #94a3b8;
        }

        .report-note {
            margin: 0 0 8px;
            font-size: 7.6px;
            color: #64748b;
        }

        .journal-block {
            margin-bottom: 9px;
            page-break-inside: avoid;
        }

        .journal-head {
            width: 100%;
            border: 0.6px solid #94a3b8;
            border-bottom: none;
            background: #eef2f7;
        }

        .journal-head td {
            padding: 3.6px 6px;
            font-size: 7.6px;
            vertical-align: middle;
        }

        .journal-head .journal-number {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.6px;
            font-weight: bold;
            color: #0f172a;
        }

        .journal-head .journal-type {
            padding: 1px 5px;
            border: 0.5px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
        }

        .journal-head .journal-desc {
            text-align: right;
            color: #475569;
        }

        table.data.compact {
            margin-bottom: 0;
        }

        .indent {
            padding-left: 16px !important;
            color: #475569;
        }

        table.statement {
            width: 100%;
            margin-bottom: 9px;
        }

        table.statement td {
            padding: 3px 5px;
            vertical-align: top;
        }

        table.statement td.amount {
            width: 108px;
            text-align: right;
            white-space: nowrap;
        }

        table.statement tr.group td {
            padding-top: 8px;
            padding-bottom: 3px;
            border-bottom: 0.6px solid #cbd5e1;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #334155;
        }

        table.statement tr.subtotal td {
            border-top: 0.5px solid #cbd5e1;
            font-weight: bold;
        }

        table.statement tr.grand-total td {
            padding-top: 5px;
            padding-bottom: 5px;
            border-top: 1.4px solid #0f172a;
            border-bottom: 1.4px solid #0f172a;
            font-size: 9.4px;
            font-weight: bold;
            color: #0f172a;
        }

        table.statement tr.profit td {
            padding-top: 6px;
            padding-bottom: 6px;
            border-top: 1.4px solid #0f172a;
            border-bottom: 1.4px solid #0f172a;
            font-size: 10px;
            font-weight: bold;
            color: #065f46;
        }

        .sub-label {
            padding-left: 14px !important;
            color: #475569;
        }

        .statement-card {
            margin-bottom: 10px;
            padding: 7px 9px 3px;
            border: 0.6px solid #cbd5e1;
            background: #fbfcfe;
        }

        table.bs-layout {
            width: 100%;
        }

        table.bs-layout > tbody > tr > td.bs-col {
            width: 49%;
            vertical-align: top;
        }

        table.bs-layout > tbody > tr > td.bs-gap {
            width: 2%;
        }

        .bs-card {
            margin-bottom: 8px;
            padding: 7px 9px 3px;
            border: 0.6px solid #cbd5e1;
            background: #fbfcfe;
        }

        .bs-total {
            margin-bottom: 8px;
            padding: 6px 9px;
            border: 0.8px solid #0f172a;
            background: #0f172a;
            color: #ffffff;
            font-size: 8.6px;
            font-weight: bold;
        }

        .bs-total table {
            width: 100%;
        }

        .bs-total td.amount {
            text-align: right;
            white-space: nowrap;
        }

        .signature-wrap {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .signature-date {
            margin-bottom: 8px;
            text-align: right;
            font-size: 7.8px;
            color: #64748b;
        }

        table.signature-grid {
            width: 100%;
            text-align: center;
            font-size: 8px;
        }

        table.signature-grid td {
            width: 33.33%;
            padding: 0 8px;
            vertical-align: top;
        }

        .sig-leading {
            color: #64748b;
        }

        .sig-role {
            font-weight: bold;
            color: #0f172a;
        }

        .sig-space {
            height: 48px;
        }

        .sig-name {
            padding-top: 3px;
            border-top: 0.6px solid #94a3b8;
            font-weight: bold;
            color: #334155;
        }

        .sig-nip {
            margin-top: 1px;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7px;
            color: #94a3b8;
        }

        .doc-footer {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 0.5px solid #e2e8f0;
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $reportTitle = match ($tab) {
        'bku' => 'Buku Kas Umum',
        'journal' => 'Jurnal Umum',
        'trial_balance' => 'Neraca Saldo',
        'income_statement' => 'Laporan Laba Rugi',
        'balance_sheet' => 'Neraca',
        default => 'Laporan Keuangan',
    };

    $periodLabel = $tab === 'balance_sheet'
        ? 'Posisi per ' . date('d/m/Y', strtotime($endDate))
        : 'Periode ' . date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate));

    $rp = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $rpParen = static fn ($value): string => '(Rp ' . number_format((float) $value, 0, ',', '.') . ')';
    $rpSigned = static function ($value): string {
        $amount = (float) $value;

        return $amount < 0
            ? '(Rp ' . number_format(abs($amount), 0, ',', '.') . ')'
            : 'Rp ' . number_format($amount, 0, ',', '.');
    };
    $companyName = mb_strtoupper((string) ($store['store_name'] ?: $identity['app_name']));
    $companyTagline = (string) ($store['store_tagline'] ?: $identity['app_tagline']);
@endphp

<table class="doc-head">
    <tr>
        <td class="logo-cell">
            <img src="{{ $logoPath }}" alt="Logo">
        </td>
        <td>
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-tagline">{{ $companyTagline }}</div>
            <div class="company-meta">
                @if(! empty($store['store_address_line1']))
                    {{ $store['store_address_line1'] }}@if(! empty($store['store_address_line2'])), {{ $store['store_address_line2'] }}@endif
                @endif
                @if(! empty($store['store_phone']))
                    &middot; Telp. {{ $store['store_phone'] }}
                @endif
                @if(! empty($store['store_email']))
                    &middot; {{ $store['store_email'] }}
                @endif
            </div>
        </td>
        <td class="mark-cell">
            LAPORAN
            <strong>KEUANGAN</strong>
        </td>
    </tr>
</table>

<div class="head-rule"></div>

<div class="title-block">
    <h1>{{ $reportTitle }}</h1>
    <p class="period">{{ $periodLabel }}</p>
</div>

@if($tab === 'bku')
    @php
        $entries = $data['entries'];
        $totalIn = $entries->reduce(static fn ($carry, $entry): string => bcadd($carry, (string) $entry->debit_in, 4), '0.0000');
        $totalOut = $entries->reduce(static fn ($carry, $entry): string => bcadd($carry, (string) $entry->credit_out, 4), '0.0000');
        $endingBalance = $entries->isNotEmpty() ? (string) $entries->last()->running_balance : '0.0000';
    @endphp

    <p class="report-note">{{ $entries->count() }} catatan kas pada periode ini.</p>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 17%;">No. BKU</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 17%;">Akun Kas</th>
                <th>Keterangan</th>
                <th class="num" style="width: 12%;">Masuk</th>
                <th class="num" style="width: 12%;">Keluar</th>
                <th class="num" style="width: 14%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="mono nowrap">{{ $entry->bku_number }}</td>
                    <td class="nowrap">{{ $entry->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $entry->cashAccount->account_name }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="num positive">{{ bccomp((string) $entry->debit_in, '0.0000', 4) === 1 ? $rp($entry->debit_in) : '—' }}</td>
                    <td class="num negative">{{ bccomp((string) $entry->credit_out, '0.0000', 4) === 1 ? $rp($entry->credit_out) : '—' }}</td>
                    <td class="num">{{ $rpSigned($entry->running_balance) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Tidak ada catatan kas pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="num">Total mutasi kas</td>
                <td class="num">{{ $rp($totalIn) }}</td>
                <td class="num">{{ $rp($totalOut) }}</td>
                <td class="num">{{ $rpSigned($endingBalance) }}</td>
            </tr>
        </tfoot>
    </table>
    <p class="report-note">Saldo akhir per {{ date('d/m/Y', strtotime($endDate)) }}: <strong>{{ $rpSigned($endingBalance) }}</strong></p>

@elseif($tab === 'journal')
    @php
        $entries = $data['entries'];
        $grandDebit = $entries->reduce(static fn ($carry, $journal): string => bcadd($carry, (string) $journal->total_debit, 4), '0.0000');
        $grandCredit = $entries->reduce(static fn ($carry, $journal): string => bcadd($carry, (string) $journal->total_credit, 4), '0.0000');
    @endphp

    <p class="report-note">{{ $entries->count() }} jurnal dengan total debit {{ $rp($grandDebit) }} dan total kredit {{ $rp($grandCredit) }}.</p>

    @forelse($entries as $journal)
        <div class="journal-block">
            <table class="journal-head">
                <tr>
                    <td class="journal-number">{{ $journal->journal_number }}</td>
                    <td style="width: 16%;">{{ $journal->entry_date->format('d/m/Y') }}</td>
                    <td style="width: 16%;"><span class="journal-type">{{ $journal->journal_type->value }}</span></td>
                    <td class="journal-desc">{{ $journal->description }}</td>
                </tr>
            </table>
            <table class="data compact">
                <thead>
                    <tr>
                        <th style="width: 13%;">Kode</th>
                        <th>Nama Akun</th>
                        <th class="num" style="width: 18%;">Debit</th>
                        <th class="num" style="width: 18%;">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->items as $item)
                        <tr>
                            <td class="mono muted">{{ $item->chartOfAccount->account_code }}</td>
                            <td class="{{ bccomp((string) $item->credit, '0.0000', 4) === 1 ? 'indent' : '' }}">{{ $item->chartOfAccount->account_name }}</td>
                            <td class="num">{{ bccomp((string) $item->debit, '0.0000', 4) === 1 ? $rp($item->debit) : '—' }}</td>
                            <td class="num">{{ bccomp((string) $item->credit, '0.0000', 4) === 1 ? $rp($item->credit) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="num">Total jurnal</td>
                        <td class="num">{{ $rp($journal->total_debit) }}</td>
                        <td class="num">{{ $rp($journal->total_credit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <table class="data">
            <tbody><tr><td class="empty">Belum ada jurnal pada periode ini.</td></tr></tbody>
        </table>
    @endforelse

@elseif($tab === 'trial_balance')
    <p class="report-note">
        {{ count($data['rows']) }} akun dengan saldo &middot;
        Status: <strong>{{ $data['is_balanced'] ? 'Seimbang' : 'Tidak seimbang' }}</strong>
    </p>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 14%;">Kode Akun</th>
                <th>Nama Akun</th>
                <th style="width: 16%;">Tipe</th>
                <th class="num" style="width: 18%;">Debit</th>
                <th class="num" style="width: 18%;">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['rows'] as $row)
                <tr>
                    <td class="mono muted">{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    <td>{{ $row['account_type'] }}</td>
                    <td class="num">{{ bccomp((string) $row['debit'], '0.0000', 4) === 1 ? $rp($row['debit']) : '—' }}</td>
                    <td class="num">{{ bccomp((string) $row['credit'], '0.0000', 4) === 1 ? $rp($row['credit']) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">Tidak ada saldo akun pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="num">Total</td>
                <td class="num">{{ $rp($data['total_debit']) }}</td>
                <td class="num">{{ $rp($data['total_credit']) }}</td>
            </tr>
        </tfoot>
    </table>

@elseif($tab === 'income_statement')
    <div class="statement-card">
        <table class="statement">
            <tr class="group"><td colspan="2">Pendapatan</td></tr>
            <tr>
                <td>Pendapatan barang etalase</td>
                <td class="amount">{{ $rp($data['retail_revenue_gross']) }}</td>
            </tr>
            <tr>
                <td class="sub-label">Potongan penjualan</td>
                <td class="amount">{{ $rpParen($data['retail_discount']) }}</td>
            </tr>
            <tr class="subtotal">
                <td>Pendapatan bersih barang</td>
                <td class="amount">{{ $rp($data['retail_revenue_net']) }}</td>
            </tr>
            <tr>
                <td>Pendapatan layanan jasa</td>
                <td class="amount">{{ $rp($data['service_revenue_gross']) }}</td>
            </tr>
            <tr>
                <td class="sub-label">Potongan grosir</td>
                <td class="amount">{{ $rpParen($data['service_discount']) }}</td>
            </tr>
            <tr class="subtotal">
                <td>Pendapatan bersih layanan</td>
                <td class="amount">{{ $rp($data['service_revenue_net']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total pendapatan bersih</td>
                <td class="amount">{{ $rp($data['total_net_revenue']) }}</td>
            </tr>
        </table>
    </div>

    <div class="statement-card">
        <table class="statement">
            <tr class="group"><td colspan="2">Harga Pokok Penjualan</td></tr>
            <tr>
                <td>HPP barang etalase</td>
                <td class="amount">{{ $rpParen($data['retail_cogs']) }}</td>
            </tr>
            <tr>
                <td>HPP bahan layanan</td>
                <td class="amount">{{ $rpParen($data['service_cogs']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total HPP</td>
                <td class="amount">{{ $rpParen($data['total_cogs']) }}</td>
            </tr>
            <tr class="subtotal">
                <td>Laba kotor barang</td>
                <td class="amount">{{ $rpSigned($data['retail_gross_profit']) }}</td>
            </tr>
            <tr class="subtotal">
                <td>Laba kotor layanan</td>
                <td class="amount">{{ $rpSigned($data['service_gross_profit']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total laba kotor</td>
                <td class="amount">{{ $rpSigned($data['total_gross_profit']) }}</td>
            </tr>
        </table>
    </div>

    <div class="statement-card">
        <table class="statement">
            <tr class="group"><td colspan="2">Beban Operasional dan Kerusakan</td></tr>
            @forelse($data['expenses'] as $expense)
                <tr>
                    <td>{{ $expense['account_code'] }} &middot; {{ $expense['account_name'] }}</td>
                    <td class="amount">{{ $rp($expense['amount']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="muted">Tidak ada beban pada periode ini.</td>
                </tr>
            @endforelse
            <tr class="grand-total">
                <td>Total beban</td>
                <td class="amount">{{ $rpParen($data['total_expenses']) }}</td>
            </tr>
        </table>
    </div>

    <table class="statement">
        <tr class="profit">
            <td>Laba bersih periode berjalan</td>
            <td class="amount">{{ $rpSigned($data['net_profit']) }}</td>
        </tr>
    </table>

@elseif($tab === 'balance_sheet')
    <table class="bs-layout">
        <tr>
            <td class="bs-col">
                <div class="bs-card">
                    <p class="section-title">Aset</p>
                    <table class="statement">
                        @forelse($data['assets'] as $asset)
                            <tr>
                                <td>{{ $asset['code'] }} &middot; {{ $asset['name'] }}</td>
                                <td class="amount">{{ $rpSigned($asset['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted">Tidak ada saldo aset.</td></tr>
                        @endforelse
                        <tr class="grand-total">
                            <td>Total Aset</td>
                            <td class="amount">{{ $rpSigned($data['total_assets']) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="bs-gap"></td>
            <td class="bs-col">
                <div class="bs-card">
                    <p class="section-title">Kewajiban</p>
                    <table class="statement">
                        @forelse($data['liabilities'] as $liability)
                            <tr>
                                <td>{{ $liability['code'] }} &middot; {{ $liability['name'] }}</td>
                                <td class="amount">{{ $rpSigned($liability['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted">Tidak ada saldo kewajiban.</td></tr>
                        @endforelse
                        <tr class="subtotal">
                            <td>Total Kewajiban</td>
                            <td class="amount">{{ $rpSigned($data['total_liabilities']) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="bs-card">
                    <p class="section-title">Modal</p>
                    <table class="statement">
                        @forelse($data['equity'] as $equity)
                            <tr>
                                <td>{{ $equity['code'] }} &middot; {{ $equity['name'] }}</td>
                                <td class="amount">{{ $rpSigned($equity['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted">Tidak ada saldo modal.</td></tr>
                        @endforelse
                        <tr class="subtotal">
                            <td>Total Modal</td>
                            <td class="amount">{{ $rpSigned($data['total_equity']) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="bs-total">
                    <table>
                        <tr>
                            <td>Total Kewajiban dan Modal</td>
                            <td class="amount">{{ $rpSigned($data['total_liabilities_and_equity']) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
@endif

<div class="signature-wrap">
    <div class="signature-date">
        Dicetak {{ $printedAt->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB
    </div>

    <table class="signature-grid">
        <tr>
            <td>
                <div class="sig-leading">Mengetahui,</div>
                <div class="sig-role">Penanggung jawab TeFa MPLB</div>
                <div class="sig-space"></div>
                <div class="sig-name">( ........................................ )</div>
                <div class="sig-nip">NIP. ........................................</div>
            </td>
            <td>
                <div class="sig-leading">Diperiksa,</div>
                <div class="sig-role">Guru pembimbing piket</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $printedBy ?: '........................................' }} )</div>
                <div class="sig-nip">NIP. {{ $printedByNip ?: '........................................' }}</div>
            </td>
            <td>
                <div class="sig-leading">Disusun,</div>
                <div class="sig-role">Petugas kasir</div>
                <div class="sig-space"></div>
                <div class="sig-name">( ........................................ )</div>
                <div class="sig-nip">NISN. ........................................</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-footer">
    {{ $companyName }} &middot; {{ $companyTagline }} &middot; Dokumen dihasilkan otomatis oleh sistem
</div>

<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont('Helvetica', 'normal');
    $size = 7;
    $label = 'Halaman {PAGE_NUM} dari {PAGE_COUNT}';
    $textWidth = $fontMetrics->get_text_width($label, $font, $size);
    $x = ($pdf->get_width() - $textWidth) / 2;
    $y = $pdf->get_height() - 26;

    $pdf->page_text($x, $y, $label, $font, $size, [0.55, 0.6, 0.66]);
}
</script>
</body>
</html>
