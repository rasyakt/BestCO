<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('tier_name', 100)->comment('Contoh: Grosir >= 100 Lembar');
            $table->decimal('min_qty', 19, 4)->comment('Contoh: 100.0000');
            $table->decimal('max_qty', 19, 4)->nullable()->comment('NULL berarti tak terbatas ke atas');
            $table->enum('discount_type', ['PERCENTAGE', 'FIXED_DISCOUNT_PER_UNIT', 'SPECIAL_UNIT_PRICE']);
            $table->decimal('discount_value', 19, 4)->comment('Jika PERCENTAGE: 10.0000 (10%), Jika FIXED: potongan Rp/unit, Jika SPECIAL: harga baru Rp/unit');
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['service_id', 'is_active', 'min_qty'], 'idx_tier_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_price_tiers');
    }
};
