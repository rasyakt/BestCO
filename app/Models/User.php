<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'nip_nisn',
    'password',
    'pin_code',
    'role_type',
    'is_active',
])]
#[Hidden(['password', 'remember_token', 'pin_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role_type' => UserRole::class,
        ];
    }

    /**
     * Interaksi pin_code sebagai atribut hashed (tidak pernah diexpose dalam bentuk plainteks).
     */
    protected function pinCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null ? null : bcrypt($value),
        );
    }

    /**
     * Cek apakah user memiliki peran tertentu.
     */
    public function hasRole(string|UserRole $role): bool
    {
        $roleEnum = $role instanceof UserRole ? $role : UserRole::from($role);

        return $this->role_type === $roleEnum;
    }

    /**
     * Cek apakah user memiliki salah satu dari peran yang diberikan.
     */
    public function hasAnyRole(array $roles): bool
    {
        return collect($roles)->contains(fn (string $role): bool => $this->hasRole($role));
    }

    /**
     * Get the student profile associated with the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
    }

    /**
     * Get the shift attendances where this user is the scheduled student.
     */
    public function shiftAttendancesAsScheduled(): HasMany
    {
        return $this->hasMany(ShiftAttendance::class, 'scheduled_user_id');
    }

    /**
     * Get the shift attendances where this user is the substitute student.
     */
    public function shiftAttendancesAsSubstitute(): HasMany
    {
        return $this->hasMany(ShiftAttendance::class, 'substituted_user_id');
    }

    /**
     * Get the shift attendances where this user is the active operator.
     */
    public function shiftAttendancesAsActiveOperator(): HasMany
    {
        return $this->hasMany(ShiftAttendance::class, 'active_operator_user_id');
    }

    /**
     * Get the POS shifts opened by this user.
     */
    public function posShiftsOpened(): HasMany
    {
        return $this->hasMany(PosShift::class, 'opened_by_user_id');
    }

    /**
     * Get the POS shifts closed by this user.
     */
    public function posShiftsClosed(): HasMany
    {
        return $this->hasMany(PosShift::class, 'closed_by_user_id');
    }

    /**
     * Get the POS shifts supervised by this teacher.
     */
    public function posShiftsSupervised(): HasMany
    {
        return $this->hasMany(PosShift::class, 'supervisor_teacher_id');
    }

    /**
     * Get the piket schedules where this user is scheduled.
     */
    public function piketSchedules(): HasMany
    {
        return $this->hasMany(PiketSchedule::class, 'scheduled_student_id');
    }

    /**
     * Get the stock mutations requested by this user.
     */
    public function stockMutationsRequested(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'requested_by_user_id');
    }

    /**
     * Get the stock mutations approved by this user.
     */
    public function stockMutationsApproved(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'approved_by_user_id');
    }

    /**
     * Get the spoilage logs reported by this user.
     */
    public function spoilageLogsReported(): HasMany
    {
        return $this->hasMany(SpoilageLog::class, 'reported_by_user_id');
    }

    /**
     * Get the spoilage logs verified by this user.
     */
    public function spoilageLogsVerified(): HasMany
    {
        return $this->hasMany(SpoilageLog::class, 'verified_by_user_id');
    }

    /**
     * Get the POS transactions where this user is the cashier.
     */
    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'cashier_user_id');
    }

    /**
     * Get the journal entries created by this user.
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'created_by_user_id');
    }

    /**
     * Get the cash book entries recorded by this user.
     */
    public function cashBookEntries(): HasMany
    {
        return $this->hasMany(CashBookEntry::class, 'recorded_by_user_id');
    }

    /**
     * Get the school class where this user is homeroom teacher.
     */
    public function homeroomClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'homeroom_teacher_id');
    }
}
