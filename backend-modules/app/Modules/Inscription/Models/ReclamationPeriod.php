<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamationPeriod extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'academic_year_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];


    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
