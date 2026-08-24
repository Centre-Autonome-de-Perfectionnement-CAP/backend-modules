<?php

namespace App\Modules\Inscription\Services;

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
        private PdfService $pdfService
    ) {
    }
    public function submitDossier(Request $request, string $cycleName, array $validDiplomas, array $fileFields, bool $isPersonalInfoRequired = true): array
    {
        return DB::transaction(function () use ($request, $cycleName, $validDiplomas, $fileFields, $isPersonalInfoRequired) {
            $now = now();
            $submissionPeriod = SubmissionPeriod::where('academic_year_id', $request->academic_year_id)
                ->where('department_id', $request->department_id)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->first();

            if (!$submissionPeriod) {
                throw new BusinessException(
                    message: 'Pas de période de soumission active pour la filière sélectionnée et cette année académique',
                    errorCode: 'SUBMISSION_PERIOD_CLOSED'
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
                Log::info('Creating PersonalInformation', [
                    'birth_date' => $request->birth_date,
                    'birth_place' => $request->birth_place,
                    'birth_country' => $request->birth_country,
                    'all_data' => $request->all()
                ]);

                $personalInformation = PersonalInformation::create([
                    'last_name' => strtoupper(trim($request->last_name)),
                    'first_names' => StringUtilityService::capitalize($request->first_names),
                    'email' => $request->email,
                    'birth_date' => $request->birth_date ?? null,
                    'birth_place' => $request->birth_place ?? null,
                    'birth_country' => $request->birth_country ?? 'Bénin',
                    'gender' => $request->gender,
                    'contacts' => $request->contacts, 
                ]);
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

            $pendingStudent = PendingStudent::create([
                'personal_information_id' => $personalInformation->id,
                'tracking_code' => 'CAP-' . Str::random(10),
                'cuca_opinion' => 'pending',
                'cuca_comment' => null,
                'cuo_opinion' => null,
                'rejection_reason' => null,
                'cuco_mail_sent' => false,
                'documents' => $documents, 
                'level' => $request->study_level,
                'entry_diploma_id' => $request->entry_diploma_id ?? null,
                'photo' => $photoPath,
                'academic_year_id' => $request->academic_year_id,
                'department_id' => $request->department_id,
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
            $pendingStudent = PendingStudent::where('tracking_code', $trackingCode)->firstOrFail();

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
        $pendingStudent = PendingStudent::with([
            'personalInformation',
            'department.cycle',
            'academicYear',
            'entryDiploma',
            'studentPendingStudents.student.pendingStudents.personalInformation',
            'studentPendingStudents.academicPaths'
        ])
        ->where('tracking_code', strtoupper($trackingCode))
        ->first();

        if (!$pendingStudent) {
            throw new ResourceNotFoundException('Dossier non trouvé');
        }

        return [
            'dossier' => $pendingStudent,
        ];
    }

    /**
     * Vérifie si un candidat a déjà un dossier pour l'année académique.
     */
    public function checkExistingPendingDossier(string $email, ?int $academicYearId = null): ?array
    {
        $normalizedEmail = trim(mb_strtolower($email));
        
        $query = PendingStudent::whereHas('personalInformation', function ($q) use ($normalizedEmail) {
            $q->whereRaw('LOWER(email) = ?', [$normalizedEmail]);
        })
        ->with(['personalInformation', 'department.cycle', 'academicYear', 'entryDiploma'])
        ->latest();

        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            if (!$currentYear) {
                $now = now();
                $currentYear = AcademicYear::where('year_start', '<=', $now)->where('year_end', '>=', $now)->first();
            }
            if ($currentYear) {
                $academicYearId = $currentYear->id;
            }
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $pendingStudent = $query->first();

        if (!$pendingStudent) {
            return null;
        }

        $isValidated = (
            $pendingStudent->status === 'validated' ||
            $pendingStudent->cuca_opinion === 'favorable' ||
            $pendingStudent->cuo_opinion === 'favorable' ||
            $pendingStudent->studentPendingStudents()->exists()
        );

        $isRejected = ($pendingStudent->status === 'rejected');
        $canEdit = !$isValidated && !$isRejected;

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
            'initial_wave' => (int) ($pendingStudent->initial_wave ?? 1),
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
            $pendingStudent->status === 'validated' ||
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
            'documents' => $pendingStudent->documents ?? [],
            'has_photo' => !empty($pendingStudent->photo),
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

            $isValidated = (
                $pendingStudent->status === 'validated' ||
                $pendingStudent->cuca_opinion === 'favorable' ||
                $pendingStudent->cuo_opinion === 'favorable' ||
                $pendingStudent->studentPendingStudents()->exists()
            );

            if ($isValidated) {
                throw new BusinessException('Ce dossier a déjà été validé et ne peut plus être modifié.', 'DOSSIER_ALREADY_VALIDATED');
            }

            $modifications = [];

            // 1. Mise à jour des contacts
            $personalInfo = $pendingStudent->personalInformation;
            if ($request->has('contacts')) {
                $newContacts = $request->input('contacts');
                if (is_array($newContacts)) {
                    $cleaned = array_values(array_filter($newContacts, fn($c) => !empty(trim((string)$c))));
                    if (!empty($cleaned)) {
                        $personalInfo->contacts = $cleaned;
                        $personalInfo->save();
                        $modifications[] = 'Contacts / Téléphone';
                    }
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
            foreach ($fileFields as $field => $documentName) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $file = $this->fileStorageService->uploadFile(
                        $request->file($field),
                        null,
                        'public',
                        "dossiers/updates"
                    );
                    $documents[$documentName] = $file->id;
                    $modifications[] = "Document : {$documentName}";
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

            // 4. Marquer le dossier comme mis à jour par le candidat (initial_wave reste inchangée)
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

                if (filter_var($personalInfo->email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($personalInfo->email)->queue(
                        new DossierSubmissionWithAttachment($personalInfo, $pendingStudent, $pdfPath)
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Erreur lors de la génération PDF ou envoi email de mise à jour: ' . $e->getMessage());
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
}
