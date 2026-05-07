<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class StudentIdService
{
    /**
     * Rechercher un étudiant par son identité
     */
    public function lookupByIdentity(array $data): ?string
    {
        $pi = PersonalInformation::query()
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($data['last_name'])])
            ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($data['first_names'])])
            ->whereDate('birth_date', $data['birth_date'])
            ->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($data['birth_place'])])
            ->first();

        if (!$pi) {
            throw new BusinessException(
                message: 'Aucune identité trouvée avec ces informations. Veuillez vérifier vos données (nom, prénoms, date et lieu de naissance).',
                errorCode: 'IDENTITY_NOT_FOUND',
                statusCode: 404
            );
        }

        // Rechercher le Student via les PendingStudents liés à ce PersonalInformation
        $pendingStudent = $pi->pendingStudents()->first();
        
        if (!$pendingStudent) {
            throw new BusinessException(
                message: 'Aucun dossier trouvé. Assurez-vous d\'avoir soumis au moins une candidature.',
                errorCode: 'NO_PENDING_STUDENT',
                statusCode: 404
            );
        }

        // Récupérer le Student via la table pivot student_pending_student
        $studentPendingStudent = $pendingStudent->studentPendingStudents()->first();
        
        if (!$studentPendingStudent) {
            throw new BusinessException(
                message: 'Votre matricule n\'a pas encore été attribué. Utilisez l\'onglet "Obtenir un matricule" pour en créer un.',
                errorCode: 'STUDENT_ID_NOT_ASSIGNED'
            );
        }

        $student = $studentPendingStudent->student;
        
        if (!$student) {
            throw new BusinessException(
                message: 'Votre matricule n\'a pas encore été attribué. Utilisez l\'onglet "Obtenir un matricule" pour en créer un.',
                errorCode: 'STUDENT_ID_NOT_ASSIGNED'
            );
        }

        Log::info('Matricule recherché', [
            'student_id' => $student->id,
            'student_id_number' => $student->student_id_number,
        ]);

        return $student->student_id_number;
    }

    /**
     * Assigner un matricule à un étudiant
     */
    public function assignStudentId(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // Rechercher l'identité
            $pi = PersonalInformation::query()
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($data['last_name'])])
                ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($data['first_names'])])
                ->whereDate('birth_date', $data['birth_date'])
                ->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($data['birth_place'])])
                ->first();

            if (!$pi) {
                throw new BusinessException(
                    message: 'Aucune identité trouvée avec ces informations. Assurez-vous d\'avoir déjà soumis une candidature au CAP.',
                    errorCode: 'IDENTITY_NOT_FOUND',
                    statusCode: 404
                );
            }

            // Vérifier si un matricule existe déjà pour ce numéro de téléphone
            $existingStudent = Student::where('student_id_number', $data['phone'])->first();
            
            if ($existingStudent) {
                throw new BusinessException(
                    message: 'Ce numéro de téléphone est déjà utilisé comme matricule. Utilisez l\'onglet "Consulter mon matricule" pour le retrouver.',
                    errorCode: 'STUDENT_ID_ALREADY_EXISTS',
                    statusCode: 409
                );
            }

            // Mettre à jour le PersonalInformation avec le téléphone
            $contacts = is_array($pi->contacts) ? $pi->contacts : [];
            if (!in_array($data['phone'], $contacts)) {
                $contacts[] = $data['phone'];
                $pi->update(['contacts' => $contacts]);
            }

            // Créer l'étudiant
            $student = Student::create([
                'student_id_number' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password'] ?? 'default123'),
            ]);

            Log::info('Matricule assigné', [
                'student_id' => $student->id,
                'student_id_number' => $student->student_id_number,
                'personal_information_id' => $pi->id,
            ]);

            return $student;
        });
    }

    /**
     * Générer un matricule unique
     */
    public function generateStudentId(string $prefix = 'STD'): string
    {
        do {
            $year = date('Y');
            $random = str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $studentId = "{$prefix}{$year}{$random}";
        } while (Student::where('student_id_number', $studentId)->exists());

        return $studentId;
    }

    /**
     * Valider un matricule
     */
    public function validateStudentId(string $studentId): bool
    {
        // Vérifier le format et l'existence
        if (strlen($studentId) < 8) {
            return false;
        }

        return Student::where('student_id_number', $studentId)->exists();
    }

    /**
     * Mettre à jour le mot de passe d'un étudiant
     */
    public function updatePassword(Student $student, string $newPassword): Student
    {
        $student->update([
            'password' => Hash::make($newPassword),
        ]);

        Log::info('Mot de passe étudiant mis à jour', [
            'student_id' => $student->id,
        ]);

        return $student->fresh();
    }
}
