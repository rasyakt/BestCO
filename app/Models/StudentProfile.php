<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'school_class_id', 'nis', 'phone', 'total_piket_completed', 'total_substitute_completed'])]
class StudentProfile extends Model
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
            'total_piket_completed' => 'integer',
            'total_substitute_completed' => 'integer',
        ];
    }

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the school class the student belongs to.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }
}
