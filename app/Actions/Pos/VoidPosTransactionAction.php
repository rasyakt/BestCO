<?php

declare(strict_types=1);

namespace App\Actions\Pos;

use App\Actions\Accounting\CreateReversingJournalAction;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
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

            if ($transaction->status !== 'COMPLETED') {
                throw new \DomainException('Hanya transaksi berstatus COMPLETED yang dapat dibatalkan.');
            }

            if (! $teacherSupervisor->is_active || ! $teacherSupervisor->hasRole(UserRole::PIKET_GURU)) {
                throw new \DomainException('Pembatalan transaksi harus disetujui guru piket yang aktif.');
            }

            // 1. Kembalikan stok item retail & bahan baku jasa (BoM) memakai
            // nilai HPP snapshot saat transaksi, bukan HPP rata-rata terkini.
            foreach ($transaction->items as $item) {
                if ($item->item_type === 'RETAIL_PRODUCT' && $item->product_id) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                    $oldStock = (string) $product->current_stock;
                    $restoreQty = (string) $item->qty;
                    $newStock = bcadd($oldStock, $restoreQty, 4);
                    $restoreValue = (string) $item->line_cogs_total;
                    $unitCost = bccomp($restoreQty, '0.0000', 4) === 1
                        ? bcdiv($restoreValue, $restoreQty, 4)
                        : '0.0000';

                    $product->update(['current_stock' => $newStock]);

                    StockLedgerEntry::create([
                        'product_id' => $product->id,
                        'pos_shift_id' => $transaction->pos_shift_id,
                        'reference_type' => 'VOID_RESTORE',
                        'reference_id' => $transaction->id,
                        'qty_change' => '+' . $restoreQty,
                        'balance_before' => $oldStock,
                        'balance_after' => $newStock,
                        'unit_cost' => $unitCost,
                        'total_value' => $restoreValue,
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
                            'reference_type' => 'VOID_RESTORE',
                            'reference_id' => $transaction->id,
                            'qty_change' => '+' . $restoreQty,
                            'balance_before' => $oldStock,
                            'balance_after' => $newStock,
                            'unit_cost' => (string) $bom->unit_cost_snapshot,
                            'total_value' => (string) $bom->total_bom_cost,
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

            // 3. Update shift expected cash hanya untuk transaksi tunai.
            $shift = $transaction->posShift;
            if (
                $shift
                && $shift->status->value === 'OPEN'
                && $transaction->payment_method === PaymentMethod::CASH
            ) {
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

