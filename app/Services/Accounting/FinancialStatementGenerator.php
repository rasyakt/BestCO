<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class FinancialStatementGenerator
{
    /**
     * Laporan Buku Kas Umum (BKU) dengan running balance presisi.
     */
    public function getCashBook(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = CashBookEntry::with(['journalEntry', 'cashAccount', 'recordedBy', 'posShift'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($startDate) {
            $query->whereDate('transaction_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('transaction_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Laporan Jurnal Umum (General Journal).
     */
    public function getGeneralJournal(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = JournalEntry::with(['items.chartOfAccount', 'createdBy', 'posShift'])
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Buku Besar (General Ledger) per akun perkiraan.
     */
    public function getGeneralLedger(int|string $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = ChartOfAccount::findOrFail($accountId);

        $itemsQuery = JournalItem::with('journalEntry')
            ->where('chart_of_account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate): void {
                if ($startDate) {
                    $q->whereDate('entry_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->whereDate('entry_date', '<=', $endDate);
                }
            })
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_items.id', 'asc')
            ->select('journal_items.*');

        $items = $itemsQuery->get();

        $runningBalance = '0.0000';
        $ledgerRows = [];

        foreach ($items as $item) {
            $debit = (string) $item->debit;
            $credit = (string) $item->credit;

            if ($account->normal_balance === NormalBalance::DEBIT) {
                $runningBalance = bcadd($runningBalance, $debit, 4);
                $runningBalance = bcsub($runningBalance, $credit, 4);
            } else {
                $runningBalance = bcadd($runningBalance, $credit, 4);
                $runningBalance = bcsub($runningBalance, $debit, 4);
            }

            $ledgerRows[] = [
                'date' => $item->journalEntry->entry_date->format('d/m/Y'),
                'journal_number' => $item->journalEntry->journal_number,
                'description' => $item->line_description ?: $item->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => $account,
            'rows' => $ledgerRows,
            'final_balance' => $runningBalance,
        ];
    }

    /**
     * Neraca Saldo (Trial Balance).
     */
    public function getTrialBalance(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = ChartOfAccount::orderBy('account_code')->get();

        $totalDebit = '0.0000';
        $totalCredit = '0.0000';
        $rows = [];

        foreach ($accounts as $account) {
            $itemsQuery = JournalItem::where('chart_of_account_id', $account->id);

            if ($startDate || $endDate) {
                $itemsQuery->whereHas('journalEntry', function ($q) use ($startDate, $endDate): void {
                    if ($startDate) {
                        $q->whereDate('entry_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->whereDate('entry_date', '<=', $endDate);
                    }
                });
            }

            $sumDebit = number_format((float) $itemsQuery->sum('debit'), 4, '.', '');
            $sumCredit = number_format((float) $itemsQuery->sum('credit'), 4, '.', '');

            if (bccomp($sumDebit, '0.0000', 4) === 0 && bccomp($sumCredit, '0.0000', 4) === 0) {
                continue;
            }

            $netBalance = bcsub($sumDebit, $sumCredit, 4);

            $debitBalance = '0.0000';
            $creditBalance = '0.0000';

            if ($account->normal_balance === NormalBalance::DEBIT) {
                if (bccomp($netBalance, '0.0000', 4) >= 0) {
                    $debitBalance = $netBalance;
                } else {
                    $creditBalance = bcmul($netBalance, '-1', 4);
                }
            } else {
                $creditNet = bcsub($sumCredit, $sumDebit, 4);
                if (bccomp($creditNet, '0.0000', 4) >= 0) {
                    $creditBalance = $creditNet;
                } else {
                    $debitBalance = bcmul($creditNet, '-1', 4);
                }
            }

            $totalDebit = bcadd($totalDebit, $debitBalance, 4);
            $totalCredit = bcadd($totalCredit, $creditBalance, 4);

            $rows[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->name,
                'account_type' => $account->account_type->value,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => bccomp($totalDebit, $totalCredit, 4) === 0,
        ];
    }

    /**
     * Laporan Laba Rugi Komprehensif (Income Statement) dengan pemisahan segmen Ritel vs Jasa.
     */
    public function getIncomeStatement(?string $startDate = null, ?string $endDate = null): array
    {
        $revenueAccounts = ChartOfAccount::where('account_type', AccountType::REVENUE)
            ->orderBy('account_code')
            ->get();

        $cogsAccounts = ChartOfAccount::where('account_type', AccountType::COGS)
            ->orderBy('account_code')
            ->get();

        $expenseAccounts = ChartOfAccount::where('account_type', AccountType::EXPENSE)
            ->orderBy('account_code')
            ->get();

        // Kalkulasi Pendapatan Segmen
        $retailRevenue = '0.0000';
        $retailDiscount = '0.0000';
        $serviceRevenue = '0.0000';
        $serviceDiscount = '0.0000';
        $otherRevenue = '0.0000';

        foreach ($revenueAccounts as $acct) {
            $bal = $this->getAccountNetCreditBalance($acct->id, $startDate, $endDate);
            if ($acct->account_code === '4-1101') {
                $retailRevenue = bcadd($retailRevenue, $bal, 4);
            } elseif ($acct->account_code === '4-1102') {
                $retailDiscount = bcadd($retailDiscount, $bal, 4);
            } elseif (str_starts_with($acct->account_code, '4-21') && $acct->account_code !== '4-2109') {
                $serviceRevenue = bcadd($serviceRevenue, $bal, 4);
            } elseif ($acct->account_code === '4-2109') {
                $serviceDiscount = bcadd($serviceDiscount, $bal, 4);
            } else {
                $otherRevenue = bcadd($otherRevenue, $bal, 4);
            }
        }

        $netRetailRevenue = bcsub($retailRevenue, $retailDiscount, 4);
        $netServiceRevenue = bcsub($serviceRevenue, $serviceDiscount, 4);
        $totalNetRevenue = bcadd(bcadd($netRetailRevenue, $netServiceRevenue, 4), $otherRevenue, 4);

        // Kalkulasi HPP (COGS)
        $cogsRetail = '0.0000';
        $cogsService = '0.0000';
        foreach ($cogsAccounts as $acct) {
            $bal = $this->getAccountNetDebitBalance($acct->id, $startDate, $endDate);
            if ($acct->account_code === '5-1101') {
                $cogsRetail = bcadd($cogsRetail, $bal, 4);
            } else {
                $cogsService = bcadd($cogsService, $bal, 4);
            }
        }
        $totalCogs = bcadd($cogsRetail, $cogsService, 4);

        // Laba Kotor (Gross Profit)
        $grossProfitRetail = bcsub($netRetailRevenue, $cogsRetail, 4);
        $grossProfitService = bcsub($netServiceRevenue, $cogsService, 4);
        $totalGrossProfit = bcsub($totalNetRevenue, $totalCogs, 4);

        // Beban Operasional (Expenses)
        $expenses = [];
        $totalExpenses = '0.0000';
        foreach ($expenseAccounts as $acct) {
            $bal = $this->getAccountNetDebitBalance($acct->id, $startDate, $endDate);
            if (bccomp($bal, '0.0000', 4) !== 0) {
                $expenses[] = [
                    'account_code' => $acct->account_code,
                    'account_name' => $acct->name,
                    'amount' => $bal,
                ];
                $totalExpenses = bcadd($totalExpenses, $bal, 4);
            }
        }

        // Laba Bersih (Net Profit)
        $netProfit = bcsub($totalGrossProfit, $totalExpenses, 4);

        return [
            'retail_revenue_gross' => $retailRevenue,
            'retail_discount' => $retailDiscount,
            'retail_revenue_net' => $netRetailRevenue,
            'retail_cogs' => $cogsRetail,
            'retail_gross_profit' => $grossProfitRetail,

            'service_revenue_gross' => $serviceRevenue,
            'service_discount' => $serviceDiscount,
            'service_revenue_net' => $netServiceRevenue,
            'service_cogs' => $cogsService,
            'service_gross_profit' => $grossProfitService,

            'total_net_revenue' => $totalNetRevenue,
            'total_cogs' => $totalCogs,
            'total_gross_profit' => $totalGrossProfit,

            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Neraca Keuangan (Balance Sheet).
     */
    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $assets = ChartOfAccount::where('account_type', AccountType::ASSET)->orderBy('account_code')->get();
        $liabilities = ChartOfAccount::where('account_type', AccountType::LIABILITY)->orderBy('account_code')->get();
        $equity = ChartOfAccount::where('account_type', AccountType::EQUITY)->orderBy('account_code')->get();

        $assetRows = [];
        $totalAssets = '0.0000';
        foreach ($assets as $acct) {
            $bal = $this->getAccountNetDebitBalance($acct->id, null, $asOfDate);
            if (bccomp($bal, '0.0000', 4) !== 0) {
                $assetRows[] = ['code' => $acct->account_code, 'name' => $acct->name, 'amount' => $bal];
                $totalAssets = bcadd($totalAssets, $bal, 4);
            }
        }

        $liabilityRows = [];
        $totalLiabilities = '0.0000';
        foreach ($liabilities as $acct) {
            $bal = $this->getAccountNetCreditBalance($acct->id, null, $asOfDate);
            if (bccomp($bal, '0.0000', 4) !== 0) {
                $liabilityRows[] = ['code' => $acct->account_code, 'name' => $acct->name, 'amount' => $bal];
                $totalLiabilities = bcadd($totalLiabilities, $bal, 4);
            }
        }

        $equityRows = [];
        $totalEquity = '0.0000';
        foreach ($equity as $acct) {
            $bal = $this->getAccountNetCreditBalance($acct->id, null, $asOfDate);
            if (bccomp($bal, '0.0000', 4) !== 0) {
                $equityRows[] = ['code' => $acct->account_code, 'name' => $acct->name, 'amount' => $bal];
                $totalEquity = bcadd($totalEquity, $bal, 4);
            }
        }

        // Tambahkan Laba Tahun Berjalan ke Ekuitas
        $incomeStatement = $this->getIncomeStatement(null, $asOfDate);
        $currentYearProfit = $incomeStatement['net_profit'];

        if (bccomp($currentYearProfit, '0.0000', 4) !== 0) {
            $equityRows[] = [
                'code' => '3-2000',
                'name' => 'Laba Bersih Periode Berjalan',
                'amount' => $currentYearProfit,
            ];
            $totalEquity = bcadd($totalEquity, $currentYearProfit, 4);
        }

        $totalLiabilitiesAndEquity = bcadd($totalLiabilities, $totalEquity, 4);

        return [
            'assets' => $assetRows,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilityRows,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equityRows,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => bccomp($totalAssets, $totalLiabilitiesAndEquity, 4) === 0,
        ];
    }

    private function getAccountNetDebitBalance(int|string $accountId, ?string $startDate, ?string $endDate): string
    {
        $q = JournalItem::where('chart_of_account_id', $accountId);
        if ($startDate || $endDate) {
            $q->whereHas('journalEntry', function ($sub) use ($startDate, $endDate): void {
                if ($startDate) {
                    $sub->whereDate('entry_date', '>=', $startDate);
                }
                if ($endDate) {
                    $sub->whereDate('entry_date', '<=', $endDate);
                }
            });
        }

        $debit = number_format((float) $q->sum('debit'), 4, '.', '');
        $credit = number_format((float) $q->sum('credit'), 4, '.', '');

        return bcsub($debit, $credit, 4);
    }

    private function getAccountNetCreditBalance(int|string $accountId, ?string $startDate, ?string $endDate): string
    {
        $q = JournalItem::where('chart_of_account_id', $accountId);
        if ($startDate || $endDate) {
            $q->whereHas('journalEntry', function ($sub) use ($startDate, $endDate): void {
                if ($startDate) {
                    $sub->whereDate('entry_date', '>=', $startDate);
                }
                if ($endDate) {
                    $sub->whereDate('entry_date', '<=', $endDate);
                }
            });
        }

        $debit = number_format((float) $q->sum('debit'), 4, '.', '');
        $credit = number_format((float) $q->sum('credit'), 4, '.', '');

        return bcsub($credit, $debit, 4);
    }
}

