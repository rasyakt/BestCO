@extends('layouts.app', ['title' => 'Ganti Password — BESTCO'])

@section('content')
<div class="space-y-6">
    <x-page-header title="Ganti Password" subtitle="Perbarui password akun Anda. Pastikan menggunakan kombinasi yang kuat." />

    <div class="mx-auto max-w-lg">

        @if(session('success'))
            <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password.update') }}" class="card divide-y divide-slate-100">
            @csrf
            @method('PUT')

            <div class="p-6">
                <h2 class="text-sm font-semibold text-slate-900">Ubah Kata Sandi</h2>
                <p class="mt-1 text-xs text-slate-500">Setelah disimpan, sesi login di perangkat lain akan otomatis dinonaktifkan.</p>
            </div>

            <div class="space-y-5 p-6">

                {{-- Password Saat Ini --}}
                <div x-data="{ show: false }">
                    <label for="current_password" class="form-label">Password Saat Ini</label>
                    <div class="relative mt-1">
                        <input
                            :type="show ? 'text' : 'password'"
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            placeholder="Masukkan password saat ini"
                            class="input !pr-10 @error('current_password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                            required
                        >
                        <button
                            type="button"
                            @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                            tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <span x-show="!show">
                                <x-icon name="eye" class="h-4 w-4" />
                            </span>
                            <span x-show="show" x-cloak>
                                <x-icon name="eye-off" class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div x-data="{ show: false }">
                    <label for="password" class="form-label">Password Baru</label>
                    <div class="relative mt-1">
                        <input
                            :type="show ? 'text' : 'password'"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="Masukkan password baru"
                            class="input !pr-10 @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                            required
                        >
                        <button
                            type="button"
                            @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                            tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <span x-show="!show">
                                <x-icon name="eye" class="h-4 w-4" />
                            </span>
                            <span x-show="show" x-cloak>
                                <x-icon name="eye-off" class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                    <p class="form-hint">Min. 8 karakter, kombinasi huruf besar, kecil, dan angka.</p>
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password Baru --}}
                <div x-data="{ show: false }">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <div class="relative mt-1">
                        <input
                            :type="show ? 'text' : 'password'"
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Ulangi password baru"
                            class="input !pr-10"
                            required
                        >
                        <button
                            type="button"
                            @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Lihat password'"
                            tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <span x-show="!show">
                                <x-icon name="eye" class="h-4 w-4" />
                            </span>
                            <span x-show="show" x-cloak>
                                <x-icon name="eye-off" class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 bg-slate-50 px-6 py-4">
                <a href="{{ url()->previous() }}" class="btn btn-secondary text-xs">Batal</a>
                <button type="submit" class="btn btn-primary text-xs">
                    Simpan Password Baru
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

