<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $allow_decimal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 */
#[Fillable(['code', 'name', 'allow_decimal'])]
class Unit extends Model
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
            'allow_decimal' => 'boolean',
        ];
    }

    /**
     * Get the products that use this unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    /**
     * Get the unit conversions where this is the source unit.
     */
    public function unitConversionsAsSource(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'source_unit_id');
    }

    /**
     * Get the unit conversions where this is the target unit.
     */
    public function unitConversionsAsTarget(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'target_unit_id');
    }
}
