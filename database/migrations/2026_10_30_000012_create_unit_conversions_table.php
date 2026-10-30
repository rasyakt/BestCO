<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_product_id')->constrained('products')->comment('Produk Etalase (misal: Kertas HVS A4 75gr [Rim])');
            $table->foreignId('target_product_id')->constrained('products')->comment('Produk Bahan Jasa (misal: Kertas HVS A4 [Lembar])');
            $table->foreignId('source_unit_id')->constrained('units');
            $table->foreignId('target_unit_id')->constrained('units');
            $table->decimal('conversion_factor', 19, 4)->comment('Contoh: 500.0000 (1 Rim = 500 Lembar) atau 1000.0000 (1 Kg = 1000 Gram)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['source_product_id', 'target_product_id'], 'uq_source_target_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
