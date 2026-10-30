<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\Enums\SpoilageReason;

final readonly class SpoilageData
{
    /**
     * @param array<int, array{product_id: string, qty_wasted: string, unit_cost: string}> $directItems
     *   Item rusak langsung (untuk SPOILAGE_SOURCE = RETAIL_DAMAGE / RAW_MATERIAL_DAMAGE).
     */
    public function __construct(
        public string $posShiftId,
        public string $spoilageSource,
        public ?string $serviceId,
        public string $failedOutputQty,
        public SpoilageReason $reasonCategory,
        public string $reasonDescription,
        public string $reportedByUserId,
        public ?string $verifiedByUserId = null,
        public array $directItems = [],
    ) {}
}
