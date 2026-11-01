@extends('layouts.app')

@section('content')
@php
    $actor = auth()->user();
    $roleBadges = [
        'superadmin' => 'badge-purple',
        'admin' => 'badge-brand',
        'piket_guru' => 'badge-info',
        'piket_siswa' => 'badge-neutral',
    ];
@endphp

<div class="space-y-6" x-data="{
    confirmStatus(form, name, isActive) {
        window.bestcoDialog.confirm({
            title: isActive ? 'Nonaktifkan akun?' : 'Aktifkan akun?',
            message: isActive
                ? 'Akun ' + name + ' tidak akan bisa masuk ke BESTCO sampai diaktifkan kembali.'
                : 'Akun ' + name + ' akan kembali bisa masuk sesuai role-nya.',
            type: isActive ? 'warning' : 'success',
            confirmText: isActive ? 'Nonaktifkan' : 'Aktifkan',
            cancelText: 'Batal',
        }).then((confirmed) => {
            if (confirmed) {
                form.submit();
            }
        });
    }
}">
    <x-page-header
        title="Kelola Pengguna"
        subtitle="Atur akun, role, dan status akses sesuai tingkatan kewenangan Anda.">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah pengguna
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Total akun terkelola</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900 num-tabular">{{ $counts['total'] }}</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                    <x-icon name="users" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Sesuai cakupan role Anda</p>
        </div>

        <div class="card p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Akun aktif</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-700 num-tabular">{{ $counts['active'] }}</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-icon name="check-circle" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Dapat masuk dan bertugas</p>
        </div>

        <div class="card p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Akun nonaktif</p>
                    <p class="mt-1 text-2xl font-semibold text-rose-600 num-tabular">{{ $counts['inactive'] }}</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <x-icon name="x-circle" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-2 text-[11px] text-slate-400">Akses sementara dibekukan</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-4">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">Cari pengguna</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="text" id="q" name="q" value="{{ $search }}"
                        placeholder="Nama, username, NIP/NISN, atau email..." class="input">
                </div>
            </div>

            <div class="w-full lg:w-52">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="select">
                    <option value="ALL">Semua role terkelola</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ $roleFilter === $role->value ? 'selected' : '' }}>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full lg:w-44">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="select">
                    <option value="ALL" {{ $statusFilter === 'ALL' ? 'selected' : '' }}>Semua status</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Tampilkan</button>
                <a href="{{ route('users.index') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Daftar pengguna -->
    <div class="table-shell">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="card-title">Daftar akun</h2>
                <p class="card-subtitle">
                    @if($users->total() > 0)
                        Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} akun
                    @else
                        Belum ada akun yang sesuai
                    @endif
                </p>
            </div>
            <span class="badge badge-neutral">{{ count($roles) }} role dalam cakupan</span>
        </div>

        @if($users->isEmpty())
            <div class="empty-state">
                <span class="empty-state-icon"><x-icon name="users" class="h-5 w-5" /></span>
                <p class="text-sm font-semibold text-slate-700">Pengguna tidak ditemukan</p>
                <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-400">
                    Ubah kata kunci atau filter, atau tambahkan akun baru.
                </p>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm mt-4">
                    <x-icon name="plus" class="h-3.5 w-3.5" />
                    Tambah pengguna
                </a>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th class="hidden xl:table-cell">NIP / NISN</th>
                            <th class="hidden xl:table-cell">Kelas / NIS</th>
                            <th>Status</th>
                            <th class="hidden xl:table-cell">Terakhir masuk</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $managedUser)
                            @php
                                $initials = collect(explode(' ', $managedUser->name))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                    ->implode('');
                                $isSelf = $managedUser->is($actor);
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="avatar h-8 w-8">{{ $initials }}</span>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1.5 truncate text-[12px] font-semibold text-slate-900">
                                                {{ $managedUser->name }}
                                                @if($isSelf)
                                                    <span class="badge badge-neutral">Anda</span>
                                                @endif
                                            </p>
                                            <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                                <span class="font-mono">{{ '@' . $managedUser->username }}</span>
                                                @if($managedUser->email)
                                                    <span class="mx-1">&bull;</span>{{ $managedUser->email }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $roleBadges[$managedUser->role_type->value] ?? 'badge-neutral' }}">
                                        {{ $managedUser->role_type->label() }}
                                    </span>
                                </td>
                                <td class="hidden xl:table-cell">
                                    <span class="font-mono text-[11px] text-slate-600">{{ $managedUser->nip_nisn ?: '—' }}</span>
                                </td>
                                <td class="hidden xl:table-cell">
                                    @if($managedUser->role_type->value === 'piket_siswa')
                                        <p class="text-[11px] font-medium text-slate-700">{{ $managedUser->studentProfile?->schoolClass?->name ?? 'Kelas belum diatur' }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-slate-400">NIS {{ $managedUser->studentProfile?->nis ?? '—' }}</p>
                                    @else
                                        <span class="text-[11px] text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $managedUser->is_active ? 'badge-success' : 'badge-danger' }}">
                                        {{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="hidden xl:table-cell">
                                    <span class="text-[11px] text-slate-500">
                                        {{ $managedUser->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('users.edit', $managedUser) }}"
                                            class="btn btn-secondary btn-sm" title="Edit akun {{ $managedUser->name }}">
                                            <x-icon name="edit" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('users.status', $managedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button"
                                                @disabled($isSelf)
                                                @click="confirmStatus($el.form, @js($managedUser->name), {{ $managedUser->is_active ? 'true' : 'false' }})"
                                                class="btn btn-sm border {{ $managedUser->is_active ? 'border-rose-200 bg-white text-rose-600 hover:bg-rose-50' : 'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' }} disabled:cursor-not-allowed disabled:opacity-40"
                                                title="{{ $isSelf ? 'Akun sendiri tidak dapat dinonaktifkan' : ($managedUser->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun') }}">
                                                <x-icon :name="$managedUser->is_active ? 'x-circle' : 'check-circle'" class="h-3.5 w-3.5" />
                                                {{ $managedUser->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($users->hasPages())
        <div class="card px-4 py-3">
            {{ $users->links() }}
        </div>
    @endif

    <!-- Hierarki akses -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Hierarki pengelolaan akun</h2>
                <p class="card-subtitle">Setiap role hanya dapat mengelola akun di bawahnya.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4">
            @foreach(\App\Enums\UserRole::cases() as $hierarchyRole)
                <div class="p-4 {{ $loop->last ? '' : 'sm:border-r sm:border-slate-100' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="badge {{ $roleBadges[$hierarchyRole->value] ?? 'badge-neutral' }}">{{ $hierarchyRole->label() }}</span>
                        <span class="font-mono text-[10px] text-slate-400">Level {{ $hierarchyRole->level() }}</span>
                    </div>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                        @switch($hierarchyRole->value)
                            @case('superadmin')
                                Satu akun untuk mengelola seluruh akun dan pengaturan sistem.
                                @break
                            @case('admin')
                                Mengelola guru piket dan siswa piket.
                                @break
                            @case('piket_guru')
                                Mengelola siswa piket.
                                @break
                            @default
                                Tidak mengelola akun lain.
                        @endswitch
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
