<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\JournalType;
use App\Enums\PaymentMethod;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\PosTransactionBom;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Services\Pos\TieredPricingResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CheckoutPosTransactionAction
{
    public function __construct(
        private TieredPricingResolver $tierResolver,
    ) {}

    /**
     * Checkout transaksi POS gabungan (barang etalase + jasa) dengan deduksi stok
     * atomik, pencatatan BOM, dan posting jurnal akuntansi lengkap.
     */
    public function execute(CheckoutPayloadData $data): PosTransaction
    {
        return DB::transaction(function () use ($data): PosTransaction {
            $shift = PosShift::where('id', $data->posShiftId)->where('status', 'OPEN')->lockForUpdate()->firstOrFail();
            $cashier = User::findOrFail($data->cashierUserId);

            $manualDiscountTotal = number_format((float) $data->manualDiscountTotal, 4, '.', '');
            $amountPaid = number_format((float) $data->amountPaid, 4, '.', '');

            // Accumulators.
            $subtotalRetail = '0.0000';
            $subtotalService = '0.0000';
            $tierDiscountTotal = '0.0000';
            $manualDiscountTotal = '0.0000';
            $totalCogsRetail = '0.0000';
            $totalCogsService = '0.0000';

            // Data untuk persist setelah transaction dibuat.
            $transactionItems = [];
            $stockLedgerEntries = [];
            $journalLines = [];

            // --- Pass 1: proses setiap item (kunci produk, potong stok, hitung harga) ---
            foreach ($data->items as $cartItem) {
                if ($cartItem->itemType === 'RETAIL_PRODUCT') {
                    $result = $this->processRetailItem($cartItem, (string) $cashier->id);
                } else {
                    $result = $this->processServiceItem($cartItem, (string) $cashier->id);
                }

                $subtotalRetail = bcadd($subtotalRetail, $result['subtotalRetail'], 4);
                $subtotalService = bcadd($subtotalService, $result['subtotalService'], 4);
                $tierDiscountTotal = bcadd($tierDiscountTotal, $result['tierDiscountTotal'], 4);
                $manualDiscountTotal = bcadd($manualDiscountTotal, $result['manualDiscountTotal'], 4);
                $totalCogsRetail = bcadd($totalCogsRetail, $result['cogsRetail'], 4);
                $totalCogsService = bcadd($totalCogsService, $result['cogsService'], 4);

                $transactionItems[] = $result['transactionItem'];
                $stockLedgerEntries = array_merge($stockLedgerEntries, $result['stockLedgerEntries']);
                $journalLines = array_merge($journalLines, $result['journalLines']);
            }

            if (bccomp($data->manualDiscountTotal, '0.0000', 4) === 1 && bccomp($manualDiscountTotal, '0.0000', 4) === 0) {
                $manualDiscountTotal = $data->manualDiscountTotal;
                $journalLines[] = new JournalLineData(
                    chartOfAccountCode: '4-1102',
                    debit: $manualDiscountTotal,
                    credit: '0.0000',
                    lineDescription: 'Diskon manual kasir',
                );
            }

            $totalGross = bcadd($subtotalRetail, $subtotalService, 4);
            $totalDiscounts = bcadd($tierDiscountTotal, $manualDiscountTotal, 4);
            $grandTotal = bcsub($totalGross, $totalDiscounts, 4);
            $changeAmount = bcsub($amountPaid, $grandTotal, 4);

            // --- Buat pos_transactions ---
            $invoiceNumber = 'INV-' . Carbon::now()->format('Ymd') . '-' .
                str_pad((string) (PosTransaction::count() + 1), 4, '0', STR_PAD_LEFT);

            $transaction = PosTransaction::create([
                'invoice_number' => $invoiceNumber,
                'pos_shift_id' => $shift->id,
                'cashier_user_id' => $cashier->id,
                'customer_name' => $data->customerName ?? 'Pelanggan Umum',
                'subtotal_retail' => $subtotalRetail,
                'subtotal_service' => $subtotalService,
                'tier_discount_total' => $tierDiscountTotal,
                'manual_discount_total' => $manualDiscountTotal,
                'grand_total' => $grandTotal,
                'total_cogs_retail' => $totalCogsRetail,
                'total_cogs_service' => $totalCogsService,
                'payment_method' => $data->paymentMethod->value,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'status' => 'COMPLETED',
                'journal_entry_id' => null,
                'transaction_date' => Carbon::now(),
            ]);

            // --- Pass 2: persist item, bom, dan ledger (sudah dikurangi stok di pass 1) ---
            foreach ($transactionItems as $ti) {
                $ti['pos_transaction_id'] = $transaction->id;
                $posItem = PosTransactionItem::create(array_filter($ti, fn ($k) => $k !== '_boms', ARRAY_FILTER_USE_KEY));

                if (!empty($ti['_boms'])) {
                    foreach ($ti['_boms'] as $bom) {
                        PosTransactionBom::create([
                            'pos_transaction_item_id' => $posItem->id,
                            'raw_material_product_id' => $bom['raw_material_product_id'],
                            'qty_consumed' => $bom['qty_consumed'],
                            'unit_cost_snapshot' => $bom['unit_cost_snapshot'],
                            'total_bom_cost' => $bom['total_bom_cost'],
                        ]);
                    }
                }
            }

            foreach ($stockLedgerEntries as $sle) {
                $sle['pos_transaction_id'] = $transaction->id;
                $sle['pos_shift_id'] = $shift->id;
                StockLedgerEntry::create(array_filter($sle, fn ($k) => $k !== '_product', ARRAY_FILTER_USE_KEY));
            }

            // --- Update system_expected_cash pada shift ---
            $shift->increment('system_expected_cash', $grandTotal);

            // --- Posting jurnal akuntansi lengkap ---
            $period = AccountingPeriod::where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            $coaCash = ChartOfAccount::where('account_code', '1-1101')->lockForUpdate()->firstOrFail();
            $coaQris = ChartOfAccount::where('account_code', '1-1103')->lockForUpdate()->firstOrFail();

            $allJournalLines = array_merge([
                // Debit Kas / QRIS.
                new JournalLineData(
                    chartOfAccountCode: $data->paymentMethod === PaymentMethod::CASH ? $coaCash->account_code : $coaQris->account_code,
                    debit: $grandTotal,
                    credit: '0.0000',
                    lineDescription: 'Debit Kas / QRIS (Penerimaan POS)',
                ),
            ], $journalLines);

            $journalData = new JournalEntryData(
                journalNumber: 'JRN-' . Carbon::now()->format('Ymd') . '-' .
                    str_pad((string) (JournalEntry::count() + 1), 4, '0', STR_PAD_LEFT),
                accountingPeriodId: (string) ($period?->getKey() ?? ''),
                posShiftId: (string) $shift->id,
                entryDate: Carbon::now(),
                journalType: JournalType::SALES_POS,
                referenceType: 'App\Models\PosTransaction',
                referenceId: $transaction->id,
                description: 'Transaksi POS ' . $invoiceNumber . ' (' . $data->paymentMethod->label() . ')',
                lines: $allJournalLines,
                createdByUserId: (string) $cashier->id,
            );

            $journalEntry = app(\App\Actions\Accounting\PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            $transaction->update(['journal_entry_id' => $journalEntry->id]);

            return $transaction->fresh(['items.boms', 'journalEntry']);
        });
    }

    /**
     * Proses item retail: kunci produk, validasi stok, potong stok, hitung COGS.
     *
     * @return array{
     *     subtotalRetail: string,
     *     subtotalService: string,
     *     tierDiscountTotal: string,
     *     cogsRetail: string,
     *     cogsService: string,
     *     transactionItem: array,
     *     stockLedgerEntries: array,
     *     journalLines: JournalLineData[]
     * }
     */
    private function processRetailItem(CartItemData $item, string $cashierId): array
    {
        $product = Product::where('id', $item->productId)->lockForUpdate()->firstOrFail();

        $qty = number_format((float) $item->qty, 4, '.', '');
        $manualDiscount = number_format((float) $item->manualDiscountAmount, 4, '.', '');
        $unitPrice = (string) $product->selling_price;
        $avgCost = (string) $product->average_cost;

        if (bccomp((string) $product->current_stock, $qty, 4) === -1) {
            throw new \DomainException('Stok tidak mencukupi untuk ' . $product->name);
        }

        $subtotalGross = bcmul($qty, $unitPrice, 4);
        $subtotalNet = bcsub($subtotalGross, $manualDiscount, 4);
        $cogsTotal = bcmul($qty, $avgCost, 4);

        // Potong stok.
        $balanceBefore = (string) $product->current_stock;
        $product->update(['current_stock' => bcsub($balanceBefore, $qty, 4)]);

        $journalLines = [];
        if (bccomp($subtotalGross, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '4-1101',
                debit: '0.0000',
                credit: $subtotalGross,
                lineDescription: 'Pendapatan penjualan ' . $product->name,
            );
        }
        if (bccomp($manualDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '4-1102',
                debit: $manualDiscount,
                credit: '0.0000',
                lineDescription: 'Potongan penjualan ' . $product->name,
            );
        }
        if (bccomp($cogsTotal, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '5-1101',
                debit: $cogsTotal,
                credit: '0.0000',
                lineDescription: 'HPP ' . $product->name,
            );
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '1-1301',
                debit: '0.0000',
                credit: $cogsTotal,
                lineDescription: 'Kredit Persediaan Etalase ' . $product->name,
            );
        }

        $stockLedgerEntries = [[
            'product_id' => $product->id,
            '_product' => $product,
            'qty_change' => '-' . $qty,
            'balance_before' => $balanceBefore,
            'balance_after' => bcsub($balanceBefore, $qty, 4),
            'unit_cost' => $avgCost,
            'total_value' => $cogsTotal,
            'reference_type' => 'POS_SALE',
            'reference_id' => 0,
            'created_by' => $cashierId,
            'created_at' => Carbon::now(),
        ]];

        $transactionItem = [
            'item_type' => 'RETAIL_PRODUCT',
            'product_id' => $product->id,
            'service_id' => null,
            'applied_price_tier_id' => null,
            'item_name_snapshot' => $product->name,
            'qty' => $qty,
            'base_unit_price' => $unitPrice,
            'tier_discount_per_unit' => '0.0000',
            'manual_discount_amount' => $manualDiscount,
            'final_unit_price' => bcsub($unitPrice, bcdiv($manualDiscount, $qty, 4), 4),
            'line_subtotal_gross' => $subtotalGross,
            'line_total_discount' => $manualDiscount,
            'line_net_total' => $subtotalNet,
            'line_cogs_total' => $cogsTotal,
            '_boms' => [],
        ];

        return [
            'subtotalRetail' => $subtotalGross,
            'subtotalService' => '0.0000',
            'tierDiscountTotal' => '0.0000',
            'manualDiscountTotal' => $manualDiscount,
            'cogsRetail' => $cogsTotal,
            'cogsService' => '0.0000',
            'transactionItem' => $transactionItem,
            'stockLedgerEntries' => $stockLedgerEntries,
            'journalLines' => $journalLines,
        ];
    }

    /**
     * Proses item jasa: evaluasi tier, hitung harga akhir, deduksi BoM, hitung COGS jasa.
     */
    private function processServiceItem(CartItemData $item, string $cashierId): array
    {
        $service = Service::where('id', $item->serviceId)->firstOrFail();
        $qty = number_format((float) $item->qty, 4, '.', '');
        $manualDiscount = number_format((float) $item->manualDiscountAmount, 4, '.', '');

        $pricing = $this->tierResolver->resolve($service, $qty);
        $finalUnitPrice = $pricing['final_unit_price'];
        $subtotalNet = bcmul($qty, $finalUnitPrice, 4);
        $tierDiscount = $pricing['line_tier_discount_total'];
        $grossSubtotal = $pricing['line_subtotal_gross'];

        $cogsService = '0.0000';
        $bomRecords = [];
        $stockLedgerEntries = [];
        $journalLines = [];

        $boms = $service->boms()->get();
        foreach ($boms as $bom) {
            $rawMaterial = Product::where('id', $bom->raw_material_product_id)->lockForUpdate()->firstOrFail();
            $qtyConsumed = bcmul($qty, (string) $bom->consumption_qty, 4);
            $unitCost = (string) $rawMaterial->average_cost;
            $bomCost = bcmul($qtyConsumed, $unitCost, 4);

            if (bccomp((string) $rawMaterial->current_stock, $qtyConsumed, 4) === -1) {
                throw new \DomainException('Stok bahan baku tidak mencukupi untuk ' . $rawMaterial->name);
            }

            $balanceBefore = (string) $rawMaterial->current_stock;
            $rawMaterial->update(['current_stock' => bcsub($balanceBefore, $qtyConsumed, 4)]);

            $cogsService = bcadd($cogsService, $bomCost, 4);

            $bomRecords[] = [
                'raw_material_product_id' => $rawMaterial->id,
                'qty_consumed' => $qtyConsumed,
                'unit_cost_snapshot' => $unitCost,
                'total_bom_cost' => $bomCost,
            ];

            $stockLedgerEntries[] = [
                'product_id' => $rawMaterial->id,
                '_product' => $rawMaterial,
                'qty_change' => '-' . $qtyConsumed,
                'balance_before' => $balanceBefore,
                'balance_after' => bcsub($balanceBefore, $qtyConsumed, 4),
                'unit_cost' => $unitCost,
                'total_value' => $bomCost,
                'reference_type' => 'SERVICE_BOM',
                'reference_id' => 0,
                'created_by' => $cashierId,
                'created_at' => Carbon::now(),
            ];
        }

        $journalLines[] = new JournalLineData(
            chartOfAccountCode: '4-2101',
            debit: '0.0000',
            credit: $grossSubtotal,
            lineDescription: 'Pendapatan jasa ' . $service->name . ' (bruto)',
        );
        if (bccomp($tierDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '4-2109',
                debit: $tierDiscount,
                credit: '0.0000',
                lineDescription: 'Potongan grosir jasa ' . $service->name,
            );
        }
        if (bccomp($manualDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '4-2109',
                debit: $manualDiscount,
                credit: '0.0000',
                lineDescription: 'Diskon manual jasa ' . $service->name,
            );
        }
        if (bccomp($cogsService, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '5-2101',
                debit: $cogsService,
                credit: '0.0000',
                lineDescription: 'HPP bahan baku jasa ' . $service->name,
            );
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '1-1302',
                debit: '0.0000',
                credit: $cogsService,
                lineDescription: 'Kredit Persediaan Bahan Baku Jasa',
            );
        }

        $transactionItem = [
            'item_type' => 'SERVICE',
            'product_id' => null,
            'service_id' => $service->id,
            'applied_price_tier_id' => $pricing['applied_tier_id'],
            'item_name_snapshot' => $service->name,
            'qty' => $qty,
            'base_unit_price' => $pricing['base_unit_price'],
            'tier_discount_per_unit' => $pricing['tier_discount_per_unit'],
            'manual_discount_amount' => $manualDiscount,
            'final_unit_price' => $finalUnitPrice,
            'line_subtotal_gross' => $grossSubtotal,
            'line_total_discount' => bcadd($tierDiscount, $manualDiscount, 4),
            'line_net_total' => $subtotalNet,
            'line_cogs_total' => $cogsService,
            '_boms' => $bomRecords,
        ];

        return [
            'subtotalRetail' => '0.0000',
            'subtotalService' => $grossSubtotal,
            'tierDiscountTotal' => $tierDiscount,
            'manualDiscountTotal' => $manualDiscount,
            'cogsRetail' => '0.0000',
            'cogsService' => $cogsService,
            'transactionItem' => $transactionItem,
            'stockLedgerEntries' => $stockLedgerEntries,
            'journalLines' => $journalLines,
        ];
    }
}
