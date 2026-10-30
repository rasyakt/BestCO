<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
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
        return redirect()->route('pos.terminal');
    });

    // Manajemen Shift Kasir & Presensi Piket
    Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/shift/open', [ShiftController::class, 'open'])->name('shift.open');
    Route::post('/shift/close', [ShiftController::class, 'close'])->name('shift.close');

    // Terminal POS Kasir (Diproteksi Shift Gate)
    Route::get('/pos', PosTerminal::class)
        ->middleware('shift.active')
        ->name('pos.terminal');

    // Laporan Akuntansi & BKU
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
