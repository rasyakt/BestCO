<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property \App\Enums\PromotionScope $scope
 * @property int|null $category_id
 * @property int|null $product_id
 * @property int|null $service_id
 * @property \App\Enums\PromotionDiscountType $discount_type
 * @property string $discount_value
 * @property string $min_order_amount
 * @property string|null $max_discount_amount
 * @property \Illuminate\Support\Carbon $start_datetime
 * @property \Illuminate\Support\Carbon $end_datetime
 * @property bool $is_active
 * @property int $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Service|null $service
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PosTransaction> $transactions
 */
#[Fillable([
    'code',
    'name',
    'description',
    'scope',
    'category_id',
    'product_id',
    'service_id',
    'discount_type',
    'discount_value',
    'min_order_amount',
    'max_discount_amount',
    'start_datetime',
    'end_datetime',
    'is_active',
    'created_by_user_id',
])]
class Promotion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scope' => PromotionScope::class,
            'discount_type' => PromotionDiscountType::class,
            'discount_value' => 'decimal:4',
            'min_order_amount' => 'decimal:4',
            'max_discount_amount' => 'decimal:4',
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'applied_promotion_id');
    }

    /**
     * Scope untuk promo yang sedang aktif dan berlaku saat ini.
     */
    public function scopeActiveNow(Builder $query, ?Carbon $now = null): Builder
    {
        $time = $now ?? Carbon::now();

        return $query->where('is_active', true)
            ->where('start_datetime', '<=', $time)
            ->where('end_datetime', '>=', $time);
    }

    public function isCurrentlyActive(?Carbon $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $time = $now ?? Carbon::now();

        return $time->gte($this->start_datetime) && $time->lte($this->end_datetime);
    }

    public function isScheduled(?Carbon $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $time = $now ?? Carbon::now();

        return $time->lt($this->start_datetime);
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $time = $now ?? Carbon::now();

        return $time->gt($this->end_datetime);
    }
}

