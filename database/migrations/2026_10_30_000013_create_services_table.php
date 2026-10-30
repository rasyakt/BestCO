<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique()->comment('SRV-FC-A4, SRV-PRT-BW, SRV-PRT-CL-LIGHT, SRV-PRT-CL-FULL, SRV-LAM-SM, SRV-LAM-LG');
            $table->string('name', 150);
            $table->foreignId('category_id')->constrained('categories');
            $table->enum('service_group', ['FOTOCOPY', 'PRINT', 'LAMINATING', 'BINDING', 'SCAN', 'OTHER']);
            $table->string('base_unit_name', 30)->default('Lembar')->comment('Lembar, Dokumen, Buku, Pcs');
            $table->decimal('base_price', 19, 4)->comment('Harga normal per satuan sebelum diskon grosir');
            $table->foreignId('revenue_account_id')->constrained('chart_of_accounts')->comment('FK ke COA Pendapatan Jasa');
            $table->foreignId('discount_account_id')->constrained('chart_of_accounts')->comment('FK ke COA Potongan Pendapatan Jasa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['service_group', 'is_active'], 'idx_services_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
