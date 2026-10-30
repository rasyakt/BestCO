<?php

declare(strict_types=1);

namespace App\Enums;

enum AbsenceStatus: string
{
    case PRESENT = 'PRESENT';
    case SICK = 'SICK';
    case PERMITTED = 'PERMITTED';
    case ABSENT = 'ABSENT';
    case DISPENSATION = 'DISPENSATION';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Hadir',
            self::SICK => 'Sakit',
            self::PERMITTED => 'Izin',
            self::ABSENT => 'Alpa',
            self::DISPENSATION => 'Dispensasi',
        };
    }
}
