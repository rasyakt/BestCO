<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    /**
     * Tampilkan form ganti password milik user yang sedang login.
     */
    public function editPassword(): View
    {
        return view('profile.change-password');
    }

    /**
     * Simpan password baru setelah memverifikasi password lama.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password baru tidak cocok.',
            'password.min'              => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();

        // Verifikasi password lama
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        // Tolak jika password baru sama dengan yang lama
        if (Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'password' => 'Password baru tidak boleh sama dengan password saat ini.',
            ]);
        }

        $user->update([
            'password' => $request->input('password'),
        ]);

        // Invalidasi semua sesi lain (login ulang otomatis dengan sesi baru)
        auth()->logoutOtherDevices($request->input('password'));

        return redirect()
            ->route('profile.password')
            ->with('success', 'Password berhasil diperbarui. Sesi lain telah dinonaktifkan.');
    }
}

