@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        eyebrow="Operasional Shift"
        title="Shift Kasir & Piket"
        subtitle="Kelola pembukaan dan penutupan shift, verifikasi presensi 3 slot siswa piket, serta pencocokan kas fisik bersama guru supervisor.">
        <x-slot:actions>
            @if($activeShift)
                <a href="{{ route('pos.terminal') }}" class="btn btn-primary">
                    <x-icon name="pos" class="h-4 w-4" />
                    Buka Terminal Kasir
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @else
                <span class="badge badge-warning px-3 py-2">Shift belum dibuka</span>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($activeShift)
        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
            <!-- Left: overview & attendance -->
            <div class="space-y-6 xl:col-span-2">
                <!-- Active shift overview -->
                <div class="card overflow-hidden">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-900 to-brand-950 px-5 py-5 text-white">
                        <div class="pointer-events-none absolute -right-10 -top-16 h-44 w-44 rounded-full border border-white/10"></div>
                        <div class="pointer-events-none absolute -bottom-16 right-16 h-32 w-32 rounded-full bg-white/[0.04]"></div>

                        <div class="relative flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-brand-200">
                                    <x-icon name="clock" class="h-5 w-5" />
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                                        </span>
                                        <span class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-emerald-300">Shift Berlangsung</span>
                                    </div>
                                    <p class="mt-1 font-mono text-base font-extrabold">{{ $activeShift->shift_code }}</p>
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-white/50">
                                <p class="font-bold uppercase tracking-[0.12em] text-white/35">Dibuka</p>
                                <p class="mt-1 font-mono text-white/80">{{ $activeShift->opened_at->format('d/m/Y H:i') }} WIB</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4 sm:divide-y-0">
                        <div class="p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400">Guru Supervisor</p>
                            <p class="mt-1.5 truncate text-xs font-bold text-slate-800">{{ $activeShift->supervisorTeacher->name }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400">Operator Pembuka</p>
                            <p class="mt-1.5 truncate text-xs font-bold text-slate-800">{{ $activeShift->openedBy->name }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400">Kas Awal</p>
                            <p class="mt-1.5 text-xs font-bold text-slate-800 num-tabular">Rp {{ number_format((float) $activeShift->opening_cash_balance, 0, ',', '.') }}</p>
                        </div>
                        <div class="p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-brand-500">Ekspektasi Kas</p>
                            <p class="mt-1.5 text-xs font-extrabold text-brand-700 num-tabular">Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Attendance -->
                <div class="table-shell">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="card-title">Petugas Piket Bertugas</h2>
                            <p class="card-subtitle">3 slot siswa dengan verifikasi kehadiran</p>
                        </div>
                        <span class="badge badge-success">{{ $activeShift->attendances->count() }} slot aktif</span>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Slot</th>
                                    <th>Siswa Terjadwal</th>
                                    <th>Kehadiran</th>
                                    <th>Operator Aktual</th>
                                    <th>Check-In</th>
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
                                        <td><span class="badge badge-neutral">Slot {{ $att->slot_number }}</span></td>
                                        <td>
                                            <p class="text-[11px] font-bold text-slate-800">{{ $att->scheduledUser->name }}</p>
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
                                                <p class="text-[11px] font-bold text-indigo-700">{{ $att->substituteUser->name }}</p>
                                                <p class="mt-0.5 text-[10px] text-indigo-400">Pengganti sah</p>
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

            <!-- Right: close shift -->
            <div class="card xl:sticky xl:top-[92px]">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Penutupan Shift</h2>
                        <p class="card-subtitle">Cash count bersama guru piket</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                        <x-icon name="lock" class="h-4 w-4" />
                    </span>
                </div>

                <form method="POST" action="{{ route('shift.close') }}">
                    @csrf
                    <input type="hidden" name="pos_shift_id" value="{{ $activeShift->id }}">

                    <div class="card-body space-y-4">
                        @if($errors->has('close_pin'))
                            <div class="alert alert-danger">
                                <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{{ $errors->first('close_pin') }}</span>
                            </div>
                        @endif

                        <div class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-brand-500">Ekspektasi Kas Sistem</p>
                            <p class="mt-1 text-xl font-extrabold text-brand-900 num-tabular">Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}</p>
                            <p class="mt-1 text-[10px] text-brand-600/70">Saldo awal + seluruh penjualan tunai shift ini</p>
                        </div>

                        <div>
                            <label for="actual_closing_cash" class="form-label">Kas Fisik Aktual (Rp) <span class="text-rose-600">*</span></label>
                            <input type="number" step="1" id="actual_closing_cash" name="actual_closing_cash" required
                                placeholder="Contoh: 350000" class="input font-mono text-base">
                        </div>

                        <div>
                            <label for="supervisor_pin" class="form-label">PIN Guru Supervisor <span class="text-rose-600">*</span></label>
                            <input type="password" id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                                placeholder="6 digit PIN" class="input font-mono tracking-[0.3em]">
                            <span class="form-hint">{{ $activeShift->supervisorTeacher->name }}</span>
                        </div>

                        <div>
                            <label for="closing_notes" class="form-label">Catatan Penutupan</label>
                            <textarea id="closing_notes" name="closing_notes" rows="3" placeholder="Kondisi operasional atau keterangan jika ada selisih kas..."
                                class="textarea"></textarea>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menutup shift ini? Kas fisik akan dikunci dan selisih akan otomatis dijurnal.')"
                            class="btn btn-dark w-full">
                            <x-icon name="lock" class="h-4 w-4" />
                            Tutup Shift Sekarang
                        </button>
                        <p class="mt-2 text-center text-[10px] leading-relaxed text-slate-400">Pastikan perhitungan uang fisik sudah final. Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Open shift -->
        <div class="card overflow-hidden">
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-amber-600 to-orange-700 px-6 py-6 text-white">
                <div class="pointer-events-none absolute -right-8 -top-12 h-40 w-40 rounded-full border border-white/20"></div>
                <div class="pointer-events-none absolute -bottom-16 right-24 h-32 w-32 rounded-full bg-white/10"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[9px] font-extrabold uppercase tracking-[0.16em] text-white/90 backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                            Shift Belum Dibuka
                        </span>
                        <h2 class="mt-3 text-xl font-extrabold tracking-tight">Mulai Operasional Kasir Hari Ini</h2>
                        <p class="mt-1 max-w-xl text-[12px] leading-relaxed text-white/70">Verifikasi presensi 3 slot siswa piket, tentukan pengganti jika berhalangan, lalu input modal kas laci awal bersama guru piket.</p>
                    </div>
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                        <x-icon name="clock" class="h-7 w-7" />
                    </span>
                </div>
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
                    <!-- Supervisor & cash -->
                    <div>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-brand-50 text-[10px] font-extrabold text-brand-700">1</span>
                            <h3 class="text-sm font-extrabold text-slate-900">Supervisi &amp; Modal Awal</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="supervisor_teacher_id" class="form-label">Guru Piket Supervisor <span class="text-rose-600">*</span></label>
                                <select id="supervisor_teacher_id" name="supervisor_teacher_id" required class="select">
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="supervisor_pin" class="form-label">PIN Approval Guru <span class="text-rose-600">*</span></label>
                                <input type="password" id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                                    placeholder="Default testing: 123456" class="input font-mono tracking-[0.3em]">
                            </div>

                            <div>
                                <label for="opening_cash_balance" class="form-label">Modal Kas Awal (Rp) <span class="text-rose-600">*</span></label>
                                <input type="number" step="1" id="opening_cash_balance" name="opening_cash_balance" value="200000" required
                                    class="input font-mono text-base">
                            </div>
                        </div>
                    </div>

                    <!-- Slots -->
                    <div>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-brand-50 text-[10px] font-extrabold text-brand-700">2</span>
                            <h3 class="text-sm font-extrabold text-slate-900">Presensi 3 Slot Siswa Piket</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            @for($i = 0; $i < 3; $i++)
                                @php
                                    $slotNum = $i + 1;
                                    $grade = match ($slotNum) { 1 => 'X MPLB', 2 => 'XI MPLB', 3 => 'XII MPLB' };
                                    $scheduledUser = $defaultStudents[$slotNum] ?? ($students[$i] ?? $students->first());
                                @endphp
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4">
                                    <div class="flex items-center justify-between border-b border-slate-200/70 pb-2.5">
                                        <div>
                                            <p class="text-xs font-extrabold text-slate-800">Slot {{ $slotNum }}</p>
                                            <p class="mt-0.5 text-[10px] text-slate-400">{{ $grade }} &bull; Perwakilan kelas</p>
                                        </div>
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                                            <x-icon name="users" class="h-4 w-4" />
                                        </span>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        <div>
                                            <label class="form-label">Siswa Terjadwal</label>
                                            <select name="slots[{{ $i }}][scheduled_user_id]" class="select">
                                                @foreach($students as $st)
                                                    <option value="{{ $st->id }}" {{ $scheduledUser && $scheduledUser->id === $st->id ? 'selected' : '' }}>
                                                        {{ $st->name }} ({{ $st->username }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="form-label">Status Kehadiran</label>
                                            <select name="slots[{{ $i }}][status]" onchange="toggleSubstitute(this, {{ $i }})" class="select">
                                                <option value="PRESENT">Hadir Aktual</option>
                                                <option value="SICK">Sakit</option>
                                                <option value="PERMITTED">Izin</option>
                                                <option value="ABSENT">Alpa</option>
                                                <option value="DISPENSATION">Dispensasi Sekolah</option>
                                            </select>
                                        </div>

                                        <div id="substitute-box-{{ $i }}" class="hidden space-y-2 rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-3">
                                            <div>
                                                <label class="form-label text-amber-800">Alasan Ketidakhadiran</label>
                                                <input type="text" name="slots[{{ $i }}][absence_reason]" placeholder="Misal: Sakit demam"
                                                    class="input bg-white">
                                            </div>
                                            <div>
                                                <label class="form-label text-amber-800">Siswa Pengganti</label>
                                                <select name="slots[{{ $i }}][substituted_user_id]" class="select bg-white">
                                                    <option value="">-- Pilih Siswa Pengganti --</option>
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

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10px] leading-relaxed text-slate-400">Dengan membuka shift, seluruh transaksi kasir akan tercatat pada shift ini.</p>
                    <button type="submit" class="btn btn-primary">
                        Buka Shift Kasir
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Recent shifts -->
    @if($recentShifts->isNotEmpty())
        <div class="table-shell">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="card-title">Riwayat Shift Terakhir</h2>
                    <p class="card-subtitle">5 shift kasir terakhir beserta selisih kas</p>
                </div>
                <span class="badge badge-neutral">Audit Kas</span>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode Shift</th>
                            <th>Tanggal</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th class="text-right">Saldo Awal</th>
                            <th class="text-right">Kas Fisik</th>
                            <th class="text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentShifts as $rs)
                            <tr>
                                <td class="font-mono text-[11px] font-bold text-slate-800">{{ $rs->shift_code }}</td>
                                <td class="text-slate-600">{{ $rs->shift_date->format('d/m/Y') }}</td>
                                <td class="text-slate-700">{{ $rs->supervisorTeacher->name }}</td>
                                <td>
                                    <span class="badge {{ $rs->status->value === 'OPEN' ? 'badge-success' : 'badge-neutral' }}">{{ $rs->status->value }}</span>
                                </td>
                                <td class="text-right num-tabular">Rp {{ number_format((float) $rs->opening_cash_balance, 0, ',', '.') }}</td>
                                <td class="text-right num-tabular">
                                    {{ $rs->actual_closing_cash ? 'Rp ' . number_format((float) $rs->actual_closing_cash, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-right num-tabular">
                                    @if($rs->cash_discrepancy !== null)
                                        @if(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 0)
                                            <span class="font-bold text-emerald-600">Rp 0 (Pas)</span>
                                        @elseif(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 1)
                                            <span class="font-bold text-sky-600">+Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (Lebih)</span>
                                        @else
                                            <span class="font-bold text-rose-600">Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (Kurang)</span>
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
