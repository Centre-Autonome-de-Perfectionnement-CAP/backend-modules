<?php

namespace App\Modules\Stockage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
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
            'name' => $this->name,
            'original_name' => $this->original_name,
            'path' => $this->path,
            'disk' => $this->disk,
            'visibility' => $this->visibility,
            'collection' => $this->collection,
            'size' => $this->size,
            'size_formatted' => $this->size_for_humans,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'file_hash' => $this->file_hash,
            'is_image' => $this->isImage(),
            'is_document' => $this->isDocument(),
            'is_locked' => $this->is_locked,
            'download_count' => $this->download_count,
            'metadata' => $this->metadata,
            
            // Informations du module
            'module' => [
                'name' => $this->module_name,
                'resource_type' => $this->module_resource_type,
                'resource_id' => $this->module_resource_id,
            ],
            
            // Relations
            'owner' => [
                'id' => $this->owner->id ?? null,
                'name' => $this->owner->name ?? null,
            ],
            
            'locked_by' => $this->when($this->is_locked, [
                'id' => $this->lockedBy->id ?? null,
                'name' => $this->lockedBy->name ?? null,
                'locked_at' => $this->locked_at?->toISOString(),
            ]),
            
            // URLs
            'url' => $this->url,
            'download_url' => route('api.files.download', ['file' => $this->id]),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'last_accessed_at' => $this->last_accessed_at?->toISOString(),
        ];
    }
}
