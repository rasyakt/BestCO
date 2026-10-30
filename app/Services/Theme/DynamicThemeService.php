<?php

declare(strict_types=1);

namespace App\Services\Theme;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

final class DynamicThemeService
{
    public const DEFAULT_PALETTE = [
        '--color-primary-50'  => '#eff6ff',
        '--color-primary-100' => '#dbeafe',
        '--color-primary-200' => '#bfdbfe',
        '--color-primary-300' => '#93c5fd',
        '--color-primary-400' => '#60a5fa',
        '--color-primary-500' => '#3b82f6',
        '--color-primary-600' => '#2563eb',
        '--color-primary-700' => '#1d4ed8',
        '--color-primary-800' => '#1e40af',
        '--color-primary-900' => '#1e3a8a',
        '--color-primary-950' => '#172554',
    ];

    public function getPalette(): array
    {
        return Cache::rememberForever('bestco.theme_css_vars', function (): array {
            $setting = SystemSetting::where('setting_key', 'ui_theme_palette')->first();
            if ($setting && is_array($setting->setting_value)) {
                return array_merge(self::DEFAULT_PALETTE, $setting->setting_value);
            }
            return self::DEFAULT_PALETTE;
        });
    }

    public function getCssVariables(): string
    {
        $palette = $this->getPalette();
        $css = ":root {\n";
        foreach ($palette as $var => $value) {
            $css .= "    {$var}: {$value};\n";
        }
        $css .= "}\n";
        return $css;
    }

    public function updatePalette(array $newPalette): void
    {
        $merged = array_merge(self::DEFAULT_PALETTE, $newPalette);

        SystemSetting::updateOrCreate(
            ['setting_key' => 'ui_theme_palette'],
            [
                'setting_group' => 'theme',
                'setting_value' => $merged,
            ]
        );

        Cache::forget('bestco.theme_css_vars');
    }

    public function resetPalette(): void
    {
        SystemSetting::where('setting_key', 'ui_theme_palette')->delete();
        Cache::forget('bestco.theme_css_vars');
    }
}
