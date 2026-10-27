<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploiDuTempsResource extends JsonResource
{
    public function toArray($request): array
    {
        if (!$this->resource) {
            return [];
        }

        $program = $this->program;
        $cep     = $program?->courseElementProfessor;

        return [
            'id'   => $this->resource?->id,
            'uuid' => $this->resource?->uuid,

            'academic_year' => $this->whenLoaded('academicYear', function () {
                return $this->academicYear ? [
                    'id'            => $this->academicYear->id,
                    'academic_year' => $this->academicYear->academic_year,
                    'libelle'       => $this->academicYear->libelle,
                    'is_current'    => $this->academicYear->is_current,
                ] : null;
            }),

            'department' => $this->whenLoaded('department', function () {
                return $this->department ? [
                    'id'           => $this->department->id,
                    'name'         => $this->department->name,
                    'abbreviation' => $this->department->abbreviation,
                ] : null;
            }),

            'class_group' => $this->whenLoaded('classGroup', function () {
                return $this->classGroup ? [
                    'id'          => $this->classGroup->id,
                    'group_name'  => $this->classGroup->group_name,
                    'study_level' => $this->classGroup->study_level,
                ] : null;
            }),

            'program' => $this->whenLoaded('program', function () use ($program, $cep) {
                if (!$program) return null;

                return [
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
                ];
            }),

            'room' => $this->whenLoaded('room', function () {
                return $this->room ? [
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
                ] : null;
            }),

            'day_of_week' => $this->day_of_week,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,

            'duration_in_minutes' => $this->duration_in_minutes,
            'duration_in_hours'   => $this->duration_in_hours,
            'duration_formatted'  => $this->duration_formatted,

            'is_recurring'        => $this->is_recurring,
            'recurrence_end_date' => $this->recurrence_end_date?->format('Y-m-d'),
            'excluded_dates'      => $this->excluded_dates ?? [],

            'notes'        => $this->notes,
            'is_cancelled' => $this->is_cancelled,
            'is_active'    => $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}