@php
    $initialToasts = [];
    if (session()->has('success')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => session('success'),
        ];
    }
    if (session()->has('error')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'danger',
            'title' => 'Perhatian',
            'message' => session('error'),
        ];
    }
    if (session()->has('warning')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'warning',
            'title' => 'Peringatan',
            'message' => session('warning'),
        ];
    }
    if (session()->has('info')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'info',
            'title' => 'Informasi',
            'message' => session('info'),
        ];
    }
@endphp

<div x-data="bestcoToastManager(@js($initialToasts))"
    x-init="init()"
    class="fixed top-4 right-4 z-[99999] flex flex-col gap-2 w-full max-w-[330px] pointer-events-none p-2 sm:p-0"
    aria-live="polite">

    <!-- Tombol Tutup Semua jika ada > 1 notifikasi bersamaan -->
    <template x-if="visibleCount > 1">
        <div class="flex justify-end pointer-events-auto pr-1">
            <button type="button" @click="clearAll()"
                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/90 px-2.5 py-0.5 text-[10.5px] font-medium text-slate-600 shadow-sm backdrop-blur transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-300 dark:hover:bg-slate-700"
                title="Tutup semua notifikasi aktif">
                <span>Tutup semua</span>
                <span class="rounded bg-slate-100 px-1 text-[9px] font-bold dark:bg-slate-700" x-text="visibleCount"></span>
                <x-icon name="x" class="h-2.5 w-2.5" />
            </button>
        </div>
    </template>

    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
            x-transition:enter="transform ease-out duration-250 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-3 scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 sm:translate-x-3"
            @mouseenter="pauseTimer(toast)"
            @mouseleave="resumeTimer(toast)"
            class="pointer-events-auto relative w-full overflow-hidden rounded-xl border bg-white/95 p-2.5 px-3 shadow-lg backdrop-blur-md transition-all duration-200 dark:bg-slate-900/95"
            :class="{
                'border-emerald-200/90 shadow-emerald-500/10 dark:border-emerald-800/80': toast.type === 'success',
                'border-rose-200/90 shadow-rose-500/10 dark:border-rose-800/80': toast.type === 'danger' || toast.type === 'error',
                'border-amber-200/90 shadow-amber-500/10 dark:border-amber-800/80': toast.type === 'warning',
                'border-sky-200/90 shadow-sky-500/10 dark:border-sky-800/80': toast.type === 'info' || toast.type === 'primary',
                'scale-[1.03] ring-2 ring-rose-400/50 shadow-xl': toast.pulsing
            }">

            <div class="flex items-start gap-2.5">
                <!-- Icon Sesuai Type (Ukuran Ramping) -->
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border mt-0.5"
                    :class="{
                        'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-400': toast.type === 'success',
                        'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/60 dark:border-rose-800 dark:text-rose-400': toast.type === 'danger' || toast.type === 'error',
                        'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/60 dark:border-amber-800 dark:text-amber-400': toast.type === 'warning',
                        'bg-sky-50 text-sky-600 border-sky-200 dark:bg-sky-950/60 dark:border-sky-800 dark:text-sky-400': toast.type === 'info' || toast.type === 'primary'
                    }">
                    <template x-if="toast.type === 'success'">
                        <x-icon name="check-circle" class="h-3.5 w-3.5" />
                    </template>
                    <template x-if="toast.type === 'danger' || toast.type === 'error'">
                        <x-icon name="x-circle" class="h-3.5 w-3.5" />
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <x-icon name="spoilage" class="h-3.5 w-3.5" />
                    </template>
                    <template x-if="toast.type === 'info' || toast.type === 'primary'">
                        <x-icon name="info" class="h-3.5 w-3.5" />
                    </template>
                </div>

                <!-- Konten Pesan Ringkas -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[11.5px] font-bold leading-tight text-slate-900 dark:text-white" x-text="toast.title"></span>
                        
                        <!-- Badge Counter Duplikat saat Spam -->
                        <template x-if="toast.count > 1">
                            <span class="inline-flex items-center rounded-full px-1.5 py-0.2 text-[9.5px] font-extrabold tracking-wide"
                                :class="{
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300': toast.type === 'success',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300': toast.type === 'danger' || toast.type === 'error',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300': toast.type === 'warning',
                                    'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300': toast.type === 'info' || toast.type === 'primary'
                                }"
                                x-text="`${toast.count}×`"></span>
                        </template>
                    </div>
                    <p class="mt-0.5 text-[11px] leading-snug text-slate-600 dark:text-slate-300 break-words line-clamp-3" x-text="toast.message"></p>
                </div>

                <!-- Tombol Tutup -->
                <button type="button" @click="remove(toast.id)"
                    class="rounded p-0.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none dark:hover:bg-slate-800 dark:hover:text-slate-200 shrink-0"
                    aria-label="Tutup notifikasi">
                    <x-icon name="x" class="h-3 w-3" />
                </button>
            </div>

            <!-- Progress Bar Auto-Dismiss Ramping -->
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full transition-all duration-100 ease-linear"
                    :style="`width: ${toast.progress}%`"
                    :class="{
                        'bg-emerald-500': toast.type === 'success',
                        'bg-rose-500': toast.type === 'danger' || toast.type === 'error',
                        'bg-amber-500': toast.type === 'warning',
                        'bg-sky-500': toast.type === 'info' || toast.type === 'primary'
                    }"></div>
            </div>
        </div>
    </template>
</div>

<script>
function bestcoToastManager(initialToasts = []) {
    return {
        toasts: [],
        maxVisible: 3,

        get visibleCount() {
            return this.toasts.filter(t => t.visible).length;
        },

        init() {
            // Pasang helper global di window
            window.bestcoToast = {
                show: (message, type = 'info', title = null, duration = 3800) => {
                    this.add({ message, type, title, duration });
                },
                success: (message, title = 'Berhasil', duration = 3800) => {
                    this.add({ message, type: 'success', title, duration });
                },
                error: (message, title = 'Perhatian', duration = 4200) => {
                    this.add({ message, type: 'danger', title, duration });
                },
                warning: (message, title = 'Peringatan', duration = 3800) => {
                    this.add({ message, type: 'warning', title, duration });
                },
                info: (message, title = 'Informasi', duration = 3800) => {
                    this.add({ message, type: 'info', title, duration });
                },
                clear: () => {
                    this.clearAll();
                }
            };

            // Muat notifikasi session awal jika ada
            if (Array.isArray(initialToasts) && initialToasts.length > 0) {
                initialToasts.forEach(t => this.add(t));
            }

            // Dengarkan custom event dari browser / Livewire
            window.addEventListener('bestco-toast', (event) => {
                if (event.detail) {
                    this.add(event.detail);
                }
            });

            window.addEventListener('toast', (event) => {
                if (event.detail) {
                    this.add(event.detail);
                }
            });
        },

        add(payload) {
            const rawMessage = (payload.message || '').toString().trim();
            if (!rawMessage) return;

            const type = (payload.type === 'error') ? 'danger' : (payload.type || 'info');
            
            let defaultTitle = 'Informasi';
            if (type === 'success') defaultTitle = 'Berhasil';
            else if (type === 'danger') defaultTitle = 'Perhatian';
            else if (type === 'warning') defaultTitle = 'Peringatan';

            const title = payload.title || defaultTitle;
            const duration = payload.duration || 3800;

            // 1. DEDUPLIKASI / COALESCING: Cek jika pesan serupa sudah aktif ditampilkan
            const existing = this.toasts.find(t => t.visible && t.message === rawMessage && t.type === type);
            if (existing) {
                // Tambahkan hitungan kali spam (2×, 3×, 4×, ...)
                existing.count = (existing.count || 1) + 1;
                // Reset waktu countdown
                existing.remaining = duration;
                existing.progress = 100;
                
                // Beri efek getar / pulse halus agar pengguna tahu kliknya direspons
                existing.pulsing = true;
                clearTimeout(existing._pulseTimer);
                existing._pulseTimer = setTimeout(() => {
                    existing.pulsing = false;
                }, 280);

                // Pastikan timer jalan
                if (!existing.timer) {
                    this.startTimer(existing);
                }
                return;
            }

            // 2. BATAS MAKSIMUM TOAST (CAPPING): Cegah layar tertutup tumpukan notifikasi
            const visibleToasts = this.toasts.filter(t => t.visible);
            if (visibleToasts.length >= this.maxVisible) {
                // Hapus toast tertua agar ruang tetap lega
                const oldest = visibleToasts[visibleToasts.length - 1];
                this.remove(oldest.id, true);
            }

            const id = payload.id || 'toast_' + Math.random().toString(36).substring(2, 9);
            const toast = {
                id,
                type,
                title,
                message: rawMessage,
                duration,
                remaining: duration,
                progress: 100,
                count: 1,
                pulsing: false,
                visible: true,
                timer: null,
                _pulseTimer: null,
                startTime: Date.now()
            };

            this.toasts.unshift(toast);
            this.startTimer(toast);
        },

        startTimer(toast) {
            const step = 50;
            if (toast.timer) {
                clearInterval(toast.timer);
            }
            
            toast.timer = setInterval(() => {
                toast.remaining -= step;
                toast.progress = Math.max(0, (toast.remaining / toast.duration) * 100);

                if (toast.remaining <= 0) {
                    this.remove(toast.id);
                }
            }, step);
        },

        pauseTimer(toast) {
            if (toast.timer) {
                clearInterval(toast.timer);
                toast.timer = null;
            }
        },

        resumeTimer(toast) {
            if (!toast.timer && toast.remaining > 0) {
                this.startTimer(toast);
            }
        },

        remove(id, immediate = false) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                const toast = this.toasts[index];
                if (toast.timer) {
                    clearInterval(toast.timer);
                    toast.timer = null;
                }
                if (toast._pulseTimer) {
                    clearTimeout(toast._pulseTimer);
                }
                toast.visible = false;
                
                if (immediate) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                } else {
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 220);
                }
            }
        },

        clearAll() {
            this.toasts.forEach(t => {
                if (t.timer) clearInterval(t.timer);
                if (t._pulseTimer) clearTimeout(t._pulseTimer);
                t.visible = false;
            });
            setTimeout(() => {
                this.toasts = [];
            }, 220);
        }
    };
}
</script>
