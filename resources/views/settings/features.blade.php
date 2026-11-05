@extends('layouts.app', ['title' => 'Pengaturan Fitur & Hak Akses — BESTCO'])

@section('content')
<div class="mx-auto max-w-6xl space-y-6" x-data="featureManagerComponent()">
    <!-- Page Header -->
    <x-page-header
        title="Pengaturan Fitur & Hak Akses"
        subtitle="Kelola status aktif/nonaktif setiap fitur aplikasi secara dinamis serta hak akses perannya.">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Tombol Aktifkan Semua -->
                <form method="POST" action="{{ route('settings.features.enableAll') }}">
                    @csrf
                    <button type="button"
                        @click="window.bestcoDialog.confirm({
                            title: 'Aktifkan Semua Fitur',
                            message: 'Apakah Anda yakin ingin mengaktifkan seluruh fitur aplikasi secara global?',
                            detail: 'Setiap modul akan dibuka kembali sesuai hak akses perannya masing-masing.',
                            type: 'info',
                            confirmText: 'Ya, Aktifkan Semua',
                            cancelText: 'Batal',
                            onConfirm: () => $el.closest('form').submit()
                        })"
                        class="btn btn-secondary btn-sm">
                        <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
                        <span>Aktifkan Semua</span>
                    </button>
                </form>

                <!-- Tombol Reset ke Default Pabrik -->
                <form method="POST" action="{{ route('settings.features.reset') }}">
                    @csrf
                    <button type="button"
                        @click="window.bestcoDialog.confirm({
                            title: 'Kembalikan Pengaturan Standar',
                            message: 'Kembalikan matriks hak akses fitur ke konfigurasi awal bawaan sistem?',
                            detail: 'Semua perubahan kustomisasi aktif/nonaktif dan izin peran akan dihapus.',
                            type: 'warning',
                            confirmText: 'Ya, Reset ke Default',
                            cancelText: 'Batal',
                            onConfirm: () => $el.closest('form').submit()
                        })"
                        class="btn btn-secondary btn-sm">
                        <x-icon name="refresh" class="h-4 w-4 text-slate-500" />
                        <span>Reset Default</span>
                    </button>
                </form>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <div class="card p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Fitur</span>
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="shield" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $totalFeatures }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Terdaftar di sistem</p>
        </div>

        <div class="card p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Fitur Aktif</span>
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <x-icon name="check" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $activeFeatures }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Dapat diakses pengguna</p>
        </div>

        <div class="card p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-rose-600 dark:text-rose-400">Fitur Nonaktif</span>
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                    <x-icon name="x" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">{{ $inactiveFeatures }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Dinonaktifkan global</p>
        </div>

        <div class="card p-3.5 sm:p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-brand-600 dark:text-brand-400">Peran Dinamis</span>
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-400">
                    <x-icon name="users" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ count($configurableRoles) }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Admin, Guru, Siswa</p>
        </div>
    </div>

    <!-- Filter & Kontrol Pencarian -->
    <div class="card p-3 sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Tabs Kategori -->
            <div class="flex flex-wrap items-center gap-1">
                <button type="button" @click="selectedGroup = 'ALL'"
                    :class="selectedGroup === 'ALL' ? 'bg-brand-50 text-brand-700 font-semibold border-brand-200 dark:bg-brand-950/80 dark:text-brand-300 dark:border-brand-800' : 'text-slate-600 hover:bg-slate-100 border-transparent dark:text-slate-400 dark:hover:bg-slate-800'"
                    class="rounded-lg border px-3 py-1.5 text-xs transition">
                    Semua Kategori ({{ $totalFeatures }})
                </button>
                @foreach($groupedFeatures as $groupName => $items)
                    <button type="button" @click="selectedGroup = '{{ $groupName }}'"
                        :class="selectedGroup === '{{ $groupName }}' ? 'bg-brand-50 text-brand-700 font-semibold border-brand-200 dark:bg-brand-950/80 dark:text-brand-300 dark:border-brand-800' : 'text-slate-600 hover:bg-slate-100 border-transparent dark:text-slate-400 dark:hover:bg-slate-800'"
                        class="rounded-lg border px-3 py-1.5 text-xs transition">
                        {{ $groupName }} ({{ count($items) }})
                    </button>
                @endforeach
            </div>

            <!-- Search box -->
            <div class="relative w-full sm:w-64">
                <div class="search-field w-full">
                    <x-icon name="search" />
                    <input type="text" x-model="searchQuery"
                        placeholder="Cari fitur..."
                        class="input pr-8 text-xs">
                </div>
                <template x-if="searchQuery">
                    <button type="button" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <x-icon name="x" class="h-3.5 w-3.5" />
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Form Matriks Fitur & Peran -->
    <form action="{{ route('settings.features.update') }}" method="POST" id="feature-form">
        @csrf
        @method('PUT')

        <div class="table-shell overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-[38%] min-w-[280px]">Fitur & Modul Aplikasi</th>
                            <th class="w-[14%] text-center">Status Global</th>
                            <th class="w-[12%] text-center">Superadmin</th>
                            <th class="w-[12%] text-center">Admin</th>
                            <th class="w-[12%] text-center">Guru Piket</th>
                            <th class="w-[12%] text-center">Siswa Piket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($features as $featureKey => $feature)
                            <tr x-show="matchesRow('{{ $feature['group'] }}', '{{ strtolower($feature['label']) }}', '{{ strtolower($feature['description']) }}')"
                                x-transition
                                :class="{ 'opacity-60 bg-slate-50/60 dark:bg-slate-900/40': !isActiveState['{{ $featureKey }}'] }">
                                
                                <!-- Info Fitur -->
                                <td class="align-top py-3.5">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 mt-0.5">
                                            <x-icon :name="$feature['icon']" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                                    {{ $feature['label'] }}
                                                </span>
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                    {{ $feature['group'] }}
                                                </span>
                                                <template x-if="!isActiveState['{{ $featureKey }}']">
                                                    <span class="rounded-full bg-rose-100 px-2 py-0.2 text-[9.5px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                        Nonaktif
                                                    </span>
                                                </template>
                                            </div>
                                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                                                {{ $feature['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Master Toggle Switch (Aktif / Nonaktif) -->
                                <td class="align-middle text-center py-3.5">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <label class="relative inline-flex cursor-pointer items-center">
                                            <input type="checkbox"
                                                name="features[{{ $featureKey }}][is_active]"
                                                value="1"
                                                x-model="isActiveState['{{ $featureKey }}']"
                                                class="peer sr-only">
                                            <div class="peer h-5 w-9 rounded-full bg-slate-300 after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-focus:outline-none dark:bg-slate-700 dark:peer-checked:bg-emerald-500"></div>
                                        </label>
                                        <span class="text-[10px] font-semibold"
                                            :class="isActiveState['{{ $featureKey }}'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-400'">
                                            <span x-text="isActiveState['{{ $featureKey }}'] ? 'Aktif' : 'Nonaktif'"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Superadmin (Selalu Akses Penuh / Lock) -->
                                <td class="align-middle text-center py-3.5">
                                    <div class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-300"
                                        title="Superadmin memiliki kewenangan master penuh untuk seluruh modul sistem">
                                        <x-icon name="lock" class="h-3 w-3" />
                                        <span>Master</span>
                                    </div>
                                </td>

                                <!-- Checkbox Admin -->
                                <td class="align-middle text-center py-3.5">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1.5"
                                        :class="{ 'cursor-not-allowed opacity-40': !isActiveState['{{ $featureKey }}'] }">
                                        <input type="checkbox"
                                            name="features[{{ $featureKey }}][roles][]"
                                            value="admin"
                                            :disabled="!isActiveState['{{ $featureKey }}']"
                                            {{ in_array('admin', $feature['roles'], true) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900">
                                    </label>
                                </td>

                                <!-- Checkbox Guru Piket -->
                                <td class="align-middle text-center py-3.5">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1.5"
                                        :class="{ 'cursor-not-allowed opacity-40': !isActiveState['{{ $featureKey }}'] }">
                                        <input type="checkbox"
                                            name="features[{{ $featureKey }}][roles][]"
                                            value="piket_guru"
                                            :disabled="!isActiveState['{{ $featureKey }}']"
                                            {{ in_array('piket_guru', $feature['roles'], true) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900">
                                    </label>
                                </td>

                                <!-- Checkbox Siswa Piket -->
                                <td class="align-middle text-center py-3.5">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1.5"
                                        :class="{ 'cursor-not-allowed opacity-40': !isActiveState['{{ $featureKey }}'] }">
                                        <input type="checkbox"
                                            name="features[{{ $featureKey }}][roles][]"
                                            value="piket_siswa"
                                            :disabled="!isActiveState['{{ $featureKey }}']"
                                            {{ in_array('piket_siswa', $feature['roles'], true) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900">
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Simpan Perubahan -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/80 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <x-icon name="info" class="h-4 w-4 text-brand-600 shrink-0" />
                    <span>Perubahan hak akses peran dan status aktif fitur akan langsung berlaku seketika di seluruh sistem.</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <a href="{{ route('settings.features') }}" class="btn btn-secondary btn-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="check" class="h-4 w-4" />
                        <span>Simpan Pengaturan Fitur</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function featureManagerComponent() {
    return {
        selectedGroup: 'ALL',
        searchQuery: '',
        isActiveState: {
            @foreach($features as $key => $feature)
                '{{ $key }}': {{ $feature['is_active'] ? 'true' : 'false' }},
            @endforeach
        },

        matchesRow(group, label, description) {
            // Filter kategori
            if (this.selectedGroup !== 'ALL' && this.selectedGroup !== group) {
                return false;
            }

            // Filter pencarian
            if (this.searchQuery.trim() !== '') {
                const query = this.searchQuery.toLowerCase().trim();
                return label.includes(query) || description.includes(query);
            }

            return true;
        }
    };
}
</script>
@endsection

