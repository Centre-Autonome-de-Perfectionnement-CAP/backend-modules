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
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'total_hours' => (float) $this->total_hours,
            'hours_completed' => (float) $this->hours_completed,
            'remaining_hours' => $this->remaining_hours,
            'progress_percentage' => $this->progress_percentage,
            'is_recurring' => $this->is_recurring,
            'recurrence_end_date' => $this->recurrence_end_date?->format('Y-m-d'),
            'excluded_dates' => $this->excluded_dates,
            'notes' => $this->notes,
            'is_cancelled' => $this->is_cancelled,
            'is_completed' => $this->isCompleted(),
            'estimated_end_date' => $this->calculateEstimatedEndDate()?->format('Y-m-d'),
            
            // Relations
            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'program' => $this->whenLoaded('program', function () {
                return [
                    'id' => $this->program->id,
                    'class_group_id' => $this->program->class_group_id,
                    'course_element_professor_id' => $this->program->course_element_professor_id,
                ];
            }),
            
            // Relations profondes via program
            'course_element' => $this->when(
                $this->relationLoaded('program') && $this->program->relationLoaded('courseElementProfessor'),
                function () {
                    $cep = $this->program->courseElementProfessor;
                    if ($cep && $cep->relationLoaded('courseElement')) {
                        return [
                            'id' => $cep->courseElement->id,
                            'name' => $cep->courseElement->name,
                            'code' => $cep->courseElement->code,
                            'credits' => $cep->courseElement->credits,
                        ];
                    }
                    return null;
                }
            ),
            
            'professor' => $this->when(
                $this->relationLoaded('program') && $this->program->relationLoaded('courseElementProfessor'),
                function () {
                    $cep = $this->program->courseElementProfessor;
                    if ($cep && $cep->relationLoaded('professor')) {
                        return [
                            'id' => $cep->professor->id,
                            'first_name' => $cep->professor->first_name,
                            'last_name' => $cep->professor->last_name,
                            'email' => $cep->professor->email,
                        ];
                    }
                    return null;
                }
            ),
            
            'class_group' => $this->when(
                $this->relationLoaded('program') && $this->program->relationLoaded('classGroup'),
                function () {
                    return [
                        'id' => $this->program->classGroup->id,
                        'group_name' => $this->program->classGroup->group_name,
                        'study_level' => $this->program->classGroup->study_level,
                    ];
                }
            ),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
