<?php

declare(strict_types=1);

namespace App\Enums;

enum StockOpnameStatus: string
{
    case DRAFT = 'DRAFT';
    case APPROVED = 'APPROVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::APPROVED => 'Disetujui',
        };
    }
}
