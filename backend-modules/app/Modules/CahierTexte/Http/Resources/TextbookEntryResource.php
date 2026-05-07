<?php

namespace App\Modules\CahierTexte\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TextbookEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'program_id' => $this->program_id,
            'scheduled_course_id' => $this->scheduled_course_id,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'hours_taught' => (float) $this->hours_taught,
            'session_title' => $this->session_title,
            'content_covered' => $this->content_covered,
            'objectives' => $this->objectives,
            'teaching_methods' => $this->teaching_methods,
            'homework' => $this->homework,
            'homework_due_date' => $this->homework_due_date?->format('Y-m-d'),
            'resources' => $this->resources,
            'attachments' => $this->attachments,
            'students_present' => $this->students_present,
            'students_absent' => $this->students_absent,
            'observations' => $this->observations,
            'status' => $this->status,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'validated_at' => $this->validated_at?->format('Y-m-d H:i:s'),
            
            // Relations
            'program' => $this->whenLoaded('program', function () {
                return [
                    'id' => $this->program->id,
                    'uuid' => $this->program->uuid,
                ];
            }),
            
            'scheduled_course' => $this->whenLoaded('scheduledCourse', function () {
                return [
                    'id' => $this->scheduledCourse->id,
                    'uuid' => $this->scheduledCourse->uuid,
                ];
            }),
            
            'course_element' => $this->whenLoaded('program.courseElementProfessor.courseElement', function () {
                $courseElement = $this->program->courseElementProfessor->courseElement;
                return [
                    'id' => $courseElement->id,
                    'name' => $courseElement->name,
                    'code' => $courseElement->code,
                ];
            }),
            
            'professor' => $this->whenLoaded('program.courseElementProfessor.professor', function () {
                $professor = $this->program->courseElementProfessor->professor;
                return [
                    'id' => $professor->id,
                    'first_name' => $professor->first_name,
                    'last_name' => $professor->last_name,
                    'email' => $professor->email,
                ];
            }),
            
            'class_group' => $this->whenLoaded('program.classGroup', function () {
                return [
                    'id' => $this->program->classGroup->id,
                    'group_name' => $this->program->classGroup->group_name,
                    'study_level' => $this->program->classGroup->study_level,
                ];
            }),
            
            'validator' => $this->whenLoaded('validator', function () {
                return [
                    'id' => $this->validator->id,
                    'name' => $this->validator->name,
                    'email' => $this->validator->email,
                ];
            }),
            
            'comments_count' => $this->whenCounted('comments'),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
