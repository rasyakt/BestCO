<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Piket\ClosePosShiftAction;
use App\Actions\Piket\OpenPosShiftAction;
use App\DTOs\Piket\ShiftOpeningData;
use App\DTOs\Piket\StudentAttendanceSlotData;
use App\Enums\AbsenceStatus;
use App\Enums\ShiftStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PiketSchedule;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PiketShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student1;
    private User $student2;
    private User $studentSubstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $students = User::where('role_type', 'piket_siswa')->get();
        $this->student1 = $students[0];
        $this->student2 = $students[1];
        $this->studentSubstitute = $students[2];

        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    public function test_opens_pos_shift_with_three_student_slots_and_substitution(): void
    {
        $action = app(OpenPosShiftAction::class);

        $shift = $action->execute(new ShiftOpeningData(
            supervisorTeacherId: (string) $this->teacher->id,
            openedByUserId: (string) $this->student1->id,
            openingCashBalance: '250000.0000',
            slots: [
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 1,
                    scheduledUserId: (string) $this->student1->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->student1->id,
                ),
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 2,
                    scheduledUserId: (string) $this->student2->id,
                    scheduledStudentStatus: AbsenceStatus::SICK,
                    absenceReason: 'Demam tinggi',
                    isSubstituted: true,
                    substitutedUserId: (string) $this->studentSubstitute->id,
                    activeOperatorUserId: (string) $this->studentSubstitute->id,
                ),
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 3,
                    scheduledUserId: (string) $this->student1->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->student1->id,
                ),
            ],
        ));

        $this->assertSame(ShiftStatus::OPEN, $shift->status);
        $this->assertSame('250000.0000', (string) $shift->opening_cash_balance);
        $this->assertCount(3, $shift->attendances);

        $attendance2 = $shift->attendances()->where('slot_number', 2)->firstOrFail();
        $this->assertTrue($attendance2->is_substituted);
        $this->assertSame($this->studentSubstitute->id, $attendance2->active_operator_user_id);
    }

    public function test_closes_pos_shift_and_posts_discrepancy_journal_on_shortage(): void
    {
        $openAction = app(OpenPosShiftAction::class);
        $shift = $openAction->execute(new ShiftOpeningData(
            supervisorTeacherId: (string) $this->teacher->id,
            openedByUserId: (string) $this->student1->id,
            openingCashBalance: '200000.0000',
            slots: [
                new StudentAttendanceSlotData(
                    piketScheduleId: null,
                    slotNumber: 1,
                    scheduledUserId: (string) $this->student1->id,
                    scheduledStudentStatus: AbsenceStatus::PRESENT,
                    absenceReason: null,
                    isSubstituted: false,
                    substitutedUserId: null,
                    activeOperatorUserId: (string) $this->student1->id,
                ),
            ],
        ));

        $closeAction = app(ClosePosShiftAction::class);
        // Expected cash adalah 200.000, kas fisik dihitung 195.000 (selisih kurang 5.000)
        $closedShift = $closeAction->execute(
            posShiftId: (string) $shift->id,
            actualClosingCash: '195000.0000',
            closedByUserId: (string) $this->teacher->id,
            closingNotes: 'Selisih Rp 5.000 karena salah kembalian koin'
        );

        $this->assertSame(ShiftStatus::CLOSED, $closedShift->status);
        $this->assertSame('-5000.0000', (string) $closedShift->cash_discrepancy);

        // Periksa jurnal selisih kas kurang (6-1901 Beban Selisih Kas Kurang)
        $expenseAccount = ChartOfAccount::where('account_code', '6-1901')->firstOrFail();
        $this->assertSame('5000.0000', (string) $expenseAccount->current_balance);
    }
}

