<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PendingStudentService
{
    /**
     * Récupérer tous les étudiants en attente avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = PendingStudent::query()->with([
            'entryDiploma',
            'personalInformation',
            'department',
            'academicYear'
        ]);

        // Filtre par département
        if (!empty($filters['department_id']) && is_numeric($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Filtre par année académique
        if (!empty($filters['academic_year_id']) && is_numeric($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        // Filtre par diplôme d'entrée
        if (!empty($filters['entry_diploma_id']) && is_numeric($filters['entry_diploma_id'])) {
            $query->where('entry_diploma_id', $filters['entry_diploma_id']);
        }

        // Filtre par niveau
        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        // Filtre par cohorte
        if (!empty($filters['cohort']) && !empty($filters['academic_year_id'])) {
            // Récupérer les périodes de soumission pour cette année académique
            $periods = \DB::table('submission_periods')
                ->where('academic_year_id', $filters['academic_year_id'])
                ->select('start_date', 'end_date')
                ->groupBy('start_date', 'end_date')
                ->orderBy('start_date')
                ->get();
            
            // Trouver la période correspondant à la cohorte demandée
            $cohortIndex = (int)$filters['cohort'] - 1;
            if (isset($periods[$cohortIndex])) {
                $period = $periods[$cohortIndex];
                $query->whereDate('pending_students.created_at', '>=', $period->start_date)
                      ->whereDate('pending_students.created_at', '<=', $period->end_date);
            }
        }

        // Recherche
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('tracking_code', 'like', "%{$search}%")
                  ->orWhereHas('personalInformation', function ($subQuery) use ($search) {
                      $subQuery->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Créer un étudiant en attente
     */
    public function create(array $data): PendingStudent
    {
        return DB::transaction(function () use ($data) {
            // Si personal_information_id n'est pas fourni, créer d'abord PersonalInformation
            if (!isset($data['personal_information_id'])) {
                $personalInfo = \App\Modules\Inscription\Models\PersonalInformation::create([
                    'first_names' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'contacts' => isset($data['phone']) ? ['phone' => $data['phone']] : null,
                    'entry_diploma_id' => $data['entry_diploma_id'] ?? null,
                ]);
                $data['personal_information_id'] = $personalInfo->id;
            }

            $pendingStudent = PendingStudent::create([
                'personal_information_id' => $data['personal_information_id'],
                'tracking_code' => $this->generateTrackingCode(),
                'department_id' => $data['department_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'level' => $data['level'] ?? null,
                'entry_diploma_id' => $data['entry_diploma_id'],
                'status' => 'pending',
                'documents' => $data['documents'] ?? [],
            ]);

            Log::info('Étudiant en attente créé', [
                'pending_student_id' => $pendingStudent->id,
                'tracking_code' => $pendingStudent->tracking_code,
            ]);

            return $pendingStudent->load('personalInformation', 'entryDiploma');
        });
    }

    /**
     * Générer un code de suivi unique
     */
    private function generateTrackingCode(): string
    {
        do {
            $code = 'CAP-' . strtoupper(Str::random(8));
        } while (PendingStudent::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Récupérer un étudiant en attente par ID
     */
    public function getById(int $id): ?PendingStudent
    {
        return PendingStudent::with([
            'entryDiploma',
            'personalInformation',
            'department',
            'academicYear',
            'studentPendingStudents.student',
            'files'
        ])->find($id);
    }

    /**
     * Mettre à jour un étudiant en attente
     */
    public function update(PendingStudent $pendingStudent, array $data): PendingStudent
    {
        return DB::transaction(function () use ($pendingStudent, $data) {
            // Mettre à jour les informations personnelles si elles sont fournies
            $personalInfoData = [];
            if (isset($data['first_name'])) {
                $personalInfoData['first_names'] = $data['first_name'];
                unset($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $personalInfoData['last_name'] = $data['last_name'];
                unset($data['last_name']);
            }
            if (isset($data['email'])) {
                $personalInfoData['email'] = $data['email'];
                unset($data['email']);
            }
            if (isset($data['phone'])) {
                $contacts = $pendingStudent->personalInformation->contacts ?? [];
                $contacts['phone'] = $data['phone'];
                $personalInfoData['contacts'] = $contacts;
                unset($data['phone']);
            }

            // Mettre à jour PersonalInformation si nécessaire
            if (!empty($personalInfoData) && $pendingStudent->personalInformation) {
                $pendingStudent->personalInformation->update($personalInfoData);
            }

            // Mettre à jour PendingStudent
            $pendingStudent->update($data);

            Log::info('Étudiant en attente mis à jour', [
                'pending_student_id' => $pendingStudent->id,
                'tracking_code' => $pendingStudent->tracking_code,
            ]);

            return $pendingStudent->fresh([
                'entryDiploma',
                'personalInformation',
                'department',
                'academicYear'
            ]);
        });
    }

    /**
     * Supprimer un étudiant en attente
     */
    public function delete(PendingStudent $pendingStudent): bool
    {
        try {
            $pendingStudent->delete();

            Log::info('Étudiant en attente supprimé', [
                'pending_student_id' => $pendingStudent->id,
                'tracking_code' => $pendingStudent->tracking_code,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'étudiant en attente', [
                'pending_student_id' => $pendingStudent->id,
                'tracking_code' => $pendingStudent->tracking_code,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Changer le statut d'un étudiant en attente
     */
    public function changeStatus(PendingStudent $pendingStudent, string $status): PendingStudent
    {
        return DB::transaction(function () use ($pendingStudent, $status) {
            $oldStatus = $pendingStudent->status;

            $pendingStudent->update(['status' => $status]);

            // Si le statut passe à "approved", créer l'étudiant officiel
            if ($status === 'approved' && $oldStatus !== 'approved') {
                $this->createOfficialStudent($pendingStudent);
            }

            Log::info('Statut de l\'étudiant en attente changé', [
                'pending_student_id' => $pendingStudent->id,
                'tracking_code' => $pendingStudent->tracking_code,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);

            return $pendingStudent->fresh();
        });
    }

    /**
     * Mettre à jour l'opinion CUCA
     */
    public function updateCucaOpinion(PendingStudent $pendingStudent, string $opinion, ?string $comment = null): PendingStudent
    {
        $pendingStudent->update([
            'cuca_opinion' => $opinion,
            'cuca_comment' => $comment,
        ]);

        Log::info('Opinion CUCA mise à jour', [
            'pending_student_id' => $pendingStudent->id,
            'tracking_code' => $pendingStudent->tracking_code,
            'opinion' => $opinion,
        ]);

        return $pendingStudent->fresh();
    }

    /**
     * Récupérer les statistiques
     */
    public function getStatistics(): array
    {
        return [
            'total' => PendingStudent::count(),
            'pending' => PendingStudent::where('status', 'pending')->count(),
            'documents_submitted' => PendingStudent::where('status', 'documents_submitted')->count(),
            'approved' => PendingStudent::where('status', 'approved')->count(),
            'rejected' => PendingStudent::where('status', 'rejected')->count(),
            'waiting_cuca' => PendingStudent::where('status', 'waiting_cuca')->count(),
        ];
    }

    /**
     * Récupérer par code de suivi
     */
    public function getByTrackingCode(string $trackingCode): ?PendingStudent
    {
        return PendingStudent::with([
            'entryDiploma',
            'personalInformation',
            'department',
            'academicYear',
            'files'
        ])->where('tracking_code', $trackingCode)->first();
    }

    /**
     * Créer un étudiant officiel à partir d'un étudiant en attente approuvé
     */
    private function createOfficialStudent(PendingStudent $pendingStudent): void
    {
        // Générer un numéro d'étudiant unique
        $studentIdNumber = $this->generateStudentIdNumber();

        // Créer l'étudiant dans la table students
        $student = \App\Modules\Inscription\Models\Student::create([
            'student_id_number' => $studentIdNumber,
            'password' => bcrypt($studentIdNumber), // Mot de passe par défaut = numéro étudiant
        ]);

        // Créer la liaison dans student_pending_student
        \App\Modules\Inscription\Models\StudentPendingStudent::create([
            'student_id' => $student->id,
            'pending_student_id' => $pendingStudent->id,
        ]);

        // Déterminer la cohorte basée sur la période de dépôt
        $cohort = $this->determineCohort($pendingStudent);
        
        // Créer l'entrée dans academic_paths pour l'année académique actuelle
        \App\Modules\Inscription\Models\AcademicPath::create([
            'student_pending_student_id' => $student->studentPendingStudents()->first()->id,
            'academic_year_id' => $pendingStudent->academic_year_id,
            'study_level' => $pendingStudent->level,
            'cohort' => $cohort,
            'financial_status' => 'Non exonéré', // Par défaut non exonéré
        ]);

        Log::info('Étudiant officiel créé', [
            'student_id' => $student->id,
            'student_id_number' => $studentIdNumber,
            'pending_student_id' => $pendingStudent->id,
            'tracking_code' => $pendingStudent->tracking_code,
        ]);
    }

    /**
     * Déterminer la cohorte basée sur la période de dépôt
     */
    private function determineCohort(PendingStudent $pendingStudent): ?string
    {
        // Récupérer les périodes distinctes pour cette année académique
        $periods = DB::table('submission_periods')
            ->where('academic_year_id', $pendingStudent->academic_year_id)
            ->select('start_date', 'end_date')
            ->groupBy('start_date', 'end_date')
            ->orderBy('start_date', 'asc')
            ->get();
        
        if ($periods->isEmpty()) {
            return '1'; // Par défaut cohorte 1
        }
        
        // Trouver dans quelle période le pending_student a été créé
        $createdAt = $pendingStudent->created_at;
        $cohortNumber = 1;
        
        foreach ($periods as $index => $period) {
            $startDate = \Carbon\Carbon::parse($period->start_date);
            $endDate = \Carbon\Carbon::parse($period->end_date);
            
            if ($createdAt->between($startDate, $endDate)) {
                $cohortNumber = $index + 1;
                break;
            }
        }
        
        return (string)$cohortNumber;
    }
    
    /**
     * Générer un numéro d'étudiant unique
     */
    private function generateStudentIdNumber(): string
    {
        do {
            // Format: ANNEE + 4 chiffres aléatoires (ex: 20240001)
            $year = date('Y');
            $number = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $studentId = $year . $number;
        } while (\App\Modules\Inscription\Models\Student::where('student_id_number', $studentId)->exists());

        return $studentId;
    }
}