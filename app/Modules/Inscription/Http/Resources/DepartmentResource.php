<?php

namespace App\Modules\Inscription\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Department",
 *     type="object",
 *     title="Department",
 *     description="Représentation d'un département",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Informatique"),
 *     @OA\Property(property="abbreviation", type="string", example="INFO"),
 *     @OA\Property(property="cycle_id", type="integer", example=1),
 *     @OA\Property(property="next_level_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class DepartmentResource extends JsonResource
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
            'cycle_id' => $this->cycle_id,
            'next_level_id' => $this->next_level_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
