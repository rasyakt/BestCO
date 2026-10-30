<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('account_code', 20)->unique()->comment('Contoh: 1-1101, 1-1301, 4-1001, 5-2001');
            $table->string('account_name', 150);
            $table->enum('account_type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'COGS', 'EXPENSE']);
            $table->string('account_subtype', 60)->comment('CASH_BANK, INVENTORY_RETAIL, INVENTORY_RAW, OPERATING_REVENUE, SPOILAGE_EXPENSE, dll.');
            $table->enum('normal_balance', ['DEBIT', 'CREDIT']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts');
            $table->boolean('is_postable')->default(true)->comment('FALSE untuk akun Header/Induk');
            $table->boolean('is_system_locked')->default(false);
            $table->decimal('opening_balance', 19, 4)->default(0.0000);
            $table->decimal('current_balance', 19, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['account_type', 'is_active'], 'idx_coa_type_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
