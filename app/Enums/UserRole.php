<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case PIKET_GURU = 'piket_guru';
    case PIKET_SISWA = 'piket_siswa';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::ADMIN => 'Admin',
            self::PIKET_GURU => 'Piket Guru',
            self::PIKET_SISWA => 'Piket Siswa',
        };
    }
}
