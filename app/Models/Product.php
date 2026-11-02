<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property int $category_id
 * @property int $unit_id
 * @property \App\Enums\InventoryType $inventory_type
 * @property string $current_stock
 * @property string $min_stock_alert
 * @property string $average_cost
 * @property string $last_purchase_price
 * @property string $selling_price
 * @property int $inventory_account_id
 * @property int $cogs_account_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Unit|null $unit
 * @property-read \App\Models\ChartOfAccount|null $inventoryAccount
 * @property-read \App\Models\ChartOfAccount|null $cogsAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockLedgerEntry> $stockLedgerEntries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceBom> $asServiceBom
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceBom> $asRawMaterialBom
 */
#[Fillable([
    'sku',
    'barcode',
    'name',
    'category_id',
    'unit_id',
    'inventory_type',
    'current_stock',
    'min_stock_alert',
    'average_cost',
    'last_purchase_price',
    'selling_price',
    'inventory_account_id',
    'cogs_account_id',
    'is_active',
])]
class Product extends Model
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
            'inventory_type' => InventoryType::class,
            'current_stock' => 'decimal:4',
            'min_stock_alert' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_purchase_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cast stok dan biaya ke string untuk perhitungan bcmath.
     */
    protected function currentStock(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    protected function averageCost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => number_format((float) $value, 4, '.', ''),
        );
    }

    /**
     * Get the category of the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the unit of measure of the product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the inventory account (COA Persediaan).
     */
    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'inventory_account_id');
    }

    /**
     * Get the COGS account (COA HPP).
     */
    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cogs_account_id');
    }

    /**
     * Get the unit conversions where this product is the source (retail).
     */
    public function unitConversionsAsSource(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'source_product_id');
    }

    /**
     * Get the unit conversions where this product is the target (raw material).
     */
    public function unitConversionsAsTarget(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'target_product_id');
    }

    /**
     * Get the service BOMs where this product is the raw material.
     */
    public function serviceBoms(): HasMany
    {
        return $this->hasMany(ServiceBom::class, 'raw_material_product_id');
    }

    /**
     * Get the stock ledger entries tracking this product.
     */
    public function stockLedgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class, 'product_id');
    }

    /**
     * Get the POS transaction BOMs consuming this raw material.
     */
    public function posTransactionBoms(): HasMany
    {
        return $this->hasMany(PosTransactionBom::class, 'raw_material_product_id');
    }

    /**
     * Get the spoilage log items referencing this product.
     */
    public function spoilageLogItems(): HasMany
    {
        return $this->hasMany(SpoilageLogItem::class, 'product_id');
    }

    /**
     * Get the stock mutations where this is the source product.
     */
    public function stockMutationsAsSource(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'source_product_id');
    }

    /**
     * Get the stock mutations where this is the target product.
     */
    public function stockMutationsAsTarget(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'target_product_id');
    }

    /**
     * Cek apakah stok saat ini di bawah batas peringatan.
     */
    public function isBelowMinStock(): bool
    {
        return \bccomp($this->current_stock, $this->min_stock_alert, 4) === -1;
    }

    /**
     * Cek apakah produk mencukupi stok untuk kuantitas yang diminta.
     */
    public function hasStock(string $qty): bool
    {
        return \bccomp($this->current_stock, $qty, 4) >= 0;
    }
}
