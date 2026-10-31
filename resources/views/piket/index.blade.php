@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        eyebrow="SDM & Presensi"
        title="Presensi Piket Siswa"
        subtitle="Monitoring kedisiplinan, roster, dan pergantian petugas piket siswa Kelas X, XI, dan XII MPLB pada setiap shift operasional.">
        <x-slot:actions>
            <a href="{{ route('shift.index') }}" class="btn btn-primary">
                <x-icon name="clock" class="h-4 w-4" />
                Kelola Shift Aktif
                <x-icon name="arrow-right" class="h-4 w-4" />
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Tingkat Kehadiran</p>
                    <p class="stat-value">
                        @if($totalAttendances > 0)
                            {{ $attendanceRate }}<span class="text-base font-bold text-slate-400">%</span>
                        @else
                            <span class="text-slate-300">&mdash;</span>
                        @endif
                    </p>
                </div>
                <span class="stat-icon bg-emerald-50 text-emerald-700 ring-emerald-100"><x-icon name="check-circle" class="h-5 w-5" /></span>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
                <div class="progress-track">
                    <div class="progress-bar bg-emerald-500" style="width: {{ min(100, (float) $attendanceRate) }}%"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">
                    {{ $totalAttendances > 0 ? $totalPresent . ' kehadiran tepat waktu' : 'Belum ada sesi presensi tercatat' }}
                </p>
            </div>
        </div>

        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Total Sesi Tercatat</p>
                    <p class="stat-value">{{ number_format((float) $totalAttendances, 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-brand-50 text-brand-700 ring-brand-100"><x-icon name="list" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Sepanjang tahun ajaran aktif</p>
        </div>

        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Substitusi Petugas</p>
                    <p class="stat-value text-amber-600">{{ number_format((float) $totalSubstituted, 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-amber-50 text-amber-700 ring-amber-100"><x-icon name="swap" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Disetujui guru supervisor</p>
        </div>

        <div class="card card-hover p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Ketidakhadiran (Alpa)</p>
                    <p class="stat-value text-rose-600">{{ number_format((float) $totalAbsent, 0, ',', '.') }}</p>
                </div>
                <span class="stat-icon bg-rose-50 text-rose-600 ring-rose-100"><x-icon name="x-circle" class="h-5 w-5" /></span>
            </div>
            <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Tanpa keterangan sah</p>
        </div>
    </div>

    <!-- Today roster -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Jadwal Piket Hari Ini</h2>
                <p class="card-subtitle">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }} &bull; roster petugas terjadwal</p>
            </div>
            <span class="badge badge-brand">{{ $todaySchedules->count() }} slot terdaftar</span>
        </div>

        <div class="card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @forelse($todaySchedules as $schedule)
                    <div class="group relative overflow-hidden rounded-xl border border-slate-100 bg-slate-50/60 p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="badge badge-neutral">Slot {{ $schedule->slot_number }}</span>
                                <p class="mt-2 truncate text-sm font-extrabold text-slate-900">{{ $schedule->scheduledStudent?->name ?? 'Belum Ditentukan' }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $schedule->schoolClass?->name ?? '-' }} &bull; NISN {{ $schedule->scheduledStudent?->nip_nisn ?? '-' }}</p>
                            </div>
                            <span class="avatar h-9 w-9">{{ mb_strtoupper(mb_substr($schedule->scheduledStudent?->name ?? '?', 0, 1)) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="empty-state py-8">
                            <span class="empty-state-icon"><x-icon name="calendar" class="h-6 w-6" /></span>
                            <p class="text-sm font-bold text-slate-700">Belum ada jadwal piket</p>
                            <p class="mt-1 text-xs text-slate-400">Roster untuk hari ini belum terdaftar.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Attendance log -->
    <div class="table-shell">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 class="card-title">Log Riwayat Presensi</h2>
                <p class="card-subtitle">Rekapitulasi kehadiran per shift dan status pergantian siswa</p>
            </div>

            <form method="GET" action="{{ route('piket.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="select sm:w-44">
                        <option value="ALL" {{ $statusFilter === 'ALL' ? 'selected' : '' }}>Semua Status</option>
                        <option value="PRESENT" {{ $statusFilter === 'PRESENT' ? 'selected' : '' }}>Hadir</option>
                        <option value="SICK" {{ $statusFilter === 'SICK' ? 'selected' : '' }}>Sakit</option>
                        <option value="PERMITTED" {{ $statusFilter === 'PERMITTED' ? 'selected' : '' }}>Izin</option>
                        <option value="DISPENSATION" {{ $statusFilter === 'DISPENSATION' ? 'selected' : '' }}>Dispensasi</option>
                        <option value="ABSENT" {{ $statusFilter === 'ABSENT' ? 'selected' : '' }}>Alpa</option>
                    </select>
                </div>
                <div>
                    <label for="date" class="form-label">Tanggal</label>
                    <input type="date" id="date" name="date" value="{{ $dateFilter }}" class="input sm:w-40">
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="submit" class="btn btn-dark">Filter</button>
                    <a href="{{ route('piket.index') }}" class="btn btn-ghost">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Shift &amp; Tanggal</th>
                        <th>Slot</th>
                        <th>Siswa Terjadwal</th>
                        <th>Status</th>
                        <th>Petugas Aktual</th>
                        <th>Approval Guru</th>
                        <th>Check-In</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
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
                            <td class="whitespace-nowrap">
                                <span class="font-mono text-[11px] font-bold text-slate-800">{{ $att->posShift?->shift_code ?? '-' }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $att->posShift?->shift_date?->format('d/m/Y') }}</span>
                            </td>
                            <td><span class="badge badge-neutral">Slot {{ $att->slot_number }}</span></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-[9px] font-extrabold text-slate-500">
                                        {{ mb_strtoupper(mb_substr($att->scheduledUser?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-800">{{ $att->scheduledUser?->name ?? '-' }}</p>
                                        <p class="font-mono text-[10px] text-slate-400">{{ $att->scheduledUser?->nip_nisn }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                                @if($att->is_substituted)
                                    <span class="mt-1 block text-[10px] font-semibold text-amber-600">Substitusi</span>
                                @endif
                            </td>
                            <td>
                                <p class="text-[11px] font-semibold text-slate-700">{{ $att->activeOperator?->name ?? '-' }}</p>
                                @if($att->is_substituted && $att->absence_reason)
                                    <p class="mt-0.5 max-w-[180px] text-[10px] italic text-slate-400">"{{ $att->absence_reason }}"</p>
                                @endif
                            </td>
                            <td class="text-[11px] text-slate-500">{{ $att->approvedByTeacher?->name ?? $att->posShift?->supervisorTeacher?->name ?? '-' }}</td>
                            <td>
                                <span class="chip">{{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <span class="empty-state-icon"><x-icon name="users" class="h-6 w-6" /></span>
                                    <p class="text-sm font-bold text-slate-700">Belum ada catatan presensi</p>
                                    <p class="mt-1 text-xs text-slate-400">Ubah filter status atau tanggal untuk melihat data lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
