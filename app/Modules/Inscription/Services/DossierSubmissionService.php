<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\EntryDiploma;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DossierSubmissionService
{
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
                throw new \Exception('Pas de période de soumission active pour la filière sélectionnée et cette année académique.', 400);
            }

            $department = Department::findOrFail($request->department_id);
            if ($department->cycle?->name !== $cycleName) {
                throw new \Exception("La filière choisie ne fait pas partie du cycle {$cycleName}.", 400);
            }

            if ($request->has('entry_diploma_id')) {
                $entryDiploma = EntryDiploma::findOrFail($request->entry_diploma_id);
                if (!in_array($entryDiploma->name, $validDiplomas)) {
                    throw new \Exception("Diplôme d'entrée invalide pour le cycle de {$cycleName}.", 400);
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
                // Récupère les informations d'identité depuis une inscription antérieure (Prépa)
                $student = \App\Models\User::where('student_id_number', $request->student_id_number)->firstOrFail();
                $studentPendingStudent = StudentPendingStudent::where('student_id', $student->id)
                    ->whereHas('pendingStudent', function ($query) {
                        $query->where('department_id', 1) // Prépa (supposition)
                              ->where('level', '1');
                    })
                    ->firstOrFail();
                $personalInformation = $studentPendingStudent->pendingStudent->personalInformation;
            }

            $year = now()->year;
            $cyclePath = strtolower($cycleName);
            $documents = [];
            foreach ($fileFields as $field => $documentName) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $path = $request->file($field)->store("dossiers/$year/$cyclePath/{$field}", 'public');
                    $documents[$documentName] = $path;
                } elseif (!in_array($field, ['attestation_depot_dossier', 'attestation_anglais', 'diplome_licence'])) {
                    $this->deleteFiles($documents);
                    throw new \Exception("Tentative d'upload échouée {$documentName}.", 400);
                }
            }

            $photoPath = "null";
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoPath = $request->file('photo')->store("dossiers/$year/photos", 'public');
            }

            $pendingStudent = PendingStudent::create([
                'personal_information_id' => $personalInformation->id,
                'tracking_code' => 'CAP-' . Str::random(10),
                'cuca_opinion' => 'pending',
                'cuca_comment' => null,
                'cuo_opinion' => null,
                'rejection_reason' => null,
                'cuco_mail_sent' => false,
                'documents' => json_encode($documents),
                'level' => $request->study_level,
                'entry_diploma_id' => $request->entry_diploma_id ?? null,
                'photo' => $photoPath,
                'academic_year_id' => $request->academic_year_id,
                'department_id' => $request->department_id,
            ]);

            // Envoi d'email optionnel: non implémenté ici pour éviter les dépendances
            try {
                Log::info('Dossier soumis: ' . $pendingStudent->tracking_code);
            } catch (\Exception $e) {
                Log::error('Echec log envoi mail: ' . $e->getMessage());
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
                throw new \Exception('No active submission period for the selected department and academic year.', 400);
            }

            $year = now()->year;
            $cyclePath = strtolower('Complement');

            $names = $validated['names'];
            $files = $validated['files'];
            if (!is_array($files)) { $files = [$files]; }
            if (!is_array($names)) { $names = [$names]; }
            if (count($files) !== count($names)) {
                throw new \Exception('Le nombre de fichiers ne correspond pas au nombre de noms.');
            }

            $documents = [];
            foreach ($files as $index => $file) {
                $name = $names[$index];
                if ($file->isValid()) {
                    $path = $file->store("dossiers/$year/$cyclePath/{$name}", 'public');
                    $documents[$name . "(Complément)"] = $path;
                } else {
                    throw new \Exception('Le fichier ' . $file->getClientOriginalName() . ' est invalide.');
                }
            }

            $pendingStudent->update([
                'documents' => json_encode(array_merge(
                    (array) json_decode($pendingStudent->documents, true),
                    $documents
                )),
            ]);

            return [
                'message' => 'Complément de dossier soumis avec succès.',
                'tracking_code' => $trackingCode,
                'documents_added' => count($documents),
            ];
        });
    }

    public function validateIngenieurSpecialiteEligibility(string $studentIdNumber, int $departmentId): void
    {
        // Vérifications simplifiées pour compatibilité
        $student = \App\Models\User::where('student_id_number', $studentIdNumber)->first();
        if (!$student) {
            throw new \Exception('Etudiant non retrouvé.', 400);
        }

        $existsPrepa = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', function ($query) {
                $query->where('department_id', 1) // Prépa (supposition)
                      ->where('level', '1');
            })
            ->exists();
        if (!$existsPrepa) {
            throw new \Exception('Student has not completed Classes Préparatoires.', 400);
        }

        $department = Department::findOrFail($departmentId);
        if ($department->name === 'Classes Préparatoires') {
            throw new \Exception('La filière choisie n\'est pas valide.', 400);
        }
    }

    private function deleteFiles(array $files): void
    {
        foreach ($files as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Exception $e) {
                Log::error('Echec lors de la suppression du fichier: ' . $path . ' - ' . $e->getMessage());
            }
        }
    }
}
