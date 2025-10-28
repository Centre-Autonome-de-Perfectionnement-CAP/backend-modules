<?php

namespace App\Modules\Inscription\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PersonalInformation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'last_name',
        'first_names',
        'email',
        'birth_date',
        'birth_place',
        'birth_country',
        'gender',
        'contacts',
        'entry_diploma_id',
        'nationality',
        'photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contacts' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
