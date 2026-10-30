<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JournalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'journal_number',
    'accounting_period_id',
    'pos_shift_id',
    'entry_date',
    'journal_type',
    'reference_type',
    'reference_id',
    'description',
    'total_debit',
    'total_credit',
    'is_posted',
    'is_reversed',
    'reversed_by_journal_id',
    'created_by_user_id',
    'posted_at',
])]
class JournalEntry extends Model
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
            'entry_date' => 'date',
            'journal_type' => JournalType::class,
            'reference_id' => 'integer',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
            'is_posted' => 'boolean',
            'is_reversed' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Cast total debit/kredit ke string untuk perhitungan bcmath.
     */
    protected function totalDebit(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function totalCredit(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the accounting period this entry belongs to.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    /**
     * Get the POS shift related to this entry (if any).
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the line items of this journal entry.
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'journal_entry_id');
    }

    /**
     * Get the user who created this entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the journal entry that reversed this entry (if any).
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_journal_id');
    }

    /**
     * Get the journal entries that reverse this entry.
     */
    public function reversals(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversed_by_journal_id');
    }
}
