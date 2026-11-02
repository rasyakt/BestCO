<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\DiscountTierType;
use App\Enums\InventoryType;
use App\Enums\NormalBalance;
use App\Enums\ServiceGroup;
use App\Enums\UserRole;
use App\Models\AccountingPeriod;
use App\Models\AcademicYear;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\PiketSchedule;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\PosTransactionBom;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\SchoolClass;
use App\Models\Service;
use App\Models\ServiceBom;
use App\Models\ServicePriceTier;
use App\Models\ShiftAttendance;
use App\Models\SpoilageLog;
use App\Models\SpoilageLogItem;
use App\Models\StockLedgerEntry;
use App\Models\StockMutation;
use App\Models\StudentProfile;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnits();
        $this->seedCategories();
        $this->seedChartOfAccounts();
        $this->seedUsers();
        $this->seedAcademicAndClasses();
        $this->seedProducts();
        $this->seedUnitConversions();
        $this->seedServices();
        $this->seedAccountingPeriod();
        $this->seedSystemSettings();
    }

    /**
     * Seed satuan ukuran (Units).
     */
    private function seedUnits(): void
    {
        $units = [
            ['code' => 'RIM', 'name' => 'Rim', 'allow_decimal' => false],
            ['code' => 'LBR', 'name' => 'Lembar', 'allow_decimal' => false],
            ['code' => 'PCS', 'name' => 'Pcs', 'allow_decimal' => false],
            ['code' => 'PACK', 'name' => 'Pack', 'allow_decimal' => false],
            ['code' => 'GRM', 'name' => 'Gram', 'allow_decimal' => true],
            ['code' => 'KG', 'name' => 'Kilogram', 'allow_decimal' => true],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }

    /**
     * Seed kategori produk dan jasa.
     */
    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'ATK Etalase', 'slug' => 'atk-etalase', 'category_scope' => 'RETAIL'],
            ['name' => 'Bahan Baku Jasa', 'slug' => 'bahan-baku-jasa', 'category_scope' => 'RAW_MATERIAL'],
            ['name' => 'Layanan Jasa', 'slug' => 'layanan-jasa', 'category_scope' => 'SERVICE'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    /**
     * Seed Chart of Accounts lengkap (20+ perkiraan standar BESTCO).
     */
    private function seedChartOfAccounts(): void
    {
        $accounts = [
            // Aset
            ['code' => '1-1101', 'name' => 'Kas Laci Kasir (POS Cash)', 'type' => AccountType::ASSET, 'subtype' => 'CASH_BANK', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '1-1102', 'name' => 'Kas di Tangan', 'type' => AccountType::ASSET, 'subtype' => 'CASH_BANK', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '1-1103', 'name' => 'Kas di Bank', 'type' => AccountType::ASSET, 'subtype' => 'CASH_BANK', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '1-1301', 'name' => 'Persediaan Barang Dagang (Etalase)', 'type' => AccountType::ASSET, 'subtype' => 'INVENTORY_RETAIL', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '1-1302', 'name' => 'Persediaan Bahan Baku Jasa', 'type' => AccountType::ASSET, 'subtype' => 'INVENTORY_RAW', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            // Kewajiban
            ['code' => '2-1101', 'name' => 'Utang Usaha / Dagang', 'type' => AccountType::LIABILITY, 'subtype' => 'TRADE_PAYABLE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            // Ekuitas
            ['code' => '3-1101', 'name' => 'Modal Awal Teaching Factory MPLB', 'type' => AccountType::EQUITY, 'subtype' => 'OPENING_EQUITY', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            ['code' => '3-1201', 'name' => 'Laba Ditahan', 'type' => AccountType::EQUITY, 'subtype' => 'RETAINED_EARNINGS', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            // Pendapatan
            ['code' => '4-1101', 'name' => 'Pendapatan Penjualan Etalase (Ritel)', 'type' => AccountType::REVENUE, 'subtype' => 'OPERATING_REVENUE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            ['code' => '4-1102', 'name' => 'Potongan Penjualan Etalase', 'type' => AccountType::REVENUE, 'subtype' => 'SALES_DISCOUNT', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '4-2101', 'name' => 'Pendapatan Jasa Fotocopy', 'type' => AccountType::REVENUE, 'subtype' => 'SERVICE_REVENUE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            ['code' => '4-2102', 'name' => 'Pendapatan Jasa Print', 'type' => AccountType::REVENUE, 'subtype' => 'SERVICE_REVENUE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            ['code' => '4-2103', 'name' => 'Pendapatan Jasa Laminating & Jilid', 'type' => AccountType::REVENUE, 'subtype' => 'SERVICE_REVENUE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            ['code' => '4-2109', 'name' => 'Potongan Pendapatan Jasa (Tier/Grosir)', 'type' => AccountType::REVENUE, 'subtype' => 'SERVICE_DISCOUNT', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '4-9101', 'name' => 'Pendapatan Selisih Kas Lebih', 'type' => AccountType::REVENUE, 'subtype' => 'CASH_OVERAGE', 'normal' => NormalBalance::CREDIT, 'postable' => true],
            // HPP
            ['code' => '5-1101', 'name' => 'HPP Barang Dagang (Etalase)', 'type' => AccountType::COGS, 'subtype' => 'RETAIL_COGS', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '5-2101', 'name' => 'HPP Bahan Baku Jasa', 'type' => AccountType::COGS, 'subtype' => 'SERVICE_COGS', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            // Beban
            ['code' => '6-1101', 'name' => 'Beban Kerusakan / Kegagalan Produksi Jasa', 'type' => AccountType::EXPENSE, 'subtype' => 'SPOILAGE_EXPENSE', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '6-1102', 'name' => 'Beban Kerusakan Barang Etalase', 'type' => AccountType::EXPENSE, 'subtype' => 'RETAIL_DAMAGE_EXPENSE', 'normal' => NormalBalance::DEBIT, 'postable' => true],
            ['code' => '6-1901', 'name' => 'Beban Selisih Kas Kurang', 'type' => AccountType::EXPENSE, 'subtype' => 'CASH_SHORTAGE', 'normal' => NormalBalance::DEBIT, 'postable' => true],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(
                ['account_code' => $account['code']],
                [
                    'account_name' => $account['name'],
                    'account_type' => $account['type']->value,
                    'account_subtype' => $account['subtype'],
                    'normal_balance' => $account['normal']->value,
                    'is_postable' => $account['postable'],
                    'is_system_locked' => true,
                    'is_active' => true,
                    'opening_balance' => '0.0000',
                    'current_balance' => '0.0000',
                ]
            );
        }
    }

    /**
     * Seed pengguna untuk keempat role.
     */
    private function seedUsers(): void
    {
        $superadmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Superadmin BESTCO',
                'email' => 'superadmin@bestco.sch.id',
                'nip_nisn' => 'SA001',
                'password' => bcrypt('password'),
                'pin_code' => bcrypt('123456'),
                'role_type' => UserRole::SUPERADMIN->value,
                'is_active' => true,
            ]
        );

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator BESTCO',
                'email' => 'admin@bestco.sch.id',
                'nip_nisn' => 'AD001',
                'password' => bcrypt('password'),
                'pin_code' => bcrypt('123456'),
                'role_type' => UserRole::ADMIN->value,
                'is_active' => true,
            ]
        );

        // 3 Guru Piket dengan PIN 123456
        $guruPiket = [];
        for ($i = 1; $i <= 3; $i++) {
            $guruPiket[] = User::firstOrCreate(
                ['username' => "guru_piket_{$i}"],
                [
                    'name' => "Guru Piket {$i}",
                    'email' => "guru.piket.{$i}@bestco.sch.id",
                    'nip_nisn' => sprintf('GURU%03d', $i),
                    'password' => bcrypt('password'),
                    'pin_code' => bcrypt('123456'),
                    'role_type' => UserRole::PIKET_GURU->value,
                    'is_active' => true,
                ]
            );
        }

        // 9 Siswa Piket (3 per kelas X, XI, XII)
        $siswaPiket = [];
        $grades = ['X', 'XI', 'XII'];
        foreach ($grades as $gradeIndex => $grade) {
            for ($i = 1; $i <= 3; $i++) {
                $username = "siswa_{$grade}_{$i}";
                $siswaPiket[] = User::firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => "Siswa Piket {$grade} {$i}",
                        'email' => "siswa.{$grade}.{$i}@bestco.sch.id",
                        'nip_nisn' => 'NISN' . $grade . sprintf('%03d', $i),
                        'password' => bcrypt('password'),
                        'pin_code' => null,
                        'role_type' => UserRole::PIKET_SISWA->value,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info("Seeded users: 1 superadmin, 1 admin, " . count($guruPiket) . " piket_guru, " . count($siswaPiket) . " piket_siswa");
    }

    /**
     * Seed tahun akademik dan kelas.
     */
    private function seedAcademicAndClasses(): void
    {
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'semester' => 'GENAP',
                'start_date' => '2026-01-01',
                'end_date' => '2026-06-30',
                'is_active' => true,
            ]
        );

        $classes = [];
        foreach (['X', 'XI', 'XII'] as $grade) {
            $className = "{$grade} MPLB 1";
            $classes[] = SchoolClass::firstOrCreate(
                ['name' => $className, 'academic_year_id' => $academicYear->id],
                [
                    'grade_level' => $grade,
                    'homeroom_teacher_id' => null,
                ]
            );
        }

        // Buat profil siswa dan jadwal piket untuk 9 siswa
        $grades = ['X', 'XI', 'XII'];
        $students = User::where('role_type', UserRole::PIKET_SISWA->value)->get();
        foreach ($students as $index => $student) {
            $grade = $grades[$index % 3];
            $schoolClass = SchoolClass::where('name', "{$grade} MPLB 1")->first();
            $studentProfile = StudentProfile::firstOrCreate(
                ['user_id' => $student->id],
                [
                    'school_class_id' => $schoolClass->id,
                    'nis' => $grade . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT),
                    'phone' => null,
                    'total_piket_completed' => 0,
                    'total_substitute_completed' => 0,
                ]
            );

            // Jadwal piket harian (slot sesuai urutan)
            $slotNumber = ($index % 3) + 1;
            PiketSchedule::firstOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'schedule_date' => today(),
                    'slot_number' => $slotNumber,
                ],
                [
                    'day_of_week' => today()->dayOfWeekIso,
                    'school_class_id' => $schoolClass->id,
                    'scheduled_student_id' => $student->id,
                    'notes' => 'Jadwal piket harian MPLB',
                ]
            );
        }

        $this->command->info("Seeded academic year, 3 school classes, student profiles, and piket schedules.");
    }

    /**
     * Seed produk ritel dan bahan baku jasa.
     */
    private function seedProducts(): void
    {
        $categoryRetail = Category::where('category_scope', 'RETAIL')->first();
        $categoryRaw = Category::where('category_scope', 'RAW_MATERIAL')->first();

        $unitRim = Unit::where('code', 'RIM')->first();
        $unitLbr = Unit::where('code', 'LBR')->first();
        $unitGrm = Unit::where('code', 'GRM')->first();
        $unitPcs = Unit::where('code', 'PCS')->first();
        $unitPack = Unit::where('code', 'PACK')->first();

        $coaInventoryRetail = ChartOfAccount::where('account_code', '1-1301')->first();
        $coaInventoryRaw = ChartOfAccount::where('account_code', '1-1302')->first();
        $coaCogsRetail = ChartOfAccount::where('account_code', '5-1101')->first();
        $coaCogsService = ChartOfAccount::where('account_code', '5-2101')->first();

        $products = [
            // Retail products (etalase)
            ['sku' => 'ATK-HVS-A4-RIM', 'barcode' => 'ATK001', 'name' => 'Kertas HVS A4 75gr [Rim]', 'category' => $categoryRetail, 'unit' => $unitRim, 'type' => InventoryType::RETAIL, 'stock' => '10.0000', 'min' => '2.0000', 'avgCost' => '45000.0000', 'selling' => '55000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            ['sku' => 'ATK-HVS-F4-RIM', 'barcode' => 'ATK002', 'name' => 'Kertas HVS F4 75gr [Rim]', 'category' => $categoryRetail, 'unit' => $unitRim, 'type' => InventoryType::RETAIL, 'stock' => '10.0000', 'min' => '2.0000', 'avgCost' => '50000.0000', 'selling' => '60000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            ['sku' => 'ATK-PULPEN-PACK', 'barcode' => 'ATK003', 'name' => 'Pulpen [Pack]', 'category' => $categoryRetail, 'unit' => $unitPack, 'type' => InventoryType::RETAIL, 'stock' => '50.0000', 'min' => '10.0000', 'avgCost' => '18000.0000', 'selling' => '25000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            ['sku' => 'ATK-MAP-PCS', 'barcode' => 'ATK004', 'name' => 'Map Arkip [Pcs]', 'category' => $categoryRetail, 'unit' => $unitPcs, 'type' => InventoryType::RETAIL, 'stock' => '30.0000', 'min' => '5.0000', 'avgCost' => '8000.0000', 'selling' => '12000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            ['sku' => 'ATK-BUKU-PCS', 'barcode' => 'ATK005', 'name' => 'Buku Tulis [Pcs]', 'category' => $categoryRetail, 'unit' => $unitPcs, 'type' => InventoryType::RETAIL, 'stock' => '40.0000', 'min' => '10.0000', 'avgCost' => '6000.0000', 'selling' => '9000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            ['sku' => 'ATK-STAPLER-PCS', 'barcode' => 'ATK006', 'name' => 'Stapler [Pcs]', 'category' => $categoryRetail, 'unit' => $unitPcs, 'type' => InventoryType::RETAIL, 'stock' => '5.0000', 'min' => '1.0000', 'avgCost' => '35000.0000', 'selling' => '45000.0000', 'invAcct' => $coaInventoryRetail, 'cogsAcct' => $coaCogsRetail],
            // Raw material products (bahan baku jasa)
            ['sku' => 'RAW-HVS-A4-LBR', 'barcode' => 'RAW001', 'name' => 'Kertas HVS A4 [Lembar]', 'category' => $categoryRaw, 'unit' => $unitLbr, 'type' => InventoryType::RAW_MATERIAL, 'stock' => '1000.0000', 'min' => '100.0000', 'avgCost' => '90.0000', 'selling' => '0.0000', 'invAcct' => $coaInventoryRaw, 'cogsAcct' => $coaCogsService],
            ['sku' => 'RAW-HVS-F4-LBR', 'barcode' => 'RAW002', 'name' => 'Kertas HVS F4 [Lembar]', 'category' => $categoryRaw, 'unit' => $unitLbr, 'type' => InventoryType::RAW_MATERIAL, 'stock' => '500.0000', 'min' => '50.0000', 'avgCost' => '100.0000', 'selling' => '0.0000', 'invAcct' => $coaInventoryRaw, 'cogsAcct' => $coaCogsService],
            ['sku' => 'RAW-TONER-GRM', 'barcode' => 'RAW003', 'name' => 'Tinta Toner Fotocopy [Gram]', 'category' => $categoryRaw, 'unit' => $unitGrm, 'type' => InventoryType::RAW_MATERIAL, 'stock' => '1000.0000', 'min' => '100.0000', 'avgCost' => '200.0000', 'selling' => '0.0000', 'invAcct' => $coaInventoryRaw, 'cogsAcct' => $coaCogsService],
            ['sku' => 'RAW-LAM-SM-LBR', 'barcode' => 'RAW004', 'name' => 'Plastik Laminating Kecil [Lembar]', 'category' => $categoryRaw, 'unit' => $unitLbr, 'type' => InventoryType::RAW_MATERIAL, 'stock' => '200.0000', 'min' => '20.0000', 'avgCost' => '1500.0000', 'selling' => '0.0000', 'invAcct' => $coaInventoryRaw, 'cogsAcct' => $coaCogsService],
            ['sku' => 'RAW-LAM-LG-LBR', 'barcode' => 'RAW005', 'name' => 'Plastik Laminating Besar [Lembar]', 'category' => $categoryRaw, 'unit' => $unitLbr, 'type' => InventoryType::RAW_MATERIAL, 'stock' => '200.0000', 'min' => '20.0000', 'avgCost' => '2500.0000', 'selling' => '0.0000', 'invAcct' => $coaInventoryRaw, 'cogsAcct' => $coaCogsService],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                [
                    'barcode' => $product['barcode'],
                    'name' => $product['name'],
                    'category_id' => $product['category']->id,
                    'unit_id' => $product['unit']->id,
                    'inventory_type' => $product['type']->value,
                    'current_stock' => $product['stock'],
                    'min_stock_alert' => $product['min'],
                    'average_cost' => $product['avgCost'],
                    'last_purchase_price' => $product['avgCost'],
                    'selling_price' => $product['selling'],
                    'inventory_account_id' => $product['invAcct']->id,
                    'cogs_account_id' => $product['cogsAcct']->id,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("Seeded " . count($products) . " products (retail + raw material).");
    }

    /**
     * Seed konversi satuan (UoM Mutation).
     */
    private function seedUnitConversions(): void
    {
        $retailA4 = Product::where('sku', 'ATK-HVS-A4-RIM')->first();
        $rawA4 = Product::where('sku', 'RAW-HVS-A4-LBR')->first();
        $retailF4 = Product::where('sku', 'ATK-HVS-F4-RIM')->first();
        $rawF4 = Product::where('sku', 'RAW-HVS-F4-LBR')->first();

        $unitRim = Unit::where('code', 'RIM')->first();
        $unitLbr = Unit::where('code', 'LBR')->first();

        $conversions = [
            ['source' => $retailA4, 'target' => $rawA4, 'sourceUnit' => $unitRim, 'targetUnit' => $unitLbr, 'factor' => '500.0000'],
            ['source' => $retailF4, 'target' => $rawF4, 'sourceUnit' => $unitRim, 'targetUnit' => $unitLbr, 'factor' => '500.0000'],
        ];

        foreach ($conversions as $conversion) {
            UnitConversion::firstOrCreate(
                [
                    'source_product_id' => $conversion['source']->id,
                    'target_product_id' => $conversion['target']->id,
                ],
                [
                    'source_unit_id' => $conversion['sourceUnit']->id,
                    'target_unit_id' => $conversion['targetUnit']->id,
                    'conversion_factor' => $conversion['factor'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("Seeded unit conversions (1 Rim = 500 Lembar).");
    }

    /**
     * Seed jasa, tier harga, dan BoM.
     */
    private function seedServices(): void
    {
        $categoryService = Category::where('category_scope', 'SERVICE')->first();

        $coaRevenueFc = ChartOfAccount::where('account_code', '4-2101')->first();
        $coaDiscountFc = ChartOfAccount::where('account_code', '4-2109')->first();
        $coaRevenuePr = ChartOfAccount::where('account_code', '4-2102')->first();
        $coaDiscountPr = ChartOfAccount::where('account_code', '4-2109')->first();
        $coaRevenueLam = ChartOfAccount::where('account_code', '4-2103')->first();
        $coaDiscountLam = ChartOfAccount::where('account_code', '4-2109')->first();

        $rawPaperA4 = Product::where('sku', 'RAW-HVS-A4-LBR')->first();
        $rawToner = Product::where('sku', 'RAW-TONER-GRM')->first();
        $rawLamSm = Product::where('sku', 'RAW-LAM-SM-LBR')->first();
        $rawLamLg = Product::where('sku', 'RAW-LAM-LG-LBR')->first();

        $services = [
            [
                'code' => 'SRV-FC-A4',
                'name' => 'Fotocopy Hitam Putih A4',
                'group' => ServiceGroup::FOTOCOPY,
                'unit' => 'Lembar',
                'price' => '300.0000',
                'revAcct' => $coaRevenueFc,
                'discAcct' => $coaDiscountFc,
                'boms' => [
                    ['product' => $rawPaperA4, 'qty' => '1.0000'],
                    ['product' => $rawToner, 'qty' => '0.0500'],
                ],
            ],
            [
                'code' => 'SRV-PRT-BW',
                'name' => 'Print Hitam Putih',
                'group' => ServiceGroup::PRINT,
                'unit' => 'Lembar',
                'price' => '500.0000',
                'revAcct' => $coaRevenuePr,
                'discAcct' => $coaDiscountPr,
                'boms' => [
                    ['product' => $rawPaperA4, 'qty' => '1.0000'],
                    ['product' => $rawToner, 'qty' => '0.0500'],
                ],
            ],
            [
                'code' => 'SRV-PRT-CL-LIGHT',
                'name' => 'Print Warna Ringan',
                'group' => ServiceGroup::PRINT,
                'unit' => 'Lembar',
                'price' => '1000.0000',
                'revAcct' => $coaRevenuePr,
                'discAcct' => $coaDiscountPr,
                'boms' => [
                    ['product' => $rawPaperA4, 'qty' => '1.0000'],
                    ['product' => $rawToner, 'qty' => '0.0500'],
                ],
            ],
            [
                'code' => 'SRV-PRT-CL-FULL',
                'name' => 'Print Full Warna',
                'group' => ServiceGroup::PRINT,
                'unit' => 'Lembar',
                'price' => '2000.0000',
                'revAcct' => $coaRevenuePr,
                'discAcct' => $coaDiscountPr,
                'boms' => [
                    ['product' => $rawPaperA4, 'qty' => '1.0000'],
                    ['product' => $rawToner, 'qty' => '0.0500'],
                ],
            ],
            [
                'code' => 'SRV-LAM-SM',
                'name' => 'Laminating Kecil',
                'group' => ServiceGroup::LAMINATING,
                'unit' => 'Lembar',
                'price' => '3000.0000',
                'revAcct' => $coaRevenueLam,
                'discAcct' => $coaDiscountLam,
                'boms' => [
                    ['product' => $rawLamSm, 'qty' => '1.0000'],
                ],
            ],
            [
                'code' => 'SRV-LAM-LG',
                'name' => 'Laminating Besar',
                'group' => ServiceGroup::LAMINATING,
                'unit' => 'Lembar',
                'price' => '5000.0000',
                'revAcct' => $coaRevenueLam,
                'discAcct' => $coaDiscountLam,
                'boms' => [
                    ['product' => $rawLamLg, 'qty' => '1.0000'],
                ],
            ],
        ];

        foreach ($services as $serviceData) {
            $service = Service::firstOrCreate(
                ['code' => $serviceData['code']],
                [
                    'name' => $serviceData['name'],
                    'category_id' => $categoryService->id,
                    'service_group' => $serviceData['group']->value,
                    'base_unit_name' => $serviceData['unit'],
                    'base_price' => $serviceData['price'],
                    'revenue_account_id' => $serviceData['revAcct']->id,
                    'discount_account_id' => $serviceData['discAcct']->id,
                    'is_active' => true,
                ]
            );

            // Tier grosir untuk Fotocopy >= 100 lembar
            if ($serviceData['code'] === 'SRV-FC-A4') {
                ServicePriceTier::firstOrCreate(
                    ['service_id' => $service->id, 'tier_name' => 'Grosir >= 100 Lembar'],
                    [
                        'min_qty' => '100.0000',
                        'max_qty' => null,
                        'discount_type' => DiscountTierType::SPECIAL_UNIT_PRICE->value,
                        'discount_value' => '225.0000',
                        'priority' => 1,
                        'is_active' => true,
                    ]
                );
            }

            foreach ($serviceData['boms'] as $bom) {
                ServiceBom::firstOrCreate(
                    [
                        'service_id' => $service->id,
                        'raw_material_product_id' => $bom['product']->id,
                    ],
                    [
                        'consumption_qty' => $bom['qty'],
                        'is_required' => true,
                        'notes' => 'BoM layanan ' . $serviceData['name'],
                    ]
                );
            }
        }

        $this->command->info("Seeded " . count($services) . " services with price tiers and BoM.");
    }

    /**
     * Seed periode akuntansi default.
     */
    private function seedAccountingPeriod(): void
    {
        $periodName = now()->format('F Y');
        AccountingPeriod::firstOrCreate(
            ['period_name' => $periodName],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    /**
     * Seed pengaturan sistem awal.
     */
    private function seedSystemSettings(): void
    {
        SystemSetting::firstOrCreate(
            ['setting_key' => 'app_name'],
            [
                'setting_group' => 'school',
                'setting_value' => ['name' => 'BESTCO MPLB Teaching Factory'],
            ]
        );
    }
}
