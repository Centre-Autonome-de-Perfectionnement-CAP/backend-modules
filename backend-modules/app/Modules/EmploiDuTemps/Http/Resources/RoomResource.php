<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'building_id' => $this->building_id,
            'name' => $this->name,
            'code' => $this->code,
            'capacity' => $this->capacity,
            'room_type' => $this->room_type,
            'equipment' => $this->equipment,
            'is_available' => $this->is_available,
            'building' => $this->whenLoaded('building', function () {
                return [
                    'id' => $this->building->id,
                    'name' => $this->building->name,
                    'code' => $this->building->code,
                ];
            }),
            'scheduled_courses_count' => $this->when(
                $this->relationLoaded('scheduledCourses'),
                fn() => $this->scheduledCourses->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
