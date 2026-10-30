<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->unique()->comment('INV-20261030-0001');
            $table->foreignId('pos_shift_id')->constrained('pos_shifts');
            $table->foreignId('cashier_user_id')->constrained('users')->comment('Siswa piket operator');
            $table->string('customer_name', 120)->nullable()->default('Pelanggan Umum');
            $table->decimal('subtotal_retail', 19, 4)->default(0.0000);
            $table->decimal('subtotal_service', 19, 4)->default(0.0000);
            $table->decimal('tier_discount_total', 19, 4)->default(0.0000)->comment('Total potongan harga grosir otomatis');
            $table->decimal('manual_discount_total', 19, 4)->default(0.0000)->comment('Diskon tambahan kasir');
            $table->decimal('grand_total', 19, 4);
            $table->decimal('total_cogs_retail', 19, 4)->default(0.0000)->comment('Total HPP Barang Etalase');
            $table->decimal('total_cogs_service', 19, 4)->default(0.0000)->comment('Total HPP Bahan Baku Jasa (Kertas + Tinta + Laminating)');
            $table->enum('payment_method', ['CASH', 'QRIS', 'TRANSFER'])->default('CASH');
            $table->decimal('amount_paid', 19, 4);
            $table->decimal('change_amount', 19, 4)->default(0.0000);
            $table->enum('status', ['COMPLETED', 'VOIDED'])->default('COMPLETED');
            $table->foreignId('voided_by_teacher_id')->nullable()->constrained('users');
            $table->string('void_reason', 255)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            $table->index(['transaction_date', 'status'], 'idx_trx_date_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
