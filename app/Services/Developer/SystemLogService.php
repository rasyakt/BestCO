<?php

declare(strict_types=1);

namespace App\Services\Developer;

final class SystemLogService
{
    private const MAX_READ_BYTES = 2097152; // 2 MB baca dari akhir file agar cepat & hemat memori

    /**
     * Ambil sejumlah log error terbaru dari storage/logs/laravel.log.
     *
     * @return array<int, array{
     *     id: string,
     *     timestamp: string,
     *     environment: string,
     *     level: string,
     *     message: string,
     *     details: string,
     *     full: string,
     *     is_error: bool
     * }>
     */
    public static function getRecentErrors(int $limit = 10): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath) || ! is_readable($logPath)) {
            return [];
        }

        $fileSize = filesize($logPath);
        if ($fileSize === 0) {
            return [];
        }

        // Baca sebagian dari akhir file jika file sangat besar
        $fp = fopen($logPath, 'rb');
        if (! $fp) {
            return [];
        }

        $readBytes = min($fileSize, self::MAX_READ_BYTES);
        fseek($fp, -$readBytes, SEEK_END);
        $content = fread($fp, $readBytes);
        fclose($fp);

        if ($content === false || trim($content) === '') {
            return [];
        }

        // Pola entri log Laravel standar: [YYYY-MM-DD HH:MM:SS] env.LEVEL: Message
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_\-]+)\.([A-Z]+):\s+(.*)$/m';

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return [];
        }

        $count = count($matches[0]);
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $matches[1][$i][0];
            $environment = $matches[2][$i][0];
            $level = strtoupper($matches[3][$i][0]);
            $firstLineMsg = trim($matches[4][$i][0]);

            $startOffset = $matches[0][$i][1];
            $endOffset = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($content);
            $fullBlock = trim(substr($content, $startOffset, $endOffset - $startOffset));

            // Pisahkan pesan baris pertama dengan stack trace / details
            $blockLines = explode("\n", $fullBlock);
            array_shift($blockLines); // Hapus baris header
            $details = trim(implode("\n", $blockLines));

            // Saring level error yang relevan
            $isErrorLevel = in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true);

            $entries[] = [
                'id' => 'log_' . md5($timestamp . $firstLineMsg . $i),
                'timestamp' => $timestamp,
                'environment' => $environment,
                'level' => $level,
                'message' => $firstLineMsg,
                'details' => $details,
                'full' => $fullBlock,
                'is_error' => $isErrorLevel,
            ];
        }

        // Ambil yang bertipe error terlebih dahulu, atau semua entri jika error sedikit
        $errorEntries = array_filter($entries, fn (array $e) => $e['is_error']);

        // Urutkan dari yang paling baru (terbaru di paling atas)
        $result = ! empty($errorEntries) ? array_reverse(array_values($errorEntries)) : array_reverse($entries);

        return array_slice($result, 0, $limit);
    }

    /**
     * Hapus / kosongkan isi file log.
     */
    public static function clear(): bool
    {
        $logPath = storage_path('logs/laravel.log');

        if (file_exists($logPath)) {
            return file_put_contents($logPath, '') !== false;
        }

        return true;
    }

    /**
     * Ukuran file log dalam format yang mudah dibaca.
     */
    public static function getFileSizeFormatted(): string
    {
        $logPath = storage_path('logs/laravel.log');
        if (! file_exists($logPath)) {
            return '0 B';
        }

        $bytes = (float) filesize($logPath);
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

