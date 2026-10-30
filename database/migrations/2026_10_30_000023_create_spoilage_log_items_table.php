<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spoilage_log_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('spoilage_log_id')->constrained('spoilage_logs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->comment('Kertas (Lembar), Tinta (Gram), atau Barang Etalase');
            $table->decimal('qty_wasted', 19, 4)->comment('Contoh: 10.0000 Lembar Kertas atau 0.5000 Gram Tinta (10 x 0.05g)');
            $table->decimal('unit_cost_at_spoilage', 19, 4);
            $table->decimal('subtotal_cost', 19, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spoilage_log_items');
    }
};
