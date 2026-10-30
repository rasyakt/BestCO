@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header & Date Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Laporan Keuangan & Buku Kas Umum (BKU)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Siklus akuntansi lengkap double-entry otomatis untuk laboratorium Teaching Factory MPLB.</p>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2 text-xs">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white">
            </div>
            <span class="text-slate-400">s/d</span>
            <div>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-2.5 py-1.5 rounded border border-slate-300 bg-white">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded font-semibold">
                Filter
            </button>
            <button type="button" onclick="window.print()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold border border-slate-300">
                🖨️ Cetak
            </button>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 space-x-1 text-xs overflow-x-auto">
        <a href="{{ route('reports.index', ['tab' => 'bku', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="px-4 py-2.5 font-bold border-b-2 whitespace-nowrap {{ $tab === 'bku' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Buku Kas Umum (BKU)
        </a>
        <a href="{{ route('reports.index', ['tab' => 'journal', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="px-4 py-2.5 font-bold border-b-2 whitespace-nowrap {{ $tab === 'journal' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Jurnal Umum
        </a>
        <a href="{{ route('reports.index', ['tab' => 'trial_balance', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="px-4 py-2.5 font-bold border-b-2 whitespace-nowrap {{ $tab === 'trial_balance' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Neraca Saldo
        </a>
        <a href="{{ route('reports.index', ['tab' => 'income_statement', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="px-4 py-2.5 font-bold border-b-2 whitespace-nowrap {{ $tab === 'income_statement' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Laba Rugi (Segmen)
        </a>
        <a href="{{ route('reports.index', ['tab' => 'balance_sheet', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="px-4 py-2.5 font-bold border-b-2 whitespace-nowrap {{ $tab === 'balance_sheet' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Neraca Keuangan
        </a>
    </div>

    <!-- Content Card -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        @if($tab === 'bku')
            <!-- TAB 1: BUKU KAS UMUM -->
            <div class="space-y-3">
                <div class="flex justify-between items-center border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Buku Kas Umum (BKU)</h2>
                    <span class="text-xs text-slate-500">Periode: {{ date('d/m/Y', strtotime($startDate)) }} &mdash; {{ date('d/m/Y', strtotime($endDate)) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border border-slate-200">
                        <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                            <tr>
                                <th class="py-2.5 px-3 border-r">No BKU</th>
                                <th class="py-2.5 px-3 border-r">Tanggal</th>
                                <th class="py-2.5 px-3 border-r">Akun Kas</th>
                                <th class="py-2.5 px-3 border-r">Uraian Transaksi</th>
                                <th class="py-2.5 px-3 border-r text-right">Penerimaan (Debit)</th>
                                <th class="py-2.5 px-3 border-r text-right">Pengeluaran (Kredit)</th>
                                <th class="py-2.5 px-3 text-right">Saldo Kas Berjalan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data['entries'] as $e)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2 px-3 font-mono text-[11px] border-r text-slate-700">{{ $e->bku_number }}</td>
                                    <td class="py-2 px-3 border-r text-slate-600">{{ $e->transaction_date->format('d/m/Y') }}</td>
                                    <td class="py-2 px-3 border-r font-medium text-slate-800">{{ $e->cashAccount->name }}</td>
                                    <td class="py-2 px-3 border-r text-slate-700">{{ $e->description }}</td>
                                    <td class="py-2 px-3 border-r text-right num-tabular text-emerald-700">
                                        {{ bccomp((string) $e->debit_in, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->debit_in, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2 px-3 border-r text-right num-tabular text-rose-700">
                                        {{ bccomp((string) $e->credit_out, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $e->credit_out, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2 px-3 text-right font-bold num-tabular text-slate-900">
                                        Rp {{ number_format((float) $e->running_balance, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada mutasi kas pada periode yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($tab === 'journal')
            <!-- TAB 2: JURNAL UMUM -->
            <div class="space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Jurnal Umum Transaksi (Double-Entry)</h2>
                    <span class="text-xs text-slate-500">Semua posting bersifat immutable</span>
                </div>

                <div class="space-y-4">
                    @forelse($data['entries'] as $jrn)
                        <div class="border border-slate-200 rounded-lg overflow-hidden text-xs">
                            <div class="bg-slate-50 p-2.5 border-b flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-slate-900">{{ $jrn->journal_number }}</span>
                                    <span class="text-slate-500">{{ $jrn->entry_date->format('d/m/Y') }}</span>
                                    <span class="px-2 py-0.5 rounded font-mono text-[10px] bg-white border">{{ $jrn->journal_type->value }}</span>
                                </div>
                                <span class="text-slate-600 font-medium">{{ $jrn->description }}</span>
                            </div>
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($jrn->items as $it)
                                        <tr>
                                            <td class="py-1.5 px-3 w-28 font-mono text-slate-500">{{ $it->chartOfAccount->account_code }}</td>
                                            <td class="py-1.5 px-3 {{ bccomp((string) $it->credit, '0.0000', 4) === 1 ? 'pl-8 text-slate-600' : 'font-semibold text-slate-800' }}">
                                                {{ $it->chartOfAccount->name }}
                                            </td>
                                            <td class="py-1.5 px-3 text-right num-tabular w-36">
                                                {{ bccomp((string) $it->debit, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $it->debit, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="py-1.5 px-3 text-right num-tabular w-36">
                                                {{ bccomp((string) $it->credit, '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $it->credit, 0, ',', '.') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50/70 border-t font-bold text-slate-800">
                                    <tr>
                                        <td colspan="2" class="py-1.5 px-3 text-right">Total:</td>
                                        <td class="py-1.5 px-3 text-right num-tabular">Rp {{ number_format((float) $jrn->total_debit, 0, ',', '.') }}</td>
                                        <td class="py-1.5 px-3 text-right num-tabular">Rp {{ number_format((float) $jrn->total_credit, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">Belum ada jurnal transaksi.</div>
                    @endforelse
                </div>
            </div>

        @elseif($tab === 'trial_balance')
            <!-- TAB 3: NERACA SALDO -->
            <div class="space-y-3">
                <div class="flex justify-between items-center border-b pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Neraca Saldo (Trial Balance)</h2>
                    <span class="text-xs font-bold {{ $data['is_balanced'] ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $data['is_balanced'] ? '✓ SEIMBANG (DEBIT = KREDIT)' : '⚠ TIDAK SEIMBANG' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border border-slate-200">
                        <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                            <tr>
                                <th class="py-2.5 px-3 border-r w-28">Kode Akun</th>
                                <th class="py-2.5 px-3 border-r">Nama Akun</th>
                                <th class="py-2.5 px-3 border-r w-28">Tipe</th>
                                <th class="py-2.5 px-3 border-r text-right w-40">Debit</th>
                                <th class="py-2.5 px-3 text-right w-40">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($data['rows'] as $r)
                                <tr>
                                    <td class="py-2 px-3 border-r font-mono">{{ $r['account_code'] }}</td>
                                    <td class="py-2 px-3 border-r font-medium text-slate-800">{{ $r['account_name'] }}</td>
                                    <td class="py-2 px-3 border-r text-slate-500">{{ $r['account_type'] }}</td>
                                    <td class="py-2 px-3 border-r text-right num-tabular font-medium">
                                        {{ bccomp($r['debit'], '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $r['debit'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2 px-3 text-right num-tabular font-medium">
                                        {{ bccomp($r['credit'], '0.0000', 4) === 1 ? 'Rp ' . number_format((float) $r['credit'], 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100 border-t font-bold text-slate-900">
                            <tr>
                                <td colspan="3" class="py-2.5 px-3 text-right">TOTAL:</td>
                                <td class="py-2.5 px-3 border-r text-right num-tabular">Rp {{ number_format((float) $data['total_debit'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right num-tabular">Rp {{ number_format((float) $data['total_credit'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @elseif($tab === 'income_statement')
            <!-- TAB 4: LABA RUGI SEGMEN -->
            <div class="space-y-6 max-w-4xl mx-auto text-xs">
                <div class="text-center border-b pb-3">
                    <h2 class="text-base font-bold text-slate-900">LAPORAN LABA RUGI KOMPREHENSIF</h2>
                    <p class="text-slate-500 mt-0.5">Teaching Factory & Unit Bisnis MPLB &bull; Periode s/d {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>

                <div class="space-y-4">
                    <!-- Segmen 1: Perdagangan ATK Etalase -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-2">
                        <h3 class="font-bold text-xs text-slate-900 border-b pb-1 uppercase tracking-wider">A. Segmen Ritel (ATK Etalase)</h3>
                        <div class="flex justify-between py-1">
                            <span>Pendapatan Penjualan Ritel (Bruto)</span>
                            <span class="num-tabular font-medium">Rp {{ number_format((float) $data['retail_revenue_gross'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-slate-500">
                            <span>Potongan Penjualan Ritel</span>
                            <span class="num-tabular">(Rp {{ number_format((float) $data['retail_discount'], 0, ',', '.') }})</span>
                        </div>
                        <div class="flex justify-between py-1 font-semibold border-t">
                            <span>Pendapatan Bersih Ritel</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['retail_revenue_net'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-rose-700">
                            <span>Harga Pokok Penjualan (HPP) Ritel</span>
                            <span class="num-tabular font-medium">(Rp {{ number_format((float) $data['retail_cogs'], 0, ',', '.') }})</span>
                        </div>
                        <div class="flex justify-between py-1.5 font-bold border-t border-slate-300 text-slate-900">
                            <span>Laba Kotor Segmen Ritel</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['retail_gross_profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Segmen 2: Layanan Jasa (Fotocopy, Print, Jilid) -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-2">
                        <h3 class="font-bold text-xs text-slate-900 border-b pb-1 uppercase tracking-wider">B. Segmen Layanan Jasa (Fotocopy, Print, Laminating)</h3>
                        <div class="flex justify-between py-1">
                            <span>Pendapatan Layanan Jasa (Bruto)</span>
                            <span class="num-tabular font-medium">Rp {{ number_format((float) $data['service_revenue_gross'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-slate-500">
                            <span>Potongan Grosir Layanan Jasa (Tier)</span>
                            <span class="num-tabular">(Rp {{ number_format((float) $data['service_discount'], 0, ',', '.') }})</span>
                        </div>
                        <div class="flex justify-between py-1 font-semibold border-t">
                            <span>Pendapatan Bersih Layanan Jasa</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['service_revenue_net'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-rose-700">
                            <span>HPP Bahan Baku Jasa (Kertas + Tinta BoM)</span>
                            <span class="num-tabular font-medium">(Rp {{ number_format((float) $data['service_cogs'], 0, ',', '.') }})</span>
                        </div>
                        <div class="flex justify-between py-1.5 font-bold border-t border-slate-300 text-slate-900">
                            <span>Laba Kotor Segmen Layanan Jasa</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['service_gross_profit'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Total Laba Kotor Gabungan -->
                    <div class="p-3 bg-brand-50 border border-brand-200 rounded-lg flex justify-between items-center font-bold text-brand-900">
                        <span>TOTAL LABA KOTOR GABUNGAN (A + B)</span>
                        <span class="text-sm num-tabular">Rp {{ number_format((float) $data['total_gross_profit'], 0, ',', '.') }}</span>
                    </div>

                    <!-- Beban Operasional -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 space-y-2">
                        <h3 class="font-bold text-xs text-slate-900 border-b pb-1 uppercase tracking-wider">C. Beban Operasional & Kerusakan Produksi</h3>
                        @forelse($data['expenses'] as $exp)
                            <div class="flex justify-between py-1 text-slate-700">
                                <span>{{ $exp['account_code'] }} &bull; {{ $exp['account_name'] }}</span>
                                <span class="num-tabular">Rp {{ number_format((float) $exp['amount'], 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="py-1 text-slate-400">Tidak ada beban operasional tercatat pada periode ini.</div>
                        @endforelse
                        <div class="flex justify-between py-1.5 font-semibold border-t text-rose-800">
                            <span>Total Beban Operasional</span>
                            <span class="num-tabular">(Rp {{ number_format((float) $data['total_expenses'], 0, ',', '.') }})</span>
                        </div>
                    </div>

                    <!-- Laba Bersih -->
                    <div class="p-4 bg-slate-900 text-white rounded-lg flex justify-between items-center font-bold">
                        <span class="text-xs uppercase tracking-wider">LABA BERSIH OPERASIONAL (NET PROFIT)</span>
                        <span class="text-base num-tabular text-emerald-400">
                            Rp {{ number_format((float) $data['net_profit'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

        @elseif($tab === 'balance_sheet')
            <!-- TAB 5: NERACA KEUANGAN -->
            <div class="space-y-6 max-w-4xl mx-auto text-xs">
                <div class="text-center border-b pb-3">
                    <h2 class="text-base font-bold text-slate-900">NERACA KEUANGAN (BALANCE SHEET)</h2>
                    <p class="text-slate-500 mt-0.5">Posisi Per Tanggal {{ date('d/m/Y', strtotime($endDate)) }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Aktiva / Aset -->
                    <div class="space-y-3">
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <h3 class="font-bold text-xs text-slate-900 border-b pb-1 mb-2 uppercase tracking-wider">ASET (AKTIVA)</h3>
                            <div class="space-y-1.5">
                                @forelse($data['assets'] as $ast)
                                    <div class="flex justify-between py-0.5 text-slate-700">
                                        <span>{{ $ast['code'] }} &bull; {{ $ast['name'] }}</span>
                                        <span class="num-tabular font-medium">Rp {{ number_format((float) $ast['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <div class="text-slate-400">Tidak ada saldo aset.</div>
                                @endforelse
                            </div>
                            <div class="pt-3 mt-3 border-t border-slate-300 flex justify-between font-bold text-slate-900">
                                <span>TOTAL ASET</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_assets'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pasiva: Kewajiban + Ekuitas -->
                    <div class="space-y-4">
                        <!-- Kewajiban -->
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <h3 class="font-bold text-xs text-slate-900 border-b pb-1 mb-2 uppercase tracking-wider">KEWAJIBAN (LIABILITAS)</h3>
                            <div class="space-y-1.5">
                                @forelse($data['liabilities'] as $lia)
                                    <div class="flex justify-between py-0.5 text-slate-700">
                                        <span>{{ $lia['code'] }} &bull; {{ $lia['name'] }}</span>
                                        <span class="num-tabular font-medium">Rp {{ number_format((float) $lia['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <div class="text-slate-400">Tidak ada saldo liabilitas.</div>
                                @endforelse
                            </div>
                            <div class="pt-2 mt-2 border-t flex justify-between font-semibold text-slate-800">
                                <span>Total Kewajiban</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_liabilities'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Ekuitas -->
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <h3 class="font-bold text-xs text-slate-900 border-b pb-1 mb-2 uppercase tracking-wider">EKUITAS (MODAL)</h3>
                            <div class="space-y-1.5">
                                @forelse($data['equity'] as $eq)
                                    <div class="flex justify-between py-0.5 text-slate-700">
                                        <span>{{ $eq['code'] }} &bull; {{ $eq['name'] }}</span>
                                        <span class="num-tabular font-medium">Rp {{ number_format((float) $eq['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <div class="text-slate-400">Tidak ada saldo ekuitas.</div>
                                @endforelse
                            </div>
                            <div class="pt-2 mt-2 border-t flex justify-between font-semibold text-slate-800">
                                <span>Total Ekuitas</span>
                                <span class="num-tabular">Rp {{ number_format((float) $data['total_equity'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Total Liabilitas & Ekuitas -->
                        <div class="p-3 bg-slate-900 text-white rounded-lg flex justify-between items-center font-bold">
                            <span>TOTAL KEWAJIBAN & EKUITAS</span>
                            <span class="num-tabular">Rp {{ number_format((float) $data['total_liabilities_and_equity'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
