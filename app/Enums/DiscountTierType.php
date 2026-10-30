<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountTierType: string
{
    case PERCENTAGE = 'PERCENTAGE';
    case FIXED_DISCOUNT_PER_UNIT = 'FIXED_DISCOUNT_PER_UNIT';
    case SPECIAL_UNIT_PRICE = 'SPECIAL_UNIT_PRICE';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Potongan Persentase',
            self::FIXED_DISCOUNT_PER_UNIT => 'Potongan Tetap per Unit',
            self::SPECIAL_UNIT_PRICE => 'Harga Khusus per Unit',
        };
    }
}
