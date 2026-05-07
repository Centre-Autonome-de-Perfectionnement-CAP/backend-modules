<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Amount;
use App\Modules\Finance\Models\Exoneration;
use Illuminate\Support\Facades\DB;

class TarifService
{
    /**
     * Récupère tous les tarifs
     */
    public function getAllTarifs()
    {
        return Amount::with(['academicYear'])
            ->get()
            ->map(function($amount) {
                $classes = DB::table('amount_class_groups')
                    ->join('departments', 'amount_class_groups.department_id', '=', 'departments.id')
                    ->where('amount_id', $amount->id)
                    ->select('departments.name', 'amount_class_groups.study_level')
                    ->get();
                
                $classesList = $classes->map(function($c) {
                    return $c->name . '-' . $c->study_level;
                })->toArray();
                
                $amount->classes_list = implode(', ', $classesList);
                $amount->class_groups_count = count($classesList);
                return $amount;
            });
    }

    /**
     * Crée un nouveau tarif
     */
    public function createTarif($data)
    {
        DB::beginTransaction();
        
        try {
            $classGroups = $data['class_groups'];
            unset($data['class_groups']);
            
            $tarif = Amount::create($data);
            
            foreach ($classGroups as $class) {
                DB::table('amount_class_groups')->insert([
                    'amount_id' => $tarif->id,
                    'academic_year_id' => $class['academic_year_id'],
                    'department_id' => $class['department_id'],
                    'study_level' => $class['study_level'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            DB::commit();
            return $tarif->load('classGroups');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Met à jour un tarif
     */
    public function updateTarif($id, $data)
    {
        DB::beginTransaction();
        
        try {
            if ($data['type'] === 'exoneration') {
                $tarif = Exoneration::findOrFail($id);
            } else {
                $tarif = Amount::findOrFail($id);
            }
            
            $tarif->update($data);
            
            DB::commit();
            return $tarif;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Supprime un tarif
     */
    public function deleteTarif($id)
    {
        DB::beginTransaction();
        
        try {
            // Essaie d'abord dans Amount
            $amount = Amount::find($id);
            if ($amount) {
                $amount->delete();
                DB::commit();
                return;
            }
            
            // Puis dans Exoneration
            $exoneration = Exoneration::find($id);
            if ($exoneration) {
                $exoneration->delete();
                DB::commit();
                return;
            }
            
            throw new \Exception('Tarif non trouvé');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Calcule le montant exonéré pour un étudiant
     */
    public function calculateExonerationAmount($studentId, $baseAmount)
    {
        $exoneration = Exoneration::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();
        
        if (!$exoneration) {
            return 0;
        }
        
        if ($exoneration->type === 'percentage') {
            return ($baseAmount * $exoneration->value) / 100;
        }
        
        return $exoneration->value;
    }

    /**
     * Calcule les pénalités de retard
     */
    public function calculateLatePenalty($paymentDate, $dueDate, $baseAmount)
    {
        $penalty = Amount::where('type', 'penalty')->first();
        
        if (!$penalty || !$penalty->is_active) {
            return 0;
        }
        
        $daysLate = now()->parse($paymentDate)->diffInDays(now()->parse($dueDate));
        
        if ($daysLate <= 0) {
            return 0;
        }
        
        if ($penalty->penalty_type === 'percentage') {
            return ($baseAmount * $penalty->penalty_amount) / 100;
        }
        
        return $penalty->penalty_amount;
    }
}