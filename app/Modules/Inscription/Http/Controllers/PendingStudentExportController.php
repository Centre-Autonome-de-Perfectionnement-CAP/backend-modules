<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PendingStudentExportController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'cohort' => 'required|not_in:all',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ]);
        
        $data = $this->prepareData($request);
        $template = $this->getTemplate($data['isPrepa']);
        $filename = $this->generateFilename('pdf', $data);
        
        $pdf = Pdf::loadView("core::pdfs.{$template}", $data)
            ->setPaper('a4', 'landscape');
        
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'cohort' => 'required|not_in:all',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ]);
        
        $data = $this->prepareData($request);
        $template = $this->getTemplate($data['isPrepa']);
        $filename = $this->generateFilename('xlsx', $data);
        
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
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend();
    }

    public function exportWord(Request $request)
    {
        $request->validate([
            'cohort' => 'required|not_in:all',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ]);
        
        $data = $this->prepareData($request);
        $template = $this->getTemplate($data['isPrepa']);
        $filename = $this->generateFilename('docx', $data);
        
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
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend();
    }

    private function prepareData(Request $request): array
    {
        $year = $request->get('year');
        $filiere = $request->get('filiere');
        $cohort = $request->get('cohort');
        
        $query = PendingStudent::with(['personalInformation', 'department', 'academicYear']);
        
        if ($year && $year !== 'all') {
            if (is_numeric($year)) {
                $query->where('academic_year_id', $year);
            } else {
                $query->whereHas('academicYear', function($q) use ($year) {
                    $q->where('academic_year', $year);
                });
            }
        }
        
        if ($filiere && $filiere !== 'all') {
            if (is_numeric($filiere)) {
                $query->where('department_id', $filiere);
            } else {
                $query->whereHas('department', function($q) use ($filiere) {
                    $q->where('name', $filiere);
                });
            }
        }
        
        if ($cohort && $cohort !== 'all' && $year && is_numeric($year)) {
            $periods = \DB::table('submission_periods')
                ->where('academic_year_id', $year)
                ->select('start_date', 'end_date')
                ->groupBy('start_date', 'end_date')
                ->orderBy('start_date')
                ->get();
            
            $cohortIndex = (int)$cohort - 1;
            if (isset($periods[$cohortIndex])) {
                $period = $periods[$cohortIndex];
                $query->whereDate('created_at', '>=', $period->start_date)
                      ->whereDate('created_at', '<=', $period->end_date);
            }
        }
        
        $pendingStudents = $query->get();
        
        $academicYear = null;
        if ($year && is_numeric($year)) {
            $academicYear = AcademicYear::find($year);
        } else {
            $academicYear = AcademicYear::where('is_current', true)->first();
        }
        
        $department = $pendingStudents->first()?->department;
        $isPrepa = $department && strpos(strtolower($department->name), 'prepa') !== false;
        
        return [
            'pendingStudents' => $pendingStudents,
            'academicYear' => $academicYear?->academic_year ?? 'N/A',
            'department' => $department?->name ?? 'Toutes filières',
            'formation' => $department?->name ?? 'Formation générale',
            'isPrepa' => $isPrepa,
            'includeContact' => false,
            'cohort' => $cohort ?? 'all'
        ];
    }

    private function getTemplate(bool $isPrepa): string
    {
        return $isPrepa ? 'liste-cuca-cuo-prepa' : 'liste-cuca-cuo';
    }

    private function generateFilename(string $extension, array $data): string
    {
        $department = str_replace(' ', '_', $data['department']);
        $academicYear = str_replace(['/', '-'], '_', $data['academicYear']);
        $cohort = $data['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');
        
        return "LISTE_CUCA_CUO_{$academicYear}_COHORTE_{$cohort}_{$department}_{$dateTime}.{$extension}";
    }
}