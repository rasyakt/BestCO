<?php

declare(strict_types=1);

namespace App\DTOs\Pos;

use App\Enums\PaymentMethod;

final readonly class CheckoutPayloadData
{
    public function __construct(
        public string $posShiftId,
        public string $cashierUserId,
        public ?string $customerName,
        public PaymentMethod $paymentMethod,
        public string $amountPaid,
        public string $manualDiscountTotal,
        /** @var CartItemData[] */
        public array $items,
    ) {}
}
