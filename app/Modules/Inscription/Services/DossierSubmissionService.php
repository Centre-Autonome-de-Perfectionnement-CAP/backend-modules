<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Constants\DocumentFields;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\EntryDiploma;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\FileUploadException;
use App\Modules\Inscription\Mail\DossierSubmissionConfirmation;
use App\Modules\Inscription\Mail\DossierCompletedConfirmation;
use App\Modules\Inscription\Mail\DossierSubmissionWithAttachment;
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Core\Services\PdfService;
use App\Services\StringUtilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DossierSubmissionService
{
    public function __construct(
        private FileStorageService $fileStorageService,
        private PdfService $pdfService,
        private AcademicYearService $academicYearService
    ) {
    }
    public function submitDossier(Request $request, string $cycleName, array $validDiplomas, array $fileFields, bool $isPersonalInfoRequired = true): array
    {
        return DB::transaction(function () use ($request, $cycleName, $validDiplomas, $fileFields, $isPersonalInfoRequired) {
            $now = now();
            $today = $now->toDateString();
            $submissionPeriod = SubmissionPeriod::where('academic_year_id', $request->academic_year_id)
                ->where('department_id', $request->department_id)
                ->where(function ($q) use ($now, $today) {
                    $q->where(function ($sub) use ($now) {
                        $sub->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                    })->orWhere(function ($sub) use ($today) {
                        $sub->whereDate('start_date', '<=', $today)
                            ->whereDate('end_date', '>=', $today);
                    });
                })
                ->first();

            if (!$submissionPeriod) {
                throw new BusinessException(
                    message: "Pas de période de soumission active pour la filière sélectionnée et cette année académique",
                    errorCode: 'NO_ACTIVE_SUBMISSION_PERIOD'
                );
            }

            $department = Department::findOrFail($request->department_id);
            Log::debug('DEBUG', [
                    'department->cycle?->name' => $department->cycle?->name,
                    'cycleName' => $cycleName,
                ]);
            if ($department->cycle?->name !== $cycleName) {
                throw new BusinessException(
                    message: "La filière choisie ne fait pas partie du cycle {$cycleName}",
                    errorCode: 'INVALID_DEPARTMENT_CYCLE'
                );
            }

            // Vérifier les doublons de dossier pour ce MÊME cycle et cette année académique
            if ($isPersonalInfoRequired && !empty($request->email)) {
                $normalizedEmail = trim(mb_strtolower($request->email));
                $isTargetPrepa = str_starts_with($department->abbreviation ?? '', 'P-') || stripos($department->name ?? '', 'prépa') !== false;

                $existingQuery = PendingStudent::where('academic_year_id', $request->academic_year_id)
                    ->whereHas('personalInformation', function ($q) use ($normalizedEmail) {
                        $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
                    })
                    ->where('status', '!=', 'rejected');

                if ($department->cycle_id) {
                    $existingQuery->whereHas('department', function ($dq) use ($department, $isTargetPrepa) {
                        $dq->where('cycle_id', $department->cycle_id);
                        if ($department->cycle_id == 3) {
                            // Cycle Ingénierie : différencier Prépa vs Spécialité
                            if ($isTargetPrepa) {
                                $dq->where(function ($sub) {
                                    $sub->where('abbreviation', 'like', 'P-%')
                                        ->orWhere('name', 'like', '%Prépa%');
                                });
                            } else {
                                $dq->where('abbreviation', 'not like', 'P-%')
                                   ->where('name', 'not like', '%Prépa%');
                            }
                        }
                    });
                }

                $existingDossier = $existingQuery->first();

                if ($existingDossier) {
                    throw new BusinessException(
                        message: "Un dossier de candidature pour ce cycle ({$cycleName}) a déjà été soumis avec cette adresse email ({$request->email}) pour cette année académique. Vous pouvez le transférer vers la vague active ou le mettre à jour.",
                        errorCode: 'DOSSIER_ALREADY_EXISTS',
                        statusCode: 409
                    );
                }
            }

            if ($request->has('entry_diploma_id')) {
                $entryDiploma = EntryDiploma::findOrFail($request->entry_diploma_id);
                if (!in_array($entryDiploma->name, $validDiplomas)) {
                    throw new BusinessException(
                        message: "Diplôme d'entrée invalide pour le cycle de {$cycleName}",
                        errorCode: 'INVALID_ENTRY_DIPLOMA'
                    );
                }
            }

            $personalInformation = null;
            if ($isPersonalInfoRequired) {
                $normalizedEmail = trim(mb_strtolower($request->email));
                $personalInformation = PersonalInformation::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

                if ($personalInformation) {
                    $personalInformation->update([
                        'last_name' => strtoupper(trim($request->last_name)),
                        'first_names' => StringUtilityService::capitalize($request->first_names),
                        'birth_date' => $request->birth_date ?? $personalInformation->birth_date,
                        'birth_place' => $request->birth_place ?? $personalInformation->birth_place,
                        'birth_country' => $request->birth_country ?? $personalInformation->birth_country,
                        'gender' => $request->gender ?? $personalInformation->gender,
                        'contacts' => $request->contacts ?? $personalInformation->contacts,
                    ]);
                } else {
                    $personalInformation = PersonalInformation::create([
                        'last_name' => strtoupper(trim($request->last_name)),
                        'first_names' => StringUtilityService::capitalize($request->first_names),
                        'email' => $normalizedEmail,
                        'birth_date' => $request->birth_date ?? null,
                        'birth_place' => $request->birth_place ?? null,
                        'birth_country' => $request->birth_country ?? 'Bénin',
                        'gender' => $request->gender,
                        'contacts' => $request->contacts, 
                    ]);
                }
            } else {
                $student = Student::where('student_id_number', $request->student_id_number)->firstOrFail();
                $studentPendingStudent = StudentPendingStudent::where('student_id', $student->id)
                    ->with('pendingStudent.personalInformation')
                    ->firstOrFail();
                    
                $personalInformation = $studentPendingStudent->pendingStudent->personalInformation;
            }

            $documents = [];
            foreach ($fileFields as $field => $documentName) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $file = $this->fileStorageService->uploadFile(
                        $request->file($field),
                        null,
                        'public',
                        "dossiers/{$cycleName}"
                    );
                    $documents[$documentName] = $file->id;
                } elseif (!in_array($field, ['attestation_depot_dossier', 'attestation_anglais', 'diplome_licence'])) {
                    throw new FileUploadException(
                        fileName: $documentName,
                        reason: "Le fichier {$documentName} est invalide ou n'a pas pu être téléchargé"
                    );
                }
            }

            $photoPath = null;
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoFile = $this->fileStorageService->uploadFile(
                    $request->file('photo'),
                    null,
                    'public',
                    "dossiers/photos"
                );
                $photoPath = $photoFile->id;
            }

            $initialWave = $this->academicYearService->resolveWave(
                (int) $request->academic_year_id,
                (int) $request->department_id,
                $now
            );

            $pendingStudent = PendingStudent::create([
                'personal_information_id' => $personalInformation->id,
                'tracking_code' => 'CAP-' . Str::random(10),
                'cuca_opinion' => 'pending',
                'cuca_comment' => null,
                'cuo_opinion' => null,
                'cuco_mail_sent' => false,
                'documents' => $documents, 
                'level' => $request->study_level,
                'entry_diploma_id' => $request->entry_diploma_id ?? null,
                'photo' => $photoPath,
                'academic_year_id' => $request->academic_year_id,
                'department_id' => $request->department_id,
                'initial_wave' => $initialWave,
            ]);

            // Génération de la fiche de confirmation et envoi par email
            try {
                $submissionDatetime = now()->format('d/m/Y à H:i');
                $academicYear = AcademicYear::findOrFail($request->academic_year_id);
                
                // Préparer les données pour le PDF
                $pdfData = [
                    'tracking_code' => $pendingStudent->tracking_code,
                    'submission_datetime' => $submissionDatetime,
                    'last_name' => $personalInformation->last_name,
                    'first_names' => $personalInformation->first_names,
                    'email' => $personalInformation->email,
                    'contacts' => $personalInformation->contacts,
                    'birth_date' => $personalInformation->birth_date ? date('d/m/Y', strtotime($personalInformation->birth_date)) : null,
                    'birth_place' => $personalInformation->birth_place,
                    'gender' => $personalInformation->gender,
                    'cycle_name' => $cycleName,
                    'department' => $department->name,
                    'study_level' => $request->study_level,
                    'academic_year' => $academicYear->academic_year,
                    'documents' => array_keys($documents),
                ];

                // Générer le PDF
                $pdfFileName = 'fiche_confirmation_' . $pendingStudent->tracking_code . '.pdf';
                $pdfPath = storage_path('app/temp/' . $pdfFileName);
                
                // Créer le dossier temp s'il n'existe pas
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                $this->pdfService->saveWithTemplate('fiche-confirmation-inscription', $pdfData, $pdfPath);

                // Préparer les données pour l'email
                $mailData = [
                    'department' => $department->name,
                    'academic_year' => $academicYear->academic_year,
                    'tracking_code' => $pendingStudent->tracking_code,
                    'study_level' => $request->study_level,
                    'first_names' => $personalInformation->first_names,
                    'last_name' => $personalInformation->last_name,
                    'email' => $personalInformation->email,
                    'contacts' => $personalInformation->contacts,
                    'cycle_name' => $cycleName,
                    'submission_datetime' => $submissionDatetime,
                ];

                // Envoyer l'email avec la fiche en pièce jointe
                Mail::to($personalInformation->email)->send(new DossierSubmissionWithAttachment($mailData, $pdfPath));

                // Supprimer le fichier temporaire après l'envoi
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }

                Log::info('Fiche de confirmation envoyée avec succès', [
                    'tracking_code' => $pendingStudent->tracking_code,
                    'email' => $personalInformation->email
                ]);
            } catch (\Exception $e) {
                Log::error('Echec lors de la génération/envoi de la fiche de confirmation: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return [
                'message' => 'Dossier soumis avec succès.',
                'tracking_code' => $pendingStudent->tracking_code,
            ];
        });
    }

    public function submitComplementDossier(array $validated, string $trackingCode): array
    {
        return DB::transaction(function () use ($validated, $trackingCode) {
            $now = now();
            $cleanCode = trim($trackingCode);
            $pendingStudent = PendingStudent::where(function ($q) use ($cleanCode) {
                $q->where('tracking_code', $cleanCode)
                  ->orWhereRaw('LOWER(tracking_code) = ?', [strtolower($cleanCode)])
                  ->orWhereRaw('UPPER(tracking_code) = ?', [strtoupper($cleanCode)]);
            })->firstOrFail();

            $submissionPeriod = SubmissionPeriod::where('academic_year_id', $pendingStudent->academic_year_id)
                ->where('department_id', $pendingStudent->department_id)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->first();

            if (!$submissionPeriod) {
                throw new BusinessException(
                    message: 'Aucune période de soumission active pour le département sélectionné et cette année académique',
                    errorCode: 'SUBMISSION_PERIOD_CLOSED'
                );
            }

            $names = $validated['names'];
            $files = $validated['files'];
            if (!is_array($files)) { $files = [$files]; }
            if (!is_array($names)) { $names = [$names]; }
            if (count($files) !== count($names)) {
                throw new BusinessException(
                    message: 'Le nombre de fichiers ne correspond pas au nombre de noms',
                    errorCode: 'FILES_NAMES_MISMATCH'
                );
            }

            $documents = [];
            foreach ($files as $index => $file) {
                $name = $names[$index];
                if ($file->isValid()) {
                    $uploadedFile = $this->fileStorageService->uploadFile(
                        $file,
                        null,
                        'public',
                        "dossiers/complements"
                    );
                    $documents[$name . "(Complément)"] = $uploadedFile->id;
                } else {
                    throw new FileUploadException(
                        fileName: $file->getClientOriginalName(),
                        reason: 'Le fichier est invalide'
                    );
                }
            }

            $existingDocuments = $pendingStudent->documents ?? [];
            $mergedDocuments = array_merge((array) $existingDocuments, $documents);
            
            $pendingStudent->update([
                'documents' => $mergedDocuments, 
            ]);
            $department = Department::findOrFail($pendingStudent->department_id);
            $personalInformation = $pendingStudent->personalInformation;

            try {
                $mailData = [
                    'department' => $department->name,
                    'academic_year' => AcademicYear::findOrFail($pendingStudent->academic_year_id)->academic_year,
                    'tracking_code' => $pendingStudent->tracking_code,
                    'study_level' => $pendingStudent->study_level,
                    'first_names' => $personalInformation->first_names,
                ];
                Mail::to($personalInformation->email)->send(new DossierCompletedConfirmation($mailData));
            } catch (\Exception $e) {
                Log::error('Failed to send confirmation email: ' . $e->getMessage());
            }

            return [
                'message' => 'Complément de dossier soumis avec succès.',
                'tracking_code' => $trackingCode,
                'documents_added' => count($documents),
            ];
        });
    }

    public function validateIngenieurSpecialiteEligibility(string $studentIdNumber, int $departmentId): void
    {
        // Rechercher l'étudiant par son matricule
        $student = Student::where('student_id_number', $studentIdNumber)->first();
        if (!$student) {
            throw new ResourceNotFoundException('Étudiant non retrouvé avec ce matricule');
        }

        // Vérifier que l'étudiant a un dossier Prépa validé
        // Les départements Prépa commencent par "P-"
        $existsPrepa = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', function ($query) {
                $query->whereHas('department', function ($deptQuery) {
                    $deptQuery->where('abbreviation', 'LIKE', 'P-%');
                })
                ->where('status', 'approved');
            })
            ->exists();
            
        if (!$existsPrepa) {
            throw new BusinessException(
                message: 'Vous devez avoir complété et validé les Classes Préparatoires pour vous inscrire en Spécialité',
                errorCode: 'PREPARATORY_NOT_COMPLETED'
            );
        }

        // Vérifier que le département choisi n'est pas une Prépa
        $department = Department::findOrFail($departmentId);
        if (str_starts_with($department->abbreviation ?? '', 'P-')) {
            throw new BusinessException(
                message: 'Vous ne pouvez pas vous inscrire en Prépa pour la Spécialité. Choisissez un département de Spécialité (GC, GT, GE, GME).',
                errorCode: 'INVALID_DEPARTMENT'
            );
        }
    }

    public function getDossierByTrackingCode(string $trackingCode): array
    {
        $cleanCode = trim($trackingCode);
        $pendingStudent = PendingStudent::with([
            'personalInformation',
            'department.cycle',
            'academicYear',
            'entryDiploma',
            'studentPendingStudents.student.pendingStudents.personalInformation',
            'studentPendingStudents.academicPaths'
        ])
        ->where(function ($q) use ($cleanCode) {
            $q->where('tracking_code', $cleanCode)
              ->orWhereRaw('LOWER(tracking_code) = ?', [strtolower($cleanCode)])
              ->orWhereRaw('UPPER(tracking_code) = ?', [strtoupper($cleanCode)]);
        })
        ->first();

        if (!$pendingStudent) {
            throw new ResourceNotFoundException('Dossier non trouvé');
        }

        $resolvedDocuments = [];
        if (!empty($pendingStudent->documents) && is_array($pendingStudent->documents)) {
            foreach ($pendingStudent->documents as $name => $val) {
                $fileId = is_numeric($val) ? (int) $val : (is_array($val) ? ($val['id'] ?? null) : null);
                $file = $fileId ? \App\Modules\Stockage\Models\File::find($fileId) : null;

                if ($file) {
                    // URL publique lisible par le candidat sans authentification
                    $publicUrl = route('api.files.inscription.view', ['file' => $file->id]);
                    $resolvedDocuments[$name] = [
                        'id'            => $file->id,
                        'name'          => $name,
                        'original_name' => $file->original_name,
                        'url'           => $publicUrl,
                        'mime_type'     => $file->mime_type,
                        'size'          => $file->size,
                    ];
                } elseif (is_string($val) && !is_numeric($val)) {
                    $resolvedDocuments[$name] = [
                        'name' => $name,
                        'path' => $val,
                        'url'  => str_starts_with($val, 'http') ? $val : url('/storage/' . ltrim($val, '/')),
                    ];
                }
            }
        }

        $photoUrl = null;
        if (!empty($pendingStudent->photo)) {
            $photoFile = is_numeric($pendingStudent->photo) ? \App\Modules\Stockage\Models\File::find((int) $pendingStudent->photo) : null;
            if ($photoFile) {
                $photoUrl = url('/storage/files/' . ltrim($photoFile->path, '/'));
            } elseif (is_string($pendingStudent->photo)) {
                $photoUrl = str_starts_with($pendingStudent->photo, 'http') ? $pendingStudent->photo : url('/storage/' . ltrim($pendingStudent->photo, '/'));
            }
        }

        $dossierArray = $pendingStudent->toArray();
        $dossierArray['documents'] = $resolvedDocuments;
        $dossierArray['photo_url'] = $photoUrl;

        return [
            'dossier' => $dossierArray,
        ];
    }

    /**
     * Vérifie si un candidat a déjà un dossier pour l'année académique.
     */
    /**
     * Vérifie si un candidat a déjà un dossier en attente pour l'année académique,
     * et détecte s'il s'agit d'un même cycle (report/transfert) ou d'une progression de cycle (Licence -> Master, Prépa -> Spécialité).
     */
    public function checkExistingPendingDossier(string $email, ?int $academicYearId = null, ?string $targetCycle = null): ?array
    {
        $normalizedEmail = trim(mb_strtolower($email));
        $now = now();
        
        $baseQuery = PendingStudent::whereHas('personalInformation', function ($q) use ($normalizedEmail) {
            $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
        })
        ->with(['personalInformation', 'department.cycle', 'academicYear', 'entryDiploma'])
        ->latest();

        $targetYear = null;
        if ($academicYearId) {
            $targetYear = AcademicYear::find($academicYearId);
        } else {
            // Priorité : is_current > période de soumission active aujourd'hui > année calendaire active > dernière année
            $targetYear = AcademicYear::where('is_current', true)->first();
            if (!$targetYear) {
                $targetYear = AcademicYear::where('submission_start', '<=', $now)
                    ->where('submission_end', '>=', $now)
                    ->first();
            }
            if (!$targetYear) {
                $targetYear = AcademicYear::where('year_start', '<=', $now)
                    ->where('year_end', '>=', $now)
                    ->first();
            }
            if (!$targetYear) {
                $targetYear = AcademicYear::latest('id')->first();
            }
        }

        // Identifier le cycle cible demandé
        $targetCycleNorm = strtolower(trim($targetCycle ?? ''));
        $targetCycleKey = null;
        $targetCycleLabel = null;
        if (str_contains($targetCycleNorm, 'master')) {
            $targetCycleKey = 'master';
            $targetCycleLabel = 'Master';
        } elseif (str_contains($targetCycleNorm, 'prepa')) {
            $targetCycleKey = 'ingenieur_prepa';
            $targetCycleLabel = 'Prépa Ingénieur';
        } elseif (str_contains($targetCycleNorm, 'specialite') || str_contains($targetCycleNorm, 'specialité') || str_contains($targetCycleNorm, 'ing')) {
            $targetCycleKey = 'ingenieur_specialite';
            $targetCycleLabel = 'Cycle Ingénieur Spécialité';
        } elseif (str_contains($targetCycleNorm, 'licence')) {
            $targetCycleKey = 'licence';
            $targetCycleLabel = 'Licence Professionnelle';
        }

        // Helper pour qualifier le cycle d'un dossier
        $getCycleInfo = function ($ps) {
            if (!$ps || !$ps->department) return ['key' => 'licence', 'label' => 'Licence Professionnelle'];
            $dept = $ps->department;
            $isPrepa = str_starts_with($dept->abbreviation ?? '', 'P-') || stripos($dept->name ?? '', 'prépa') !== false;
            if ($isPrepa) {
                return ['key' => 'ingenieur_prepa', 'label' => 'Prépa Ingénieur'];
            }
            $cycleName = $dept->cycle?->name ?? '';
            if ($cycleName === 'Master') {
                return ['key' => 'master', 'label' => 'Master'];
            }
            if ($cycleName === 'Ingénierie') {
                return ['key' => 'ingenieur_specialite', 'label' => 'Cycle Ingénieur Spécialité'];
            }
            return ['key' => 'licence', 'label' => 'Licence Professionnelle'];
        };

        // 1. Chercher d'abord un dossier correspondant au MÊME cycle cible dans l'année active
        $pendingStudent = null;
        $allDossiers = (clone $baseQuery)->get();
        if ($allDossiers->isEmpty()) {
            return null;
        }

        if ($targetCycleKey) {
            // Priorité A : Même cycle dans l'année active
            if ($targetYear) {
                $pendingStudent = $allDossiers->first(function ($d) use ($targetYear, $targetCycleKey, $getCycleInfo) {
                    return (int)$d->academic_year_id === (int)$targetYear->id && $getCycleInfo($d)['key'] === $targetCycleKey;
                });
            }
            // Priorité B : Même cycle dans une année passée (report de dossier possible)
            if (!$pendingStudent) {
                $pendingStudent = $allDossiers->first(function ($d) use ($targetCycleKey, $getCycleInfo) {
                    return $getCycleInfo($d)['key'] === $targetCycleKey;
                });
            }
        }

        // Priorité C : Si rien trouvé pour le cycle cible, récupérer le tout dernier dossier (progression de cycle)
        if (!$pendingStudent) {
            $pendingStudent = $allDossiers->first();
        }

        if (!$pendingStudent) {
            return null;
        }

        $existingCycleInfo = $getCycleInfo($pendingStudent);
        $existingCycleKey = $existingCycleInfo['key'];
        $existingCycleLabel = $existingCycleInfo['label'];

        $isSameCycle = true;
        $isCycleProgression = false;
        if ($targetCycleKey) {
            $isSameCycle = ($targetCycleKey === $existingCycleKey);
            $isCycleProgression = !$isSameCycle;
        }

        $isValidated = (
            $pendingStudent->status === 'approved' ||
            $pendingStudent->cuca_opinion === 'favorable' ||
            $pendingStudent->cuo_opinion === 'favorable' ||
            $pendingStudent->studentPendingStudents()->exists()
        );

        $isRejected = ($pendingStudent->status === 'rejected');
        $canEdit = !$isValidated;

        $targetYearId = $targetYear ? (int) $targetYear->id : (int) $pendingStudent->academic_year_id;
        $academicYearService = app(AcademicYearService::class);
        $currentActiveWave = $academicYearService->resolveWave(
            $targetYearId,
            (int) $pendingStudent->department_id,
            $now
        );

        $initialWave = (int) ($pendingStudent->initial_wave ?? 1);
        $isOlderAcademicYear = ($targetYear && (int)$pendingStudent->academic_year_id !== (int)$targetYear->id);

        // Le transfert de vague / report est proposé quand c'est le même cycle et qu'une vague ou une année est active
        $canTransferWave = $isSameCycle && ($currentActiveWave !== $initialWave || $isOlderAcademicYear);
        $canTransferToCurrentYear = $isSameCycle && $isOlderAcademicYear;

        // Données pour le pré-remplissage fluide lors d'une progression de cycle
        $rawContacts = $pendingStudent->personalInformation?->contacts;
        $contactsList = [];
        $phone = null;
        $address = null;

        if (is_string($rawContacts)) {
            $decoded = json_decode($rawContacts, true);
            $rawContacts = is_array($decoded) ? $decoded : [$rawContacts];
        }

        if (is_array($rawContacts)) {
            if (isset($rawContacts['phone'])) {
                $phone = (string)$rawContacts['phone'];
                $address = $rawContacts['address'] ?? null;
                $contactsList = [$phone];
            } else {
                foreach ($rawContacts as $c) {
                    if (is_string($c) && trim($c) !== '') {
                        $contactsList[] = trim($c);
                    }
                }
                $phone = $contactsList[0] ?? null;
            }
        }

        $formattedBirthDate = null;
        if ($pendingStudent->personalInformation?->birth_date) {
            $bd = $pendingStudent->personalInformation->birth_date;
            $formattedBirthDate = $bd instanceof \DateTimeInterface
                ? $bd->format('Y-m-d')
                : date('Y-m-d', strtotime((string)$bd));
        }

        $prefillData = [
            'last_name' => $pendingStudent->personalInformation?->last_name,
            'first_names' => $pendingStudent->personalInformation?->first_names,
            'email' => $pendingStudent->personalInformation?->email,
            'birth_date' => $formattedBirthDate,
            'birth_place' => $pendingStudent->personalInformation?->birth_place,
            'birth_country' => $pendingStudent->personalInformation?->birth_country ?? 'Bénin',
            'gender' => $pendingStudent->personalInformation?->gender,
            'nationality' => $pendingStudent->personalInformation?->nationality,
            'phone' => $phone,
            'contacts' => !empty($contactsList) ? $contactsList : ($phone ? [$phone] : ['']),
            'address' => $address,
        ];

        return [
            'exists' => true,
            'id' => $pendingStudent->id,
            'tracking_code' => $pendingStudent->tracking_code,
            'first_names' => $pendingStudent->personalInformation?->first_names,
            'last_name' => $pendingStudent->personalInformation?->last_name,
            'email' => $pendingStudent->personalInformation?->email,
            'cycle' => $pendingStudent->department?->cycle?->name,
            'department_id' => $pendingStudent->department_id,
            'department_name' => $pendingStudent->department?->name,
            'study_level' => $pendingStudent->level,
            'entry_diploma_id' => $pendingStudent->entry_diploma_id,
            'academic_year_id' => $pendingStudent->academic_year_id,
            'academic_year' => $pendingStudent->academicYear?->academic_year,
            'initial_wave' => $initialWave,
            'current_active_wave' => $currentActiveWave,
            'can_transfer_wave' => $canTransferWave,
            'can_transfer_to_current_year' => $canTransferToCurrentYear,
            'is_older_year' => $isOlderAcademicYear,
            'target_academic_year' => $targetYear?->academic_year,
            'target_academic_year_id' => $targetYear?->id,
            'is_same_cycle' => $isSameCycle,
            'is_cycle_progression' => $isCycleProgression,
            'existing_cycle_key' => $existingCycleKey,
            'existing_cycle_label' => $existingCycleLabel,
            'target_cycle_key' => $targetCycleKey,
            'target_cycle_label' => $targetCycleLabel,
            'prefill_data' => $prefillData,
            'transferred_from_wave' => $pendingStudent->transferred_from_wave,
            'transfer_history' => $pendingStudent->transfer_history ?? [],
            'status' => $pendingStudent->status,
            'is_validated' => $isValidated,
            'is_rejected' => $isRejected,
            'can_edit' => $canEdit,
            'is_updated_by_student' => (bool) ($pendingStudent->is_updated_by_student ?? false),
            'submitted_at' => $pendingStudent->created_at?->toISOString(),
            'last_student_update_at' => $pendingStudent->last_student_update_at?->toISOString(),
        ];
    }

    /**
     * Récupère les données d'un dossier pour pré-remplir le formulaire de modification.
     */
    public function getDossierForUpdate(string $email, ?string $trackingCode = null): array
    {
        $normalizedEmail = trim(mb_strtolower($email));
        
        $query = PendingStudent::whereHas('personalInformation', function ($q) use ($normalizedEmail) {
            $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
        })
        ->with(['personalInformation', 'department.cycle', 'academicYear', 'entryDiploma'])
        ->latest();

        if (!empty($trackingCode)) {
            $cleanCode = trim($trackingCode);
            $query->where(function ($q) use ($cleanCode) {
                $q->where('tracking_code', $cleanCode)
                  ->orWhereRaw('LOWER(tracking_code) = ?', [strtolower($cleanCode)])
                  ->orWhereRaw('UPPER(tracking_code) = ?', [strtoupper($cleanCode)]);
            });
        }

        $pendingStudent = $query->first();

        if (!$pendingStudent) {
            throw new ResourceNotFoundException('Dossier introuvable.');
        }

        $isValidated = (
            $pendingStudent->status === 'approved' ||
            $pendingStudent->cuca_opinion === 'favorable' ||
            $pendingStudent->cuo_opinion === 'favorable' ||
            $pendingStudent->studentPendingStudents()->exists()
        );

        if ($isValidated) {
            throw new BusinessException('Ce dossier a déjà été validé et ne peut plus être modifié.', 'DOSSIER_ALREADY_VALIDATED');
        }

        $contacts = $pendingStudent->personalInformation?->contacts;
        if (is_string($contacts)) {
            $contacts = json_decode($contacts, true) ?: [$contacts];
        }

        $allFields = DocumentFields::allFields();
        $resolvedDocuments = [];
        $documentsByField = [];

        if (!empty($pendingStudent->documents) && is_array($pendingStudent->documents)) {
            foreach ($pendingStudent->documents as $name => $val) {
                $fileId = is_numeric($val) ? (int) $val : (is_array($val) ? ($val['id'] ?? null) : null);
                $file = $fileId ? \App\Modules\Stockage\Models\File::find($fileId) : null;

                $docInfo = null;
                if ($file) {
                    $publicUrl = route('api.files.inscription.view', ['file' => $file->id]);
                    $docInfo = [
                        'id'            => $file->id,
                        'name'          => $name,
                        'original_name' => $file->original_name,
                        'url'           => $publicUrl,
                        'mime_type'     => $file->mime_type,
                        'size'          => $file->size,
                    ];
                } elseif (is_string($val) && !is_numeric($val)) {
                    $docInfo = [
                        'name' => $name,
                        'path' => $val,
                        'url'  => str_starts_with($val, 'http') ? $val : url('/storage/' . ltrim($val, '/')),
                    ];
                }

                if ($docInfo) {
                    $resolvedDocuments[$name] = $docInfo;

                    // Associer au champ correspondant du formulaire (ex: demande_da, cv, etc.)
                    foreach ($allFields as $fieldKey => $docLabel) {
                        if ($docLabel === $name || $fieldKey === $name) {
                            $documentsByField[$fieldKey] = $docInfo;
                        }
                    }
                }
            }
        }

        $photoUrl = null;
        if (!empty($pendingStudent->photo)) {
            $photoFile = is_numeric($pendingStudent->photo) ? \App\Modules\Stockage\Models\File::find((int) $pendingStudent->photo) : null;
            if ($photoFile) {
                $photoUrl = route('api.files.inscription.view', ['file' => $photoFile->id]);
            } elseif (is_string($pendingStudent->photo)) {
                $photoUrl = str_starts_with($pendingStudent->photo, 'http') ? $pendingStudent->photo : url('/storage/' . ltrim($pendingStudent->photo, '/'));
            }
        }

        return [
            'tracking_code' => $pendingStudent->tracking_code,
            'initial_wave' => (int) ($pendingStudent->initial_wave ?? 1),
            'first_names' => $pendingStudent->personalInformation?->first_names,
            'last_name' => $pendingStudent->personalInformation?->last_name,
            'email' => $pendingStudent->personalInformation?->email,
            'birth_date' => $pendingStudent->personalInformation?->birth_date ? date('Y-m-d', strtotime($pendingStudent->personalInformation->birth_date)) : '',
            'birth_place' => $pendingStudent->personalInformation?->birth_place,
            'birth_country' => $pendingStudent->personalInformation?->birth_country,
            'gender' => $pendingStudent->personalInformation?->gender,
            'contacts' => is_array($contacts) ? $contacts : [''],
            'cycle_name' => $pendingStudent->department?->cycle?->name,
            'department_id' => $pendingStudent->department_id,
            'academic_year_id' => $pendingStudent->academic_year_id,
            'study_level' => $pendingStudent->level,
            'entry_diploma_id' => $pendingStudent->entry_diploma_id,
            'documents' => $resolvedDocuments,
            'documents_by_field' => $documentsByField,
            'has_photo' => !empty($pendingStudent->photo),
            'photo_url' => $photoUrl,
        ];
    }

    /**
     * Met à jour le dossier existant d'un candidat en conservant sa Vague 1 d'origine.
     */
    public function updateExistingDossier(Request $request, array $fileFields): array
    {
        return DB::transaction(function () use ($request, $fileFields) {
            $trackingCode = trim((string) $request->input('tracking_code', ''));
            $email = trim(mb_strtolower((string) $request->input('email', '')));

            $query = PendingStudent::whereHas('personalInformation', function ($q) use ($email) {
                $q->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->with(['personalInformation', 'department.cycle', 'academicYear', 'entryDiploma'])
            ->latest();

            if (!empty($trackingCode)) {
                $query->where(function ($q) use ($trackingCode) {
                    $q->where('tracking_code', $trackingCode)
                      ->orWhereRaw('LOWER(tracking_code) = ?', [strtolower($trackingCode)])
                      ->orWhereRaw('UPPER(tracking_code) = ?', [strtoupper($trackingCode)]);
                });
            }

            $pendingStudent = $query->first();

            if (!$pendingStudent) {
                throw new BusinessException(
                    message: "Dossier introuvable ou vous n'avez pas l'autorisation de le modifier.",
                    errorCode: 'DOSSIER_NOT_FOUND'
                );
            }

            // Vérification du jeton OTP si présent
            $verificationToken = $request->input('verification_token', $request->input('_verificationToken'));
            if (!empty($verificationToken)) {
                $otpService = app(\App\Modules\Core\Services\OtpService::class);
                if (!$otpService->validateToken((string)$verificationToken, $email)) {
                    throw new BusinessException(
                        message: "Le jeton de vérification est invalide ou a expiré.",
                        errorCode: 'INVALID_VERIFICATION_TOKEN',
                        statusCode: 403
                    );
                }
            }

            $isValidated = (
                $pendingStudent->status === 'approved' ||
                $pendingStudent->cuca_opinion === 'favorable' ||
                $pendingStudent->cuo_opinion === 'favorable' ||
                $pendingStudent->studentPendingStudents()->exists()
            );

            if ($isValidated) {
                throw new BusinessException('Ce dossier a déjà été validé et ne peut plus être modifié.', 'DOSSIER_ALREADY_VALIDATED');
            }

            $modifications = [];

            // 1. Mise à jour de l'état civil et des contacts
            $personalInfo = $pendingStudent->personalInformation;
            if ($personalInfo) {
                $piUpdated = false;

                if ($request->filled('last_name') && strtoupper(trim($request->last_name)) !== $personalInfo->last_name) {
                    $personalInfo->last_name = strtoupper(trim($request->last_name));
                    $modifications[] = 'Nom : ' . $personalInfo->last_name;
                    $piUpdated = true;
                }

                if ($request->filled('first_names')) {
                    $capFirstNames = StringUtilityService::capitalize($request->first_names);
                    if ($capFirstNames !== $personalInfo->first_names) {
                        $personalInfo->first_names = $capFirstNames;
                        $modifications[] = 'Prénoms : ' . $capFirstNames;
                        $piUpdated = true;
                    }
                }

                if ($request->filled('birth_date') && $request->birth_date !== $personalInfo->birth_date) {
                    $personalInfo->birth_date = $request->birth_date;
                    $modifications[] = 'Date de naissance';
                    $piUpdated = true;
                }

                if ($request->filled('birth_place') && $request->birth_place !== $personalInfo->birth_place) {
                    $personalInfo->birth_place = $request->birth_place;
                    $modifications[] = 'Lieu de naissance';
                    $piUpdated = true;
                }

                if ($request->filled('birth_country') && $request->birth_country !== $personalInfo->birth_country) {
                    $personalInfo->birth_country = $request->birth_country;
                    $modifications[] = 'Pays de naissance';
                    $piUpdated = true;
                }

                if ($request->filled('gender') && $request->gender !== $personalInfo->gender) {
                    $personalInfo->gender = $request->gender;
                    $modifications[] = 'Genre';
                    $piUpdated = true;
                }

                if ($request->has('contacts')) {
                    $newContacts = $request->input('contacts');
                    if (is_array($newContacts)) {
                        $cleaned = array_values(array_filter($newContacts, fn($c) => !empty(trim((string)$c))));
                        if (!empty($cleaned) && $cleaned !== $personalInfo->contacts) {
                            $personalInfo->contacts = $cleaned;
                            $modifications[] = 'Contacts / Téléphone';
                            $piUpdated = true;
                        }
                    }
                }

                if ($piUpdated) {
                    $personalInfo->save();
                }
            }

            // 2. Mise à jour filière / niveau
            if ($request->has('department_id') && (int)$request->department_id !== (int)$pendingStudent->department_id) {
                $newDept = Department::findOrFail($request->department_id);
                $oldDeptName = $pendingStudent->department?->name;
                $pendingStudent->department_id = $newDept->id;
                $modifications[] = "Filière : {$oldDeptName} → {$newDept->name}";
            }

            if ($request->has('study_level') && (string)$request->study_level !== (string)$pendingStudent->level) {
                $pendingStudent->level = $request->study_level;
                $modifications[] = "Niveau d'étude : {$request->study_level}";
            }

            if ($request->has('entry_diploma_id') && (int)$request->entry_diploma_id !== (int)$pendingStudent->entry_diploma_id) {
                $pendingStudent->entry_diploma_id = $request->entry_diploma_id;
                $modifications[] = "Diplôme d'entrée";
            }

            // 3. Remplacement des fichiers / pièces jointes
            $documents = $pendingStudent->documents ?? [];
            $cycleName = $pendingStudent->department?->cycle?->name;
            $cycleFields = DocumentFields::forCycle($cycleName);
            $effectiveFields = !empty($fileFields) ? $fileFields : $cycleFields;

            foreach ($effectiveFields as $field => $documentName) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $file = $this->fileStorageService->uploadFile(
                        $request->file($field),
                        null,
                        'public',
                        "dossiers/updates"
                    );

                    $canonicalName = $cycleFields[$field] ?? $documentName;

                    // Supprimer les alias précédents pour éviter toute duplication
                    foreach ([$documentName, $canonicalName, $field] as $k) {
                        unset($documents[$k]);
                    }

                    $documents[$canonicalName] = $file->id;
                    $modifications[] = "Document : {$canonicalName}";
                }
            }
            $pendingStudent->documents = $documents;

            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoFile = $this->fileStorageService->uploadFile(
                    $request->file('photo'),
                    null,
                    'public',
                    "dossiers/photos"
                );
                $pendingStudent->photo = $photoFile->id;
                $modifications[] = "Photo d'identité";
            }

            // 4. Gestion du transfert vers la vague actuelle si demandé
            if ($request->boolean('transfer_to_current_wave') || $request->input('transfer_to_current_wave') === '1') {
                $academicYearService = app(AcademicYearService::class);
                $currentWave = $academicYearService->resolveWave(
                    (int) $pendingStudent->academic_year_id,
                    (int) $pendingStudent->department_id,
                    now()
                );

                $fromWave = (int) ($pendingStudent->initial_wave ?? 1);
                if ($currentWave !== $fromWave) {
                    $history = $pendingStudent->transfer_history ?? [];
                    $history[] = [
                        'from_wave' => $fromWave,
                        'to_wave' => $currentWave,
                        'transferred_at' => now()->toDateTimeString(),
                        'transferred_by' => 'Candidat (Mise à jour portail)',
                        'reason' => "Transfert automatique lors de la mise à jour du dossier en Vague {$currentWave}",
                    ];

                    $pendingStudent->transferred_from_wave = $pendingStudent->transferred_from_wave ?? $fromWave;
                    $pendingStudent->initial_wave = $currentWave;
                    $pendingStudent->transfer_history = $history;
                    $modifications[] = "Transfert de vague : Vague {$fromWave} → Vague {$currentWave}";
                }
            }

            // 5. Marquer le dossier comme mis à jour par le candidat
            $existingSummary = $pendingStudent->student_update_summary ?? [];
            if (!is_array($existingSummary)) {
                $existingSummary = [];
            }
            $updateEntry = [
                'updated_at' => now()->toISOString(),
                'changes' => !empty($modifications) ? $modifications : ['Mise à jour générale'],
            ];
            $existingSummary[] = $updateEntry;

            $pendingStudent->is_updated_by_student = true;
            $pendingStudent->last_student_update_at = now();
            $pendingStudent->student_update_summary = $existingSummary;
            $pendingStudent->save();

            // 5. Régénérer la fiche PDF mise à jour et envoyer l'email de confirmation
            try {
                $submissionDatetime = now()->format('d/m/Y à H:i');
                $academicYear = $pendingStudent->academicYear ?? AcademicYear::find($pendingStudent->academic_year_id);
                $department = $pendingStudent->department ?? Department::find($pendingStudent->department_id);
                $cycleName = $department?->cycle?->name ?? 'Licence';

                $pdfData = [
                    'tracking_code' => $pendingStudent->tracking_code,
                    'submission_datetime' => $submissionDatetime . ' (Mise à jour)',
                    'last_name' => $personalInfo->last_name,
                    'first_names' => $personalInfo->first_names,
                    'email' => $personalInfo->email,
                    'contacts' => $personalInfo->contacts,
                    'birth_date' => $personalInfo->birth_date ? date('d/m/Y', strtotime($personalInfo->birth_date)) : null,
                    'birth_place' => $personalInfo->birth_place,
                    'gender' => $personalInfo->gender,
                    'cycle_name' => $cycleName,
                    'department' => $department?->name,
                    'study_level' => $pendingStudent->level,
                    'academic_year' => $academicYear?->academic_year,
                    'documents' => array_keys($documents),
                ];

                $pdfFileName = 'fiche_confirmation_' . $pendingStudent->tracking_code . '.pdf';
                $pdfPath = storage_path('app/temp/' . $pdfFileName);
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                $this->pdfService->saveWithTemplate('fiche-confirmation-inscription', $pdfData, $pdfPath);

                $mailData = [
                    'department' => $department?->name ?? 'EPAC',
                    'academic_year' => $academicYear?->academic_year ?? '',
                    'tracking_code' => $pendingStudent->tracking_code,
                    'study_level' => $pendingStudent->level,
                    'first_names' => $personalInfo->first_names,
                    'last_name' => $personalInfo->last_name,
                    'email' => $personalInfo->email,
                    'contacts' => $personalInfo->contacts,
                    'cycle_name' => $cycleName,
                    'submission_datetime' => $submissionDatetime . ' (Mise à jour)',
                ];

                if (filter_var($personalInfo->email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($personalInfo->email)->send(
                        new DossierSubmissionWithAttachment($mailData, $pdfPath)
                    );
                }

                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            } catch (\Throwable $e) {
                Log::error('Erreur lors de la génération PDF ou envoi email de mise à jour: ' . $e->getMessage());
                if (isset($pdfPath) && file_exists($pdfPath)) {
                    @unlink($pdfPath);
                }
            }

            return [
                'success' => true,
                'message' => 'Votre dossier a été mis à jour avec succès.',
                'tracking_code' => $pendingStudent->tracking_code,
                'initial_wave' => (int) ($pendingStudent->initial_wave ?? 1),
                'modifications' => $modifications,
            ];
        });
    }

    /**
     * Permet au candidat de transférer directement son dossier existant vers la vague active
     * sans avoir besoin de modifier ni re-saisir les informations.
     */
    public function transferDossierWaveByCandidate(string $email, string $trackingCode): array
    {
        return DB::transaction(function () use ($email, $trackingCode) {
            $normalizedEmail = trim(mb_strtolower($email));
            $cleanCode = trim($trackingCode);

            $pendingStudent = PendingStudent::whereHas('personalInformation', function ($q) use ($normalizedEmail) {
                $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
            })
            ->where(function ($q) use ($cleanCode) {
                $q->where('tracking_code', $cleanCode)
                  ->orWhereRaw('LOWER(tracking_code) = ?', [strtolower($cleanCode)])
                  ->orWhereRaw('UPPER(tracking_code) = ?', [strtoupper($cleanCode)]);
            })
            ->with(['personalInformation', 'department.cycle', 'academicYear', 'entryDiploma'])
            ->first();

            if (!$pendingStudent) {
                throw new BusinessException("Dossier introuvable.", 'DOSSIER_NOT_FOUND', 404);
            }

            $isValidated = (
                $pendingStudent->status === 'approved' ||
                $pendingStudent->cuca_opinion === 'favorable' ||
                $pendingStudent->cuo_opinion === 'favorable' ||
                $pendingStudent->studentPendingStudents()->exists()
            );

            // Si le dossier est validé, on autorise le transfert vers la nouvelle vague sans lever d'exception

            $targetYear = AcademicYear::where('is_current', true)->first();
            if (!$targetYear) {
                $targetYear = AcademicYear::where('submission_start', '<=', now())
                    ->where('submission_end', '>=', now())
                    ->first();
            }
            if (!$targetYear) {
                $targetYear = AcademicYear::latest('id')->first();
            }

            $targetYearId = $targetYear ? (int)$targetYear->id : (int)$pendingStudent->academic_year_id;
            $fromYearId = (int)$pendingStudent->academic_year_id;
            $isYearChanged = ($targetYear && $fromYearId !== $targetYearId);

            $academicYearService = app(AcademicYearService::class);
            $currentWave = $academicYearService->resolveWave(
                $targetYearId,
                (int) $pendingStudent->department_id,
                now()
            );

            $fromWave = (int) ($pendingStudent->initial_wave ?? 1);

            if (!$isYearChanged && $currentWave === $fromWave) {
                return [
                    'success' => true,
                    'already_in_wave' => true,
                    'tracking_code' => $pendingStudent->tracking_code,
                    'current_wave' => $currentWave,
                    'academic_year' => $pendingStudent->academicYear?->academic_year,
                    'message' => "Votre dossier est déjà assigné à la Vague {$currentWave}.",
                ];
            }

            // Enregistrer l'historique du transfert
            $history = $pendingStudent->transfer_history ?? [];
            if (!is_array($history)) {
                $history = [];
            }
            $reasonText = $isYearChanged
                ? "Report de candidature vers l'année active ({$targetYear?->academic_year}) Vague {$currentWave}"
                : "Transfert direct vers la Vague {$currentWave} via le portail candidat";

            $history[] = [
                'from_wave' => $fromWave,
                'to_wave' => $currentWave,
                'from_academic_year_id' => $fromYearId,
                'to_academic_year_id' => $targetYearId,
                'transferred_at' => now()->toDateTimeString(),
                'transferred_by' => 'Candidat (Portail)',
                'reason' => $reasonText,
            ];

            $pendingStudent->transferred_from_wave = $pendingStudent->transferred_from_wave ?? $fromWave;
            $pendingStudent->initial_wave = $currentWave;
            if ($isYearChanged) {
                $pendingStudent->academic_year_id = $targetYearId;
            }
            $pendingStudent->transfer_history = $history;
            $pendingStudent->is_updated_by_student = true;
            $pendingStudent->last_student_update_at = now();

            $existingSummary = $pendingStudent->student_update_summary ?? [];
            if (!is_array($existingSummary)) {
                $existingSummary = [];
            }
            $changeMsg = $isYearChanged
                ? "Report vers l'année {$targetYear?->academic_year} (Vague {$currentWave})"
                : "Transfert direct de vague : Vague {$fromWave} → Vague {$currentWave}";
            $existingSummary[] = [
                'updated_at' => now()->toISOString(),
                'changes' => [$changeMsg],
            ];
            $pendingStudent->student_update_summary = $existingSummary;
            $pendingStudent->save();

            Log::info("Dossier transféré directement par le candidat", [
                'tracking_code' => $pendingStudent->tracking_code,
                'from_wave' => $fromWave,
                'to_wave' => $currentWave,
                'from_year' => $fromYearId,
                'to_year' => $targetYearId,
            ]);

            $validatedNotice = $isValidated 
                ? " (Votre dossier est déjà validé par la scolarité et cette validation reste acquise en Vague {$currentWave})."
                : "";
            $yearNotice = $isYearChanged
                ? " et reporté vers l'année académique {$targetYear?->academic_year}"
                : "";

            return [
                'success' => true,
                'already_in_wave' => false,
                'tracking_code' => $pendingStudent->tracking_code,
                'from_wave' => $fromWave,
                'new_wave' => $currentWave,
                'academic_year' => $targetYear?->academic_year ?? $pendingStudent->academicYear?->academic_year,
                'is_validated' => $isValidated,
                'message' => "Votre dossier a été transféré avec succès vers la Vague {$currentWave}{$yearNotice}.{$validatedNotice}",
            ];
        });
    }

    /**
     * Récupère les périodes de soumission actives pour un cycle donné.
     */
    public function getActiveSubmissionPeriods(string $cycleName): array
    {
        $now = now();
        return SubmissionPeriod::whereHas('department.cycle', function ($q) use ($cycleName) {
                $q->where('name', $cycleName);
            })
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->with(['department', 'academicYear'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'department' => $p->department?->name,
                    'department_id' => $p->department_id,
                    'academic_year' => $p->academicYear?->academic_year,
                    'academic_year_id' => $p->academic_year_id,
                    'start_date' => $p->start_date ? $p->start_date->format('Y-m-d H:i:s') : null,
                    'end_date' => $p->end_date ? $p->end_date->format('Y-m-d H:i:s') : null,
                ];
            })
            ->toArray();
    }
}

