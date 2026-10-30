<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shift_code',
    'shift_date',
    'supervisor_teacher_id',
    'opened_by_user_id',
    'closed_by_user_id',
    'opening_cash_balance',
    'system_expected_cash',
    'actual_closing_cash',
    'cash_discrepancy',
    'status',
    'closing_notes',
])]
class PosShift extends Model
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
            'shift_date' => 'date',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash_balance' => 'decimal:4',
            'system_expected_cash' => 'decimal:4',
            'actual_closing_cash' => 'decimal:4',
            'cash_discrepancy' => 'decimal:4',
            'status' => ShiftStatus::class,
        ];
    }

    /**
     * Cast saldo kas ke string untuk perhitungan bcmath.
     */
    protected function openingCashBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the teacher supervising this shift.
     */
    public function supervisorTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_teacher_id');
    }

    /**
     * Get the user who opened this shift.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /**
     * Get the user who closed this shift.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * Get the attendance records of this shift.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(ShiftAttendance::class, 'pos_shift_id');
    }

    /**
     * Get the POS transactions processed in this shift.
     */
    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'pos_shift_id');
    }

    /**
     * Get the internal stock mutations logged in this shift.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'pos_shift_id');
    }

    /**
     * Get the spoilage logs logged in this shift.
     */
    public function spoilageLogs(): HasMany
    {
        return $this->hasMany(SpoilageLog::class, 'pos_shift_id');
    }

    /**
     * Get the cash book entries recorded in this shift.
     */
    public function cashBookEntries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class, 'pos_shift_id');
    }

    /**
     * Get the journal entries related to this shift.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'pos_shift_id');
    }
}
