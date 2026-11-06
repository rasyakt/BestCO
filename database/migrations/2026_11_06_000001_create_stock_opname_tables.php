<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table): void {
            $table->id();
            $table->string('opname_number', 40)->unique()->comment('OPN-20261106-0001');
            $table->date('opname_date');
            $table->enum('status', ['DRAFT', 'APPROVED'])->default('DRAFT');
            $table->string('notes', 255)->nullable();
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('total_shortage_value', 19, 4)->default(0.0000);
            $table->decimal('total_overage_value', 19, 4)->default(0.0000);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'opname_date'], 'idx_opname_status_date');
        });

        Schema::create('stock_opname_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('system_qty', 19, 4);
            $table->decimal('counted_qty', 19, 4);
            $table->decimal('difference_qty', 19, 4)->comment('counted_qty - system_qty; negatif = kurang, positif = lebih');
            $table->decimal('unit_cost', 19, 4);
            $table->decimal('difference_value', 19, 4)->comment('difference_qty x unit_cost; negatif = kerugian');
            $table->string('notes', 150)->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'product_id'], 'uniq_opname_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
