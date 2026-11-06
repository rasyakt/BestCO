<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_opname_id',
    'product_id',
    'system_qty',
    'counted_qty',
    'difference_qty',
    'unit_cost',
    'difference_value',
    'notes',
])]
class StockOpnameItem extends Model
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
            'system_qty' => 'decimal:4',
            'counted_qty' => 'decimal:4',
            'difference_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'difference_value' => 'decimal:4',
        ];
    }

    /**
     * Cast kuantitas dan nilai ke string agar aman dipakai bcmath.
     */
    protected function systemQty(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function countedQty(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function differenceQty(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function unitCost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function differenceValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the opname session this item belongs to.
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    /**
     * Get the product being counted.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Apakah hasil hitung lebih besar dari stok sistem.
     */
    public function isOverage(): bool
    {
        return bccomp($this->difference_qty, '0.0000', 4) === 1;
    }

    /**
     * Apakah hasil hitung lebih kecil dari stok sistem.
     */
    public function isShortage(): bool
    {
        return bccomp($this->difference_qty, '0.0000', 4) === -1;
    }
}
