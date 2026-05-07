<?php

namespace App\Modules\Soutenance\Services;

use App\Modules\Soutenance\Models\DefenseSubmissionPeriod;
use Illuminate\Support\Facades\Log;

class DefenseSubmissionPeriodService
{
    public function getAll(array $filters = [])
    {
        $query = DefenseSubmissionPeriod::query()->with('academicYear');

        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    public function create(array $data): DefenseSubmissionPeriod
    {
        $period = DefenseSubmissionPeriod::create($data);

        Log::info('Période de soumission créée', ['period_id' => $period->id]);

        return $period->load('academicYear');
    }

    public function update(DefenseSubmissionPeriod $period, array $data): DefenseSubmissionPeriod
    {
        $period->update($data);

        Log::info('Période de soumission mise à jour', ['period_id' => $period->id]);

        return $period->fresh('academicYear');
    }

    public function delete(DefenseSubmissionPeriod $period): bool
    {
        $period->delete();

        Log::info('Période de soumission supprimée', ['period_id' => $period->id]);

        return true;
    }

    public function getActivePeriod()
    {
        $now = now();
        return DefenseSubmissionPeriod::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->with('academicYear')
            ->first();
    }
}
