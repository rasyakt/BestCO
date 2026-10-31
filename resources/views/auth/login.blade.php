<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111827">
    <title>Masuk — BESTCO</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1fr_0.9fr]">

        <!-- Panel kiri -->
        <section class="hidden flex-col justify-between bg-slate-900 p-10 text-white lg:flex xl:p-14">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-white text-xs font-bold text-slate-900">
                    BC
                </span>
                <div class="leading-tight">
                    <span class="block text-sm font-semibold tracking-wide">BESTCO</span>
                    <span class="mt-0.5 block text-[11px] text-slate-400">Teaching Factory MPLB</span>
                </div>
            </div>

            <div class="max-w-lg">
                <h1 class="text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">
                    Kasir, stok, dan laporan dalam satu tempat.
                </h1>

                <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                    Aplikasi praktik untuk siswa MPLB. Mulai dari transaksi, stok, sampai laporan keuangan.
                </p>

                <div class="mt-8 space-y-4">
                    @foreach([
                        ['icon' => 'pos', 'title' => 'Kasir dan struk', 'desc' => 'Transaksi cepat dengan persetujuan guru.'],
                        ['icon' => 'package', 'title' => 'Stok etalase dan bahan', 'desc' => 'Stok terpisah, HPP tetap akurat.'],
                        ['icon' => 'shield', 'title' => 'Laporan keuangan', 'desc' => 'Setiap transaksi tercatat otomatis.'],
                    ] as $feature)
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white/10 text-slate-300">
                                <x-icon :name="$feature['icon']" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-white">{{ $feature['title'] }}</p>
                                <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-white/10 pt-4 text-[11px] text-slate-500">
                <span>SMK Teaching Factory</span>
                <span>Laboratorium MPLB</span>
            </div>
        </section>

        <!-- Panel form -->
        <section class="flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-[420px]">

                <!-- Brand mobile -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-xs font-bold text-white">BC</span>
                    <div class="leading-tight">
                        <span class="block text-sm font-semibold tracking-wide text-slate-900">BESTCO</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Teaching Factory MPLB</span>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-2xl font-semibold leading-tight tracking-tight text-slate-900">Masuk</h2>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-slate-500">Gunakan akun sekolah untuk melanjutkan.</p>
                </div>

                <div class="card p-6 sm:p-7">
                    @if($errors->has('login'))
                        <div class="alert alert-danger mb-5">
                            <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span class="flex-1 leading-relaxed">{{ $errors->first('login') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="login" class="form-label">Username, NIP, atau NISN</label>
                            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                                placeholder="Contoh: siswa.x1"
                                class="input">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required autocomplete="current-password"
                                    placeholder="Masukkan password"
                                    class="input pr-10">
                                <button type="button" onclick="togglePassword()" aria-label="Tampilkan password"
                                    class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                    <x-icon name="eye" id="eye-open" class="h-4 w-4" />
                                    <x-icon name="eye-off" id="eye-closed" class="hidden h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                            <input type="checkbox" name="remember" class="checkbox">
                            Ingat saya
                        </label>

                        <button type="submit" class="btn btn-primary btn-lg mt-1 w-full">
                            Masuk
                        </button>
                    </form>
                </div>

                <details class="group mt-4 rounded-md border border-slate-200 bg-white px-4 py-3">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-[11px] font-medium text-slate-500 transition hover:text-slate-700">
                        <span>Akun demo</span>
                        <x-icon name="chevron-down" class="h-3.5 w-3.5 transition group-open:rotate-180" />
                    </summary>
                    <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                        <div class="flex items-center justify-between gap-3">
                            <span>Siswa operator</span>
                            <span class="chip">siswa.x1 / password</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Guru supervisor</span>
                            <span class="chip">guru.dewi / password</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Superadmin</span>
                            <span class="chip">superadmin / password</span>
                        </div>
                        <p class="pt-1 text-[10px] leading-relaxed text-slate-400">PIN guru default: <span class="font-mono font-medium text-slate-500">123456</span></p>
                    </div>
                </details>

                <p class="mt-6 text-center text-[10px] text-slate-400">
                    BESTCO &copy; {{ now()->year }} &bull; Teaching Factory MPLB
                </p>
            </div>
        </section>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const open = document.getElementById('eye-open');
            const closed = document.getElementById('eye-closed');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            open.classList.toggle('hidden', isHidden);
            closed.classList.toggle('hidden', ! isHidden);
        }
    </script>
</body>
</html>
