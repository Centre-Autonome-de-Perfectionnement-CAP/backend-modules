<?php

namespace App\Modules\Inscription\Http\Resources;

use App\Modules\Inscription\Models\AcademicYear;
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
        if ($this->is_current !== $isCurrent) {
            $this->resource->update(['is_current' => $isCurrent]);
            if ($isCurrent) {
                AcademicYear::where('id', '!=', $this->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        }
        
        return [
            'id' => $this->id,
            'libelle' => $this->academic_year,
            'date_debut' => $this->year_start,
            'date_fin' => $this->year_end,
            'is_current' => $isCurrent,
        ];
    }
}