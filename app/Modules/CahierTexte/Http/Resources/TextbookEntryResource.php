<?php

namespace App\Modules\CahierTexte\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TextbookEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'program_id'          => $this->program_id,
            'scheduled_course_id' => $this->scheduled_course_id,
            'session_date'        => $this->session_date?->format('Y-m-d'),
            'start_time'          => $this->start_time,
            'end_time'            => $this->end_time,
            'hours_taught'        => (float) $this->hours_taught,
            'session_title'       => $this->session_title,
            'content_covered'     => $this->content_covered,
            'objectives'          => $this->objectives,
            'teaching_methods'    => $this->teaching_methods,
            'homework'            => $this->homework,
            'homework_due_date'   => $this->homework_due_date?->format('Y-m-d'),
            'resources'           => $this->resources,
            'attachments'         => $this->attachments,
            'students_present'    => $this->students_present,
            'students_absent'     => $this->students_absent,
            'observations'        => $this->observations,
            'status'              => $this->status,
            'published_at'        => $this->published_at?->format('Y-m-d H:i:s'),
            'validated_at'        => $this->validated_at?->format('Y-m-d H:i:s'),

            // ── Élément de cours (via program.courseElement) ────────────────
            'course_element' => $this->whenLoaded('program', function () {
                $ce = $this->program?->courseElement;
                return $ce ? [
                    'id'   => $ce->id,
                    'name' => $ce->name,
                    'code' => $ce->code,
                ] : null;
            }),

            // ── Professeur (via program.professor) ──────────────────────────
            'professor' => $this->whenLoaded('program', function () {
                $prof = $this->program?->professor;
                return $prof ? [
                    'id'         => $prof->id,
                    'first_name' => $prof->first_name,
                    'last_name'  => $prof->last_name,
                    'email'      => $prof->email,
                ] : null;
            }),

            // ── Groupe de classe (via program.classGroup) ───────────────────
            'class_group' => $this->whenLoaded('program', function () {
                $cg = $this->program?->classGroup;
                return $cg ? [
                    'id'          => $cg->id,
                    'group_name'  => $cg->group_name,
                    'study_level' => $cg->study_level,
                ] : null;
            }),

            // ── Validateur ──────────────────────────────────────────────────
            'validator' => $this->whenLoaded('validator', function () {
                return [
                    'id'    => $this->validator->id,
                    'name'  => trim($this->validator->first_name . ' ' . $this->validator->last_name),
                    'email' => $this->validator->email,
                ];
            }),

            'comments_count' => $this->whenCounted('comments'),
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
