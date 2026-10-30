<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case QRIS = 'QRIS';
    case TRANSFER = 'TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai',
            self::QRIS => 'QRIS',
            self::TRANSFER => 'Transfer',
        };
    }
}
