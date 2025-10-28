<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="AcademicYear",
 *     title="Academic Year",
 *     description="Modèle représentant une année académique",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="submission_start", type="string", format="date-time", description="Date de début des soumissions"),
 *     @OA\Property(property="submission_end", type="string", format="date-time", description="Date de fin des soumissions"),
 *     @OA\Property(property="reclamation_start", type="string", format="date-time", description="Date de début des réclamations"),
 *     @OA\Property(property="reclamation_end", type="string", format="date-time", description="Date de fin des réclamations"),
 *     @OA\Property(property="academic_year", type="string", description="Année académique (ex: 2023-2024)"),
 *     @OA\Property(property="year_start", type="integer", description="Année de début"),
 *     @OA\Property(property="year_end", type="integer", description="Année de fin"),
 *     @OA\Property(property="uuid", type="string", format="uuid", description="UUID unique"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 */
class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['submission_start', 'submission_end', 'academic_year', 'year_start', 'year_end', 'is_current'];

    protected $casts = [
        'submission_start' => 'date',
        'submission_end' => 'date',
        'is_current' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
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
