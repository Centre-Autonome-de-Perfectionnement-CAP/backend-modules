<?php

namespace App\Modules\LegacyStudent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Inscription\Models\Department;

class LegacyStudent extends Model
{
    use HasFactory;

    protected $table = 'legacy_students';

    protected $fillable = [
        'matricule',
        'last_name',
        'first_name',
        'date_of_birth',
        'place_of_birth',
        'email',
        'phone',
        'enrollment_year',
        'cycle',
        'status',
        'rejection_reason',
        'validated_by',
        'validated_at',
        'department_id',
        'notes',
    ];

    protected $casts = [
        'enrollment_year' => 'integer',
        'validated_at' => 'datetime',
        'date_of_birth' => 'date:Y-m-d',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'legacy_student_filieres',
            'legacy_student_id',
            'department_id'
        )->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(LegacyStudentServiceRequest::class, 'legacy_student_id');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(LegacyStudentAcademicRecord::class, 'legacy_student_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }
}
