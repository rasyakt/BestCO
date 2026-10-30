<?php

declare(strict_types=1);

namespace App\Enums;

enum SpoilageReason: string
{
    case PAPER_JAM = 'PAPER_JAM';
    case STREAKED_PRINT = 'STREAKED_PRINT';
    case MISPRINT_OPERATOR = 'MISPRINT_OPERATOR';
    case TONER_SMEAR = 'TONER_SMEAR';
    case LAMINATING_WRINKLE = 'LAMINATING_WRINKLE';
    case PHYSICAL_DAMAGE = 'PHYSICAL_DAMAGE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::PAPER_JAM => 'Kertas Macet / Nyangkut',
            self::STREAKED_PRINT => 'Hasil Cetak Bergaris',
            self::MISPRINT_OPERATOR => 'Salah Cetak (Operator)',
            self::TONER_SMEAR => 'Nodaan Tinta / Toner',
            self::LAMINATING_WRINKLE => 'Plastik Laminating Kusut',
            self::PHYSICAL_DAMAGE => 'Kerusakan Fisik',
            self::OTHER => 'Lainnya',
        };
    }
}
