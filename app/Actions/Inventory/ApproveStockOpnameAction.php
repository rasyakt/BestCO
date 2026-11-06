<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostDoubleEntryJournalAction;
use App\DTOs\Accounting\JournalEntryData;
use App\DTOs\Accounting\JournalLineData;
use App\Enums\JournalType;
use App\Enums\StockOpnameStatus;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ApproveStockOpnameAction
{
    private const SHORTAGE_ACCOUNT_CODE = '6-1902';
    private const OVERAGE_ACCOUNT_CODE = '4-9102';

    /**
     * Setujui sesi opname lalu terapkan penyesuaian stok dan jurnalnya.
     *
     * Selisih yang dicatat adalah selisih saat penghitungan (snapshot draf).
     * Jika stok berubah setelah penghitungan (mis. ada penjualan), selisih yang
     * sama tetap diterapkan pada stok terkini agar hasil fisik tetap konsisten.
     */
    public function execute(StockOpname $opname, string $approvedByUserId): StockOpname
    {
        return DB::transaction(function () use ($opname, $approvedByUserId): StockOpname {
            /** @var StockOpname $locked */
            $locked = StockOpname::whereKey($opname->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isDraft()) {
                throw new \DomainException('Sesi opname ini sudah disetujui dan tidak dapat diproses ulang.');
            }

            $locked->load('items');
            $items = $locked->items;

            if ($items->isEmpty()) {
                throw new \DomainException('Sesi opname tidak memiliki item untuk disetujui.');
            }

            $totalShortage = '0.0000';
            $totalOverage = '0.0000';
            $inventoryDebits = [];
            $inventoryCredits = [];

            foreach ($items as $item) {
                /** @var StockOpnameItem $item */
                $differenceQty = (string) $item->difference_qty;

                if (bccomp($differenceQty, '0.0000', 4) === 0) {
                    continue;
                }

                /** @var Product $product */
                $product = Product::with('inventoryAccount')
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceBefore = (string) $product->current_stock;
                $balanceAfter = bcadd($balanceBefore, $differenceQty, 4);

                if (bccomp($balanceAfter, '0.0000', 4) === -1) {
                    throw new \DomainException(
                        'Penyesuaian opname membuat stok negatif untuk ' . $product->name
                        . ' (stok sekarang: ' . $balanceBefore . ', selisih: ' . $differenceQty . ').'
                    );
                }

                $unitCost = (string) $item->unit_cost;
                $differenceValue = (string) $item->difference_value;
                $absoluteValue = bccomp($differenceValue, '0.0000', 4) === -1
                    ? bcmul($differenceValue, '-1', 4)
                    : $differenceValue;

                $inventoryAccountCode = $product->inventoryAccount?->account_code
                    ?? ($product->inventory_type->value === 'RETAIL' ? '1-1301' : '1-1302');

                if (bccomp($differenceQty, '0.0000', 4) === -1) {
                    $totalShortage = bcadd($totalShortage, $absoluteValue, 4);
                    $inventoryCredits[$inventoryAccountCode] = bcadd(
                        $inventoryCredits[$inventoryAccountCode] ?? '0.0000',
                        $absoluteValue,
                        4
                    );
                } else {
                    $totalOverage = bcadd($totalOverage, $absoluteValue, 4);
                    $inventoryDebits[$inventoryAccountCode] = bcadd(
                        $inventoryDebits[$inventoryAccountCode] ?? '0.0000',
                        $absoluteValue,
                        4
                    );
                }

                $product->update(['current_stock' => $balanceAfter]);

                StockLedgerEntry::create([
                    'product_id' => $product->id,
                    'pos_shift_id' => null,
                    'reference_type' => 'OPNAME',
                    'reference_id' => $locked->id,
                    'qty_change' => $differenceQty,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'unit_cost' => $unitCost,
                    'total_value' => $absoluteValue,
                    'created_by' => $approvedByUserId,
                    'created_at' => Carbon::now(),
                ]);
            }

            $shouldPostJournal = bccomp($totalShortage, '0.0000', 4) === 1
                || bccomp($totalOverage, '0.0000', 4) === 1;

            $journalEntry = null;

            if ($shouldPostJournal) {
                $period = AccountingPeriod::where('is_closed', false)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($period === null) {
                    throw new \DomainException('Tidak ada periode akuntansi terbuka untuk mencatat penyesuaian opname.');
                }

                $journalLines = [];

                if (bccomp($totalShortage, '0.0000', 4) === 1) {
                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: self::SHORTAGE_ACCOUNT_CODE,
                        debit: $totalShortage,
                        credit: '0.0000',
                        lineDescription: 'Beban selisih kurang persediaan opname ' . $locked->opname_number,
                    );

                    foreach ($inventoryCredits as $accountCode => $amount) {
                        if (bccomp($amount, '0.0000', 4) !== 1) {
                            continue;
                        }

                        $journalLines[] = new JournalLineData(
                            chartOfAccountCode: $accountCode,
                            debit: '0.0000',
                            credit: $amount,
                            lineDescription: 'Kredit persediaan selisih kurang opname ' . $locked->opname_number,
                        );
                    }
                }

                if (bccomp($totalOverage, '0.0000', 4) === 1) {
                    foreach ($inventoryDebits as $accountCode => $amount) {
                        if (bccomp($amount, '0.0000', 4) !== 1) {
                            continue;
                        }

                        $journalLines[] = new JournalLineData(
                            chartOfAccountCode: $accountCode,
                            debit: $amount,
                            credit: '0.0000',
                            lineDescription: 'Debit persediaan selisih lebih opname ' . $locked->opname_number,
                        );
                    }

                    $journalLines[] = new JournalLineData(
                        chartOfAccountCode: self::OVERAGE_ACCOUNT_CODE,
                        debit: '0.0000',
                        credit: $totalOverage,
                        lineDescription: 'Pendapatan selisih lebih persediaan opname ' . $locked->opname_number,
                    );
                }

                $entryDate = Carbon::parse($locked->opname_date)->startOfDay();

                $journalEntry = app(PostDoubleEntryJournalAction::class)->execute(
                    new JournalEntryData(
                        journalNumber: $this->nextJournalNumber($entryDate),
                        accountingPeriodId: (string) $period->getKey(),
                        posShiftId: null,
                        entryDate: $entryDate,
                        journalType: JournalType::ADJUSTMENT,
                        referenceType: 'App\Models\StockOpname',
                        referenceId: $locked->id,
                        description: 'Penyesuaian stok opname ' . $locked->opname_number . ' (' . $locked->items_count . ' item)',
                        lines: $journalLines,
                        createdByUserId: $approvedByUserId,
                    )
                );
            }

            $locked->update([
                'status' => StockOpnameStatus::APPROVED,
                'approved_by_user_id' => $approvedByUserId,
                'approved_at' => Carbon::now(),
                'journal_entry_id' => $journalEntry?->id,
                'total_shortage_value' => $totalShortage,
                'total_overage_value' => $totalOverage,
            ]);

            return $locked->fresh([
                'items.product.unit',
                'createdBy',
                'approvedBy',
                'journalEntry',
            ]);
        });
    }

    private function nextJournalNumber(Carbon $entryDate): string
    {
        $sequence = JournalEntry::whereDate('entry_date', $entryDate->toDateString())->count() + 1;

        do {
            $journalNumber = 'JRN-' . $entryDate->format('Ymd') . '-'
                . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (JournalEntry::where('journal_number', $journalNumber)->exists());

        return $journalNumber;
    }
}
