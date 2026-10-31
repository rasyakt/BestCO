<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        // 1. Metrik Penjualan Hari Ini
        $todaySalesTotal = PosTransaction::whereDate('transaction_date', $today)
            ->where('status', 'COMPLETED')
            ->sum('grand_total');

        $todayTrxCount = PosTransaction::whereDate('transaction_date', $today)
            ->where('status', 'COMPLETED')
            ->count();

        // 2. Shift Aktif Hari Ini
        $activeShift = PosShift::with(['supervisorTeacher', 'openedBy', 'attendances.activeOperator'])
            ->where('status', 'OPEN')
            ->whereDate('shift_date', $today)
            ->latest('opened_at')
            ->first();

        // 3. Peringatan Stok Kritis (Low Stock Alerts)
        $lowStockProducts = Product::with('unit')
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereColumn('current_stock', '<=', 'min_stock_alert')
                    ->orWhere('current_stock', '<=', 10.0000);
            })
            ->orderBy('current_stock', 'asc')
            ->limit(6)
            ->get();

        // 4. Transaksi Terakhir
        $recentTransactions = PosTransaction::with(['cashier', 'items'])
            ->latest('id')
            ->limit(6)
            ->get();

        // 5. Tren Penjualan 7 Hari Terakhir
        $salesTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $total = PosTransaction::whereDate('transaction_date', $date)
                ->where('status', 'COMPLETED')
                ->sum('grand_total');

            $salesTrend[] = [
                'day' => $date->locale('id')->isoFormat('ddd'),
                'date_short' => $date->format('d/m'),
                'total' => (float) $total,
            ];
        }

        return view('dashboard.index', compact(
            'todaySalesTotal',
            'todayTrxCount',
            'activeShift',
            'lowStockProducts',
            'recentTransactions',
            'salesTrend'
        ));
    }
}
