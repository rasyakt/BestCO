<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Enums\StockOpnameStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CreateStockOpnameAction
{
    /**
     * Buat sesi stok opname berstatus draf.
     * Stok belum diubah sampai sesi disetujui oleh admin.
     *
     * @param array<int, array{product_id: int|string, counted_qty: int|float|string}> $items
     */
    public function execute(
        array $items,
        string $opnameDate,
        ?string $notes,
        string $createdByUserId,
    ): StockOpname {
        if (empty($items)) {
            throw new \DomainException('Minimal satu barang harus dihitung.');
        }

        return DB::transaction(function () use ($items, $opnameDate, $notes, $createdByUserId): StockOpname {
            $date = Carbon::parse($opnameDate)->startOfDay();

            if ($date->greaterThan(Carbon::today())) {
                throw new \DomainException('Tanggal opname tidak boleh melebihi hari ini.');
            }

            $productIds = array_map(
                static fn (array $item): int => (int) ($item['product_id'] ?? 0),
                $items
            );

            if (count(array_unique($productIds)) !== count($productIds)) {
                throw new \DomainException('Produk yang sama tidak boleh muncul lebih dari satu kali.');
            }

            $products = Product::with('inventoryAccount')
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw new \DomainException('Ada produk yang tidak ditemukan atau sudah tidak aktif.');
            }

            $opname = StockOpname::create([
                'opname_number' => $this->nextOpnameNumber($date),
                'opname_date' => $date->toDateString(),
                'status' => StockOpnameStatus::DRAFT,
                'notes' => $notes,
                'items_count' => count($items),
                'total_shortage_value' => '0.0000',
                'total_overage_value' => '0.0000',
                'created_by_user_id' => $createdByUserId,
            ]);

            $totalShortage = '0.0000';
            $totalOverage = '0.0000';

            foreach ($items as $itemData) {
                /** @var Product $product */
                $product = $products->get((int) $itemData['product_id']);
                $countedQty = $this->normalizeCountedQty((string) $itemData['counted_qty']);
                $systemQty = (string) $product->current_stock;
                $differenceQty = bcsub($countedQty, $systemQty, 4);
                $unitCost = (string) $product->average_cost;
                $differenceValue = bcmul($differenceQty, $unitCost, 4);

                if (bccomp($differenceValue, '0.0000', 4) === -1) {
                    $totalShortage = bcadd($totalShortage, bcmul($differenceValue, '-1', 4), 4);
                } elseif (bccomp($differenceValue, '0.0000', 4) === 1) {
                    $totalOverage = bcadd($totalOverage, $differenceValue, 4);
                }

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $product->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'difference_qty' => $differenceQty,
                    'unit_cost' => $unitCost,
                    'difference_value' => $differenceValue,
                ]);
            }

            $opname->update([
                'total_shortage_value' => $totalShortage,
                'total_overage_value' => $totalOverage,
            ]);

            return $opname->fresh(['items.product.unit', 'createdBy']);
        });
    }

    private function normalizeCountedQty(string $value): string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d{1,9}(\.\d{1,4})?$/', $value)) {
            throw new \DomainException('Kuantitas fisik tidak valid. Gunakan angka 0 atau lebih dengan maksimal 4 desimal.');
        }

        return number_format((float) $value, 4, '.', '');
    }

    private function nextOpnameNumber(Carbon $date): string
    {
        $sequence = StockOpname::whereDate('opname_date', $date->toDateString())->count() + 1;

        do {
            $opnameNumber = 'OPN-' . $date->format('Ymd') . '-'
                . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (StockOpname::where('opname_number', $opnameNumber)->exists());

        return $opnameNumber;
    }
}
