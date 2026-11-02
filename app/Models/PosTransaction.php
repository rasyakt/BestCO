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

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $pos_shift_id
 * @property int $cashier_user_id
 * @property string $customer_name
 * @property string $subtotal_retail
 * @property string $subtotal_service
 * @property string $tier_discount_total
 * @property string $manual_discount_total
 * @property string $promo_discount_total
 * @property int|null $applied_promotion_id
 * @property string|null $promo_name_snapshot
 * @property string $grand_total
 * @property string $total_cogs_retail
 * @property string $total_cogs_service
 * @property \App\Enums\PaymentMethod $payment_method
 * @property string $amount_paid
 * @property string $change_amount
 * @property string $status
 * @property int|null $voided_by_teacher_id
 * @property string|null $void_reason
 * @property int|null $journal_entry_id
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PosShift $posShift
 * @property-read \App\Models\User $cashier
 * @property-read \App\Models\Promotion|null $promotion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PosTransactionItem> $items
 * @property-read \App\Models\User|null $voidedByTeacher
 * @property-read \App\Models\JournalEntry|null $journalEntry
 */
#[Fillable([
    'invoice_number',
    'pos_shift_id',
    'cashier_user_id',
    'customer_name',
    'subtotal_retail',
    'subtotal_service',
    'tier_discount_total',
    'manual_discount_total',
    'promo_discount_total',
    'applied_promotion_id',
    'promo_name_snapshot',
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
            'promo_discount_total' => 'decimal:4',
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
     * Get the applied promotion (if any).
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'applied_promotion_id');
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
