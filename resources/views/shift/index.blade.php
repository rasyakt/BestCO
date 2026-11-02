@extends('layouts.app')

@section('content')
@php
    $canManageShift = auth()->user()->hasAnyRole(['superadmin', 'admin', 'piket_guru']);
    $canOpenPos = $canManageShift || ($activeShift && $activeShift->attendances->contains(
        fn ($attendance) => (int) $attendance->active_operator_user_id === (int) auth()->id()
    ));
@endphp
<div class="space-y-6">
    <x-page-header
        title="Shift Kasir"
        subtitle="Buka, tutup, dan cocokkan kas shift.">
        <x-slot:actions>
            @if($activeShift && $canOpenPos)
                <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                    <x-icon name="pos" class="h-4 w-4" />
                    Buka kasir
                </a>
            @elseif($activeShift)
                <span class="text-[11px] font-medium text-slate-500">Anda bukan operator aktif shift ini</span>
            @else
                <span class="text-[11px] font-medium text-amber-700">Shift belum dibuka</span>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($activeShift && $activeShift->shift_date->lt(today()))
        <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/90 p-4 text-amber-900 shadow-xs">
            <x-icon name="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div class="flex-1 text-xs">
                <p class="font-semibold">Shift Sebelumnya Belum Ditutup (Dibuka {{ $activeShift->shift_date->translatedFormat('d F Y') }})</p>
                <p class="mt-1 text-amber-800">
                    Shift <strong>{{ $activeShift->shift_code }}</strong> masih berstatus aktif. Anda dapat langsung melanjutkan dengan klik <strong>Buka kasir</strong> di atas, atau lakukan <strong>Tutup shift</strong> pada panel kanan untuk merekonsiliasi kas fisik sebelum membuka shift baru hari ini.
                </p>
            </div>
        </div>
    @endif

    @if($activeShift)
        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
            <!-- Kiri -->
            <div class="space-y-6 {{ $canManageShift ? 'xl:col-span-2' : 'xl:col-span-3' }}">
                <div class="card">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <p class="text-[11px] font-medium text-emerald-700">Shift berlangsung</p>
                            </div>
                            <p class="mt-1 font-mono text-base font-semibold text-slate-900">{{ $activeShift->shift_code }}</p>
                        </div>
                        <div class="text-right text-[11px] text-slate-500">
                            <p>Dibuka</p>
                            <p class="mt-0.5 font-mono text-slate-700">{{ $activeShift->opened_at->format('d/m/Y H:i') }} WIB</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4 sm:divide-y-0">
                        <div class="p-4">
                            <p class="text-[10px] text-slate-400">Guru</p>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-800">{{ $activeShift->supervisorTeacher->name }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] text-slate-400">Dibuka oleh</p>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-800">{{ $activeShift->openedBy->name }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] text-slate-400">Kas awal</p>
                            <p class="mt-1 text-xs font-semibold text-slate-800 num-tabular">Rp {{ number_format((float) $activeShift->opening_cash_balance, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[10px] text-slate-400">Kas seharusnya</p>
                            <p class="mt-1 text-xs font-semibold text-brand-700 num-tabular">Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Petugas -->
                <div class="table-shell">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="card-title">Petugas piket</h2>
                            <p class="card-subtitle">3 slot siswa dengan status kehadiran</p>
                        </div>
                        <span class="text-[11px] text-slate-500">{{ $activeShift->attendances->count() }} slot</span>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Slot</th>
                                    <th>Siswa</th>
                                    <th>Status</th>
                                    <th>Petugas</th>
                                    <th>Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeShift->attendances as $att)
                                    @php
                                        $statusValue = $att->scheduled_student_status->value;
                                        $statusBadge = match ($statusValue) {
                                            'PRESENT' => ['badge-success', 'Hadir'],
                                            'SICK' => ['badge-warning', 'Sakit'],
                                            'PERMITTED' => ['badge-info', 'Izin'],
                                            'DISPENSATION' => ['badge-purple', 'Dispensasi'],
                                            default => ['badge-danger', 'Alpa'],
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-[11px] text-slate-500">Slot {{ $att->slot_number }}</td>
                                        <td>
                                            <p class="text-[11px] font-medium text-slate-800">{{ $att->scheduledUser->name }}</p>
                                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $att->scheduledUser->username }}</p>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                                            @if($att->absence_reason)
                                                <p class="mt-1 max-w-[180px] text-[10px] text-slate-400">{{ $att->absence_reason }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            @if($att->is_substituted && $att->substituteUser)
                                                <p class="text-[11px] font-medium text-slate-800">{{ $att->substituteUser->name }}</p>
                                                <p class="mt-0.5 text-[10px] text-slate-400">Pengganti</p>
                                            @else
                                                <p class="text-[11px] text-slate-700">{{ $att->scheduledUser->name }}</p>
                                            @endif
                                        </td>
                                        <td><span class="chip">{{ $att->check_in_at->format('H:i') }} WIB</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($canManageShift)
            <!-- Tutup shift -->
            <div class="card xl:sticky xl:top-[84px]">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Tutup shift</h2>
                        <p class="card-subtitle">Hitung kas bersama guru</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('shift.close') }}">
                    @csrf
                    <input type="hidden" name="pos_shift_id" value="{{ $activeShift->id }}">

                    <div class="card-body space-y-4">
                        @if($errors->has('supervisor_pin'))
                            <div class="alert alert-danger">
                                <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{{ $errors->first('supervisor_pin') }}</span>
                            </div>
                        @endif

                        <div class="rounded-md border border-brand-100 bg-brand-50 p-4">
                            <p class="text-[11px] font-medium text-brand-700">Kas seharusnya</p>
                            <p class="mt-1 text-xl font-semibold text-brand-900 num-tabular">Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}</p>
                            <p class="mt-1 text-[10px] text-brand-600">Kas awal ditambah penjualan tunai</p>
                        </div>

                        <div>
                            <label for="actual_closing_cash" class="form-label">Kas fisik (Rp) <span class="text-rose-600">*</span></label>
                            <input type="text" inputmode="numeric" data-currency="true"
                                id="actual_closing_cash" name="actual_closing_cash" required
                                placeholder="Contoh: 350000" class="input font-mono text-base">
                        </div>

                        <div>
                            <label for="supervisor_pin" class="form-label">PIN guru <span class="text-rose-600">*</span></label>
                            <input type="password" inputmode="numeric" data-numeric="true" data-allow-leading-zero="true"
                                id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                                placeholder="6 digit" class="input font-mono tracking-[0.3em]">
                            <span class="form-hint">{{ $activeShift->supervisorTeacher->name }}</span>
                        </div>

                        <div>
                            <label for="closing_notes" class="form-label">Catatan</label>
                            <textarea id="closing_notes" name="closing_notes" rows="3" placeholder="Keterangan jika ada selisih kas..."
                                class="textarea"></textarea>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                        <button type="button"
                            @click="window.bestcoDialog.confirm({
                                title: 'Konfirmasi Tutup Shift',
                                message: 'Apakah Anda yakin ingin menutup shift kasir saat ini?',
                                detail: 'Kas akan dikunci dan selisih kas fisik dengan sistem akan dicatat otomatis ke jurnal akuntansi.',
                                type: 'danger',
                                confirmText: 'Ya, Tutup Shift',
                                cancelText: 'Batal',
                                onConfirm: () => $el.closest('form').submit()
                            })"
                            class="btn btn-dark w-full justify-center">
                            <x-icon name="lock" class="h-4 w-4" />
                            Tutup shift
                        </button>
                        <p class="mt-2 text-center text-[10px] leading-relaxed text-slate-400">Pastikan hitungan uang sudah final. Tindakan ini tidak bisa dibatalkan.</p>
                    </div>
                </form>
            </div>
            @endif
        </div>
    @else
        @if($canManageShift)
        <!-- Buka shift -->
        <div class="card">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-900">Buka shift</h2>
                <p class="mt-1 text-[12px] leading-relaxed text-slate-500">Isi kas awal dan status kehadiran 3 petugas piket.</p>
            </div>

            @if($errors->has('supervisor_pin'))
                <div class="px-6 pt-5">
                    <div class="alert alert-danger">
                        <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ $errors->first('supervisor_pin') }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('shift.open') }}">
                @csrf

                <div class="space-y-6 p-6">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">1. Guru dan kas awal</h3>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="supervisor_teacher_id" class="form-label">Guru piket <span class="text-rose-600">*</span></label>
                                <select id="supervisor_teacher_id" name="supervisor_teacher_id" required class="select">
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="supervisor_pin" class="form-label">PIN guru <span class="text-rose-600">*</span></label>
                                <input type="password" inputmode="numeric" data-numeric="true" data-allow-leading-zero="true"
                                    id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                                    placeholder="6 digit" class="input font-mono tracking-[0.3em]">
                            </div>

                            <div>
                                <label for="opening_cash_balance" class="form-label">Kas awal (Rp) <span class="text-rose-600">*</span></label>
                                <input type="text" inputmode="numeric" data-currency="true"
                                    id="opening_cash_balance" name="opening_cash_balance" value="200000" required
                                    class="input font-mono text-base">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">2. Presensi petugas</h3>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            @for($i = 0; $i < 3; $i++)
                                @php
                                    $slotNum = $i + 1;
                                    $grade = match ($slotNum) { 1 => 'X MPLB', 2 => 'XI MPLB', 3 => 'XII MPLB' };
                                    $scheduledUser = $defaultStudents[$slotNum] ?? ($students[$i] ?? $students->first());
                                @endphp
                                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                                    <div class="border-b border-slate-200 pb-2.5">
                                        <p class="text-xs font-semibold text-slate-800">Slot {{ $slotNum }}</p>
                                        <p class="mt-0.5 text-[10px] text-slate-400">{{ $grade }} &bull; Perwakilan kelas</p>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        <div>
                                            <label class="form-label">Siswa</label>
                                            <select name="slots[{{ $i }}][scheduled_user_id]" class="select">
                                                @foreach($students as $st)
                                                    <option value="{{ $st->id }}" {{ $scheduledUser && $scheduledUser->id === $st->id ? 'selected' : '' }}>
                                                        {{ $st->name }} ({{ $st->username }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label">Kehadiran</label>
                                            <select name="slots[{{ $i }}][status]" onchange="toggleSubstitute(this, {{ $i }})" class="select">
                                                <option value="PRESENT">Hadir</option>
                                                <option value="SICK">Sakit</option>
                                                <option value="PERMITTED">Izin</option>
                                                <option value="ABSENT">Alpa</option>
                                                <option value="DISPENSATION">Dispensasi</option>
                                            </select>
                                        </div>

                                        <div id="substitute-box-{{ $i }}" class="hidden space-y-2 rounded border border-dashed border-amber-300 bg-amber-50 p-3">
                                            <div>
                                                <label class="form-label text-amber-800">Alasan</label>
                                                <input type="text" name="slots[{{ $i }}][absence_reason]" placeholder="Contoh: Sakit demam"
                                                    class="input bg-white">
                                            </div>
                                            <div>
                                                <label class="form-label text-amber-800">Siswa pengganti</label>
                                                <select name="slots[{{ $i }}][substituted_user_id]" class="select bg-white">
                                                    <option value="">Pilih siswa pengganti</option>
                                                    @foreach($students as $sub)
                                                        <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->username }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10px] leading-relaxed text-slate-400">Semua transaksi kasir akan tercatat pada shift ini.</p>
                    <button type="submit" class="btn btn-primary">
                        Buka shift
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="card">
            <div class="card-body flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-600">
                    <x-icon name="lock" class="h-4 w-4" />
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Shift belum dibuka</h2>
                    <p class="mt-1 text-[12px] leading-relaxed text-slate-500">
                        Pembukaan shift dilakukan oleh guru piket. Silakan hubungi guru piket yang bertugas.
                    </p>
                </div>
            </div>
        </div>
        @endif
    @endif

    <!-- Riwayat -->
    @if($recentShifts->isNotEmpty())
        <div class="table-shell">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Riwayat shift</h2>
                    <p class="card-subtitle">5 shift terakhir dan selisih kas</p>
                </div>
                <span class="text-[11px] text-slate-500">Audit kas</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode shift</th>
                            <th>Tanggal</th>
                            <th>Guru</th>
                            <th>Status</th>
                            <th class="text-right">Kas awal</th>
                            <th class="text-right">Kas fisik</th>
                            <th class="text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentShifts as $rs)
                            <tr>
                                <td class="font-mono text-[11px] font-medium text-slate-800">{{ $rs->shift_code }}</td>
                                <td class="text-slate-600">{{ $rs->shift_date->format('d/m/Y') }}</td>
                                <td class="text-slate-700">{{ $rs->supervisorTeacher->name }}</td>
                                <td>
                                    <span class="badge {{ $rs->status->value === 'OPEN' ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $rs->status->value === 'OPEN' ? 'Aktif' : 'Selesai' }}
                                    </span>
                                </td>
                                <td class="text-right num-tabular">Rp {{ number_format((float) $rs->opening_cash_balance, 0, ',', '.') }}</td>
                                <td class="text-right num-tabular">
                                    {{ $rs->actual_closing_cash ? 'Rp ' . number_format((float) $rs->actual_closing_cash, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-right num-tabular">
                                    @if($rs->cash_discrepancy !== null)
                                        @if(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 0)
                                            <span class="font-medium text-emerald-600">Rp 0 (pas)</span>
                                        @elseif(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 1)
                                            <span class="font-medium text-sky-600">+Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (lebih)</span>
                                        @else
                                            <span class="font-medium text-rose-600">Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (kurang)</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
    function toggleSubstitute(selectElem, index) {
        const box = document.getElementById('substitute-box-' + index);
        if (selectElem.value !== 'PRESENT') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }
</script>
@endsection
