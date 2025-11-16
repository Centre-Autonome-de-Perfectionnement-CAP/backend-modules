<?php

namespace App\Modules\Cours\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'class_group_id' => $this->class_group_id,
            'course_element_professor_id' => $this->course_element_professor_id,
            'academic_year_id' => $this->academic_year_id,
            'weighting' => $this->weighting,
            'retake_weighting' => $this->retake_weighting,
            
            // Année académique
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            
            // Classe/Groupe
            'class_group' => $this->whenLoaded('classGroup', function () {
                return [
                    'id' => $this->classGroup->id,
                    'uuid' => $this->classGroup->uuid,
                    'group_name' => $this->classGroup->group_name,
                    'study_level' => $this->classGroup->study_level,
                    'academic_year' => $this->classGroup->academicYear ? [
                        'id' => $this->classGroup->academicYear->id,
                        'name' => $this->classGroup->academicYear->name,
                    ] : null,
                    'department' => $this->classGroup->department ? [
                        'id' => $this->classGroup->department->id,
                        'name' => $this->classGroup->department->name,
                        'code' => $this->classGroup->department->code,
                    ] : null,
                ];
            }),
            
            // Assignation Cours-Professeur
            'course_element_professor' => $this->whenLoaded('courseElementProfessor', function () {
                return [
                    'id' => $this->courseElementProfessor->id,
                    'course_element' => $this->courseElementProfessor->courseElement ? [
                        'id' => $this->courseElementProfessor->courseElement->id,
                        'name' => $this->courseElementProfessor->courseElement->name,
                        'code' => $this->courseElementProfessor->courseElement->code,
                        'credits' => $this->courseElementProfessor->courseElement->credits,
                        'teaching_unit' => $this->courseElementProfessor->courseElement->teachingUnit ? [
                            'id' => $this->courseElementProfessor->courseElement->teachingUnit->id,
                            'name' => $this->courseElementProfessor->courseElement->teachingUnit->name,
                            'code' => $this->courseElementProfessor->courseElement->teachingUnit->code,
                        ] : null,
                    ] : null,
                    'professor' => $this->courseElementProfessor->professor ? [
                        'id' => $this->courseElementProfessor->professor->id,
                        'first_name' => $this->courseElementProfessor->professor->first_name,
                        'last_name' => $this->courseElementProfessor->professor->last_name,
                        'full_name' => $this->courseElementProfessor->professor->full_name,
                        'email' => $this->courseElementProfessor->professor->email,
                    ] : null,
                ];
            }),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
