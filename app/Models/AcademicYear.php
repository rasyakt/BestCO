<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'semester', 'start_date', 'end_date', 'is_active'])]
class AcademicYear extends Model
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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the school classes associated with this academic year.
     */
    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'academic_year_id');
    }

    /**
     * Get the piket schedules associated with this academic year.
     */
    public function piketSchedules(): HasMany
    {
        return $this->hasMany(PiketSchedule::class, 'academic_year_id');
    }
}
