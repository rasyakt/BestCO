<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Feature\FeatureManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FeatureSettingController extends Controller
{
    public function __construct(
        private readonly FeatureManager $featureManager
    ) {}

    /**
     * Tampilkan antarmuka manajemen pengaturan fitur dan hak akses dinamis.
     */
    public function index(): View
    {
        $features = $this->featureManager->getFeatures();
        $configurableRoles = $this->featureManager->configurableRoles();

        $groupedFeatures = collect($features)->groupBy('group');

        $totalFeatures = count($features);
        $activeFeatures = collect($features)->where('is_active', true)->count();
        $inactiveFeatures = $totalFeatures - $activeFeatures;

        return view('settings.features', [
            'features' => $features,
            'groupedFeatures' => $groupedFeatures,
            'configurableRoles' => $configurableRoles,
            'totalFeatures' => $totalFeatures,
            'activeFeatures' => $activeFeatures,
            'inactiveFeatures' => $inactiveFeatures,
        ]);
    }

    /**
     * Simpan perubahan matriks fitur dan hak akses role.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'features' => 'nullable|array',
            'features.*.is_active' => 'nullable',
            'features.*.roles' => 'nullable|array',
            'features.*.roles.*' => 'string|in:admin,piket_guru,piket_siswa',
        ]);

        try {
            $this->featureManager->updatePermissions(
                $request->input('features', []),
                (int) auth()->id()
            );

            return redirect()
                ->route('settings.features')
                ->with('success', 'Pengaturan fitur dan hak akses peran berhasil disimpan!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan pengaturan fitur: '.$e->getMessage());
        }
    }

    /**
     * Aktifkan seluruh fitur secara global.
     */
    public function enableAll(): RedirectResponse
    {
        try {
            $this->featureManager->enableAll((int) auth()->id());

            return redirect()
                ->route('settings.features')
                ->with('success', 'Seluruh fitur aplikasi berhasil diaktifkan secara global!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal mengaktifkan semua fitur: '.$e->getMessage());
        }
    }

    /**
     * Kembalikan pengaturan fitur ke default standar pabrik.
     */
    public function reset(): RedirectResponse
    {
        try {
            $this->featureManager->resetToDefault((int) auth()->id());

            return redirect()
                ->route('settings.features')
                ->with('success', 'Pengaturan fitur berhasil dikembalikan ke standar awal sistem!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal mereset pengaturan fitur: '.$e->getMessage());
        }
    }
}

