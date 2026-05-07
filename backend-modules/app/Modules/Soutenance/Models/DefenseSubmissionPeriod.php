<?php

namespace App\Modules\Soutenance\Models;

use App\Modules\Inscription\Models\AcademicYear;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefenseSubmissionPeriod extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'academic_year_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function defenseSubmissions()
    {
        return $this->hasMany(DefenseSubmission::class);
    }
}
