<?php

namespace App\Modules\Inscription\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PersonalInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'last_name',
        'first_names',
        'email',
        'contacts',
        'birth_date',
        'birth_place',
        'nationality',
        'birth_country',
        'address',
        'phone',
        'emergency_contact',
        'gender',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contacts' => 'array',
    ];

    /**
     * Attributs qui peuvent être null
     */
    protected $nullable = [
        'birth_date',
        'birth_place',
        'birth_country',
        'student_id',
    ];

    /**
     * Boot method pour générer automatiquement l'UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
