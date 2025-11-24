<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\DecisionService;
use App\Modules\Notes\Http\Requests\ExportPVRequest;
use App\Modules\Notes\Http\Requests\ExportPVDeliberationRequest;
use App\Modules\Notes\Http\Requests\GetStudentsBySemesterRequest;
use App\Modules\Notes\Http\Requests\GetStudentsByYearRequest;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class DecisionController extends Controller
{
    use ApiResponse;

    private DecisionService $decisionService;

    public function __construct(DecisionService $decisionService)
    {
        $this->decisionService = $decisionService;
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
}
