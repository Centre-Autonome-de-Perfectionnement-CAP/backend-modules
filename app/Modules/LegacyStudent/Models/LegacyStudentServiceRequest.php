<?php

namespace App\Modules\LegacyStudent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStudentServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'legacy_student_services';

    protected $fillable = [
        'legacy_student_id',
        'matricule',
        'student_name',
        'email',
        'phone',
        'service_type',
        'service_name',
        'filiere_name',
        'enrollment_year',
        'status',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'enrollment_year' => 'integer',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function legacyStudent(): BelongsTo
    {
        return $this->belongsTo(LegacyStudent::class, 'legacy_student_id');
    }
}
