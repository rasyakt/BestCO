<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostDoubleEntryJournalAction;
use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordPurchaseRestockAction
{
    private const FUNDING_ACCOUNT_CODES = ['1-1102', '1-1103', '2-1101'];

    /**
     * Catat pembelian / restock barang etalase atau bahan baku jasa, update stok & HPP,
     * lalu posting jurnal pembelian.
     */
    public function execute(
        string $productId,
        string $qtyPurchased,
        string $purchaseUnitPrice,
        string $totalInvoiceAmount,
        string $accountId, // 1-1102 Kas Besar atau 2-1101 Utang Usaha
        string $createdByUserId,
        ?string $journalNumber = null,
    ): array {
        return DB::transaction(function () use (
            $productId,
            $qtyPurchased,
            $purchaseUnitPrice,
            $totalInvoiceAmount,
            $accountId,
            $createdByUserId,
            $journalNumber
        ): array {
            $product = Product::where('id', $productId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if (! preg_match('/^\d+(\.\d{1,4})?$/', trim($qtyPurchased))) {
                throw new \DomainException('Kuantitas pembelian tidak valid.');
            }

            if (! preg_match('/^\d+(\.\d{1,4})?$/', trim($purchaseUnitPrice))) {
                throw new \DomainException('Harga beli satuan tidak valid.');
            }

            $qty = number_format((float) $qtyPurchased, 4, '.', '');
            $unitPrice = number_format((float) $purchaseUnitPrice, 4, '.', '');

            if (bccomp($qty, '0.0000', 4) !== 1) {
                throw new \DomainException('Kuantitas pembelian harus lebih besar dari nol.');
            }

            if (bccomp($unitPrice, '0.0000', 4) !== 1) {
                throw new \DomainException('Harga beli satuan harus lebih besar dari nol.');
            }

            // Nilai total selalu dihitung ulang agar tidak dapat dimanipulasi dari klien.
            $totalInvoiceAmount = bcmul($qty, $unitPrice, 4);

            $coaCashOrDebt = ChartOfAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();

            if (! in_array($coaCashOrDebt->account_code, self::FUNDING_ACCOUNT_CODES, true)) {
                throw new \DomainException('Akun pendanaan harus Kas di Tangan, Kas di Bank, atau Utang Usaha.');
            }

            $period = AccountingPeriod::where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            if ($period === null) {
                throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat pembelian.');
            }

            $coaInventory = ChartOfAccount::where('id', $product->inventory_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStock = (string) $product->current_stock;
            $oldAvgCost = (string) $product->average_cost;
            $newStock = bcadd($oldStock, $qty, 4);

            $newAvgCost = bccomp($newStock, '0.0000', 4) === 1
                ? bcdiv(
                    bcadd(bcmul($oldStock, $oldAvgCost, 4), bcmul($qty, $unitPrice, 4), 4),
                    $newStock,
                    4
                )
                : $unitPrice;

            $product->update([
                'current_stock' => $newStock,
                'average_cost' => $newAvgCost,
                'last_purchase_price' => $unitPrice,
            ]);

            $journalNumber = $journalNumber ?? $this->nextJournalNumber();

            $journalData = new JournalEntryData(
                journalNumber: $journalNumber,
                accountingPeriodId: (string) $period->getKey(),
                posShiftId: null,
                entryDate: Carbon::now(),
                journalType: JournalType::PURCHASE,
                referenceType: 'App\Models\Product',
                referenceId: $product->id,
                description: 'Pembelian / restock '.$qty.' '.$product->unit->code.
                    ' '.$product->name.' (Rp '.$totalInvoiceAmount.')',
                lines: [
                    new JournalLineData(
                        chartOfAccountCode: $coaInventory->account_code,
                        debit: $totalInvoiceAmount,
                        credit: '0.0000',
                        lineDescription: 'Debit Persediaan',
                    ),
                    new JournalLineData(
                        chartOfAccountCode: $coaCashOrDebt->account_code,
                        debit: '0.0000',
                        credit: $totalInvoiceAmount,
                        lineDescription: 'Kredit Kas Besar / Utang Usaha',
                    ),
                ],
                createdByUserId: (string) $createdByUserId,
            );

            $journalEntry = app(PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            StockLedgerEntry::create([
                'product_id' => $product->id,
                'pos_shift_id' => null,
                'reference_type' => 'PURCHASE',
                'reference_id' => $journalEntry->id,
                'qty_change' => $qty,
                'balance_before' => $oldStock,
                'balance_after' => $newStock,
                'unit_cost' => $unitPrice,
                'total_value' => $totalInvoiceAmount,
                'created_by' => $createdByUserId,
                'created_at' => Carbon::now(),
            ]);

            return [
                'product' => $product->fresh(),
                'journal_entry' => $journalEntry,
                'new_average_cost' => $newAvgCost,
            ];
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
}
