<?php

declare(strict_types=1);

namespace App\Actions\Piket;

use App\Actions\Accounting\PostDoubleEntryJournalAction;
use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\JournalType;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ClosePosShiftAction
{
    /**
     * Tutup shift kasir: hitung system_expected_cash, bandingkan dengan actual_closing_cash,
     * posting jurnal selisih kas jika ada, dan tandai shift sebagai CLOSED.
     */
    public function execute(
        string $posShiftId,
        string $actualClosingCash,
        string $closedByUserId,
        ?string $closingNotes = null
    ): PosShift {
        return DB::transaction(function () use ($posShiftId, $actualClosingCash, $closedByUserId, $closingNotes): PosShift {
            $shift = PosShift::where('id', $posShiftId)->where('status', 'OPEN')->lockForUpdate()->firstOrFail();
            $closedBy = User::where('id', $closedByUserId)->where('is_active', true)->firstOrFail();

            if (! $closedBy->hasAnyRole([UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::PIKET_GURU])) {
                throw new \DomainException('Hanya staf yang dapat menutup shift kasir.');
            }

            if (! is_numeric($actualClosingCash) || bccomp((string) $actualClosingCash, '0.0000', 4) === -1) {
                throw new \DomainException('Kas fisik penutupan tidak boleh negatif.');
            }

            $actualClosingCash = number_format((float) $actualClosingCash, 4, '.', '');
            $openingCash = (string) $shift->opening_cash_balance;

            // Kas fisik shift hanya berasal dari akun Kas Laci Kasir (1-1101).
            // QRIS/transfer masuk ke rekening bank dan tidak boleh menambah
            // ekspektasi uang tunai di laci.
            $cashAccount = ChartOfAccount::where('account_code', '1-1101')->firstOrFail();

            $cashIn = CashBookEntry::where('pos_shift_id', $shift->id)
                ->where('cash_account_id', $cashAccount->id)
                ->where('mutation_direction', 'IN')
                ->sum('debit_in');
            $cashOut = CashBookEntry::where('pos_shift_id', $shift->id)
                ->where('cash_account_id', $cashAccount->id)
                ->where('mutation_direction', 'OUT')
                ->sum('credit_out');

            $cashIn = number_format((float) $cashIn, 4, '.', '');
            $cashOut = number_format((float) $cashOut, 4, '.', '');

            $systemExpectedCash = bcsub(bcadd($openingCash, $cashIn, 4), $cashOut, 4);

            $discrepancy = bcsub($actualClosingCash, $systemExpectedCash, 4);

            $period = null;

            if (bccomp($discrepancy, '0.0000', 4) !== 0) {
                $period = AccountingPeriod::where('is_closed', false)
                    ->orderBy('start_date', 'desc')
                    ->lockForUpdate()
                    ->first();

                if ($period === null) {
                    throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat selisih kas.');
                }
            }

            $shift->update([
                'closed_by_user_id' => $closedBy->id,
                'closed_at' => Carbon::now(),
                'actual_closing_cash' => $actualClosingCash,
                'system_expected_cash' => $systemExpectedCash,
                'cash_discrepancy' => $discrepancy,
                'status' => 'CLOSED',
                'closing_notes' => $closingNotes,
            ]);

            // Posting jurnal selisih kas jika ada.
            if (bccomp($discrepancy, '0.0000', 4) !== 0) {
                $coaCash = $cashAccount;

                if (bccomp($discrepancy, '0.0000', 4) === 1) {
                    // Kas lebih: Dr Kas, Cr Pendapatan Selisih Kas Lebih.
                    $coaRevenue = ChartOfAccount::where('account_code', '4-9101')->lockForUpdate()->firstOrFail();
                    $journalData = new JournalEntryData(
                        journalNumber: $this->nextJournalNumber(),
                        accountingPeriodId: (string) ($period?->getKey() ?? ''),
                        posShiftId: (string) $shift->id,
                        entryDate: Carbon::now(),
                        journalType: JournalType::CASH_DISCREPANCY,
                        referenceType: 'App\Models\PosShift',
                        referenceId: $shift->id,
                        description: 'Selisih kas lebih saat tutup shift '.$shift->shift_code,
                        lines: [
                            new JournalLineData(
                                chartOfAccountCode: $coaCash->account_code,
                                debit: $discrepancy,
                                credit: '0.0000',
                                lineDescription: 'Debit Kas Laci Kasir',
                            ),
                            new JournalLineData(
                                chartOfAccountCode: $coaRevenue->account_code,
                                debit: '0.0000',
                                credit: $discrepancy,
                                lineDescription: 'Kredit Pendapatan Selisih Kas Lebih',
                            ),
                        ],
                        createdByUserId: (string) $closedBy->id,
                    );
                } else {
                    // Kas kurang: Dr Beban Selisih Kas Kurang, Cr Kas.
                    $discrepancyAbs = bcmul($discrepancy, '-1', 4);
                    $coaExpense = ChartOfAccount::where('account_code', '6-1901')->lockForUpdate()->firstOrFail();
                    $journalData = new JournalEntryData(
                        journalNumber: $this->nextJournalNumber(),
                        accountingPeriodId: (string) ($period?->getKey() ?? ''),
                        posShiftId: (string) $shift->id,
                        entryDate: Carbon::now(),
                        journalType: JournalType::CASH_DISCREPANCY,
                        referenceType: 'App\Models\PosShift',
                        referenceId: $shift->id,
                        description: 'Selisih kas kurang saat tutup shift '.$shift->shift_code,
                        lines: [
                            new JournalLineData(
                                chartOfAccountCode: $coaExpense->account_code,
                                debit: $discrepancyAbs,
                                credit: '0.0000',
                                lineDescription: 'Debit Beban Selisih Kas Kurang',
                            ),
                            new JournalLineData(
                                chartOfAccountCode: $coaCash->account_code,
                                debit: '0.0000',
                                credit: $discrepancyAbs,
                                lineDescription: 'Kredit Kas Laci Kasir',
                            ),
                        ],
                        createdByUserId: (string) $closedBy->id,
                    );
                }

                app(PostDoubleEntryJournalAction::class)->execute($journalData);
            }

            return $shift->fresh();
        });
    }

    private function nextJournalNumber(): string
    {
        $sequence = JournalEntry::count() + 1;

        do {
            $journalNumber = 'JRN-'.Carbon::now()->format('Ymd').'-'
                .str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (JournalEntry::where('journal_number', $journalNumber)->exists());

        return $journalNumber;
    }

    private function nextJournalNumber(): string
    {
        $sequence = JournalEntry::count() + 1;

        do {
            $journalNumber = 'JRN-' . Carbon::now()->format('Ymd') . '-'
                . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (JournalEntry::where('journal_number', $journalNumber)->exists());

        return $journalNumber;
    }
}
