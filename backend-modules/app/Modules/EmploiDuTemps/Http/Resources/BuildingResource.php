<?php

namespace App\Modules\EmploiDuTemps\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'rooms_count' => $this->when(
                $this->relationLoaded('rooms'),
                fn() => $this->rooms->count()
            ),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
