<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $account_code
 * @property string $account_name
 * @property \App\Enums\AccountType $account_type
 * @property string|null $account_subtype
 * @property \App\Enums\NormalBalance $normal_balance
 * @property int|null $parent_id
 * @property bool $is_postable
 * @property bool $is_system_locked
 * @property string $opening_balance
 * @property string $current_balance
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChartOfAccount|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChartOfAccount> $children
 */
#[Fillable([
    'account_code',
    'account_name',
    'account_type',
    'account_subtype',
    'normal_balance',
    'parent_id',
    'is_postable',
    'is_system_locked',
    'opening_balance',
    'current_balance',
    'is_active',
])]
class ChartOfAccount extends Model
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
            'account_type' => AccountType::class,
            'account_subtype' => 'string',
            'normal_balance' => NormalBalance::class,
            'is_postable' => 'boolean',
            'is_system_locked' => 'boolean',
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cast saldo ke string untuk perhitungan bcmath.
     */
    protected function currentBalance(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the parent account (if any).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Get the child accounts (if any).
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Get the journal items posted to this account.
     */
    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'chart_of_account_id');
    }

    /**
     * Get the products using this account as inventory account.
     */
    public function productsAsInventory(): HasMany
    {
        return $this->hasMany(Product::class, 'inventory_account_id');
    }

    /**
     * Get the products using this account as COGS account.
     */
    public function productsAsCogs(): HasMany
    {
        return $this->hasMany(Product::class, 'cogs_account_id');
    }

    /**
     * Get the services using this account as revenue account.
     */
    public function servicesAsRevenue(): HasMany
    {
        return $this->hasMany(Service::class, 'revenue_account_id');
    }

    /**
     * Get the services using this account as discount account.
     */
    public function servicesAsDiscount(): HasMany
    {
        return $this->hasMany(Service::class, 'discount_account_id');
    }

    /**
     * Get the cash book entries using this account as cash account.
     */
    public function cashBookEntries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class, 'cash_account_id');
    }

    /**
     * Cek apakah akun ini merupakan akun kas/bank.
     */
    public function isCashBank(): bool
    {
        return $this->account_subtype === 'CASH_BANK';
    }
}
