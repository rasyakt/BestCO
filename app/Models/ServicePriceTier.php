<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountTierType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'tier_name',
    'min_qty',
    'max_qty',
    'discount_type',
    'discount_value',
    'priority',
    'is_active',
])]
class ServicePriceTier extends Model
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
            'min_qty' => 'decimal:4',
            'max_qty' => 'decimal:4',
            'discount_type' => DiscountTierType::class,
            'discount_value' => 'decimal:4',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cast nilai diskon ke string untuk perhitungan bcmath.
     */
    protected function discountValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the service that owns this tier.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
