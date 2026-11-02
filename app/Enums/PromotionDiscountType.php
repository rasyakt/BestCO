<?php

declare(strict_types=1);

namespace App\Enums;

enum PromotionDiscountType: string
{
    case PERCENTAGE = 'PERCENTAGE';
    case FIXED_AMOUNT = 'FIXED_AMOUNT';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Persentase (%)',
            self::FIXED_AMOUNT => 'Potongan Nominal (Rp)',
        };
    }
}

