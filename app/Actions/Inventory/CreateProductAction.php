<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostDoubleEntryJournalAction;
use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\InventoryType;
use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateProductAction
{
    private const FUNDING_ACCOUNT_CODES = ['1-1102', '2-1101', '3-1101'];

    /**
     * Daftarkan produk baru (barang etalase atau bahan baku jasa) beserta HPP,
     * harga jual, dan saldo awal persediaan jika ada.
     *
     * @param array{
     *     name: string,
     *     sku?: ?string,
     *     barcode?: ?string,
     *     category_id: int|string,
     *     unit_id: int|string,
     *     inventory_type: string,
     *     average_cost: int|float|string,
     *     selling_price: int|float|string,
     *     min_stock_alert?: int|float|string,
     *     initial_stock?: int|float|string,
     *     funding_account_id?: ?string,
     *     user_id: int|string
     * } $data
     */
    public function execute(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $unit = Unit::findOrFail($data['unit_id']);
            $category = Category::findOrFail($data['category_id']);

            $inventoryType = InventoryType::from($data['inventory_type']);
            $avgCost = number_format((float) ($data['average_cost'] ?? 0), 4, '.', '');
            $sellingPrice = $inventoryType === InventoryType::RAW_MATERIAL
                ? '0.0000'
                : number_format((float) ($data['selling_price'] ?? 0), 4, '.', '');
            $minAlert = number_format((float) ($data['min_stock_alert'] ?? 5), 4, '.', '');
            $initialStock = number_format((float) ($data['initial_stock'] ?? 0), 4, '.', '');

            // Otomatisasi pemetaan akun COA
            if ($inventoryType === InventoryType::RETAIL) {
                $invCoa = ChartOfAccount::where('account_code', '1-1301')->firstOrFail(); // Persediaan Barang Dagang
                $cogsCoa = ChartOfAccount::where('account_code', '5-1101')->firstOrFail(); // HPP Barang Dagang
            } else {
                $invCoa = ChartOfAccount::where('account_code', '1-1302')->firstOrFail(); // Persediaan Bahan Baku Jasa
                $cogsCoa = ChartOfAccount::where('account_code', '5-2101')->firstOrFail(); // HPP Bahan Baku Jasa
            }

            // Generate SKU jika tidak diisi
            $sku = trim((string) ($data['sku'] ?? ''));
            if ($sku === '') {
                $prefix = $inventoryType === InventoryType::RETAIL ? 'ATK' : 'RAW';
                $slug = strtoupper((string) Str::slug(substr($data['name'], 0, 12)));
                $slug = preg_replace('/[^A-Z0-9]/', '', $slug) ?: 'ITEM';
                $sku = "{$prefix}-{$slug}-" . strtoupper($unit->code);
                $baseSku = $sku;
                $counter = 1;
                while (Product::where('sku', $sku)->exists()) {
                    $sku = "{$baseSku}-{$counter}";
                    $counter++;
                }
            } else {
                if (Product::where('sku', $sku)->exists()) {
                    throw new InvalidArgumentException("SKU {$sku} sudah terdaftar.");
                }
            }

            $barcode = trim((string) ($data['barcode'] ?? ''));
            $barcode = $barcode === '' ? null : $barcode;
            if ($barcode !== null && Product::where('barcode', $barcode)->exists()) {
                throw new InvalidArgumentException("Barcode {$barcode} sudah digunakan oleh produk lain.");
            }

            // Buat produk baru
            $product = Product::create([
                'sku' => $sku,
                'barcode' => $barcode,
                'name' => trim($data['name']),
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'inventory_type' => $inventoryType,
                'current_stock' => $initialStock,
                'min_stock_alert' => $minAlert,
                'average_cost' => $avgCost,
                'last_purchase_price' => $avgCost,
                'selling_price' => $sellingPrice,
                'inventory_account_id' => $invCoa->id,
                'cogs_account_id' => $cogsCoa->id,
                'is_active' => true,
            ]);

            // Stok awal selalu dicatat ke kartu stok. Jurnal hanya dibuat jika
            // nilai persediaan benar-benar positif.
            if (bccomp($initialStock, '0.0000', 4) > 0) {
                $totalValue = bcmul($initialStock, $avgCost, 4);
                $journalEntry = null;

                if (bccomp($totalValue, '0.0000', 4) > 0) {
                    // Default akun lawan: Kas Besar (1-1102), Utang Usaha (2-1101), atau Modal Awal (3-1101)
                    $fundingAccountId = $data['funding_account_id'] ?? null;
                    $fundingCoa = $fundingAccountId
                        ? ChartOfAccount::findOrFail($fundingAccountId)
                        : ChartOfAccount::where('account_code', '3-1101')->first()
                            ?? ChartOfAccount::where('account_code', '1-1102')->firstOrFail();

                    if (! in_array($fundingCoa->account_code, self::FUNDING_ACCOUNT_CODES, true)) {
                        throw new InvalidArgumentException('Akun sumber dana harus Kas Besar, Utang Usaha, atau Modal Awal.');
                    }

                    $period = AccountingPeriod::where('is_closed', false)
                        ->orderBy('start_date', 'desc')
                        ->first();

                    if ($period === null) {
                        throw new InvalidArgumentException('Tidak ada periode akuntansi terbuka untuk mencatat saldo awal.');
                    }

                    $journalData = new JournalEntryData(
                        journalNumber: $this->nextJournalNumber(),
                        accountingPeriodId: (string) $period->getKey(),
                        posShiftId: null,
                        entryDate: Carbon::now(),
                        journalType: JournalType::PURCHASE,
                        referenceType: 'App\Models\Product',
                        referenceId: $product->id,
                        description: "Saldo awal produk baru {$product->name} ({$initialStock} {$unit->code} @ Rp " . number_format((float) $avgCost, 0, ',', '.') . ")",
                        lines: [
                            new JournalLineData(
                                chartOfAccountCode: $invCoa->account_code,
                                debit: $totalValue,
                                credit: '0.0000',
                                lineDescription: "Debit Persediaan {$product->name}",
                            ),
                            new JournalLineData(
                                chartOfAccountCode: $fundingCoa->account_code,
                                debit: '0.0000',
                                credit: $totalValue,
                                lineDescription: "Kredit {$fundingCoa->account_name} (Saldo Awal)",
                            ),
                        ],
                        createdByUserId: (string) $data['user_id'],
                    );

                    $journalEntry = app(PostDoubleEntryJournalAction::class)->execute($journalData);
                }

                StockLedgerEntry::create([
                    'product_id' => $product->id,
                    'pos_shift_id' => null,
                    'reference_type' => 'INITIAL_STOCK',
                    'reference_id' => $journalEntry?->id ?? 0,
                    'qty_change' => $initialStock,
                    'balance_before' => '0.0000',
                    'balance_after' => $initialStock,
                    'unit_cost' => $avgCost,
                    'total_value' => $totalValue,
                    'created_by' => $data['user_id'],
                    'created_at' => Carbon::now(),
                ]);
            }

            return $product->fresh(['unit', 'category', 'inventoryAccount', 'cogsAccount']);
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

