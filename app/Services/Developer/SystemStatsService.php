<?php

declare(strict_types=1);

namespace App\Services\Developer;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SystemStatsService
{
    /**
     * Ambil data statistik sistem lengkap.
     *
     * @return array<string, mixed>
     */
    public static function getStats(): array
    {
        return [
            'runtime' => self::getRuntimeStats(),
            'server' => self::getServerStats(),
            'database' => self::getDatabaseStats(),
            'app_data' => self::getAppDataStats(),
            'drivers' => self::getDriversStats(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function getRuntimeStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => (bool) config('app.debug'),
            'app_url' => (string) config('app.url'),
            'timezone' => (string) config('app.timezone'),
            'server_time' => now()->format('d M Y H:i:s T'),
            'locale' => (string) config('app.locale'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function getServerStats(): array
    {
        $basePath = base_path();
        $freeDisk = @disk_free_space($basePath);
        $totalDisk = @disk_total_space($basePath);

        $diskUsedPercent = ($freeDisk !== false && $totalDisk !== false && $totalDisk > 0)
            ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1)
            : null;

        $memUsage = memory_get_usage(true);
        $peakMem = memory_get_peak_usage(true);

        return [
            'os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP ' . PHP_VERSION . ' Development Server',
            'memory_usage' => self::formatBytes($memUsage),
            'peak_memory' => self::formatBytes($peakMem),
            'memory_limit' => ini_get('memory_limit') ?: 'N/A',
            'max_execution_time' => (ini_get('max_execution_time') ?: '0') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'N/A',
            'post_max_size' => ini_get('post_max_size') ?: 'N/A',
            'disk_free' => $freeDisk !== false ? self::formatBytes((float) $freeDisk) : 'N/A',
            'disk_total' => $totalDisk !== false ? self::formatBytes((float) $totalDisk) : 'N/A',
            'disk_used_percent' => $diskUsedPercent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDatabaseStats(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $dbName = (string) DB::connection()->getDatabaseName();
            $driver = (string) config('database.default');

            // Hitung tabel
            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);

            // Hitung ukuran database jika MySQL
            $sizeMb = 0.0;
            if (str_contains(strtolower($driver), 'mysql')) {
                $sizeQuery = DB::select(
                    'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                     FROM information_schema.TABLES 
                     WHERE table_schema = ?',
                    [$dbName]
                );
                $sizeMb = (float) ($sizeQuery[0]->size_mb ?? 0.0);
            }

            return [
                'status' => 'Connected',
                'driver' => strtoupper($driver),
                'database_name' => $dbName,
                'version' => $dbVersion,
                'table_count' => $tableCount,
                'size_mb' => $sizeMb > 0 ? $sizeMb . ' MB' : '< 1 MB',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'Disconnected (' . $e->getMessage() . ')',
                'driver' => (string) config('database.default'),
                'database_name' => '-',
                'version' => '-',
                'table_count' => 0,
                'size_mb' => '-',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function getAppDataStats(): array
    {
        try {
            $userCounts = [
                'total' => User::count(),
                'superadmin' => User::where('role_type', 'superadmin')->count(),
                'admin' => User::where('role_type', 'admin')->count(),
                'piket_guru' => User::where('role_type', 'piket_guru')->count(),
                'piket_siswa' => User::where('role_type', 'piket_siswa')->count(),
                'active' => User::where('is_active', true)->count(),
            ];

            $txCounts = [
                'total' => PosTransaction::count(),
                'completed' => PosTransaction::where('status', 'COMPLETED')->count(),
                'voided' => PosTransaction::where('status', 'VOIDED')->count(),
                'total_revenue' => (float) PosTransaction::where('status', 'COMPLETED')->sum('grand_total'),
            ];

            $productCounts = [
                'retail' => Product::where('inventory_type', 'RETAIL')->count(),
                'raw_material' => Product::where('inventory_type', 'RAW_MATERIAL')->count(),
                'services' => Service::count(),
            ];

            $journalCounts = [
                'entries' => JournalEntry::count(),
                'items' => JournalItem::count(),
            ];

            $shiftCounts = [
                'total' => PosShift::count(),
                'open' => PosShift::where('status', 'OPEN')->count(),
            ];

            return [
                'users' => $userCounts,
                'transactions' => $txCounts,
                'inventory' => $productCounts,
                'journals' => $journalCounts,
                'shifts' => $shiftCounts,
            ];
        } catch (\Throwable $e) {
            return [
                'users' => ['total' => 0, 'active' => 0],
                'transactions' => ['total' => 0, 'completed' => 0, 'voided' => 0, 'total_revenue' => 0],
                'inventory' => ['retail' => 0, 'raw_material' => 0, 'services' => 0],
                'journals' => ['entries' => 0, 'items' => 0],
                'shifts' => ['total' => 0, 'open' => 0],
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private static function getDriversStats(): array
    {
        return [
            'cache_driver' => (string) config('cache.default'),
            'session_driver' => (string) config('session.driver'),
            'queue_connection' => (string) config('queue.default'),
            'mail_mailer' => (string) config('mail.default'),
        ];
    }

    private static function formatBytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

