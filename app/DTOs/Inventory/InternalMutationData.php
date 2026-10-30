<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class InternalMutationData
{
    public function __construct(
        public string $posShiftId,
        public string $unitConversionId,
        public string $sourceQtyToDeduct,
        public string $requestedByUserId,
        public string $approvedByUserId,
        public ?string $notes = null,
    ) {}
}
