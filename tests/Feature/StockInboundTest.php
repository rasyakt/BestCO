<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JournalType;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInboundTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $teacher;
    private User $student;

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
    }

    public function test_superadmin_and_admin_and_teacher_can_view_inbound_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.inbound'));
        $response->assertStatus(200);
        $response->assertSee('Barang Masuk');
        $response->assertSee('Sumber Dana Pembelian');
        $response->assertSee('Daftar Barang yang Masuk');

        $responseGuru = $this->actingAs($this->teacher)->get(route('inventory.inbound'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Barang Masuk');
    }

    public function test_student_cannot_access_inbound_page(): void
    {
        $response = $this->actingAs($this->student)->get(route('inventory.inbound'));
        $response->assertStatus(403);
    }

    public function test_admin_can_record_multi_product_inbound_stock(): void
    {
        $products = Product::where('is_active', true)->take(2)->get();
        $this->assertCount(2, $products);

        $prod1 = $products[0];
        $prod2 = $products[1];

        $initialStock1 = (float) $prod1->current_stock;
        $initialStock2 = (float) $prod2->current_stock;

        $fundingAccount = ChartOfAccount::where('account_code', '1-1102')->firstOrFail();

        $payload = [
            'account_id' => $fundingAccount->id,
            'invoice_number' => 'INV-TEST-001',
            'notes' => 'Pengadaan batch testing',
            'items' => [
                [
                    'product_id' => $prod1->id,
                    'qty' => 10,
                    'price' => 50000,
                ],
                [
                    'product_id' => $prod2->id,
                    'qty' => 5,
                    'price' => 20000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('inventory.inbound.store'), $payload);

        $response->assertRedirect(route('inventory.inbound'));
        $response->assertSessionHas('success');

        // Verifikasi stok produk terupdate
        $prod1->refresh();
        $prod2->refresh();

        $this->assertEquals($initialStock1 + 10, (float) $prod1->current_stock);
        $this->assertEquals($initialStock2 + 5, (float) $prod2->current_stock);
        $this->assertEquals(50000, (float) $prod1->last_purchase_price);
        $this->assertEquals(20000, (float) $prod2->last_purchase_price);

        // Verifikasi StockLedgerEntry
        $ledgerEntries = StockLedgerEntry::where('reference_type', 'PURCHASE')
            ->whereIn('product_id', [$prod1->id, $prod2->id])
            ->latest('id')
            ->take(2)
            ->get();

        $this->assertCount(2, $ledgerEntries);

        // Verifikasi Balanced Journal Entry
        $journal = JournalEntry::with('items.chartOfAccount')
            ->where('journal_type', JournalType::PURCHASE)
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(600000, (float) $journal->total_debit);
        $this->assertEquals(600000, (float) $journal->total_credit);
        $this->assertTrue($journal->is_posted);
        $this->assertStringContainsString('INV-TEST-001', $journal->description);

        // 2 debit lines (inventory) + 1 credit line (Kas Besar)
        $this->assertCount(3, $journal->items);
        $creditLine = $journal->items->where('credit', '>', 0)->first();
        $this->assertEquals('1-1102', $creditLine->chartOfAccount->account_code);
        $this->assertEquals(600000, (float) $creditLine->credit);
    }

    public function test_teacher_and_student_cannot_submit_inbound_stock(): void
    {
        $fundingAccount = ChartOfAccount::where('account_code', '1-1102')->firstOrFail();
        $prod = Product::firstOrFail();

        $payload = [
            'account_id' => $fundingAccount->id,
            'items' => [
                [
                    'product_id' => $prod->id,
                    'qty' => 5,
                    'price' => 10000,
                ],
            ],
        ];

        // Piket Guru gets 403 on store
        $responseTeacher = $this->actingAs($this->teacher)->post(route('inventory.inbound.store'), $payload);
        $responseTeacher->assertStatus(403);

        // Piket Siswa gets 403 on store
        $responseStudent = $this->actingAs($this->student)->post(route('inventory.inbound.store'), $payload);
        $responseStudent->assertStatus(403);
    }

    public function test_validation_fails_on_empty_items_or_invalid_quantity(): void
    {
        $fundingAccount = ChartOfAccount::where('account_code', '1-1102')->firstOrFail();

        // Empty items
        $response = $this->actingAs($this->admin)->post(route('inventory.inbound.store'), [
            'account_id' => $fundingAccount->id,
            'items' => [],
        ]);
        $response->assertSessionHasErrors(['items']);

        // Zero quantity
        $prod = Product::firstOrFail();
        $responseZero = $this->actingAs($this->admin)->post(route('inventory.inbound.store'), [
            'account_id' => $fundingAccount->id,
            'items' => [
                [
                    'product_id' => $prod->id,
                    'qty' => 0,
                    'price' => 10000,
                ],
            ],
        ]);
        $responseZero->assertSessionHasErrors(['items.0.qty']);
    }
}

