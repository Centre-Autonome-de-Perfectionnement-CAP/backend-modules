<?php

namespace App\Modules\Inscription\Services;

use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Core\Services\PdfService;
use App\Modules\Core\Services\NationalityService;

/**
 * Service de gestion des étudiants
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
                // ✅ AJOUT : personal_information.id pour les appels assign/remove
                'personal_information.id as personal_information_id',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'personal_information.gender as sexe',
                'personal_information.birth_date as dateNaissance',
                // ✅ AJOUT : photo de l'étudiant
                'personal_information.photo',
                'departments.name as filiere',
                'pending_students.level as niveau',
                'academic_years.academic_year as annee',
                'entry_diplomas.name as entryDiploma',
                'pending_students.status as statut',
                'personal_information.email',
                DB::raw(DatabaseAdapter::jsonExtract('personal_information.contacts', '$.phone') . ' as telephone'),
                DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id) as matricule"),
                // ✅ AJOUT : nom du groupe pour le verrouillage côté frontend
                'class_groups.group_name as classGroupName',
                // ✅ AJOUT : role_id depuis personal_information pour détecter le responsable
                'personal_information.role_id'
            )
            ->where('pending_students.status', '!=', 'pending');

        // Filtre année
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            if (is_numeric($filters['year'])) {
                $query->where('academic_years.id', $filters['year']);
            } else {
                $query->where('academic_years.academic_year', $filters['year']);
            }
        }

        // Filtre filière
        if (!empty($filters['filiere']) && $filters['filiere'] !== 'all') {
            if (is_numeric($filters['filiere'])) {
                $query->where('departments.id', $filters['filiere']);
            } else {
                $query->where('departments.name', $filters['filiere']);
            }
        }

        // Filtre diplôme d'entrée
        if (!empty($filters['entry_diploma']) && $filters['entry_diploma'] !== 'all') {
            $query->where('entry_diplomas.name', $filters['entry_diploma']);
        }

        // Filtre niveau
        if (!empty($filters['niveau']) && $filters['niveau'] !== 'all') {
            $query->where('pending_students.level', $filters['niveau']);
        }

        // Filtre cohorte
        if (!empty($filters['cohort']) && $filters['cohort'] !== 'all') {
            $query->whereNotNull('student_pending_student.id')
                  ->whereExists(function ($subQuery) use ($filters) {
                      $subQuery->select(DB::raw(1))
                          ->from('academic_paths')
                          ->whereColumn('academic_paths.student_pending_student_id', 'student_pending_student.id')
                          ->where('academic_paths.cohort', $filters['cohort']);
                  });
        }

        // Filtre redoublant
        if (!empty($filters['redoublant']) && $filters['redoublant'] !== 'all') {
            // Ce filtre est appliqué après la transformation (voir ci-dessous)
        }

        // Recherche texte
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where(
                    DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names'])),
                    'like',
                    "%{$search}%"
                )->orWhere(
                    DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id)"),
                    'like',
                    "%{$search}%"
                );
            });
        }

        $query->orderBy('personal_information.last_name')
              ->orderBy('personal_information.first_names');

        $results = $query->paginate($perPage);

        // Transformation : ajout des champs calculés
        $results->getCollection()->transform(function ($student) use ($filters) {
            // Statut redoublant
            $student->redoublant = (
                $student->niveau &&
                $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)
            ) ? 'Oui' : 'Non';

            // ✅ AJOUT : booléen isClassResponsible (role_id = 9 dans personal_information)
            $student->isClassResponsible = ((int) $student->role_id === 9);

            return $student;
        });

        // Filtre redoublant appliqué après transformation
        if (!empty($filters['redoublant']) && $filters['redoublant'] !== 'all') {
            $filteredItems = $results->getCollection()->filter(function ($student) use ($filters) {
                return $student->redoublant === $filters['redoublant'];
            })->values();
            $results->setCollection($filteredItems);
        }

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
                'personal_information.id as personal_information_id',
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
                'class_groups.group_name as classGroupName',
                'personal_information.role_id'
            )
            ->where('pending_students.id', $id)
            ->first();

        if ($student) {
            $student->redoublant = (
                $student->niveau &&
                $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)
            ) ? 'Oui' : 'Non';

            $student->isClassResponsible = ((int) $student->role_id === 9);
        }

        return $student;
    }

    /**
     * Vérifie si un étudiant est redoublant pour un niveau donné
     */
    public function isRepeatingStudent(?int $studentPendingStudentId, string $level): bool
    {
        if (!$studentPendingStudentId) {
            return false;
        }

        try {
            $count = DB::table('academic_paths')
                ->where('student_pending_student_id', $studentPendingStudentId)
                ->where('study_level', $level)
                ->distinct()
                ->count('academic_year_id');

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
        $students = $this->getAllForExport($filters);

        $academicYear = null;
        $department = null;

        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = DB::table('academic_years')->where('id', $filters['year'])->first();
        }
        if (!empty($filters['filiere']) && is_numeric($filters['filiere'])) {
            $department = DB::table('departments')->where('id', $filters['filiere'])->first();
        }

        $etudiantsEnAttente = $students->map(function ($student) {
            return [
                'matricule' => $student->matricule ?? 'N/A',
                'nom'       => explode(' ', $student->nomPrenoms)[0] ?? '',
                'prenoms'   => implode(' ', array_slice(explode(' ', $student->nomPrenoms), 1)) ?: '',
                'red'       => $student->redoublant === 'Oui',
                'nationalite' => NationalityService::getNationality($student->nationality ?? ''),
            ];
        });

        $classeLabel = ($department && $department->abbreviation
            ? $department->abbreviation
            : ($filters['filiere'] ?? 'N/A')) . '-' . ($filters['niveau'] ?? 'N/A');

        $data = [
            'annee'              => $academicYear ? $academicYear->academic_year : ($filters['year'] ?? 'N/A'),
            'filiere'            => $department ? $department->name : ($filters['filiere'] ?? 'N/A'),
            'classe'             => $classeLabel,
            'etudiantsEnAttente' => $etudiantsEnAttente,
        ];

        $cohort   = $filters['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');
        $filename = 'FICHE_PRESENCE_'
            . ($academicYear ? str_replace(['/', '-'], '_', $academicYear->academic_year) : 'N_A')
            . '_COHORTE_' . $cohort
            . '_' . ($department && $department->abbreviation ? $department->abbreviation : 'N_A')
            . '_' . ($filters['niveau'] ?? 'N_A');
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
        $students = $this->getAllForExport($filters);

        $academicYear = null;
        $department = null;

        if (!empty($filters['year']) && is_numeric($filters['year'])) {
            $academicYear = DB::table('academic_years')->where('id', $filters['year'])->first();
        }
        if (!empty($filters['filiere']) && is_numeric($filters['filiere'])) {
            $department = DB::table('departments')->where('id', $filters['filiere'])->first();
        }

        $etudiants = $students->map(function ($student) {
            return (object) [
                'etudiant' => (object) [
                    'matricule' => $student->matricule ?? 'N/A',
                    'nom'       => explode(' ', $student->nomPrenoms)[0] ?? '',
                    'prenoms'   => implode(' ', array_slice(explode(' ', $student->nomPrenoms), 1)) ?: '',
                    'red'       => $student->redoublant === 'Oui',
                ],
            ];
        });

        $classeLabel = ($department && $department->abbreviation
            ? $department->abbreviation
            : ($filters['filiere'] ?? 'N/A')) . '-' . ($filters['niveau'] ?? 'N/A');

        $data = [
            'annee'    => $academicYear ? $academicYear->academic_year : ($filters['year'] ?? 'N/A'),
            'filiere'  => $department ? $department->name : ($filters['filiere'] ?? 'N/A'),
            'classe'   => $classeLabel,
            'etudiants' => $etudiants,
        ];

        $cohort   = $filters['cohort'] ?? 'all';
        $dateTime = now()->format('Ymd_His');
        $filename = 'FICHE_EMARGEMENT_'
            . ($academicYear ? str_replace(['/', '-'], '_', $academicYear->academic_year) : 'N_A')
            . '_COHORTE_' . $cohort
            . '_' . ($department && $department->abbreviation ? $department->abbreviation : 'N_A')
            . '_' . ($filters['niveau'] ?? 'N_A');
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
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'pending_students.level as niveau',
                'class_groups.group_name as groupe',
                'personal_information.nationality'
            );

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

        $results->transform(function ($student) {
            $student->redoublant = (
                $student->niveau &&
                $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)
            ) ? 'Oui' : 'Non';
            return $student;
        });

        return $results;
    }

    /**
     * Met à jour les informations d'un étudiant
     */
    public function update(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $pendingStudent = DB::table('pending_students')->where('id', $id)->first();

            if (!$pendingStudent) {
                DB::rollBack();
                return null;
            }

            $personalInfoData = [];
            if (isset($data['first_name']))    $personalInfoData['first_names'] = $data['first_name'];
            if (isset($data['last_name']))     $personalInfoData['last_name']   = $data['last_name'];
            if (isset($data['email']))         $personalInfoData['email']       = $data['email'];
            if (isset($data['gender'])) {
                $gender = $data['gender'];
                if (in_array($gender, ['Masculin', 'M'])) $gender = 'M';
                elseif (in_array($gender, ['Féminin', 'F'])) $gender = 'F';
                $personalInfoData['gender'] = $gender;
            }
            if (isset($data['date_of_birth'])) $personalInfoData['birth_date'] = $data['date_of_birth'];

            if (!empty($personalInfoData)) {
                DB::table('personal_information')
                    ->where('id', $pendingStudent->personal_information_id)
                    ->update($personalInfoData);
            }

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
            return $this->getById($id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'étudiant', [
                'id'    => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}