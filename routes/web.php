<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\FeatureSettingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PiketAttendanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\QrisSettingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SpoilageController;
use App\Http\Controllers\StockInboundController;
use App\Http\Controllers\StockMutationController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Livewire\Pos\PosTerminal;
use App\Services\Feature\FeatureManager;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Authenticated Routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profil & Keamanan Akun
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/', function () {
        $user = auth()->user();
        $featureManager = app(FeatureManager::class);

        if ($user && $featureManager->canAccess('dashboard', $user)) {
            return redirect()->route('dashboard');
        }

        if ($user && $featureManager->canAccess('pos', $user)) {
            return redirect()->route('pos.terminal');
        }

        return redirect()->route('dashboard');
    });

    // Executive Dashboard (Dinamis via Fitur Dashboard)
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $featureManager = app(FeatureManager::class);

        if ($user && ! $featureManager->canAccess('dashboard', $user)) {
            if ($featureManager->canAccess('pos', $user)) {
                return redirect()->route('pos.terminal');
            }
            abort(403, 'Akses ditolak: Fitur Dashboard dinonaktifkan untuk peran Anda.');
        }

        return app(DashboardController::class)->index();
    })->middleware('feature:dashboard')->name('dashboard');

    // Manajemen Shift Kasir & Presensi Piket
    Route::middleware('feature:shift')->group(function (): void {
        Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
        Route::post('/shift/open', [ShiftController::class, 'open'])
            ->middleware('role:superadmin,admin,piket_guru')
            ->name('shift.open');
        Route::post('/shift/close', [ShiftController::class, 'close'])
            ->middleware('role:superadmin,admin,piket_guru')
            ->name('shift.close');
    });

    Route::get('/piket', [PiketAttendanceController::class, 'index'])
        ->middleware('feature:piket')
        ->name('piket.index');

    // Terminal POS Kasir (Diproteksi Shift Gate & Status Fitur Kasir)
    Route::get('/pos', PosTerminal::class)
        ->middleware(['shift.active', 'feature:pos'])
        ->name('pos.terminal');

    // Riwayat Transaksi Kasir POS & Void
    Route::middleware('feature:transactions')->group(function (): void {
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])
            ->middleware('role:superadmin,admin,piket_guru')
            ->name('transactions.void');
    });

    // Manajemen Promo & Diskon Berjadwal (Superadmin, Admin, Piket Guru / Sesuai Pengaturan Fitur)
    Route::middleware('feature:promotions')->group(function (): void {
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::get('/promotions/create', [PromotionController::class, 'create'])->name('promotions.create');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::get('/promotions/{promotion}/edit', [PromotionController::class, 'edit'])->name('promotions.edit');
        Route::put('/promotions/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::patch('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');
        Route::delete('/promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
    });

    // Manajemen Dual-Inventory, Restock Pembelian, Mutasi Internal, & Spoilage
    Route::middleware('feature:inventory')->group(function (): void {
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
        Route::post('/inventory/restock', [InventoryController::class, 'restock'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.restock');
        Route::get('/inventory/{id}/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');
    });

    // Barang Masuk (Inbound)
    Route::middleware('feature:inventory_inbound')->group(function (): void {
        Route::get('/inventory/inbound', [StockInboundController::class, 'index'])->name('inventory.inbound');
        Route::post('/inventory/inbound', [StockInboundController::class, 'store'])
            ->middleware('role:superadmin,admin')
            ->name('inventory.inbound.store');
    });

    // Pindah Stok (Mutasi)
    Route::middleware('feature:inventory_mutations')->group(function (): void {
        Route::get('/inventory/mutations', [StockMutationController::class, 'index'])->name('inventory.mutations');
    });

    // Barang Rusak (Spoilage)
    Route::middleware('feature:inventory_spoilage')->group(function (): void {
        Route::get('/inventory/spoilage', [SpoilageController::class, 'index'])->name('inventory.spoilage');
    });

    // Laporan Akuntansi & BKU
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('feature:reports')
        ->name('reports.index');

    // Kelola Pengguna (dibatasi juga oleh hierarki peran di controller)
    Route::middleware('feature:users')->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    });

    // Kustomisasi Tema Dinamis
    Route::middleware('feature:theme')->group(function (): void {
        Route::get('/settings/theme', [ThemeController::class, 'index'])->name('theme.index');
        Route::post('/settings/theme/preset', [ThemeController::class, 'applyPreset'])->name('theme.preset');
        Route::get('/settings/theme/preview', [ThemeController::class, 'preview'])->name('theme.preview');
        Route::post('/settings/theme/custom', [ThemeController::class, 'applyCustom'])->name('theme.custom');
        Route::post('/settings/theme/reset', [ThemeController::class, 'reset'])->name('theme.reset');
    });

    // Pengaturan QRIS
    Route::middleware('feature:settings_qris')->group(function (): void {
        Route::get('/settings/qris', [QrisSettingController::class, 'index'])->name('settings.qris');
        Route::post('/settings/qris/upload', [QrisSettingController::class, 'upload'])->name('settings.qris.upload');
        Route::delete('/settings/qris', [QrisSettingController::class, 'delete'])->name('settings.qris.delete');
    });

    // Pengaturan Toko
    Route::middleware('feature:settings_store')->group(function (): void {
        Route::get('/settings/store', [StoreSettingController::class, 'index'])->name('settings.store');
        Route::put('/settings/store', [StoreSettingController::class, 'update'])->name('settings.store.update');
        Route::delete('/settings/store/reset', [StoreSettingController::class, 'reset'])->name('settings.store.reset');
    });

    // PENGATURAN FITUR (KHUSUS SUPERADMIN)
    Route::middleware('role:superadmin')->group(function (): void {
        Route::get('/settings/features', [FeatureSettingController::class, 'index'])->name('settings.features');
        Route::put('/settings/features', [FeatureSettingController::class, 'update'])->name('settings.features.update');
        Route::post('/settings/features/reset', [FeatureSettingController::class, 'reset'])->name('settings.features.reset');
        Route::post('/settings/features/enable-all', [FeatureSettingController::class, 'enableAll'])->name('settings.features.enableAll');

        // DEVELOPER CONSOLE (KHUSUS SUPERADMIN MURNI - BEBAS DARI MATRIKS FEATURE)
        Route::get('/developer', [DeveloperController::class, 'index'])->name('dev.index');
        Route::put('/developer/identity', [DeveloperController::class, 'updateIdentity'])->name('dev.identity.update');
        Route::delete('/developer/identity/logo', [DeveloperController::class, 'resetLogo'])->name('dev.identity.resetLogo');
        Route::delete('/developer/logs', [DeveloperController::class, 'clearLogs'])->name('dev.logs.clear');
        Route::get('/developer/logs/download', [DeveloperController::class, 'downloadLogs'])->name('dev.logs.download');
    });
});
