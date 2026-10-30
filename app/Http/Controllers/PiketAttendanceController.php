<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AbsenceStatus;
use App\Models\PiketSchedule;
use App\Models\ShiftAttendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PiketAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'ALL');
        $dateFilter = $request->query('date', '');

        // 1. Statistik Presensi
        $totalAttendances = ShiftAttendance::count();
        $totalPresent = ShiftAttendance::where('scheduled_student_status', AbsenceStatus::PRESENT)->count();
        $totalSubstituted = ShiftAttendance::where('is_substituted', true)->count();
        $totalAbsent = ShiftAttendance::where('scheduled_student_status', AbsenceStatus::ABSENT)->count();
        $attendanceRate = $totalAttendances > 0 ? round(($totalPresent / $totalAttendances) * 100, 1) : 100.0;

        // 2. Jadwal Piket Hari Ini (day_of_week: 1=Senin ... 7=Minggu)
        $dayOfWeek = (int) now()->dayOfWeekIso;
        $todaySchedules = PiketSchedule::with(['schoolClass', 'scheduledStudent'])
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('slot_number')
            ->get();

        // 3. Riwayat Presensi Detail
        $query = ShiftAttendance::with([
            'posShift',
            'scheduledUser',
            'substituteUser',
            'activeOperator',
            'approvedByTeacher',
        ])->latest('id');

        if ($statusFilter !== 'ALL') {
            $query->where('scheduled_student_status', $statusFilter);
        }

        if ($dateFilter !== '') {
            $query->whereHas('posShift', function ($q) use ($dateFilter): void {
                $q->whereDate('shift_date', $dateFilter);
            });
        }

        $attendances = $query->paginate(20);

        return view('piket.index', compact(
            'attendances',
            'totalAttendances',
            'totalPresent',
            'totalSubstituted',
            'totalAbsent',
            'attendanceRate',
            'todaySchedules',
            'statusFilter',
            'dateFilter'
        ));
    }
}
