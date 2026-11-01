<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Actions\Accounting\PostDoubleEntryJournalAction;
use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\JournalType;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\PosTransactionBom;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShiftAttendance;
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
            if ($data->items === []) {
                throw new \DomainException('Keranjang belanja masih kosong.');
            }

            $shift = PosShift::where('id', $data->posShiftId)
                ->where('status', 'OPEN')
                ->lockForUpdate()
                ->firstOrFail();

            $cashier = User::where('id', $data->cashierUserId)
                ->where('is_active', true)
                ->firstOrFail();

            $this->ensureCashierIsAuthorized($cashier, $shift);

            $globalManualDiscount = $this->normalizeAmount(
                $data->manualDiscountTotal,
                'diskon manual'
            );
            $amountPaid = $this->normalizeAmount($data->amountPaid, 'jumlah pembayaran');

            // Accumulators.
            $subtotalRetail = '0.0000';
            $subtotalService = '0.0000';
            $tierDiscountTotal = '0.0000';
            $retailManualDiscount = '0.0000';
            $serviceManualDiscount = '0.0000';
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
                    $retailManualDiscount = bcadd(
                        $retailManualDiscount,
                        $result['manualDiscountTotal'],
                        4
                    );
                } elseif ($cartItem->itemType === 'SERVICE') {
                    $result = $this->processServiceItem($cartItem, (string) $cashier->id);
                    $serviceManualDiscount = bcadd(
                        $serviceManualDiscount,
                        $result['manualDiscountTotal'],
                        4
                    );
                } else {
                    throw new \DomainException('Tipe item keranjang tidak dikenal.');
                }

                $subtotalRetail = bcadd($subtotalRetail, $result['subtotalRetail'], 4);
                $subtotalService = bcadd($subtotalService, $result['subtotalService'], 4);
                $tierDiscountTotal = bcadd($tierDiscountTotal, $result['tierDiscountTotal'], 4);
                $totalCogsRetail = bcadd($totalCogsRetail, $result['cogsRetail'], 4);
                $totalCogsService = bcadd($totalCogsService, $result['cogsService'], 4);

                $transactionItems[] = $result['transactionItem'];
                $stockLedgerEntries = array_merge($stockLedgerEntries, $result['stockLedgerEntries']);
                $journalLines = array_merge($journalLines, $result['journalLines']);
            }

            $itemManualDiscountTotal = bcadd($retailManualDiscount, $serviceManualDiscount, 4);

            // Diskon manual tingkat keranjang dialokasikan proporsional ke segmen
            // ritel dan jasa berdasarkan nilai bersih masing-masing setelah diskon
            // item dan diskon grosir.
            $retailNetBeforeGlobal = bcsub($subtotalRetail, $retailManualDiscount, 4);
            $serviceNetBeforeGlobal = bcsub(
                bcsub($subtotalService, $tierDiscountTotal, 4),
                $serviceManualDiscount,
                4
            );
            $baseForGlobalDiscount = bcadd($retailNetBeforeGlobal, $serviceNetBeforeGlobal, 4);

            // Resolusi akun potongan agar tidak bergantung pada kode hardcoded.
            $coaDiscRetail = ChartOfAccount::where('account_code', '4-1102')->lockForUpdate()->firstOrFail();
            $coaDiscService = ChartOfAccount::where('account_code', '4-2109')->lockForUpdate()->firstOrFail();

            if (bccomp($globalManualDiscount, '0.0000', 4) === 1) {
                if (bccomp($baseForGlobalDiscount, '0.0000', 4) !== 1) {
                    throw new \DomainException('Diskon manual tidak dapat diterapkan pada keranjang bernilai nol.');
                }

                if (bccomp($globalManualDiscount, $baseForGlobalDiscount, 4) === 1) {
                    throw new \DomainException('Diskon manual melebihi total belanja.');
                }

                $retailShare = bccomp($retailNetBeforeGlobal, '0.0000', 4) === 1
                    ? bcdiv(bcmul($globalManualDiscount, $retailNetBeforeGlobal, 6), $baseForGlobalDiscount, 4)
                    : '0.0000';
                $serviceShare = bcsub($globalManualDiscount, $retailShare, 4);

                if (bccomp($retailShare, '0.0000', 4) === 1) {
                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: $coaDiscRetail->account_code,
                        debit: $retailShare,
                        credit: '0.0000',
                        lineDescription: 'Diskon manual keranjang (etalase)',
                    );
                }

                if (bccomp($serviceShare, '0.0000', 4) === 1) {
                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: $coaDiscService->account_code,
                        debit: $serviceShare,
                        credit: '0.0000',
                        lineDescription: 'Diskon manual keranjang (jasa)',
                    );
                }
            }

            $manualDiscountTotal = bcadd($itemManualDiscountTotal, $globalManualDiscount, 4);

            $totalGross = bcadd($subtotalRetail, $subtotalService, 4);
            $totalDiscounts = bcadd($tierDiscountTotal, $manualDiscountTotal, 4);
            $grandTotal = bcsub($totalGross, $totalDiscounts, 4);

            if (bccomp($grandTotal, '0.0000', 4) !== 1) {
                throw new \DomainException('Grand total transaksi harus lebih besar dari nol.');
            }

            if ($data->paymentMethod === PaymentMethod::CASH) {
                if (bccomp($amountPaid, $grandTotal, 4) === -1) {
                    throw new \DomainException('Uang pembayaran tunai kurang dari grand total.');
                }

                $changeAmount = bcsub($amountPaid, $grandTotal, 4);
            } else {
                if (bccomp($amountPaid, $grandTotal, 4) !== 0) {
                    throw new \DomainException('Pembayaran non-tunai harus sama dengan grand total.');
                }

                $changeAmount = '0.0000';
            }

            // --- Buat pos_transactions ---
            $invoiceSequence = PosTransaction::whereDate('transaction_date', today())->count() + 1;

            do {
                $invoiceNumber = 'INV-'.Carbon::now()->format('Ymd').'-'
                    .str_pad((string) $invoiceSequence, 4, '0', STR_PAD_LEFT);
                $invoiceSequence++;
            } while (PosTransaction::where('invoice_number', $invoiceNumber)->exists());

            $transaction = PosTransaction::create([
                'invoice_number' => $invoiceNumber,
                'pos_shift_id' => $shift->id,
                'cashier_user_id' => $cashier->id,
                'customer_name' => $data->customerName ?: 'Pelanggan Umum',
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

                if (! empty($ti['_boms'])) {
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
                $sle['reference_id'] = $transaction->id;
                $sle['pos_shift_id'] = $shift->id;
                StockLedgerEntry::create(array_filter($sle, fn ($k) => $k !== '_product', ARRAY_FILTER_USE_KEY));
            }

            // --- Update system_expected_cash pada shift (hanya transaksi tunai) ---
            if ($data->paymentMethod === PaymentMethod::CASH) {
                $shift->increment('system_expected_cash', $grandTotal);
            }

            // --- Posting jurnal akuntansi lengkap ---
            $period = AccountingPeriod::where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            if ($period === null) {
                throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat transaksi.');
            }

            $coaCash = ChartOfAccount::where('account_code', '1-1101')->lockForUpdate()->firstOrFail();
            $coaQris = ChartOfAccount::where('account_code', '1-1103')->lockForUpdate()->firstOrFail();

            $paymentMethodCode = $data->paymentMethod === PaymentMethod::CASH ? $coaCash->account_code : $coaQris->account_code;

            $allJournalLines = array_merge([
                // Debit Kas / QRIS.
                new JournalLineData(
                    chartOfAccountCode: $paymentMethodCode,
                    debit: $grandTotal,
                    credit: '0.0000',
                    lineDescription: 'Debit Kas / QRIS (Penerimaan POS)',
                ),
            ], $journalLines);

            $journalData = new JournalEntryData(
                journalNumber: 'JRN-'.Carbon::now()->format('Ymd').'-'.
                    str_pad((string) (JournalEntry::count() + 1), 4, '0', STR_PAD_LEFT),
                accountingPeriodId: (string) $period->getKey(),
                posShiftId: (string) $shift->id,
                entryDate: Carbon::now(),
                journalType: JournalType::SALES_POS,
                referenceType: 'App\Models\PosTransaction',
                referenceId: $transaction->id,
                description: 'Transaksi POS '.$invoiceNumber.' ('.$data->paymentMethod->label().')',
                lines: $allJournalLines,
                createdByUserId: (string) $cashier->id,
            );

            $journalEntry = app(PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            $transaction->update(['journal_entry_id' => $journalEntry->id]);

            return $transaction->fresh(['items.boms', 'journalEntry']);
        });
    }

    /**
     * Pastikan kasir aktif dan berhak bertransaksi pada shift yang dipilih.
     */
    private function ensureCashierIsAuthorized(User $cashier, PosShift $shift): void
    {
        if ($cashier->hasAnyRole([UserRole::SUPERADMIN, UserRole::ADMIN])) {
            return;
        }

        if ($cashier->role_type === UserRole::PIKET_SISWA) {
            $isOperator = ShiftAttendance::where('pos_shift_id', $shift->id)
                ->where('active_operator_user_id', $cashier->id)
                ->exists();

            if (! $isOperator) {
                throw new \DomainException('Kasir tidak terdaftar sebagai operator aktif pada shift ini.');
            }

            return;
        }

        if ($cashier->role_type === UserRole::PIKET_GURU) {
            $isSupervisor = (int) $shift->supervisor_teacher_id === (int) $cashier->id;
            $isOperator = ShiftAttendance::where('pos_shift_id', $shift->id)
                ->where('active_operator_user_id', $cashier->id)
                ->exists();

            if (! $isSupervisor && ! $isOperator) {
                throw new \DomainException('Guru piket bukan supervisor atau operator aktif pada shift ini.');
            }

            return;
        }

        throw new \DomainException('Peran pengguna tidak berhak melakukan transaksi kasir.');
    }

    /**
     * Validasi dan normalisasi nominal non-negatif dengan maksimal 4 desimal.
     */
    private function normalizeAmount(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new \DomainException("Nilai {$label} tidak valid.");
        }

        return number_format((float) $value, 4, '.', '');
    }

    /**
     * Proses item retail: kunci produk, validasi stok, potong stok, hitung COGS.
     *
     * @return array{
     *     subtotalRetail: string,
     *     subtotalService: string,
     *     tierDiscountTotal: string,
     *     manualDiscountTotal: string,
     *     cogsRetail: string,
     *     cogsService: string,
     *     transactionItem: array,
     *     stockLedgerEntries: array,
     *     journalLines: JournalLineData[]
     * }
     */
    private function processRetailItem(CartItemData $item, string $cashierId): array
    {
        $product = Product::with(['inventoryAccount', 'cogsAccount'])
            ->where('id', $item->productId)
            ->where('is_active', true)
            ->where('inventory_type', 'RETAIL')
            ->lockForUpdate()
            ->firstOrFail();

        $inventoryCode = $product->inventoryAccount?->account_code ?? '1-1301';
        $cogsCode = $product->cogsAccount?->account_code ?? '5-1101';
        $revenueCode = $product->inventoryAccount?->account_code ?? '1-1301';

        $qty = $this->normalizeQuantity($item->qty);
        $manualDiscount = $this->normalizeAmount($item->manualDiscountAmount, 'diskon item');
        $unitPrice = (string) $product->selling_price;
        $avgCost = (string) $product->average_cost;

        $subtotalGross = bcmul($qty, $unitPrice, 4);

        if (bccomp($manualDiscount, $subtotalGross, 4) === 1) {
            throw new \DomainException('Diskon item melebihi nilai barang '.$product->name.'.');
        }

        if (bccomp((string) $product->current_stock, $qty, 4) === -1) {
            throw new \DomainException('Stok tidak mencukupi untuk '.$product->name);
        }

        $subtotalNet = bcsub($subtotalGross, $manualDiscount, 4);
        $cogsTotal = bcmul($qty, $avgCost, 4);

        // Potong stok.
        $balanceBefore = (string) $product->current_stock;
        $product->update(['current_stock' => bcsub($balanceBefore, $qty, 4)]);

        $journalLines = [];
        if (bccomp($subtotalGross, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $revenueCode,
                debit: '0.0000',
                credit: $subtotalGross,
                lineDescription: 'Pendapatan penjualan '.$product->name,
            );
        }
        if (bccomp($manualDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: '4-1102',
                debit: $manualDiscount,
                credit: '0.0000',
                lineDescription: 'Potongan penjualan '.$product->name,
            );
        }
        if (bccomp($cogsTotal, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $cogsCode,
                debit: $cogsTotal,
                credit: '0.0000',
                lineDescription: 'HPP '.$product->name,
            );
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $inventoryCode,
                debit: '0.0000',
                credit: $cogsTotal,
                lineDescription: 'Kredit Persediaan Etalase '.$product->name,
            );
        }

        $stockLedgerEntries = [[
            'product_id' => $product->id,
            '_product' => $product,
            'qty_change' => '-'.$qty,
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
        $service = Service::with(['revenueAccount', 'discountAccount'])
            ->where('id', $item->serviceId)
            ->where('is_active', true)
            ->firstOrFail();

        $revenueCode = $service->revenueAccount?->account_code ?? '4-2101';
        $discountCode = $service->discountAccount?->account_code ?? '4-2109';

        $qty = $this->normalizeQuantity($item->qty);
        $manualDiscount = $this->normalizeAmount($item->manualDiscountAmount, 'diskon item');

        $pricing = $this->tierResolver->resolve($service, $qty);
        $finalUnitPrice = $pricing['final_unit_price'];
        $subtotalNet = bcmul($qty, $finalUnitPrice, 4);
        $tierDiscount = $pricing['line_tier_discount_total'];
        $grossSubtotal = $pricing['line_subtotal_gross'];

        if (bccomp($manualDiscount, $subtotalNet, 4) === 1) {
            throw new \DomainException('Diskon item melebihi nilai layanan '.$service->name.'.');
        }

        $lineNetTotal = bcsub($subtotalNet, $manualDiscount, 4);
        $finalUnitPriceAfterManual = bcsub($finalUnitPrice, bcdiv($manualDiscount, $qty, 4), 4);

        $cogsService = '0.0000';
        $bomRecords = [];
        $stockLedgerEntries = [];
        $journalLines = [];

        $boms = $service->boms()->get();
        foreach ($boms as $bom) {
            $rawMaterial = Product::with(['inventoryAccount', 'cogsAccount'])
                ->where('id', $bom->raw_material_product_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $consumptionQty = (string) $bom->consumption_qty;

            if (bccomp($consumptionQty, '0.0000', 4) !== 1) {
                throw new \DomainException('Konfigurasi BoM layanan '.$service->name.' tidak valid.');
            }

            $qtyConsumed = bcmul($qty, $consumptionQty, 4);
            $unitCost = (string) $rawMaterial->average_cost;
            $bomCost = bcmul($qtyConsumed, $unitCost, 4);

            if (bccomp((string) $rawMaterial->current_stock, $qtyConsumed, 4) === -1) {
                throw new \DomainException('Stok bahan baku tidak mencukupi untuk '.$rawMaterial->name);
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
                'qty_change' => '-'.$qtyConsumed,
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
            chartOfAccountCode: $revenueCode,
            debit: '0.0000',
            credit: $grossSubtotal,
            lineDescription: 'Pendapatan jasa '.$service->name.' (bruto)',
        );
        if (bccomp($tierDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $discountCode,
                debit: $tierDiscount,
                credit: '0.0000',
                lineDescription: 'Potongan grosir jasa '.$service->name,
            );
        }
        if (bccomp($manualDiscount, '0.0000', 4) === 1) {
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $discountCode,
                debit: $manualDiscount,
                credit: '0.0000',
                lineDescription: 'Diskon manual jasa '.$service->name,
            );
        }
        if (bccomp($cogsService, '0.0000', 4) === 1) {
            $cogsCode = $rawMaterial->cogsAccount?->account_code ?? '5-2101';
            $invCode = $rawMaterial->inventoryAccount?->account_code ?? '1-1302';
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $cogsCode,
                debit: $cogsService,
                credit: '0.0000',
                lineDescription: 'HPP bahan baku jasa '.$service->name,
            );
            $journalLines[] = new JournalLineData(
                chartOfAccountCode: $invCode,
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
            'final_unit_price' => $finalUnitPriceAfterManual,
            'line_subtotal_gross' => $grossSubtotal,
            'line_total_discount' => bcadd($tierDiscount, $manualDiscount, 4),
            'line_net_total' => $lineNetTotal,
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

    private function normalizeQuantity(string $value): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new \DomainException('Kuantitas item tidak valid.');
        }

        $normalized = number_format((float) $value, 4, '.', '');

        if (bccomp($normalized, '0.0000', 4) !== 1) {
            throw new \DomainException('Kuantitas item harus lebih besar dari nol.');
        }

        return $normalized;
    }
}
