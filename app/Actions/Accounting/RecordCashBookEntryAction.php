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

            // Ambil saldo berjalan terakhir untuk akun kas ini (urutan kronologis).
            $lastEntry = CashBookEntry::where('cash_account_id', $cashAccountId)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->latest('id')
                ->first();

            $previousBalance = $lastEntry ? (string) $lastEntry->running_balance : '0.0000';

            $debitIn = number_format((float) $debitIn, 4, '.', '');
            $creditOut = number_format((float) $creditOut, 4, '.', '');

            $newBalance = bcadd($previousBalance, $debitIn, 4);
            $newBalance = bcsub($newBalance, $creditOut, 4);

            $bkuNumber = 'BKU-' . Carbon::parse($transactionDate)->format('Ymd') . '-' .
                str_pad((string) (CashBookEntry::whereDate('transaction_date', Carbon::parse($transactionDate)->format('Y-m-d'))->count() + 1), 4, '0', STR_PAD_LEFT);

            return CashBookEntry::create([
                'bku_number' => $bkuNumber,
                'pos_shift_id' => $posShift?->id,
                'journal_entry_id' => $journalEntry->id,
                'cash_account_id' => $cashAccount->id,
                'transaction_date' => $transactionDate instanceof Carbon
                    ? $transactionDate
                    : Carbon::parse($transactionDate),
                'mutation_direction' => $mutationDirection,
                'category' => $categoryValue,
                'description' => $description,
                'debit_in' => $debitIn,
                'credit_out' => $creditOut,
                'running_balance' => $newBalance,
                'recorded_by_user_id' => $recordedByUserId,
            ]);
        });
    }
}
