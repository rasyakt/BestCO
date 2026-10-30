<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mutations', function (Blueprint $table): void {
            $table->id();
            $table->string('mutation_number', 40)->unique()->comment('MUT-20261030-0001');
            $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts');
            $table->foreignId('unit_conversion_id')->constrained('unit_conversions');
            $table->foreignId('source_product_id')->constrained('products')->comment('Barang Etalase yang diambil (Rim)');
            $table->foreignId('target_product_id')->constrained('products')->comment('Bahan Baku Jasa yang bertambah (Lembar)');
            $table->decimal('source_qty_deducted', 19, 4)->comment('Contoh: 1.0000 Rim');
            $table->decimal('conversion_factor', 19, 4)->comment('Contoh: 500.0000');
            $table->decimal('target_qty_added', 19, 4)->comment('Contoh: 500.0000 Lembar');
            $table->decimal('source_unit_cost', 19, 4)->comment('HPP per Rim saat mutasi, misal 45000.0000');
            $table->decimal('target_unit_cost', 19, 4)->comment('HPP per Lembar hasil konversi, misal 90.0000');
            $table->decimal('total_mutation_value', 19, 4)->comment('Total nilai buku yang dimutasi');
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->constrained('users')->comment('Guru Piket atau Admin');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->string('notes', 255)->nullable();
            $table->timestamp('mutated_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
