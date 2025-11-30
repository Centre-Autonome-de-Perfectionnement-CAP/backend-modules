<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\DecisionService;
use App\Modules\Notes\Services\CourseRetakeService;
use App\Modules\Notes\Http\Requests\ExportPVRequest;
use App\Modules\Notes\Http\Requests\ExportPVDeliberationRequest;
use App\Modules\Notes\Http\Requests\GetStudentsBySemesterRequest;
use App\Modules\Notes\Http\Requests\GetStudentsByYearRequest;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    use ApiResponse;

    private DecisionService $decisionService;
    private CourseRetakeService $retakeService;

    public function __construct(DecisionService $decisionService, CourseRetakeService $retakeService)
    {
        $this->decisionService = $decisionService;
        $this->retakeService = $retakeService;
    }

    public function exportPVFinAnnee(ExportPVRequest $request)
    {
        $data = $this->decisionService->preparePVFinAnneeData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->validation_average ?? 12
        );

        $result = $this->decisionService->processYearDeliberationAndProgression(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->validation_average ?? 12,
            $request->deliberation_date,
            $data['etudiants']->toArray()
        );
        
        \Log::info('Résultat processYearDeliberationAndProgression', $result);

        $cleanAnnee = str_replace(['/', '-', ' ', '(', ')'], '_', $data['annee']);
        $filename = "PV_Fin_Annee_{$cleanAnnee}.pdf";

        $pdf = Pdf::loadView('core::pdfs.proces-verbal-resultats-fin-annee', $data)
            ->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function exportPVDeliberation(ExportPVDeliberationRequest $request)
    {
        $data = $this->decisionService->preparePVDeliberationData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->semester
        );

        $cleanAnnee = str_replace(['/', '-', ' ', '(', ')'], '_', $data['annee']);
        $filename = "PV_Deliberation_S{$request->semester}_{$cleanAnnee}.pdf";

        $pdf = Pdf::loadView('core::pdfs.pv-deliberation-semestriel', $data)
            ->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function exportRecapNotes(ExportPVDeliberationRequest $request)
    {
        $data = $this->decisionService->prepareRecapNotesData(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->semester
        );

        $cleanAnnee = str_replace(['/', '-', ' ', '(', ')'], '_', $data['annee']);
        $filename = "Recap_Notes_{$cleanAnnee}.pdf";

        $pdf = Pdf::loadView('core::pdfs.recapitulatif-notes-session-normale', $data)
            ->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function getStudentsBySemester(GetStudentsBySemesterRequest $request)
    {
        $students = $this->decisionService->getStudentsBySemester(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort,
            $request->semester
        );

        return $this->successResponse($students, 'Étudiants récupérés avec succès');
    }

    public function getStudentsByYear(GetStudentsByYearRequest $request)
    {
        $students = $this->decisionService->getStudentsByYear(
            $request->academic_year_id,
            $request->department_id,
            $request->level,
            $request->cohort
        );

        return $this->successResponse($students, 'Étudiants récupérés avec succès');
    }

    public function saveSemesterDecisions(Request $request)
    {
        $request->validate([
            'decisions' => 'required|array',
            'decisions.*.student_pending_student_id' => 'required|integer',
            'decisions.*.semester_decision' => 'required|string'
        ]);

        $result = $this->decisionService->saveSemesterDecisions($request->decisions);
        return $this->successResponse($result, 'Décisions semestrielles enregistrées');
    }

    public function saveYearDecisions(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|integer',
            'class_group_id' => 'required|integer',
            'decisions' => 'required|array',
            'decisions.*.student_pending_student_id' => 'required|integer',
            'decisions.*.year_decision' => 'required|string'
        ]);

        $result = $this->decisionService->saveYearDecisions($request->decisions);
        
        $retakes = $this->retakeService->processYearEndRetakes(
            $request->academic_year_id,
            $request->class_group_id
        );

        return $this->successResponse([
            'decisions' => $result,
            'retakes_created' => count($retakes)
        ], 'Décisions annuelles enregistrées et reprises créées');
    }
}
