<?php

namespace App\Modules\Notes\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinimalAverage extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'minimal_averages';

    protected $fillable = [
        'cycle_id',
        'academic_year_id',
        'minimal_average',
    ];

    protected $casts = [
        'minimal_average' => 'float',
    ];

    /**
     * Relation with cycle
     */
    public function cycle()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Cycle::class);
    }

    /**
     * Relation with academic year
     */
    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Core\Models\AcademicYear::class);
    }

    /**
     * Check if a given average meets the minimum requirement
     */
    public function meetsRequirement(float $average): bool
    {
        return $average >= $this->minimal_average;
    }
}
