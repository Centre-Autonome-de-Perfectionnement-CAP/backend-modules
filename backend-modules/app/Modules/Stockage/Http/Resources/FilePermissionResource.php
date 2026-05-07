<?php

namespace App\Modules\Stockage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilePermissionResource extends JsonResource
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
            'permission_type' => $this->permission_type,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            
            // Bénéficiaire
            'user' => $this->when($this->user_id, [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ]),
            
            'role' => $this->when($this->role_id, [
                'id' => $this->role->id ?? null,
                'name' => $this->role->name ?? null,
                'display_name' => $this->role->display_name ?? null,
            ]),
            
            // Qui a accordé
            'granted_by' => [
                'id' => $this->grantedBy->id ?? null,
                'name' => $this->grantedBy->name ?? null,
            ],
            
            // Timestamps
            'granted_at' => $this->granted_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
