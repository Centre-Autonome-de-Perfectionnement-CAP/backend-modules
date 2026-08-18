<?php

namespace App\Models;

use App\Modules\Inscription\Models\Department;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model pour legacy_student_departments.
 *
 * Utilisé via LegacyStudent::departments() ->using(LegacyStudentDepartment::class)
 * pour pouvoir accéder proprement au pivot (ex: $department->pivot->cycle_id)
 * et, si besoin plus tard, y ajouter des accesseurs/scopes propres au rattachement.
 */
class LegacyStudentDepartment extends Pivot
{
    protected $table = 'legacy_student_departments';

    public $incrementing = true;

    protected $fillable = [
        'legacy_student_id',
        'department_id',
        'cycle_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function legacyStudent()
    {
        return $this->belongsTo(LegacyStudent::class);
    }
}
