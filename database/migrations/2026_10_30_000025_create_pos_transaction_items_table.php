<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->enum('item_type', ['RETAIL_PRODUCT', 'SERVICE']);
            $table->foreignId('product_id')->nullable()->constrained('products')->comment('Terisi jika item_type = RETAIL_PRODUCT');
            $table->foreignId('service_id')->nullable()->constrained('services')->comment('Terisi jika item_type = SERVICE');
            $table->foreignId('applied_price_tier_id')->nullable()->constrained('service_price_tiers')->comment('Terisi jika mendapat harga grosir fotocopy >= 100 lbr');
            $table->string('item_name_snapshot', 180);
            $table->decimal('qty', 19, 4);
            $table->decimal('base_unit_price', 19, 4)->comment('Harga asli per unit sebelum diskon tier');
            $table->decimal('tier_discount_per_unit', 19, 4)->default(0.0000);
            $table->decimal('manual_discount_amount', 19, 4)->default(0.0000);
            $table->decimal('final_unit_price', 19, 4);
            $table->decimal('line_subtotal_gross', 19, 4)->comment('qty * base_unit_price');
            $table->decimal('line_total_discount', 19, 4)->comment('(qty * tier_discount_per_unit) + manual_discount_amount');
            $table->decimal('line_net_total', 19, 4)->comment('line_subtotal_gross - line_total_discount');
            $table->decimal('line_cogs_total', 19, 4)->default(0.0000)->comment('HPP item ritel atau akumulasi BoM jasa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
    }
};
