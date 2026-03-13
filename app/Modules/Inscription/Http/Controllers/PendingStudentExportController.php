<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\PendingStudentExportService;
use App\Modules\Inscription\Http\Requests\ExportPendingStudentsRequest;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PendingStudentExportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PendingStudentExportService $exportService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function exportPdf(ExportPendingStudentsRequest $request)
    {
        $filters = $request->only(['year', 'filiere', 'cohort']);
        
        $validation = $this->exportService->validateStudentsHaveStatus($filters);
        if ($validation) {
            return $this->errorResponse($validation['message'], 422);
        }
        
        $data = $this->exportService->prepareExportData($filters);
        $template = $this->exportService->getTemplate($data['isPrepa']);
        $filename = $this->exportService->generateFilename('pdf', $data);
        
        \Log::info('Export PDF filename:', ['filename' => $filename, 'data' => $data]);
        
        $pdf = Pdf::loadView("core::pdfs.{$template}", $data)
            ->setPaper('a4', 'landscape');
        
        $output = $pdf->output();
        
        return response()->make($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportExcel(ExportPendingStudentsRequest $request)
    {
        $filters = $request->only(['year', 'filiere', 'cohort']);
        
        $validation = $this->exportService->validateStudentsHaveStatus($filters);
        if ($validation) {
            return $this->errorResponse($validation['message'], 422);
        }
        
        $data = $this->exportService->prepareExportData($filters);
        $template = $this->exportService->getTemplate($data['isPrepa']);
        $filename = $this->exportService->generateFilename('xlsx', $data);
        
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $worksheet->setCellValue('A1', 'Liste CUCA-CUO - ' . $data['department']);
        
        $row = 3;
        $worksheet->setCellValue('A' . $row, 'N° d\'ordre');
        $worksheet->setCellValue('B' . $row, 'Nom et prénoms');
        $worksheet->setCellValue('C' . $row, 'Nationalité');
        $worksheet->setCellValue('D' . $row, 'Spécialité');
        $worksheet->setCellValue('E' . $row, 'Documents');
        $worksheet->setCellValue('F' . $row, 'Avis CUCA');
        $worksheet->setCellValue('G' . $row, 'Commentaire');
        if (!$data['isPrepa']) {
            $worksheet->setCellValue('H' . $row, 'Décision CUO');
        }
        
        $row++;
        $i = 1;
        foreach ($data['pendingStudents'] as $student) {
            $worksheet->setCellValue('A' . $row, $i);
            $worksheet->setCellValue('B' . $row, $student->personalInformation->last_name . ' ' . $student->personalInformation->first_names);
            $worksheet->setCellValue('C' . $row, $student->personalInformation->birth_country);
            $worksheet->setCellValue('D' . $row, $data['isPrepa'] ? 'Première année en Classes Préparatoires' : 'Première année en ' . $student->department->name);
            $documents = is_string($student->documents) ? json_decode($student->documents, true) : $student->documents;
            $worksheet->setCellValue('E' . $row, implode(', ', array_keys($documents ?? [])));
            $worksheet->setCellValue('F' . $row, $student->cuca_opinion === 'pending' ? 'Non défini' : $student->cuca_opinion);
            $worksheet->setCellValue('G' . $row, $student->cuca_comment ?? '');
            if (!$data['isPrepa']) {
                $worksheet->setCellValue('H' . $row, $student->cuo_opinion === 'pending' ? 'Non défini' : ($student->cuo_opinion ?? ''));
            }
            
            $row++;
            $i++;
        }
        
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ])->deleteFileAfterSend();
    }

    public function exportWord(ExportPendingStudentsRequest $request)
    {
        $filters = $request->only(['year', 'filiere', 'cohort']);
        
        $validation = $this->exportService->validateStudentsHaveStatus($filters);
        if ($validation) {
            return $this->errorResponse($validation['message'], 422);
        }
        
        $data = $this->exportService->prepareExportData($filters);
        $template = $this->exportService->getTemplate($data['isPrepa']);
        $filename = $this->exportService->generateFilename('docx', $data);
        
        \Log::info('Export Word filename:', ['filename' => $filename]);
        
        $phpWord = new PhpWord();
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        
        // Titre
        $section->addText("Liste CUCA-CUO - {$data['department']}", ['bold' => true, 'size' => 16]);
        $section->addTextBreak();
        
        // Informations générales
        $section->addText("ETABLISSEMENT : Ecole Polytechnique d'Abomey-Calavi (EPAC)");
        $section->addText("DEPARTEMENT : Centre Autonome de Perfectionnement (CAP)");
        $section->addText("FORMATION : {$data['department']} ({$data['formation']})");
        $section->addText("ANNEE ACADEMIQUE : {$data['academicYear']}");
        $section->addTextBreak();
        
        // Tableau
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $table = $section->addTable($tableStyle);
        
        // En-têtes
        $table->addRow();
        $table->addCell(1000)->addText('N°', ['bold' => true]);
        $table->addCell(3000)->addText('Nom et prénoms', ['bold' => true]);
        $table->addCell(2000)->addText('Nationalité', ['bold' => true]);
        $table->addCell(2500)->addText('Spécialité', ['bold' => true]);
        $table->addCell(3000)->addText('Documents', ['bold' => true]);
        $table->addCell(2000)->addText('Avis CUCA', ['bold' => true]);
        $table->addCell(2000)->addText('Commentaire', ['bold' => true]);
        if (!$data['isPrepa']) {
            $table->addCell(2000)->addText('Décision CUO', ['bold' => true]);
        }
        
        // Données
        $i = 1;
        foreach ($data['pendingStudents'] as $student) {
            $table->addRow();
            $table->addCell(1000)->addText($i);
            $table->addCell(3000)->addText($student->personalInformation->last_name . ' ' . $student->personalInformation->first_names);
            $table->addCell(2000)->addText($student->personalInformation->birth_country);
            $table->addCell(2500)->addText($data['isPrepa'] ? 'Première année en Classes Préparatoires' : 'Première année en ' . $student->department->name);
            $documents = is_string($student->documents) ? json_decode($student->documents, true) : $student->documents;
            $table->addCell(3000)->addText(implode(', ', array_keys($documents ?? [])));
            $table->addCell(2000)->addText($student->cuca_opinion === 'pending' ? 'Non défini' : $student->cuca_opinion);
            $table->addCell(2000)->addText($student->cuca_comment ?? '');
            if (!$data['isPrepa']) {
                $table->addCell(2000)->addText($student->cuo_opinion === 'pending' ? 'Non défini' : ($student->cuo_opinion ?? ''));
            }
            
            $i++;
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ])->deleteFileAfterSend();
    }

    public function exportEmails(ExportPendingStudentsRequest $request)
    {
        \Log::channel('single')->info('=== DEBUT exportEmails ===');
        \Log::channel('single')->info('Request URL: ' . $request->fullUrl());
        \Log::channel('single')->info('Request Method: ' . $request->method());
        \Log::channel('single')->info('Auth header: ' . $request->header('Authorization'));
        
        $filters = $request->only(['year', 'filiere', 'cohort']);
        \Log::channel('single')->info('Filters:', $filters);
        
        $data = $this->exportService->prepareEmailsExportData($filters);
        \Log::channel('single')->info('Data prepared:', ['totalStudents' => $data['totalStudents'], 'academicYear' => $data['academicYear']]);
        
        $filename = $this->exportService->generateEmailsFilename($data);
        \Log::channel('single')->info('Filename generated:', ['filename' => $filename]);
        
        // Export Excel
        $filename = str_replace('.pdf', '.xlsx', $filename);
        
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        $row = 1;
        $worksheet->setCellValue('A' . $row, 'Liste des Emails - Étudiants en Attente');
        $worksheet->mergeCells('A1:C1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $row += 2;
        $worksheet->setCellValue('A' . $row, 'Année Académique: ' . $data['academicYear']);
        $row++;
        $worksheet->setCellValue('A' . $row, 'Total étudiants: ' . $data['totalStudents']);
        $row++;
        $worksheet->setCellValue('A' . $row, 'Date: ' . $data['exportDate']);
        $row += 2;
        
        foreach ($data['studentsByDepartment'] as $departmentName => $students) {
            $worksheet->setCellValue('A' . $row, $departmentName . ' (' . $students->count() . ' étudiant(s))');
            $worksheet->mergeCells('A' . $row . ':C' . $row);
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $worksheet->setCellValue('A' . $row, 'N°');
            $worksheet->setCellValue('B' . $row, 'Nom et Prénoms');
            $worksheet->setCellValue('C' . $row, 'Email');
            $worksheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $row++;
            
            $i = 1;
            foreach ($students as $student) {
                $worksheet->setCellValue('A' . $row, $i);
                $worksheet->setCellValue('B' . $row, $student->personalInformation->last_name . ' ' . $student->personalInformation->first_names);
                $worksheet->setCellValue('C' . $row, $student->personalInformation->email);
                $row++;
                $i++;
            }
            
            $emailList = $students->pluck('personalInformation.email')->implode(', ');
            $row++;
            $worksheet->setCellValue('A' . $row, 'Emails (copier-coller):');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $worksheet->setCellValue('A' . $row, $emailList);
            $worksheet->mergeCells('A' . $row . ':C' . $row);
            $row += 2;
        }
        
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ])->deleteFileAfterSend();
    }
}    /*
*
     * Export PDF des étudiants validés par la CUO groupés par Prépa et Licence
     */
    public function exportValidatedStudents(ExportPendingStudentsRequest $request)
    {
        $filters = $request->only(['year', 'type']);
        
        if (empty($filters['year']) || $filters['year'] === 'all') {
            return $this->errorResponse('Veuillez sélectionner une année académique', 422);
        }
        
        if (empty($filters['type'])) {
            return $this->errorResponse('Veuillez sélectionner un type d\'étudiant', 422);
        }
        
        $data = $this->exportService->prepareValidatedStudentsByTypeExportData($filters);
        
        if ($data['totalStudents'] === 0) {
            return $this->errorResponse('Aucun étudiant validé trouvé pour cette année académique et ce type', 404);
        }
        
        $filename = $this->exportService->generateValidatedStudentsFilename($data);
        
        $pdf = Pdf::loadView('core::pdfs.liste-etudiants-valides-par-type', $data)
            ->setPaper('a4', 'landscape');
        
        $output = $pdf->output();
        
        return response()->make($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }