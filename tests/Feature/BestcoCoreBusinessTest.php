<?php

declare(strict_types=1);

use App\Actions\Inventory\MutateRetailToRawMaterialAction;
use App\Actions\Inventory\RecordSpoilageAction;
use App\Actions\Pos\CheckoutPosTransactionAction;
use App\DTOs\Inventory\InternalMutationData;
use App\DTOs\Inventory\SpoilageData;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\PaymentMethod;
use App\Enums\SpoilageReason;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShiftAttendance;
use App\Models\UnitConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestcoCoreBusinessTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private PosShift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->student = User::where('role_type', 'piket_siswa')->firstOrFail();
        $this->shift = PosShift::create([
            'shift_code' => 'SHF-TEST-' . now()->format('YmdHis'),
            'shift_date' => today(),
            'supervisor_teacher_id' => $this->teacher->id,
            'opened_by_user_id' => $this->teacher->id,
            'opening_cash_balance' => '200000.0000',
            'system_expected_cash' => '200000.0000',
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        ShiftAttendance::create([
            'pos_shift_id' => $this->shift->id,
            'piket_schedule_id' => null,
            'slot_number' => 1,
            'scheduled_user_id' => $this->student->id,
            'scheduled_student_status' => 'PRESENT',
            'absence_reason' => null,
            'is_substituted' => false,
            'substituted_user_id' => null,
            'active_operator_user_id' => $this->student->id,
            'check_in_at' => now(),
            'check_out_at' => null,
            'approved_by_teacher_id' => $this->teacher->id,
        ]);

        // Pastikan periode akuntansi terbuka tersedia.
        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    public function test_converts_1_rim_retail_paper_into_500_sheets_service_raw_material_and_posts_balanced_journal(): void
    {
        $retailPaper = Product::where('sku', 'ATK-HVS-A4-RIM')->firstOrFail();
        $rawPaper = Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail();
        $conversion = UnitConversion::where('source_product_id', $retailPaper->id)
            ->where('target_product_id', $rawPaper->id)
            ->firstOrFail();

        $initialRetailStock = (string) $retailPaper->current_stock;
        $initialRawStock = (string) $rawPaper->current_stock;

        $action = app(MutateRetailToRawMaterialAction::class);
        $mutation = $action->execute(new InternalMutationData(
            posShiftId: (string) $this->shift->id,
            unitConversionId: (string) $conversion->id,
            sourceQtyToDeduct: '1.0000',
            requestedByUserId: (string) $this->student->id,
            approvedByUserId: (string) $this->teacher->id,
            notes: 'Stok kertas fotocopy habis saat jam istirahat',
        ));

        $retailPaper->refresh();
        $rawPaper->refresh();

        $this->assertSame('1.0000', bcsub((string) $initialRetailStock, (string) $retailPaper->current_stock, 4));
        $this->assertSame('500.0000', bcsub((string) $rawPaper->current_stock, (string) $initialRawStock, 4));

        $journal = JournalEntry::findOrFail($mutation->journal_entry_id);
        $this->assertSame($journal->total_debit, $journal->total_credit);
        $this->assertSame($mutation->total_mutation_value, $journal->total_debit);
    }

    public function test_deducts_exact_0_0500_gram_toner_per_sheet_and_applies_wholesale_tier_discount(): void
    {
        $photocopyService = Service::where('code', 'SRV-FC-A4')->firstOrFail();
        $rawPaper = Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail();
        $rawToner = Product::where('sku', 'RAW-TONER-GRM')->firstOrFail();

        $initialPaper = (string) $rawPaper->current_stock;
        $initialToner = (string) $rawToner->current_stock;

        $checkoutAction = app(CheckoutPosTransactionAction::class);
        $transaction = $checkoutAction->execute(new CheckoutPayloadData(
            posShiftId: (string) $this->shift->id,
            cashierUserId: (string) $this->student->id,
            customerName: 'Budi Santoso',
            paymentMethod: PaymentMethod::CASH,
            amountPaid: '50000.0000',
            manualDiscountTotal: '0.0000',
            items: [
                new CartItemData(
                    itemType: 'SERVICE',
                    productId: null,
                    serviceId: (string) $photocopyService->id,
                    qty: '150.0000',
                    manualDiscountAmount: '0.0000',
                ),
            ],
        ));

        $rawPaper->refresh();
        $rawToner->refresh();

        $this->assertSame('150.0000', bcsub($initialPaper, (string) $rawPaper->current_stock, 4));
        $this->assertSame('7.5000', bcsub($initialToner, (string) $rawToner->current_stock, 4));
        $this->assertSame(1, bccomp((string) $transaction->tier_discount_total, '0.0000', 4));

        $journal = JournalEntry::findOrFail($transaction->journal_entry_id);
        $sumDebit = $journal->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->debit, 4), '0.0000');
        $sumCredit = $journal->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->credit, 4), '0.0000');

        $this->assertSame($sumDebit, $sumCredit);
        $this->assertSame((string) $journal->total_debit, $sumDebit);
    }

    public function test_records_failed_photocopy_spoilage_deducting_paper_and_toner(): void
    {
        $photocopyService = Service::where('code', 'SRV-FC-A4')->firstOrFail();
        $rawPaper = Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail();
        $rawToner = Product::where('sku', 'RAW-TONER-GRM')->firstOrFail();
        $spoilageAccount = ChartOfAccount::where('account_code', '6-1101')->firstOrFail();

        $initialPaper = (string) $rawPaper->current_stock;
        $initialToner = (string) $rawToner->current_stock;
        $initialSpoilageExpense = (string) $spoilageAccount->current_balance;

        $spoilageAction = app(RecordSpoilageAction::class);
        $spoilageLog = $spoilageAction->execute(new SpoilageData(
            posShiftId: (string) $this->shift->id,
            spoilageSource: 'SERVICE_PRODUCTION',
            serviceId: (string) $photocopyService->id,
            failedOutputQty: '20.0000',
            reasonCategory: SpoilageReason::PAPER_JAM,
            reasonDescription: 'Kertas nyangkut di roller pemanas mesin fotocopy',
            reportedByUserId: (string) $this->student->id,
            verifiedByUserId: (string) $this->teacher->id,
            directItems: [],
        ));

        $rawPaper->refresh();
        $rawToner->refresh();
        $spoilageAccount->refresh();

        $this->assertSame('20.0000', bcsub($initialPaper, (string) $rawPaper->current_stock, 4));
        $this->assertSame('1.0000', bcsub($initialToner, (string) $rawToner->current_stock, 4));
        $this->assertSame(
            number_format((float) $spoilageLog->total_spoilage_cost, 4, '.', ''),
            bcsub((string) $spoilageAccount->current_balance, $initialSpoilageExpense, 4)
        );
    }

    public function test_records_retail_product_spoilage_deducting_inventory_and_posting_journal(): void
    {
        $pen = Product::where('sku', 'ATK-PULPEN-PACK')->firstOrFail();
        $initialPenStock = (string) $pen->current_stock;
        $retailInventoryAccount = ChartOfAccount::where('account_code', '1-1301')->firstOrFail();
        $retailSpoilageExpense = ChartOfAccount::where('account_code', '6-1102')->firstOrFail();

        $initialInventoryBal = (string) $retailInventoryAccount->current_balance;
        $initialExpenseBal = (string) $retailSpoilageExpense->current_balance;

        $spoilageAction = app(RecordSpoilageAction::class);
        $spoilageLog = $spoilageAction->execute(new SpoilageData(
            posShiftId: (string) $this->shift->id,
            spoilageSource: 'RETAIL_DAMAGE',
            serviceId: null,
            failedOutputQty: '0.0000',
            reasonCategory: SpoilageReason::PHYSICAL_DAMAGE,
            reasonDescription: 'Kemasan pulpen pecah / bocor tinta',
            reportedByUserId: (string) $this->student->id,
            verifiedByUserId: (string) $this->teacher->id,
            directItems: [
                [
                    'product_id' => (string) $pen->id,
                    'qty_wasted' => '2.0000',
                    'unit_cost' => (string) $pen->average_cost,
                ],
            ],
        ));

        $pen->refresh();
        $retailInventoryAccount->refresh();
        $retailSpoilageExpense->refresh();

        $this->assertSame('2.0000', bcsub($initialPenStock, (string) $pen->current_stock, 4));
        $expectedCost = bcmul('2.0000', (string) $pen->average_cost, 4);
        $this->assertSame(
            number_format((float) $expectedCost, 4, '.', ''),
            number_format((float) $spoilageLog->total_spoilage_cost, 4, '.', '')
        );
        $this->assertSame(
            number_format((float) $expectedCost, 4, '.', ''),
            bcsub((string) $retailSpoilageExpense->current_balance, $initialExpenseBal, 4)
        );
    }
}
