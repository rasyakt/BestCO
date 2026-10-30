<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\JournalType;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordPurchaseRestockAction
{
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
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            $oldStock = (string) $product->current_stock;
            $oldAvgCost = (string) $product->average_cost;
            $newStock = bcadd($oldStock, $qtyPurchased, 4);

            $newAvgCost = $newStock > 0
                ? bcdiv(
                    bcadd(bcmul($oldStock, $oldAvgCost, 4), bcmul($qtyPurchased, $purchaseUnitPrice, 4), 4),
                    $newStock,
                    4
                )
                : $purchaseUnitPrice;

            $product->update([
                'current_stock' => $newStock,
                'average_cost' => $newAvgCost,
                'last_purchase_price' => $purchaseUnitPrice,
            ]);

            StockLedgerEntry::create([
                'product_id' => $product->id,
                'pos_shift_id' => null,
                'reference_type' => 'PURCHASE',
                'reference_id' => 0,
                'qty_change' => $qtyPurchased,
                'balance_before' => $oldStock,
                'balance_after' => $newStock,
                'unit_cost' => $purchaseUnitPrice,
                'total_value' => bcmul($qtyPurchased, $purchaseUnitPrice, 4),
                'created_by' => $createdByUserId,
                'created_at' => Carbon::now(),
            ]);

            $coaInventory = ChartOfAccount::where('id', $product->inventory_account_id)->lockForUpdate()->firstOrFail();
            $coaCashOrDebt = ChartOfAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();

            $period = app(\App\Models\AccountingPeriod::class)
                ->where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            $journalNumber = $journalNumber ?? ('JRN-' . Carbon::now()->format('Ymd') . '-' .
                str_pad((string) (JournalEntry::count() + 1), 4, '0', STR_PAD_LEFT));

            $journalData = new JournalEntryData(
                journalNumber: $journalNumber,
                accountingPeriodId: (string) ($period?->getKey() ?? ''),
                posShiftId: null,
                entryDate: Carbon::now(),
                journalType: JournalType::PURCHASE,
                referenceType: 'App\Models\Product',
                referenceId: $product->id,
                description: 'Pembelian / restock ' . $qtyPurchased . ' ' . $product->unit->code .
                    ' ' . $product->name . ' (Rp ' . $totalInvoiceAmount . ')',
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

            $journalEntry = app(\App\Actions\Accounting\PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            return [
                'product' => $product->fresh(),
                'journal_entry' => $journalEntry,
                'new_average_cost' => $newAvgCost,
            ];
        });
    }
}
