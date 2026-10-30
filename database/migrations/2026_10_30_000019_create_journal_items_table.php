<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
            $table->string('line_description', 255)->nullable();
            $table->decimal('debit', 19, 4)->default(0.0000);
            $table->decimal('credit', 19, 4)->default(0.0000);
            $table->timestamps();
            $table->index('chart_of_account_id', 'idx_ji_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_items');
    }
};
