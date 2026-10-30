<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_boms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_transaction_item_id')->constrained('pos_transaction_items')->cascadeOnDelete();
            $table->foreignId('raw_material_product_id')->constrained('products');
            $table->decimal('qty_consumed', 19, 4)->comment('Contoh: 150.0000 Lembar Kertas atau 7.5000 Gram Tinta (150 * 0.05g)');
            $table->decimal('unit_cost_snapshot', 19, 4);
            $table->decimal('total_bom_cost', 19, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_boms');
    }
};
