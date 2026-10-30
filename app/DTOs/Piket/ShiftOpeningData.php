<?php

declare(strict_types=1);

namespace App\DTOs\Piket;

final readonly class ShiftOpeningData
{
    /**
     * @param StudentAttendanceSlotData[] $slots
     */
    public function __construct(
        public string $posShiftId,
        public string $supervisorTeacherId,
        public string $openedByUserId,
        public string $openingCashBalance,
        public array $slots,
        public ?string $notes = null,
    ) {}
}
