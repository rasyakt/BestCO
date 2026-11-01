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
            ->get();

        // Ambil jadwal piket hari ini jika ada
        $todaySchedules = PiketSchedule::with(['scheduledStudent', 'schoolClass'])
            ->whereDate('schedule_date', today())
            ->orderBy('slot_number')
            ->get();

        // Jika tidak ada di DB untuk hari ini, ambil 3 siswa pertama dari X, XI, XII
        if ($todaySchedules->isEmpty() && $students->count() >= 3) {
            $defaultStudents = [
                1 => $students[0],
                2 => $students[1],
                3 => $students[2],
            ];
        } else {
            $defaultStudents = [];
            foreach ($todaySchedules as $sched) {
                $defaultStudents[$sched->slot_number] = $sched->scheduledStudent;
            }
        }

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
            'supervisor_teacher_id' => ['required', 'exists:users,id'],
            'supervisor_pin' => ['required', 'string'],
            'opening_cash_balance' => ['required', new StrictCurrency(min: 0)],
            'slots' => ['required', 'array', 'size:3'],
            'slots.*.scheduled_user_id' => ['required', 'exists:users,id'],
            'slots.*.status' => ['required', 'in:PRESENT,SICK,PERMITTED,ABSENT,DISPENSATION'],
            'slots.*.absence_reason' => ['nullable', 'string', 'required_unless:slots.*.status,PRESENT'],
            'slots.*.substituted_user_id' => ['nullable', 'exists:users,id', 'required_unless:slots.*.status,PRESENT'],
        ]);

        $teacher = User::findOrFail($validated['supervisor_teacher_id']);

        // Verifikasi PIN guru piket
        if (!Hash::check($validated['supervisor_pin'], $teacher->pin_code)) {
            return back()->withInput()->withErrors([
                'supervisor_pin' => 'PIN Approval Guru Piket salah.',
            ]);
        }

        $slotDataList = [];
        foreach ($validated['slots'] as $idx => $slot) {
            $slotNumber = $idx + 1;
            $status = AbsenceStatus::from($slot['status']);
            $isSubstituted = $status !== AbsenceStatus::PRESENT;
            $substituteId = $isSubstituted ? $slot['substituted_user_id'] : null;
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

        $shift = $action->execute(new ShiftOpeningData(
            supervisorTeacherId: (string) $teacher->id,
            openedByUserId: (string) auth()->id(),
            openingCashBalance: number_format((float) $validated['opening_cash_balance'], 4, '.', ''),
            slots: $slotDataList,
            notes: 'Dibuka oleh ' . auth()->user()->name,
        ));

        return redirect()->route('pos.terminal')->with('success', 'Shift ' . $shift->shift_code . ' berhasil dibuka!');
    }

    public function close(Request $request, ClosePosShiftAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'pos_shift_id' => ['required', 'exists:pos_shifts,id'],
            'supervisor_pin' => ['required', 'string'],
            'actual_closing_cash' => ['required', new StrictCurrency(min: 0)],
            'closing_notes' => ['nullable', 'string'],
        ]);

        $shift = PosShift::findOrFail($validated['pos_shift_id']);
        $teacher = $shift->supervisorTeacher;

        // Verifikasi PIN guru supervisor
        if (!Hash::check($validated['supervisor_pin'], $teacher->pin_code)) {
            return back()->withInput()->withErrors([
                'close_pin' => 'PIN Approval Guru Piket supervisor salah.',
            ]);
        }

        $closedShift = $action->execute(
            posShiftId: (string) $shift->id,
            actualClosingCash: number_format((float) $validated['actual_closing_cash'], 4, '.', ''),
            closedByUserId: (string) auth()->id(),
            closingNotes: $validated['closing_notes'] ?? null
        );

        $msg = 'Shift ' . $closedShift->shift_code . ' berhasil ditutup.';
        if (bccomp((string) $closedShift->cash_discrepancy, '0.0000', 4) !== 0) {
            $msg .= ' Terjadi selisih kas fisik Rp ' . number_format((float) $closedShift->cash_discrepancy, 0, ',', '.') . ' dan jurnal penyesuaian otomatis telah diposting.';
        }

        return redirect()->route('shift.index')->with('success', $msg);
    }
}

