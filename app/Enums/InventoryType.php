<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryType: string
{
    case RETAIL = 'RETAIL';
    case RAW_MATERIAL = 'RAW_MATERIAL';

    public function label(): string
    {
        return match ($this) {
            self::RETAIL => 'Barang Dagang (Etalase)',
            self::RAW_MATERIAL => 'Bahan Baku Jasa',
        };
    }
}
