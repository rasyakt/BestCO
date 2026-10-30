<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bku_number',
    'pos_shift_id',
    'journal_entry_id',
    'cash_account_id',
    'transaction_date',
    'mutation_direction',
    'category',
    'description',
    'debit_in',
    'credit_out',
    'running_balance',
    'recorded_by_user_id',
])]
class CashBookEntry extends Model
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
            'transaction_date' => 'datetime',
            'mutation_direction' => 'string',
            'debit_in' => 'decimal:4',
            'credit_out' => 'decimal:4',
            'running_balance' => 'decimal:4',
        ];
    }

    /**
     * Cast nilai kas ke string untuk perhitungan bcmath.
     */
    protected function debitIn(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function creditOut(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function runningBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the POS shift this entry belongs to (if any).
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the journal entry source of this cash book entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Get the cash/bank account of this entry.
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account_id');
    }

    /**
     * Get the user who recorded this entry.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
