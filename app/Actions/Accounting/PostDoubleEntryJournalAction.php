<?php

declare(strict_types=1);

namespace App\Actions\Accounting;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\NormalBalance;
use App\Exceptions\UnbalancedJournalException;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class PostDoubleEntryJournalAction
{
    /**
     * Posting jurnal double-entry yang atomik dan immutable.
     *
     * @throws UnbalancedJournalException jika total debit != total credit atau total <= 0.
     */
    public function execute(JournalEntryData $data): JournalEntry
    {
        return DB::transaction(function () use ($data): JournalEntry {
            // Validasi periode akuntansi masih terbuka.
            $period = AccountingPeriod::where('id', $data->accountingPeriodId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($period->is_closed) {
                throw new \DomainException('Akuntansi periode sudah ditutup, tidak bisa posting jurnal.');
            }

            // Validasi keseimbangan jurnal menggunakan bcmath (scale 4).
            $totalDebit = '0.0000';
            $totalCredit = '0.0000';

            foreach ($data->lines as $line) {
                $totalDebit = bcadd($totalDebit, $line->debit, 4);
                $totalCredit = bcadd($totalCredit, $line->credit, 4);
            }

            if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
                throw new UnbalancedJournalException(
                    'Jurnal tidak seimbang: total debit ' . $totalDebit . ' != total kredit ' . $totalCredit . '.'
                );
            }

            if (bccomp($totalDebit, '0.0000', 4) !== 1) {
                throw new UnbalancedJournalException('Total debit jurnal harus lebih besar dari nol.');
            }

            // Lock semua akun yang terlibat untuk mencegah race condition.
            $accountCodes = array_unique(array_map(fn (JournalLineData $line): string => $line->chartOfAccountCode, $data->lines));
            $accounts = ChartOfAccount::whereIn('account_code', $accountCodes)
                ->lockForUpdate()
                ->get()
                ->keyBy('account_code');

            // Persist journal_entries.
            $journalEntry = JournalEntry::create([
                'journal_number' => $data->journalNumber,
                'accounting_period_id' => $data->accountingPeriodId,
                'pos_shift_id' => $data->posShiftId,
                'entry_date' => $data->entryDate->format('Y-m-d'),
                'journal_type' => $data->journalType->value,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId,
                'description' => $data->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_posted' => true,
                'is_reversed' => false,
                'reversed_by_journal_id' => $data->reversedByJournalId,
                'created_by_user_id' => $data->createdByUserId,
                'posted_at' => Carbon::now(),
            ]);

            // Persist journal_items dan update saldo akun.
            foreach ($data->lines as $line) {
                $account = $accounts->get($line->chartOfAccountCode);
                if (!$account) {
                    throw new \DomainException('Chart of Account dengan kode ' . $line->chartOfAccountCode . ' tidak ditemukan.');
                }

                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'chart_of_account_id' => $account->id,
                    'line_description' => $line->lineDescription,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                ]);

                // Update current_balance berdasarkan normal_balance.
                $this->updateAccountBalance($account, $line->debit, $line->credit);
            }

            // Untuk setiap baris yang menyentuh akun CASH_BANK, catat otomatis ke Buku Kas Umum.
            // RecordCashBookEntryAction akan dipanggil oleh pemanggil untuk transaksi POS,
            // namun di sini kita memastikan BKU tercantum untuk jurnal kas umum.
            $this->recordCashBookEntries($journalEntry, $data);

            return $journalEntry->load('items.chartOfAccount');
        });
    }

    /**
     * Update saldo aktual chart_of_accounts berdasarkan normal balance.
     */
    private function updateAccountBalance(ChartOfAccount $account, string $debit, string $credit): void
    {
        $current = (string) $account->current_balance;
        $newBalance = $current;

        if ($account->normal_balance === NormalBalance::DEBIT) {
            // Saldo debit bertambah dengan debit, berkurang dengan kredit.
            $newBalance = bcadd($current, $debit, 4);
            $newBalance = bcsub($newBalance, $credit, 4);
        } else {
            // Saldo kredit bertambah dengan kredit, berkurang dengan debit.
            $newBalance = bcadd($current, $credit, 4);
            $newBalance = bcsub($newBalance, $debit, 4);
        }

        $account->update(['current_balance' => $newBalance]);
    }

    /**
     * Catat entri Buku Kas Umum untuk baris jurnal yang menyentuh akun kas/bank.
     */
    private function recordCashBookEntries(JournalEntry $journalEntry, JournalEntryData $data): void
    {
        $cashBookService = app(\App\Actions\Accounting\RecordCashBookEntryAction::class);

        foreach ($journalEntry->items as $item) {
            $account = $item->chartOfAccount;
            if (!$account->isCashBank()) {
                continue;
            }

            $debit = $item->debit;
            $credit = $item->credit;

            $cashBookService->execute(
                journalEntryId: (string) $journalEntry->id,
                posShiftId: $data->posShiftId,
                cashAccountId: (string) $account->id,
                transactionDate: $data->entryDate,
                mutationDirection: $debit !== '0.0000' ? 'IN' : 'OUT',
                category: $journalEntry->journal_type instanceof \BackedEnum ? $journalEntry->journal_type->value : (string) $journalEntry->journal_type,
                description: $journalEntry->description . ' (BKU otomatis dari ' . $journalEntry->journal_number . ')',
                debitIn: $debit,
                creditOut: $credit,
                recordedByUserId: $data->createdByUserId,
            );
        }
    }
}
