<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

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

    public function program()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\Program::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class);
    }
}
