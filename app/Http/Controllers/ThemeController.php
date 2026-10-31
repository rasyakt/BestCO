<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Theme\DynamicThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ThemeController extends Controller
{
    public const PRESETS = [
        'enterprise_blue' => [
            'name' => 'Enterprise Blue (Default)',
            'palette' => [
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
            ],
        ],
        'emerald_slate' => [
            'name' => 'Emerald Slate',
            'palette' => [
                '--color-primary-50'  => '#ecfdf5',
                '--color-primary-100' => '#d1fae5',
                '--color-primary-200' => '#a7f3d0',
                '--color-primary-300' => '#6ee7b7',
                '--color-primary-400' => '#34d399',
                '--color-primary-500' => '#10b981',
                '--color-primary-600' => '#059669',
                '--color-primary-700' => '#047857',
                '--color-primary-800' => '#065f46',
                '--color-primary-900' => '#064e3b',
                '--color-primary-950' => '#022c22',
            ],
        ],
        'indigo_steel' => [
            'name' => 'Indigo Steel',
            'palette' => [
                '--color-primary-50'  => '#eef2ff',
                '--color-primary-100' => '#e0e7ff',
                '--color-primary-200' => '#c7d2fe',
                '--color-primary-300' => '#a5b4fc',
                '--color-primary-400' => '#818cf8',
                '--color-primary-500' => '#6366f1',
                '--color-primary-600' => '#4f46e5',
                '--color-primary-700' => '#4338ca',
                '--color-primary-800' => '#3730a3',
                '--color-primary-900' => '#312e81',
                '--color-primary-950' => '#1e1b4b',
            ],
        ],
        'crimson_corp' => [
            'name' => 'Crimson Corp',
            'palette' => [
                '--color-primary-50'  => '#fff1f2',
                '--color-primary-100' => '#ffe4e6',
                '--color-primary-200' => '#fecdd3',
                '--color-primary-300' => '#fda4af',
                '--color-primary-400' => '#fb7185',
                '--color-primary-500' => '#f43f5e',
                '--color-primary-600' => '#e11d48',
                '--color-primary-700' => '#be123c',
                '--color-primary-800' => '#9f1239',
                '--color-primary-900' => '#881337',
                '--color-primary-950' => '#4c0519',
            ],
        ],
    ];

    public function __construct(
        private readonly DynamicThemeService $themeService
    ) {}

    public function index(): View
    {
        $currentPalette = $this->themeService->getPalette();
        $presets = self::PRESETS;

        return view('settings.theme', compact('currentPalette', 'presets'));
    }

    public function applyPreset(Request $request): RedirectResponse
    {
        $presetKey = $request->input('preset_key');
        if (isset(self::PRESETS[$presetKey])) {
            $this->themeService->updatePalette(self::PRESETS[$presetKey]['palette']);
            return back()->with('success', 'Tema ' . self::PRESETS[$presetKey]['name'] . ' berhasil diterapkan!');
        }

        return back()->withErrors(['preset_key' => 'Preset tidak ditemukan.']);
    }

    public function reset(): RedirectResponse
    {
        $this->themeService->resetPalette();

        return back()->with('success', 'Tema berhasil direset ke Enterprise Blue default.');
    }
}

