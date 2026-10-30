<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceGroup: string
{
    case FOTOCOPY = 'FOTOCOPY';
    case PRINT = 'PRINT';
    case LAMINATING = 'LAMINATING';
    case BINDING = 'BINDING';
    case SCAN = 'SCAN';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::FOTOCOPY => 'Fotocopy',
            self::PRINT => 'Print',
            self::LAMINATING => 'Laminating',
            self::BINDING => 'Penjilidan',
            self::SCAN => 'Scan',
            self::OTHER => 'Lainnya',
        };
    }
}
