<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

/**
 * Amount Model - Fee Structure
 *
 * Stores the fee structure for each academic year, department, and level combination.
 * Different fees apply based on student status (national, international, exempted, sponsored).
 */
class Amount extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'type',
        'libelle',
        'academic_year_id',
        'amount',
        'is_active',
        'penalty_amount',
        'penalty_type',
        'penalty_active',
        'uemoa_training_fee',
        'non_uemoa_training_fee',
        'exempted_training_fee',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'penalty_active' => 'boolean',
        'uemoa_training_fee' => 'decimal:2',
        'non_uemoa_training_fee' => 'decimal:2',
        'exempted_training_fee' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation: Academic Year
     */
    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class);
    }

    /**
     * Relation: Classes auxquelles ce tarif s'applique
     */
    public function classGroups()
    {
        return $this->belongsToMany(
            \App\Modules\Inscription\Models\ClassGroup::class,
            'amount_class_groups',
            'amount_id',
            'department_id'
        )->withPivot('academic_year_id', 'study_level');
    }

    /**
     * Get the appropriate training fee based on student origin
     *
     * @param string $origin - 'uemoa', 'non_uemoa', 'exempted'
     * @return float
     */
    public function getTrainingFeeByOrigin(string $origin): float
    {
        return match($origin) {
            'uemoa' => (float) $this->uemoa_training_fee,
            'non_uemoa' => (float) $this->non_uemoa_training_fee,
            'exempted' => (float) $this->exempted_training_fee,
            default => (float) $this->uemoa_training_fee,
        };
    }

    /**
     * Get total fee (registration + training) based on student origin
     *
     * @param string $origin
     * @return float
     */
    public function getTotalFeeByOrigin(string $origin): float
    {
        return (float) $this->registration_fee + $this->getTrainingFeeByOrigin($origin);
    }
}
