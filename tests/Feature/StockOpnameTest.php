<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JournalType;
use App\Enums\StockOpnameStatus;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
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

    public function test_admin_and_teacher_can_view_stock_opname_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('inventory.opname'))
            ->assertOk()
            ->assertSee('Stok Opname')
            ->assertSee('Buat Opname');

        $this->actingAs($this->teacher)
            ->get(route('inventory.opname.create'))
            ->assertOk()
            ->assertSee('Qty Fisik')
            ->assertSee('Simpan Draf Opname');
    }

    public function test_student_cannot_access_stock_opname(): void
    {
        $this->actingAs($this->student)
            ->get(route('inventory.opname'))
            ->assertForbidden();
    }

    public function test_teacher_can_create_draft_without_changing_stock(): void
    {
        $product = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();
        $initialStock = (string) $product->current_stock;

        $response = $this->actingAs($this->teacher)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Opname uji coba',
            'items' => [
                ['product_id' => $product->id, 'counted_qty' => '8'],
                ['product_id' => Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail()->id, 'counted_qty' => '1005'],
            ],
        ]);

        $opname = StockOpname::firstOrFail();

        $response->assertRedirect(route('inventory.opname.show', $opname));
        $this->assertSame(StockOpnameStatus::DRAFT, $opname->status);
        $this->assertSame(2, $opname->items_count);
        $this->assertSame('-2.0000', $opname->items->first()->difference_qty);
        $this->assertSame('90000.0000', $opname->total_shortage_value);
        $this->assertSame('450.0000', $opname->total_overage_value);
        $this->assertSame($initialStock, (string) $product->fresh()->current_stock);
        $this->assertSame(0, StockLedgerEntry::where('reference_type', 'OPNAME')->count());
        $this->assertSame(0, JournalEntry::where('journal_type', JournalType::ADJUSTMENT)->count());
    }

    public function test_admin_approval_applies_shortage_and_overage_with_balanced_journal(): void
    {
        $retail = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();
        $raw = Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail();

        $this->actingAs($this->admin)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $retail->id, 'counted_qty' => '8'],
                ['product_id' => $raw->id, 'counted_qty' => '1005'],
            ],
        ])->assertRedirect();

        $opname = StockOpname::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('inventory.opname.approve', $opname))
            ->assertRedirect(route('inventory.opname.show', $opname));

        $opname->refresh();

        $this->assertSame(StockOpnameStatus::APPROVED, $opname->status);
        $this->assertSame($this->admin->id, $opname->approved_by_user_id);
        $this->assertNotNull($opname->approved_at);
        $this->assertNotNull($opname->journal_entry_id);

        $this->assertSame('8.0000', (string) $retail->fresh()->current_stock);
        $this->assertSame('1005.0000', (string) $raw->fresh()->current_stock);

        $ledgerEntries = StockLedgerEntry::where('reference_type', 'OPNAME')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $ledgerEntries);
        $this->assertSame('-2.0000', $ledgerEntries[0]->qty_change);
        $this->assertSame('90000.0000', $ledgerEntries[0]->total_value);
        $this->assertSame('5.0000', $ledgerEntries[1]->qty_change);
        $this->assertSame('450.0000', $ledgerEntries[1]->total_value);

        $journal = JournalEntry::with('items.chartOfAccount')->findOrFail($opname->journal_entry_id);

        $this->assertSame(JournalType::ADJUSTMENT, $journal->journal_type);
        $this->assertSame($journal->total_debit, $journal->total_credit);

        $shortageDebit = $journal->items->firstWhere('chartOfAccount.account_code', '6-1902');
        $retailCredit = $journal->items->firstWhere('chartOfAccount.account_code', '1-1301');
        $rawDebit = $journal->items->firstWhere('chartOfAccount.account_code', '1-1302');
        $overageCredit = $journal->items->firstWhere('chartOfAccount.account_code', '4-9102');

        $this->assertSame('90000.0000', (string) $shortageDebit->debit);
        $this->assertSame('90000.0000', (string) $retailCredit->credit);
        $this->assertSame('450.0000', (string) $rawDebit->debit);
        $this->assertSame('450.0000', (string) $overageCredit->credit);
    }

    public function test_teacher_cannot_approve_opname(): void
    {
        $product = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();

        $this->actingAs($this->teacher)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'counted_qty' => '9'],
            ],
        ]);

        $opname = StockOpname::firstOrFail();

        $this->actingAs($this->teacher)
            ->post(route('inventory.opname.approve', $opname))
            ->assertForbidden();

        $this->assertSame(StockOpnameStatus::DRAFT, $opname->fresh()->status);
    }

    public function test_approved_opname_cannot_be_approved_twice_or_deleted(): void
    {
        $product = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();

        $this->actingAs($this->admin)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'counted_qty' => '9'],
            ],
        ]);

        $opname = StockOpname::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('inventory.opname.approve', $opname))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('inventory.opname.approve', $opname))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->delete(route('inventory.opname.destroy', $opname))
            ->assertSessionHas('error');

        $this->assertSame(StockOpnameStatus::APPROVED, $opname->fresh()->status);
    }

    public function test_creating_opname_requires_at_least_one_counted_product(): void
    {
        $product = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();

        $this->actingAs($this->admin)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'counted_qty' => ''],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, StockOpname::count());
    }

    public function test_draft_can_be_deleted_by_its_creator(): void
    {
        $product = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();

        $this->actingAs($this->teacher)->post(route('inventory.opname.store'), [
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'counted_qty' => '9'],
            ],
        ]);

        $opname = StockOpname::firstOrFail();

        $this->actingAs($this->teacher)
            ->delete(route('inventory.opname.destroy', $opname))
            ->assertRedirect(route('inventory.opname'));

        $this->assertDatabaseMissing('stock_opnames', ['id' => $opname->id]);
    }
}
