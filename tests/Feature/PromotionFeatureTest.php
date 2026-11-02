<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Pos\CheckoutPosTransactionAction;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\PaymentMethod;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use App\Models\AccountingPeriod;
use App\Models\Category;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\ShiftAttendance;
use App\Models\User;
use App\Services\Pos\PromotionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $teacher;
    private User $student;
    private PosShift $activeShift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superadmin = User::where('role_type', 'superadmin')->firstOrFail();
        $this->admin = User::where('role_type', 'admin')->firstOrFail();
        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->student = User::where('role_type', 'piket_siswa')->firstOrFail();

        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );

        // Buat shift kasir terbuka untuk pengujian transaksi
        $this->activeShift = PosShift::create([
            'shift_code' => 'SHF-TEST-PROMO',
            'shift_date' => now()->toDateString(),
            'supervisor_teacher_id' => $this->teacher->id,
            'opened_by_user_id' => $this->teacher->id,
            'opening_cash_balance' => '100000.0000',
            'system_expected_cash' => '100000.0000',
            'status' => 'OPEN',
        ]);

        ShiftAttendance::create([
            'pos_shift_id' => $this->activeShift->id,
            'piket_schedule_id' => null,
            'slot_number' => 1,
            'scheduled_user_id' => $this->student->id,
            'scheduled_student_status' => 'PRESENT',
            'active_operator_user_id' => $this->student->id,
            'check_in_at' => now(),
            'approved_by_teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_student_cannot_access_promotion_crud(): void
    {
        $this->actingAs($this->student)->get(route('promotions.index'))->assertStatus(403);
        $this->actingAs($this->student)->get(route('promotions.create'))->assertStatus(403);
        $this->actingAs($this->student)->post(route('promotions.store'), [])->assertStatus(403);
    }

    public function test_admin_and_teacher_can_view_promotions_index(): void
    {
        $resAdmin = $this->actingAs($this->admin)->get(route('promotions.index'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Diskon &amp; Promo', false);

        $resTeacher = $this->actingAs($this->teacher)->get(route('promotions.index'));
        $resTeacher->assertStatus(200);
        $resTeacher->assertSee('Buat Promo Baru');
    }

    public function test_admin_can_create_percentage_promotion_with_time_range(): void
    {
        $category = Category::firstOrFail();

        $payload = [
            'name' => 'Promo Diskon Kategori ATK 15%',
            'code' => 'ATK15',
            'description' => 'Diskon khusus alat tulis kantor.',
            'scope' => PromotionScope::CATEGORY->value,
            'category_id' => $category->id,
            'discount_type' => PromotionDiscountType::PERCENTAGE->value,
            'discount_value' => '15',
            'min_order_amount' => '20000',
            'max_discount_amount' => '10000',
            'start_datetime' => now()->format('Y-m-d\TH:i'),
            'end_datetime' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('promotions.store'), $payload);
        $response->assertRedirect(route('promotions.index'));

        $this->assertDatabaseHas('promotions', [
            'name' => 'Promo Diskon Kategori ATK 15%',
            'code' => 'ATK15',
            'scope' => PromotionScope::CATEGORY->value,
            'category_id' => $category->id,
            'discount_type' => PromotionDiscountType::PERCENTAGE->value,
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_toggle_promotion_status(): void
    {
        $promo = Promotion::firstOrFail();
        $initialStatus = $promo->is_active;

        $response = $this->actingAs($this->teacher)->patch(route('promotions.toggle', $promo));
        $response->assertRedirect();

        $promo->refresh();
        $this->assertSame(! $initialStatus, $promo->is_active);
    }

    public function test_teacher_cannot_delete_promotion_but_admin_can(): void
    {
        $promo = Promotion::firstOrFail();

        // Guru ditolak menghapus
        $this->actingAs($this->teacher)->delete(route('promotions.destroy', $promo))->assertStatus(403);
        $this->assertDatabaseHas('promotions', ['id' => $promo->id]);

        // Admin berhasil menghapus
        $resAdmin = $this->actingAs($this->admin)->delete(route('promotions.destroy', $promo));
        $resAdmin->assertRedirect(route('promotions.index'));
        $this->assertDatabaseMissing('promotions', ['id' => $promo->id]);
    }

    public function test_promotion_resolver_correctly_evaluates_active_and_expired_promo(): void
    {
        $resolver = app(PromotionResolver::class);
        $product = Product::where('inventory_type', 'RETAIL')->firstOrFail();

        // 1. Buat promo yang aktif
        $activePromo = Promotion::create([
            'name' => 'Promo Diskon Ritel 20%',
            'code' => 'RITEL20',
            'scope' => PromotionScope::ALL_ITEMS,
            'discount_type' => PromotionDiscountType::PERCENTAGE,
            'discount_value' => '20.0000',
            'min_order_amount' => '0.0000',
            'start_datetime' => now()->subDay(),
            'end_datetime' => now()->addDays(2),
            'is_active' => true,
            'created_by_user_id' => $this->admin->id,
        ]);

        $cart = [
            [
                'type' => 'RETAIL',
                'id' => $product->id,
                'subtotal_net' => '50000.0000',
            ],
        ];

        $res = $resolver->resolve($cart, $activePromo->id);
        $this->assertNotNull($res['applied_promotion']);
        $this->assertSame($activePromo->id, $res['applied_promotion']->id);
        // 20% dari 50.000 = 10.000
        $this->assertEquals('10000.0000', $res['promo_discount_total']);

        // 2. Jika promo kedaluwarsa, tidak boleh diterapkan
        $activePromo->update([
            'end_datetime' => now()->subHour(),
        ]);

        $resExpired = $resolver->resolve($cart, $activePromo->id);
        $this->assertNull($resExpired['applied_promotion']);
        $this->assertEquals('0.0000', $resExpired['promo_discount_total']);
    }

    public function test_checkout_transaction_with_promo_discount_posts_balanced_double_entry_journal(): void
    {
        $product = Product::where('inventory_type', 'RETAIL')->where('current_stock', '>=', 5)->firstOrFail();
        $sellingPrice = (string) $product->selling_price;

        $promo = Promotion::create([
            'name' => 'Diskon Promo Uji Coba Rp 2.000',
            'code' => 'TEST2K',
            'scope' => PromotionScope::PRODUCT,
            'product_id' => $product->id,
            'discount_type' => PromotionDiscountType::FIXED_AMOUNT,
            'discount_value' => '2000.0000',
            'min_order_amount' => '0.0000',
            'start_datetime' => now()->subDay(),
            'end_datetime' => now()->addDays(5),
            'is_active' => true,
            'created_by_user_id' => $this->admin->id,
        ]);

        $qty = '2.0000';
        $grossSubtotal = bcmul($qty, $sellingPrice, 4);
        $expectedGrandTotal = bcsub($grossSubtotal, '2000.0000', 4);

        $action = app(CheckoutPosTransactionAction::class);
        $payload = new CheckoutPayloadData(
            posShiftId: (string) $this->activeShift->id,
            cashierUserId: (string) $this->student->id,
            customerName: 'Siswa Pembeli Promo',
            paymentMethod: PaymentMethod::CASH,
            amountPaid: $expectedGrandTotal,
            manualDiscountTotal: '0.0000',
            items: [
                new CartItemData(
                    itemType: 'RETAIL_PRODUCT',
                    productId: (string) $product->id,
                    serviceId: null,
                    qty: $qty,
                    manualDiscountAmount: '0.0000'
                ),
            ],
            appliedPromotionId: $promo->id,
        );

        $transaction = $action->execute($payload);

        $this->assertSame('COMPLETED', $transaction->status);
        $this->assertSame($promo->id, $transaction->applied_promotion_id);
        $this->assertEquals('2000.0000', $transaction->promo_discount_total);
        $this->assertEquals($expectedGrandTotal, $transaction->grand_total);

        // Verifikasi Jurnal Akuntansi Berimbang
        /** @var JournalEntry $journal */
        $journal = $transaction->journalEntry;
        $this->assertNotNull($journal);

        // Harus ada baris debit untuk akun potongan penjualan ritel 4-1102 sebesar 2000
        $discountItem = $journal->items->first(function ($it) {
            return $it->chartOfAccount?->account_code === '4-1102';
        });
        $this->assertNotNull($discountItem, 'Jurnal harus memuat debit ke COA 4-1102 untuk diskon ritel');
        $this->assertEquals('2000.0000', $discountItem->debit);

        // Total debit dan kredit di jurnal harus seimbang
        $totalDebit = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit, 'Jurnal akuntansi wajib seimbang (Debit = Kredit)');
    }
}
