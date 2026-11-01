<?php

declare(strict_types=1);

namespace App\Actions\Accounting;

use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordCashBookEntryAction
{
    /**
     * Catat entri ke Buku Kas Umum (BKU) dengan running balance yang akurat.
     */
    public function execute(
        int|string $journalEntryId,
        int|string|null $posShiftId,
        int|string $cashAccountId,
        \DateTimeInterface $transactionDate,
        string $mutationDirection,
        \App\Enums\JournalType|string $category,
        string $description,
        string $debitIn,
        string $creditOut,
        int|string $recordedByUserId,
    ): CashBookEntry {
        $categoryValue = $category instanceof \BackedEnum ? $category->value : $category;

        return DB::transaction(function () use (
            $journalEntryId,
            $posShiftId,
            $cashAccountId,
            $transactionDate,
            $mutationDirection,
            $categoryValue,
            $description,
            $debitIn,
            $creditOut,
            $recordedByUserId
        ): CashBookEntry {
            $cashAccount = ChartOfAccount::where('id', $cashAccountId)
                ->where('account_subtype', 'CASH_BANK')
                ->lockForUpdate()
                ->firstOrFail();

            $journalEntry = JournalEntry::findOrFail($journalEntryId);

            $posShift = null;
            if ($posShiftId) {
                $posShift = PosShift::findOrFail($posShiftId);
            }

            $transactionDate = Carbon::parse($transactionDate)->startOfSecond();
            $debitIn = number_format((float) $debitIn, 4, '.', '');
            $creditOut = number_format((float) $creditOut, 4, '.', '');
            $delta = bcsub($debitIn, $creditOut, 4);

            // Kunci seluruh entri akun kas agar saldo berjalan konsisten saat ada
            // entri backdate (mis. jurnal pembalik periode lama).
            $existingEntries = CashBookEntry::where('cash_account_id', $cashAccount->id)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $previousBalance = '0.0000';

            foreach ($existingEntries as $existingEntry) {
                if ($existingEntry->transaction_date->lte($transactionDate)) {
                    $previousBalance = (string) $existingEntry->running_balance;
                }
            }

            $newBalance = bcadd($previousBalance, $delta, 4);

            $bkuNumber = 'BKU-' . $transactionDate->format('Ymd') . '-' .
                str_pad((string) (CashBookEntry::whereDate('transaction_date', $transactionDate->format('Y-m-d'))->count() + 1), 4, '0', STR_PAD_LEFT);

            $entry = CashBookEntry::create([
                'bku_number' => $bkuNumber,
                'pos_shift_id' => $posShift?->id,
                'journal_entry_id' => $journalEntry->id,
                'cash_account_id' => $cashAccount->id,
                'transaction_date' => $transactionDate,
                'mutation_direction' => $mutationDirection,
                'category' => $categoryValue,
                'description' => $description,
                'debit_in' => $debitIn,
                'credit_out' => $creditOut,
                'running_balance' => $newBalance,
                'recorded_by_user_id' => $recordedByUserId,
            ]);

            // Jika entri baru disisipkan di tengah riwayat, geser saldo berjalan
            // seluruh entri setelahnya sebesar delta yang sama.
            $laterEntries = CashBookEntry::where('cash_account_id', $cashAccount->id)
                ->whereKeyNot($entry->id)
                ->where(function ($query) use ($transactionDate, $entry): void {
                    $query->where('transaction_date', '>', $transactionDate)
                        ->orWhere(function ($sameDateQuery) use ($transactionDate, $entry): void {
                            $sameDateQuery->where('transaction_date', $transactionDate)
                                ->where('id', '>', $entry->id);
                        });
                })
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($laterEntries as $laterEntry) {
                $laterEntry->update([
                    'running_balance' => bcadd((string) $laterEntry->running_balance, $delta, 4),
                ]);
            }

            return $entry;
        });
    }
}
