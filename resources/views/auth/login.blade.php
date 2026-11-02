<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Masuk — BESTCO Teaching Factory</title>

    @include('partials.theme-script')

    <link rel="icon" type="image/png" href="{{ asset('img/best.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/best.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Tema dinamis dimuat setelah CSS bawaan agar warnanya tidak tertimpa -->
    <style>
        {!! app(\App\Services\Theme\DynamicThemeService::class)->getCssVariables() !!}
    </style>
</head>
<body class="min-h-full bg-slate-100/90 font-sans text-slate-800 antialiased selection:bg-brand-500 selection:text-white relative flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-8">

    <!-- Toggle tema terang / gelap -->
    <button type="button" id="login-theme-toggle"
        class="fixed right-4 top-4 z-30 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 sm:right-5 sm:top-5"
        title="Ganti tema" aria-label="Ganti tema" aria-pressed="false">
        <span class="theme-icon-moon">
            <x-icon name="moon" class="h-4 w-4" />
        </span>
        <span class="theme-icon-sun">
            <x-icon name="sun" class="h-4 w-4" />
        </span>
    </button>

    <!-- Latar Belakang Halus & Bersih -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-32 -left-32 h-[420px] w-[420px] rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 h-[420px] w-[420px] rounded-full bg-slate-400/10 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    </div>

    <!-- Unified Floating Container Card (Proporsi Seimbang 50:50) -->
    <main class="relative z-10 w-full max-w-4xl xl:max-w-5xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-300/30 grid lg:grid-cols-2 lg:min-h-[580px]">

        <!-- ============================================================= -->
        <!-- SISI KIRI: IDENTITAS & ILUSTRASI WORKSTATION KASIR SEIMBANG    -->
        <!-- ============================================================= -->
        <section class="login-hero hidden lg:flex flex-col justify-between p-8 xl:p-12 text-white relative overflow-hidden border-r border-white/10"
            style="background-image: linear-gradient(135deg, #020617 0%, #0f172a 55%, #1e1b4b 100%);">
            <!-- Ambient Lighting -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 h-56 w-56 rounded-full bg-brand-500/15 blur-3xl pointer-events-none"></div>

            <!-- Logo & Brand Header -->
            <div class="relative z-10">
                <x-bestco-logo theme="dark" size="lg" />
            </div>

            <!-- Ilustrasi Vektor Simetris & Seimbang -->
            <div class="relative z-10 my-auto py-4">
                <div class="relative mx-auto w-full max-w-[340px]">
                    <svg viewBox="0 0 360 240" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto drop-shadow-xl">
                        <defs>
                            <linearGradient id="posMonitorGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1e293b"/>
                                <stop offset="100%" stop-color="#0f172a"/>
                            </linearGradient>
                            <linearGradient id="accentBlueGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#3b82f6"/>
                                <stop offset="100%" stop-color="#2563eb"/>
                            </linearGradient>
                            <linearGradient id="softReceipt" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#ffffff"/>
                                <stop offset="100%" stop-color="#f8fafc"/>
                            </linearGradient>
                        </defs>

                        <!-- Bayangan Meja Kerja Simetris -->
                        <ellipse cx="180" cy="220" rx="140" ry="10" fill="#000000" fill-opacity="0.3"/>

                        <!-- Stand Monitor Aluminium (Simetris di Sumbu X=180) -->
                        <path d="M162 212 L170 162 L190 162 L198 212 Z" fill="#334155" stroke="#475569" stroke-width="1"/>
                        <ellipse cx="180" cy="212" rx="38" ry="6" fill="#1e293b" stroke="#475569" stroke-width="1.2"/>

                        <!-- Bezel Monitor Utama (Tepat di Tengah: X=60 s/d 300, Width=240) -->
                        <rect x="60" y="30" width="240" height="144" rx="12" fill="#090d16" stroke="#334155" stroke-width="1.8"/>

                        <!-- Layar Display POS (Tepat di Tengah: X=67 s/d 293, Width=226) -->
                        <rect x="67" y="37" width="226" height="130" rx="8" fill="url(#posMonitorGrad)"/>

                        <!-- Top Navbar Layar -->
                        <rect x="67" y="37" width="226" height="22" rx="8" fill="#1e293b"/>
                        <circle cx="81" cy="48" r="3" fill="#10b981"/>
                        <text x="91" y="51" fill="#f8fafc" font-size="8" font-family="Plus Jakarta Sans, sans-serif" font-weight="700">BESTCO POS</text>
                        <rect x="233" y="43" width="50" height="10" rx="3" fill="url(#accentBlueGrad)"/>
                        <text x="241" y="50.5" fill="#ffffff" font-size="6.5" font-family="Plus Jakarta Sans, sans-serif" font-weight="600">SHIFT AKTIF</text>

                        <!-- Konten POS Kasir -->
                        <g opacity="0.95">
                            <!-- Baris Item 1 -->
                            <rect x="79" y="68" width="8" height="8" rx="2" fill="#3b82f6" fill-opacity="0.3"/>
                            <text x="93" y="75" fill="#cbd5e1" font-size="7.5" font-family="Plus Jakarta Sans, sans-serif" font-weight="500">Kertas HVS F4 75gr</text>
                            <text x="250" y="75" fill="#f8fafc" font-size="7.5" font-family="JetBrains Mono, monospace" font-weight="600">60.000</text>

                            <!-- Baris Item 2 -->
                            <rect x="79" y="84" width="8" height="8" rx="2" fill="#10b981" fill-opacity="0.3"/>
                            <text x="93" y="91" fill="#cbd5e1" font-size="7.5" font-family="Plus Jakarta Sans, sans-serif" font-weight="500">Fotocopy A4 x100</text>
                            <text x="250" y="91" fill="#f8fafc" font-size="7.5" font-family="JetBrains Mono, monospace" font-weight="600">22.500</text>

                            <!-- Garis Pemisah -->
                            <line x1="79" y1="102" x2="281" y2="102" stroke="#334155" stroke-dasharray="2 2"/>

                            <!-- Total -->
                            <text x="79" y="117" fill="#94a3b8" font-size="7.5" font-family="Plus Jakarta Sans, sans-serif">Total Transaksi</text>
                            <text x="230" y="118" fill="#38bdf8" font-size="9.5" font-family="JetBrains Mono, monospace" font-weight="700">Rp 82.500</text>

                            <!-- Tombol Kasir -->
                            <rect x="79" y="130" width="98" height="18" rx="4" fill="#334155"/>
                            <text x="108" y="141.5" fill="#cbd5e1" font-size="7" font-family="Plus Jakarta Sans, sans-serif" font-weight="600">Tunai (Cash)</text>

                            <rect x="183" y="130" width="98" height="18" rx="4" fill="url(#accentBlueGrad)"/>
                            <text x="211" y="141.5" fill="#ffffff" font-size="7" font-family="Plus Jakarta Sans, sans-serif" font-weight="700">Bayar QRIS</text>
                        </g>

                        <!-- Perangkat Kasir Kiri: Card Reader / EDC Pad -->
                        <g transform="translate(18, 168)">
                            <rect x="0" y="0" width="34" height="24" rx="4" fill="#1e293b" stroke="#334155" stroke-width="1"/>
                            <rect x="5" y="4" width="24" height="7" rx="2" fill="#10b981" fill-opacity="0.2"/>
                            <circle cx="17" cy="17" r="3" fill="#38bdf8"/>
                        </g>

                        <!-- Perangkat Kasir Kanan: Printer Thermal & Struk -->
                        <g transform="translate(308, 155)">
                            <!-- Body Printer Mini -->
                            <rect x="0" y="14" width="36" height="26" rx="4" fill="#1e293b" stroke="#334155" stroke-width="1"/>
                            <!-- Slot Kertas -->
                            <rect x="4" y="17" width="28" height="2.5" rx="1" fill="#0f172a"/>
                            <circle cx="8" cy="28" r="2" fill="#10b981"/>
                            <!-- Kertas Struk Keluar -->
                            <path d="M6 16 L6 -10 L10 -8 L14 -10 L18 -8 L22 -10 L26 -8 L30 -10 L30 16 Z" fill="url(#softReceipt)" stroke="#cbd5e1" stroke-width="0.7"/>
                            <line x1="10" y1="-2" x2="26" y2="-2" stroke="#94a3b8" stroke-dasharray="1 1"/>
                            <line x1="10" y1="4" x2="26" y2="4" stroke="#64748b"/>
                            <line x1="10" y1="9" x2="22" y2="9" stroke="#64748b"/>
                        </g>
                    </svg>
                </div>

                <!-- Teks Pengenal Singkat & Elegan -->
                <div class="mt-3 text-center">
                    <h2 class="text-base font-semibold tracking-tight text-white xl:text-lg">
                        Teaching Factory MPLB
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Sistem Informasi Kasir & Operasional Bisnis
                    </p>
                </div>
            </div>

            <!-- Footer Panel Kiri -->
            <div class="relative z-10 border-t border-white/10 pt-4 flex items-center justify-between text-[11px] text-slate-500">
                <!-- <span>SMK Negeri 1 Ciamis</span> -->
            </div>
        </section>

        <!-- ============================================================= -->
        <!-- SISI KANAN: FORM LOGIN TERPUSAT & SEIMBANG SECARA VERTIKAL    -->
        <!-- ============================================================= -->
        <section class="flex flex-col justify-between p-8 sm:p-12 xl:p-14 bg-white">
            <!-- Brand Mobile (Hanya di layar kecil) -->
            <div class="mb-6 lg:hidden">
                <x-bestco-logo theme="light" size="md" />
            </div>

            <!-- Form Content (Vertically Centered: my-auto) -->
            <div class="my-auto w-full max-w-sm mx-auto py-2">
                <!-- Form Title -->
                <div class="mb-6">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Masuk ke Sistem</h1>
                    <p class="mt-1.5 text-xs sm:text-sm text-slate-500">Masukkan kredensial akun Anda untuk memulai sesi kerja.</p>
                </div>

                <!-- Notifikasi Error Login -->
                @if($errors->has('login'))
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800">
                        <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" />
                        <span class="flex-1 leading-relaxed font-medium">{{ $errors->first('login') }}</span>
                    </div>
                @endif

                <!-- Form Login Inti -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="form-label text-xs font-semibold text-slate-700">Username, NIP, atau NISN</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="user" class="h-4 w-4" />
                            </div>
                            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                                placeholder="Masukkan username, NIP, atau NISN"
                                class="input !pl-10 text-xs sm:text-sm !h-11">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="form-label text-xs font-semibold text-slate-700">Password</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <x-icon name="lock" class="h-4 w-4" />
                            </div>
                            <input type="password" id="password" name="password" required autocomplete="current-password"
                                placeholder="Masukkan password akun"
                                class="input !pl-10 !pr-10 text-xs sm:text-sm !h-11">
                            <button type="button" onclick="togglePassword()" aria-label="Tampilkan password"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                <x-icon name="eye" id="eye-open" class="h-4 w-4" />
                                <x-icon name="eye-off" id="eye-closed" class="hidden h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600 select-none">
                            <input type="checkbox" name="remember" class="checkbox">
                            <span>Ingat saya</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary !h-11 w-full text-sm font-semibold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                        <span>Masuk ke Akun</span>
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>
            </div>

            <!-- Footer Hak Cipta -->
            <p class="text-center text-[11px] text-slate-400 border-t border-slate-100 pt-4">
                BESTCO &copy; {{ now()->year }} &bull; SMK NEGERI 1 CIAMIS
            </p>
        </section>
    </main>

    <!-- Script Password Toggle Sederhana -->
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

    <!-- Sinkronisasi ikon toggle tema -->
    <script>
        (() => {
            const button = document.getElementById('login-theme-toggle');

            if (!button || !window.BestcoTheme) {
                return;
            }

            const sync = () => {
                const isDark = window.BestcoTheme.isDark;
                const label = isDark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap';

                button.title = label;
                button.setAttribute('aria-label', label);
                button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            };

            button.addEventListener('click', () => {
                window.BestcoTheme.toggle();
                sync();
            });

            window.addEventListener('bestco-theme-changed', sync);
            sync();
        })();
    </script>
</body>
</html>
