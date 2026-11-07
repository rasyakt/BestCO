<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Product;

final class MovingAverageCostCalculator
{
    /**
     * Hitung Moving Average Cost baru setelah penambahan stok.
     *
     * Rumus: ((Old Stock * Old Avg Cost) + (Added Qty * Added Unit Cost)) / (Old Stock + Added Qty)
     */
    public function calculate(Product $product, string $addedQty, string $addedUnitCost): string
    {
        $oldStock = (string) $product->current_stock;
        $oldAvgCost = (string) $product->average_cost;

        if (bccomp($oldStock, '0.0000', 4) <= 0) {
            return bccomp($addedQty, '0.0000', 4) > 0 ? $addedUnitCost : '0.0000';
        }

        $oldValue = bcmul($oldStock, $oldAvgCost, 4);
        $addedValue = bcmul($addedQty, $addedUnitCost, 4);
        $newStock = bcadd($oldStock, $addedQty, 4);

        if (bccomp($newStock, '0.0000', 4) <= 0) {
            return $addedUnitCost;
        }

        return bcdiv(bcadd($oldValue, $addedValue, 4), $newStock, 4);
    }

    /**
     * Hitung total nilai stok saat ini berdasarkan Moving Average Cost.
     */
    public function totalStockValue(Product $product): string
    {
        return bcmul((string) $product->current_stock, (string) $product->average_cost, 4);
    }
}
