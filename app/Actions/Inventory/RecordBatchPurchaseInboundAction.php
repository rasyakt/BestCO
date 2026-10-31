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
use InvalidArgumentException;

final class RecordBatchPurchaseInboundAction
{
    /**
     * Catat barang masuk / restock multi-produk secara batch dalam satu transaksi atomik.
     * Mengupdate stok & HPP setiap produk, mencatat buku besar persediaan (StockLedgerEntry),
     * dan memposting jurnal ganda yang seimbang ke kas / utang usaha.
     *
     * @param array<int, array{product_id: int|string, qty: int|float|string, price: int|float|string}> $items
     * @return array{journal_entry: JournalEntry, total_amount: string, items_count: int, processed_products: array}
     */
    public function execute(
        array $items,
        string $accountId,
        string $createdByUserId,
        ?string $invoiceNumber = null,
        ?string $notes = null,
    ): array {
        if (empty($items)) {
            throw new InvalidArgumentException('Daftar barang masuk tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $items,
            $accountId,
            $createdByUserId,
            $invoiceNumber,
            $notes
        ): array {
            $coaCashOrDebt = ChartOfAccount::where('id', $accountId)->lockForUpdate()->firstOrFail();

            $period = AccountingPeriod::where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            $productIds = array_unique(array_column($items, 'product_id'));
            $products = Product::with(['unit', 'inventoryAccount'])
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalInvoiceAmount = '0.0000';
            $journalLines = [];
            $ledgerEntriesToCreate = [];
            $processedProducts = [];

            foreach ($items as $itemData) {
                $productId = (int) $itemData['product_id'];
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (!$product) {
                    throw new InvalidArgumentException("Produk dengan ID {$productId} tidak ditemukan.");
                }

                $qty = number_format((float) $itemData['qty'], 4, '.', '');
                $price = number_format((float) $itemData['price'], 4, '.', '');

                if (bccomp($qty, '0.0000', 4) <= 0) {
                    throw new InvalidArgumentException("Kuantitas untuk produk {$product->name} harus lebih dari 0.");
                }
                if (bccomp($price, '0.0000', 4) < 0) {
                    throw new InvalidArgumentException("Harga beli untuk produk {$product->name} tidak boleh negatif.");
                }

                $lineSubtotal = bcmul($qty, $price, 4);
                $totalInvoiceAmount = bcadd($totalInvoiceAmount, $lineSubtotal, 4);

                $oldStock = (string) $product->current_stock;
                $oldAvgCost = (string) $product->average_cost;
                $newStock = bcadd($oldStock, $qty, 4);

                $newAvgCost = bccomp($newStock, '0.0000', 4) > 0
                    ? bcdiv(
                        bcadd(bcmul($oldStock, $oldAvgCost, 4), bcmul($qty, $price, 4), 4),
                        $newStock,
                        4
                    )
                    : $price;

                $product->update([
                    'current_stock' => $newStock,
                    'average_cost' => $newAvgCost,
                    'last_purchase_price' => $price,
                ]);

                $unitCode = $product->unit ? $product->unit->code : 'UNIT';
                $formattedQty = rtrim(rtrim($qty, '0'), '.');
                $formattedPrice = number_format((float) $price, 0, ',', '.');

                // Siapkan baris jurnal debit untuk akun persediaan produk
                $journalLines[] = new JournalLineData(
                    chartOfAccountCode: $product->inventoryAccount->account_code,
                    debit: $lineSubtotal,
                    credit: '0.0000',
                    lineDescription: "Masuk {$product->name} ({$formattedQty} {$unitCode} @ Rp {$formattedPrice})",
                );

                $ledgerEntriesToCreate[] = [
                    'product_id' => $product->id,
                    'pos_shift_id' => null,
                    'reference_type' => 'PURCHASE',
                    'qty_change' => $qty,
                    'balance_before' => $oldStock,
                    'balance_after' => $newStock,
                    'unit_cost' => $price,
                    'total_value' => $lineSubtotal,
                    'created_by' => $createdByUserId,
                    'created_at' => Carbon::now(),
                ];

                $processedProducts[] = [
                    'product' => $product->fresh(),
                    'qty' => $qty,
                    'unit_cost' => $price,
                    'subtotal' => $lineSubtotal,
                    'new_stock' => $newStock,
                    'new_average_cost' => $newAvgCost,
                ];
            }

            // Tambahkan baris kredit tunggal untuk sumber dana (Kas Besar / Utang Usaha)
            $invLabel = $invoiceNumber ? " [Faktur: {$invoiceNumber}]" : '';
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $coaCashOrDebt->account_code,
                debit: '0.0000',
                credit: $totalInvoiceAmount,
                lineDescription: "Kredit {$coaCashOrDebt->account_name}{$invLabel}",
            );

            $journalCount = JournalEntry::count() + 1;
            $journalNumber = 'JRN-' . Carbon::now()->format('Ymd') . '-' . str_pad((string) $journalCount, 4, '0', STR_PAD_LEFT);

            $descParts = [
                'Barang Masuk Multi-item (' . count($items) . ' produk)',
            ];
            if ($invoiceNumber) {
                $descParts[] = "Faktur: {$invoiceNumber}";
            }
            if ($notes) {
                $descParts[] = $notes;
            }
            $description = implode(' | ', $descParts);

            $journalData = new JournalEntryData(
                journalNumber: $journalNumber,
                accountingPeriodId: (string) ($period?->getKey() ?? ''),
                posShiftId: null,
                entryDate: Carbon::now(),
                journalType: JournalType::PURCHASE,
                referenceType: 'BATCH_PURCHASE',
                referenceId: null,
                description: $description,
                lines: $journalLines,
                createdByUserId: (string) $createdByUserId,
            );

            /** @var JournalEntry $journalEntry */
            $journalEntry = app(PostDoubleEntryJournalAction::class)->execute($journalData);

            // Simpan data kartu stok ledger dengan referensi ke id jurnal
            foreach ($ledgerEntriesToCreate as $ledgerData) {
                $ledgerData['reference_id'] = $journalEntry->id;
                StockLedgerEntry::create($ledgerData);
            }

            return [
                'journal_entry' => $journalEntry,
                'total_amount' => $totalInvoiceAmount,
                'items_count' => count($items),
                'processed_products' => $processedProducts,
            ];
        });
    }
}

