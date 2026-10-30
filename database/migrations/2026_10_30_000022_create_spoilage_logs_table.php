<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spoilage_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('spoilage_number', 40)->unique()->comment('SPL-20261030-0001');
            $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts');
            $table->enum('spoilage_source', ['SERVICE_PRODUCTION', 'RETAIL_DAMAGE', 'RAW_MATERIAL_DAMAGE']);
            $table->foreignId('service_id')->nullable()->constrained('services')->comment('Diisi jika gagal fotocopy/print/laminating');
            $table->decimal('failed_output_qty', 19, 4)->default(0.0000)->comment('Jumlah lembar gagal fotocopy/print');
            $table->enum('reason_category', ['PAPER_JAM', 'STREAKED_PRINT', 'MISPRINT_OPERATOR', 'TONER_SMEAR', 'LAMINATING_WRINKLE', 'PHYSICAL_DAMAGE', 'OTHER']);
            $table->string('reason_description', 255);
            $table->decimal('total_spoilage_cost', 19, 4)->default(0.0000)->comment('Akumulasi HPP kertas + tinta 0.05g/lembar yang terbuang');
            $table->foreignId('reported_by_user_id')->constrained('users');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->comment('Guru Piket / Admin');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spoilage_logs');
    }
};
