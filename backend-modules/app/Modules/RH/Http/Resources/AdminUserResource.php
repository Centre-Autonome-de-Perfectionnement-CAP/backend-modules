<?php

namespace App\Modules\RH\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'rib_number' => $this->rib_number,
            'rib' => $this->rib,
            'rib_url' => $this->rib ? url("/api/stockage/files/{$this->rib}/view") : null,
            'ifu_number' => $this->ifu_number,
            'ifu' => $this->ifu,
            'ifu_url' => $this->ifu ? url("/api/stockage/files/{$this->ifu}/view") : null,
            'bank' => $this->bank,
            'photo' => $this->photo,
            'photo_url' => $this->photo ? url("/api/stockage/files/{$this->photo}/view") : null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description ?? null,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
