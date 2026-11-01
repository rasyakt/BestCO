<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('pos.terminal');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $credentials['login'];

        // Cari user berdasarkan username, nip_nisn, atau email
        $user = User::where('is_active', true)
            ->where(function ($query) use ($loginInput): void {
                $query->where('username', $loginInput)
                    ->orWhere('nip_nisn', $loginInput)
                    ->orWhere('email', $loginInput);
            })
            ->first();

        if ($user && Auth::attempt(['id' => $user->id, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);

            // Siswa piket selalu diarahkan ke terminal kasir (tidak boleh ke dashboard staf)
            if ($user->hasRole('piket_siswa')) {
                $request->session()->forget('url.intended');

                return redirect()->route('pos.terminal');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'Kombinasi ID Pengguna / Password salah atau akun dinonaktifkan.',
        ])->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

