<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'type' => $this->type,
            'name' => $this->name,
            'duration_in_minutes' => $this->duration_in_minutes,
            'duration_in_hours' => $this->duration_in_hours,
            'scheduled_courses_count' => $this->when(
                $this->relationLoaded('scheduledCourses'),
                fn() => $this->scheduledCourses->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
