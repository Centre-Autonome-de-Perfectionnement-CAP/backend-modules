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
        'program_id',
        'level',
        'academic_year_id',
        'amount',
        'sponsored_amount',
        'is_active',
        'penalty_amount',
        'penalty_type',
        'penalty_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sponsored_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'penalty_active' => 'boolean',
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
     * Relation: Department
     */
    public function department()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\Department::class);
    }

    /**
     * Get the appropriate training fee based on student status
     * 
     * @param string $status - 'national', 'international', 'exempted', 'sponsored'
     * @return float
     */
    public function getTrainingFeeByStatus(string $status): float
    {
        return match($status) {
            'national' => (float) $this->national_training_fee,
            'international' => (float) $this->international_training_fee,
            'exempted' => (float) $this->exempted_training_fee,
            'sponsored' => (float) $this->sponsored_training_fee,
            default => (float) $this->national_training_fee,
        };
    }

    /**
     * Get total fee (registration + training) based on student status
     * 
     * @param string $status
     * @return float
     */
    public function getTotalFeeByStatus(string $status): float
    {
        return (float) $this->registration_fee + $this->getTrainingFeeByStatus($status);
    }
}
