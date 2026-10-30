<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('shift_code', 30)->unique()->comment('Contoh: SHF-20261030-01');
            $table->date('shift_date');
            $table->foreignId('supervisor_teacher_id')->constrained('users')->comment('Guru piket fleksibel yang bertugas hari itu');
            $table->foreignId('opened_by_user_id')->constrained('users');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash_balance', 19, 4)->default(0.0000);
            $table->decimal('system_expected_cash', 19, 4)->default(0.0000);
            $table->decimal('actual_closing_cash', 19, 4)->nullable();
            $table->decimal('cash_discrepancy', 19, 4)->nullable()->comment('Selisih kas: actual - expected');
            $table->enum('status', ['OPEN', 'CLOSED', 'AUDITED'])->default('OPEN');
            $table->text('closing_notes')->nullable();
            $table->timestamps();
            $table->index(['shift_date', 'status'], 'idx_shifts_date_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};
