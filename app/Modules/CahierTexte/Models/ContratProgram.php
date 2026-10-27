<?php

namespace App\Modules\CahierTexte\Models;

use Illuminate\Database\Eloquent\Model;

class ContratProgram extends Model{
    protected $table = 'contrat_programs';

    protected $fillable = [
        'contrat_id',
        'course_element_professor_id',
        'program_id',
        'number_monographie',
        'amount_monographie',
        'course_support_file',
        'updated_by',
        'amount_program'
    ];

    protected $casts = [
        'course_support_file' => 'array',
    ];

    public function contrat()
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function courseElementProfessor()
    {
        return $this->belongsTo(CourseElementProfessor::class, 'course_element_professor_id');
    }
}