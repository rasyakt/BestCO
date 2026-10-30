<?php

declare(strict_types=1);

namespace App\Enums;

enum JournalType: string
{
    case GENERAL = 'GENERAL';
    case SALES_POS = 'SALES_POS';
    case INTERNAL_MUTATION = 'INTERNAL_MUTATION';
    case SPOILAGE = 'SPOILAGE';
    case PURCHASE = 'PURCHASE';
    case CASH_DISCREPANCY = 'CASH_DISCREPANCY';
    case ADJUSTMENT = 'ADJUSTMENT';
    case CLOSING = 'CLOSING';
    case REVERSING = 'REVERSING';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'Umum',
            self::SALES_POS => 'Penjualan POS',
            self::INTERNAL_MUTATION => 'Mutasi Internal',
            self::SPOILAGE => 'Barang Rusak',
            self::PURCHASE => 'Pembelian',
            self::CASH_DISCREPANCY => 'Selisih Kas',
            self::ADJUSTMENT => 'Penyesuaian',
            self::CLOSING => 'Penutupan Buku',
            self::REVERSING => 'Pembalikan',
        };
    }
}
