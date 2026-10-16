<?php

namespace App\Modules\LegacyStudent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStudentAcademicRecord extends Model
{
    use HasFactory;

    protected $table = 'legacy_student_academic_records';

    protected $fillable = [
        'legacy_student_id',
        'academic_year',
        'level',
        'semester',
        'general_average',
        'total_credits',
        'obtained_credits',
        'decision',
        'mention',
        'thesis_title',
        'thesis_grade',
        'thesis_date',
        'quitus_accorded',
        'courses',
        'notes',
    ];

    protected $casts = [
        'general_average' => 'float',
        'thesis_grade' => 'float',
        'total_credits' => 'integer',
        'obtained_credits' => 'integer',
        'quitus_accorded' => 'boolean',
        'courses' => 'array',
        'thesis_date' => 'date:Y-m-d',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(LegacyStudent::class, 'legacy_student_id');
    }
}
