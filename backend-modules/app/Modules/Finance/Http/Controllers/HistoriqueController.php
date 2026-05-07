<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\HistoriqueService;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * Récupère l'historique financier par classe
     */
    public function getByClass(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $year = $request->get('year');
            
            $historique = $this->historiqueService->getHistoriqueByClass($classId, $year);
            
            return response()->json([
                'success' => true,
                'data' => $historique
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère l'état financier détaillé d'un étudiant
     */
    public function getStudentFinancialState($studentId)
    {
        try {
            $financialState = $this->historiqueService->getStudentFinancialState($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $financialState
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'état financier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporte l'état financier d'une classe
     */
    public function exportClassFinancialState(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $year = $request->get('year');
            
            $export = $this->historiqueService->exportClassFinancialState($classId, $year);
            
            return response()->download($export['path'], $export['filename']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}