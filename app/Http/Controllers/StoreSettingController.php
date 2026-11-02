<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StoreSettingController extends Controller
{
    public function index(): View
    {
        $storeInfo = $this->getStoreInfo();
        
        return view('settings.store', [
            'storeInfo' => $storeInfo,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:100',
            'store_tagline' => 'nullable|string|max:100',
            'store_address_line1' => 'nullable|string|max:100',
            'store_address_line2' => 'nullable|string|max:100',
            'store_phone' => 'nullable|string|max:20',
            'store_email' => 'nullable|email|max:100',
            'receipt_footer_line1' => 'nullable|string|max:100',
            'receipt_footer_line2' => 'nullable|string|max:100',
        ], [
            'store_name.required' => 'Nama toko wajib diisi.',
            'store_name.max' => 'Nama toko maksimal 100 karakter.',
            'store_email.email' => 'Format email tidak valid.',
        ]);

        try {
            SystemSetting::updateOrCreate(
                ['setting_key' => 'store_information'],
                [
                    'setting_group' => 'pos',
                    'setting_value' => $validated,
                    'updated_by' => auth()->id(),
                ]
            );

            return redirect()
                ->route('settings.store')
                ->with('success', 'Informasi toko berhasil diperbarui!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui informasi toko: '.$e->getMessage());
        }
    }

    public function reset(): RedirectResponse
    {
        try {
            SystemSetting::where('setting_key', 'store_information')->delete();

            return redirect()
                ->route('settings.store')
                ->with('success', 'Informasi toko dikembalikan ke default!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal reset informasi toko: '.$e->getMessage());
        }
    }

    public static function getStoreInfo(): array
    {
        $setting = SystemSetting::where('setting_key', 'store_information')->first();

        // Default values
        $defaults = [
            'store_name' => 'BESTCO',
            'store_tagline' => 'Teaching Factory MPLB',
            'store_address_line1' => 'SMK Business Center',
            'store_address_line2' => null,
            'store_phone' => null,
            'store_email' => null,
            'receipt_footer_line1' => 'Terima kasih',
            'receipt_footer_line2' => 'Laboratorium Praktik MPLB',
        ];

        if (! $setting) {
            return $defaults;
        }

        return array_merge($defaults, $setting->setting_value);
    }
}

