<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number',
    'pos_shift_id',
    'cashier_user_id',
    'customer_name',
    'subtotal_retail',
    'subtotal_service',
    'tier_discount_total',
    'manual_discount_total',
    'grand_total',
    'total_cogs_retail',
    'total_cogs_service',
    'payment_method',
    'amount_paid',
    'change_amount',
    'status',
    'voided_by_teacher_id',
    'void_reason',
    'journal_entry_id',
    'transaction_date',
])]
class PosTransaction extends Model
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
            'subtotal_retail' => 'decimal:4',
            'subtotal_service' => 'decimal:4',
            'tier_discount_total' => 'decimal:4',
            'manual_discount_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'total_cogs_retail' => 'decimal:4',
            'total_cogs_service' => 'decimal:4',
            'payment_method' => PaymentMethod::class,
            'amount_paid' => 'decimal:4',
            'change_amount' => 'decimal:4',
            'status' => 'string',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * Cast nilai finansial ke string untuk perhitungan bcmath.
     */
    protected function grandTotal(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the POS shift this transaction belongs to.
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the cashier who processed this transaction.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    /**
     * Get the items of this transaction.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class, 'pos_transaction_id');
    }

    /**
     * Get the BOM consumption records of this transaction's items.
     */
    public function boms(): HasMany
    {
        return $this->hasManyThrough(PosTransactionBom::class, PosTransactionItem::class);
    }

    /**
     * Get the teacher who voided this transaction (if any).
     */
    public function voidedByTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_teacher_id');
    }

    /**
     * Get the journal entry of this transaction.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
