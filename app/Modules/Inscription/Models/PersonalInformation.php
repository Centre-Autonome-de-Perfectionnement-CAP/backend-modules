<?php

namespace App\Modules\Inscription\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'address',
        'phone',
        'emergency_contact',
        'gender',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
