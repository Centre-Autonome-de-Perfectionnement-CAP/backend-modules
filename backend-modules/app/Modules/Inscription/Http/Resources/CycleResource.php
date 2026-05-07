<?php

namespace App\Modules\Inscription\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Cycle",
 *     type="object",
 *     title="Cycle",
 *     description="Représentation d'un cycle d'études",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Licence"),
 *     @OA\Property(property="abbreviation", type="string", example="L"),
 *     @OA\Property(property="years_count", type="integer", example=3),
 *     @OA\Property(property="is_lmd", type="boolean", example=true),
 *     @OA\Property(property="type", type="string", example="undergraduate"),
 *     @OA\Property(property="departments", type="array", @OA\Items(ref="#/components/schemas/Department")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CycleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'years_count' => $this->years_count,
            'is_lmd' => $this->is_lmd,
            'type' => $this->type,
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
