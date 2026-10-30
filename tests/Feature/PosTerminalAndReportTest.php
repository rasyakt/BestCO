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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->student = User::where('role_type', 'piket_siswa')->firstOrFail();
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
        // Buka shift terlebih dahulu
        $action = app(OpenPosShiftAction::class);
        $action->execute(new ShiftOpeningData(
            supervisorTeacherId: (string) $this->teacher->id,
            openedByUserId: (string) $this->student->id,
            openingCashBalance: '200000.0000',
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
            ],
        ));

        $response = $this->actingAs($this->student)->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('Terminal POS');
        $response->assertSee('Keranjang Transaksi POS');
    }

    public function test_teacher_can_view_all_financial_report_tabs(): void
    {
        $tabs = ['bku', 'journal', 'trial_balance', 'income_statement', 'balance_sheet'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($this->teacher)->get('/reports?tab=' . $tab);
            $response->assertStatus(200);
        }
    }
}
