<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\DTOs\Inventory\InternalMutationData;
use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\StockMutation;
use App\Models\UnitConversion;
use App\Services\Inventory\MovingAverageCostCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class MutateRetailToRawMaterialAction
{
    public function __construct(
        private MovingAverageCostCalculator $macCalculator,
    ) {}

    /**
     * Mutasi stok barang etalase (RETAIL) ke bahan baku jasa (RAW_MATERIAL)
     * dengan konversi satuan otomatis dan posting jurnal akuntansi.
     */
    public function execute(InternalMutationData $data): StockMutation
    {
        return DB::transaction(function () use ($data): StockMutation {
            $posShift = \App\Models\PosShift::where('id', $data->posShiftId)
                ->where('status', \App\Enums\ShiftStatus::OPEN->value)
                ->lockForUpdate()
                ->firstOrFail();

            $unitConversion = UnitConversion::where('id', $data->unitConversionId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceProduct = Product::where('id', $unitConversion->source_product_id)
                ->where('inventory_type', 'RETAIL')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $targetProduct = Product::where('id', $unitConversion->target_product_id)
                ->where('inventory_type', 'RAW_MATERIAL')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceQty = trim($data->sourceQtyToDeduct);

            if ($sourceQty === '' || ! preg_match('/^\d+(\.\d{1,4})?$/', $sourceQty)) {
                throw new \DomainException('Kuantitas mutasi tidak valid.');
            }

            $sourceQty = number_format((float) $sourceQty, 4, '.', '');

            if (bccomp($sourceQty, '0.0000', 4) !== 1) {
                throw new \DomainException('Kuantitas mutasi harus lebih besar dari nol.');
            }

            // Verifikasi stok retail mencukupi.
            if (bccomp((string) $sourceProduct->current_stock, $sourceQty, 4) === -1) {
                throw new \DomainException(
                    'Stok etalase tidak mencukupi. Tersedia: ' . $sourceProduct->current_stock .
                    ', diminta: ' . $sourceQty
                );
            }
            $conversionFactor = (string) $unitConversion->conversion_factor;
            $targetQtyAdded = bcmul($sourceQty, $conversionFactor, 4);
            $sourceUnitCost = (string) $sourceProduct->average_cost;
            $totalMutationValue = bcmul($sourceQty, $sourceUnitCost, 4);
            $targetUnitCost = bccomp($targetQtyAdded, '0.0000', 4) === 0
                ? '0.0000'
                : bcdiv($totalMutationValue, $targetQtyAdded, 4);

            $sourceBalanceBefore = (string) $sourceProduct->current_stock;
            $targetBalanceBefore = (string) $targetProduct->current_stock;

            // Kurangi stok etalase.
            $sourceProduct->update([
                'current_stock' => bcsub($sourceBalanceBefore, $sourceQty, 4),
            ]);

            // Tambah stok bahan baku jasa & hitung ulang MAC.
            $targetBalanceAfter = bcadd($targetBalanceBefore, $targetQtyAdded, 4);
            $newAvgCost = $this->macCalculator->calculate($targetProduct, $targetQtyAdded, $targetUnitCost);

            $targetProduct->update([
                'current_stock' => $targetBalanceAfter,
                'average_cost' => $newAvgCost,
            ]);

            // Buat stock_mutations terlebih dahulu (dapatkan ID untuk reference_id ledger).
            $mutationNumber = 'MUT-' . Carbon::now()->format('Ymd') . '-' .
                str_pad((string) (StockMutation::count() + 1), 4, '0', STR_PAD_LEFT);

            $mutation = StockMutation::create([
                'mutation_number' => $mutationNumber,
                'pos_shift_id' => $data->posShiftId,
                'unit_conversion_id' => $unitConversion->id,
                'source_product_id' => $sourceProduct->id,
                'target_product_id' => $targetProduct->id,
                'source_qty_deducted' => $sourceQty,
                'conversion_factor' => $conversionFactor,
                'target_qty_added' => $targetQtyAdded,
                'source_unit_cost' => $sourceUnitCost,
                'target_unit_cost' => $targetUnitCost,
                'total_mutation_value' => $totalMutationValue,
                'requested_by_user_id' => $data->requestedByUserId,
                'approved_by_user_id' => $data->approvedByUserId,
                'journal_entry_id' => null,
                'notes' => $data->notes,
                'mutated_at' => Carbon::now(),
            ]);

            // Tulis stock_ledger_entries dengan reference_id yang menunjuk ke mutation.
            StockLedgerEntry::create([
                'product_id' => $sourceProduct->id,
                'pos_shift_id' => $data->posShiftId,
                'reference_type' => 'INTERNAL_MUTATION_OUT',
                'reference_id' => $mutation->id,
                'qty_change' => '-' . $sourceQty,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => bcsub($sourceBalanceBefore, $sourceQty, 4),
                'unit_cost' => $sourceUnitCost,
                'total_value' => $totalMutationValue,
                'created_by' => $data->requestedByUserId,
                'created_at' => Carbon::now(),
            ]);

            StockLedgerEntry::create([
                'product_id' => $targetProduct->id,
                'pos_shift_id' => $data->posShiftId,
                'reference_type' => 'INTERNAL_MUTATION_IN',
                'reference_id' => $mutation->id,
                'qty_change' => $targetQtyAdded,
                'balance_before' => $targetBalanceBefore,
                'balance_after' => $targetBalanceAfter,
                'unit_cost' => $targetUnitCost,
                'total_value' => $totalMutationValue,
                'created_by' => $data->requestedByUserId,
                'created_at' => Carbon::now(),
            ]);

            // Posting jurnal menggunakan akun persediaan masing-masing produk.
            if (bccomp($totalMutationValue, '0.0000', 4) === 1) {
                $coaInventoryRaw = ChartOfAccount::where('id', $targetProduct->inventory_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $coaInventoryRetail = ChartOfAccount::where('id', $sourceProduct->inventory_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $period = AccountingPeriod::where('is_closed', false)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($period === null) {
                    throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat mutasi.');
                }

                $journalData = new JournalEntryData(
                    journalNumber: $this->nextJournalNumber(),
                    accountingPeriodId: (string) $period->getKey(),
                    posShiftId: (string) $posShift->id,
                    entryDate: Carbon::now(),
                    journalType: JournalType::INTERNAL_MUTATION,
                    referenceType: 'App\Models\StockMutation',
                    referenceId: $mutation->id,
                    description: 'Mutasi internal: ' . $sourceQty . ' ' . $sourceProduct->unit->code .
                        ' etalase → ' . $targetQtyAdded . ' ' . $targetProduct->unit->code . ' bahan baku jasa',
                    lines: [
                        new JournalLineData(
                            chartOfAccountCode: $coaInventoryRaw->account_code,
                            debit: $totalMutationValue,
                            credit: '0.0000',
                            lineDescription: 'Debit Persediaan Bahan Baku Jasa',
                        ),
                        new JournalLineData(
                            chartOfAccountCode: $coaInventoryRetail->account_code,
                            debit: '0.0000',
                            credit: $totalMutationValue,
                            lineDescription: 'Kredit Persediaan Barang Dagang (Etalase)',
                        ),
                    ],
                    createdByUserId: $data->requestedByUserId,
                );

                $journalEntry = app(\App\Actions\Accounting\PostDoubleEntryJournalAction::class)
                    ->execute($journalData);

                $mutation->update(['journal_entry_id' => $journalEntry->id]);
            }

            return $mutation->fresh(['sourceProduct', 'targetProduct', 'unitConversion', 'journalEntry']);
        });
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
