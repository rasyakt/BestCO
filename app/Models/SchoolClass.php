<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'grade_level', 'name', 'homeroom_teacher_id'])]
class SchoolClass extends Model
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
            'grade_level' => 'string',
        ];
    }

    /**
     * Get the academic year that owns the class.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the homeroom teacher of the class.
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /**
     * Get the student profiles in this class.
     */
    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'school_class_id');
    }

    /**
     * Get the piket schedules for this class.
     */
    public function piketSchedules(): HasMany
    {
        return $this->hasMany(PiketSchedule::class, 'school_class_id');
    }
}
