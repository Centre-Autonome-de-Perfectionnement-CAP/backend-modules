<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'academic_year',
        'libelle',
        'year_start',
        'year_end',
        'submission_start',
        'submission_end',
        'reclamation_start',
        'reclamation_end',
        'is_current',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AcademicYearFactory::new();
    }

    protected $casts = [
        'year_start' => 'date',
        'year_end' => 'date',
        'submission_start' => 'date',
        'submission_end' => 'date',
        'reclamation_start' => 'date',
        'reclamation_end' => 'date',
        'is_current' => 'boolean',
    ];

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function submissionPeriod()
    {
        return $this->hasMany(SubmissionPeriod::class);
    }
    
    public function submissionPeriods()
    {
        return $this->hasMany(SubmissionPeriod::class);
    }

    public function reclamationPeriod()
    {
        return $this->hasMany(ReclamationPeriod::class);
    }

    public function academicPath()
    {
        return $this->hasMany(AcademicPath::class);
    }

    public function defenseSubmissionPeriods()
    {
        return $this->hasMany(DefenseSubmissionPeriod::class);
    }

    public function minimalAverage()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
