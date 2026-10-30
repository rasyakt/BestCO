<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case ASSET = 'ASSET';
    case LIABILITY = 'LIABILITY';
    case EQUITY = 'EQUITY';
    case REVENUE = 'REVENUE';
    case COGS = 'COGS';
    case EXPENSE = 'EXPENSE';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Aset',
            self::LIABILITY => 'Kewajiban',
            self::EQUITY => 'Ekuitas',
            self::REVENUE => 'Pendapatan',
            self::COGS => 'HPP',
            self::EXPENSE => 'Beban',
        };
    }
}
