<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\TarifService;
use App\Modules\Finance\Http\Requests\CreateTarifRequest;
use App\Modules\Finance\Http\Requests\UpdateTarifRequest;
use Illuminate\Http\Request;

class TarifController extends Controller
{
    protected $tarifService;

    public function __construct(TarifService $tarifService)
    {
        $this->tarifService = $tarifService;
    }

    /**
     * Liste tous les tarifs
     */
    public function index()
    {
        try {
            $tarifs = $this->tarifService->getAllTarifs();
            
            return response()->json([
                'success' => true,
                'data' => $tarifs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tarifs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
<<<<<<< HEAD
     * Liste toutes les classes disponibles
     */
    public function getAvailableClasses(Request $request)
    {
        try {
            $academicYearId = $request->query('academic_year_id');
            
            $query = \DB::table('class_groups')
                ->join('departments', 'class_groups.department_id', '=', 'departments.id');
            
            if ($academicYearId) {
                $query->where('class_groups.academic_year_id', $academicYearId);
            }
            
            $classes = $query->select(
                    'class_groups.academic_year_id',
                    'class_groups.department_id',
                    'class_groups.study_level',
                    'departments.name as department_name'
                )
                ->groupBy('class_groups.academic_year_id', 'class_groups.department_id', 'class_groups.study_level', 'departments.name')
                ->get()
                ->map(function($class) {
                    return [
                        'academic_year_id' => (int) $class->academic_year_id,
                        'department_id' => (int) $class->department_id,
                        'study_level' => (int) $class->study_level,
                        'label' => $class->department_name . ' - Niveau ' . $class->study_level,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère un tarif avec ses classes
     */
    public function show($id)
    {
        try {
            $amount = \App\Modules\Finance\Models\Amount::with('academicYear')->findOrFail($id);
            
            $classGroups = \DB::table('amount_class_groups')
                ->where('amount_id', $id)
                ->select('academic_year_id', 'department_id', 'study_level')
                ->get()
                ->map(fn($c) => [
                    'academic_year_id' => (int) $c->academic_year_id,
                    'department_id' => (int) $c->department_id,
                    'study_level' => (int) $c->study_level,
                ]);
            
            $amount->class_groups = $classGroups;
            
            return response()->json([
                'success' => true,
                'data' => $amount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
=======
>>>>>>> eea2b06 (draft)
     * Crée un nouveau tarif
     */
    public function store(CreateTarifRequest $request)
    {
        try {
            $tarif = $this->tarifService->createTarif($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Tarif créé avec succès',
                'data' => $tarif
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un tarif
     */
    public function update(UpdateTarifRequest $request, $id)
    {
        try {
            $tarif = $this->tarifService->updateTarif($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Tarif mis à jour avec succès',
                'data' => $tarif
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un tarif
     */
    public function destroy($id)
    {
        try {
            $this->tarifService->deleteTarif($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Tarif supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}