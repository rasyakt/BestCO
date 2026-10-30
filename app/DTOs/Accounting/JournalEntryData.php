<?php

declare(strict_types=1);

namespace App\DTOs\Accounting;

use App\Enums\JournalType;

final readonly class JournalEntryData
{
    public function __construct(
        public string $journalNumber,
        public string $accountingPeriodId,
        public ?string $posShiftId,
        public \DateTimeInterface $entryDate,
        public JournalType $journalType,
        public ?string $referenceType,
        public ?int $referenceId,
        public string $description,
        /** @var JournalLineData[] */
        public array $lines,
        public string $createdByUserId,
        public ?string $reversedByJournalId = null,
    ) {}
}
