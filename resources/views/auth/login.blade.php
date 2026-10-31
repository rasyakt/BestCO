<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Masuk — BESTCO Teaching Factory MPLB</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">

        <!-- Brand panel -->
        <section class="relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14"
            style="background:
                radial-gradient(90% 60% at 15% 0%, color-mix(in srgb, var(--color-primary-500) 30%, transparent) 0%, transparent 60%),
                radial-gradient(70% 50% at 90% 100%, color-mix(in srgb, var(--color-primary-400) 18%, transparent) 0%, transparent 60%),
                linear-gradient(150deg, var(--color-primary-950) 0%, #060d1c 100%);">

            <!-- Decorative grid -->
            <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
                style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 64px 64px; mask-image: radial-gradient(ellipse at 30% 30%, black 0%, transparent 75%);"></div>

            <div class="relative flex items-center gap-3">
                <span class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sm font-extrabold tracking-wider text-brand-900 shadow-xl shadow-black/30">
                    BC
                    <span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-brand-950 bg-emerald-400"></span>
                </span>
                <div class="leading-tight">
                    <span class="block text-base font-extrabold tracking-[0.1em]">BESTCO</span>
                    <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-[0.22em] text-white/45">Teaching Factory MPLB</span>
                </div>
            </div>

            <div class="relative max-w-xl">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white/70 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Sistem Operasional Terpadu
                </div>

                <h1 class="text-4xl font-extrabold leading-[1.12] tracking-tight xl:text-[2.9rem]">
                    Operasional bisnis sekolah,
                    <span class="bg-gradient-to-r from-white via-brand-200 to-brand-400 bg-clip-text text-transparent">dalam satu kendali.</span>
                </h1>

                <p class="mt-5 max-w-lg text-sm leading-relaxed text-white/55">
                    Kasir POS, persediaan ganda, presensi piket, hingga laporan keuangan double-entry —
                    dirancang untuk praktik nyata siswa MPLB.
                </p>

                <div class="mt-9 space-y-3.5">
                    @foreach([
                        ['icon' => 'pos', 'title' => 'Kasir POS & Struk Thermal', 'desc' => 'Transaksi cepat dengan tiered pricing dan approval guru.'],
                        ['icon' => 'package', 'title' => 'Dual-Inventory Terkontrol', 'desc' => 'Stok etalase dan bahan baku jasa terpisah, HPP akurat.'],
                        ['icon' => 'shield', 'title' => 'Akuntansi Double-Entry', 'desc' => 'Setiap transaksi otomatis terjurnal dan dapat diaudit.'],
                    ] as $feature)
                        <div class="flex items-start gap-3.5 rounded-2xl border border-white/[0.08] bg-white/[0.04] p-3.5 backdrop-blur-sm">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/[0.08] text-brand-300">
                                <x-icon :name="$feature['icon']" class="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <p class="text-xs font-bold text-white">{{ $feature['title'] }}</p>
                                <p class="mt-0.5 text-[11px] leading-relaxed text-white/40">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative flex items-center justify-between border-t border-white/10 pt-5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/30">
                <span>SMK Teaching Factory</span>
                <span>Laboratorium MPLB</span>
            </div>
        </section>

        <!-- Form panel -->
        <section class="flex items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-[420px]">

                <!-- Mobile brand -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-900 text-sm font-extrabold tracking-wider text-white shadow-lg shadow-brand-900/20">BC</span>
                    <div class="leading-tight">
                        <span class="block text-base font-extrabold tracking-[0.1em] text-slate-900">BESTCO</span>
                        <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400">Teaching Factory MPLB</span>
                    </div>
                </div>

                <div class="mb-7">
                    <p class="mb-2 text-[10px] font-extrabold uppercase tracking-[0.18em] text-brand-700">Portal Login</p>
                    <h2 class="text-[1.75rem] font-extrabold leading-tight tracking-tight text-slate-900">Selamat datang kembali</h2>
                    <p class="mt-2 text-[13px] leading-relaxed text-slate-500">Masuk untuk melanjutkan operasional Teaching Factory hari ini.</p>
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
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <x-icon name="user" class="h-4 w-4" />
                                </span>
                                <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                                    placeholder="Contoh: siswa.x1 atau guru.dewi"
                                    class="input pl-10">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <x-icon name="lock" class="h-4 w-4" />
                                </span>
                                <input type="password" id="password" name="password" required autocomplete="current-password"
                                    placeholder="Masukkan password"
                                    class="input px-10">
                                <button type="button" onclick="togglePassword()" aria-label="Tampilkan password"
                                    class="absolute right-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                    <x-icon name="eye" id="eye-open" class="h-4 w-4" />
                                    <x-icon name="eye-off" id="eye-closed" class="hidden h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                                <input type="checkbox" name="remember" class="checkbox">
                                Ingat saya di perangkat ini
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg mt-2 w-full">
                            Masuk ke Workspace
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </form>
                </div>

                <details class="group mt-4 rounded-2xl border border-slate-200/80 bg-white/60 px-4 py-3 backdrop-blur-sm">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-[11px] font-bold text-slate-500 transition hover:text-slate-700">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="help" class="h-3.5 w-3.5" />
                            Akun demo untuk pengujian
                        </span>
                        <x-icon name="chevron-down" class="h-3.5 w-3.5 transition group-open:rotate-180" />
                    </summary>
                    <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Siswa Operator</span>
                            <span class="chip">siswa.x1 / password</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Guru Supervisor</span>
                            <span class="chip">guru.dewi / password</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">Superadmin</span>
                            <span class="chip">superadmin / password</span>
                        </div>
                        <p class="pt-1 text-[10px] leading-relaxed text-slate-400">PIN approval guru supervisor default: <span class="font-mono font-semibold text-slate-500">123456</span></p>
                    </div>
                </details>

                <p class="mt-6 text-center text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
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
