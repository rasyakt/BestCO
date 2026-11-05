<?php

declare(strict_types=1);

namespace App\Services\Developer;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class DatabaseBackupService
{
    private const BACKUP_DIR = 'backups';

    /**
     * Ambil path direktori penyimpanan file backup.
     */
    public static function getBackupDirectory(): string
    {
        $dir = storage_path('app/' . self::BACKUP_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Ambil daftar file backup yang tersedia.
     *
     * @return array<int, array{
     *     filename: string,
     *     path: string,
     *     size: int,
     *     size_formatted: string,
     *     created_at: string,
     *     is_compressed: bool
     * }>
     */
    public static function listBackups(): array
    {
        $dir = self::getBackupDirectory();
        $files = glob($dir . '/*.{sql,gz}', GLOB_BRACE) ?: [];
        $files = array_values(array_unique($files));

        $list = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $size = (int) filesize($file);
            $mtime = filemtime($file);

            $list[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => $size,
                'size_formatted' => self::formatBytes($size),
                'created_at' => Carbon::createFromTimestamp($mtime)->format('d M Y H:i:s'),
                'is_compressed' => str_ends_with($filename, '.gz'),
            ];
        }

        // Urutkan dari yang paling baru
        usort($list, fn ($a, $b) => filemtime($b['path']) <=> filemtime($a['path']));

        return $list;
    }

    /**
     * Ambil path absolut file backup berdasarkan nama file yang aman.
     */
    public static function getBackupPath(string $filename): ?string
    {
        $safeName = basename($filename);

        // Validasi format nama file yang ketat untuk mencegah manipulasi
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+\.(sql|sql\.gz|gz)$/i', $safeName)) {
            return null;
        }

        $fullPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $safeName;

        if (file_exists($fullPath) && is_file($fullPath)) {
            return $fullPath;
        }

        return null;
    }

    /**
     * Hapus file backup tertentu.
     */
    public static function deleteBackup(string $filename): bool
    {
        $path = self::getBackupPath($filename);
        if ($path && file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Buat backup database lengkap (.sql atau .sql.gz).
     *
     * @return string Nama file backup yang dihasilkan
     */
    public static function createBackup(bool $compress = false): string
    {
        $dir = self::getBackupDirectory();
        $timestamp = date('Y-m-d_H-i-s');
        $ext = $compress ? 'sql.gz' : 'sql';
        $filename = "backup-bestco-{$timestamp}.{$ext}";
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $driver = (string) config('database.default');
        $sqlContent = self::generateDumpSql($driver);

        if ($compress) {
            $compressed = gzencode($sqlContent, 9);
            if ($compressed === false) {
                throw new RuntimeException('Gagal mengompresi data backup ke format GZIP.');
            }
            file_put_contents($filepath, $compressed);
        } else {
            file_put_contents($filepath, $sqlContent);
        }

        Log::info("Database backup created: {$filename} by user #" . auth()->id());

        return $filename;
    }

    /**
     * Restore database dari file lokal yang sudah ada di direktori backup.
     */
    public static function restoreFromFile(string $filename): void
    {
        $path = self::getBackupPath($filename);
        if (! $path) {
            throw new RuntimeException("File backup '{$filename}' tidak ditemukan.");
        }

        self::executeRestoreSql(self::readSqlContent($path));
        Log::info("Database restored from backup: {$filename} by user #" . auth()->id());
    }

    /**
     * Restore database dari file yang diunggah pengguna.
     */
    public static function restoreFromUpload(UploadedFile $file): void
    {
        $clientExt = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        $allowedExts = ['sql', 'gz'];
        if (! in_array($clientExt, $allowedExts, true) && ! str_ends_with($file->getClientOriginalName(), '.sql.gz')) {
            throw new RuntimeException('Format file tidak didukung. Harap upload file .sql atau .sql.gz.');
        }

        $rawContent = file_get_contents($file->getRealPath());
        if ($rawContent === false || trim($rawContent) === '') {
            throw new RuntimeException('File backup kosong atau tidak dapat dibaca.');
        }

        // Simpan salinan ke direktori backup agar riwayat tetap ada
        $timestamp = date('Y-m-d_H-i-s');
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $savedName = "uploaded-{$timestamp}-{$safeOriginalName}";
        file_put_contents(self::getBackupDirectory() . DIRECTORY_SEPARATOR . $savedName, $rawContent);

        self::executeRestoreSql(self::readSqlContentString($rawContent));
        Log::info("Database restored from uploaded file: {$savedName} by user #" . auth()->id());
    }

    /**
     * Baca konten SQL mentah dari file (otomatis dekompresi jika gzip).
     */
    private static function readSqlContent(string $path): string
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Gagal membaca file '{$path}'.");
        }

        return self::readSqlContentString($raw);
    }

    /**
     * Dekompresi string jika berformat GZIP.
     */
    private static function readSqlContentString(string $content): string
    {
        // Cek magic bytes GZIP (0x1F, 0x8B)
        if (str_starts_with($content, "\x1f\x8b")) {
            $decompressed = @gzdecode($content);
            if ($decompressed === false) {
                throw new RuntimeException('File terkompresi GZIP rusak atau tidak dapat didekompresi.');
            }
            return $decompressed;
        }

        return $content;
    }

    /**
     * Eksekusi seluruh statement SQL restore ke dalam database.
     */
    private static function executeRestoreSql(string $sql): void
    {
        $driver = (string) config('database.default');
        $isMysql = str_contains(strtolower($driver), 'mysql');
        $isSqlite = str_contains(strtolower($driver), 'sqlite');

        // Nonaktifkan pemeriksaan foreign key sebelum restore
        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
        } elseif ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            // Eksekusi script SQL
            DB::unprepared($sql);
        } finally {
            // Selalu kembalikan foreign key checks ke status aktif
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($isSqlite) {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }

        // Bersihkan cache aplikasi & view
        Cache::flush();
        try {
            Artisan::call('view:clear');
        } catch (\Throwable) {
            // Abaikan jika tidak di lingkungan CLI
        }
    }

    /**
     * Hasilkan isi dump SQL berdasarkan driver database.
     */
    private static function generateDumpSql(string $driver): string
    {
        if (str_contains(strtolower($driver), 'sqlite')) {
            return self::generateSqliteDump();
        }

        return self::generateMysqlDump();
    }

    /**
     * Hasilkan SQL dump standar untuk MySQL.
     */
    private static function generateMysqlDump(): string
    {
        $pdo = DB::connection()->getPdo();
        $dbName = (string) DB::connection()->getDatabaseName();
        $date = date('Y-m-d H:i:s');

        $out = "-- ============================================================\n";
        $out .= "-- BESTCO Enterprise Database Backup\n";
        $out .= "-- Tanggal Pembuatan : {$date}\n";
        $out .= "-- Database         : {$dbName}\n";
        $out .= "-- Server Version   : " . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) . "\n";
        $out .= "-- ============================================================\n\n";

        $out .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
        $out .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
        $out .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
        $out .= "/*!40101 SET NAMES utf8mb4 */;\n";
        $out .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
        $out .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
        $out .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
        $out .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";

        // Ambil daftar seluruh base table
        $tablesQuery = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];
        foreach ($tablesQuery as $row) {
            $tables[] = array_values((array) $row)[0];
        }

        foreach ($tables as $table) {
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "-- Struktur Tabel `{$table}`\n";
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($createRow)) {
                $createSql = ((array) $createRow[0])['Create Table'];
                $out .= $createSql . ";\n\n";
            }

            // Dump data baris per baris dalam batch
            $count = DB::table($table)->count();
            if ($count > 0) {
                $out .= "-- Dumping data untuk tabel `{$table}` ({$count} baris)\n";

                // Baca kolom
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $escapedCols = array_map(fn ($c) => "`{$c}`", $columns);
                $colsSql = implode(', ', $escapedCols);

                $rows = DB::table($table)->get();
                $chunkSize = 100;
                $rowChunks = $rows->chunk($chunkSize);

                foreach ($rowChunks as $chunk) {
                    $valuesArr = [];
                    foreach ($chunk as $row) {
                        $rowVals = [];
                        foreach ($columns as $col) {
                            $val = $row->{$col} ?? null;
                            if ($val === null) {
                                $rowVals[] = 'NULL';
                            } elseif (is_numeric($val) && ! is_string($val)) {
                                $rowVals[] = (string) $val;
                            } else {
                                $rowVals[] = $pdo->quote((string) $val);
                            }
                        }
                        $valuesArr[] = '(' . implode(', ', $rowVals) . ')';
                    }
                    $out .= "INSERT INTO `{$table}` ({$colsSql}) VALUES\n" . implode(",\n", $valuesArr) . ";\n";
                }

                $out .= "\n";
            }
        }

        $out .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
        $out .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
        $out .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
        $out .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
        $out .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
        $out .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
        $out .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
        $out .= "-- Selesai pada: " . date('Y-m-d H:i:s') . "\n";

        return $out;
    }

    /**
     * Hasilkan SQL dump untuk SQLite (kompatibel saat unit/feature testing).
     */
    private static function generateSqliteDump(): string
    {
        $pdo = DB::connection()->getPdo();
        $date = date('Y-m-d H:i:s');

        $out = "-- BESTCO SQLite Database Backup\n";
        $out .= "-- Date: {$date}\n\n";
        $out .= "PRAGMA foreign_keys = OFF;\n\n";

        $tablesQuery = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        foreach ($tablesQuery as $t) {
            $table = $t->name;
            $createSql = $t->sql;

            $out .= "DROP TABLE IF EXISTS \"{$table}\";\n";
            $out .= $createSql . ";\n";

            $rows = DB::table($table)->get();
            if ($rows->count() > 0) {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $escapedCols = array_map(fn ($c) => "\"{$c}\"", $columns);
                $colsSql = implode(', ', $escapedCols);

                foreach ($rows as $row) {
                    $rowVals = [];
                    foreach ($columns as $col) {
                        $val = $row->{$col} ?? null;
                        if ($val === null) {
                            $rowVals[] = 'NULL';
                        } elseif (is_numeric($val) && ! is_string($val)) {
                            $rowVals[] = (string) $val;
                        } else {
                            $rowVals[] = $pdo->quote((string) $val);
                        }
                    }
                    $out .= "INSERT INTO \"{$table}\" ({$colsSql}) VALUES (" . implode(', ', $rowVals) . ");\n";
                }
            }
            $out .= "\n";
        }

        $out .= "PRAGMA foreign_keys = ON;\n";

        return $out;
    }

    private static function formatBytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
