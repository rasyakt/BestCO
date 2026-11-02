<div x-data="bestcoDialogComponent()"
    x-init="initDialog()"
    x-cloak
    @keydown.escape.window="close()"
    class="relative z-[9999]">

    <!-- Backdrop -->
    <div x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/65 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">

        <!-- Modal Dialog Card -->
        <div x-show="isOpen"
            @click.outside="if (isAlert) close()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white p-6 shadow-2xl border border-slate-200/80 text-left">

            <!-- Close icon on top right -->
            <button type="button" @click="close()"
                class="absolute right-4 top-4 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none"
                aria-label="Tutup">
                <x-icon name="x" class="h-4 w-4" />
            </button>

            <!-- Icon Header based on type -->
            <div class="flex items-center gap-4">
                <!-- Danger Icon -->
                <template x-if="dialog.type === 'danger'">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600">
                        <x-icon name="spoilage" class="h-5 w-5" />
                    </div>
                </template>

                <!-- Warning Icon -->
                <template x-if="dialog.type === 'warning'">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-600">
                        <x-icon name="spoilage" class="h-5 w-5" />
                    </div>
                </template>

                <!-- Success Icon -->
                <template x-if="dialog.type === 'success'">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600">
                        <x-icon name="check-circle" class="h-5 w-5" />
                    </div>
                </template>

                <!-- Primary / Info Icon -->
                <template x-if="dialog.type === 'primary' || dialog.type === 'info'">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-brand-200 bg-brand-50 text-brand-700">
                        <x-icon name="info" class="h-5 w-5" />
                    </div>
                </template>

                <div class="min-w-0 flex-1 pr-6">
                    <h3 class="text-base font-bold text-slate-900 leading-snug" x-text="dialog.title"></h3>
                    <p class="mt-0.5 text-xs text-slate-500" x-show="dialog.subtitle" x-text="dialog.subtitle"></p>
                </div>
            </div>

            <!-- Body Message -->
            <div class="mt-4">
                <p class="text-xs text-slate-600 leading-relaxed" x-text="dialog.message"></p>

                <!-- Detail info box if provided -->
                <template x-if="dialog.detail">
                    <div class="mt-3 rounded-lg border border-slate-100 bg-slate-50/80 p-3 text-[11px] leading-relaxed text-slate-500">
                        <p x-text="dialog.detail"></p>
                    </div>
                </template>
            </div>

            <!-- Actions Footer -->
            <div class="mt-6 flex items-center justify-end gap-2.5">
                <!-- Cancel button for Confirm dialogs -->
                <button type="button"
                    x-show="!isAlert"
                    @click="cancel()"
                    class="btn btn-secondary px-4 py-2.5 rounded-lg text-xs font-semibold flex-1 sm:flex-initial">
                    <span x-text="dialog.cancelText || 'Batal'"></span>
                </button>

                <!-- Confirm or OK button -->
                <button type="button"
                    @click="confirmAction()"
                    :class="{
                        'btn btn-danger': dialog.type === 'danger',
                        'btn btn-warning': dialog.type === 'warning',
                        'btn btn-success': dialog.type === 'success',
                        'btn btn-primary': dialog.type === 'primary' || dialog.type === 'info'
                    }"
                    class="px-5 py-2.5 rounded-lg text-xs font-semibold flex-1 sm:flex-initial shadow-xs">
                    <span x-text="dialog.confirmText || (isAlert ? 'Mengerti' : 'Lanjutkan')"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function bestcoDialogComponent() {
    return {
        isOpen: false,
        isAlert: false,
        dialog: {
            title: '',
            subtitle: '',
            message: '',
            detail: '',
            type: 'primary',
            confirmText: 'Lanjutkan',
            cancelText: 'Batal',
            onConfirm: null,
            onCancel: null,
            resolve: null
        },
        initDialog() {
            window.bestcoDialog = {
                confirm: (options) => {
                    return new Promise((resolve) => {
                        this.isAlert = false;
                        this.dialog = {
                            title: options.title || 'Konfirmasi Tindakan',
                            subtitle: options.subtitle || '',
                            message: options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                            detail: options.detail || '',
                            type: options.type || 'primary',
                            confirmText: options.confirmText || 'Lanjutkan',
                            cancelText: options.cancelText || 'Batal',
                            onConfirm: options.onConfirm || null,
                            onCancel: options.onCancel || null,
                            resolve: resolve
                        };
                        this.isOpen = true;
                    });
                },
                alert: (options) => {
                    return new Promise((resolve) => {
                        if (typeof options === 'string') {
                            options = { message: options };
                        }
                        this.isAlert = true;
                        this.dialog = {
                            title: options.title || 'Informasi',
                            subtitle: options.subtitle || '',
                            message: options.message || '',
                            detail: options.detail || '',
                            type: options.type || 'info',
                            confirmText: options.confirmText || 'Mengerti',
                            cancelText: '',
                            onConfirm: options.onConfirm || null,
                            onCancel: null,
                            resolve: resolve
                        };
                        this.isOpen = true;
                    });
                }
            };

            // Override window.alert agar alert bawaan browser tidak pernah muncul lagi
            window.alert = function(msg) {
                if (window.bestcoDialog) {
                    window.bestcoDialog.alert({
                        title: 'Peringatan',
                        message: String(msg),
                        type: 'warning'
                    });
                } else {
                    console.warn(msg);
                }
            };
        },
        confirmAction() {
            const onConfirm = this.dialog.onConfirm;
            const resolve = this.dialog.resolve;
            this.isOpen = false;
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
            if (typeof resolve === 'function') {
                resolve(true);
            }
        },
        cancel() {
            const onCancel = this.dialog.onCancel;
            const resolve = this.dialog.resolve;
            this.isOpen = false;
            if (typeof onCancel === 'function') {
                onCancel();
            }
            if (typeof resolve === 'function') {
                resolve(false);
            }
        },
        close() {
            this.cancel();
        }
    };
}
</script>

