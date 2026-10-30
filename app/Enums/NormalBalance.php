<?php

declare(strict_types=1);

namespace App\Enums;

enum NormalBalance: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'Debit',
            self::CREDIT => 'Kredit',
        };
    }
}
