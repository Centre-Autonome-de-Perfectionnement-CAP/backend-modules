<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Exoneration;
use Illuminate\Support\Facades\DB;

class ExonerationService
{
    public function getAll(array $filters = [])
    {
        $query = Exoneration::with(['studentPendingStudent.pendingStudent.personalInformation']);

        if (!empty($filters['student_pending_student_id'])) {
            $query->where('student_pending_student_id', $filters['student_pending_student_id']);
        }

        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data): Exoneration
    {
        return Exoneration::create($data);
    }

    public function update(Exoneration $exoneration, array $data): Exoneration
    {
        $exoneration->update($data);
        return $exoneration->fresh();
    }

    public function delete(Exoneration $exoneration): bool
    {
        return $exoneration->delete();
    }

    public function getStudentExoneration(int $studentPendingStudentId, int $academicYearId): ?Exoneration
    {
        return Exoneration::where('student_pending_student_id', $studentPendingStudentId)
            ->where('academic_year_id', $academicYearId)
            ->first();
    }

    public function calculateExoneratedAmount(float $baseAmount, ?Exoneration $exoneration): float
    {
        if (!$exoneration) {
            return $baseAmount;
        }

        if ($exoneration->type === 'percentage') {
            return $baseAmount * (1 - $exoneration->value / 100);
        }

        return max(0, $baseAmount - $exoneration->value);
    }
}
