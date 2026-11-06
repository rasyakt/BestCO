<?php

declare(strict_types=1);

namespace App\Services\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class FeatureManager
{
    private const SETTING_KEY = 'feature_permissions';
    private const SETTING_GROUP = 'system';

    /**
     * Cache in-memory untuk satu daur hidup request HTTP.
     *
     * @var array<string, array{is_active: bool, roles: array<int, string>}>|null
     */
    private ?array $memoryCache = null;

    /**
     * Definisi seluruh fitur yang ada di dalam aplikasi BESTCO.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     group: string,
     *     icon: string,
     *     description: string,
     *     default_enabled: bool,
     *     default_roles: array<int, string>
     * }>
     */
    public function registry(): array
    {
        return [
            'dashboard' => [
                'key' => 'dashboard',
                'label' => 'Dashboard Eksekutif',
                'group' => 'Operasional',
                'icon' => 'dashboard',
                'description' => 'Ringkasan metrik penjualan, grafik omzet harian, dan ringkasan operasional TEFA.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'pos' => [
                'key' => 'pos',
                'label' => 'Kasir / Terminal POS',
                'group' => 'Operasional',
                'icon' => 'pos',
                'description' => 'Terminal transaksi penjualan produk retail dan pesanan layanan fotokopi/jilid.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru', 'piket_siswa'],
            ],
            'transactions' => [
                'key' => 'transactions',
                'label' => 'Riwayat Transaksi',
                'group' => 'Operasional',
                'icon' => 'receipt',
                'description' => 'Pencarian riwayat struk belanja, cetak ulang nota kasir, dan pembatalan (void) transaksi.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'promotions' => [
                'key' => 'promotions',
                'label' => 'Diskon & Promo Berjadwal',
                'group' => 'Operasional',
                'icon' => 'tag',
                'description' => 'Pengelolaan program diskon persen/rupiah, kupon kasir, dan rentang waktu promo aktif.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'pos_manual_discount' => [
                'key' => 'pos_manual_discount',
                'label' => 'Diskon Manual Kasir',
                'group' => 'Operasional',
                'icon' => 'discount',
                'description' => 'Izin memasukkan potongan harga / diskon rupiah manual pada transaksi keranjang kasir POS.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru', 'piket_siswa'],
            ],
            'pos_coupon' => [
                'key' => 'pos_coupon',
                'label' => 'Kupon Promo Kasir',
                'group' => 'Operasional',
                'icon' => 'tag',
                'description' => 'Izin memasukkan dan menerapkan kode kupon/voucher promo pada keranjang kasir POS.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru', 'piket_siswa'],
            ],
            'inventory' => [
                'key' => 'inventory',
                'label' => 'Katalog Persediaan',
                'group' => 'Persediaan',
                'icon' => 'package',
                'description' => 'Master data barang retail, layanan fotokopi, konversi satuan, dan kartu stok persediaan.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'inventory_inbound' => [
                'key' => 'inventory_inbound',
                'label' => 'Barang Masuk (Inbound)',
                'group' => 'Persediaan',
                'icon' => 'download',
                'description' => 'Penerimaan stok pembelian multi-barang dari supplier dengan pendanaan Kas Tunai / Bank.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'inventory_mutations' => [
                'key' => 'inventory_mutations',
                'label' => 'Pindah Stok (Mutasi)',
                'group' => 'Persediaan',
                'icon' => 'swap',
                'description' => 'Mutasi internal barang dagang retail menjadi bahan baku layanan (misal Rim ke Lembar).',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'inventory_spoilage' => [
                'key' => 'inventory_spoilage',
                'label' => 'Barang Rusak (Spoilage)',
                'group' => 'Persediaan',
                'icon' => 'spoilage',
                'description' => 'Pencatatan kerusakan produk cacat/kadaluarsa atau kertas gagal cetak beserta beban kerugian.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'inventory_opname' => [
                'key' => 'inventory_opname',
                'label' => 'Stok Opname',
                'group' => 'Persediaan',
                'icon' => 'scale',
                'description' => 'Penghitungan stok fisik, analisis selisih, persetujuan admin, dan penyesuaian stok otomatis ke kartu stok serta jurnal.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'shift' => [
                'key' => 'shift',
                'label' => 'Shift Kasir',
                'group' => 'Tim',
                'icon' => 'clock',
                'description' => 'Buka/tutup shift kasir harian, penghitungan uang fisik laci, dan pencatatan selisih kas.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru', 'piket_siswa'],
            ],
            'piket' => [
                'key' => 'piket',
                'label' => 'Presensi Piket',
                'group' => 'Tim',
                'icon' => 'users',
                'description' => 'Jadwal dan rekaman presensi kehadiran piket guru dan siswa terjadwal pada jam operasional.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'reports' => [
                'key' => 'reports',
                'label' => 'Laporan & Akuntansi',
                'group' => 'Keuangan',
                'icon' => 'chart',
                'description' => 'Buku Kas Umum (BKU), Neraca Saldo, Laba Rugi TEFA, dan jurnal umum otomatis.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'users' => [
                'key' => 'users',
                'label' => 'Kelola Pengguna',
                'group' => 'Sistem',
                'icon' => 'users',
                'description' => 'Manajemen akun staf/siswa, reset PIN otorisasi kasir, dan aktivasi akun bertingkat.',
                'default_enabled' => true,
                'default_roles' => ['admin', 'piket_guru'],
            ],
            'settings_store' => [
                'key' => 'settings_store',
                'label' => 'Informasi Toko',
                'group' => 'Sistem',
                'icon' => 'store',
                'description' => 'Profil identitas unit TEFA, alamat, kontak telepon, dan footer nota cetak kasir.',
                'default_enabled' => true,
                'default_roles' => ['admin'],
            ],
            'settings_qris' => [
                'key' => 'settings_qris',
                'label' => 'Pengaturan QRIS',
                'group' => 'Sistem',
                'icon' => 'qr',
                'description' => 'Upload barcode QRIS statis toko untuk penerimaan pembayaran non-tunai di POS.',
                'default_enabled' => true,
                'default_roles' => ['admin'],
            ],
            'theme' => [
                'key' => 'theme',
                'label' => 'Tema Tampilan',
                'group' => 'Sistem',
                'icon' => 'palette',
                'description' => 'Kustomisasi warna aksen, mode terang/gelap, dan personalisasi visual antarmuka.',
                'default_enabled' => true,
                'default_roles' => [], // Default khusus Superadmin
            ],
        ];
    }

    /**
     * Daftar role yang dapat diatur hak aksesnya (selain Superadmin yang selalu memiliki akses penuh).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function configurableRoles(): array
    {
        return [
            ['value' => UserRole::ADMIN->value, 'label' => 'Admin'],
            ['value' => UserRole::PIKET_GURU->value, 'label' => 'Piket Guru'],
            ['value' => UserRole::PIKET_SISWA->value, 'label' => 'Piket Siswa'],
        ];
    }

    /**
     * Dapatkan konfigurasi gabungan registry dengan data yang tersimpan di database.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     group: string,
     *     icon: string,
     *     description: string,
     *     is_active: bool,
     *     roles: array<int, string>
     * }>
     */
    public function getFeatures(): array
    {
        $registry = $this->registry();
        $stored = $this->getStoredPermissions();

        $result = [];
        foreach ($registry as $key => $meta) {
            $is_active = isset($stored[$key]['is_active'])
                ? (bool) $stored[$key]['is_active']
                : $meta['default_enabled'];

            $roles = isset($stored[$key]['roles']) && is_array($stored[$key]['roles'])
                ? array_values(array_unique(array_map('strval', $stored[$key]['roles'])))
                : $meta['default_roles'];

            $result[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'group' => $meta['group'],
                'icon' => $meta['icon'],
                'description' => $meta['description'],
                'is_active' => $is_active,
                'roles' => $roles,
            ];
        }

        return $result;
    }

    /**
     * Cek apakah user berwenang mengakses fitur ini.
     *
     * Aturan:
     * 1. Jika user belum login -> false.
     * 2. Superadmin selalu memiliki akses penuh (master administrator).
     * 3. Jika fitur dinonaktifkan secara global (is_active = false) -> false.
     * 4. Jika role user terdaftar dalam izin fitur -> true.
     */
    public function canAccess(string $featureKey, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Superadmin memiliki hak akses mutlak untuk operasional dan perbaikan darurat
        if ($user->hasRole(UserRole::SUPERADMIN)) {
            return true;
        }

        $features = $this->getFeatures();
        if (! isset($features[$featureKey])) {
            return false;
        }

        $feature = $features[$featureKey];

        // Jika fitur dinonaktifkan di tingkat sistem
        if (! $feature['is_active']) {
            return false;
        }

        $userRole = is_string($user->role_type)
            ? $user->role_type
            : ($user->role_type?->value ?? '');

        return in_array($userRole, $feature['roles'], true);
    }

    /**
     * Cek apakah fitur diaktifkan secara global di sistem.
     */
    public function isGloballyEnabled(string $featureKey): bool
    {
        $features = $this->getFeatures();

        return isset($features[$featureKey]) && $features[$featureKey]['is_active'];
    }

    /**
     * Perbarui izin matriks fitur dari form Superadmin.
     *
     * @param array<string, array{is_active?: mixed, roles?: array<int, string>}> $inputFeatures
     */
    public function updatePermissions(array $inputFeatures, int $userId): void
    {
        $registry = $this->registry();
        $cleaned = [];

        foreach ($registry as $key => $meta) {
            $input = $inputFeatures[$key] ?? [];

            // Status aktif/nonaktif: bernilai true jika checkbox/toggle bernilai '1', 'true', true, atau 1
            $isActive = ! empty($input['is_active']);

            // Role yang diizinkan
            $roles = isset($input['roles']) && is_array($input['roles'])
                ? array_values(array_intersect(
                    ['admin', 'piket_guru', 'piket_siswa'],
                    array_map('strval', $input['roles'])
                ))
                : [];

            $cleaned[$key] = [
                'is_active' => $isActive,
                'roles' => $roles,
            ];
        }

        SystemSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY],
            [
                'setting_group' => self::SETTING_GROUP,
                'setting_value' => $cleaned,
                'updated_by' => $userId,
            ]
        );

        $this->flushCache();
    }

    /**
     * Aktifkan seluruh fitur secara global.
     */
    public function enableAll(int $userId): void
    {
        $features = $this->getFeatures();
        $cleaned = [];

        foreach ($features as $key => $item) {
            $cleaned[$key] = [
                'is_active' => true,
                'roles' => $item['roles'],
            ];
        }

        SystemSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY],
            [
                'setting_group' => self::SETTING_GROUP,
                'setting_value' => $cleaned,
                'updated_by' => $userId,
            ]
        );

        $this->flushCache();
    }

    /**
     * Kembalikan pengaturan fitur ke default bawaan sistem.
     */
    public function resetToDefault(int $userId): void
    {
        SystemSetting::where('setting_key', self::SETTING_KEY)->delete();
        $this->flushCache();
    }

    /**
     * Ambil data tersimpan dari basis data.
     *
     * @return array<string, array{is_active: bool, roles: array<int, string>}>
     */
    private function getStoredPermissions(): array
    {
        if ($this->memoryCache !== null) {
            return $this->memoryCache;
        }

        try {
            $setting = SystemSetting::where('setting_key', self::SETTING_KEY)->first();
            if ($setting && is_array($setting->setting_value)) {
                $this->memoryCache = $setting->setting_value;

                return $this->memoryCache;
            }
        } catch (\Throwable) {
            // Jika tabel belum siap saat migrasi awal
        }

        $this->memoryCache = [];

        return $this->memoryCache;
    }

    /**
     * Bersihkan memory cache.
     */
    public function flushCache(): void
    {
        $this->memoryCache = null;
    }
}

