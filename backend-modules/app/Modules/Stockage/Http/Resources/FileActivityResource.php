<?php

namespace App\Modules\Stockage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileActivityResource extends JsonResource
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
            'file_id' => $this->file_id,
            'activity_type' => $this->activity_type,
            'description' => $this->description,
            'metadata' => $this->metadata,
            
            // Utilisateur
            'user' => $this->when($this->user_id, [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ]),
            
            // Informations techniques
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            
            // Timestamp
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
