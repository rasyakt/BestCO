@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Presensi Piket"
        subtitle="Kehadiran siswa piket di setiap shift.">
        <x-slot:actions>
            <a href="{{ route('shift.index') }}" class="btn btn-primary">
                <x-icon name="clock" class="h-4 w-4" />
                Kelola shift
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <div class="card p-5">
            <p class="stat-label">Kehadiran</p>
            <p class="stat-value">
                @if($totalAttendances > 0)
                    {{ $attendanceRate }}<span class="text-base font-medium text-slate-400">%</span>
                @else
                    <span class="text-slate-300">&mdash;</span>
                @endif
            </p>
            <div class="mt-3 border-t border-slate-100 pt-3">
                <div class="progress-track">
                    <div class="progress-bar bg-emerald-500" style="width: {{ min(100, (float) $attendanceRate) }}%"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">
                    {{ $totalAttendances > 0 ? $totalPresent . ' hadir tepat waktu' : 'Belum ada sesi presensi' }}
                </p>
            </div>
        </div>

        <div class="card p-5">
            <p class="stat-label">Sesi tercatat</p>
            <p class="stat-value">{{ number_format((float) $totalAttendances, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Tahun ajaran aktif</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Pergantian petugas</p>
            <p class="stat-value text-amber-600">{{ number_format((float) $totalSubstituted, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Disetujui guru</p>
        </div>

        <div class="card p-5">
            <p class="stat-label">Tidak hadir</p>
            <p class="stat-value text-rose-600">{{ number_format((float) $totalAbsent, 0, ',', '.') }}</p>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Tanpa keterangan</p>
        </div>
    </div>

    <!-- Jadwal hari ini -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Jadwal piket hari ini</h2>
                <p class="card-subtitle">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ $todaySchedules->count() }} slot</span>
        </div>

        <div class="card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @forelse($todaySchedules as $schedule)
                    <div class="rounded-md border border-slate-200 p-4">
                        <p class="text-[11px] font-medium text-slate-400">Slot {{ $schedule->slot_number }}</p>
                        <p class="mt-1.5 truncate text-sm font-semibold text-slate-900">{{ $schedule->scheduledStudent?->name ?? 'Belum ditentukan' }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ $schedule->schoolClass?->name ?? '-' }} &bull; NISN {{ $schedule->scheduledStudent?->nip_nisn ?? '-' }}</p>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="empty-state py-8">
                            <span class="empty-state-icon"><x-icon name="calendar" class="h-5 w-5" /></span>
                            <p class="text-sm font-semibold text-slate-700">Belum ada jadwal piket</p>
                            <p class="mt-1 text-xs text-slate-400">Jadwal untuk hari ini belum diisi.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Riwayat presensi -->
    <div class="table-shell">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 class="card-title">Riwayat presensi</h2>
                <p class="card-subtitle">Kehadiran dan petugas pengganti per shift</p>
            </div>

            <form method="GET" action="{{ route('piket.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="select sm:w-44">
                        <option value="ALL" {{ $statusFilter === 'ALL' ? 'selected' : '' }}>Semua status</option>
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
                        <th>Shift</th>
                        <th>Slot</th>
                        <th>Siswa</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <th>Guru</th>
                        <th>Masuk</th>
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
                                <span class="font-mono text-[11px] font-medium text-slate-800">{{ $att->posShift?->shift_code ?? '-' }}</span>
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $att->posShift?->shift_date?->format('d/m/Y') }}</span>
                            </td>
                            <td class="text-[11px] text-slate-500">Slot {{ $att->slot_number }}</td>
                            <td>
                                <p class="text-[11px] font-medium text-slate-800">{{ $att->scheduledUser?->name ?? '-' }}</p>
                                <p class="font-mono text-[10px] text-slate-400">{{ $att->scheduledUser?->nip_nisn }}</p>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
                                @if($att->is_substituted)
                                    <span class="mt-1 block text-[10px] font-medium text-amber-600">Ada pengganti</span>
                                @endif
                            </td>
                            <td>
                                <p class="text-[11px] font-medium text-slate-700">{{ $att->activeOperator?->name ?? '-' }}</p>
                                @if($att->is_substituted && $att->absence_reason)
                                    <p class="mt-0.5 max-w-[180px] text-[10px] text-slate-400">{{ $att->absence_reason }}</p>
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
                                    <span class="empty-state-icon"><x-icon name="users" class="h-5 w-5" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada catatan presensi</p>
                                    <p class="mt-1 text-xs text-slate-400">Ubah status atau tanggal untuk melihat data lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
