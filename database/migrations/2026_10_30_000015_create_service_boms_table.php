<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_boms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('raw_material_product_id')->constrained('products')->comment('FK ke products dengan inventory_type = RAW_MATERIAL');
            $table->decimal('consumption_qty', 19, 4)->comment('Kertas = 1.0000 Lembar, Tinta Fotocopy = 0.0500 Gram (setara 0.00005 Kg)');
            $table->boolean('is_required')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['service_id', 'raw_material_product_id'], 'uq_service_raw_material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_boms');
    }
};
