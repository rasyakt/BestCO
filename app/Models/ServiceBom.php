<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'raw_material_product_id',
    'consumption_qty',
    'is_required',
    'notes',
])]
class ServiceBom extends Model
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
            'consumption_qty' => 'decimal:4',
            'is_required' => 'boolean',
        ];
    }

    /**
     * Cast kuantitas konsumsi ke string untuk perhitungan bcmath.
     */
    protected function consumptionQty(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the service that consumes this raw material.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get the raw material product consumed by the service.
     */
    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'raw_material_product_id');
    }
}
