<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts');
            $table->string('reference_type', 60)->comment('POS_SALE, SERVICE_BOM, INTERNAL_MUTATION_OUT, INTERNAL_MUTATION_IN, SPOILAGE, PURCHASE, OPNAME');
            $table->unsignedBigInteger('reference_id')->comment('ID referensi tabel sumber');
            $table->decimal('qty_change', 19, 4)->comment('Positif untuk masuk, Negatif untuk keluar (presisi 4 desimal)');
            $table->decimal('balance_before', 19, 4);
            $table->decimal('balance_after', 19, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('total_value', 19, 4);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'created_at'], 'idx_sle_prod_created');
            $table->index(['reference_type', 'reference_id'], 'idx_sle_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger_entries');
    }
};
