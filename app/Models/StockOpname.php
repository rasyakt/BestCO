<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockOpnameStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'opname_number',
    'opname_date',
    'status',
    'notes',
    'items_count',
    'total_shortage_value',
    'total_overage_value',
    'created_by_user_id',
    'approved_by_user_id',
    'journal_entry_id',
    'approved_at',
])]
class StockOpname extends Model
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
            'opname_date' => 'date',
            'status' => StockOpnameStatus::class,
            'items_count' => 'integer',
            'total_shortage_value' => 'decimal:4',
            'total_overage_value' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Cast nilai selisih ke string untuk perhitungan bcmath.
     */
    protected function totalShortageValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function totalOverageValue(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the counted items of this opname session.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class, 'stock_opname_id');
    }

    /**
     * Get the user who created the opname session.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who approved the opname session.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the journal entry that posted the stock adjustment.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Apakah sesi ini masih berupa draf dan belum mengubah stok.
     */
    public function isDraft(): bool
    {
        return $this->status === StockOpnameStatus::DRAFT;
    }
}
