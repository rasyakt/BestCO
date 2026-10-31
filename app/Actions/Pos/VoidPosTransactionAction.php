<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Actions\Accounting\CreateReversingJournalAction;
use App\Models\JournalEntry;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class VoidPosTransactionAction
{
    public function __construct(
        private readonly CreateReversingJournalAction $reversingAction,
    ) {}

    public function execute(
        PosTransaction $transaction,
        User $teacherSupervisor,
        string $voidReason
    ): PosTransaction {
        return DB::transaction(function () use ($transaction, $teacherSupervisor, $voidReason): PosTransaction {
            if ($transaction->status === 'VOIDED') {
                throw new \DomainException('Transaksi ini sudah berstatus VOID.');
            }

            // 1. Kembalikan stok item retail & bahan baku jasa (BoM)
            foreach ($transaction->items as $item) {
                if ($item->item_type === 'RETAIL_PRODUCT' && $item->product_id) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                    $oldStock = (string) $product->current_stock;
                    $restoreQty = (string) $item->qty;
                    $newStock = bcadd($oldStock, $restoreQty, 4);

                    $product->update(['current_stock' => $newStock]);

                    StockLedgerEntry::create([
                        'product_id' => $product->id,
                        'pos_shift_id' => $transaction->pos_shift_id,
                        'pos_transaction_id' => $transaction->id,
                        'reference_type' => 'VOID_RESTORE',
                        'reference_id' => $transaction->id,
                        'qty_change' => '+' . $restoreQty,
                        'balance_before' => $oldStock,
                        'balance_after' => $newStock,
                        'unit_cost' => (string) $product->average_cost,
                        'total_value' => bcmul($restoreQty, (string) $product->average_cost, 4),
                        'created_by' => $teacherSupervisor->id,
                        'created_at' => Carbon::now(),
                    ]);
                } elseif ($item->item_type === 'SERVICE') {
                    foreach ($item->boms as $bom) {
                        $rawMaterial = Product::where('id', $bom->raw_material_product_id)->lockForUpdate()->firstOrFail();
                        $oldStock = (string) $rawMaterial->current_stock;
                        $restoreQty = (string) $bom->qty_consumed;
                        $newStock = bcadd($oldStock, $restoreQty, 4);

                        $rawMaterial->update(['current_stock' => $newStock]);

                        StockLedgerEntry::create([
                            'product_id' => $rawMaterial->id,
                            'pos_shift_id' => $transaction->pos_shift_id,
                            'pos_transaction_id' => $transaction->id,
                            'reference_type' => 'VOID_RESTORE',
                            'reference_id' => $transaction->id,
                            'qty_change' => '+' . $restoreQty,
                            'balance_before' => $oldStock,
                            'balance_after' => $newStock,
                            'unit_cost' => (string) $rawMaterial->average_cost,
                            'total_value' => bcmul($restoreQty, (string) $rawMaterial->average_cost, 4),
                            'created_by' => $teacherSupervisor->id,
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            // 2. Buat Jurnal Pembalik (Reversing Journal)
            if ($transaction->journal_entry_id) {
                $originalJournal = JournalEntry::with('items.chartOfAccount')->findOrFail($transaction->journal_entry_id);
                $revNumber = 'REV-' . Carbon::now()->format('Ymd') . '-' .
                    str_pad((string) (JournalEntry::count() + 1), 4, '0', STR_PAD_LEFT);

                $this->reversingAction->execute($originalJournal, $revNumber, $teacherSupervisor);
            }

            // 3. Update shift expected cash jika shift masih OPEN
            $shift = $transaction->posShift;
            if ($shift && $shift->status->value === 'OPEN') {
                $newExpected = bcsub((string) $shift->system_expected_cash, (string) $transaction->grand_total, 4);
                $shift->update(['system_expected_cash' => $newExpected]);
            }

            // 4. Update status transaksi menjadi VOIDED
            $transaction->update([
                'status' => 'VOIDED',
                'voided_by_teacher_id' => $teacherSupervisor->id,
                'void_reason' => $voidReason,
            ]);

            return $transaction->fresh(['items.boms', 'journalEntry']);
        });
    }
}

