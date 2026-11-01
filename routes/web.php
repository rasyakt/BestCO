<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PiketAttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SpoilageController;
use App\Http\Controllers\StockInboundController;
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
        if (auth()->user()?->hasRole('piket_siswa')) {
            return redirect()->route('pos.terminal');
        }

        return redirect()->route('dashboard');
    });

    // Executive Dashboard (Superadmin, Admin, Piket Guru)
    Route::get('/dashboard', function () {
        if (auth()->user()?->hasRole('piket_siswa')) {
            return redirect()->route('pos.terminal');
        }

        return app(\App\Http\Controllers\DashboardController::class)->index();
    })->middleware('role:superadmin,admin,piket_guru,piket_siswa')->name('dashboard');

    // Manajemen Shift Kasir & Presensi Piket
    Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/shift/open', [ShiftController::class, 'open'])->name('shift.open');
    Route::post('/shift/close', [ShiftController::class, 'close'])->name('shift.close');
    Route::get('/piket', [PiketAttendanceController::class, 'index'])
        ->middleware('role:superadmin,admin,piket_guru')
        ->name('piket.index');

    // Terminal POS Kasir (Diproteksi Shift Gate)
    Route::get('/pos', PosTerminal::class)
        ->middleware('shift.active')
        ->name('pos.terminal');

    // Riwayat Transaksi Kasir POS & Void
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])->name('transactions.void');

    // Manajemen Dual-Inventory, Restock Pembelian, Mutasi Internal, & Spoilage
    Route::middleware('role:superadmin,admin,piket_guru')->group(function (): void {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/products', [InventoryController::class, 'storeProduct'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.products.store');
        Route::put('/inventory/products/{id}', [InventoryController::class, 'updateProduct'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.products.update');
        Route::post('/inventory/services', [InventoryController::class, 'storeService'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.services.store');
        Route::put('/inventory/services/{id}', [InventoryController::class, 'updateService'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.services.update');
        Route::get('/inventory/inbound', [StockInboundController::class, 'index'])->name('inventory.inbound');
        Route::post('/inventory/inbound', [StockInboundController::class, 'store'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.inbound.store');
        Route::post('/inventory/restock', [InventoryController::class, 'restock'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.restock');
        Route::get('/inventory/mutations', [StockMutationController::class, 'index'])->name('inventory.mutations');
        Route::get('/inventory/spoilage', [SpoilageController::class, 'index'])->name('inventory.spoilage');
        Route::get('/inventory/{id}/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');
    });

    // Laporan Akuntansi & BKU
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role:superadmin,admin,piket_guru')
        ->name('reports.index');

    // Kustomisasi Tema Dinamis (Khusus Superadmin)
    Route::middleware('role:superadmin')->group(function (): void {
        Route::get('/settings/theme', [ThemeController::class, 'index'])->name('theme.index');
        Route::post('/settings/theme/preset', [ThemeController::class, 'applyPreset'])->name('theme.preset');
        Route::get('/settings/theme/preview', [ThemeController::class, 'preview'])->name('theme.preview');
        Route::post('/settings/theme/custom', [ThemeController::class, 'applyCustom'])->name('theme.custom');
        Route::post('/settings/theme/reset', [ThemeController::class, 'reset'])->name('theme.reset');
    });
});

