<?php

namespace App\Modules\Inscription\Services;

use App\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

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
            throw new Exception('Identité introuvable');
        }

        // Le matricule est égal au numéro de téléphone enregistré
        $phone = is_array($pi->contacts ?? null) ? ($pi->contacts[0] ?? null) : ($pi->phone ?? null);
        
        if (!$phone) {
            throw new Exception('Aucun numéro de téléphone associé à cette identité');
        }

        $student = Student::where('student_id_number', $phone)->first();
        
        if (!$student) {
            throw new Exception('Matricule non défini pour cette identité');
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
                throw new Exception('Identité introuvable');
            }

            // Vérifier si un étudiant existe déjà avec ce numéro de téléphone
            $existingStudent = Student::where('student_id_number', $data['phone'])->first();
            
            if ($existingStudent) {
                throw new Exception('Un étudiant avec ce matricule existe déjà');
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
