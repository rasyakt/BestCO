<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('journal_number', 40)->unique()->comment('JRN-20261030-0001');
            $table->foreignId('accounting_period_id')->constrained('accounting_periods');
            $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts');
            $table->date('entry_date');
            $table->enum('journal_type', ['GENERAL', 'SALES_POS', 'INTERNAL_MUTATION', 'SPOILAGE', 'PURCHASE', 'CASH_DISCREPANCY', 'ADJUSTMENT', 'CLOSING', 'REVERSING']);
            $table->string('reference_type', 60)->nullable()->comment('Polymorphic source model');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255);
            $table->decimal('total_debit', 19, 4);
            $table->decimal('total_credit', 19, 4);
            $table->boolean('is_posted')->default(true);
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversed_by_journal_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();
            $table->index(['entry_date', 'journal_type'], 'idx_je_date_type');
            $table->index(['reference_type', 'reference_id'], 'idx_je_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
