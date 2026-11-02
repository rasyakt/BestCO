<?php

declare(strict_types=1);

namespace App\Enums;

enum PromotionScope: string
{
    case ALL_ITEMS = 'ALL_ITEMS';
    case CATEGORY = 'CATEGORY';
    case PRODUCT = 'PRODUCT';
    case SERVICE = 'SERVICE';
    case MIN_SPEND = 'MIN_SPEND';

    public function label(): string
    {
        return match ($this) {
            self::ALL_ITEMS => 'Semua Produk & Jasa',
            self::CATEGORY => 'Kategori Tertentu',
            self::PRODUCT => 'Produk Ritel Tertentu',
            self::SERVICE => 'Layanan Jasa Tertentu',
            self::MIN_SPEND => 'Minimal Total Belanja',
        };
    }
}

