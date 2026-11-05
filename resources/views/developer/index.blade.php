@extends('layouts.app', ['title' => 'Developer Console — ' . $identity['app_name']])

@section('content')
<div class="space-y-6" x-data="developerConsole()">

    <!-- Header Halaman -->
    <x-page-header
        title="Developer Console"
        subtitle="Panel kendali teknis internal, kustomisasi identitas aplikasi, pemantauan server, dan inspeksi log error.">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <span class="badge badge-purple text-xs font-semibold px-2.5 py-1">
                    <x-icon name="shield" class="h-3.5 w-3.5" />
                    Khusus Superadmin
                </span>
                <span class="badge {{ $stats['runtime']['environment'] === 'production' ? 'badge-danger' : 'badge-success' }} text-xs font-mono px-2.5 py-1 uppercase">
                    {{ $stats['runtime']['environment'] }}
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
            <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Navigasi Tab / Segmented Menu -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-xs font-medium shadow-2xs dark:border-slate-800 dark:bg-slate-900">
            <button type="button" @click="activeTab = 'branding'"
                :class="activeTab === 'branding' ? 'bg-brand-500 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 transition-all">
                <x-icon name="shop" class="h-4 w-4" />
                <span>Identitas & Logo</span>
            </button>
            <button type="button" @click="activeTab = 'stats'"
                :class="activeTab === 'stats' ? 'bg-brand-500 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 transition-all">
                <x-icon name="cpu" class="h-4 w-4" />
                <span>Statistik Sistem</span>
            </button>
            <button type="button" @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'bg-brand-500 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                class="inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 transition-all">
                <x-icon name="terminal" class="h-4 w-4" />
                <span>10 Error Log Terbaru</span>
                @if(count($logs) > 0)
                    <span class="ml-1 rounded-full bg-rose-500 px-1.5 py-0.2 text-[10px] font-bold text-white leading-none">
                        {{ count($logs) }}
                    </span>
                @endif
            </button>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400 font-mono">
                Log File: {{ $logFileSize }}
            </span>
            <a href="{{ route('dev.logs.download') }}" class="btn btn-secondary btn-sm" title="Unduh seluruh file laravel.log">
                <x-icon name="download" class="h-3.5 w-3.5" />
                <span>Unduh Log</span>
            </a>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 1: IDENTITAS & LOGO WEBSITE (BRANDING)                    -->
    <!-- ============================================================= -->
    <div x-show="activeTab === 'branding'" x-cloak x-transition.opacity>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Form Pengaturan -->
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('dev.identity.update') }}" enctype="multipart/form-data" class="card divide-y divide-slate-100 dark:divide-slate-800">
                    @csrf
                    @method('PUT')

                    <div class="p-6">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/50 dark:text-brand-400">
                                <x-icon name="store" class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Identitas & Logo Aplikasi</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Atur nama website dan logo yang tampil di header navigasi, favicon browser, dan halaman login.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-6">
                        <!-- Nama Website -->
                        <div>
                            <label for="app_name" class="form-label">Nama Website / Aplikasi <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                id="app_name"
                                name="app_name"
                                value="{{ old('app_name', $identity['app_name']) }}"
                                maxlength="50"
                                required
                                class="input @error('app_name') border-rose-400 focus:border-rose-500 @enderror"
                                placeholder="Contoh: BESTCO">
                            <p class="form-hint">Akan tampil di tab browser, header navigasi, dan halaman login.</p>
                            @error('app_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tagline Website -->
                        <div>
                            <label for="app_tagline" class="form-label">Tagline / Subtitle Aplikasi</label>
                            <input
                                type="text"
                                id="app_tagline"
                                name="app_tagline"
                                value="{{ old('app_tagline', $identity['app_tagline']) }}"
                                maxlength="100"
                                class="input @error('app_tagline') border-rose-400 focus:border-rose-500 @enderror"
                                placeholder="Contoh: Teaching Factory MPLB">
                            <p class="form-hint">Teks kecil di bawah nama aplikasi pada header navigasi dan footer.</p>
                            @error('app_tagline')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Footer Text -->
                        <div>
                            <label for="footer_text" class="form-label">Teks Deskripsi Footer</label>
                            <input
                                type="text"
                                id="footer_text"
                                name="footer_text"
                                value="{{ old('footer_text', $identity['footer_text']) }}"
                                maxlength="150"
                                class="input @error('footer_text') border-rose-400 focus:border-rose-500 @enderror"
                                placeholder="Contoh: Sistem kasir dan persediaan sekolah">
                            <p class="form-hint">Teks penjelasan di bagian kanan bawah footer aplikasi.</p>
                            @error('footer_text')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Upload Logo Website -->
                        <div>
                            <label class="form-label">Upload File Logo Baru</label>
                            <div class="mt-1 flex items-center gap-4">
                                <label class="btn btn-secondary cursor-pointer inline-flex items-center gap-2">
                                    <x-icon name="upload" class="h-4 w-4" />
                                    <span>Pilih File Gambar</span>
                                    <input type="file" name="app_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon" class="hidden" @change="previewLogoFile($event)">
                                </label>
                                <span class="text-xs text-slate-500" x-text="logoFileName || 'Maksimal 2 MB (PNG, JPG, SVG, WEBP, ICO)'"></span>
                            </div>
                            @error('app_logo')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between bg-slate-50 px-6 py-4 dark:bg-slate-900/50">
                        @if($identity['has_custom_logo'])
                            <button type="button" @click="confirmResetLogo()" class="btn btn-sm text-xs border border-slate-200 text-rose-600 hover:bg-rose-50 dark:border-slate-700 dark:hover:bg-rose-950/40">
                                <x-icon name="refresh" class="h-3.5 w-3.5" />
                                <span>Reset Logo ke Default</span>
                            </button>
                        @else
                            <span class="text-[11px] text-slate-400">Menggunakan logo monogram SVG bawaan sistem.</span>
                        @endif

                        <button type="submit" class="btn btn-primary text-xs">
                            <x-icon name="check" class="h-4 w-4" />
                            <span>Simpan Identitas Website</span>
                        </button>
                    </div>
                </form>

                <!-- Form Tersembunyi Reset Logo -->
                <form id="form-reset-logo" method="POST" action="{{ route('dev.identity.resetLogo') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>

            <!-- Preview Panel -->
            <div class="space-y-4">
                <div class="card p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Live Preview Branding</h3>
                    <p class="mt-1 text-xs text-slate-400">Tampilan logo & nama aplikasi saat ini:</p>

                    <!-- Preview di Sidebar/Header (Terang) -->
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[10px] font-semibold text-slate-400 mb-2 uppercase">Tema Terang (Light Mode)</p>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-center">
                            <x-bestco-logo theme="light" size="md" />
                        </div>
                    </div>

                    <!-- Preview di Halaman Login (Gelap) -->
                    <div class="mt-3 rounded-xl border border-slate-800 bg-slate-950 p-4 shadow-sm">
                        <p class="text-[10px] font-semibold text-slate-400 mb-2 uppercase">Tema Gelap (Dark Mode)</p>
                        <div class="p-3 bg-slate-900 rounded-lg border border-slate-800 flex items-center justify-center">
                            <x-bestco-logo theme="dark" size="md" />
                        </div>
                    </div>

                    <!-- Informasi Status Logo -->
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Tipe Logo Aktif:</span>
                            <span class="badge {{ $identity['has_custom_logo'] ? 'badge-brand' : 'badge-neutral' }}">
                                {{ $identity['has_custom_logo'] ? 'Custom Image' : 'SVG Monogram Default' }}
                            </span>
                        </div>
                        @if($identity['has_custom_logo'])
                            <div class="mt-2 text-[11px] text-slate-400 font-mono truncate" title="{{ $identity['app_logo'] }}">
                                File: {{ basename($identity['app_logo']) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 2: STATISTIK SISTEM LENGKAP                               -->
    <!-- ============================================================= -->
    <div x-show="activeTab === 'stats'" x-cloak x-transition.opacity class="space-y-6">

        <!-- Grid 1: Server & Runtime Overview -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-medium text-slate-500">PHP Version</p>
                        <p class="mt-1 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $stats['runtime']['php_version'] }}</p>
                    </div>
                    <span class="stat-icon text-brand-600 bg-brand-50 dark:bg-brand-950 dark:text-brand-400">
                        <x-icon name="code" class="h-4 w-4" />
                    </span>
                </div>
                <div class="mt-2.5 flex items-center gap-1.5 text-[11px] text-slate-400">
                    <span>Laravel: <strong class="text-slate-600 dark:text-slate-300 font-mono">v{{ $stats['runtime']['laravel_version'] }}</strong></span>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-medium text-slate-500">Database Engine</p>
                        <p class="mt-1 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $stats['database']['driver'] }}</p>
                    </div>
                    <span class="stat-icon text-emerald-600 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-400">
                        <x-icon name="database" class="h-4 w-4" />
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ $stats['database']['table_count'] }} tabel</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400 font-medium">{{ $stats['database']['size_mb'] }}</span>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-medium text-slate-500">Memory Usage</p>
                        <p class="mt-1 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $stats['server']['memory_usage'] }}</p>
                    </div>
                    <span class="stat-icon text-sky-600 bg-sky-50 dark:bg-sky-950 dark:text-sky-400">
                        <x-icon name="cpu" class="h-4 w-4" />
                    </span>
                </div>
                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Peak: {{ $stats['server']['peak_memory'] }}</span>
                    <span>Limit: <strong class="text-slate-600 dark:text-slate-300">{{ $stats['server']['memory_limit'] }}</strong></span>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-medium text-slate-500">Disk Storage</p>
                        <p class="mt-1 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $stats['server']['disk_free'] }} Free</p>
                    </div>
                    <span class="stat-icon text-amber-600 bg-amber-50 dark:bg-amber-950 dark:text-amber-400">
                        <x-icon name="server" class="h-4 w-4" />
                    </span>
                </div>
                <div class="mt-2.5">
                    @if($stats['server']['disk_used_percent'] !== null)
                        <div class="flex items-center justify-between text-[10px] text-slate-400 mb-1">
                            <span>Total: {{ $stats['server']['disk_total'] }}</span>
                            <span>{{ $stats['server']['disk_used_percent'] }}% Terpakai</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden dark:bg-slate-800">
                            <div class="h-full bg-brand-500 rounded-full" style="width: {{ $stats['server']['disk_used_percent'] }}%"></div>
                        </div>
                    @else
                        <span class="text-[11px] text-slate-400">Total: {{ $stats['server']['disk_total'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detail Lingkungan & Aplikasi -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Rincian Lingkungan Server -->
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Konfigurasi Runtime & Server</h3>
                        <p class="card-subtitle">Spesifikasi mesin dan batasan proses PHP saat ini.</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Sistem Operasi</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $stats['server']['os'] }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Web Server Software</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $stats['server']['server_software'] }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Timezone & Waktu Server</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $stats['runtime']['server_time'] }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Max Execution Time</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $stats['server']['max_execution_time'] }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Upload Max / Post Max</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $stats['server']['upload_max_filesize'] }} / {{ $stats['server']['post_max_size'] }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Driver Cache / Session / Queue</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">
                            {{ $stats['drivers']['cache_driver'] }} &bull; {{ $stats['drivers']['session_driver'] }} &bull; {{ $stats['drivers']['queue_connection'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rincian Data Aplikasi BESTCO -->
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Statistik Data Aplikasi</h3>
                        <p class="card-subtitle">Jumlah rekaman data entitas bisnis dan operasional.</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Total Akun Pengguna</span>
                        <div class="flex items-center gap-1.5 font-mono">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $stats['app_data']['users']['total'] }} akun</span>
                            <span class="text-[10px] text-slate-400">({{ $stats['app_data']['users']['active'] }} aktif)</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Komposisi Role Akun</span>
                        <div class="flex items-center gap-1 text-[10px]">
                            <span class="badge badge-purple">Super: {{ $stats['app_data']['users']['superadmin'] }}</span>
                            <span class="badge badge-brand">Admin: {{ $stats['app_data']['users']['admin'] }}</span>
                            <span class="badge badge-info">Guru: {{ $stats['app_data']['users']['piket_guru'] }}</span>
                            <span class="badge badge-neutral">Siswa: {{ $stats['app_data']['users']['piket_siswa'] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Transaksi Kasir Selesai</span>
                        <div class="flex items-center gap-2 font-mono">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $stats['app_data']['transactions']['completed'] }} trx</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">(Rp {{ number_format($stats['app_data']['transactions']['total_revenue'], 0, ',', '.') }})</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Katalog Barang & Layanan</span>
                        <div class="flex items-center gap-1.5 font-mono text-slate-800 dark:text-slate-200">
                            <span>{{ $stats['app_data']['inventory']['retail'] }} Etalase</span> &bull;
                            <span>{{ $stats['app_data']['inventory']['raw_material'] }} Bahan Baku</span> &bull;
                            <span>{{ $stats['app_data']['inventory']['services'] }} Layanan</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Shift Kasir Tercatat</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">
                            {{ $stats['app_data']['shifts']['total'] }} shift ({{ $stats['app_data']['shifts']['open'] }} sedang aktif)
                        </span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-slate-500">Pembukuan Akuntansi (Double-Entry)</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">
                            {{ $stats['app_data']['journals']['entries'] }} Jurnal &bull; {{ $stats['app_data']['journals']['items'] }} Baris Akun
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 3: 10 ERROR LOG TERBARU & FITUR SALIN LOG                 -->
    <!-- ============================================================= -->
    <div x-show="activeTab === 'logs'" x-cloak x-transition.opacity class="space-y-4">

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">10 Entri Error Log Terbaru</h3>
                    <p class="card-subtitle">Hasil pembacaan langsung dari file <code class="font-mono text-[11px] text-slate-700 bg-slate-100 px-1 py-0.5 rounded dark:bg-slate-800 dark:text-slate-300">storage/logs/laravel.log</code>.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="confirmClearLogs()" class="btn btn-secondary btn-sm text-xs text-rose-600 hover:bg-rose-50 hover:border-rose-200 dark:hover:bg-rose-950/40" title="Kosongkan isi file log">
                        <x-icon name="trash" class="h-3.5 w-3.5 text-rose-500" />
                        <span>Bersihkan Log</span>
                    </button>
                    <a href="{{ route('dev.logs.download') }}" class="btn btn-primary btn-sm text-xs">
                        <x-icon name="download" class="h-3.5 w-3.5" />
                        <span>Unduh File Log</span>
                    </a>
                </div>
            </div>

            <!-- Form Tersembunyi Bersihkan Log -->
            <form id="form-clear-logs" method="POST" action="{{ route('dev.logs.clear') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            @if(empty($logs))
                <div class="empty-state py-12">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-icon name="check-circle" class="h-6 w-6" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-white">File Log Bersih / Tidak Ada Error</p>
                    <p class="mt-1 max-w-sm text-xs text-slate-400">Tidak ada rekaman error pada file laravel.log saat ini. Aplikasi berjalan normal.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($logs as $index => $log)
                        <div class="p-4 transition hover:bg-slate-50/50 dark:hover:bg-slate-900/30" x-data="{ expanded: false, copied: false }">
                            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="rounded bg-slate-900 px-1.5 py-0.5 font-mono text-[10px] font-bold text-white dark:bg-slate-800">
                                            #{{ $index + 1 }}
                                        </span>
                                        <span class="badge {{ in_array($log['level'], ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT']) ? 'badge-danger' : 'badge-warning' }} font-mono text-[10px] font-bold">
                                            {{ $log['level'] }}
                                        </span>
                                        <span class="font-mono text-[11px] text-slate-400">
                                            {{ $log['timestamp'] }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 uppercase font-mono">
                                            env: {{ $log['environment'] }}
                                        </span>
                                    </div>

                                    <!-- Pesan Error -->
                                    <p class="font-mono text-xs font-semibold text-rose-700 dark:text-rose-400 break-all leading-relaxed">
                                        {{ $log['message'] }}
                                    </p>
                                </div>

                                <!-- Aksi Log: Salin & Expand -->
                                <div class="flex items-center gap-1.5 shrink-0 sm:pt-0.5">
                                    <!-- Tombol Salin Log -->
                                    <button
                                        type="button"
                                        @click="copyLog(@js($log['full']), $event)"
                                        class="btn btn-sm text-xs border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 inline-flex items-center gap-1.5 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                        title="Salin pesan error dan trace ke clipboard">
                                        <span x-show="!copied" class="flex items-center gap-1">
                                            <x-icon name="copy" class="h-3.5 w-3.5 text-slate-500" />
                                            <span>Salin Log</span>
                                        </span>
                                        <span x-show="copied" x-cloak class="flex items-center gap-1 text-emerald-600 font-semibold dark:text-emerald-400">
                                            <x-icon name="check" class="h-3.5 w-3.5" />
                                            <span>Tersalin!</span>
                                        </span>
                                    </button>

                                    <!-- Tombol Detail / Trace -->
                                    @if(! empty($log['details']))
                                        <button
                                            type="button"
                                            @click="expanded = !expanded"
                                            class="btn btn-sm text-xs border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 inline-flex items-center gap-1 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                            <span x-text="expanded ? 'Tutup Trace' : 'Lihat Trace'"></span>
                                            <x-icon name="chevron-down" class="h-3 w-3 transition-transform" ::class="expanded ? 'rotate-180' : ''" />
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Stack Trace Collapsible Detail -->
                            @if(! empty($log['details']))
                                <div x-show="expanded" x-cloak x-transition.opacity class="mt-3">
                                    <div class="relative rounded-lg border border-slate-800 bg-slate-950 p-3.5 text-[11px] font-mono text-slate-300 shadow-inner overflow-x-auto max-h-96">
                                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[10px] text-slate-400">
                                            <span>Stack Trace Lengkap:</span>
                                            <button type="button" @click="copyLog(@js($log['full']), $event)" class="hover:text-white transition">
                                                Salin Seluruh Trace
                                            </button>
                                        </div>
                                        <pre class="whitespace-pre-wrap leading-relaxed text-slate-300 selection:bg-brand-600 selection:text-white">{{ $log['details'] }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>

<script>
function developerConsole() {
    return {
        activeTab: 'branding',
        logoFileName: '',

        previewLogoFile(event) {
            const file = event.target.files[0];
            if (file) {
                this.logoFileName = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
            }
        },

        confirmResetLogo() {
            window.bestcoDialog.confirm({
                title: 'Kembalikan Logo ke Default?',
                message: 'Logo aplikasi kustom akan dihapus dan digantikan kembali dengan logo monogram SVG bawaan.',
                type: 'warning',
                confirmText: 'Ya, Reset Logo',
                cancelText: 'Batal',
            }).then((confirmed) => {
                if (confirmed) {
                    document.getElementById('form-reset-logo').submit();
                }
            });
        },

        confirmClearLogs() {
            window.bestcoDialog.confirm({
                title: 'Kosongkan Seluruh Error Log?',
                message: 'Seluruh riwayat catatan error pada storage/logs/laravel.log akan dihapus permanen.',
                type: 'danger',
                confirmText: 'Ya, Kosongkan Log',
                cancelText: 'Batal',
            }).then((confirmed) => {
                if (confirmed) {
                    document.getElementById('form-clear-logs').submit();
                }
            });
        },

        copyLog(text, event) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showCopiedState(event);
                }).catch(() => {
                    this.fallbackCopy(text, event);
                });
            } else {
                this.fallbackCopy(text, event);
            }
        },

        fallbackCopy(text, event) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                this.showCopiedState(event);
            } catch (err) {
                console.error('Gagal menyalin:', err);
            }
            document.body.removeChild(textArea);
        },

        showCopiedState(event) {
            const btn = event.currentTarget;
            if (btn && btn.__x) {
                btn.__x.$data.copied = true;
                setTimeout(() => {
                    btn.__x.$data.copied = false;
                }, 2000);
            }
            // Trigger toast notifikasi
            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('bestco-toast', {
                    detail: {
                        type: 'success',
                        message: 'Log error berhasil disalin ke clipboard!',
                        duration: 2500
                    }
                }));
            }
        }
    };
}
</script>
@endsection

