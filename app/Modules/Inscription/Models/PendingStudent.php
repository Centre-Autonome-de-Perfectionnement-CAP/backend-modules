<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingStudent extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PendingStudentFactory::new();
    }

    protected $fillable = [
        'personal_information_id',
        'tracking_code',
        'cuca_opinion',
        'cuca_comment',
        'cuo_opinion',
        'cuo_comment',
        'department_id',
        'academic_year_id',
        'level',
        'documents',
        'entry_diploma_id',
        'sponsorise',
        'exonere',
        'status',
        'mail_cuca_sent',
        'mail_cuca_count',
        'mail_cuo_sent',
        'mail_cuo_count',
    ];

    protected $casts = [
        'documents' => 'array',
        'mail_cuca_sent' => 'boolean',
        'mail_cuo_sent' => 'boolean',
        'mail_cuca_count' => 'integer',
        'mail_cuo_count' => 'integer',
    ];


    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function entryDiploma()
    {
        return $this->belongsTo(EntryDiploma::class);
    }

    public function studentPendingStudents()
{
    return $this->hasMany(StudentPendingStudent::class, 'pending_student_id');
}

    /**
     * Relation vers les parcours académiques via StudentPendingStudent
     */
    public function academicPaths()
    {
        return $this->hasManyThrough(
            AcademicPath::class,
            StudentPendingStudent::class,
            'pending_student_id',          // FK sur student_pending_students
            'student_pending_student_id',  // FK sur academic_paths
            'id',                          // PK locale sur pending_students
            'id'                           // PK locale sur student_pending_students
        );
    }

    /**
     * Get the files associated with this pending student.
     */
    public function files()
    {
        return $this->hasMany(\App\Modules\Stockage\Models\File::class, 'module_resource_id')
            ->where('module_name', 'inscription')
            ->where('module_resource_type', 'pending_student');
    }





   /**
     * Accessor pour modifier l'affichage du document "Quittance de 15.000F"
     * et gérer les cas où documents n'est pas un tableau valide
     */
    public function getDocumentsAttribute($value)
    {
        // Si ce n'est pas déjà un tableau, on essaie de le corriger
        if (!is_array($value)) {
            // Cas où c'est une string JSON (normalement casté, mais au cas où)
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded ?: [];
                } else {
                    // JSON invalide → on vide pour éviter les erreurs
                    $value = [];
                }
            } else {
                // null ou autre type → tableau vide
                $value = [];
            }
        }
    
        // Maintenant qu'on est sûr que c'est un tableau, on applique la modification demandée
        if (isset($value['Quittance de 15.000F'])) {
            $value['Quittance de 20.000F'] = $value['Quittance de 15.000F'];
            unset($value['Quittance de 15.000F']);
        }
    
        return $value;
    }





    
}
