<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class AcademicLevelFee extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'academic_year_id',
        'department_id',
        'study_level',
        'registration_fee',
        'uemoa_training_fee',
        'non_uemoa_training_fee',
        'exempted_training_fee',
        'is_active',
    ];

    protected $casts = [
        'registration_fee' => 'decimal:2',
        'uemoa_training_fee' => 'decimal:2',
        'non_uemoa_training_fee' => 'decimal:2',
        'exempted_training_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\Department::class);
    }

    public function getTrainingFeeByOrigin(string $origin): float
    {
        return match($origin) {
            'uemoa' => (float) $this->uemoa_training_fee,
            'non_uemoa' => (float) $this->non_uemoa_training_fee,
            'exempted' => (float) $this->exempted_training_fee,
            default => (float) $this->uemoa_training_fee,
        };
    }

    public function getTotalFeeByOrigin(string $origin): float
    {
        return (float) $this->registration_fee + $this->getTrainingFeeByOrigin($origin);
    }
}
