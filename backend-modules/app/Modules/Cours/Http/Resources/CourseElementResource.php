<?php

namespace App\Modules\Cours\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseElementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'credits' => $this->credits,
            'teaching_unit_id' => $this->teaching_unit_id,
            'teaching_unit' => $this->whenLoaded('teachingUnit', function () {
                return [
                    'id' => $this->teachingUnit->id,
                    'name' => $this->teachingUnit->name,
                    'code' => $this->teachingUnit->code,
                ];
            }),
            'resources' => CourseElementResourceResource::collection($this->whenLoaded('resources')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
