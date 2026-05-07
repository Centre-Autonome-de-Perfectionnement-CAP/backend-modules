<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'program_id' => $this->program_id,
            'time_slot_id' => $this->time_slot_id,
            'room_id' => $this->room_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_hours' => $this->total_hours,
            'hours_completed' => $this->hours_completed,
            'remaining_hours' => $this->remaining_hours,
            'progress_percentage' => $this->progress_percentage,
            'is_recurring' => $this->is_recurring,
            'recurrence_end_date' => $this->recurrence_end_date,
            'excluded_dates' => $this->excluded_dates,
            'notes' => $this->notes,
            'is_cancelled' => $this->is_cancelled,
            'is_completed' => $this->is_completed,
            'estimated_end_date' => $this->estimated_end_date,
            
            // Time Slot
            'time_slot' => $this->whenLoaded('timeSlot', function () {
                return [
                    'id' => $this->timeSlot->id,
                    'uuid' => $this->timeSlot->uuid,
                    'day_of_week' => $this->timeSlot->day_of_week,
                    'start_time' => $this->timeSlot->start_time,
                    'end_time' => $this->timeSlot->end_time,
                    'type' => $this->timeSlot->type,
                    'name' => $this->timeSlot->name,
                    'duration_in_minutes' => $this->timeSlot->duration_in_minutes,
                    'duration_in_hours' => $this->timeSlot->duration_in_hours,
                    'created_at' => $this->timeSlot->created_at?->toISOString(),
                    'updated_at' => $this->timeSlot->updated_at?->toISOString(),
                ];
            }),
            
            // Room
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'uuid' => $this->room->uuid,
                    'building_id' => $this->room->building_id,
                    'name' => $this->room->name,
                    'code' => $this->room->code,
                    'capacity' => $this->room->capacity,
                    'room_type' => $this->room->room_type,
                    'equipment' => $this->room->equipment,
                    'is_available' => $this->room->is_available,
                    'building' => $this->room->building ? [
                        'id' => $this->room->building->id,
                        'name' => $this->room->building->name,
                        'code' => $this->room->building->code,
                    ] : null,
                    'created_at' => $this->room->created_at?->toISOString(),
                    'updated_at' => $this->room->updated_at?->toISOString(),
                ];
            }),
            
            // Program with nested relations
            'program' => $this->whenLoaded('program', function () {
                return [
                    'id' => $this->program->id,
                    'class_group_id' => $this->program->class_group_id,
                    'course_element_professor_id' => $this->program->course_element_professor_id,
                ];
            }),
            
            // Expose direct relations for easier access
            'course_element' => $this->when(
                $this->relationLoaded('program') && 
                $this->program?->relationLoaded('courseElementProfessor') &&
                $this->program?->courseElementProfessor?->relationLoaded('courseElement'),
                function () {
                    $courseElement = $this->program->courseElementProfessor->courseElement;
                    return [
                        'id' => $courseElement->id,
                        'name' => $courseElement->name,
                        'code' => $courseElement->code,
                        'credits' => $courseElement->credits,
                        'teaching_unit' => $courseElement->teachingUnit ? [
                            'id' => $courseElement->teachingUnit->id,
                            'name' => $courseElement->teachingUnit->name,
                            'code' => $courseElement->teachingUnit->code,
                        ] : null,
                    ];
                }
            ),
            
            'professor' => $this->when(
                $this->relationLoaded('program') && 
                $this->program?->relationLoaded('courseElementProfessor') &&
                $this->program?->courseElementProfessor?->relationLoaded('professor'),
                function () {
                    $professor = $this->program->courseElementProfessor->professor;
                    return [
                        'id' => $professor->id,
                        'first_name' => $professor->first_name,
                        'last_name' => $professor->last_name,
                        'full_name' => $professor->full_name,
                        'email' => $professor->email,
                    ];
                }
            ),
            
            'class_group' => $this->when(
                $this->relationLoaded('program') && 
                $this->program?->relationLoaded('classGroup'),
                function () {
                    $classGroup = $this->program->classGroup;
                    return [
                        'id' => $classGroup->id,
                        'uuid' => $classGroup->uuid,
                        'group_name' => $classGroup->group_name,
                        'study_level' => $classGroup->study_level,
                        'department' => $classGroup->department ? [
                            'id' => $classGroup->department->id,
                            'name' => $classGroup->department->name,
                            'code' => $classGroup->department->abbreviation,
                        ] : null,
                        'academic_year' => $classGroup->academicYear ? [
                            'id' => $classGroup->academicYear->id,
                            'name' => $classGroup->academicYear->libelle ?? $classGroup->academicYear->academic_year,
                        ] : null,
                    ];
                }
            ),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
