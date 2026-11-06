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
            @click.outside="close()"
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
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-lg shadow-rose-500/25">
                        <x-icon name="x-circle" class="h-6 w-6" stroke-width="2" />
                    </div>
                </template>

                <!-- Warning Icon -->
                <template x-if="dialog.type === 'warning'">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg shadow-amber-500/25">
                        <x-icon name="spoilage" class="h-6 w-6" stroke-width="2" />
                    </div>
                </template>

                <!-- Success Icon -->
                <template x-if="dialog.type === 'success'">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25">
                        <x-icon name="check-circle" class="h-6 w-6" stroke-width="2" />
                    </div>
                </template>

                <!-- Primary / Info Icon -->
                <template x-if="dialog.type === 'primary' || dialog.type === 'info'">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/25">
                        <x-icon name="info" class="h-6 w-6" stroke-width="2" />
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

                <!-- Confirmation Text Input if required -->
                <template x-if="dialog.requireConfirmationText">
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                            Ketik <span class="font-mono font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200" x-text="dialog.requireConfirmationText"></span> untuk mengonfirmasi:
                        </label>
                        <input type="text"
                            x-model="enteredConfirmationText"
                            @keydown.enter.prevent="if (canConfirm) confirmAction()"
                            :placeholder="'Ketik ' + dialog.requireConfirmationText"
                            class="input font-mono text-xs w-full uppercase tracking-wider"
                            autocomplete="off"
                            spellcheck="false">
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
                    :disabled="!canConfirm"
                    :class="{
                        'btn btn-danger': dialog.type === 'danger',
                        'btn btn-warning': dialog.type === 'warning',
                        'btn btn-success': dialog.type === 'success',
                        'btn btn-primary': dialog.type === 'primary' || dialog.type === 'info'
                    }"
                    class="px-5 py-2.5 rounded-lg text-xs font-semibold flex-1 sm:flex-initial shadow-xs disabled:opacity-40 disabled:cursor-not-allowed">
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
        enteredConfirmationText: '',
        dialog: {
            title: '',
            subtitle: '',
            message: '',
            detail: '',
            type: 'primary',
            confirmText: 'Lanjutkan',
            cancelText: 'Batal',
            requireConfirmationText: '',
            onConfirm: null,
            onCancel: null,
            resolve: null
        },
        get canConfirm() {
            if (this.isAlert) return true;
            if (!this.dialog.requireConfirmationText) return true;
            return this.enteredConfirmationText.trim().toUpperCase() === this.dialog.requireConfirmationText.trim().toUpperCase();
        },
        initDialog() {
            window.bestcoDialog = {
                confirm: (options) => {
                    return new Promise((resolve) => {
                        this.isAlert = false;
                        this.enteredConfirmationText = '';
                        this.dialog = {
                            title: options.title || 'Konfirmasi Tindakan',
                            subtitle: options.subtitle || '',
                            message: options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                            detail: options.detail || '',
                            type: options.type || 'primary',
                            confirmText: options.confirmText || 'Lanjutkan',
                            cancelText: options.cancelText || 'Batal',
                            requireConfirmationText: options.requireConfirmationText || '',
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
                        this.enteredConfirmationText = '';
                        this.dialog = {
                            title: options.title || 'Informasi',
                            subtitle: options.subtitle || '',
                            message: options.message || '',
                            detail: options.detail || '',
                            type: options.type || 'info',
                            confirmText: options.confirmText || 'Mengerti',
                            cancelText: '',
                            requireConfirmationText: '',
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

            // Ganti bubble validasi HTML5 bawaan browser dengan dialog kustom.
            // Validasi tetap memakai aturan HTML5 (required, pattern, min, max, dll).
            if (! window.bestcoValidationGuardInstalled) {
                window.bestcoValidationGuardInstalled = true;

                let invalidFields = [];
                let invalidTimer = null;

                const fieldLabel = (field) => {
                    const nativeLabel = field.labels && field.labels.length > 0
                        ? field.labels[0].textContent
                        : '';
                    const fallback = field.getAttribute('aria-label')
                        || field.getAttribute('placeholder')
                        || field.getAttribute('name')
                        || 'Kolom';

                    return (nativeLabel || fallback)
                        .replace(/\s*\*\s*$/, '')
                        .replace(/:\s*$/, '')
                        .trim() || 'Kolom';
                };

                const fieldProblem = (field) => {
                    const validity = field.validity;

                    if (! validity) return 'nilai tidak valid';
                    if (validity.valueMissing) return 'wajib diisi';
                    if (validity.typeMismatch) return 'format tidak valid';
                    if (validity.patternMismatch) return 'format tidak sesuai';
                    if (validity.tooShort) return 'minimal ' + field.minLength + ' karakter';
                    if (validity.tooLong) return 'maksimal ' + field.maxLength + ' karakter';
                    if (validity.rangeUnderflow) return 'nilai minimal ' + field.min;
                    if (validity.rangeOverflow) return 'nilai maksimal ' + field.max;
                    if (validity.stepMismatch) return 'nilai tidak sesuai langkah yang diizinkan';
                    if (validity.badInput) return 'masukkan angka yang valid';
                    if (validity.customError) return field.validationMessage;

                    return 'nilai tidak valid';
                };

                document.addEventListener('invalid', (event) => {
                    const field = event.target;

                    if (! (field instanceof HTMLElement)) return;

                    event.preventDefault();
                    invalidFields.push(field);

                    if (invalidTimer !== null) return;

                    invalidTimer = window.setTimeout(() => {
                        const fields = [...new Set(invalidFields)];
                        invalidFields = [];
                        invalidTimer = null;

                        if (fields.length === 0) return;

                        const summary = fields
                            .slice(0, 3)
                            .map((item) => fieldLabel(item) + ' ' + fieldProblem(item))
                            .join(', ');
                        const rest = fields.length - 3;

                        window.bestcoDialog.alert({
                            title: fields.length === 1 ? 'Periksa Kolom Input' : 'Periksa Data Formulir',
                            message: summary + (rest > 0 ? ', dan ' + rest + ' kolom lainnya' : '') + '.',
                            detail: 'Perbaiki kolom yang ditandai, lalu kirim ulang formulir.',
                            type: 'warning',
                            confirmText: 'Mengerti'
                        }).then(() => fields[0].focus());
                    }, 0);
                }, true);
            }
        },
        confirmAction() {
            if (!this.canConfirm) return;
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

