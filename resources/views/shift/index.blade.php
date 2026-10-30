@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Shift & Piket Siswa</h1>
            <p class="text-xs text-slate-500 mt-0.5">Sistem presensi 3 slot siswa (Kelas X, XI, XII), substitusi darurat, dan supervisi Guru Piket.</p>
        </div>

        @if($activeShift)
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.terminal') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-sm">
                    Buka Terminal Kasir &rarr;
                </a>
            </div>
        @endif
    </div>

    @if($activeShift)
        <!-- Active Shift Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Shift Overview & Attendance -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Shift Status Banner -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="font-bold text-sm text-slate-800">Shift Sedang Berlangsung</span>
                            <code class="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-mono">{{ $activeShift->shift_code }}</code>
                        </div>
                        <span class="text-xs text-slate-500">
                            Dibuka: {{ $activeShift->opened_at->format('d/m/Y H:i') }} WIB
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div class="bg-slate-50 p-3 rounded border border-slate-100">
                            <span class="text-slate-500 block mb-0.5">Guru Supervisor</span>
                            <span class="font-semibold text-slate-800">{{ $activeShift->supervisorTeacher->name }}</span>
                        </div>
                        <div class="bg-slate-50 p-3 rounded border border-slate-100">
                            <span class="text-slate-500 block mb-0.5">Operator Pembuka</span>
                            <span class="font-semibold text-slate-800">{{ $activeShift->openedBy->name }}</span>
                        </div>
                        <div class="bg-slate-50 p-3 rounded border border-slate-100">
                            <span class="text-slate-500 block mb-0.5">Saldo Awal Kas Laci</span>
                            <span class="font-semibold text-slate-800 num-tabular">Rp {{ number_format((float) $activeShift->opening_cash_balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="bg-slate-50 p-3 rounded border border-slate-100">
                            <span class="text-slate-500 block mb-0.5">Ekspektasi Kas Sistem</span>
                            <span class="font-bold text-brand-700 num-tabular">Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Petugas Piket Attendance Table -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Petugas Piket Siswa Bertugas</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b">
                                <tr>
                                    <th class="py-2.5 px-3">Slot</th>
                                    <th class="py-2.5 px-3">Siswa Terjadwal</th>
                                    <th class="py-2.5 px-3">Status Kehadiran</th>
                                    <th class="py-2.5 px-3">Substitusi / Operator Aktif</th>
                                    <th class="py-2.5 px-3">Waktu Masuk</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($activeShift->attendances as $att)
                                    <tr>
                                        <td class="py-2.5 px-3 font-semibold text-slate-700">Slot {{ $att->slot_number }}</td>
                                        <td class="py-2.5 px-3">
                                            <div class="font-medium text-slate-800">{{ $att->scheduledUser->name }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $att->scheduledUser->username }}</div>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            @if($att->scheduled_student_status->value === 'PRESENT')
                                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Hadir</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    {{ $att->scheduled_student_status->value }}
                                                </span>
                                                @if($att->absence_reason)
                                                    <span class="block text-[10px] text-slate-500 mt-0.5">{{ $att->absence_reason }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3">
                                            @if($att->is_substituted && $att->substituteUser)
                                                <span class="font-semibold text-indigo-700">{{ $att->substituteUser->name }}</span>
                                                <span class="text-[10px] text-indigo-500 block">(Pengganti Sah)</span>
                                            @else
                                                <span class="text-slate-700">{{ $att->scheduledUser->name }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500 num-tabular">
                                            {{ $att->check_in_at->format('H:i') }} WIB
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Col: Form Tutup Shift -->
            <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="border-b pb-3">
                    <h2 class="text-sm font-bold text-slate-900">Penutupan Shift Kasir</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dilakukan bersama Guru Piket untuk penghitungan fisik uang tunai di laci (*cash count*).</p>
                </div>

                @if($errors->has('close_pin'))
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded text-xs text-rose-700">
                        {{ $errors->first('close_pin') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('shift.close') }}" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="pos_shift_id" value="{{ $activeShift->id }}">

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Ekspektasi Kas Sistem</label>
                        <input type="text" readonly disabled
                            value="Rp {{ number_format((float) $activeShift->system_expected_cash, 0, ',', '.') }}"
                            class="w-full px-3 py-2 bg-slate-100 rounded border border-slate-200 text-slate-600 font-bold num-tabular">
                        <span class="text-[11px] text-slate-400 mt-1 block">Saldo awal + penjualan tunai shift ini</span>
                    </div>

                    <div>
                        <label for="actual_closing_cash" class="block font-medium text-slate-700 mb-1">
                            Hasil Hitung Kas Fisik Aktual (Rp) <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" step="1" id="actual_closing_cash" name="actual_closing_cash" required
                            placeholder="Contoh: 350000"
                            class="w-full px-3 py-2 rounded border border-slate-300 font-mono text-sm focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>

                    <div>
                        <label for="supervisor_pin" class="block font-medium text-slate-700 mb-1">
                            PIN Approval Guru Supervisor ({{ $activeShift->supervisorTeacher->name }}) <span class="text-rose-600">*</span>
                        </label>
                        <input type="password" id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                            placeholder="6 digit PIN Guru"
                            class="w-full px-3 py-2 rounded border border-slate-300 font-mono text-sm focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>

                    <div>
                        <label for="closing_notes" class="block font-medium text-slate-700 mb-1">Catatan Penutupan Shift</label>
                        <textarea id="closing_notes" name="closing_notes" rows="2" placeholder="Catatan kondisi operasional atau keterangan jika ada selisih kas..."
                            class="w-full px-3 py-2 rounded border border-slate-300 text-xs focus:outline-none focus:ring-1 focus:ring-brand-600"></textarea>
                    </div>

                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menutup shift ini? Kas fisik akan dikunci dan selisih akan otomatis dijurnal.')"
                        class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded transition shadow-sm">
                        Tutup Shift Kasir Sekarang
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- Open Shift Form Section -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm space-y-6">
            <div class="border-b pb-4">
                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 mb-2">
                    Shift Kasir Belum Dibuka
                </span>
                <h2 class="text-base font-bold text-slate-900">Form Pembukaan Shift Kasir Harian</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Verifikasi presensi 3 slot siswa piket MPLB (Kelas X, XI, XII), tentukan siswa pengganti jika berhalangan hadir, dan input modal kas laci awal dengan supervisi Guru Piket.
                </p>
            </div>

            @if($errors->has('supervisor_pin'))
                <div class="p-3 bg-rose-50 border border-rose-200 rounded text-xs text-rose-700">
                    {{ $errors->first('supervisor_pin') }}
                </div>
            @endif

            <form method="POST" action="{{ route('shift.open') }}" class="space-y-6">
                @csrf

                <!-- Supervisor & Modal Awal Row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label for="supervisor_teacher_id" class="block font-medium text-slate-700 mb-1">
                            Guru Piket Supervisor Bertugas Hari Ini <span class="text-rose-600">*</span>
                        </label>
                        <select id="supervisor_teacher_id" name="supervisor_teacher_id" required
                            class="w-full px-3 py-2 rounded border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-brand-600">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} (NIP: {{ $teacher->nip_nisn ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="supervisor_pin" class="block font-medium text-slate-700 mb-1">
                            PIN Approval Guru Supervisor (6 Digit) <span class="text-rose-600">*</span>
                        </label>
                        <input type="password" id="supervisor_pin" name="supervisor_pin" maxlength="6" required
                            placeholder="Default testing: 123456"
                            class="w-full px-3 py-2 rounded border border-slate-300 font-mono focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>

                    <div>
                        <label for="opening_cash_balance" class="block font-medium text-slate-700 mb-1">
                            Modal Kas Awal Laci (Rp) <span class="text-rose-600">*</span>
                        </label>
                        <input type="number" step="1" id="opening_cash_balance" name="opening_cash_balance" value="200000" required
                            class="w-full px-3 py-2 rounded border border-slate-300 font-mono text-sm focus:outline-none focus:ring-1 focus:ring-brand-600">
                    </div>
                </div>

                <!-- 3 Slots Siswa Piket -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Presensi 3 Slot Siswa Piket Bertugas</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        @for($i = 0; $i < 3; $i++)
                            @php
                                $slotNum = $i + 1;
                                $grade = match($slotNum) { 1 => 'X MPLB', 2 => 'XI MPLB', 3 => 'XII MPLB' };
                                $scheduledUser = $defaultStudents[$slotNum] ?? ($students[$i] ?? $students->first());
                            @endphp
                            <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                    <span class="font-bold text-slate-800">Slot {{ $slotNum }} ({{ $grade }})</span>
                                    <span class="text-[11px] text-slate-500">Perwakilan Kelas</span>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Siswa Terjadwal</label>
                                    <select name="slots[{{ $i }}][scheduled_user_id]" class="w-full px-2.5 py-1.5 rounded border border-slate-300 bg-white">
                                        @foreach($students as $st)
                                            <option value="{{ $st->id }}" {{ $scheduledUser && $scheduledUser->id === $st->id ? 'selected' : '' }}>
                                                {{ $st->name }} ({{ $st->username }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Status Kehadiran</label>
                                    <select name="slots[{{ $i }}][status]" onchange="toggleSubstitute(this, {{ $i }})"
                                        class="w-full px-2.5 py-1.5 rounded border border-slate-300 bg-white font-medium">
                                        <option value="PRESENT">Hadir Aktual (PRESENT)</option>
                                        <option value="SICK">Sakit (SICK)</option>
                                        <option value="PERMITTED">Izin (PERMITTED)</option>
                                        <option value="ABSENT">Alpa (ABSENT)</option>
                                        <option value="DISPENSATION">Dispensasi Sekolah</option>
                                    </select>
                                </div>

                                <!-- Substitution Sub-form (Hidden unless status != PRESENT) -->
                                <div id="substitute-box-{{ $i }}" class="hidden space-y-2 pt-2 border-t border-dashed border-slate-300">
                                    <div>
                                        <label class="block text-[11px] font-medium text-amber-800 mb-0.5">Alasan Ketidakhadiran</label>
                                        <input type="text" name="slots[{{ $i }}][absence_reason]" placeholder="Misal: Sakit demam berdarah"
                                            class="w-full px-2.5 py-1 rounded border border-slate-300 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-amber-800 mb-0.5">Pilih Siswa Pengganti (Substitusi)</label>
                                        <select name="slots[{{ $i }}][substituted_user_id]" class="w-full px-2.5 py-1 rounded border border-slate-300 bg-white">
                                            <option value="">-- Pilih Siswa Pengganti --</option>
                                            @foreach($students as $sub)
                                                <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->username }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end">
                    <button type="submit" class="py-2.5 px-6 bg-brand-700 hover:bg-brand-800 text-white font-semibold text-xs rounded transition shadow-sm">
                        Buka Shift Kasir Sekarang &rarr;
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Recent Shifts History -->
    @if($recentShifts->isNotEmpty())
        <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Riwayat 5 Shift Kasir Terakhir</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b">
                        <tr>
                            <th class="py-2 px-3">Kode Shift</th>
                            <th class="py-2 px-3">Tanggal</th>
                            <th class="py-2 px-3">Supervisor Guru</th>
                            <th class="py-2 px-3">Status</th>
                            <th class="py-2 px-3">Saldo Awal</th>
                            <th class="py-2 px-3">Kas Fisik Aktual</th>
                            <th class="py-2 px-3">Selisih Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentShifts as $rs)
                            <tr>
                                <td class="py-2 px-3 font-mono font-semibold text-slate-800">{{ $rs->shift_code }}</td>
                                <td class="py-2 px-3 text-slate-600">{{ $rs->shift_date->format('d/m/Y') }}</td>
                                <td class="py-2 px-3 text-slate-700">{{ $rs->supervisorTeacher->name }}</td>
                                <td class="py-2 px-3">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $rs->status->value === 'OPEN' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $rs->status->value }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 num-tabular">Rp {{ number_format((float) $rs->opening_cash_balance, 0, ',', '.') }}</td>
                                <td class="py-2 px-3 num-tabular">
                                    {{ $rs->actual_closing_cash ? 'Rp ' . number_format((float) $rs->actual_closing_cash, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-2 px-3 num-tabular">
                                    @if($rs->cash_discrepancy !== null)
                                        @if(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 0)
                                            <span class="text-emerald-600 font-medium">Rp 0 (Pas)</span>
                                        @elseif(bccomp((string) $rs->cash_discrepancy, '0.0000', 4) === 1)
                                            <span class="text-blue-600 font-medium">+Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (Lebih)</span>
                                        @else
                                            <span class="text-rose-600 font-medium">Rp {{ number_format((float) $rs->cash_discrepancy, 0, ',', '.') }} (Kurang)</span>
                                        @endif
                                    @else
                                        -
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
