<?php

namespace App\Modules\Inscription\Services;

use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Core\Services\PdfService;
use App\Modules\Core\Services\NationalityService;

/**
 * Service de gestion des étudiants
 * 
 * Ce service fournit des méthodes pour gérer les étudiants inscrits,
 * y compris la détection des redoublants basée sur la table academic_paths.
 * 
 * @package App\Modules\Inscription\Services
 */
class StudentService
{
    /**
     * Récupère tous les étudiants avec pagination et filtres
     */
    public function getAll(array $filters = [], int $perPage = 10)
    {
        $query = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->join('student_pending_student', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->leftJoin('entry_diplomas', 'pending_students.entry_diploma_id', '=', 'entry_diplomas.id')
            ->leftJoin('student_groups', 'student_pending_student.student_id', '=', 'student_groups.student_id')
            ->leftJoin('class_groups', function ($join) {
                $join->on('student_groups.class_group_id', '=', 'class_groups.id')
                     ->on('class_groups.academic_year_id', '=', 'pending_students.academic_year_id')
                     ->on('class_groups.department_id', '=', 'pending_students.department_id')
                     ->on('class_groups.study_level', '=', 'pending_students.level');
            })
            ->select(
                'pending_students.id',
                'student_pending_student.id as student_pending_student_id',
                'student_pending_student.student_id',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'personal_information.gender as sexe',
                'personal_information.birth_date as dateNaissance',
                'departments.name as filiere',
                'pending_students.level as niveau',
                'academic_years.academic_year as annee',
                'entry_diplomas.name as entryDiploma',
                'pending_students.status as statut',
                'personal_information.email',
                DB::raw(DatabaseAdapter::jsonExtract('personal_information.contacts', '$.phone') . ' as telephone'),
                DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id) as matricule"),
                'class_groups.group_name as groupe'
            )
            ->where('pending_students.status', '!=', 'pending');

        // Filtres
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            if (is_numeric($filters['year'])) {
                $query->where('academic_years.id', $filters['year']);
            } else {
                $query->where('academic_years.academic_year', $filters['year']);
            }
        }

        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all') {
            if (is_numeric($filters['filiere'])) {
                $query->where('departments.id', $filters['filiere']);
            } else {
                $query->where('departments.name', $filters['filiere']);
            }
        }

        if (!empty($filters['entry_diploma']) && $filters['entry_diploma'] !== 'all') {
            $query->where('entry_diplomas.name', $filters['entry_diploma']);
        }

        if (!empty($filters['niveau']) && $filters['niveau'] !== 'all') {
            $query->where('pending_students.level', $filters['niveau']);
        }
        
        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all') {
            $query->whereNotNull('student_pending_student.id')
                  ->whereExists(function ($subQuery) use ($filters) {
                      $subQuery->select(DB::raw(1))
                          ->from('academic_paths')
                          ->whereColumn('academic_paths.student_pending_student_id', 'student_pending_student.id')
                          ->where('academic_paths.cohort', $filters['cohort']);
                  });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names'])), 'like', "%{$search}%")
                    ->orWhere(DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id)"), 'like', "%{$search}%");
            });
        }

        $query->orderBy('personal_information.last_name')
            ->orderBy('personal_information.first_names');

        $results = $query->paginate($perPage);

        // Ajouter le statut redoublant pour chaque étudiant
        $results->getCollection()->transform(function ($student) {
            $student->redoublant = ($student->niveau && $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)) ? 'Oui' : 'Non';
            return $student;
        });

        return $results;
    }

    /**
     * Récupère les détails d'un étudiant par ID
     */
    public function getById(int $id)
    {
        $student = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->join('student_pending_student', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->leftJoin('entry_diplomas', 'pending_students.entry_diploma_id', '=', 'entry_diplomas.id')
            ->leftJoin('student_groups', 'student_pending_student.student_id', '=', 'student_groups.student_id')
            ->leftJoin('class_groups', function ($join) {
                $join->on('student_groups.class_group_id', '=', 'class_groups.id')
                     ->on('class_groups.academic_year_id', '=', 'pending_students.academic_year_id')
                     ->on('class_groups.department_id', '=', 'pending_students.department_id')
                     ->on('class_groups.study_level', '=', 'pending_students.level');
            })
            ->select(
                'pending_students.id',
                'student_pending_student.id as student_pending_student_id',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'personal_information.gender as sexe',
                'personal_information.birth_date as dateNaissance',
                'personal_information.photo',
                'departments.name as filiere',
                'pending_students.level as niveau',
                'academic_years.academic_year as annee',
                'entry_diplomas.name as entryDiploma',
                'pending_students.status as statut',
                'personal_information.email',
                DB::raw(DatabaseAdapter::jsonExtract('personal_information.contacts', '$.phone') . ' as telephone'),
                DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id) as matricule"),
                'class_groups.group_name as groupe'
            )
            ->where('pending_students.id', $id)
            ->first();

        if ($student) {
            $student->redoublant = ($student->niveau && $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)) ? 'Oui' : 'Non';
        }

        return $student;
    }

    /**
     * Vérifie si un étudiant est redoublant pour un niveau donné
     * 
     * Un étudiant est considéré comme redoublant si dans la table academic_paths,
     * pour un même niveau (study_level), il existe des enregistrements avec des
     * années académiques différentes (academic_year_id).
     * 
     * Cette méthode est réutilisable depuis n'importe quel module du backend.
     * 
     * Exemple d'utilisation depuis un autre module:
     * ```php
     * use App\Modules\Inscription\Services\StudentService;
     * 
     * $studentService = app(StudentService::class);
     * $isRepeating = $studentService->isRepeatingStudent($studentPendingStudentId, 'L1');
     * 
     * if ($isRepeating) {
     *     // Logique pour les redoublants
     * }
     * ```
     * 
     * @param int|null $studentPendingStudentId ID de la relation student_pending_student
     * @param string $level Niveau d'études (L1, L2, L3, M1, M2, etc.)
     * @return bool True si l'étudiant est redoublant, False sinon
     */
    public function isRepeatingStudent(?int $studentPendingStudentId, string $level): bool
    {
        if (!$studentPendingStudentId) {
            return false;
        }

        try {
            // Compter le nombre d'années académiques distinctes pour ce niveau
            $count = DB::table('academic_paths')
                ->where('student_pending_student_id', $studentPendingStudentId)
                ->where('study_level', $level)
                ->distinct()
                ->count('academic_year_id');

            // Si l'étudiant a fait le même niveau sur plus d'une année académique, c'est un redoublant
            return $count > 1;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut redoublant', [
                'student_pending_student_id' => $studentPendingStudentId,
                'level' => $level,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Exporter la fiche de présence en PDF
     */
    public function exportFichePresence(array $filters = [])
    {
        $pdfService = app(PdfService::class);

        // Récupérer tous les étudiants avec les filtres
        $students = $this->getAllForExport($filters);

        // Récupérer les libellés depuis la base de données
        $academicYear = null;
        $department = null;
        
        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = DB::table('academic_years')->where('id', $filters['year'])->first();
        }
        
        if (!empty($filters['filiere']) && is_numeric($filters['filiere'])) {
            $department = DB::table('departments')->where('id', $filters['filiere'])->first();
        }

        // Préparer les données pour la vue
        $etudiantsEnAttente = $students->map(function ($student) {
            return [
                'matricule' => $student->matricule ?? 'N/A',
                'nom' => explode(' ', $student->nomPrenoms)[0] ?? '',
                'prenoms' => implode(' ', array_slice(explode(' ', $student->nomPrenoms), 1)) ?: '',
                'red' => $student->redoublant === 'Oui',
                'nationalite' => NationalityService::getNationality($student->nationality ?? ''),
            ];
        });

        $classeLabel = ($department && $department->abbreviation ? $department->abbreviation : ($filters['filiere'] ?? 'N/A')) . '-' . ($filters['niveau'] ?? 'N/A');

        $data = [
            'annee' => $academicYear ? $academicYear->academic_year : ($filters['year'] ?? 'N/A'),
            'filiere' => $department ? $department->name : ($filters['filiere'] ?? 'N/A'),
            'classe' => $classeLabel,
            'etudiantsEnAttente' => $etudiantsEnAttente,
        ];

        $cohort = $filters['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');
        $filename = 'FICHE_PRESENCE_' . ($academicYear ? str_replace(['/', '-'], '_', $academicYear->academic_year) : 'N_A') . '_COHORTE_' . $cohort . '_' . ($department && $department->abbreviation ? $department->abbreviation : 'N_A') . '_' . ($filters['niveau'] ?? 'N_A');
        if (!empty($filters['groupe'])) {
            $filename .= '_GROUPE_' . $filters['groupe'];
        }
        $filename .= '_' . $dateTime . '.pdf';

        return $pdfService->downloadWithTemplate(
            'liste-presence',
            $data,
            $filename,
            ['orientation' => 'portrait']
        );
    }

    /**
     * Exporter la fiche d'émargement en PDF
     */
    public function exportFicheEmargement(array $filters = [])
    {
        $pdfService = app(PdfService::class);

        // Récupérer tous les étudiants avec les filtres
        $students = $this->getAllForExport($filters);

        // Récupérer les libellés depuis la base de données
        $academicYear = null;
        $department = null;
        
        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = DB::table('academic_years')->where('id', $filters['year'])->first();
        }
        
        if (!empty($filters['filiere']) && is_numeric($filters['filiere'])) {
            $department = DB::table('departments')->where('id', $filters['filiere'])->first();
        }

        // Préparer les données pour la vue (format adapté au template)
        $etudiants = $students->map(function ($student) {
            return (object) [
                'etudiant' => (object) [
                    'matricule' => $student->matricule ?? 'N/A',
                    'nom' => explode(' ', $student->nomPrenoms)[0] ?? '',
                    'prenoms' => implode(' ', array_slice(explode(' ', $student->nomPrenoms), 1)) ?: '',
                    'red' => $student->redoublant === 'Oui',
                ],
            ];
        });

        $classeLabel = ($department && $department->abbreviation ? $department->abbreviation : ($filters['filiere'] ?? 'N/A')) . '-' . ($filters['niveau'] ?? 'N/A');

        $data = [
            'annee' => $academicYear ? $academicYear->academic_year : ($filters['year'] ?? 'N/A'),
            'filiere' => $department ? $department->name : ($filters['filiere'] ?? 'N/A'),
            'classe' => $classeLabel,
            'etudiants' => $etudiants,
        ];

        $cohort = $filters['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');
        $filename = 'FICHE_EMARGEMENT_' . ($academicYear ? str_replace(['/', '-'], '_', $academicYear->academic_year) : 'N_A') . '_COHORTE_' . $cohort . '_' . ($department && $department->abbreviation ? $department->abbreviation : 'N_A') . '_' . ($filters['niveau'] ?? 'N_A');
        if (!empty($filters['groupe'])) {
            $filename .= '_GROUPE_' . $filters['groupe'];
        }
        $filename .= '_' . $dateTime . '.pdf';

        return $pdfService->downloadWithTemplate(
            'liste-emargement',
            $data,
            $filename,
            ['orientation' => 'portrait']
        );
    }

    /**
     * Récupère tous les étudiants pour l'export (sans pagination)
     */
    private function getAllForExport(array $filters = [])
    {
        $query = DB::table('students')
            ->join('student_pending_student', 'students.id', '=', 'student_pending_student.student_id')
            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->leftJoin('student_groups', 'students.id', '=', 'student_groups.student_id')
            ->leftJoin('class_groups', function ($join) {
                $join->on('student_groups.class_group_id', '=', 'class_groups.id')
                     ->on('class_groups.academic_year_id', '=', 'pending_students.academic_year_id')
                     ->on('class_groups.department_id', '=', 'pending_students.department_id')
                     ->on('class_groups.study_level', '=', 'pending_students.level');
            })
            ->select(
                'students.id',
                'student_pending_student.id as student_pending_student_id',
                'students.student_id_number as matricule',
<<<<<<< HEAD
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'pending_students.level as niveau',
                'class_groups.group_name as groupe',
                'personal_information.nationality'
=======
                DB::raw("CONCAT(personal_information.last_name, ' ', personal_information.first_names) as nomPrenoms"),
                'pending_students.level as niveau',
                'class_groups.group_name as groupe'
>>>>>>> eea2b06 (draft)
            );

        // Filtres
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            if (is_numeric($filters['year'])) {
                $query->where('academic_years.id', $filters['year']);
            } else {
                $query->where('academic_years.academic_year', $filters['year']);
            }
        }

        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all') {
            if (is_numeric($filters['filiere'])) {
                $query->where('departments.id', $filters['filiere']);
            } else {
                $query->where('departments.name', $filters['filiere']);
            }
        }

        if (!empty($filters['niveau']) && $filters['niveau'] !== 'all') {
            $query->where('pending_students.level', $filters['niveau']);
        }
        
        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all') {
            $query->whereNotNull('student_pending_student.id')
                  ->whereExists(function ($subQuery) use ($filters) {
                      $subQuery->select(DB::raw(1))
                          ->from('academic_paths')
                          ->whereColumn('academic_paths.student_pending_student_id', 'student_pending_student.id')
                          ->where('academic_paths.cohort', $filters['cohort']);
                  });
        }

        if (!empty($filters['groupe']) && $filters['groupe'] !== 'all') {
            $query->where('class_groups.group_name', $filters['groupe']);
        }

        $query->orderBy('personal_information.last_name')
            ->orderBy('personal_information.first_names');

        $results = collect($query->get());

        // Ajouter le statut redoublant pour chaque étudiant
        $results->transform(function ($student) {
            $student->redoublant = ($student->niveau && $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)) ? 'Oui' : 'Non';
            return $student;
        });

        return $results;
    }

    /**
     * Met à jour les informations d'un étudiant
     * 
     * @param int $id ID du pending_student
     * @param array $data Données à mettre à jour
     * @return object|null Données de l'étudiant mis à jour ou null si échec
     */
    public function update(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            // Récupérer l'étudiant et ses informations personnelles
            $pendingStudent = DB::table('pending_students')
                ->where('id', $id)
                ->first();

            if (!$pendingStudent) {
                DB::rollBack();
                return null;
            }

            // Préparer les données pour la mise à jour de personal_information
            $personalInfoData = [];
            if (isset($data['first_name'])) {
                $personalInfoData['first_names'] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $personalInfoData['last_name'] = $data['last_name'];
            }
            if (isset($data['email'])) {
                $personalInfoData['email'] = $data['email'];
            }
            if (isset($data['gender'])) {
                // Normaliser le genre
                $gender = $data['gender'];
                if (in_array($gender, ['Masculin', 'M'])) {
                    $gender = 'M';
                } elseif (in_array($gender, ['Féminin', 'F'])) {
                    $gender = 'F';
                }
                $personalInfoData['gender'] = $gender;
            }
            if (isset($data['date_of_birth'])) {
                $personalInfoData['birth_date'] = $data['date_of_birth'];
            }

            // Mettre à jour les informations personnelles
            if (!empty($personalInfoData)) {
                DB::table('personal_information')
                    ->where('id', $pendingStudent->personal_information_id)
                    ->update($personalInfoData);
            }

            // Mettre à jour les contacts si le téléphone est fourni
            if (isset($data['phone'])) {
                $personalInfo = DB::table('personal_information')
                    ->where('id', $pendingStudent->personal_information_id)
                    ->first();
                
                $contacts = json_decode($personalInfo->contacts ?? '{}', true);
                $contacts['phone'] = $data['phone'];
                
                DB::table('personal_information')
                    ->where('id', $pendingStudent->personal_information_id)
                    ->update(['contacts' => json_encode($contacts)]);
            }

            DB::commit();

            // Récupérer et retourner les données mises à jour
            return $this->getById($id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'étudiant', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
