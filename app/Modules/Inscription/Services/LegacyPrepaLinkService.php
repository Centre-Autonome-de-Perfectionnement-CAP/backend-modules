<?php

namespace App\Modules\Inscription\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyPrepaLinkService
{
    /**
     * Recherche les candidats (pending_students) de type Ingénieur Prépa
     * pour l'année académique courante, potentiellement à rattacher à un historique Prépa
     */
    public function searchCandidates(array $filters = [], int $perPage = 10)
    {
        $query = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->leftJoin('student_pending_student', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->leftJoin('entry_diplomas', 'pending_students.entry_diploma_id', '=', 'entry_diplomas.id')
            ->leftJoin('students', 'students.id', '=', 'student_pending_student.student_id')
            ->select(
                'pending_students.id',
                'pending_students.tracking_code',
                'pending_students.status',
                'pending_students.level',
                'pending_students.department_id',
                'pending_students.academic_year_id',
                'student_pending_student.id as student_pending_student_id',
                'student_pending_student.student_id',
                'students.student_id_number as matricule',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']).' as nomPrenoms'),
                'personal_information.last_name',
                'personal_information.first_names',
                'personal_information.email',
                DB::raw(DatabaseAdapter::jsonExtract('personal_information.contacts', 'phone').' as telephone'),
                'personal_information.gender',
                'personal_information.birth_date',
                'departments.name as filiere',
                'departments.abbreviation as dept_abbreviation',
                'departments.cycle_id',
                'academic_years.academic_year as annee',
                'entry_diplomas.name as entryDiploma'
            )
            ->where('pending_students.status', '!=', 'rejected');

        // Filtrer uniquement Ingénieur Prépa (département commençant par P- ou contenant 'prépa'/'prepa')
        $query->where(function ($q) {
            $q->where('departments.abbreviation', 'like', 'P-%')
                ->orWhere('departments.name', 'like', '%prépa%')
                ->orWhere('departments.name', 'like', '%Prépa%')
                ->orWhere('departments.name', 'like', '%PREPA%')
                ->orWhere('departments.name', 'like', '%prepa%');
        });

        // Exclure ceux déjà rattachés à un historique Prépa validé
        $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('academic_paths')
                ->whereColumn('academic_paths.student_pending_student_id', 'student_pending_student.id')
                ->where(function ($w) {
                    $w->where('academic_paths.study_level', 'like', '%prépa%')
                        ->orWhere('academic_paths.study_level', 'like', '%Prépa%')
                        ->orWhere('academic_paths.study_level', 'like', '%PREPA%')
                        ->orWhere('academic_paths.study_level', 'Prépa')
                        ->orWhere('academic_paths.study_level', 'PREPA');
                });
        });

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']).' like ?', ["%{$search}%"])
                    ->orWhere('personal_information.email', 'like', "%{$search}%")
                    ->orWhere('students.student_id_number', 'like', "%{$search}%")
                    ->orWhere('pending_students.tracking_code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['academic_year_id'])) {
            $query->where('pending_students.academic_year_id', $filters['academic_year_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('pending_students.department_id', $filters['department_id']);
        }

        $query->orderBy('pending_students.created_at', 'desc')
            ->orderBy('personal_information.last_name')
            ->orderBy('personal_information.first_names');

        return $query->paginate($perPage);
    }

    /**
     * Vérifie si un pending_student est déjà rattaché à un historique Prépa
     */
    public function checkLinkStatus(int $pendingStudentId)
    {
        $pending = PendingStudent::findOrFail($pendingStudentId);
        $sps = StudentPendingStudent::where('pending_student_id', $pending->id)->first();

        $hasLegacyPrepa = false;
        $legacyPrepaPaths = [];

        if ($sps) {
            $legacyPrepaPaths = AcademicPath::where('student_pending_student_id', $sps->id)
                ->where(function ($q) {
                    $q->where('study_level', 'like', '%prépa%')
                        ->orWhere('study_level', 'like', '%Prépa%')
                        ->orWhere('study_level', 'like', '%PREPA%')
                        ->orWhere('study_level', 'Prépa')
                        ->orWhere('study_level', 'PREPA');
                })
                ->with(['academicYear'])
                ->get();

            $hasLegacyPrepa = $legacyPrepaPaths->isNotEmpty();
        }

        return [
            'has_legacy_prepa' => $hasLegacyPrepa,
            'legacy_prepa_paths' => $legacyPrepaPaths,
        ];
    }

    /**
     * Rattache l'historique Prépa (année antérieure validée) au dossier Prépa courant
     */
    public function linkLegacyPrepa(int $pendingStudentId, array $data)
    {
        return DB::transaction(function () use ($pendingStudentId, $data) {
            $pendingStudent = PendingStudent::with(['personalInformation', 'department'])->findOrFail($pendingStudentId);

            // Vérifier que c'est bien un Prépa
            $dept = $pendingStudent->department;
            $isPrepa = false;
            if ($dept) {
                $isPrepa = str_starts_with($dept->abbreviation ?? '', 'P-')
                    || stripos($dept->name ?? '', 'prépa') !== false
                    || stripos($dept->name ?? '', 'prepa') !== false;
            }
            if (! $isPrepa) {
                throw new BusinessException(
                    message: 'Ce dossier n\'est pas un dossier de Prépa Ingénieur.',
                    errorCode: 'NOT_PREPA'
                );
            }

            $academicYearHistoricalId = $data['academic_year_id'] ?? null;
            if (! $academicYearHistoricalId) {
                throw new BusinessException(message: 'L\'année académique historique est requise.');
            }

            $historicalYear = AcademicYear::find($academicYearHistoricalId);
            if (! $historicalYear) {
                throw new ResourceNotFoundException('Année académique historique introuvable. Elle doit exister dans academic_years.');
            }

            $studyLevel = $data['study_level'] ?? 'Prépa';
            $departmentHistoricalId = $data['department_id'] ?? $pendingStudent->department_id;

            $deptHistorical = Department::find($departmentHistoricalId);
            if (! $deptHistorical) {
                throw new ResourceNotFoundException('Département historique introuvable.');
            }

            $isPrepaHistorical = str_starts_with($deptHistorical->abbreviation ?? '', 'P-')
                || stripos($deptHistorical->name ?? '', 'prépa') !== false
                || stripos($deptHistorical->name ?? '', 'prepa') !== false;

            if (! $isPrepaHistorical) {
                throw new BusinessException(
                    message: 'Le département historique doit être un département de Prépa.',
                    errorCode: 'NOT_PREPA_DEPT'
                );
            }

            $student = null;
            $sps = StudentPendingStudent::where('pending_student_id', $pendingStudent->id)->first();

            if ($sps && $sps->student) {
                $student = $sps->student;
            }

            if (! $student) {
                $studentIdNumber = null;

                if (! empty($data['student_id_number'])) {
                    $provided = trim($data['student_id_number']);
                    $existingByProvided = Student::where('student_id_number', $provided)->first();
                    if ($existingByProvided) {
                        $student = $existingByProvided;
                    } else {
                        $studentIdNumber = $provided;
                    }
                }

                if (! $student && ! $studentIdNumber) {
                    $phone = $this->extractPhone($pendingStudent->personalInformation);
                    $derived = $this->normalizePhoneToMatricule($phone);
                    if ($derived) {
                        $existingByPhone = Student::where('student_id_number', $derived)->first();
                        if ($existingByPhone) {
                            $student = $existingByPhone;
                        } else {
                            $studentIdNumber = $derived;
                        }
                    }
                }

                if (! $student && ! $studentIdNumber) {
                    $studentIdNumber = app(StudentIdService::class)->generateStudentId('STD');
                }

                if (! $student) {
                    $student = Student::create([
                        'student_id_number' => $studentIdNumber,
                        'password' => bcrypt(Str::random(16)),
                    ]);
                }
            }

            if (! $sps) {
                $sps = StudentPendingStudent::create([
                    'student_id' => $student->id,
                    'pending_student_id' => $pendingStudent->id,
                ]);
            } elseif ($sps->student_id !== $student->id) {
                $sps->update(['student_id' => $student->id]);
            }

            $existingPath = AcademicPath::where('student_pending_student_id', $sps->id)
                ->where('academic_year_id', $historicalYear->id)
                ->where(function ($q) use ($studyLevel) {
                    $q->where('study_level', $studyLevel)
                        ->orWhere('study_level', 'like', '%prepa%')
                        ->orWhere('study_level', 'like', '%prépa%');
                })
                ->first();

            if ($existingPath) {
                throw new BusinessException(
                    message: 'Cet historique Prépa est déjà rattaché pour cet étudiant.',
                    errorCode: 'ALREADY_LINKED'
                );
            }

            // L'attestation par l'administrateur de cet historique Prépa validé vaut
            // validation du dossier : on le passe en "approved", condition requise
            // pour l'éligibilité à l'inscription en Spécialité Ingénieur.
            // Cette étape crée le parcours académique de l'année courante du dossier.
            if ($pendingStudent->status !== 'approved') {
                app(PendingStudentService::class)->changeStatus($pendingStudent, 'approved');
                $pendingStudent->refresh();
            }

            // A1 - "déplacer" l'année au lieu d'ajouter une ligne au cursus :
            // on récupère le parcours du dossier (année courante) et on le repositionne
            // sur l'année historique de la Prépa. À défaut (déjà déplacé lors d'un
            // rattachement antérieur), on repart d'un parcours Prépa existant.
            $academicPath = AcademicPath::where('student_pending_student_id', $sps->id)
                ->where('academic_year_id', $pendingStudent->academic_year_id)
                ->first();

            if (! $academicPath) {
                $academicPath = AcademicPath::where('student_pending_student_id', $sps->id)
                    ->where(function ($q) {
                        $q->where('study_level', 'like', '%prepa%')
                            ->orWhere('study_level', 'like', '%prépa%');
                    })
                    ->orderByDesc('id')
                    ->first();
            }

            $pathAttributes = [
                'academic_year_id' => $historicalYear->id,
                'study_level' => $studyLevel,
                'year_decision' => $data['year_decision'] ?? 'pass',
                'deliberation_date' => $data['deliberation_date'] ?? null,
                'role_id' => $data['role_id'] ?? $academicPath?->role_id,
                'financial_status' => $data['financial_status'] ?? $academicPath?->financial_status ?? 'Non exonéré',
                'cohort' => $data['cohort'] ?? $academicPath?->cohort,
            ];

            if ($academicPath) {
                $academicPath->update($pathAttributes);
                $academicPath = $academicPath->fresh();
            } else {
                $academicPath = AcademicPath::create(array_merge($pathAttributes, [
                    'student_pending_student_id' => $sps->id,
                ]));
            }

            return [
                'student' => $student,
                'student_pending_student' => $sps,
                'academic_path' => $academicPath,
                'pending_student' => $pendingStudent,
            ];
        });
    }

    /**
     * Extrait le numéro de téléphone depuis le PersonalInformation
     * (contacts peut être un tableau natif, une chaîne JSON ou une chaîne JSON doublement encodée)
     */
    private function extractPhone($personalInformation): ?string
    {
        if (! $personalInformation) {
            return null;
        }

        $contacts = $personalInformation->contacts ?? null;

        if (is_string($contacts)) {
            $decoded = json_decode($contacts, true);
            if (! is_array($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $contacts = $decoded;
        }

        if (! is_array($contacts)) {
            return null;
        }

        return $contacts['phone']
            ?? $contacts['telephone']
            ?? $contacts['mobile']
            ?? $contacts['tel']
            ?? $contacts[0]
            ?? null;
    }

    /**
     * Normalise un numéro de téléphone en matricule :
     * retire le code pays +229 et le préfixe national 01
     */
    private function normalizePhoneToMatricule(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '229')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '01')) {
            $digits = substr($digits, 2);
        }

        return $digits !== '' ? $digits : null;
    }
}
