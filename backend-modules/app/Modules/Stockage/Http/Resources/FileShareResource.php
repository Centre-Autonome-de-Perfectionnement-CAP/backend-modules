<?php

namespace App\Modules\Stockage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileShareResource extends JsonResource
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
            'token' => $this->token,
            'has_password' => !is_null($this->password_hash),
            'allow_download' => $this->allow_download,
            'allow_preview' => $this->allow_preview,
            'max_downloads' => $this->max_downloads,
            'download_count' => $this->download_count,
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'has_reached_limit' => $this->hasReachedDownloadLimit(),
            
            // URLs
            'share_url' => $this->share_url,
            
            // Créateur
            'creator' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ],
            
            // Timestamps
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
