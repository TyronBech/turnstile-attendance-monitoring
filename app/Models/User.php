<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Pending student detail attributes waiting to be synced.
     *
     * @var array<string, string|null>
     */
    protected array $pendingStudentDetailAttributes = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usr_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'rfid',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'profile_image',
        'guardian_name',
        'guardian_contact_number',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
        'student_id',
        'guardian_name',
        'guardian_contact_number',
    ];

    /**
     * Bootstrap model event handlers.
     */
    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            $user->syncPendingStudentDetailAttributes();
        });
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->getMiddleInitial(),
            $this->last_name,
        ])));
    }

    /**
     * Get the student's ID number from the student detail record.
     */
    public function getStudentIdAttribute(): string
    {
        return $this->studentDetail?->id_number ?? '';
    }

    /**
     * Store the student's ID number for post-save syncing.
     */
    public function setStudentIdAttribute(?string $value): void
    {
        $this->pendingStudentDetailAttributes['id_number'] = $value;
    }

    /**
     * Get the guardian name from the student detail record.
     */
    public function getGuardianNameAttribute(): string
    {
        return $this->studentDetail?->guardian_name ?? '';
    }

    /**
     * Store the guardian name for post-save syncing.
     */
    public function setGuardianNameAttribute(?string $value): void
    {
        $this->pendingStudentDetailAttributes['guardian_name'] = $value;
    }

    /**
     * Get the guardian contact number from the student detail record.
     */
    public function getGuardianContactNumberAttribute(): string
    {
        return $this->studentDetail?->guardian_contact_number ?? '';
    }

    /**
     * Store the guardian contact number for post-save syncing.
     */
    public function setGuardianContactNumberAttribute(?string $value): void
    {
        $this->pendingStudentDetailAttributes['guardian_contact_number'] = $value;
    }

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
            'two_factor_confirmed_at' => 'datetime',
            'status' => 'boolean',
        ];
    }

    /**
     * Get all attendance logs for this student.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the student detail record associated with this user.
     */
    public function studentDetail(): HasOne
    {
        return $this->hasOne(StudentDetail::class);
    }

    /**
     * Get the employee detail record associated with this user.
     */
    public function employeeDetail(): HasOne
    {
        return $this->hasOne(EmployeeDetail::class);
    }

    /**
     * Persist any virtual student-only attributes to usr_student_details.
     */
    protected function syncPendingStudentDetailAttributes(): void
    {
        if ($this->pendingStudentDetailAttributes === []) {
            return;
        }

        $detailAttributes = [
            'id_number' => $this->pendingStudentDetailAttributes['id_number'] ?? $this->studentDetail?->id_number,
            'guardian_name' => $this->pendingStudentDetailAttributes['guardian_name'] ?? $this->studentDetail?->guardian_name ?? '',
            'guardian_contact_number' => $this->pendingStudentDetailAttributes['guardian_contact_number'] ?? $this->studentDetail?->guardian_contact_number ?? '',
            'level' => $this->studentDetail?->level ?? 'N/A',
            'section' => $this->studentDetail?->section ?? 'N/A',
            'active_id_number' => $this->pendingStudentDetailAttributes['id_number'] ?? $this->studentDetail?->active_id_number,
        ];

        if (! filled((string) $detailAttributes['id_number'])) {
            $this->pendingStudentDetailAttributes = [];

            return;
        }

        /** @var StudentDetail $studentDetail */
        $studentDetail = $this->studentDetail()->updateOrCreate(
            ['user_id' => $this->id],
            $detailAttributes,
        );

        $this->setRelation('studentDetail', $studentDetail);
        $this->pendingStudentDetailAttributes = [];
    }

    /**
     * Get the first-character initial from the first middle-name word.
     */
    private function getMiddleInitial(): string
    {
        $middleName = trim((string) $this->middle_name);

        if ($middleName === '') {
            return '';
        }

        $firstMiddleWord = preg_split('/\s+/', $middleName)[0] ?? '';

        if ($firstMiddleWord === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($firstMiddleWord, 0, 1)).'.';
    }
}
