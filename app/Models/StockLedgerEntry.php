<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'pos_shift_id',
    'reference_type',
    'reference_id',
    'qty_change',
    'balance_before',
    'balance_after',
    'unit_cost',
    'total_value',
    'created_by',
])]
class StockLedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'qty_change' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Cast nilai stok ke string untuk perhitungan bcmath.
     */
    protected function qtyChange(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the product this ledger entry tracks.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the POS shift related to this ledger entry.
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the user who created this ledger entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
