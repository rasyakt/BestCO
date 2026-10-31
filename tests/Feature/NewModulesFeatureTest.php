<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Piket\OpenPosShiftAction;
use App\Actions\Pos\CheckoutPosTransactionAction;
use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\AbsenceStatus;
use App\Enums\InventoryType;
use App\Enums\PaymentMethod;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Theme\DynamicThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModulesFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $admin;
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->student = User::where('role_type', 'piket_siswa')->firstOrFail();
        $this->admin = User::where('role_type', 'admin')->firstOrFail();
        $this->superadmin = User::where('role_type', 'superadmin')->firstOrFail();

        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan kasir, kas, dan stok hari ini.');
        $response->assertSee('Penjualan hari ini');
    }

    public function test_authenticated_user_can_view_inventory_list_and_filters(): void
    {
        $response = $this->actingAs($this->student)->get('/inventory');
        $response->assertStatus(200);
        $response->assertSee('Stok barang etalase dan bahan baku jasa.');
        $response->assertSee('Daftar stok');

        $responseRetail = $this->actingAs($this->student)->get('/inventory?type=RETAIL');
        $responseRetail->assertStatus(200);
    }

    public function test_user_can_restock_product_and_update_average_cost(): void
    {
        $product = Product::where('inventory_type', InventoryType::RETAIL)->firstOrFail();
        $product->update([
            'current_stock' => '10.0000',
            'average_cost' => '40000.0000',
        ]);

        $cashAccount = ChartOfAccount::where('account_code', '1-1102')->firstOrFail();

        $response = $this->actingAs($this->admin)->post('/inventory/restock', [
            'product_id' => $product->id,
            'qty_purchased' => 10,
            'purchase_unit_price' => 60000,
            'account_id' => $cashAccount->id,
        ]);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('20.0000', $product->current_stock);
        // (10 * 40.000 + 10 * 60.000) / 20 = 50.000
        $this->assertSame('50000.0000', $product->average_cost);
    }

    public function test_user_can_view_stock_ledger(): void
    {
        $product = Product::firstOrFail();

        $response = $this->actingAs($this->admin)->get("/inventory/{$product->id}/ledger");
        $response->assertStatus(200);
        $response->assertSee('Kartu stok:');
        $response->assertSee($product->name);
    }

    public function test_user_can_view_transactions_and_void_with_supervisor_teacher_pin(): void
    {
        // 1. Setup shift aktif
        $openShiftAction = app(OpenPosShiftAction::class);
        $shift = $openShiftAction->execute(
            new ShiftOpeningData(
                supervisorTeacherId: (string) $this->teacher->id,
                openedByUserId: (string) $this->student->id,
                openingCashBalance: '100000.0000',
                slots: [
                    new StudentAttendanceSlotData(
                        piketScheduleId: null,
                        slotNumber: 1,
                        scheduledUserId: (string) $this->student->id,
                        scheduledStudentStatus: AbsenceStatus::PRESENT,
                        absenceReason: null,
                        isSubstituted: false,
                        substitutedUserId: null,
                        activeOperatorUserId: (string) $this->student->id,
                    ),
                    new StudentAttendanceSlotData(
                        piketScheduleId: null,
                        slotNumber: 2,
                        scheduledUserId: (string) $this->student->id,
                        scheduledStudentStatus: AbsenceStatus::PRESENT,
                        absenceReason: null,
                        isSubstituted: false,
                        substitutedUserId: null,
                        activeOperatorUserId: (string) $this->student->id,
                    ),
                    new StudentAttendanceSlotData(
                        piketScheduleId: null,
                        slotNumber: 3,
                        scheduledUserId: (string) $this->student->id,
                        scheduledStudentStatus: AbsenceStatus::PRESENT,
                        absenceReason: null,
                        isSubstituted: false,
                        substitutedUserId: null,
                        activeOperatorUserId: (string) $this->student->id,
                    ),
                ]
            )
        );

        // 2. Buat transaksi checkout
        $product = Product::where('inventory_type', InventoryType::RETAIL)->firstOrFail();
        $product->update(['current_stock' => '20.0000', 'selling_price' => '15000.0000', 'average_cost' => '10000.0000']);

        $checkoutAction = app(CheckoutPosTransactionAction::class);
        $tx = $checkoutAction->execute(new CheckoutPayloadData(
            posShiftId: (string) $shift->id,
            cashierUserId: (string) $this->student->id,
            customerName: 'Siswa Pembeli',
            paymentMethod: PaymentMethod::CASH,
            amountPaid: '30000.0000',
            manualDiscountTotal: '0.0000',
            items: [
                new CartItemData(
                    itemType: 'RETAIL_PRODUCT',
                    productId: (string) $product->id,
                    serviceId: null,
                    qty: '2.0000',
                    manualDiscountAmount: '0.0000',
                ),
            ],
        ));
        $product->refresh();
        $this->assertSame('18.0000', $product->current_stock);

        // 3. Coba void dengan PIN salah
        $responseFail = $this->actingAs($this->admin)->post("/transactions/{$tx->id}/void", [
            'supervisor_teacher_id' => $this->teacher->id,
            'supervisor_pin' => '000000', // salah
            'void_reason' => 'Uji PIN salah',
        ]);
        $responseFail->assertSessionHasErrors(['supervisor_pin']);

        // 4. Void dengan PIN benar
        $responseSuccess = $this->actingAs($this->admin)->post("/transactions/{$tx->id}/void", [
            'supervisor_teacher_id' => $this->teacher->id,
            'supervisor_pin' => '123456', // benar
            'void_reason' => 'Salah kuantitas pembelian',
        ]);

        $responseSuccess->assertRedirect('/transactions');
        $responseSuccess->assertSessionHas('success');

        $tx->refresh();
        $this->assertSame('VOIDED', $tx->status);
        $this->assertSame($this->teacher->id, $tx->voided_by_teacher_id);

        // Pastikan stok dikembalikan
        $product->refresh();
        $this->assertSame('20.0000', $product->current_stock);
    }

    public function test_theme_customizer_preset_and_reset(): void
    {
        $themeService = app(DynamicThemeService::class);

        // 1. Terapkan preset Emerald Slate
        $responsePreset = $this->actingAs($this->superadmin)->post('/settings/theme/preset', [
            'preset_key' => 'emerald_slate',
        ]);
        $responsePreset->assertSessionHas('success');

        $palette = $themeService->getPalette();
        $this->assertSame('#10b981', $palette['--color-primary-500']);

        // 2. Reset ke default Enterprise Blue
        $responseReset = $this->actingAs($this->superadmin)->post('/settings/theme/reset');
        $responseReset->assertSessionHas('success');

        $resetPalette = $themeService->getPalette();
        $this->assertSame('#3b82f6', $resetPalette['--color-primary-500']);
        $this->assertNull($themeService->getCustomBaseColor());
    }

    public function test_theme_custom_color_generates_full_palette(): void
    {
        $previewResponse = $this->actingAs($this->superadmin)
            ->getJson('/settings/theme/preview?color=%23ff0000');

        $previewResponse->assertOk();

        $preview = $previewResponse->json();
        $this->assertSame('#ff0000', $preview['color']);
        $this->assertCount(11, $preview['palette']);
        $this->assertArrayHasKey('--color-primary-600', $preview['palette']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $preview['palette']['--color-primary-600']);

        $customResponse = $this->actingAs($this->superadmin)->post('/settings/theme/custom', [
            'color' => '#ff0000',
        ]);

        $customResponse->assertSessionHas('success');

        $themeService = app(DynamicThemeService::class);
        $palette = $themeService->getPalette();
        $this->assertCount(11, $palette);
        $this->assertArrayHasKey('--color-primary-950', $palette);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $palette['--color-primary-700']);
        $this->assertSame('#ff0000', $themeService->getCustomBaseColor());

        $invalidResponse = $this->actingAs($this->superadmin)->post('/settings/theme/custom', [
            'color' => 'merah',
        ]);

        $invalidResponse->assertSessionHas('error');
    }

    public function test_theme_css_is_rendered_after_vite_assets(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/settings/theme');

        $response->assertOk();
        $response->assertSee('Warna sendiri');
        $response->assertSee('Ambil warna');
        $response->assertSee('Terapkan warna');

        $html = $response->getContent();
        $assetPosition = strpos($html, 'build/assets/');
        $themePosition = strpos($html, 'html:root');

        $this->assertNotFalse($assetPosition);
        $this->assertNotFalse($themePosition);
        $this->assertGreaterThan($assetPosition, $themePosition);
    }

    public function test_user_can_view_piket_attendance_and_roster(): void
    {
        $response = $this->actingAs($this->teacher)->get('/piket');

        $response->assertStatus(200);
        $response->assertSee('Presensi Piket');
        $response->assertSee('Kehadiran');
    }

    public function test_user_can_view_spoilage_log(): void
    {
        $response = $this->actingAs($this->admin)->get('/inventory/spoilage');

        $response->assertStatus(200);
        $response->assertSee('Barang Rusak');
        $response->assertSee('Nilai kerugian');
    }

    public function test_user_can_view_internal_stock_mutations(): void
    {
        $response = $this->actingAs($this->admin)->get('/inventory/mutations');

        $response->assertStatus(200);
        $response->assertSee('Pindah Stok');
        $response->assertSee('Nilai stok dipindah');
    }
}
