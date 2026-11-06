<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan akun khusus selisih persediaan hasil stock opname.
     * Akun ini dipakai untuk menjurnal kekurangan (beban) dan kelebihan (pendapatan) stok.
     */
    public function up(): void
    {
        $now = now();

        $accounts = [
            [
                'account_code' => '6-1902',
                'account_name' => 'Beban Selisih Persediaan (Stock Opname)',
                'account_type' => 'EXPENSE',
                'account_subtype' => 'INVENTORY_SHORTAGE',
                'normal_balance' => 'DEBIT',
            ],
            [
                'account_code' => '4-9102',
                'account_name' => 'Pendapatan Selisih Persediaan (Stock Opname)',
                'account_type' => 'REVENUE',
                'account_subtype' => 'INVENTORY_OVERAGE',
                'normal_balance' => 'CREDIT',
            ],
        ];

        foreach ($accounts as $account) {
            if (DB::table('chart_of_accounts')->where('account_code', $account['account_code'])->exists()) {
                continue;
            }

            DB::table('chart_of_accounts')->insert([
                ...$account,
                'parent_id' => null,
                'is_postable' => true,
                'is_system_locked' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('chart_of_accounts')
            ->whereIn('account_code', ['6-1902', '4-9102'])
            ->delete();
    }
};
