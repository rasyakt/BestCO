<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->nullable()->unique()->comment('Kode kupon opsional, misal HARDIKNAS');
            $table->string('name', 150)->comment('Nama promo, misal Diskon Ujian Sekolah');
            $table->text('description')->nullable();
            $table->enum('scope', ['ALL_ITEMS', 'CATEGORY', 'PRODUCT', 'SERVICE', 'MIN_SPEND'])->default('ALL_ITEMS');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->enum('discount_type', ['PERCENTAGE', 'FIXED_AMOUNT'])->default('PERCENTAGE');
            $table->decimal('discount_value', 19, 4)->comment('Nilai diskon (persen atau rupiah)');
            $table->decimal('min_order_amount', 19, 4)->default(0.0000)->comment('Syarat minimal belanja kotor');
            $table->decimal('max_discount_amount', 19, 4)->nullable()->comment('Batas maksimal diskon untuk tipe persentase');
            $table->dateTime('start_datetime')->comment('Waktu mulai berlaku');
            $table->dateTime('end_datetime')->comment('Waktu berakhir berlaku');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['is_active', 'start_datetime', 'end_datetime'], 'idx_promotions_active_dates');
            $table->index('scope', 'idx_promotions_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};

