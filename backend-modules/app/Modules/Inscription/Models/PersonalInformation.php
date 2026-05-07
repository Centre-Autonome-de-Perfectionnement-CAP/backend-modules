<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class PersonalInformation extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PersonalInformationFactory::new();
    }

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
    ];

    protected $casts = [
        'birth_date' => 'date',
        'contacts' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation vers les dossiers (pending_students)
     */
    public function pendingStudents()
    {
        return $this->hasMany(PendingStudent::class, 'personal_information_id');
    }
}
