<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class QrisSettingController extends Controller
{
    public function index(): View
    {
        $qrisImagePath = $this->getQrisImagePath();
        
        return view('settings.qris', [
            'qrisImagePath' => $qrisImagePath,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'qris_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'qris_image.required' => 'File QR code wajib diupload.',
            'qris_image.image' => 'File harus berupa gambar.',
            'qris_image.mimes' => 'Format gambar harus PNG, JPG, atau JPEG.',
            'qris_image.max' => 'Ukuran file maksimal 2MB.',
        ]);

        try {
            // Hapus file lama jika ada
            $oldPath = $this->getQrisImagePath();
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Upload file baru
            $file = $request->file('qris_image');
            $filename = 'qris-'.time().'.png';
            $path = $file->storeAs('images', $filename, 'public');

            // Simpan ke database
            SystemSetting::updateOrCreate(
                ['setting_key' => 'pos_qris_image_path'],
                [
                    'setting_group' => 'pos',
                    'setting_value' => ['path' => $path],
                    'updated_by' => auth()->id(),
                ]
            );

            return redirect()
                ->route('settings.qris')
                ->with('success', 'QR code QRIS berhasil diupload!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal upload QR code: '.$e->getMessage());
        }
    }

    public function delete(): RedirectResponse
    {
        try {
            $path = $this->getQrisImagePath();
            
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            SystemSetting::where('setting_key', 'pos_qris_image_path')->delete();

            return redirect()
                ->route('settings.qris')
                ->with('success', 'QR code QRIS berhasil dihapus!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus QR code: '.$e->getMessage());
        }
    }

    private function getQrisImagePath(): ?string
    {
        $setting = SystemSetting::where('setting_key', 'pos_qris_image_path')->first();
        
        return $setting?->setting_value['path'] ?? null;
    }
}

