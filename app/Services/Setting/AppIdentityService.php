<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class AppIdentityService
{
    private const CACHE_KEY = 'app_identity_settings';
    private const SETTING_KEY = 'app_identity';

    /**
     * Ambil data identitas website (nama, tagline, logo).
     *
     * @return array{
     *     app_name: string,
     *     app_tagline: string,
     *     app_logo: ?string,
     *     app_logo_url: string,
     *     has_custom_logo: bool,
     *     footer_text: string
     * }
     */
    public static function get(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $defaults = [
                'app_name' => 'BESTCO',
                'app_tagline' => 'Teaching Factory MPLB',
                'app_logo' => null,
                'footer_text' => 'Sistem kasir dan persediaan sekolah',
            ];

            try {
                $setting = SystemSetting::where('setting_key', self::SETTING_KEY)->first();
                $data = is_array($setting?->setting_value) ? $setting->setting_value : [];
            } catch (\Throwable) {
                $data = [];
            }

            $merged = array_merge($defaults, $data);
            $hasCustomLogo = ! empty($merged['app_logo']) && Storage::disk('public')->exists($merged['app_logo']);

            return [
                'app_name' => (string) ($merged['app_name'] ?: 'BESTCO'),
                'app_tagline' => (string) ($merged['app_tagline'] ?: 'Teaching Factory MPLB'),
                'app_logo' => $hasCustomLogo ? $merged['app_logo'] : null,
                'app_logo_url' => $hasCustomLogo ? Storage::disk('public')->url($merged['app_logo']) : asset('img/best.png'),
                'has_custom_logo' => $hasCustomLogo,
                'footer_text' => (string) ($merged['footer_text'] ?: 'Sistem kasir dan persediaan sekolah'),
            ];
        });
    }

    /**
     * Perbarui identitas website.
     *
     * @param array{
     *     app_name: string,
     *     app_tagline?: ?string,
     *     footer_text?: ?string
     * } $attributes
     */
    public static function update(array $attributes, ?UploadedFile $logoFile = null): void
    {
        $current = self::get();
        $logoPath = $current['app_logo'];

        if ($logoFile !== null) {
            // Hapus logo lama jika ada
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            // Simpan logo baru di storage/branding
            $extension = $logoFile->getClientOriginalExtension() ?: 'png';
            $fileName = 'logo_' . time() . '.' . $extension;
            $logoPath = $logoFile->storeAs('branding', $fileName, 'public');
        }

        $payload = [
            'app_name' => trim($attributes['app_name'] ?? 'BESTCO'),
            'app_tagline' => trim($attributes['app_tagline'] ?? 'Teaching Factory MPLB'),
            'app_logo' => $logoPath,
            'footer_text' => trim($attributes['footer_text'] ?? 'Sistem kasir dan persediaan sekolah'),
        ];

        SystemSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY],
            [
                'setting_group' => 'developer',
                'setting_value' => $payload,
                'updated_by' => auth()->id(),
            ]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Reset logo ke bawaan sistem (SVG / best.png).
     */
    public static function resetLogo(): void
    {
        $current = self::get();

        if ($current['app_logo'] && Storage::disk('public')->exists($current['app_logo'])) {
            Storage::disk('public')->delete($current['app_logo']);
        }

        $setting = SystemSetting::where('setting_key', self::SETTING_KEY)->first();
        if ($setting && is_array($setting->setting_value)) {
            $val = $setting->setting_value;
            $val['app_logo'] = null;
            $setting->update([
                'setting_value' => $val,
                'updated_by' => auth()->id(),
            ]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
