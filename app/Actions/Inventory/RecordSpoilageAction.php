<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\DTOs\Inventory\SpoilageData;
use App\Enums\JournalType;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ServiceBom;
use App\Models\SpoilageLog;
use App\Models\SpoilageLogItem;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RecordSpoilageAction
{
    /**
     * Catat barang rusak / gagal produksi, potong stok bahan baku, dan posting jurnal beban kerusakan.
     */
    public function execute(SpoilageData $data): SpoilageLog
    {
        return DB::transaction(function () use ($data): SpoilageLog {
            $posShift = PosShift::findOrFail($data->posShiftId);

            // Hitung total biaya kerusakan dan kumpulkan item yang terbuang.
            $items = [];
            $totalCost = '0.0000';

            if ($data->spoilageSource === 'SERVICE_PRODUCTION' && $data->serviceId) {
                $serviceBoms = ServiceBom::where('service_id', $data->serviceId)
                    ->where('is_required', true)
                    ->get();

                foreach ($serviceBoms as $bom) {
                    $rawMaterial = Product::where('id', $bom->raw_material_product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $qtyWasted = bcmul($data->failedOutputQty, (string) $bom->consumption_qty, 4);
                    $unitCost = (string) $rawMaterial->average_cost;
                    $subtotal = bcmul($qtyWasted, $unitCost, 4);

                    // Verifikasi stok mencukupi.
                    if (bccomp((string) $rawMaterial->current_stock, $qtyWasted, 4) === -1) {
                        throw new \DomainException(
                            'Stok bahan baku tidak mencukupi untuk pencatatan kerusakan: ' .
                            $rawMaterial->name . ' (tersedia: ' . $rawMaterial->current_stock . ', dibutuhkan: ' . $qtyWasted . ')'
                        );
                    }

                    // Potong stok bahan baku.
                    $balanceBefore = (string) $rawMaterial->current_stock;
                    $rawMaterial->update([
                        'current_stock' => bcsub($balanceBefore, $qtyWasted, 4),
                    ]);

                    $totalCost = bcadd($totalCost, $subtotal, 4);

                    $items[] = [
                        'product' => $rawMaterial,
                        'qty_wasted' => $qtyWasted,
                        'unit_cost' => $unitCost,
                        'subtotal' => $subtotal,
                        'balance_before' => $balanceBefore,
                        'balance_after' => bcsub($balanceBefore, $qtyWasted, 4),
                    ];
                }
            } elseif (!empty($data->directItems)) {
                foreach ($data->directItems as $directItem) {
                    $product = Product::where('id', $directItem['product_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $qtyWasted = $directItem['qty_wasted'];
                    $unitCost = $directItem['unit_cost'];
                    $subtotal = bcmul($qtyWasted, $unitCost, 4);

                    if (bccomp((string) $product->current_stock, $qtyWasted, 4) === -1) {
                        throw new \DomainException('Stok tidak mencukupi untuk ' . $product->name);
                    }

                    $balanceBefore = (string) $product->current_stock;
                    $product->update([
                        'current_stock' => bcsub($balanceBefore, $qtyWasted, 4),
                    ]);

                    $totalCost = bcadd($totalCost, $subtotal, 4);

                    $items[] = [
                        'product' => $product,
                        'qty_wasted' => $qtyWasted,
                        'unit_cost' => $unitCost,
                        'subtotal' => $subtotal,
                        'balance_before' => $balanceBefore,
                        'balance_after' => bcsub($balanceBefore, $qtyWasted, 4),
                    ];
                }
            }

            // Buat spoilage_log.
            $spoilageNumber = 'SPL-' . Carbon::now()->format('Ymd') . '-' .
                str_pad((string) (SpoilageLog::count() + 1), 4, '0', STR_PAD_LEFT);

            $spoilageLog = SpoilageLog::create([
                'spoilage_number' => $spoilageNumber,
                'pos_shift_id' => $posShift->id,
                'spoilage_source' => $data->spoilageSource,
                'service_id' => $data->serviceId,
                'failed_output_qty' => $data->failedOutputQty,
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
                SpoilageLogItem::create([
                    'spoilage_log_id' => $spoilageLog->id,
                    'product_id' => $item['product']->id,
                    'qty_wasted' => $item['qty_wasted'],
                    'unit_cost_at_spoilage' => $item['unit_cost'],
                    'subtotal_cost' => $item['subtotal'],
                ]);

                StockLedgerEntry::create([
                    'product_id' => $item['product']->id,
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

            // Posting jurnal: Dr. 6-1101 Beban Kerusakan Produksi Jasa, Cr. 1-1302 Persediaan Bahan Baku Jasa.
            $coaSpoilageExpense = ChartOfAccount::where('account_code', '6-1101')->lockForUpdate()->firstOrFail();
            $coaInventoryRaw = ChartOfAccount::where('account_code', '1-1302')->lockForUpdate()->firstOrFail();

            $period = app(\App\Models\AccountingPeriod::class)
                ->where('is_closed', false)
                ->orderBy('start_date', 'desc')
                ->first();

            $journalData = new JournalEntryData(
                journalNumber: 'JRN-' . Carbon::now()->format('Ymd') . '-' .
                    str_pad((string) (JournalEntry::count() + 1), 4, '0', STR_PAD_LEFT),
                accountingPeriodId: (string) ($period?->getKey() ?? ''),
                posShiftId: (string) $posShift->id,
                entryDate: Carbon::now(),
                journalType: JournalType::SPOILAGE,
                referenceType: 'App\Models\SpoilageLog',
                referenceId: $spoilageLog->id,
                description: 'Barang rusak / gagal produksi: ' . $data->reasonDescription .
                    ' (total Rp ' . $totalCost . ')',
                lines: [
                    new JournalLineData(
                        chartOfAccountCode: $coaSpoilageExpense->account_code,
                        debit: $totalCost,
                        credit: '0.0000',
                        lineDescription: 'Debit Beban Kerusakan / Kegagalan Produksi Jasa',
                    ),
                    new JournalLineData(
                        chartOfAccountCode: $coaInventoryRaw->account_code,
                        debit: '0.0000',
                        credit: $totalCost,
                        lineDescription: 'Kredit Persediaan Bahan Baku Jasa',
                    ),
                ],
                createdByUserId: $data->reportedByUserId,
            );

            $journalEntry = app(\App\Actions\Accounting\PostDoubleEntryJournalAction::class)
                ->execute($journalData);

            $spoilageLog->update(['journal_entry_id' => $journalEntry->id]);

            return $spoilageLog->fresh(['items.product', 'journalEntry']);
        });
    }
}
