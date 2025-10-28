<?php

namespace App\Modules\Inscription\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->academic_year,
            'date_debut' => $this->submission_start?->format('Y-m-d'),
            'date_fin' => $this->submission_end?->format('Y-m-d'),
        ];
    }
}
