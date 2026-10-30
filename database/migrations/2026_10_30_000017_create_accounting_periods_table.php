<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('period_name', 50)->comment('Contoh: Oktober 2026');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closing_journal_entry_id')->nullable();
            $table->timestamps();
            $table->unique(['start_date', 'end_date'], 'uq_period_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
