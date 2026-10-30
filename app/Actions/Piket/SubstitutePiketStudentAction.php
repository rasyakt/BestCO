<?php

declare(strict_types=1);

namespace App\Actions\Piket;

use App\Models\PiketSchedule;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SubstitutePiketStudentAction
{
    /**
     * Catat penggantian siswa piket pada jadwal tertentu (dikeluarkan saat pembukaan shift).
     */
    public function execute(
        string $piketScheduleId,
        string $substitutedUserId,
        string $absenceReason,
        string $approvedByTeacherId,
    ): PiketSchedule {
        return DB::transaction(function () use (
            $piketScheduleId,
            $substitutedUserId,
            $absenceReason,
            $approvedByTeacherId
        ): PiketSchedule {
            $schedule = PiketSchedule::where('id', $piketScheduleId)->lockForUpdate()->firstOrFail();
            $substitute = User::where('id', $substitutedUserId)
                ->where('role_type', 'piket_siswa')
                ->firstOrFail();

            // Catat substitusi pada statistik siswa pengganti.
            $profile = $substitute->studentProfile;
            if ($profile) {
                $profile->increment('total_substitute_completed');
            }

            return $schedule;
        });
    }
}
