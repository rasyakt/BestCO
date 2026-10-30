<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'spoilage_log_id',
    'product_id',
    'qty_wasted',
    'unit_cost_at_spoilage',
    'subtotal_cost',
])]
class SpoilageLogItem extends Model
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
            'qty_wasted' => 'decimal:4',
            'unit_cost_at_spoilage' => 'decimal:4',
            'subtotal_cost' => 'decimal:4',
        ];
    }

    /**
     * Cast subtotal biaya ke string untuk perhitungan bcmath.
     */
    protected function subtotalCost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the spoilage log this item belongs to.
     */
    public function spoilageLog(): BelongsTo
    {
        return $this->belongsTo(SpoilageLog::class, 'spoilage_log_id');
    }

    /**
     * Get the product wasted in this spoilage item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
