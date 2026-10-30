<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_book_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('bku_number', 40)->unique()->comment('BKU-20261030-0001');
            $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts');
            $table->foreignId('journal_entry_id')->constrained('journal_entries');
            $table->foreignId('cash_account_id')->constrained('chart_of_accounts')->comment('FK ke COA Kas di Tangan / Kas Laci POS / Bank QRIS');
            $table->timestamp('transaction_date')->useCurrent();
            $table->enum('mutation_direction', ['IN', 'OUT'])->columnName('Mutation_direction');
            $table->string('category', 60)->comment('PENJUALAN_POS, MODAL_SHIFT, PEMBELIAN_BAHAN, BEBAN_OPERASIONAL, SETORAN_BANK');
            $table->string('description', 255);
            $table->decimal('debit_in', 19, 4)->default(0.0000);
            $table->decimal('credit_out', 19, 4)->default(0.0000);
            $table->decimal('running_balance', 19, 4)->comment('Saldo berjalan kronologis');
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->timestamps();
            $table->index(['transaction_date', 'cash_account_id'], 'idx_bku_date_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_book_entries');
    }
};
