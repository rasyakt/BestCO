<?php

declare(strict_types=1);

namespace App\Actions\Piket;

use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\Enums\AbsenceStatus;
use App\Enums\ShiftStatus;
use App\Models\PosShift;
use App\Models\PiketSchedule;
use App\Models\ShiftAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class OpenPosShiftAction
{
    /**
     * Buka shift kasir POS: catat pos_shift, 3 shift_attendances (dengan substitusi jika ada),
     * dan opsi setoran modal awal ke BKU. Semua dalam satu DB transaction.
     */
    public function execute(ShiftOpeningData $data): PosShift
    {
        return DB::transaction(function () use ($data): PosShift {
            $supervisor = User::where('id', $data->supervisorTeacherId)
                ->whereIn('role_type', ['piket_guru', 'admin', 'superadmin'])
                ->firstOrFail();

            $shiftCode = 'SHF-' . Carbon::now()->format('Ymd') . '-' .
                str_pad((string) (PosShift::whereDate('shift_date', today())->count() + 1), 2, '0', STR_PAD_LEFT);

            $shift = PosShift::create([
                'shift_code' => $shiftCode,
                'shift_date' => today(),
                'supervisor_teacher_id' => $data->supervisorTeacherId,
                'opened_by_user_id' => $data->openedByUserId,
                'closed_by_user_id' => null,
                'opened_at' => Carbon::now(),
                'closed_at' => null,
                'opening_cash_balance' => $data->openingCashBalance,
                'system_expected_cash' => $data->openingCashBalance,
                'actual_closing_cash' => null,
                'cash_discrepancy' => null,
                'status' => ShiftStatus::OPEN->value,
                'closing_notes' => null,
            ]);

            $today = today();
            $dayOfWeek = $today->dayOfWeek; // 1=Sun ... 7=Sat in Laravel; sesuaikan ke 1=Senin

            foreach ($data->slots as $slot) {
                $piketSchedule = PiketSchedule::where('id', $slot->piketScheduleId)->first();

                ShiftAttendance::create([
                    'pos_shift_id' => $shift->id,
                    'piket_schedule_id' => $slot->piketScheduleId,
                    'slot_number' => $slot->slotNumber,
                    'scheduled_user_id' => $slot->scheduledUserId,
                    'scheduled_student_status' => $slot->scheduledStudentStatus->value,
                    'absence_reason' => $slot->absenceReason,
                    'is_substituted' => $slot->isSubstituted,
                    'substituted_user_id' => $slot->substitutedUserId,
                    'active_operator_user_id' => $slot->activeOperatorUserId,
                    'check_in_at' => Carbon::now(),
                    'check_out_at' => null,
                    'approved_by_teacher_id' => $data->supervisorTeacherId,
                ]);

                // Increment statistik piket siswa.
                if ($slot->scheduledStudentStatus !== AbsenceStatus::PRESENT) {
                    // Siswa yang diganti: hitung sebagai substitusi untuk pengganti.
                    if ($slot->isSubstituted && $slot->substitutedUserId) {
                        $subProfile = StudentProfile::where('user_id', $slot->substitutedUserId)->first();
                        if ($subProfile) {
                            $subProfile->increment('total_substitute_completed');
                        }
                    }
                } else {
                    $profile = StudentProfile::where('user_id', $slot->scheduledUserId)->first();
                    if ($profile) {
                        $profile->increment('total_piket_completed');
                    }
                }
            }

            return $shift->fresh(['attendances.user', 'supervisorTeacher', 'openedBy']);
        });
    }
}
