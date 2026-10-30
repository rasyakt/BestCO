<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 64)->nullable()->unique();
            $table->string('name', 180);
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('unit_id')->constrained('units');
            $table->enum('inventory_type', ['RETAIL', 'RAW_MATERIAL'])->comment('RETAIL=Stok Etalase, RAW_MATERIAL=Bahan Baku Jasa');
            $table->decimal('current_stock', 19, 4)->default(0.0000);
            $table->decimal('min_stock_alert', 19, 4)->default(5.0000);
            $table->decimal('average_cost', 19, 4)->default(0.0000)->comment('HPP Moving Average per Base Unit');
            $table->decimal('last_purchase_price', 19, 4)->default(0.0000);
            $table->decimal('selling_price', 19, 4)->default(0.0000)->comment('Harga jual ritel (0 untuk RAW_MATERIAL murni)');
            $table->foreignId('inventory_account_id')->constrained('chart_of_accounts')->comment('FK ke COA Persediaan Etalase atau Bahan Jasa');
            $table->foreignId('cogs_account_id')->constrained('chart_of_accounts')->comment('FK ke COA HPP Ritel atau HPP Jasa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['inventory_type', 'is_active'], 'idx_products_type_active');
            $table->index('name', 'idx_products_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
