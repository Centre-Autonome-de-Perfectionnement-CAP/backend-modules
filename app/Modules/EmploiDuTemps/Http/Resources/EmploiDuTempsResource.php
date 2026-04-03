<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmploiDuTempsResource extends JsonResource
{
    public function toArray($request): array
    {
        $program = $this->program;
        $cep     = $program?->courseElementProfessor;

        return [
            'id'   => $this->id,
            'uuid' => $this->uuid,

            // Contexte académique
            'academic_year' => $this->whenLoaded('academicYear', fn() => [
                'id'            => $this->academicYear->id,
                'academic_year' => $this->academicYear->academic_year,
                'libelle'       => $this->academicYear->libelle,
                'is_current'    => $this->academicYear->is_current,
            ]),

            'department' => $this->whenLoaded('department', fn() => [
                'id'           => $this->department->id,
                'name'         => $this->department->name,
                'abbreviation' => $this->department->abbreviation,
            ]),

            // Classe (champ direct)
            'class_group' => $this->whenLoaded('classGroup', fn() => [
                'id'          => $this->classGroup->id,
                'group_name'  => $this->classGroup->group_name,
                'study_level' => $this->classGroup->study_level,
            ]),

            // Programme : cours + professeur
            'program' => $this->whenLoaded('program', fn() => $program ? [
                'id'       => $program->id,
                'semester' => $program->semester,

                'course_element' => $cep?->courseElement ? [
                    'id'      => $cep->courseElement->id,
                    'name'    => $cep->courseElement->name,
                    'code'    => $cep->courseElement->code,
                    'credits' => $cep->courseElement->credits,
                ] : null,

                'professor' => $cep?->professor ? [
                    'id'         => $cep->professor->id,
                    'first_name' => $cep->professor->first_name,
                    'last_name'  => $cep->professor->last_name,
                    'email'      => $cep->professor->email,
                ] : null,
            ] : null),

            // Salle
            'room' => $this->whenLoaded('room', fn() => $this->room ? [
                'id'       => $this->room->id,
                'name'     => $this->room->name,
                'code'     => $this->room->code,
                'capacity' => $this->room->capacity,
                'type'     => $this->room->room_type,
                'building' => $this->room->building ? [
                    'id'   => $this->room->building->id,
                    'name' => $this->room->building->name,
                    'code' => $this->room->building->code,
                ] : null,
            ] : null),

            // Horaire
            'day_of_week'        => $this->day_of_week,
            'start_time'         => $this->start_time,
            'end_time'           => $this->end_time,

            // Durée calculée automatiquement (pas stockée en base)
            'duration_in_minutes' => $this->duration_in_minutes,
            'duration_in_hours'   => $this->duration_in_hours,
            'duration_formatted'  => $this->duration_formatted,

            // Récurrence
            'is_recurring'        => $this->is_recurring,
            'recurrence_end_date' => $this->recurrence_end_date?->format('Y-m-d'),
            'excluded_dates'      => $this->excluded_dates ?? [],

            // Statut
            'notes'        => $this->notes,
            'is_cancelled' => $this->is_cancelled,
            'is_active'    => $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}