<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mutation_number',
    'pos_shift_id',
    'unit_conversion_id',
    'source_product_id',
    'target_product_id',
    'source_qty_deducted',
    'conversion_factor',
    'target_qty_added',
    'source_unit_cost',
    'target_unit_cost',
    'total_mutation_value',
    'requested_by_user_id',
    'approved_by_user_id',
    'journal_entry_id',
    'notes',
    'mutated_at',
])]
class StockMutation extends Model
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
            'source_qty_deducted' => 'decimal:4',
            'conversion_factor' => 'decimal:4',
            'target_qty_added' => 'decimal:4',
            'source_unit_cost' => 'decimal:4',
            'target_unit_cost' => 'decimal:4',
            'total_mutation_value' => 'decimal:4',
            'mutated_at' => 'datetime',
        ];
    }

    /**
     * Cast nilai mutasi ke string untuk perhitungan bcmath.
     */
    protected function totalMutationValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the POS shift where this mutation was logged.
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the unit conversion used in this mutation.
     */
    public function unitConversion(): BelongsTo
    {
        return $this->belongsTo(UnitConversion::class, 'unit_conversion_id');
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
     * Get the user who requested the mutation.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Get the user who approved the mutation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the journal entry of this mutation.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
