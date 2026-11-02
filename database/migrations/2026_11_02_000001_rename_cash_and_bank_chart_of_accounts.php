<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('chart_of_accounts')
            ->where('account_code', '1-1102')
            ->update(['account_name' => 'Kas di Tangan']);

        DB::table('chart_of_accounts')
            ->where('account_code', '1-1103')
            ->update(['account_name' => 'Kas di Bank']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('chart_of_accounts')
            ->where('account_code', '1-1102')
            ->update(['account_name' => 'Kas Besar Brankas TeFa']);

        DB::table('chart_of_accounts')
            ->where('account_code', '1-1103')
            ->update(['account_name' => 'Bank Operasional / QRIS']);
    }
};

