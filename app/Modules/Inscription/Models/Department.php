<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Department extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'cycle_id',
        'abbreviation',
        'next_level_id',
        'description',
        'is_active',
        'whatsapp_link',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\DepartmentFactory::new();
    }

    protected $casts = [
        'date_limite' => 'date',
    ];

    /**
     * Relation avec le cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }
    /**
     * Relation avec les périodes de soumission
     */
    public function submissionPeriod()
    {
        return $this->hasMany(\App\Modules\Inscription\Models\SubmissionPeriod::class, 'department_id');
    }

    /**
     * Relation avec les groupes de classe
     */
    public function classGroups()
    {
        return $this->hasMany(\App\Modules\Inscription\Models\ClassGroup::class, 'filiere_id');
    }

    /**
     * Relation avec les périodes de soumission
     */
    //public function submissionPeriod()
    //{
      //  return $this->hasMany(SubmissionPeriod::class);
    //}

    /**
     * Alias pour submissionPeriod (au pluriel)
     */
    public function submissionPeriods()
    {
        return $this->hasMany(SubmissionPeriod::class);
    }
}
