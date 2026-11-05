@php $title = 'Ganti Password'; @endphp

<x-app-layout :title="$title">
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
                <div>
                    <label for="current_password" class="form-label">Password Saat Ini</label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        class="form-input mt-1 @error('current_password') border-rose-500 focus:ring-rose-500 @enderror"
                        required
                    >
                    @error('current_password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div>
                    <label for="password" class="form-label">Password Baru</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        class="form-input mt-1 @error('password') border-rose-500 focus:ring-rose-500 @enderror"
                        required
                    >
                    <p class="mt-1 text-[11px] text-slate-400">Min. 8 karakter, kombinasi huruf besar, kecil, dan angka.</p>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password Baru --}}
                <div>
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="form-input mt-1"
                        required
                    >
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
</x-app-layout>

