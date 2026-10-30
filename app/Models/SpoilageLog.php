<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'spoilage_number',
    'pos_shift_id',
    'spoilage_source',
    'service_id',
    'failed_output_qty',
    'reason_category',
    'reason_description',
    'total_spoilage_cost',
    'reported_by_user_id',
    'verified_by_user_id',
    'journal_entry_id',
    'logged_at',
])]
class SpoilageLog extends Model
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
            'failed_output_qty' => 'decimal:4',
            'total_spoilage_cost' => 'decimal:4',
            'logged_at' => 'datetime',
        ];
    }

    /**
     * Cast total biaya kerusakan ke string untuk perhitungan bcmath.
     */
    protected function totalSpoilageCost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the POS shift where this spoilage was logged.
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the service related to this spoilage (if production failure).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get the item details of this spoilage log.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SpoilageLogItem::class, 'spoilage_log_id');
    }

    /**
     * Get the user who reported the spoilage.
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * Get the user who verified the spoilage.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Get the journal entry of this spoilage.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
