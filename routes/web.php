<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PiketAttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SpoilageController;
use App\Http\Controllers\StockMutationController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TransactionController;
use App\Livewire\Pos\PosTerminal;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Executive Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Manajemen Shift Kasir & Presensi Piket
    Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/shift/open', [ShiftController::class, 'open'])->name('shift.open');
    Route::post('/shift/close', [ShiftController::class, 'close'])->name('shift.close');
    Route::get('/piket', [PiketAttendanceController::class, 'index'])->name('piket.index');

    // Terminal POS Kasir (Diproteksi Shift Gate)
    Route::get('/pos', PosTerminal::class)
        ->middleware('shift.active')
        ->name('pos.terminal');

    // Riwayat Transaksi Kasir POS & Void
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])->name('transactions.void');

    // Manajemen Dual-Inventory, Restock Pembelian, Mutasi Internal, & Spoilage
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/restock', [InventoryController::class, 'restock'])->name('inventory.restock');
    Route::get('/inventory/mutations', [StockMutationController::class, 'index'])->name('inventory.mutations');
    Route::get('/inventory/spoilage', [SpoilageController::class, 'index'])->name('inventory.spoilage');
    Route::get('/inventory/{id}/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');

    // Laporan Akuntansi & BKU
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Kustomisasi Tema Dinamis
    Route::get('/settings/theme', [ThemeController::class, 'index'])->name('theme.index');
    Route::post('/settings/theme/preset', [ThemeController::class, 'applyPreset'])->name('theme.preset');
    Route::post('/settings/theme/reset', [ThemeController::class, 'reset'])->name('theme.reset');
});

