<?php

namespace App\Modules\Attestation\Services;

use App\Modules\Inscription\Models\{AcademicPath, Student, StudentPendingStudent};
use App\Modules\Core\Services\PdfService;
use App\Models\Signataire;
use Illuminate\Support\Facades\DB;

class AttestationService
{
    private PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Récupère les étudiants éligibles pour une attestation
     */
    public function getEligibleStudents(
        ?int $academicYearId = null,
        ?int $departmentId = null,
        ?string $cohort = null,
        ?string $search = null
    ) {
        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])
        ->whereNotNull('year_decision');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($departmentId) {
            $query->whereHas('studentPendingStudent.pendingStudent', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        if ($search) {
            $query->whereHas('studentPendingStudent.pendingStudent.personalInformation', function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_names', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($path) {
            $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
            $student = $path->studentPendingStudent?->student;
            $department = $path->studentPendingStudent?->pendingStudent?->department;

            return [
                'id' => $path->id,
                'student_pending_student_id' => $path->student_pending_student_id,
                'student_id' => $student?->student_id_number,
                'last_name' => $personalInfo?->last_name,
                'first_names' => $personalInfo?->first_names,
                'department' => $department?->name,
                'study_level' => $path->study_level,
                'cohort' => $path->cohort,
                'year_decision' => $path->year_decision,
                'academic_year' => $path->academicYear?->libelle,
            ];
        });
    }

    /**
     * Récupère les étudiants éligibles pour certificat de classes préparatoires
     */
    public function getEligibleForPreparatoryClass(
        ?int $academicYearId = null,
        ?int $departmentId = null,
        ?string $cohort = null,
        ?string $search = null
    ) {
        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])
        ->where('study_level', 1)
        ->where('year_decision', 'pass')
        ->whereHas('studentPendingStudent.pendingStudent.department', function ($q) {
            $q->where('name', 'like', '%prepa%')
              ->orWhere('name', 'like', '%prépa%');
        });

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($departmentId) {
            $query->whereHas('studentPendingStudent.pendingStudent', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        if ($search) {
            $query->whereHas('studentPendingStudent.pendingStudent.personalInformation', function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_names', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($path) {
            $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
            $student = $path->studentPendingStudent?->student;
            $department = $path->studentPendingStudent?->pendingStudent?->department;

            return [
                'id' => $path->id,
                'student_pending_student_id' => $path->student_pending_student_id,
                'student_id' => $student?->student_id_number,
                'last_name' => $personalInfo?->last_name,
                'first_names' => $personalInfo?->first_names,
                'department' => $department?->name,
                'study_level' => $path->study_level,
                'cohort' => $path->cohort,
                'year_decision' => $path->year_decision,
                'academic_year' => $path->academicYear?->libelle,
            ];
        });
    }

    /**
     * Génère une attestation de succès
     */
    public function generateAttestationSucces(int $studentPendingStudentId)
    {
        $academicPath = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])->whereHas('studentPendingStudent', function ($q) use ($studentPendingStudentId) {
            $q->where('id', $studentPendingStudentId);
        })->firstOrFail();

        // Logique de génération du PDF
        return $this->pdfService->generatePdf('core::pdfs.attestation-succes', [
            'student' => $academicPath
        ]);
    }

    /**
     * Génère un certificat de classes préparatoires
     */
    public function generateCertificatPreparatoire(int $studentPendingStudentId)
    {
        $academicPath = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])
        ->where('student_pending_student_id', $studentPendingStudentId)
        ->where('study_level', 1)
        ->where('year_decision', 'pass')
        ->whereHas('studentPendingStudent.pendingStudent.department', function ($q) {
            $q->where('name', 'like', '%prepa%')
              ->orWhere('name', 'like', '%prépa%');
        })
        ->firstOrFail();

        $personalInfo = $academicPath->studentPendingStudent?->pendingStudent?->personalInformation;
        $department = $academicPath->studentPendingStudent?->pendingStudent?->department;

        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $deliberationDate = $academicPath->deliberation_date ?? now();
        
        $etudiant = (object) [
            'genre' => $personalInfo?->gender ?? 'M',
            'nom' => $personalInfo?->last_name ?? '',
            'prenoms' => $personalInfo?->first_names ?? '',
            'ne_vers' => 0,
            'date_naissance' => $personalInfo?->birth_date ? $personalInfo->birth_date->format('d') . ' ' . $monthsFr[(int)$personalInfo->birth_date->format('n')] . ' ' . $personalInfo->birth_date->format('Y') : '',
            'lieu_naissance' => $personalInfo?->birth_place ?? '',
            'pays_naissance' => $personalInfo?->birth_country ?? '',
            'matricule' => $academicPath->studentPendingStudent?->student?->student_id_number ?? '',
            'date_soutenance' => $deliberationDate->format('d') . ' ' . $monthsFr[(int)$deliberationDate->format('n')] . ' ' . $deliberationDate->format('Y'),
            'filiere' => (object) [
                'libelle' => str_replace(['PREPA', 'Prepa', 'prépa', 'Prépa'], '', $department?->name ?? ''),
                'diplome' => (object) [
                    'libelle' => 'Conseil de Perfectionnement'
                ]
            ]
        ];

        $signataireBd = Signataire::getByRole('Directeur');
        $poste = $signataireBd?->role?->name === 'Directeur' ? 'Le Directeur' : 'Le Chef CAP';
        $signataire = (object) [
            'poste' => $poste,
            'nomination' => $signataireBd?->nom ?? 'Prof. HOUNKONNOU Mahouton Norbert'
        ];

        return $this->pdfService->downloadPdf('core::pdfs.certificat-classes-preparatoires', [
            'etudiant' => $etudiant,
            'signataire' => $signataire
        ], 'certificat-preparatoire.pdf');
    }

    /**
     * Génère plusieurs certificats de classes préparatoires dans un seul PDF
     */
    public function generateMultipleCertificatsPreparatoires(array $studentPendingStudentIds)
    {
        $etudiants = [];
        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        foreach ($studentPendingStudentIds as $studentPendingStudentId) {
            $academicPath = AcademicPath::with([
                'studentPendingStudent.pendingStudent.personalInformation',
                'studentPendingStudent.pendingStudent.department',
                'studentPendingStudent.student',
                'academicYear'
            ])
            ->where('student_pending_student_id', $studentPendingStudentId)
            ->where('study_level', 1)
            ->where('year_decision', 'pass')
            ->whereHas('studentPendingStudent.pendingStudent.department', function ($q) {
                $q->where('name', 'like', '%prepa%')
                  ->orWhere('name', 'like', '%prépa%');
            })
            ->first();

            if (!$academicPath) {
                continue;
            }

            $personalInfo = $academicPath->studentPendingStudent?->pendingStudent?->personalInformation;
            $department = $academicPath->studentPendingStudent?->pendingStudent?->department;
            $deliberationDate = $academicPath->deliberation_date ?? now();
            
            $etudiants[] = (object) [
                'genre' => $personalInfo?->gender ?? 'M',
                'nom' => $personalInfo?->last_name ?? '',
                'prenoms' => $personalInfo?->first_names ?? '',
                'ne_vers' => 0,
                'date_naissance' => $personalInfo?->birth_date ? $personalInfo->birth_date->format('d') . ' ' . $monthsFr[(int)$personalInfo->birth_date->format('n')] . ' ' . $personalInfo->birth_date->format('Y') : '',
                'lieu_naissance' => $personalInfo?->birth_place ?? '',
                'pays_naissance' => $personalInfo?->birth_country ?? '',
                'matricule' => $academicPath->studentPendingStudent?->student?->student_id_number ?? '',
                'date_soutenance' => $deliberationDate->format('d') . ' ' . $monthsFr[(int)$deliberationDate->format('n')] . ' ' . $deliberationDate->format('Y'),
                'filiere' => (object) [
                    'libelle' => str_replace(['PREPA', 'Prepa', 'prépa', 'Prépa'], '', $department?->name ?? ''),
                    'diplome' => (object) [
                        'libelle' => 'Conseil de Perfectionnement'
                    ]
                ]
            ];
        }

        if (empty($etudiants)) {
            throw new \Exception('Aucun étudiant éligible trouvé');
        }

        $signataireBd = Signataire::getByRole('Directeur');
        $poste = $signataireBd?->role?->name === 'Directeur' ? 'Le Directeur' : 'Le Chef CAP';
        $signataire = (object) [
            'poste' => $poste,
            'nomination' => $signataireBd?->nom ?? 'Prof. HOUNKONNOU Mahouton Norbert'
        ];

        return $this->pdfService->downloadPdf('core::pdfs.certificats-classes-preparatoires-multiple', [
            'etudiants' => $etudiants,
            'signataire' => $signataire
        ], 'certificats-preparatoires.pdf');
    }

    /**
     * Génère plusieurs bulletins dans un seul PDF
     */
    public function generateMultipleBulletins(array $requests)
    {
        $bulletins = [];
        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        foreach ($requests as $request) {
            $studentPendingStudentId = $request['student_pending_student_id'];
            $academicYearId = $request['academic_year_id'];
            
            $academicPath = AcademicPath::with([
                'studentPendingStudent.pendingStudent.personalInformation',
                'studentPendingStudent.pendingStudent.department',
                'studentPendingStudent.student',
                'academicYear'
            ])
            ->where('student_pending_student_id', $studentPendingStudentId)
            ->where('academic_year_id', $academicYearId)
            ->first();
            
            if (!$academicPath) continue;
            
            $personalInfo = $academicPath->studentPendingStudent?->pendingStudent?->personalInformation;
            $department = $academicPath->studentPendingStudent?->pendingStudent?->department;
            $student = $academicPath->studentPendingStudent?->student;
            $pendingStudent = $academicPath->studentPendingStudent?->pendingStudent;
            
            // Récupérer la photo de l'étudiant
            $photoPath = null;
            if ($pendingStudent && $pendingStudent->photo) {
                // Le champ photo contient l'ID du fichier dans la table files
                $photoFile = \App\Modules\Stockage\Models\File::find($pendingStudent->photo);
                if ($photoFile && $photoFile->exists()) {
                    $photoPath = \Illuminate\Support\Facades\Storage::disk($photoFile->disk)->path($photoFile->file_path);
                }
            }
            
            // Récupérer le class_group_id de l'étudiant
            $classGroupId = DB::table('student_groups')
                ->where('student_id', $student->id)
                ->value('class_group_id');
            
            // Récupérer la moyenne minimale de validation de la classe
            $classGroup = DB::table('class_groups')
                ->where('id', $classGroupId)
                ->where('academic_year_id', $academicYearId)
                ->first();
            
            $validationAverage = $classGroup->validation_average ?? 10;
            
            // Récupérer les programmes de la classe pour l'année académique
            $programs = DB::table('programs')
                ->join('course_element_professor', 'programs.course_element_professor_id', '=', 'course_element_professor.id')
                ->join('course_elements', 'course_element_professor.course_element_id', '=', 'course_elements.id')
                ->where('programs.class_group_id', $classGroupId)
                ->select('programs.id as program_id', 'course_elements.code', 'course_elements.name', 'course_elements.credits', 'programs.semester')
                ->get();
            
            // Récupérer les notes de l'étudiant
            $grades = [];
            foreach ($programs as $program) {
                $gradeRecord = DB::table('old_system_grades')
                    ->where('student_pending_student_id', $studentPendingStudentId)
                    ->where('program_id', $program->program_id)
                    ->first();
                
                if ($gradeRecord) {
                    $grades[] = (object) [
                        'code' => $program->code,
                        'name' => $program->name,
                        'credits' => $program->credits,
                        'average' => $gradeRecord->average ?? 0,
                        'semester' => $program->semester,
                        'has_retake' => false
                    ];
                }
            }
            
            $grades = collect($grades);
            
            // Préparer les données du bulletin
            $bulletinData = [];
            $totalCredits = 0;
            $obtainedCredits = 0;
            $totalAverage = 0;
            $validatedUE = 0;
            $totalUE = $grades->count();
            
            foreach ($grades as $grade) {
                //$isValidated = $grade->average >= $validationAverage;
                $isValidated = $grade->average >= 12;
                $bulletinData[] = [
                    'code' => $grade->code,
                    'nom' => $grade->name,
                    'credit' => $grade->credits,
                    'moyenne' => $grade->average,
                    'frequence' => $grade->has_retake ? 2 : 1,
                    'etat' => $isValidated ? 'Validé' : 'Non validé'
                ];
                $totalCredits += $grade->credits;
                if ($isValidated) {
                    $obtainedCredits += $grade->credits;
                    $validatedUE++;
                }
                $totalAverage += $grade->average;
            }
            
            $moyenne = $totalUE > 0 ? round(($totalAverage / $totalUE) * 5, 2) : 0;
            $grade = $moyenne >= 90 ? 'A (Excellent)' : ($moyenne >= 80 ? 'B (Très Bien)' : ($moyenne >= 70 ? 'C (Bien)' : ($moyenne >= 60 ? 'D (Assez-Bien)' : 'E (Passable)')));
            
            $dateNaissance = $personalInfo?->birth_date ? 
                $personalInfo->birth_date->format('d') . ' ' . 
                $monthsFr[(int)$personalInfo->birth_date->format('n')] . ' ' . 
                $personalInfo->birth_date->format('Y') : '';
            
            $filiereNom = str_replace(['PREPA ', 'Prepa ', 'Prépa ', 'prépa ', 'PREPA', 'Prepa', 'Prépa', 'prépa'], '', $department?->name ?? '');
            $cycle = $department?->cycle;
            
            $etudiant = (object) [
                'matricule' => $student?->student_id_number ?? '',
                'genre' => $personalInfo?->gender === 'F' ? 'féminin' : 'masculin',
                'nom' => $personalInfo?->last_name ?? '',
                'prenoms' => $personalInfo?->first_names ?? '',
                'date_naissance' => $dateNaissance,
                'lieu_de_naissance' => $personalInfo?->birth_place ?? '',
                'photo' => $photoPath,
                'filiere' => (object) [
                    'nom' => $filiereNom,
                    'diplome' => (object) ['nom' => $cycle?->name ?? 'LMD']
                ]
            ];
            
            $signataireBd = Signataire::getByRole('Chef CAP') ?? Signataire::getByRole('Directeur');
            $signataire = (object) [
                'nomination' => $signataireBd?->nom ?? 'Prof. HOUNKONNOU Mahouton Norbert'
            ];
            
            // Générer le QR code
            $qrData = "Nom: {$etudiant->nom}\nPrénoms: {$etudiant->prenoms}\nMatricule: {$etudiant->matricule}\nFilière: {$filiereNom}\nDate d'impression: " . now()->format('d/m/Y');
            
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            $qrCodeSvg = $writer->writeString($qrData);
            $qrCodeBase64 = base64_encode($qrCodeSvg);
            
            $bulletins[] = [
                'annee' => $academicPath->academicYear?->academic_year ?? '',
                'qrcode' => $qrCodeBase64,
                'etudiant' => $etudiant,
                'signataire' => $signataire,
                'bulletin_data' => [[
                    ...$bulletinData,
                    'nombre_ue' => $totalUE,
                    'nombre_ue_valide' => $validatedUE,
                    'nombre_credit_total' => $totalCredits > 0 ? $totalCredits : 1,
                    'nombre_credit_obtenu' => $obtainedCredits,
                    'moyenne' => $moyenne,
                    'grade' => $grade,
                    'decision' => $academicPath->year_decision === 'pass' ? 'Admis' : ($academicPath->year_decision === 'repeat' ? 'Redouble' : 'Exclu')
                ]]
            ];
        }
        
        if (empty($bulletins)) {
            throw new \Exception('Aucun bulletin éligible trouvé');
        }
        
        return $this->pdfService->downloadPdf('core::pdfs.bulletins-multiple', [
            'bulletins' => $bulletins
        ], 'bulletins.pdf');
    }

    /**
     * Génère plusieurs attestations de licence dans un seul PDF
     */
    public function generateMultipleAttestationsLicence(array $studentPendingStudentIds)
    {
        $attestations = [];
        
        foreach ($studentPendingStudentIds as $studentPendingStudentId) {
            $academicPath = AcademicPath::with([
                'studentPendingStudent.pendingStudent.personalInformation',
                'studentPendingStudent.pendingStudent.department',
                'studentPendingStudent.student',
                'academicYear'
            ])->whereHas('studentPendingStudent', function ($q) use ($studentPendingStudentId) {
                $q->where('id', $studentPendingStudentId);
            })->where('study_level', 'L3')
            ->first();
            
            if (!$academicPath) continue;
            
            $attestations[] = [
                'student' => $academicPath
            ];
        }
        
        if (empty($attestations)) {
            throw new \Exception('Aucune attestation éligible trouvée');
        }
        
        return $this->pdfService->downloadPdf('core::pdfs.attestations-licence-multiple', [
            'attestations' => $attestations
        ], 'attestations-licence.pdf');
    }

    /**
     * Génère un bulletin (année complète)
     */
    public function generateBulletin(int $studentPendingStudentId, int $academicYearId)
    {
        $academicPath = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])
        ->where('student_pending_student_id', $studentPendingStudentId)
        ->where('academic_year_id', $academicYearId)
        ->firstOrFail();

        $personalInfo = $academicPath->studentPendingStudent?->pendingStudent?->personalInformation;
        $department = $academicPath->studentPendingStudent?->pendingStudent?->department;
        $student = $academicPath->studentPendingStudent?->student;

        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        // Récupérer le class_group_id de l'étudiant
        $classGroupId = DB::table('student_groups')
            ->where('student_id', $student->id)
            ->value('class_group_id');

        // Récupérer la moyenne minimale de validation de la classe
        $classGroup = DB::table('class_groups')
            ->where('id', $classGroupId)
            ->where('academic_year_id', $academicYearId)
            ->first();
        
        $validationAverage = $classGroup->validation_average ?? 10;

        // Récupérer les programmes de la classe pour l'année académique
        $programs = DB::table('programs')
            ->join('course_element_professor', 'programs.course_element_professor_id', '=', 'course_element_professor.id')
            ->join('course_elements', 'course_element_professor.course_element_id', '=', 'course_elements.id')
            ->where('programs.class_group_id', $classGroupId)
            ->select('programs.id as program_id', 'course_elements.code', 'course_elements.name', 'course_elements.credits', 'programs.semester')
            ->get();

        // Récupérer les notes de l'étudiant
        $grades = [];
        foreach ($programs as $program) {
            $gradeRecord = DB::table('old_system_grades')
                ->where('student_pending_student_id', $studentPendingStudentId)
                ->where('program_id', $program->program_id)
                ->first();

            if ($gradeRecord) {
                $grades[] = (object) [
                    'code' => $program->code,
                    'name' => $program->name,
                    'credits' => $program->credits,
                    'average' => $gradeRecord->average ?? 0,
                    'semester' => $program->semester,
                    'has_retake' => false // TODO: vérifier si l'étudiant a fait un rattrapage
                ];
            }
        }

        $grades = collect($grades);

        // Préparer les données du bulletin
        $bulletinData = [];
        $totalCredits = 0;
        $obtainedCredits = 0;
        $totalAverage = 0;
        $validatedUE = 0;
        $totalUE = $grades->count();

        foreach ($grades as $grade) {
            //$isValidated = $grade->average >= $validationAverage;
            $isValidated = $grade->average >= 12;
            $bulletinData[] = [
                'code' => $grade->code,
                'nom' => $grade->name,
                'credit' => $grade->credits,
                'moyenne' => $grade->average,
                'frequence' => $grade->has_retake ? 2 : 1,
                'etat' => $isValidated ? 'Validé' : 'Non validé'
            ];
            $totalCredits += $grade->credits;
            if ($isValidated) {
                $obtainedCredits += $grade->credits;
                $validatedUE++;
            }
            $totalAverage += $grade->average;
        }

        $moyenne = $totalUE > 0 ? round(($totalAverage / $totalUE) * 5, 2) : 0; // Moyenne sur 100
        $grade = $moyenne >= 90 ? 'A (Excellent)' : ($moyenne >= 80 ? 'B (Très Bien)' : ($moyenne >= 70 ? 'C (Bien)' : ($moyenne >= 60 ? 'D (Assez-Bien)' : 'E (Passable)')));

        // Formater la date de naissance en français
        $dateNaissance = $personalInfo?->birth_date ? 
            $personalInfo->birth_date->format('d') . ' ' . 
            $monthsFr[(int)$personalInfo->birth_date->format('n')] . ' ' . 
            $personalInfo->birth_date->format('Y') : '';

        // Nettoyer le nom de la filière (enlever "Prépa" ou "PREPA")
        $filiereNom = str_replace(['PREPA ', 'Prepa ', 'Prépa ', 'prépa ', 'PREPA', 'Prepa', 'Prépa', 'prépa'], '', $department?->name ?? '');

        // Récupérer le cycle
        $cycle = $department?->cycle;

        // Récupérer la photo de l'étudiant
        $pendingStudent = $academicPath->studentPendingStudent?->pendingStudent;
        $photoPath = null;
        if ($pendingStudent && $pendingStudent->photo) {
            // Le champ photo contient l'ID du fichier dans la table files
            $photoFile = \App\Modules\Stockage\Models\File::find($pendingStudent->photo);
            if ($photoFile && $photoFile->exists()) {
                $photoPath = \Illuminate\Support\Facades\Storage::disk($photoFile->disk)->path($photoFile->file_path);
            }
        }
        
        $etudiant = (object) [
            'matricule' => $student?->student_id_number ?? '',
            'genre' => $personalInfo?->gender === 'F' ? 'féminin' : 'masculin',
            'nom' => $personalInfo?->last_name ?? '',
            'prenoms' => $personalInfo?->first_names ?? '',
            'date_naissance' => $dateNaissance,
            'lieu_de_naissance' => $personalInfo?->birth_place ?? '',
            'photo' => $photoPath,
            'filiere' => (object) [
                'nom' => $filiereNom,
                'diplome' => (object) ['nom' => $cycle?->name ?? 'LMD']
            ]
        ];

        $signataireBd = Signataire::getByRole('Chef CAP') ?? Signataire::getByRole('Directeur');
        $signataire = (object) [
            'nomination' => $signataireBd?->nom ?? 'Prof. HOUNKONNOU Mahouton Norbert'
        ];

        // Générer le QR code avec BaconQrCode et GD
        $qrData = "Nom: {$etudiant->nom}\nPrénoms: {$etudiant->prenoms}\nMatricule: {$etudiant->matricule}\nFilière: {$filiereNom}\nDate d'impression: " . now()->format('d/m/Y');
        
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrData);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        return $this->pdfService->downloadPdf('core::pdfs.bulletin', [
            'annee' => $academicPath->academicYear?->academic_year ?? '',
            'qrcode' => $qrCodeBase64,
            'qrcode_type' => 'svg',
            'etudiant' => $etudiant,
            'signataire' => $signataire,
            'bulletin_data' => [[
                ...$bulletinData,
                'nombre_ue' => $totalUE,
                'nombre_ue_valide' => $validatedUE,
                'nombre_credit_total' => $totalCredits > 0 ? $totalCredits : 1,
                'nombre_credit_obtenu' => $obtainedCredits,
                'moyenne' => $moyenne,
                'grade' => $grade,
                'decision' => $academicPath->year_decision === 'pass' ? 'Admis' : ($academicPath->year_decision === 'repeat' ? 'Redouble' : 'Exclu')
            ]]
        ], 'bulletin.pdf');
    }

    /**
     * Génère une attestation de licence
     */
    public function generateAttestationLicence(int $studentPendingStudentId)
    {
        $academicPath = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department',
            'studentPendingStudent.student',
            'academicYear'
        ])->whereHas('studentPendingStudent', function ($q) use ($studentPendingStudentId) {
            $q->where('id', $studentPendingStudentId);
        })->where('study_level', 'L3')
        ->firstOrFail();

        return $this->pdfService->generatePdf('core::pdfs.attestation-licence', [
            'student' => $academicPath
        ]);
    }
}
