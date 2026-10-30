<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AbsenceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pos_shift_id',
    'piket_schedule_id',
    'slot_number',
    'scheduled_user_id',
    'scheduled_student_status',
    'absence_reason',
    'is_substituted',
    'substituted_user_id',
    'active_operator_user_id',
    'check_in_at',
    'check_out_at',
    'approved_by_teacher_id',
])]
class ShiftAttendance extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_number' => 'integer',
            'scheduled_student_status' => AbsenceStatus::class,
            'is_substituted' => 'boolean',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    /**
     * Get the POS shift this attendance belongs to.
     */
    public function posShift(): BelongsTo
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    /**
     * Get the piket schedule referenced by this attendance.
     */
    public function piketSchedule(): BelongsTo
    {
        return $this->belongsTo(PiketSchedule::class, 'piket_schedule_id');
    }

    /**
     * Get the scheduled student.
     */
    public function scheduledUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_user_id');
    }

    /**
     * Get the substitute student (if any).
     */
    public function substituteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substituted_user_id');
    }

    /**
     * Get the active operator (scheduled or substitute).
     */
    public function activeOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'active_operator_user_id');
    }

    /**
     * Get the teacher who approved the attendance.
     */
    public function approvedByTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_teacher_id');
    }
}
