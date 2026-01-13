<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\AcademicLevelFee;
use Illuminate\Support\Facades\DB;

class AcademicLevelFeeService
{
    public function getAllFees(array $filters = [])
    {
        $query = AcademicLevelFee::with(['academicYear', 'department.cycle']);

        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['study_level'])) {
            $query->where('study_level', $filters['study_level']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('academic_year_id', 'desc')
            ->orderBy('department_id')
            ->orderBy('study_level')
            ->get();
    }

    public function createFee(array $data): AcademicLevelFee
    {
        return DB::transaction(function () use ($data) {
            return AcademicLevelFee::create($data);
        });
    }

    public function createBulkFees(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $departmentIds = $data['department_ids'];
            unset($data['department_ids']);
            
            $fees = [];
            foreach ($departmentIds as $departmentId) {
                $feeData = array_merge($data, ['department_id' => $departmentId]);
                $fees[] = AcademicLevelFee::create($feeData);
            }
            
            return $fees;
        });
    }

    public function updateFee(string $uuid, array $data): AcademicLevelFee
    {
        return DB::transaction(function () use ($uuid, $data) {
            $fee = AcademicLevelFee::where('uuid', $uuid)->firstOrFail();
            $fee->update($data);
            return $fee->fresh(['academicYear', 'department.cycle']);
        });
    }

    public function deleteFee(string $uuid): bool
    {
        $fee = AcademicLevelFee::where('uuid', $uuid)->firstOrFail();
        return $fee->delete();
    }

    public function getFeeForStudent(int $academicYearId, int $departmentId, string $studyLevel, string $origin = 'uemoa'): ?array
    {
        $fee = AcademicLevelFee::where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $studyLevel)
            ->where('is_active', true)
            ->first();

        if (!$fee) {
            return null;
        }

        return [
            'registration_fee' => (float) $fee->registration_fee,
            'training_fee' => $fee->getTrainingFeeByOrigin($origin),
            'total_fee' => $fee->getTotalFeeByOrigin($origin),
        ];
    }
}
