@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Presensi Piket & Roster Siswa TeFa</h1>
            <p class="text-xs text-slate-500 mt-0.5">Monitoring kedisiplinan dan pergantian shift piket siswa Kelas X, XI, dan XII MPLB.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('shift.index') }}" class="px-3.5 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
                Kelola Shift Kasir Aktif &rarr;
            </a>
        </div>
    </div>

    <!-- 4 Statistik Presensi Piket -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Tingkat Kehadiran Siswa</span>
            <div class="text-2xl font-bold text-slate-900 num-tabular">
                {{ $attendanceRate }}%
            </div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">
                {{ $totalPresent }} kehadiran tepat waktu
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Total Sesi Presensi Tercatat</span>
            <div class="text-2xl font-bold text-slate-900 num-tabular">
                {{ $totalAttendances }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Sepanjang tahun ajaran aktif
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Pergantian Petugas (Substitusi)</span>
            <div class="text-2xl font-bold text-amber-600 num-tabular">
                {{ $totalSubstituted }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Disetujui Guru Supervisor
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
            <span class="text-xs font-medium text-slate-500 block mb-1">Ketidakhadiran (Alpa)</span>
            <div class="text-2xl font-bold text-rose-600 num-tabular">
                {{ $totalAbsent }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">
                Tanpa keterangan sah
            </div>
        </div>
    </div>

    <!-- Jadwal Piket Hari Ini (Roster Siswa) -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs p-4 space-y-3">
        <div class="flex items-center justify-between border-b pb-2">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Jadwal Piket Terjadwal Hari Ini ({{ now()->isoFormat('dddd, D MMMM Y') }})</h2>
                <p class="text-[11px] text-slate-400">Roster siswa piket yang dijadwalkan bertugas pada hari ini.</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                {{ $todaySchedules->count() }} Slot Terdaftar
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @forelse($todaySchedules as $schedule)
                <div class="p-3 rounded border border-slate-200 bg-slate-50/50 flex items-center justify-between text-xs">
                    <div>
                        <div class="font-bold text-slate-900">
                            Slot {{ $schedule->slot_number }}: {{ $schedule->scheduledStudent?->name ?? 'Belum Ditentukan' }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Kelas: {{ $schedule->schoolClass?->name ?? '-' }} &bull; NISN: {{ $schedule->scheduledStudent?->nip_nisn ?? '-' }}
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-600">
                        Slot {{ $schedule->slot_number }}
                    </span>
                </div>
            @empty
                <div class="col-span-3 py-4 text-center text-slate-400 text-xs">
                    Tidak ada jadwal piket spesifik yang terdaftar untuk hari ini.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Filter & History Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-xs space-y-4 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs border-b pb-3">
            <div>
                <h3 class="font-bold text-sm text-slate-900">Log Riwayat Presensi & Kehadiran Shift</h3>
                <p class="text-[11px] text-slate-400">Rekapitulasi presensi kehadiran per shift kasir beserta status pergantian siswa.</p>
            </div>

            <!-- Filter Form -->
            <form method="GET" action="{{ route('piket.index') }}" class="flex items-center gap-2">
                <select name="status" class="px-3 py-1.5 rounded border border-slate-300 bg-white text-xs">
                    <option value="ALL" {{ $statusFilter === 'ALL' ? 'selected' : '' }}>Semua Status</option>
                    <option value="PRESENT" {{ $statusFilter === 'PRESENT' ? 'selected' : '' }}>Hadir (PRESENT)</option>
                    <option value="SICK" {{ $statusFilter === 'SICK' ? 'selected' : '' }}>Sakit (SICK)</option>
                    <option value="PERMITTED" {{ $statusFilter === 'PERMITTED' ? 'selected' : '' }}>Izin (PERMITTED)</option>
                    <option value="DISPENSATION" {{ $statusFilter === 'DISPENSATION' ? 'selected' : '' }}>Dispensasi</option>
                    <option value="ABSENT" {{ $statusFilter === 'ABSENT' ? 'selected' : '' }}>Alpa (ABSENT)</option>
                </select>

                <input type="date" name="date" value="{{ $dateFilter }}" class="px-3 py-1.5 rounded border border-slate-300 bg-white text-xs">

                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-semibold rounded text-xs hover:bg-slate-800">
                    Filter
                </button>
                <a href="{{ route('piket.index') }}" class="text-xs text-slate-500 hover:underline px-1">Reset</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b">
                    <tr>
                        <th class="py-2.5 px-3">Shift & Tanggal</th>
                        <th class="py-2.5 px-3">Slot</th>
                        <th class="py-2.5 px-3">Siswa Terjadwal</th>
                        <th class="py-2.5 px-3">Status Kehadiran</th>
                        <th class="py-2.5 px-3">Petugas Bertugas (Aktual)</th>
                        <th class="py-2.5 px-3">Approval Guru</th>
                        <th class="py-2.5 px-3">Waktu Check-In</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $att)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3">
                                <span class="font-mono font-medium text-slate-800">{{ $att->posShift?->shift_code ?? '-' }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $att->posShift?->shift_date?->format('d/m/Y') }}</span>
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-700">
                                Slot {{ $att->slot_number }}
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-slate-900">{{ $att->scheduledUser?->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $att->scheduledUser?->nip_nisn }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                @if($att->scheduled_student_status->value === 'PRESENT')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Hadir
                                    </span>
                                @elseif($att->scheduled_student_status->value === 'SICK')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Sakit
                                    </span>
                                @elseif($att->scheduled_student_status->value === 'PERMITTED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Izin
                                    </span>
                                @elseif($att->scheduled_student_status->value === 'DISPENSATION')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                        Dispensasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Alpa
                                    </span>
                                @endif

                                @if($att->is_substituted)
                                    <span class="block text-[10px] text-amber-700 font-medium mt-0.5">
                                        (Substitusi)
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-slate-900">{{ $att->activeOperator?->name ?? '-' }}</div>
                                @if($att->is_substituted && $att->absence_reason)
                                    <div class="text-[10px] text-slate-400 italic">"{{ $att->absence_reason }}"</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $att->approvedByTeacher?->name ?? $att->posShift?->supervisorTeacher?->name ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                {{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada catatan presensi piket ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="pt-3 border-t">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
