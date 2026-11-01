<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Piket\ClosePosShiftAction;
use App\Actions\Piket\OpenPosShiftAction;
use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\Enums\AbsenceStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\PiketSchedule;
use App\Models\PosShift;
use App\Models\User;
use App\Rules\StrictCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ShiftController extends Controller
{
    public function index(): View
    {
        $activeShift = PosShift::with(['attendances.scheduledUser', 'attendances.substituteUser', 'attendances.activeOperator', 'supervisorTeacher', 'openedBy'])
            ->where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();

        $teachers = User::where('role_type', UserRole::PIKET_GURU)
            ->where('is_active', true)
            ->get();

        $students = User::where('role_type', UserRole::PIKET_SISWA)
            ->where('is_active', true)
            ->with('studentProfile.schoolClass')
            ->get();

        // Ambil jadwal piket hari ini jika ada
        $todaySchedules = PiketSchedule::with(['scheduledStudent', 'schoolClass'])
            ->whereDate('schedule_date', today())
            ->orderBy('slot_number')
            ->get();

        $defaultStudents = [];

        if ($todaySchedules->isNotEmpty()) {
            foreach ($todaySchedules as $sched) {
                $defaultStudents[$sched->slot_number] = $sched->scheduledStudent;
            }
        }

        // Fallback: satu siswa per tingkat X, XI, dan XII agar slot tetap
        // merepresentasikan perwakilan kelas.
        $usedStudentIds = collect($defaultStudents)
            ->filter()
            ->pluck('id')
            ->all();

        foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $slotNumber => $gradeLevel) {
            if (isset($defaultStudents[$slotNumber])) {
                continue;
            }

            $student = $students->first(function (User $candidate) use ($gradeLevel, $usedStudentIds): bool {
                return ! in_array($candidate->id, $usedStudentIds, true)
                    && $candidate->studentProfile?->schoolClass?->grade_level === $gradeLevel;
            });

            if ($student !== null) {
                $defaultStudents[$slotNumber] = $student;
                $usedStudentIds[] = $student->id;
            }
        }

        // Jika data kelas belum lengkap, isi slot tersisa dengan siswa aktif lain.
        foreach ([1, 2, 3] as $slotNumber) {
            if (isset($defaultStudents[$slotNumber])) {
                continue;
            }

            $student = $students->first(
                fn (User $candidate): bool => ! in_array($candidate->id, $usedStudentIds, true)
            );

            if ($student !== null) {
                $defaultStudents[$slotNumber] = $student;
                $usedStudentIds[] = $student->id;
            }
        }

        ksort($defaultStudents);

        $recentShifts = PosShift::with(['supervisorTeacher', 'openedBy', 'closedBy'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('shift.index', compact(
            'activeShift',
            'teachers',
            'students',
            'defaultStudents',
            'todaySchedules',
            'recentShifts'
        ));
    }

    public function open(Request $request, OpenPosShiftAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'supervisor_teacher_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('role_type', UserRole::PIKET_GURU->value)
                    ->where('is_active', true),
            ],
            'supervisor_pin' => ['required', 'string'],
            'opening_cash_balance' => ['required', new StrictCurrency(min: 0)],
            'slots' => ['required', 'array', 'size:3'],
            'slots.*.scheduled_user_id' => [
                'required',
                'distinct',
                Rule::exists('users', 'id')
                    ->where('role_type', UserRole::PIKET_SISWA->value)
                    ->where('is_active', true),
            ],
            'slots.*.status' => ['required', 'in:PRESENT,SICK,PERMITTED,ABSENT,DISPENSATION'],
            'slots.*.absence_reason' => ['nullable', 'string', 'max:255', 'required_unless:slots.*.status,PRESENT'],
            'slots.*.substituted_user_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('role_type', UserRole::PIKET_SISWA->value)
                    ->where('is_active', true),
                'required_unless:slots.*.status,PRESENT',
            ],
        ], [
            'supervisor_teacher_id.exists' => 'Guru piket yang dipilih tidak aktif atau tidak memiliki peran Piket Guru.',
            'slots.*.scheduled_user_id.exists' => 'Siswa yang dipilih tidak aktif atau bukan Piket Siswa.',
            'slots.*.substituted_user_id.exists' => 'Siswa pengganti tidak aktif atau bukan Piket Siswa.',
        ]);

        $teacher = User::findOrFail($validated['supervisor_teacher_id']);

        // Verifikasi PIN guru piket
        if (! Hash::check($validated['supervisor_pin'], (string) $teacher->pin_code)) {
            return back()->withInput()->withErrors([
                'supervisor_pin' => 'PIN Approval Guru Piket salah.',
            ]);
        }

        $slotDataList = [];
        foreach (array_values($validated['slots']) as $idx => $slot) {
            $slotNumber = $idx + 1;
            $status = AbsenceStatus::from($slot['status']);
            $isSubstituted = $status !== AbsenceStatus::PRESENT;
            $substituteId = $isSubstituted ? ($slot['substituted_user_id'] ?? null) : null;

            if ($isSubstituted && $substituteId === null) {
                return back()->withInput()->withErrors([
                    "slots.{$idx}.substituted_user_id" => 'Siswa pengganti wajib dipilih untuk slot yang tidak hadir.',
                ]);
            }

            $activeOperatorId = $isSubstituted ? $substituteId : $slot['scheduled_user_id'];

            $slotDataList[] = new StudentAttendanceSlotData(
                piketScheduleId: null,
                slotNumber: $slotNumber,
                scheduledUserId: (string) $slot['scheduled_user_id'],
                scheduledStudentStatus: $status,
                absenceReason: $slot['absence_reason'] ?? null,
                isSubstituted: $isSubstituted,
                substitutedUserId: $substituteId ? (string) $substituteId : null,
                activeOperatorUserId: (string) $activeOperatorId,
            );
        }

        try {
            $shift = $action->execute(new ShiftOpeningData(
                supervisorTeacherId: (string) $teacher->id,
                openedByUserId: (string) auth()->id(),
                openingCashBalance: number_format((float) $validated['opening_cash_balance'], 4, '.', ''),
                slots: $slotDataList,
                notes: 'Dibuka oleh ' . auth()->user()->name,
            ));
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['shift' => $exception->getMessage()]);
        }

        return redirect()->route('pos.terminal')->with('success', 'Shift ' . $shift->shift_code . ' berhasil dibuka!');
    }

    public function close(Request $request, ClosePosShiftAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'pos_shift_id' => [
                'required',
                Rule::exists('pos_shifts', 'id')->where('status', ShiftStatus::OPEN->value),
            ],
            'supervisor_pin' => ['required', 'string'],
            'actual_closing_cash' => ['required', new StrictCurrency(min: 0)],
            'closing_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'pos_shift_id.exists' => 'Shift yang akan ditutup tidak ditemukan atau sudah tidak aktif.',
        ]);

        $shift = PosShift::with('supervisorTeacher')->findOrFail($validated['pos_shift_id']);
        $teacher = $shift->supervisorTeacher;

        if ($teacher === null) {
            return back()->withInput()->withErrors([
                'close_pin' => 'Guru supervisor shift tidak ditemukan.',
            ]);
        }

        // Verifikasi PIN guru supervisor
        if (! Hash::check($validated['supervisor_pin'], (string) $teacher->pin_code)) {
            return back()->withInput()->withErrors([
                'close_pin' => 'PIN Approval Guru Piket supervisor salah.',
            ]);
        }

        try {
            $closedShift = $action->execute(
                posShiftId: (string) $shift->id,
                actualClosingCash: number_format((float) $validated['actual_closing_cash'], 4, '.', ''),
                closedByUserId: (string) auth()->id(),
                closingNotes: $validated['closing_notes'] ?? null
            );
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['close_pin' => $exception->getMessage()]);
        }

        $msg = 'Shift ' . $closedShift->shift_code . ' berhasil ditutup.';
        if (bccomp((string) $closedShift->cash_discrepancy, '0.0000', 4) !== 0) {
            $msg .= ' Terjadi selisih kas fisik Rp ' . number_format((float) $closedShift->cash_discrepancy, 0, ',', '.') . ' dan jurnal penyesuaian otomatis telah diposting.';
        }

        return redirect()->route('shift.index')->with('success', $msg);
    }
}

