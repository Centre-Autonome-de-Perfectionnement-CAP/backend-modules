<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuid;

class PersonalInformation extends Authenticatable
{
    use HasFactory, HasUuid, HasApiTokens;

    protected $fillable = [
        'last_name',
        'first_names',
        'email',
        'birth_date',
        'birth_place',
        'birth_country',
        'gender',
        'contacts',
        'nationality',
        'photo',
        'password',
        'role_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contacts' => 'array',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Relation vers les dossiers
     */
    public function pendingStudents()
    {
        return $this->hasMany(PendingStudent::class, 'personal_information_id');
    }

    /**
     * Relation vers student
     */
    public function student()
    {
        return $this->hasOne(Student::class, 'personal_information_id');
    }
}