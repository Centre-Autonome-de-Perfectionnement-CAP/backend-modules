<?php

namespace App\Modules\Inscription\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionPeriodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'academic_year' => $this->academicYear->academic_year,
                    'year_start' => $this->academicYear->year_start,
                    'year_end' => $this->academicYear->year_end,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
