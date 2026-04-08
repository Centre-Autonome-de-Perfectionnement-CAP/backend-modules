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
     * Récupère le chemin de la photo d'un étudiant
     * Vérifie d'abord dans PendingStudent, puis dans PersonalInformation
     * 
     * @param mixed $pendingStudent
     * @param mixed $personalInfo
     * @return string|null Le chemin complet de la photo ou null
     */
    private function getStudentPhotoPath($pendingStudent, $personalInfo): ?string
    {
        \Log::info('🔍 [PHOTO] Début de la récupération de la photo', [
            'pending_student_id' => $pendingStudent?->id ?? 'null',
            'personal_info_id' => $personalInfo?->id ?? 'null',
            'pending_student_photo' => $pendingStudent?->photo ?? 'null',
            'personal_info_photo' => $personalInfo?->photo ?? 'null',
        ]);

        // Vérifier d'abord dans PendingStudent
        if ($pendingStudent && $pendingStudent->photo) {
            \Log::info('📸 [PHOTO] Photo trouvée dans PendingStudent', [
                'photo_id' => $pendingStudent->photo
            ]);
            
            // Vérifier si c'est un chemin direct ou un ID
            if (str_contains($pendingStudent->photo, '/')) {
                // C'est un chemin direct (ancien format)
                \Log::info('📂 [PHOTO] Chemin direct détecté (ancien format)', [
                    'photo_path' => $pendingStudent->photo
                ]);
                
                $fullPath = storage_path('app/public/' . $pendingStudent->photo);
                
                \Log::info('🗂️ [PHOTO] Chemin complet construit depuis chemin direct', [
                    'full_path' => $fullPath,
                    'file_exists' => file_exists($fullPath) ? 'OUI' : 'NON',
                ]);
                
                if (file_exists($fullPath)) {
                    \Log::info('✅ [PHOTO] Photo trouvée et validée dans PendingStudent (chemin direct)', [
                        'path' => $fullPath
                    ]);
                    return $fullPath;
                } else {
                    \Log::warning('⚠️ [PHOTO] Fichier physique introuvable (chemin direct)', [
                        'expected_path' => $fullPath
                    ]);
                }
            } else {
                // C'est un ID de fichier (nouveau format)
                $photoFile = \App\Modules\Stockage\Models\File::find($pendingStudent->photo);
                
                if ($photoFile) {
                    \Log::info('📁 [PHOTO] Fichier trouvé dans la table files', [
                        'file_id' => $photoFile->id,
                        'disk' => $photoFile->disk,
                        'file_path' => $photoFile->file_path,
                        'file_name' => $photoFile->file_name ?? 'N/A',
                    ]);
                    
                    $fullPath = \Illuminate\Support\Facades\Storage::disk($photoFile->disk)->path($photoFile->file_path);
                    
                    \Log::info('🗂️ [PHOTO] Chemin complet construit', [
                        'full_path' => $fullPath,
                        'file_exists' => file_exists($fullPath) ? 'OUI' : 'NON',
                    ]);
                    
                    if (file_exists($fullPath)) {
                        \Log::info('✅ [PHOTO] Photo trouvée et validée dans PendingStudent', [
                            'path' => $fullPath
                        ]);
                        return $fullPath;
                    } else {
                        \Log::warning('⚠️ [PHOTO] Fichier physique introuvable', [
                            'expected_path' => $fullPath
                        ]);
                    }
                } else {
                    \Log::warning('⚠️ [PHOTO] Enregistrement File introuvable dans la BDD', [
                        'photo_id' => $pendingStudent->photo
                    ]);
                }
            }
        } else {
            \Log::info('ℹ️ [PHOTO] Pas de photo dans PendingStudent');
        }
        
        // Si pas de photo dans PendingStudent, vérifier dans PersonalInformation
        if ($personalInfo && $personalInfo->photo) {
            \Log::info('📸 [PHOTO] Photo trouvée dans PersonalInformation', [
                'photo_id' => $personalInfo->photo
            ]);
            
            // Vérifier si c'est un chemin direct ou un ID
            if (str_contains($personalInfo->photo, '/')) {
                // C'est un chemin direct (ancien format)
                \Log::info('📂 [PHOTO] Chemin direct détecté dans PersonalInfo (ancien format)', [
                    'photo_path' => $personalInfo->photo
                ]);
                
                $fullPath = storage_path('app/public/' . $personalInfo->photo);
                
                \Log::info('🗂️ [PHOTO] Chemin complet construit depuis chemin direct (PersonalInfo)', [
                    'full_path' => $fullPath,
                    'file_exists' => file_exists($fullPath) ? 'OUI' : 'NON',
                ]);
                
                if (file_exists($fullPath)) {
                    \Log::info('✅ [PHOTO] Photo trouvée et validée dans PersonalInformation (chemin direct)', [
                        'path' => $fullPath
                    ]);
                    return $fullPath;
                } else {
                    \Log::warning('⚠️ [PHOTO] Fichier physique introuvable (chemin direct PersonalInfo)', [
                        'expected_path' => $fullPath
                    ]);
                }
            } else {
                // C'est un ID de fichier (nouveau format)
                $photoFile = \App\Modules\Stockage\Models\File::find($personalInfo->photo);
                
                if ($photoFile) {
                    \Log::info('📁 [PHOTO] Fichier trouvé dans la table files (PersonalInfo)', [
                        'file_id' => $photoFile->id,
                        'disk' => $photoFile->disk,
                        'file_path' => $photoFile->file_path,
                        'file_name' => $photoFile->file_name ?? 'N/A',
                    ]);
                    
                    $fullPath = \Illuminate\Support\Facades\Storage::disk($photoFile->disk)->path($photoFile->file_path);
                    
                    \Log::info('🗂️ [PHOTO] Chemin complet construit (PersonalInfo)', [
                        'full_path' => $fullPath,
                        'file_exists' => file_exists($fullPath) ? 'OUI' : 'NON',
                    ]);
                    
                    if (file_exists($fullPath)) {
                        \Log::info('✅ [PHOTO] Photo trouvée et validée dans PersonalInformation', [
                            'path' => $fullPath
                        ]);
                        return $fullPath;
                    } else {
                        \Log::warning('⚠️ [PHOTO] Fichier physique introuvable (PersonalInfo)', [
                            'expected_path' => $fullPath
                        ]);
                    }
                } else {
                    \Log::warning('⚠️ [PHOTO] Enregistrement File introuvable dans la BDD (PersonalInfo)', [
                        'photo_id' => $personalInfo->photo
                    ]);
                }
            }
        } else {
            \Log::info('ℹ️ [PHOTO] Pas de photo dans PersonalInformation');
        }
        
        \Log::warning('❌ [PHOTO] Aucune photo trouvée, utilisation de l\'avatar par défaut');
        return null;
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
            
            \Log::info('👤 [BULLETIN MULTIPLE] Données de l\'étudiant', [
                'student_id' => $student?->id,
                'student_number' => $student?->student_id_number,
                'pending_student_id' => $pendingStudent?->id,
                'personal_info_id' => $personalInfo?->id,
                'student_name' => ($personalInfo?->first_names ?? '') . ' ' . ($personalInfo?->last_name ?? ''),
            ]);
            
            // Récupérer la photo de l'étudiant
            $photoPath = $this->getStudentPhotoPath($pendingStudent, $personalInfo);
            
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
        $photoPath = $this->getStudentPhotoPath($pendingStudent, $personalInfo);
        
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

    // =========================================================================
    // HELPERS INTERNES — ajoutés depuis proj1
    // =========================================================================

    private function normalizeLevel($studyLevel): int
    {
        if (is_null($studyLevel)) return 0;
        $val = strtoupper(trim((string) $studyLevel));
        if (in_array($val, ['PREPA', 'PRÉPA', 'P', 'PC', 'PREPARATOIRE', 'PRÉPARATOIRE'])) return 0;
        $numeric = preg_replace('/^[A-Z]+/', '', $val);
        return (int) $numeric;
    }

    private function cycleIsPrepa(object $cycle): bool
    {
        $name = strtolower($cycle->name    ?? '');
        $type = strtolower($cycle->type    ?? '');
        $lib  = strtolower($cycle->libelle ?? '');
        return str_contains($name, 'prepa') || str_contains($name, 'prépa')
            || str_contains($type, 'prepa') || str_contains($type, 'prépa')
            || str_contains($lib,  'prepa') || str_contains($lib,  'prépa')
            || (int)($cycle->years_count ?? 0) === 1;
    }

    private function getLevelLabel(int $level, object $cycle): string
    {
        if ($level === 0) return 'Classe Préparatoire';
        $suffix    = $level === 1 ? 'ère' : 'ème';
        $cycleName = $cycle->libelle ?? $cycle->name ?? 'formation';
        return "{$level}{$suffix} année de {$cycleName}";
    }

    private function getNextLevelLabel(int $level, object $cycle): ?string
    {
        $yearsCount = (int)($cycle->years_count ?? 0);
        if ($this->cycleIsPrepa($cycle) || $level === 0) return null;
        if ($level >= $yearsCount) return null;
        $next      = $level + 1;
        $suffix    = $next === 1 ? 'ère' : 'ème';
        $cycleName = $cycle->libelle ?? $cycle->name ?? 'formation';
        return "{$next}{$suffix} année de {$cycleName}";
    }

    private function getSignataire(string ...$roles): object
    {
        $signataireBd = null;
        foreach ($roles as $role) {
            $signataireBd = \App\Models\Signataire::whereHas('role', function ($q) use ($role) {
                $q->where('name', $role)->orWhere('slug', strtolower(str_replace(' ', '-', $role)));
            })->first();
            if ($signataireBd) break;
        }
        $poste = 'Le Directeur';
        if ($signataireBd?->role) $poste = 'Le ' . $signataireBd->role->name;
        return (object) ['poste' => $poste, 'nomination' => $signataireBd?->nom ?? ''];
    }

    private function getMonthsFr(): array
    {
        return [
            1=>'Janvier', 2=>'Février', 3=>'Mars', 4=>'Avril', 5=>'Mai', 6=>'Juin',
            7=>'Juillet', 8=>'Août', 9=>'Septembre', 10=>'Octobre', 11=>'Novembre', 12=>'Décembre',
        ];
    }

    private function formatDateFr($date, array $monthsFr): string
    {
        if (!$date) return '';
        try { if (is_string($date)) $date = \Carbon\Carbon::parse($date); }
        catch (\Exception $e) { return (string) $date; }
        return $date->format('d') . ' ' . $monthsFr[(int)$date->format('n')] . ' ' . $date->format('Y');
    }

    private function nettoyerFiliere(string $nom): string
    {
        return trim(preg_replace('/pr[eé]pa(?:ratoire)?\s*/i', '', $nom));
    }

    private function ineligible(string $reason, int $level = 0, string $label = ''): array
    {
        return ['eligible' => false, 'reason' => $reason, 'normalized_level' => $level, 'level_label' => $label, 'next_level_label' => null];
    }

    private function getLastPassPath(int $studentPendingStudentId): AcademicPath
    {
        return AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department.cycle',
            'studentPendingStudent.student',
            'academicYear',
        ])
        ->where('student_pending_student_id', $studentPendingStudentId)
        ->where('year_decision', 'pass')
        ->whereNotNull('deliberation_date')
        ->latest('academic_year_id')
        ->firstOrFail();
    }

    public function checkPassageEligibility(AcademicPath $path): array
    {
        $department = $path->studentPendingStudent?->pendingStudent?->department;
        $cycle      = $department?->cycle;
        if (!$cycle) return $this->ineligible('Cycle de formation introuvable.');
        $yearsCount = (int)($cycle->years_count ?? 0);
        $level      = $this->normalizeLevel($path->study_level);
        $label      = $this->getLevelLabel($level, $cycle);
        if ($this->cycleIsPrepa($cycle)) return $this->ineligible('Les classes préparatoires donnent droit à un certificat, pas une attestation de passage.', $level, $label);
        if ($path->year_decision !== 'pass') return $this->ineligible("La décision pour {$label} n'est pas favorable.", $level, $label);
        if (empty($path->deliberation_date)) return $this->ineligible("La date de délibération de {$label} n'est pas renseignée.", $level, $label);
        if ($level >= $yearsCount) return $this->ineligible("L'étudiant est en {$label} (dernière année). Il est éligible à une attestation définitive.", $level, $label);
        $spId    = $path->student_pending_student_id;
        $cycleId = $department->cycle_id;
        $allPaths = AcademicPath::where('student_pending_student_id', $spId)
            ->whereHas('studentPendingStudent.pendingStudent.department', fn($q) => $q->where('cycle_id', $cycleId))->get();
        $sameLevel = $allPaths->filter(fn($p) => $this->normalizeLevel($p->study_level) === $level && $p->id !== $path->id);
        if ($sameLevel->isNotEmpty()) return $this->ineligible("L'étudiant est redoublant en {$label}.", $level, $label);
        if ($level > 1) {
            $prevLevel = $level - 1;
            $prevPath  = $allPaths->first(fn($p) => $this->normalizeLevel($p->study_level) === $prevLevel);
            if ($prevPath && $prevPath->year_decision !== 'pass') {
                $prevLabel = $this->getLevelLabel($prevLevel, $cycle);
                return $this->ineligible("La {$prevLabel} n'a pas été validée.", $level, $label);
            }
        }
        return ['eligible' => true, 'reason' => '', 'normalized_level' => $level, 'level_label' => $label, 'next_level_label' => $this->getNextLevelLabel($level, $cycle)];
    }

    public function checkDefinitiveEligibility(AcademicPath $path): array
    {
        $department = $path->studentPendingStudent?->pendingStudent?->department;
        $cycle      = $department?->cycle;
        if (!$cycle) return $this->ineligible('Cycle de formation introuvable.');
        $yearsCount = (int)($cycle->years_count ?? 0);
        $level      = $this->normalizeLevel($path->study_level);
        $label      = $this->getLevelLabel($level, $cycle);
        if ($this->cycleIsPrepa($cycle)) return $this->ineligible('Cycle préparatoire non concerné.', $level, $label);
        if ($path->year_decision !== 'pass') return $this->ineligible("Décision non favorable pour {$label}.", $level, $label);
        if (empty($path->deliberation_date)) return $this->ineligible("Date de délibération manquante pour {$label}.", $level, $label);
        if ($level < $yearsCount) return $this->ineligible("L'étudiant est en {$label} ({$level}/{$yearsCount} ans). Pas encore en dernière année.", $level, $label);
        return ['eligible' => true, 'reason' => '', 'level_label' => $label, 'cycle_name' => $cycle->libelle ?? $cycle->name ?? '', 'cycle_abbrev' => $cycle->abbreviation ?? ''];
    }

    private function buildEtudiantPassage(AcademicPath $path): object
    {
        $monthsFr     = $this->getMonthsFr();
        $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
        $department   = $path->studentPendingStudent?->pendingStudent?->department;
        $student      = $path->studentPendingStudent?->student;
        $cycle        = $department?->cycle;
        $check        = $this->checkPassageEligibility($path);
        $normalizedLevel = $check['normalized_level'] ?? $this->normalizeLevel($path->study_level);
        $levelLabel      = $check['level_label']      ?? '';
        $nextLevelLabel  = $check['next_level_label'] ?? '';
        return (object) [
            'matricule'         => $student?->student_id_number ?? '',
            'genre'             => $personalInfo?->gender === 'F' ? 'féminin' : 'masculin',
            'civilite'          => $personalInfo?->gender === 'F' ? 'Mme' : 'M.',
            'nom'               => strtoupper($personalInfo?->last_name ?? ''),
            'prenoms'           => $personalInfo?->first_names ?? '',
            'ne_vers'           => 0,
            'date_naissance'    => $this->formatDateFr($personalInfo?->birth_date, $monthsFr),
            'lieu_naissance'    => $personalInfo?->birth_place ?? '',
            'pays_naissance'    => $personalInfo?->birth_country ?? '',
            'annee_passage'     => $levelLabel,
            'annee_superieure'  => $nextLevelLabel,
            'annee_academique'  => $path->academicYear?->libelle ?? $path->academicYear?->academic_year ?? '',
            'deliberation_date' => $this->formatDateFr($path->deliberation_date, $monthsFr),
            'cohort'            => $path->cohort ?? '',
            'filiere'           => (object) [
                'nom'    => $this->nettoyerFiliere($department?->name ?? ''),
                'diplome' => (object) ['nom' => $cycle?->libelle ?? $cycle?->name ?? '', 'sigle' => $cycle?->abbreviation ?? ''],
            ],
        ];
    }

    // =========================================================================
    // ÉLIGIBILITÉ — PASSAGE (depuis proj1)
    // =========================================================================

    public function getEligibleForPassage(
        ?int    $academicYearId = null,
        ?int    $departmentId   = null,
        ?string $cohort         = null,
        ?string $search         = null
    ) {
        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department.cycle',
            'studentPendingStudent.student',
            'academicYear',
        ])->where('year_decision', 'pass')->whereNotNull('deliberation_date');

        if ($academicYearId) $query->where('academic_year_id', $academicYearId);
        if ($departmentId) $query->whereHas('studentPendingStudent.pendingStudent', fn($q) => $q->where('department_id', $departmentId));
        if ($cohort) $query->where('cohort', $cohort);
        if ($search) $query->whereHas('studentPendingStudent.pendingStudent.personalInformation', fn($q) =>
            $q->where('last_name', 'like', "%{$search}%")->orWhere('first_names', 'like', "%{$search}%")
        );

        return $query->orderBy('created_at', 'desc')->get()
            ->filter(fn($path) => $this->checkPassageEligibility($path)['eligible'])
            ->map(function ($path) {
                $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
                $student      = $path->studentPendingStudent?->student;
                $department   = $path->studentPendingStudent?->pendingStudent?->department;
                $cycle        = $department?->cycle;
                $check        = $this->checkPassageEligibility($path);
                return [
                    'id'                         => $path->id,
                    'student_pending_student_id' => $path->student_pending_student_id,
                    'student_id'                 => $student?->student_id_number,
                    'last_name'                  => $personalInfo?->last_name,
                    'first_names'                => $personalInfo?->first_names,
                    'department'                 => $department?->name,
                    'study_level'                => $check['level_label'],
                    'next_level'                 => $check['next_level_label'],
                    'cycle'                      => $cycle?->libelle ?? $cycle?->name,
                    'cohort'                     => $path->cohort,
                    'year_decision'              => $path->year_decision,
                    'academic_year'              => $path->academicYear?->libelle ?? $path->academicYear?->academic_year,
                ];
            })->values();
    }

    // =========================================================================
    // ÉLIGIBILITÉ — DÉFINITIVE (depuis proj1)
    // =========================================================================

    public function getEligibleForDefinitive(
        ?int    $academicYearId = null,
        ?int    $departmentId   = null,
        ?string $cohort         = null,
        ?string $search         = null
    ) {
        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.pendingStudent.department.cycle',
            'studentPendingStudent.student',
            'academicYear',
        ])->where('year_decision', 'pass')->whereNotNull('deliberation_date');

        if ($academicYearId) $query->where('academic_year_id', $academicYearId);
        if ($departmentId) $query->whereHas('studentPendingStudent.pendingStudent', fn($q) => $q->where('department_id', $departmentId));
        if ($cohort) $query->where('cohort', $cohort);
        if ($search) $query->whereHas('studentPendingStudent.pendingStudent.personalInformation', fn($q) =>
            $q->where('last_name', 'like', "%{$search}%")->orWhere('first_names', 'like', "%{$search}%")
        );

        return $query->orderBy('created_at', 'desc')->get()
            ->filter(fn($path) => $this->checkDefinitiveEligibility($path)['eligible'])
            ->map(function ($path) {
                $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
                $student      = $path->studentPendingStudent?->student;
                $department   = $path->studentPendingStudent?->pendingStudent?->department;
                $cycle        = $department?->cycle;
                $check        = $this->checkDefinitiveEligibility($path);
                return [
                    'id'                         => $path->id,
                    'student_pending_student_id' => $path->student_pending_student_id,
                    'student_id'                 => $student?->student_id_number,
                    'last_name'                  => $personalInfo?->last_name,
                    'first_names'                => $personalInfo?->first_names,
                    'department'                 => $department?->name,
                    'study_level'                => $check['level_label'],
                    'cycle'                      => $check['cycle_name'],
                    'cycle_abbrev'               => $check['cycle_abbrev'],
                    'cohort'                     => $path->cohort,
                    'academic_year'              => $path->academicYear?->libelle ?? $path->academicYear?->academic_year,
                ];
            })->values();
    }

    // =========================================================================
    // ÉLIGIBILITÉ — INSCRIPTION (depuis proj1)
    // =========================================================================

    public function getEligibleForInscription(
        ?int    $academicYearId = null,
        ?int    $departmentId   = null,
        ?string $search         = null
    ) {
        $query = StudentPendingStudent::with([
            'pendingStudent.personalInformation',
            'pendingStudent.department.cycle',
            'pendingStudent.academicYear',
            'student',
        ])->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'));

        if ($academicYearId) $query->whereHas('pendingStudent', fn($q) => $q->where('academic_year_id', $academicYearId));
        if ($departmentId)   $query->whereHas('pendingStudent', fn($q) => $q->where('department_id', $departmentId));
        if ($search) $query->whereHas('pendingStudent.personalInformation', fn($q) =>
            $q->where('last_name', 'like', "%{$search}%")->orWhere('first_names', 'like', "%{$search}%")
        );

        return $query->orderBy('id', 'desc')->get()->map(function ($sps) {
            $personalInfo = $sps->pendingStudent?->personalInformation;
            $department   = $sps->pendingStudent?->department;
            $cycle        = $department?->cycle;
            $academicYear = $sps->pendingStudent?->academicYear;
            $level        = $sps->pendingStudent?->level ?? '';
            return [
                'id'                         => $sps->id,
                'student_pending_student_id' => $sps->id,
                'student_id'                 => $sps->student?->student_id_number,
                'last_name'                  => $personalInfo?->last_name,
                'first_names'                => $personalInfo?->first_names,
                'department'                 => $this->nettoyerFiliere($department?->name ?? ''),
                'study_level'                => $this->getLevelLabel(
                                                    $this->normalizeLevel($level),
                                                    $cycle ?? (object)['libelle'=>'','name'=>'','years_count'=>0]
                                                ),
                'cycle'                      => $cycle?->libelle ?? $cycle?->name,
                'academic_year'              => $academicYear?->libelle ?? $academicYear?->academic_year,
            ];
        })->values();
    }

    // =========================================================================
    // GÉNÉRATION — ATTESTATION DE PASSAGE (depuis proj1)
    // =========================================================================

    public function generateAttestationPassage(int $studentPendingStudentId)
    {
        $path  = $this->getLastPassPath($studentPendingStudentId);
        $check = $this->checkPassageEligibility($path);
        if (!$check['eligible']) throw new \Exception($check['reason']);
        $signataire = $this->getSignataire('Directeur', 'Chef CAP');
        return $this->pdfService->downloadPdf('core::pdfs.attestation-passage', [
            'etudiant'   => $this->buildEtudiantPassage($path),
            'signataire' => $signataire,
        ], 'attestation-passage.pdf');
    }

    public function generateMultipleAttestationsPassage(array $studentPendingStudentIds)
    {
        $etudiants = [];
        foreach ($studentPendingStudentIds as $id) {
            try {
                $path  = $this->getLastPassPath($id);
                $check = $this->checkPassageEligibility($path);
                if (!$check['eligible']) continue;
                $etudiants[] = $this->buildEtudiantPassage($path);
            } catch (\Exception $e) { continue; }
        }
        if (empty($etudiants)) throw new \Exception('Aucun étudiant éligible au passage trouvé.');
        $signataire = $this->getSignataire('Directeur', 'Chef CAP');
        return $this->pdfService->downloadPdf('core::pdfs.attestation-passage', [
            'etudiants' => $etudiants, 'signataire' => $signataire, 'multiple' => true,
        ], 'attestations-passage.pdf');
    }

    // =========================================================================
    // GÉNÉRATION — ATTESTATION DÉFINITIVE (depuis proj1)
    // =========================================================================

    public function generateAttestationDefinitive(int $studentPendingStudentId)
    {
        $path  = $this->getLastPassPath($studentPendingStudentId);
        $check = $this->checkDefinitiveEligibility($path);
        if (!$check['eligible']) throw new \Exception($check['reason']);

        $signatairePrincipal = \App\Models\Signataire::whereHas('role', fn($q) =>
            $q->where('slug', 'directeur')->orWhere('name', 'like', '%Directeur%')
        )->first();
        $signataireAdjoint = \App\Models\Signataire::whereHas('role', fn($q) =>
            $q->where('slug', 'chef-cap')->orWhere('name', 'like', '%CAP%')
        )->first();

        $monthsFr     = $this->getMonthsFr();
        $personalInfo = $path->studentPendingStudent?->pendingStudent?->personalInformation;
        $department   = $path->studentPendingStudent?->pendingStudent?->department;
        $student      = $path->studentPendingStudent?->student;
        $cycle        = $department?->cycle;

        $attestation = (object) [
            'nom'               => strtoupper($personalInfo?->last_name ?? ''),
            'prenoms'           => $personalInfo?->first_names ?? '',
            'genre'             => $personalInfo?->gender === 'F' ? 'Féminin' : 'Masculin',
            'date_naissance'    => $this->formatDateFr($personalInfo?->birth_date, $monthsFr),
            'lieu_naissance'    => $personalInfo?->birth_place ?? '',
            'pays_naissance'    => $personalInfo?->birth_country ?? '',
            'matricule'         => $student?->student_id_number ?? '',
            'niveau_diplome'    => $check['level_label'] ?? '',
            'deliberation_date' => $this->formatDateFr($path->deliberation_date, $monthsFr),
            'annee_academique'  => $path->academicYear?->libelle ?? $path->academicYear?->academic_year ?? '',
            'filiere'           => (object) [
                'libelle' => $this->nettoyerFiliere($department?->name ?? ''),
                'diplome' => (object) [
                    'libelle' => $cycle?->libelle ?? $cycle?->name ?? '',
                    'sigle'   => $cycle?->abbreviation ?? '',
                ],
            ],
        ];

        return $this->pdfService->downloadPdf('core::pdfs.attestation-definitive', [
            'attestations'           => [0 => $attestation],
            'vers'                   => [0 => 0],
            'qrCodes'                => [0 => null],
            'signataire'             => (object)['poste' => 'Le Directeur', 'poste_adjoint' => 'Le Chef CAP'],
            'titreSignataire'        => '',
            'nomSignataire'          => $signatairePrincipal?->nom ?? '',
            'titreSignataireAdjoint' => '',
            'nomSignataireAdjoint'   => $signataireAdjoint?->nom ?? '',
        ], 'attestation-definitive.pdf');
    }

    public function generateMultipleAttestationsDefinitive(array $studentPendingStudentIds)
    {
        $paths = [];
        foreach ($studentPendingStudentIds as $id) {
            try {
                $path  = $this->getLastPassPath($id);
                $check = $this->checkDefinitiveEligibility($path);
                if ($check['eligible']) $paths[] = $path;
            } catch (\Exception $e) { continue; }
        }
        if (empty($paths)) throw new \Exception("Aucun étudiant éligible à l'attestation définitive trouvé.");
        // Déléguer à generateAttestationDefinitive pour chaque étudiant et merger
        // Ici on retourne un PDF pour le premier (multi-page géré par le blade)
        return $this->generateAttestationDefinitive($paths[0]->student_pending_student_id);
    }

    // =========================================================================
    // GÉNÉRATION — ATTESTATION D'INSCRIPTION (depuis proj1)
    // =========================================================================

    public function generateAttestationInscription(int $studentPendingStudentId)
    {
        $sps = StudentPendingStudent::with([
            'pendingStudent.personalInformation',
            'pendingStudent.department.cycle',
            'pendingStudent.academicYear',
            'student',
        ])->findOrFail($studentPendingStudentId);

        if ($sps->pendingStudent?->status !== 'approved') {
            throw new \Exception("L'étudiant n'a pas d'inscription approuvée.");
        }

        $monthsFr     = $this->getMonthsFr();
        $personalInfo = $sps->pendingStudent?->personalInformation;
        $department   = $sps->pendingStudent?->department;
        $cycle        = $department?->cycle;
        $academicYear = $sps->pendingStudent?->academicYear;
        $student      = $sps->student;
        $level        = $sps->pendingStudent?->level ?? '';

        $attestation = (object) [
            'matricule'        => $student?->student_id_number ?? '',
            'nom'              => strtoupper($personalInfo?->last_name ?? ''),
            'prenoms'          => $personalInfo?->first_names ?? '',
            'genre'            => $personalInfo?->gender === 'F' ? 'Féminin' : 'Masculin',
            'date_naissance'   => $this->formatDateFr($personalInfo?->birth_date, $monthsFr),
            'lieu_naissance'   => $personalInfo?->birth_place ?? '',
            'pays_naissance'   => $personalInfo?->birth_country ?? '',
            'niveau'           => $this->getLevelLabel(
                                      $this->normalizeLevel($level),
                                      $cycle ?? (object)['libelle'=>'','name'=>'','years_count'=>0]
                                  ),
            'annee_academique' => $academicYear?->libelle ?? $academicYear?->academic_year ?? '',
            'filiere'          => (object) [
                'nom'    => $this->nettoyerFiliere($department?->name ?? ''),
                'diplome' => (object) ['nom' => $cycle?->libelle ?? $cycle?->name ?? '', 'sigle' => $cycle?->abbreviation ?? ''],
            ],
        ];

        $signataire = $this->getSignataire('Directeur', 'Chef CAP');

        return $this->pdfService->downloadPdf('core::pdfs.attestation-inscription', [
            'attestations'           => [0 => $attestation],
            'dateDuJour'             => $this->formatDateFr(now(), $monthsFr),
            'qrCodes'                => [0 => null],
            'signataire'             => $signataire,
            'titreSignataire'        => $signataire->poste,
            'nomSignataire'          => $signataire->nomination,
            'titreSignataireAdjoint' => '',
            'nomSignataireAdjoint'   => '',
        ], 'attestation-inscription.pdf');
    }

    public function generateMultipleAttestationsInscription(array $studentPendingStudentIds)
    {
        $spsList = StudentPendingStudent::with([
            'pendingStudent.personalInformation',
            'pendingStudent.department.cycle',
            'pendingStudent.academicYear',
            'student',
        ])->whereIn('id', $studentPendingStudentIds)
          ->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'))
          ->get();

        if ($spsList->isEmpty()) throw new \Exception("Aucun étudiant éligible à l'attestation d'inscription.");

        $monthsFr     = $this->getMonthsFr();
        $attestations = [];
        foreach ($spsList as $key => $sps) {
            $personalInfo = $sps->pendingStudent?->personalInformation;
            $department   = $sps->pendingStudent?->department;
            $cycle        = $department?->cycle;
            $academicYear = $sps->pendingStudent?->academicYear;
            $student      = $sps->student;
            $level        = $sps->pendingStudent?->level ?? '';
            $attestations[$key] = (object) [
                'matricule'        => $student?->student_id_number ?? '',
                'nom'              => strtoupper($personalInfo?->last_name ?? ''),
                'prenoms'          => $personalInfo?->first_names ?? '',
                'genre'            => $personalInfo?->gender === 'F' ? 'Féminin' : 'Masculin',
                'date_naissance'   => $this->formatDateFr($personalInfo?->birth_date, $monthsFr),
                'lieu_naissance'   => $personalInfo?->birth_place ?? '',
                'pays_naissance'   => $personalInfo?->birth_country ?? '',
                'niveau'           => $this->getLevelLabel(
                                          $this->normalizeLevel($level),
                                          $cycle ?? (object)['libelle'=>'','name'=>'','years_count'=>0]
                                      ),
                'annee_academique' => $academicYear?->libelle ?? $academicYear?->academic_year ?? '',
                'filiere'          => (object) [
                    'nom'    => $this->nettoyerFiliere($department?->name ?? ''),
                    'diplome' => (object) ['nom' => $cycle?->libelle ?? $cycle?->name ?? '', 'sigle' => $cycle?->abbreviation ?? ''],
                ],
            ];
        }

        $signataire = $this->getSignataire('Directeur', 'Chef CAP');
        return $this->pdfService->downloadPdf('core::pdfs.attestation-inscription', [
            'attestations'           => $attestations,
            'dateDuJour'             => $this->formatDateFr(now(), $monthsFr),
            'qrCodes'                => array_fill(0, count($attestations), null),
            'signataire'             => $signataire,
            'titreSignataire'        => $signataire->poste,
            'nomSignataire'          => $signataire->nomination,
            'titreSignataireAdjoint' => '',
            'nomSignataireAdjoint'   => '',
        ], 'attestations-inscription.pdf');
    }
}
