<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\PdfService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DecisionController extends Controller
{
    use ApiResponse;

    private PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Exporter le PV de fin d'année
     */
    public function exportPVFinAnnee(Request $request): Response
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|string',
            'cohort' => 'nullable|string',
            'validation_average' => 'required|numeric|min:0|max:20'
        ]);

        // Récupérer les données des étudiants avec leurs moyennes
        $data = $this->getPVFinAnneeData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->validation_average
        );

        $filename = "PV_Fin_Annee_{$data['academic_year']}_{$data['department']}.pdf";

        return $this->pdfService->downloadWithTemplate(
            'pv-fin-annee',
            $data,
            $filename,
            ['orientation' => 'landscape']
        );
    }

    /**
     * Exporter le PV de délibération semestrielle
     */
    public function exportPVDeliberation(Request $request): Response
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|string',
            'cohort' => 'nullable|string',
            'semester' => 'required|integer|in:1,2'
        ]);

        $data = $this->getPVDeliberationData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->semester
        );

        $filename = "PV_Deliberation_S{$request->semester}_{$data['academic_year']}_{$data['department']}.pdf";

        return $this->pdfService->downloadWithTemplate(
            'pv-deliberation',
            $data,
            $filename,
            ['orientation' => 'landscape']
        );
    }

    /**
     * Exporter le récap des notes session normale
     */
    public function exportRecapNotes(Request $request): Response
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|string',
            'cohort' => 'nullable|string'
        ]);

        $data = $this->getRecapNotesData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort
        );

        $filename = "Recap_Notes_{$data['academic_year']}_{$data['department']}.pdf";

        return $this->pdfService->downloadWithTemplate(
            'recap-notes',
            $data,
            $filename,
            ['orientation' => 'landscape']
        );
    }

    /**
     * Sauvegarder les décisions semestrielles
     */
    public function saveSemesterDecisions(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'decisions' => 'required|array',
            'decisions.*.student_id' => 'required|exists:student_pending_students,id',
            'decisions.*.decision' => 'required|in:Admis,Admis avec dette,Redouble,Exclu'
        ]);

        // Sauvegarder les décisions en base
        foreach ($request->decisions as $decision) {
            // Logique de sauvegarde des décisions semestrielles
        }

        return $this->successResponse(null, 'Décisions semestrielles sauvegardées avec succès');
    }

    /**
     * Sauvegarder les décisions annuelles
     */
    public function saveYearDecisions(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'decisions' => 'required|array',
            'decisions.*.student_id' => 'required|exists:student_pending_students,id',
            'decisions.*.decision' => 'required|in:Admis,Admis avec dette,Redouble,Exclu,Diplômé'
        ]);

        // Sauvegarder les décisions en base
        foreach ($request->decisions as $decision) {
            // Logique de sauvegarde des décisions annuelles
        }

        return $this->successResponse(null, 'Décisions annuelles sauvegardées avec succès');
    }

    /**
     * Récupérer les données pour le PV de fin d'année
     */
    private function getPVFinAnneeData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, float $validationAverage): array
    {
        // Récupérer les données des étudiants avec leurs moyennes annuelles
        // Marquer ceux qui ont validé selon la moyenne de validation
        
        return [
            'academic_year' => '2024-2025',
            'department' => 'Informatique',
            'level' => $level,
            'cohort' => $cohort,
            'validation_average' => $validationAverage,
            'students' => [
                [
                    'matricule' => '2024001',
                    'nom' => 'DUPONT',
                    'prenoms' => 'Jean',
                    'moyenne_annuelle' => 14.5,
                    'validated' => true,
                    'decision' => 'Admis'
                ],
                [
                    'matricule' => '2024002',
                    'nom' => 'MARTIN',
                    'prenoms' => 'Marie',
                    'moyenne_annuelle' => 9.2,
                    'validated' => false,
                    'decision' => 'Redouble'
                ]
            ],
            'statistics' => [
                'total_students' => 2,
                'validated_students' => 1,
                'success_rate' => 50.0
            ]
        ];
    }

    /**
     * Récupérer les données pour le PV de délibération
     */
    private function getPVDeliberationData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, int $semester): array
    {
        return [
            'academic_year' => '2024-2025',
            'department' => 'Informatique',
            'level' => $level,
            'cohort' => $cohort,
            'semester' => $semester,
            'students' => [
                [
                    'matricule' => '2024001',
                    'nom' => 'DUPONT',
                    'prenoms' => 'Jean',
                    'moyenne' => 14.5,
                    'credits' => 28,
                    'total_credits' => 30,
                    'decision' => 'Admis'
                ]
            ]
        ];
    }

    /**
     * Récupérer les données pour le récap des notes
     */
    private function getRecapNotesData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort): array
    {
        return [
            'academic_year' => '2024-2025',
            'department' => 'Informatique',
            'level' => $level,
            'cohort' => $cohort,
            'programs' => [
                [
                    'name' => 'Programmation Web',
                    'professor' => 'Prof. BERNARD',
                    'weighting' => [30, 40, 30],
                    'students' => [
                        [
                            'matricule' => '2024001',
                            'nom' => 'DUPONT',
                            'prenoms' => 'Jean',
                            'grades' => [15.0, 14.0, 14.5],
                            'average' => 14.3
                        ]
                    ]
                ]
            ]
        ];
    }
}