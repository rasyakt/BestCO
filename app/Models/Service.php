<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'category_id',
    'service_group',
    'base_unit_name',
    'base_price',
    'revenue_account_id',
    'discount_account_id',
    'is_active',
])]
class Service extends Model
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
            'service_group' => ServiceGroup::class,
            'base_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cast harga ke string untuk perhitungan bcmath.
     */
    protected function basePrice(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the category of the service.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the revenue account (COA Pendapatan Jasa).
     */
    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'revenue_account_id');
    }

    /**
     * Get the discount account (COA Potongan Pendapatan Jasa).
     */
    public function discountAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'discount_account_id');
    }

    /**
     * Get the price tiers of the service.
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ServicePriceTier::class, 'service_id');
    }

    /**
     * Get the bill of materials of the service.
     */
    public function boms(): HasMany
    {
        return $this->hasMany(ServiceBom::class, 'service_id');
    }
}
