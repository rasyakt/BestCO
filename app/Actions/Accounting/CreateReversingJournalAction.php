<?php

declare(strict_types=1);

namespace App\Actions\Accounting;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CreateReversingJournalAction
{
    /**
     * Buat jurnal pembalik (reversing journal) untuk koreksi immutable.
     * Setiap baris debit/kredit asli dibalik, dan jurnal asli ditandai is_reversed.
     */
    public function execute(JournalEntry $originalJournal, string $reversingJournalNumber, User $createdBy): JournalEntry
    {
        return DB::transaction(function () use ($originalJournal, $reversingJournalNumber, $createdBy): JournalEntry {
            $originalJournal = JournalEntry::whereKey($originalJournal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($originalJournal->is_reversed) {
                throw new \DomainException('Jurnal ini sudah pernah dibalik.');
            }

            // Jurnal pembalik harus masuk ke periode yang masih terbuka. Jika
            // periode asal sudah ditutup, gunakan periode terbuka terbaru agar
            // koreksi tidak mengubah laporan periode yang sudah final.
            $period = $originalJournal->accountingPeriod;

            if ($period === null || $period->is_closed) {
                $period = AccountingPeriod::where('is_closed', false)
                    ->orderBy('start_date', 'desc')
                    ->lockForUpdate()
                    ->first();
            }

            if ($period === null) {
                throw new \DomainException('Tidak ada periode akuntansi terbuka untuk jurnal pembalik.');
            }

            // Tandai jurnal asli sebagai reversed.
            $originalJournal->update(['is_reversed' => true]);

            // Buat baris jurnal pembalik: debit menjadi kredit dan sebaliknya.
            $lines = [];
            $totalDebit = '0.0000';
            $totalCredit = '0.0000';

            foreach ($originalJournal->items as $item) {
                $reversedDebit = $item->credit;
                $reversedCredit = $item->debit;

                $totalDebit = bcadd($totalDebit, $reversedDebit, 4);
                $totalCredit = bcadd($totalCredit, $reversedCredit, 4);

                $lines[] = new JournalLineData(
                    chartOfAccountCode: $item->chartOfAccount->account_code,
                    debit: $reversedDebit,
                    credit: $reversedCredit,
                    lineDescription: 'REVERSING: ' . ($item->line_description ?? $originalJournal->description),
                );
            }

            $data = new JournalEntryData(
                journalNumber: $reversingJournalNumber,
                accountingPeriodId: (string) $period->id,
                posShiftId: $originalJournal->pos_shift_id ? (string) $originalJournal->pos_shift_id : null,
                entryDate: Carbon::now(),
                journalType: JournalType::REVERSING,
                referenceType: $originalJournal->reference_type,
                referenceId: $originalJournal->reference_id,
                description: 'Pembalikan jurnal ' . $originalJournal->journal_number . ': ' . $originalJournal->description,
                lines: $lines,
                createdByUserId: (string) $createdBy->getKey(),
                reversedByJournalId: null,
            );

            $action = app(PostDoubleEntryJournalAction::class);
            $reversingEntry = $action->execute($data);

            // Tandai reversing journal id pada jurnal asli (referensi balik).
            $originalJournal->update(['reversed_by_journal_id' => $reversingEntry->id]);

            return $reversingEntry->load('items.chartOfAccount');
        });
    }
}
