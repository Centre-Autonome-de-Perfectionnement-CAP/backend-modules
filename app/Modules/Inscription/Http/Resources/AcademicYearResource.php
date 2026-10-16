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
        $today = $now->toDateString();

        // Vérifier si des périodes de dépôt ou réclamation sont actives pour cette année
        $hasActivePeriods = $this->submissionPeriods()
            ->where(function ($q) use ($now, $today) {
                $q->where(function ($sub) use ($now) {
                    $sub->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                })->orWhere(function ($sub) use ($today) {
                    $sub->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today);
                });
            })->exists();

        $isWithinDates = ($this->year_start && $this->year_end)
            ? $now->between($this->year_start, $this->year_end)
            : false;

        $isCurrent = (bool) ($this->is_current || $hasActivePeriods || $isWithinDates);

        if ($isCurrent) {
            $status = 'active';
        } elseif ($this->year_start && $now->lt($this->year_start)) {
            $status = 'upcoming';
        } else {
            $status = 'ended';
        }

        return [
            'id'                 => $this->id,
            'libelle'            => $this->academic_year,
            'date_debut'         => $this->year_start?->format('Y-m-d') ?? (string)$this->year_start,
            'date_fin'           => $this->year_end?->format('Y-m-d') ?? (string)$this->year_end,
            'is_current'         => (bool) $this->is_current,
            'has_active_periods' => $hasActivePeriods,
            'status'             => $status,
        ];
    }
}
