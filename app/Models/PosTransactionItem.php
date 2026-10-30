<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pos_transaction_id',
    'item_type',
    'product_id',
    'service_id',
    'applied_price_tier_id',
    'item_name_snapshot',
    'qty',
    'base_unit_price',
    'tier_discount_per_unit',
    'manual_discount_amount',
    'final_unit_price',
    'line_subtotal_gross',
    'line_total_discount',
    'line_net_total',
    'line_cogs_total',
])]
class PosTransactionItem extends Model
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
            'item_type' => 'string',
            'qty' => 'decimal:4',
            'base_unit_price' => 'decimal:4',
            'tier_discount_per_unit' => 'decimal:4',
            'manual_discount_amount' => 'decimal:4',
            'final_unit_price' => 'decimal:4',
            'line_subtotal_gross' => 'decimal:4',
            'line_total_discount' => 'decimal:4',
            'line_net_total' => 'decimal:4',
            'line_cogs_total' => 'decimal:4',
        ];
    }

    /**
     * Cast subtotal baris ke string untuk perhitungan bcmath.
     */
    protected function lineNetTotal(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the transaction this item belongs to.
     */
    public function posTransaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    /**
     * Get the retail product of this item (if any).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the service of this item (if any).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get the applied price tier (if any).
     */
    public function appliedPriceTier(): BelongsTo
    {
        return $this->belongsTo(ServicePriceTier::class, 'applied_price_tier_id');
    }

    /**
     * Get the BOM consumption records of this item.
     */
    public function boms(): HasMany
    {
        return $this->hasMany(PosTransactionBom::class, 'pos_transaction_item_id');
    }
}
