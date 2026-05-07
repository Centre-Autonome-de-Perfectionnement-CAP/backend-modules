<?php

namespace App\Modules\Cours\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseElementResourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_element_id' => $this->course_element_id,
            'course_element' => $this->whenLoaded('courseElement', function () {
                return [
                    'id' => $this->courseElement->id,
                    'name' => $this->courseElement->name,
                    'code' => $this->courseElement->code,
                ];
            }),
            'file_id' => $this->file_id,
            'title' => $this->title,
            'description' => $this->description,
            'resource_type' => $this->resource_type,
            'is_public' => $this->is_public,
            'file' => $this->whenLoaded('file', function () {
                return [
                    'id' => $this->file->id,
                    'name' => $this->file->name,
                    'original_name' => $this->file->original_name,
                    'mime_type' => $this->file->mime_type,
                    'size' => $this->file->size,
                    'url' => url("/api/stockage/files/{$this->file->id}/view"),
                    'download_url' => url("/api/stockage/files/{$this->file->id}/download"),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
