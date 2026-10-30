<?php

declare(strict_types=1);

namespace App\DTOs\Pos;

final readonly class CartItemData
{
    public function __construct(
        public string $itemType, // 'RETAIL_PRODUCT' or 'SERVICE'
        public ?string $productId,
        public ?string $serviceId,
        public string $qty,
        public string $manualDiscountAmount,
    ) {}
}
