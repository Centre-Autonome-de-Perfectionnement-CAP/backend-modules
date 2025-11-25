<?php

namespace App\Modules\Soutenance\Services;

use App\Modules\Soutenance\Models\DefenseSubmission;
use App\Modules\Inscription\Models\Student;
use App\Modules\Core\Services\PdfService;
use App\Modules\Core\Mail\DefenseSubmissionReceived;
use App\Modules\Core\Mail\DefenseSubmissionAccepted;
use App\Modules\Core\Mail\DefenseSubmissionRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\ResourceNotFoundException;

class DefenseSubmissionService
{
    public function __construct(
        protected PdfService $pdfService
    ) {}
    public function getAll(array $filters, int $perPage = 15)
    {
        $query = DefenseSubmission::query()->with(['student', 'department', 'professor', 'period', 'room']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('student_id_number', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('first_names', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('thesis_title', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['defense_type'])) {
            $query->where('defense_type', $filters['defense_type']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['academic_year_id'])) {
            $query->forAcademicYear($filters['academic_year_id']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function create(array $data, $thesisFile = null, $additionalFiles = null): DefenseSubmission
    {
        return DB::transaction(function () use ($data, $thesisFile, $additionalFiles) {
            if (empty($data['defense_submission_period_id'])) {
                $period = \App\Modules\Soutenance\Models\DefenseSubmissionPeriod::where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();
                if (!$period) {
                    throw new \Exception("Aucune période de soumission active");
                }
                $data['defense_submission_period_id'] = $period->id;
            } else {
                $period = \App\Modules\Soutenance\Models\DefenseSubmissionPeriod::findOrFail($data['defense_submission_period_id']);
            }
            $this->validateSubmissionPeriod($period);

            $department = \App\Modules\Inscription\Models\Department::with('cycle')->findOrFail($data['department_id']);
            $cycleName = strtolower($department->cycle->name);

            $typeMap = [
                'licence' => 'licence',
                'master' => 'master',
            ];
            $expectedCycle = $typeMap[$data['defense_type']] ?? null;
            if (!$expectedCycle || strpos($cycleName, $expectedCycle) === false) {
                throw new \Exception("Le type de soutenance ne correspond pas au cycle de la filière choisie.");
            }

            $filePaths = [];
            if ($thesisFile) {
                $filePaths['thesis'] = $thesisFile->store('defenses/theses', 'public');
            }

            if ($additionalFiles) {
                $filePaths['additional'] = array_map(
                    fn ($file) => $file->store('defenses/additional', 'public'),
                    $additionalFiles
                );
            }

            $submission = DefenseSubmission::create([
                'student_id_number' => $data['student_id_number'] ?? null,
                'defense_submission_period_id' => $period->id,
                'thesis_title' => $data['thesis_title'],
                'professor_id' => $data['professor_id'],
                'files' => $filePaths,
                'status' => 'pending',
                'defense_type' => $data['defense_type'],
                'department_id' => $data['department_id'],
                'last_name' => $data['last_name'],
                'first_names' => $data['first_names'],
                'email' => $data['email'],
                'contacts' => json_encode($data['contacts']),
            ]);

            Log::info('Soumission de soutenance créée', [
                'submission_id' => $submission->id,
                'student_id_number' => $submission->student_id_number,
            ]);

            try {
                Mail::to($data['email'])->send(new DefenseSubmissionReceived($submission));
            } catch (\Exception $e) {
                Log::error('Échec envoi email confirmation soumission: '.$e->getMessage());
            }

            return $submission;
        });
    }

    private function validateSubmissionPeriod($period): void
    {
        $currentDate = now()->toDateString();
        
        if ($currentDate < $period->start_date || $currentDate > $period->end_date) {
            throw new \Exception("La période de soumission est fermée");
        }
    }

    public function getById(int $id): ?DefenseSubmission
    {
        return DefenseSubmission::with(['student', 'department', 'professor', 'period', 'room', 'juryMembers.professor', 'juryMembers.grade'])
            ->find($id);
    }

    public function updateStatus(DefenseSubmission $submission, string $status, ?string $rejectionReason = null): DefenseSubmission
    {
        $submission->update([
            'status' => $status,
            'rejection_reason' => $rejectionReason,
        ]);

        Log::info('Statut de soumission mis à jour', [
            'submission_id' => $submission->id,
            'new_status' => $status,
        ]);

        try {
            if ($status === 'accepted') {
                Mail::to($submission->email)->send(new DefenseSubmissionAccepted($submission));
            } elseif ($status === 'rejected') {
                Mail::to($submission->email)->send(new DefenseSubmissionRejected($submission, $rejectionReason));
            }
        } catch (\Exception $e) {
            Log::error('Échec envoi email changement statut: '.$e->getMessage());
        }

        return $submission->fresh(['student', 'department', 'professor', 'period', 'room']);
    }

    public function scheduleDefense(DefenseSubmission $submission, array $data): DefenseSubmission
    {
        $submission->update([
            'defense_date' => $data['defense_date'],
            'room_id' => $data['room_id'],
            'status' => 'scheduled',
        ]);

        Log::info('Soutenance planifiée', [
            'submission_id' => $submission->id,
            'defense_date' => $data['defense_date'],
            'room_id' => $data['room_id'],
        ]);

        return $submission->fresh(['student', 'department', 'professor', 'period', 'room']);
    }

    public function generateQuitusPdf(array $data)
    {
        return $this->pdfService->generatePdf('core::pdfs.quitus', $data, ['orientation' => 'portrait']);
    }

    public function generateCorrectionPdf(array $data)
    {
        $data['date_soutenance'] = date('d/m/Y', strtotime($data['date_soutenance']));
        return $this->pdfService->generatePdf('core::pdfs.correction', $data, ['orientation' => 'portrait']);
    }

    public function delete(DefenseSubmission $submission): bool
    {
        return DB::transaction(function () use ($submission) {
            if ($submission->files) {
                foreach ($submission->files as $file) {
                    if (Storage::disk('local')->exists($file)) {
                        Storage::disk('local')->delete($file);
                    }
                }
            }

            $submission->delete();

            Log::info('Soumission de soutenance supprimée', [
                'submission_id' => $submission->id,
            ]);

            return true;
        });
    }

    public function getStatistics(): array
    {
        return [
            'total' => DefenseSubmission::count(),
            'pending' => DefenseSubmission::where('status', 'pending')->count(),
            'accepted' => DefenseSubmission::where('status', 'accepted')->count(),
            'rejected' => DefenseSubmission::where('status', 'rejected')->count(),
            'scheduled' => DefenseSubmission::where('status', 'scheduled')->count(),
        ];
    }
}
