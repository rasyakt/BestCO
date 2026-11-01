<?php

declare(strict_types=1);

namespace App\Actions\Piket;

use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\Enums\AbsenceStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\PiketSchedule;
use App\Models\PosShift;
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
            $existingOpenShift = PosShift::where('status', ShiftStatus::OPEN->value)
                ->lockForUpdate()
                ->first();

            if ($existingOpenShift !== null) {
                throw new \DomainException(
                    'Masih ada shift kasir yang terbuka (' . $existingOpenShift->shift_code . '). Tutup shift tersebut terlebih dahulu.'
                );
            }

            $supervisor = User::where('id', $data->supervisorTeacherId)
                ->where('role_type', UserRole::PIKET_GURU)
                ->where('is_active', true)
                ->firstOrFail();

            $openedBy = User::where('id', $data->openedByUserId)
                ->where('is_active', true)
                ->firstOrFail();

            if (! $openedBy->hasAnyRole([UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::PIKET_GURU])) {
                throw new \DomainException('Hanya staf yang dapat membuka shift kasir.');
            }

            if (count($data->slots) !== 3) {
                throw new \DomainException('Shift kasir memerlukan tepat 3 petugas piket.');
            }

            $slotsByNumber = [];
            $scheduledIds = [];
            $operatorIds = [];

            foreach ($data->slots as $slot) {
                if (! in_array($slot->slotNumber, [1, 2, 3], true)) {
                    throw new \DomainException('Nomor slot petugas piket tidak valid.');
                }

                if (isset($slotsByNumber[$slot->slotNumber])) {
                    throw new \DomainException('Slot petugas piket tidak boleh duplikat.');
                }

                if (isset($scheduledIds[$slot->scheduledUserId])) {
                    throw new \DomainException('Setiap slot harus diisi siswa piket yang berbeda.');
                }

                User::where('id', $slot->scheduledUserId)
                    ->where('role_type', UserRole::PIKET_SISWA)
                    ->where('is_active', true)
                    ->firstOrFail();

                if ($slot->scheduledStudentStatus === AbsenceStatus::PRESENT) {
                    if ($slot->isSubstituted || $slot->substitutedUserId !== null) {
                        throw new \DomainException('Slot hadir tidak boleh memiliki siswa pengganti.');
                    }

                    $activeOperatorId = $slot->scheduledUserId;
                } else {
                    if (
                        ! $slot->isSubstituted
                        || $slot->substitutedUserId === null
                        || trim((string) $slot->absenceReason) === ''
                    ) {
                        throw new \DomainException('Slot tidak hadir wajib memiliki alasan dan siswa pengganti.');
                    }

                    $activeOperatorId = $slot->substitutedUserId;
                }

                if ($slot->activeOperatorUserId !== $activeOperatorId) {
                    throw new \DomainException('Operator aktif tidak konsisten dengan status kehadiran slot.');
                }

                $operator = User::where('id', $activeOperatorId)
                    ->where('role_type', UserRole::PIKET_SISWA)
                    ->where('is_active', true)
                    ->firstOrFail();

                if (isset($operatorIds[$operator->id])) {
                    throw new \DomainException('Setiap slot harus memiliki operator aktif yang berbeda.');
                }

                $slotsByNumber[$slot->slotNumber] = $slot;
                $scheduledIds[$slot->scheduledUserId] = true;
                $operatorIds[$operator->id] = true;
            }

            ksort($slotsByNumber);

            if (array_keys($slotsByNumber) !== [1, 2, 3]) {
                throw new \DomainException('Shift kasir memerlukan slot 1, 2, dan 3.');
            }

            $sequence = PosShift::whereDate('shift_date', today())->count() + 1;

            do {
                $shiftCode = 'SHF-' . Carbon::now()->format('Ymd') . '-'
                    . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
                $sequence++;
            } while (PosShift::where('shift_code', $shiftCode)->exists());

            $shift = PosShift::create([
                'shift_code' => $shiftCode,
                'shift_date' => today(),
                'supervisor_teacher_id' => $supervisor->id,
                'opened_by_user_id' => $openedBy->id,
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

            foreach ($slotsByNumber as $slot) {
                $piketScheduleId = $slot->piketScheduleId;

                if ($piketScheduleId === null) {
                    $piketScheduleId = PiketSchedule::whereDate('schedule_date', today())
                        ->where('slot_number', $slot->slotNumber)
                        ->where('scheduled_student_id', $slot->scheduledUserId)
                        ->value('id');
                }

                ShiftAttendance::create([
                    'pos_shift_id' => $shift->id,
                    'piket_schedule_id' => $piketScheduleId,
                    'slot_number' => $slot->slotNumber,
                    'scheduled_user_id' => $slot->scheduledUserId,
                    'scheduled_student_status' => $slot->scheduledStudentStatus->value,
                    'absence_reason' => $slot->absenceReason,
                    'is_substituted' => $slot->isSubstituted,
                    'substituted_user_id' => $slot->substitutedUserId,
                    'active_operator_user_id' => $slot->activeOperatorUserId,
                    'check_in_at' => Carbon::now(),
                    'check_out_at' => null,
                    'approved_by_teacher_id' => $supervisor->id,
                ]);

                // Increment statistik piket siswa.
                if ($slot->scheduledStudentStatus !== AbsenceStatus::PRESENT) {
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
