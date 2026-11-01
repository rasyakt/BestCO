@php
    $isEdit = isset($user) && $user !== null;
    $selectedRole = old('role_type', $isEdit ? $user->role_type->value : ($defaultRole ?? 'piket_siswa'));
    $isSelf = $isEdit && $user->is(auth()->user());
    $hasPin = $isEdit && $user->pin_code !== null;
    $roleDescriptions = [
        'superadmin' => 'Akses penuh ke seluruh akun, laporan, dan pengaturan sistem.',
        'admin' => 'Mengelola operasional, guru piket, dan siswa piket.',
        'piket_guru' => 'Mengelola siswa piket dan menjalankan operasional kasir.',
        'piket_siswa' => 'Mengakses kasir sesuai jadwal piket yang ditetapkan.',
    ];
@endphp

<form method="POST"
    action="{{ $isEdit ? route('users.update', $user) : route('users.store') }}"
    class="space-y-6"
    x-data="{ role: @js($selectedRole), descriptions: @js($roleDescriptions) }">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
                <p class="font-semibold">Periksa kembali data berikut:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <!-- Identitas -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Identitas akun</h2>
                        <p class="card-subtitle">Data utama untuk masuk dan dikenali di sistem.</p>
                    </div>
                </div>

                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="form-label">Nama lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}"
                            maxlength="150" autocomplete="name" class="input @error('name') border-rose-300 @enderror"
                            placeholder="Contoh: Guru Piket 1" required>
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="username" class="form-label">Username <span class="text-rose-500">*</span></label>
                        <input type="text" id="username" name="username" value="{{ old('username', $user->username ?? '') }}"
                            maxlength="50" autocomplete="username" class="input font-mono @error('username') border-rose-300 @enderror"
                            placeholder="Contoh: guru_piket_1" required>
                        <p class="form-hint">Huruf, angka, titik, garis bawah, atau tanda hubung.</p>
                        @error('username')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="nip_nisn" class="form-label">NIP / NISN</label>
                        <input type="text" id="nip_nisn" name="nip_nisn" value="{{ old('nip_nisn', $user->nip_nisn ?? '') }}"
                            maxlength="30" class="input font-mono @error('nip_nisn') border-rose-300 @enderror"
                            placeholder="Opsional, unik per akun">
                        @error('nip_nisn')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                            maxlength="150" autocomplete="email" class="input @error('email') border-rose-300 @enderror"
                            placeholder="Opsional, untuk kontak dan pemulihan akun">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Profil siswa -->
            <div class="card" x-show="role === 'piket_siswa'" x-cloak>
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Profil siswa piket</h2>
                        <p class="card-subtitle">Dibutuhkan untuk jadwal piket dan presensi.</p>
                    </div>
                </div>

                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="school_class_id" class="form-label">Kelas <span class="text-rose-500">*</span></label>
                        <select id="school_class_id" name="school_class_id"
                            class="select @error('school_class_id') border-rose-300 @enderror">
                            <option value="">Pilih kelas</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}"
                                    {{ (string) old('school_class_id', $user->studentProfile?->school_class_id ?? '') === (string) $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_class_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="nis" class="form-label">NIS <span class="text-rose-500">*</span></label>
                        <input type="text" id="nis" name="nis" value="{{ old('nis', $user->studentProfile?->nis ?? '') }}"
                            maxlength="25" class="input font-mono @error('nis') border-rose-300 @enderror"
                            placeholder="Contoh: X001">
                        @error('nis')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="phone" class="form-label">Nomor HP</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->studentProfile?->phone ?? '') }}"
                            maxlength="20" inputmode="tel" class="input @error('phone') border-rose-300 @enderror"
                            placeholder="Opsional, contoh: 081234567890">
                        @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Keamanan -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Keamanan akun</h2>
                        <p class="card-subtitle">
                            {{ $isEdit ? 'Kosongkan password atau PIN jika tidak ingin menggantinya.' : 'Password wajib diisi. PIN wajib untuk akun selain siswa.' }}
                        </p>
                    </div>
                </div>

                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="form-label">
                            Password {{ $isEdit ? '' : '*' }}
                        </label>
                        <input type="password" id="password" name="password"
                            autocomplete="new-password" class="input @error('password') border-rose-300 @enderror"
                            placeholder="{{ $isEdit ? 'Kosongkan jika tetap' : 'Minimal 8 karakter' }}"
                            {{ $isEdit ? '' : 'required' }}>
                        <p class="form-hint">Minimal 8 karakter.</p>
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Ulangi password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            autocomplete="new-password" class="input" placeholder="Ketik ulang password">
                    </div>

                    <div x-show="role !== 'piket_siswa'" x-cloak>
                        <label for="pin_code" class="form-label">
                            PIN 6 digit {{ $hasPin ? '' : '*' }}
                        </label>
                        <input type="text" id="pin_code" name="pin_code" maxlength="6" inputmode="numeric"
                            data-numeric="true" data-allow-leading-zero="true" autocomplete="off"
                            class="input font-mono tracking-[0.35em] @error('pin_code') border-rose-300 @enderror"
                            placeholder="{{ $hasPin ? 'Sudah diatur' : 'Contoh: 123456' }}">
                        <p class="form-hint">
                            {{ $hasPin
                                ? 'PIN sudah diatur. Isi hanya jika ingin mengganti.'
                                : 'Dipakai untuk persetujuan cepat di kasir.' }}
                        </p>
                        @error('pin_code')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Role -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Role &amp; akses</h2>
                        <p class="card-subtitle">Pilih sesuai kewenangan yang diberikan.</p>
                    </div>
                </div>

                <div class="card-body space-y-4">
                    <div>
                        <label for="role_type" class="form-label">Role <span class="text-rose-500">*</span></label>
                        <select id="role_type" name="role_type" x-model="role"
                            class="select @error('role_type') border-rose-300 @enderror" required>
                            @foreach($roles as $availableRole)
                                <option value="{{ $availableRole->value }}">{{ $availableRole->label() }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint" x-text="descriptions[role] ?? ''"></p>
                        @error('role_type')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold text-slate-600">Cakupan role</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            @if($isSelf)
                                Role akun sendiri tidak dapat diubah.
                            @elseif(count($roles) === 1)
                                Anda hanya dapat menetapkan role {{ $roles[0]->label() }}.
                            @else
                                Role yang dapat Anda tetapkan:
                                {{ collect($roles)->map(fn ($role) => $role->label())->join(', ', ' dan ') }}.
                                @if(auth()->user()->hasRole('superadmin'))
                                    <span class="mt-1 block text-[10px] text-slate-400">Akun superadmin hanya satu.</span>
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Status akun</h2>
                        <p class="card-subtitle">Akun nonaktif tidak dapat masuk.</p>
                    </div>
                </div>

                <div class="card-body space-y-3">
                    @if($isSelf)
                        <input type="hidden" name="is_active" value="1">
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" checked disabled class="checkbox mt-0.5">
                            <span>
                                <span class="block text-xs font-medium text-slate-700">Aktif</span>
                                <span class="mt-0.5 block text-[11px] text-slate-400">Akun sendiri selalu aktif.</span>
                            </span>
                        </label>
                    @else
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" name="is_active" value="1" class="checkbox mt-0.5"
                                @checked(old('is_active', $user->is_active ?? true))>
                            <span>
                                <span class="block text-xs font-medium text-slate-700">Akun aktif</span>
                                <span class="mt-0.5 block text-[11px] text-slate-400">Bisa masuk dan menjalankan tugas.</span>
                            </span>
                        </label>
                    @endif

                    @if($isEdit)
                        <div class="space-y-1.5 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                            <div class="flex items-center justify-between gap-3">
                                <span>Dibuat</span>
                                <span class="font-mono text-slate-600">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span>Terakhir masuk</span>
                                <span class="font-mono text-slate-600">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-[11px] leading-relaxed text-sky-800">
                <div class="flex items-start gap-2.5">
                    <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Gunakan <strong>nonaktifkan</strong> untuk menghentikan akses sementara.
                        Riwayat transaksi dan presensi tetap tersimpan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse items-stretch justify-end gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:items-center">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah pengguna' }}
        </button>
    </div>
</form>
