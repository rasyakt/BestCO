<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\DTOs\Inventory\SpoilageData;
use App\Enums\JournalType;
use App\Enums\ShiftStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ServiceBom;
use App\Models\SpoilageLog;
use App\Models\SpoilageLogItem;
use App\Models\StockLedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordSpoilageAction
{
    /**
     * Catat barang rusak / gagal produksi, potong stok, dan posting jurnal beban
     * kerusakan sesuai jenis persediaan yang terdampak.
     */
    public function execute(SpoilageData $data): SpoilageLog
    {
        return DB::transaction(function () use ($data): SpoilageLog {
            $posShift = PosShift::where('id', $data->posShiftId)
                ->where('status', ShiftStatus::OPEN->value)
                ->lockForUpdate()
                ->firstOrFail();

            $failedOutputQty = $this->normalizePositiveQty($data->failedOutputQty, 'Jumlah output gagal');

            // Hitung total biaya kerusakan dan kumpulkan item yang terbuang.
            $items = [];
            $totalCost = '0.0000';

            if ($data->spoilageSource === 'SERVICE_PRODUCTION') {
                if ($data->serviceId === null) {
                    throw new \DomainException('Layanan jasa wajib dipilih untuk kerusakan produksi.');
                }

                $serviceBoms = ServiceBom::where('service_id', $data->serviceId)
                    ->where('is_required', true)
                    ->get();

                if ($serviceBoms->isEmpty()) {
                    throw new \DomainException('Layanan yang dipilih tidak memiliki resep bahan baku (BoM).');
                }

                foreach ($serviceBoms as $bom) {
                    $rawMaterial = Product::where('id', $bom->raw_material_product_id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $qtyWasted = bcmul($failedOutputQty, (string) $bom->consumption_qty, 4);

                    if (bccomp($qtyWasted, '0.0000', 4) !== 1) {
                        throw new \DomainException('Kuantitas kerusakan bahan baku tidak valid.');
                    }

                    $items[] = $this->buildItem($rawMaterial, $qtyWasted);
                    $totalCost = bcadd($totalCost, $items[array_key_last($items)]['subtotal'], 4);
                }
            } elseif (! empty($data->directItems)) {
                $usedProductIds = [];

                foreach ($data->directItems as $directItem) {
                    $product = Product::where('id', $directItem['product_id'] ?? 0)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (isset($usedProductIds[$product->id])) {
                        throw new \DomainException('Produk yang sama tidak boleh muncul lebih dari satu kali pada daftar kerusakan.');
                    }

                    $usedProductIds[$product->id] = true;

                    // HPP selalu diambil dari master produk, bukan dari input klien.
                    $qtyWasted = $this->normalizePositiveQty(
                        (string) ($directItem['qty_wasted'] ?? ''),
                        'Kuantitas kerusakan ' . $product->name
                    );

                    $items[] = $this->buildItem($product, $qtyWasted);
                    $totalCost = bcadd($totalCost, $items[array_key_last($items)]['subtotal'], 4);
                }
            } else {
                throw new \DomainException('Tidak ada item kerusakan yang dicatat.');
            }

            $spoilageNumber = $this->nextSpoilageNumber();

            $spoilageLog = SpoilageLog::create([
                'spoilage_number' => $spoilageNumber,
                'pos_shift_id' => $posShift->id,
                'spoilage_source' => $data->spoilageSource,
                'service_id' => $data->serviceId,
                'failed_output_qty' => $failedOutputQty,
                'reason_category' => $data->reasonCategory->value,
                'reason_description' => $data->reasonDescription,
                'total_spoilage_cost' => $totalCost,
                'reported_by_user_id' => $data->reportedByUserId,
                'verified_by_user_id' => $data->verifiedByUserId,
                'journal_entry_id' => null,
                'logged_at' => Carbon::now(),
            ]);

            // Tulis spoilage_log_items dan stock_ledger_entries.
            foreach ($items as $item) {
                /** @var Product $product */
                $product = $item['product'];

                SpoilageLogItem::create([
                    'spoilage_log_id' => $spoilageLog->id,
                    'product_id' => $product->id,
                    'qty_wasted' => $item['qty_wasted'],
                    'unit_cost_at_spoilage' => $item['unit_cost'],
                    'subtotal_cost' => $item['subtotal'],
                ]);

                StockLedgerEntry::create([
                    'product_id' => $product->id,
                    'pos_shift_id' => $posShift->id,
                    'reference_type' => 'SPOILAGE',
                    'reference_id' => $spoilageLog->id,
                    'qty_change' => '-' . $item['qty_wasted'],
                    'balance_before' => $item['balance_before'],
                    'balance_after' => $item['balance_after'],
                    'unit_cost' => $item['unit_cost'],
                    'total_value' => $item['subtotal'],
                    'created_by' => $data->reportedByUserId,
                    'created_at' => Carbon::now(),
                ]);
            }

            if (bccomp($totalCost, '0.0000', 4) !== 1) {
                return $spoilageLog->fresh(['items.product', 'journalEntry']);
            }

            $period = AccountingPeriod::where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            if ($period === null) {
                throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat kerusakan.');
            }

            $journalLines = [];
            $expenseByAccount = [];
            $inventoryByAccount = [];

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $inventoryCode = $product->inventoryAccount?->account_code
                    ?? ($product->inventory_type->value === 'RETAIL' ? '1-1301' : '1-1302');
                $expenseCode = $product->inventory_type->value === 'RETAIL' ? '6-1102' : '6-1101';

                $expenseByAccount[$expenseCode] = bcadd(
                    $expenseByAccount[$expenseCode] ?? '0.0000',
                    $item['subtotal'],
                    4
                );
                $inventoryByAccount[$inventoryCode] = bcadd(
                    $inventoryByAccount[$inventoryCode] ?? '0.0000',
                    $item['subtotal'],
                    4
                );
            }

            foreach ($expenseByAccount as $code => $amount) {
                if (bccomp($amount, '0.0000', 4) === 1) {
                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: $code,
                        debit: $amount,
                        credit: '0.0000',
                        lineDescription: 'Debit Beban Kerusakan / Kegagalan Produksi',
                    );
                }
            }

            foreach ($inventoryByAccount as $code => $amount) {
                if (bccomp($amount, '0.0000', 4) === 1) {
                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: $code,
                        debit: '0.0000',
                        credit: $amount,
                        lineDescription: 'Kredit Persediaan Tersedia',
                    );
                }
            }

            $journalData = new JournalEntryData(
                journalNumber: $this->nextJournalNumber(),
                accountingPeriodId: (string) $period->getKey(),
                posShiftId: (string) $posShift->id,
                entryDate: Carbon::now(),
                journalType: JournalType::SPOILAGE,
                referenceType: 'App\Models\SpoilageLog',
                referenceId: $spoilageLog->id,
                description: 'Barang rusak / gagal produksi: ' . $data->reasonDescription .
                    ' (total Rp ' . $totalCost . ')',
                lines: $journalLines,
                createdByUserId: $data->reportedByUserId,
            );

            $journalEntry = app(\App\Actions\Accounting\PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            $spoilageLog->update(['journal_entry_id' => $journalEntry->id]);

            return $spoilageLog->fresh(['items.product', 'journalEntry']);
        });
    }

    /**
     * @return array{product: Product, qty_wasted: string, unit_cost: string, subtotal: string, balance_before: string, balance_after: string}
     */
    private function buildItem(Product $product, string $qtyWasted): array
    {
        $unitCost = (string) $product->average_cost;
        $subtotal = bcmul($qtyWasted, $unitCost, 4);
        $balanceBefore = (string) $product->current_stock;

        if (bccomp($balanceBefore, $qtyWasted, 4) === -1) {
            throw new \DomainException(
                'Stok tidak mencukupi untuk pencatatan kerusakan: ' .
                $product->name . ' (tersedia: ' . $balanceBefore . ', dibutuhkan: ' . $qtyWasted . ')'
            );
        }

        $balanceAfter = bcsub($balanceBefore, $qtyWasted, 4);

        $product->update(['current_stock' => $balanceAfter]);

        return [
            'product' => $product,
            'qty_wasted' => $qtyWasted,
            'unit_cost' => $unitCost,
            'subtotal' => $subtotal,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
    }

    private function normalizePositiveQty(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new \DomainException($label . ' tidak valid.');
        }

        $normalized = number_format((float) $value, 4, '.', '');

        if (bccomp($normalized, '0.0000', 4) !== 1) {
            throw new \DomainException($label . ' harus lebih besar dari nol.');
        }

        return $normalized;
    }

    private function nextSpoilageNumber(): string
    {
        $sequence = SpoilageLog::count() + 1;

        do {
            $spoilageNumber = 'SPL-' . Carbon::now()->format('Ymd') . '-'
                . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (SpoilageLog::where('spoilage_number', $spoilageNumber)->exists());

        return $spoilageNumber;
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
