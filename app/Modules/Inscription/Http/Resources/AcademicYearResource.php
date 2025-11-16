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
        $now = now();
        $isCurrent = $now->between($this->year_start, $this->year_end);
        
        return [
            'id' => $this->id,
            'libelle' => $this->academic_year,
            'date_debut' => $this->year_start,
            'date_fin' => $this->year_end,
            'is_current' => $isCurrent,
        ];
    }
}
