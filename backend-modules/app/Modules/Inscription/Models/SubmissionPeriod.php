<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionPeriod extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\SubmissionPeriodFactory::new();
    }

    protected $fillable = [
        'academic_year_id',
        'department_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];


    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
