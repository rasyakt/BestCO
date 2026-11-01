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

    /**
     * Tingkatan role. Semakin tinggi nilainya, semakin besar kewenangannya.
     */
    public function level(): int
    {
        return match ($this) {
            self::SUPERADMIN => 4,
            self::ADMIN => 3,
            self::PIKET_GURU => 2,
            self::PIKET_SISWA => 1,
        };
    }

    /**
     * Role yang boleh dikelola oleh role ini.
     *
     * Superadmin mengelola semua role. Admin mengelola guru & siswa piket.
     * Guru piket hanya mengelola siswa piket. Siswa tidak mengelola siapa pun.
     *
     * @return array<int, self>
     */
    public function manageableRoles(): array
    {
        return match ($this) {
            self::SUPERADMIN => self::cases(),
            self::ADMIN => [self::PIKET_GURU, self::PIKET_SISWA],
            self::PIKET_GURU => [self::PIKET_SISWA],
            self::PIKET_SISWA => [],
        };
    }

    /**
     * Role yang boleh ditetapkan saat membuat atau mengubah akun.
     *
     * Superadmin tidak dapat membuat superadmin baru karena akun superadmin
     * hanya satu. Role superadmin tetap dapat dilihat dan diedit oleh dirinya
     * sendiri lewat pengecualian di UserController.
     *
     * @return array<int, self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SUPERADMIN => [self::ADMIN, self::PIKET_GURU, self::PIKET_SISWA],
            self::ADMIN => [self::PIKET_GURU, self::PIKET_SISWA],
            self::PIKET_GURU => [self::PIKET_SISWA],
            self::PIKET_SISWA => [],
        };
    }

    public function canManage(self $role): bool
    {
        return in_array($role, $this->manageableRoles(), true);
    }
}
