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
use App\Modules\Stockage\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DossierSubmissionService
{
    public function __construct(private FileStorageService $fileStorageService)
    {
    }
    public function submitDossier(Request $request, string $cycleName, array $validDiplomas, array $fileFields, bool $isPersonalInfoRequired = true): array
    {
        return DB::transaction(function () use ($request, $cycleName, $validDiplomas, $fileFields, $isPersonalInfoRequired) {
            $currentDate = now()->toDateString();
            $submissionPeriod = SubmissionPeriod::where('academic_year_id', $request->academic_year_id)
                ->where('department_id', $request->department_id)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            if (!$submissionPeriod) {
                throw new BusinessException(
                    message: 'Pas de période de soumission active pour la filière sélectionnée et cette année académique',
                    errorCode: 'SUBMISSION_PERIOD_CLOSED'
                );
            }

            $department = Department::findOrFail($request->department_id);
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
                // Log pour debug
                Log::info('Creating PersonalInformation', [
                    'birth_date' => $request->birth_date,
                    'birth_place' => $request->birth_place,
                    'birth_country' => $request->birth_country,
                    'all_data' => $request->all()
                ]);

                $personalInformation = PersonalInformation::create([
                    'last_name' => $request->last_name,
                    'first_names' => $request->first_names,
                    'email' => $request->email,
                    'birth_date' => $request->birth_date ?? null,
                    'birth_place' => $request->birth_place ?? null,
                    'birth_country' => $request->birth_country ?? 'Bénin',
                    'gender' => $request->gender,
                    'contacts' => $request->contacts, // Le cast 'array' dans le modèle gère la conversion JSON
                ]);
            } else {
                // Récupère les informations d'identité depuis une inscription antérieure
                $student = Student::where('student_id_number', $request->student_id_number)->firstOrFail();
                
                // Récupérer le premier dossier (pending_student) de l'étudiant pour obtenir les infos personnelles
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
                        "dossiers/{$cycleName}",
                        $documentName
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
                    "dossiers/photos",
                    "Photo - {$personalInformation->first_names} {$personalInformation->last_name}"
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
                'documents' => $documents, // Le cast 'array' encode automatiquement en JSON
                'level' => $request->study_level,
                'entry_diploma_id' => $request->entry_diploma_id ?? null,
                'photo' => $photoPath,
                'academic_year_id' => $request->academic_year_id,
                'department_id' => $request->department_id,
            ]);

            // Envoi d'email optionnel: non implémenté ici pour éviter les dépendances
            try {
                $mailData = [
                    'department' => $department->name,
                    'academic_year' => AcademicYear::findOrFail($request->academic_year_id)->academic_year,
                    'tracking_code' => $pendingStudent->tracking_code,
                    'study_level' => $request->study_level,
                    'first_names' => $personalInformation->first_names,
                    'email' => $personalInformation->email,
                    'contacts' => $personalInformation->contacts, // Déjà un array grâce au cast
                    'cycle_name' => $cycleName,
                ];
                Mail::to($personalInformation->email)->send(new DossierSubmissionConfirmation($mailData));
            } catch (\Exception $e) {
                Log::error('Echec lors de l\'envoi du mail de confirmation: ' . $e->getMessage());
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
            $currentDate = now()->toDateString();
            $pendingStudent = PendingStudent::where('tracking_code', $trackingCode)->firstOrFail();

            $submissionPeriod = SubmissionPeriod::where('academic_year_id', $pendingStudent->academic_year_id)
                ->where('department_id', $pendingStudent->department_id)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
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
                        "dossiers/complements",
                        $name . " (Complément)"
                    );
                    $documents[$name . "(Complément)"] = $uploadedFile->id;
                } else {
                    throw new FileUploadException(
                        fileName: $file->getClientOriginalName(),
                        reason: 'Le fichier est invalide'
                    );
                }
            }

            // Fusionner les anciens documents avec les nouveaux
            $existingDocuments = $pendingStudent->documents ?? [];
            $mergedDocuments = array_merge((array) $existingDocuments, $documents);
            
            $pendingStudent->update([
                'documents' => $mergedDocuments, // Le cast 'array' encode automatiquement
            ]);

            // Récupérer les infos nécessaires pour l'email
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


}
