<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="SubmissionPeriod",
 *     title="Submission Period",
 *     description="Modèle représentant une période de soumission",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="academic_year_id", type="integer", description="ID de l'année académique"),
 *     @OA\Property(property="start_date", type="string", format="date-time", description="Date de début"),
 *     @OA\Property(property="end_date", type="string", format="date-time", description="Date de fin"),
 *     @OA\Property(property="is_active", type="boolean", description="Si la période est active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(
 *         property="academic_year",
 *         ref="#/components/schemas/AcademicYear",
 *         description="Année académique associée"
 *     )
 * )
 */
class SubmissionPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'department_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
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

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
