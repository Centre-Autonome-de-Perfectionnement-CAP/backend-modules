<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Exoneration;
use Illuminate\Support\Facades\DB;

class ExonerationService
{
    public function getAll(array $filters = [])
    {
        $query = Exoneration::query();

        if (!empty($filters['matricule'])) {
            $query->where('matricule', $filters['matricule']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
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
        // Récupérer le matricule de l'étudiant
        $academicPath = \App\Modules\Inscription\Models\AcademicPath::where('student_pending_student_id', $studentPendingStudentId)
            ->where('academic_year_id', $academicYearId)
            ->with('studentPendingStudent.pendingStudent.personalInformation')
            ->first();

        if (!$academicPath || !$academicPath->studentPendingStudent) {
            return null;
        }

        $matricule = $academicPath->studentPendingStudent->matricule;

        if (!$matricule) {
            return null;
        }

        // Rechercher l'exonération par matricule et vérifier les dates
        return Exoneration::where('matricule', $matricule)
            ->where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where(function($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->first();
    }

    public function calculateExoneratedAmount(float $baseAmount, ?Exoneration $exoneration): float
    {
        if (!$exoneration) {
            return $baseAmount;
        }

        // Si exonération totale
        if ($exoneration->type === 'full') {
            return 0;
        }

        // Si exonération partielle avec pourcentage
        if ($exoneration->percentage) {
            return $baseAmount * (1 - $exoneration->percentage / 100);
        }

        // Si exonération partielle avec montant fixe
        if ($exoneration->amount) {
            return max(0, $baseAmount - $exoneration->amount);
        }

        return $baseAmount;
    }
}
