<?php

declare(strict_types=1);

namespace App\DTOs\Piket;

use App\Enums\AbsenceStatus;

final readonly class StudentAttendanceSlotData
{
    public function __construct(
        public int $slotNumber,
        public string $scheduledUserId,
        public AbsenceStatus $scheduledStudentStatus,
        public ?string $absenceReason,
        public bool $isSubstituted,
        public ?string $substitutedUserId,
        public string $activeOperatorUserId,
        public ?string $piketScheduleId,
    ) {}
}
