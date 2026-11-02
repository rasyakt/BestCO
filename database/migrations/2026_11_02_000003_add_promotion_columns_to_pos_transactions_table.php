<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table): void {
            $table->decimal('promo_discount_total', 19, 4)->default(0.0000)->after('manual_discount_total')->comment('Total potongan dari promo aktif');
            $table->foreignId('applied_promotion_id')->nullable()->after('promo_discount_total')->constrained('promotions')->nullOnDelete();
            $table->string('promo_name_snapshot', 150)->nullable()->after('applied_promotion_id')->comment('Nama promo saat transaksi terjadi');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table): void {
            $table->dropForeign(['applied_promotion_id']);
            $table->dropColumn(['applied_promotion_id', 'promo_discount_total', 'promo_name_snapshot']);
        });
    }
};

