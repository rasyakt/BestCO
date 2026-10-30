<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['period_name', 'start_date', 'end_date', 'is_closed', 'closed_by_user_id', 'closed_at', 'closing_journal_entry_id'])]
class AccountingPeriod extends Model
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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Get the user who closed this period.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * Get the closing journal entry of this period.
     */
    public function closingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }
}
