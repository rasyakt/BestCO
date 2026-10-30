<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_product_id',
    'target_product_id',
    'source_unit_id',
    'target_unit_id',
    'conversion_factor',
    'is_active',
])]
class UnitConversion extends Model
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
            'conversion_factor' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cast konversi ke string untuk perhitungan bcmath.
     */
    protected function conversionFactor(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the source product (retail/etalase).
     */
    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    /**
     * Get the target product (bahan baku jasa).
     */
    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    /**
     * Get the source unit.
     */
    public function sourceUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'source_unit_id');
    }

    /**
     * Get the target unit.
     */
    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'target_unit_id');
    }
}
