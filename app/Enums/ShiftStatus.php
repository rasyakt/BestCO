<?php

declare(strict_types=1);

namespace App\Enums;

enum ShiftStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
    case AUDITED = 'AUDITED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Buka',
            self::CLOSED => 'Tutup',
            self::AUDITED => 'Terdapat Audit',
        };
    }
}
