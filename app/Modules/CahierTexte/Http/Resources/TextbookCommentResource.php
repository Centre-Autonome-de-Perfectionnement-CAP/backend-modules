<?php

namespace App\Modules\CahierTexte\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TextbookCommentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'textbook_entry_id' => $this->textbook_entry_id,
            'user_id' => $this->user_id,
            'comment' => $this->comment,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            
            // Relations
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            
            'parent' => $this->whenLoaded('parent', function () {
                return new TextbookCommentResource($this->parent);
            }),
            
            'replies' => TextbookCommentResource::collection($this->whenLoaded('replies')),
            
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
