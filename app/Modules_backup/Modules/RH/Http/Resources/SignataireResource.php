<?php

namespace App\Modules\RH\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SignataireResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'role_id' => $this->role_id,
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'slug' => $this->role?->slug,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
