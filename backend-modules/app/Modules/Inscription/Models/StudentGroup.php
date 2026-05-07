<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StudentGroup extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'class_group_id',
        'student_id',
    ];

    /**
     * Relation avec le groupe de classe
     */
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
