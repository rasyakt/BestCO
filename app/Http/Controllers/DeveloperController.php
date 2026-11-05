<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Developer\DatabaseBackupService;
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
        $backups = DatabaseBackupService::listBackups();

        return view('developer.index', [
            'identity' => $identity,
            'stats' => $stats,
            'logs' => $logs,
            'logFileSize' => $logFileSize,
            'backups' => $backups,
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

    /**
     * Buat backup database baru.
     */
    public function createBackup(Request $request): RedirectResponse
    {
        try {
            $compress = (bool) $request->boolean('compress');
            $filename = DatabaseBackupService::createBackup($compress);

            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('success', "Backup database berhasil dibuat: {$filename}");
        } catch (\Throwable $e) {
            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', 'Gagal membuat backup database: ' . $e->getMessage());
        }
    }

    /**
     * Unduh file backup database.
     */
    public function downloadBackup(string $filename): BinaryFileResponse|RedirectResponse
    {
        $path = DatabaseBackupService::getBackupPath($filename);

        if (! $path || ! file_exists($path)) {
            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', "File backup '{$filename}' tidak ditemukan.");
        }

        $contentType = str_ends_with($filename, '.gz') ? 'application/gzip' : 'application/sql';

        return response()->download($path, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Restore database dari file backup yang tersimpan di server.
     */
    public function restoreBackup(string $filename): RedirectResponse
    {
        try {
            DatabaseBackupService::restoreFromFile($filename);

            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('success', "Database berhasil di-restore dari backup '{$filename}'!");
        } catch (\Throwable $e) {
            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', 'Gagal me-restore database: ' . $e->getMessage());
        }
    }

    /**
     * Restore database dari file .sql / .sql.gz yang diunggah pengguna.
     */
    public function uploadRestoreBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'], // max 100MB
        ], [
            'backup_file.required' => 'Pilih file backup database (.sql atau .sql.gz).',
            'backup_file.file' => 'File yang diunggah tidak valid.',
            'backup_file.max' => 'Ukuran file backup maksimal 100 MB.',
        ]);

        try {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $request->file('backup_file');
            DatabaseBackupService::restoreFromUpload($file);

            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('success', 'Database berhasil di-restore dari file yang diunggah!');
        } catch (\Throwable $e) {
            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', 'Gagal me-restore dari file: ' . $e->getMessage());
        }
    }

    /**
     * Hapus file backup database.
     */
    public function deleteBackup(string $filename): RedirectResponse
    {
        try {
            $deleted = DatabaseBackupService::deleteBackup($filename);

            if ($deleted) {
                return redirect()
                    ->route('dev.index', ['tab' => 'backup'])
                    ->with('success', "File backup '{$filename}' berhasil dihapus.");
            }

            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', "File backup '{$filename}' tidak ditemukan.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('dev.index', ['tab' => 'backup'])
                ->with('error', 'Gagal menghapus file backup: ' . $e->getMessage());
        }
    }
}

