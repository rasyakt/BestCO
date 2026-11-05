<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Developer\SystemLogService;
use App\Services\Developer\SystemStatsService;
use App\Services\Setting\AppIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DeveloperController extends Controller
{
    /**
     * Tampilkan halaman Developer Console (Khusus Superadmin).
     */
    public function index(): View
    {
        $identity = AppIdentityService::get();
        $stats = SystemStatsService::getStats();
        $logs = SystemLogService::getRecentErrors(10);
        $logFileSize = SystemLogService::getFileSizeFormatted();

        return view('developer.index', [
            'identity' => $identity,
            'stats' => $stats,
            'logs' => $logs,
            'logFileSize' => $logFileSize,
        ]);
    }

    /**
     * Perbarui identitas website & branding logo.
     */
    public function updateIdentity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:50'],
            'app_tagline' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:150'],
            'app_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:2048'],
        ], [
            'app_name.required' => 'Nama website wajib diisi.',
            'app_name.max' => 'Nama website maksimal 50 karakter.',
            'app_logo.image' => 'File logo harus berupa gambar.',
            'app_logo.mimes' => 'Format gambar yang didukung: PNG, JPG, JPEG, SVG, WEBP, ICO.',
            'app_logo.max' => 'Ukuran file logo maksimal 2 MB.',
        ]);

        try {
            AppIdentityService::update([
                'app_name' => $validated['app_name'],
                'app_tagline' => $validated['app_tagline'] ?? null,
                'footer_text' => $validated['footer_text'] ?? null,
            ], $request->file('app_logo'));

            return redirect()
                ->route('dev.index')
                ->with('success', 'Identitas & logo website berhasil diperbarui!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui identitas website: ' . $e->getMessage());
        }
    }

    /**
     * Kembalikan logo ke bawaan sistem.
     */
    public function resetLogo(): RedirectResponse
    {
        try {
            AppIdentityService::resetLogo();

            return redirect()
                ->route('dev.index')
                ->with('success', 'Logo website dikembalikan ke logo default sistem.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal mengembalikan logo: ' . $e->getMessage());
        }
    }

    /**
     * Bersihkan / kosongkan file error log.
     */
    public function clearLogs(): RedirectResponse
    {
        try {
            SystemLogService::clear();

            return redirect()
                ->route('dev.index')
                ->with('success', 'File error log berhasil dikosongkan!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal membersihkan log: ' . $e->getMessage());
        }
    }

    /**
     * Unduh file error log saat ini.
     */
    public function downloadLogs(): BinaryFileResponse|RedirectResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath) || filesize($logPath) === 0) {
            return redirect()
                ->route('dev.index')
                ->with('error', 'File log kosong atau tidak ditemukan.');
        }

        $filename = 'laravel-log-' . date('Y-m-d_His') . '.log';

        return response()->download($logPath, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }
}

