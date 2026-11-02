<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Piket\OpenPosShiftAction;
use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\Enums\AbsenceStatus;
use App\Models\AccountingPeriod;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTerminalAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $admin;

    /** @var array<int, User> */
    private array $students;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->students = User::where('role_type', 'piket_siswa')->get()->all();
        $this->student = $this->students[0];
        $this->admin = User::where('role_type', 'admin')->firstOrFail();

        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/pos');
        $response->assertRedirect('/login');
    }

    public function test_student_without_active_shift_is_redirected_to_shift_management(): void
    {
        $response = $this->actingAs($this->student)->get('/pos');
        $response->assertRedirect('/shift');
        $response->assertSessionHas('error');
    }

    public function test_student_with_active_shift_can_access_pos_terminal(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->student)->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('Cari barang, barcode, atau layanan...');
        $response->assertSee('Keranjang');
    }

    public function test_teacher_can_view_all_financial_report_tabs(): void
    {
        $tabs = ['bku', 'journal', 'trial_balance', 'income_statement', 'balance_sheet'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($this->teacher)->get('/reports?tab=' . $tab);
            $response->assertStatus(200);
        }
    }

    public function test_can_add_retail_and_service_to_pos_cart(): void
    {
        $this->openShift();

        $retailProduct = \App\Models\Product::where('inventory_type', 'RETAIL')->firstOrFail();
        $service = \App\Models\Service::firstOrFail();

        \Livewire\Livewire::actingAs($this->student)
            ->test(\App\Livewire\Pos\PosTerminal::class)
            ->call('addToCart', 'RETAIL', $retailProduct->id)
            ->call('addToCart', 'SERVICE', $service->id)
            ->assertCount('cart', 2);
    }

    public function test_manual_discount_cannot_exceed_cart_total(): void
    {
        $this->openShift();

        $retailProduct = \App\Models\Product::where('inventory_type', 'RETAIL')->firstOrFail();
        $gross = (float) $retailProduct->selling_price;

        $component = \Livewire\Livewire::actingAs($this->student)
            ->test(\App\Livewire\Pos\PosTerminal::class)
            ->call('addToCart', 'RETAIL', $retailProduct->id)
            ->set('manualDiscountTotal', '99999999');

        $this->assertSame($gross, (float) $component->get('manualDiscountTotal'));
        $this->assertSame(0.0, (float) $component->get('grandTotal'));
        $component->assertSee('Maks. Rp '.number_format($gross, 0, ',', '.'));

        $component->call('openPaymentModal');
        $component->assertSet('showPaymentModal', true);
        $this->assertSame($gross, (float) $component->get('manualDiscountTotal'));
    }

    public function test_manual_discount_is_reclamped_when_cart_shrinks(): void
    {
        $this->openShift();

        $products = \App\Models\Product::where('inventory_type', 'RETAIL')->take(2)->get();
        $this->assertCount(2, $products);

        $firstGross = (float) $products[0]->selling_price;
        $secondGross = (float) $products[1]->selling_price;

        $component = \Livewire\Livewire::actingAs($this->student)
            ->test(\App\Livewire\Pos\PosTerminal::class)
            ->call('addToCart', 'RETAIL', $products[0]->id)
            ->call('addToCart', 'RETAIL', $products[1]->id)
            ->set('manualDiscountTotal', (string) ($firstGross + $secondGross));

        $this->assertSame(
            $firstGross + $secondGross,
            (float) $component->get('manualDiscountTotal')
        );

        $component->call('removeItem', 0);

        $this->assertSame($secondGross, (float) $component->get('manualDiscountTotal'));
        $this->assertSame(0.0, (float) $component->get('grandTotal'));
    }

    private function openShift(): PosShift
    {
        return app(OpenPosShiftAction::class)->execute(new ShiftOpeningData(
            supervisorTeacherId: (string) $this->teacher->id,
            openedByUserId: (string) $this->teacher->id,
            openingCashBalance: '200000.0000',
            slots: [
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 1,
                    scheduledUserId: (string) $this->students[0]->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->students[0]->id,
                ),
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 2,
                    scheduledUserId: (string) $this->students[1]->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->students[1]->id,
                ),
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 3,
                    scheduledUserId: (string) $this->students[2]->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->students[2]->id,
                ),
            ],
        ));
    }
}

