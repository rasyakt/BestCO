<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_year_id', 'schedule_date', 'day_of_week', 'slot_number', 'school_class_id', 'scheduled_student_id', 'notes'])]
class PiketSchedule extends Model
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
            'schedule_date' => 'date',
            'day_of_week' => 'integer',
            'slot_number' => 'integer',
        ];
    }

    /**
     * Get the academic year that scopes the schedule.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the school class assigned to the schedule slot.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Get the student scheduled for this piket slot.
     */
    public function scheduledStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_student_id');
    }
}
