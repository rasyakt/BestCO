<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pos_transaction_item_id',
    'raw_material_product_id',
    'qty_consumed',
    'unit_cost_snapshot',
    'total_bom_cost',
])]
class PosTransactionBom extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_consumed' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
            'total_bom_cost' => 'decimal:4',
        ];
    }

    /**
     * Cast total biaya BOM ke string untuk perhitungan bcmath.
     */
    protected function totalBomCost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the transaction item this BOM record belongs to.
     */
    public function posTransactionItem(): BelongsTo
    {
        return $this->belongsTo(PosTransactionItem::class, 'pos_transaction_item_id');
    }

    /**
     * Get the raw material product consumed.
     */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_product_id');
    }
}
