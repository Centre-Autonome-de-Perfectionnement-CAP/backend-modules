<?php

namespace App\Modules\Cours\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseElementProfessorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_element_id' => $this->course_element_id,
            'professor_id' => $this->professor_id,
            'principal_professor_id' => $this->principal_professor_id,
            'academic_year_id' => $this->academic_year_id,
            'class_group_id' => $this->class_group_id,
            'is_primary' => $this->is_primary,
            'course_element' => $this->whenLoaded('courseElement', function () {
                return [
                    'id' => $this->courseElement->id,
                    'name' => $this->courseElement->name,
                    'code' => $this->courseElement->code,
                    'credits' => $this->courseElement->credits,
                ];
            }),
            'professor' => $this->whenLoaded('professor', function () {
                return [
                    'id' => $this->professor->id,
                    'full_name' => $this->professor->full_name,
                    'email' => $this->professor->email,
                    'grade' => $this->professor->grade?->name,
                ];
            }),
            'principal_professor' => $this->whenLoaded('principalProfessor', function () {
                return [
                    'id' => $this->principalProfessor->id,
                    'full_name' => $this->principalProfessor->full_name,
                    'email' => $this->principalProfessor->email,
                ];
            }),
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            'class_group' => $this->whenLoaded('classGroup', function () {
                return [
                    'id' => $this->classGroup->id,
                    'group_name' => $this->classGroup->group_name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
