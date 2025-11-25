<?php

namespace App\Modules\Soutenance\Models;

use App\Modules\RH\Models\Professor;
use App\Modules\RH\Models\Grade;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DefenseJuryMember extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'defense_submission_id',
        'professor_id',
        'grade_id',
        'name',
        'role',
    ];
    
    public function defenseSubmission()
    {
        return $this->belongsTo(DefenseSubmission::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
