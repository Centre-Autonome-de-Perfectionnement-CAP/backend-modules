<?php

namespace App\Modules\Attestation\Services;

use App\Modules\Attestation\DTOs\DocumentStatusDTO;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent, AcademicYear};
use App\Modules\Attestation\DTOs\StudentEligibilityDTO;
use Illuminate\Support\Facades\DB;

/**
 * CORRECTIF (v2) — Extrait fidèle de AttestationController réel
 *
 * getStatus(), getBulletinStatus(), identify(), checkAvailability()
 * reproduisent exactement la logique (requêtes, with(), messages d'erreur)
 * du contrôleur source fourni.
 */
class AttestationStatusService
{
    public function __construct(
        private readonly EligibilityService $eligibility,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // getStatus
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAttestationStatus(string $matricule): array
    {
        $student = $this->findStudentOrFail($matricule);

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn ($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department.cycle', 'pendingStudent.academicYear'])
            ->latest('id')
            ->first();

        if (!$link) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Aucune inscription approuvée trouvée.');
        }

        $pending  = $link->pendingStudent;
        $personal = $pending->personalInformation;

        $existingRequests = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->keyBy('type');

        $eligibility = $this->eligibility->getAttestationEligibility($link);

        $documents = [];
        foreach ($eligibility as $type => $eligible) {
            $existing = $existingRequests->get($type);
            if ($existing) {
                $documents[] = DocumentStatusDTO::fromExisting($existing)->toArray();
            } elseif ($eligible) {
                $documents[] = DocumentStatusDTO::available($type)->toArray();
            }
        }

        return [
            'student' => [
                'last_name'     => $personal->last_name,
                'first_names'   => $personal->first_names,
                'matricule'     => $student->student_id_number,
                'level'         => $pending->level ?? '—',
                'department'    => $pending->department?->name ?? '—',
                'academic_year' => $pending->academicYear?->academic_year ?? '—',
            ],
            'documents' => $documents,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // getBulletinStatus
    // ══════════════════════════════════════════════════════════════════════════

    public function getBulletinStatus(string $matricule): array
    {
        $student = $this->findStudentOrFail($matricule);

        $links = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn ($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department', 'pendingStudent.academicYear'])
            ->get();

        if ($links->isEmpty()) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Aucune inscription approuvée trouvée.');
        }

        $latest   = $links->sortByDesc('id')->first();
        $personal = $latest->pendingStudent->personalInformation;
        $years    = [];

        foreach ($links as $link) {
            $year = $link->pendingStudent->academicYear;
            if (!$year) continue;

            if (!$this->eligibility->isBulletinEligible($link)) continue;

            $existing = DB::table('document_requests')
                ->where('student_pending_student_id', $link->id)
                ->where('type', 'bulletin_annuel')
                ->orderBy('submitted_at', 'desc')
                ->first();

            $bulletinData = $existing
                ? DocumentStatusDTO::fromExisting($existing)->toArray()
                : DocumentStatusDTO::available('bulletin_annuel')->toArray();

            $years[] = [
                'link_id'       => $link->id,
                'academic_year' => $year->academic_year,
                'year_id'       => $year->id,
                'is_current'    => (bool) $year->is_current,
                'bulletin'      => $bulletinData,
            ];
        }

        usort($years, fn ($a, $b) => strcmp($b['academic_year'], $a['academic_year']));

        return [
            'student' => [
                'last_name'   => $personal->last_name,
                'first_names' => $personal->first_names,
                'matricule'   => $student->student_id_number,
                'level'       => $latest->pendingStudent->level ?? '—',
                'department'  => $latest->pendingStudent->department?->name ?? '—',
            ],
            'years' => $years,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // identify
    // ══════════════════════════════════════════════════════════════════════════

    public function identify(string $matricule, string $academicYear): array
    {
        $student = $this->findStudentOrFail($matricule);

        $year = AcademicYear::where('academic_year', $academicYear)->first();
        if (!$year) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Année académique introuvable.');
        }

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn ($q) => $q
                ->where('academic_year_id', $year->id)
                ->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department'])
            ->first();

        if (!$link) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Aucune inscription approuvée trouvée pour ce matricule et cette année académique.'
            );
        }

        $personal = $link->pendingStudent->personalInformation;

        return [
            'last_name'     => $personal->last_name,
            'first_names'   => $personal->first_names,
            'matricule'     => $student->student_id_number,
            'level'         => $link->pendingStudent->level ?? '—',
            'department'    => $link->pendingStudent->department?->name ?? '—',
            'academic_year' => $year->academic_year,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // checkAvailability
    // ══════════════════════════════════════════════════════════════════════════

    public function checkAvailability(string $matricule, string $academicYear, string $type): StudentEligibilityDTO
    {
        $student = Student::where('student_id_number', strtoupper(trim($matricule)))->first();
        $year    = AcademicYear::where('academic_year', $academicYear)->first();

        if (!$student || !$year) {
            return StudentEligibilityDTO::unavailable('Données introuvables.');
        }

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn ($q) => $q->where('academic_year_id', $year->id))
            ->with('pendingStudent.department.cycle')
            ->first();

        if (!$link) {
            return StudentEligibilityDTO::unavailable('Aucune inscription trouvée pour cette année académique.');
        }

        return $this->eligibility->checkAvailabilityForType($link, $type, $year);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    private function findStudentOrFail(string $matricule): Student
    {
        $student = Student::where('student_id_number', strtoupper(trim($matricule)))->first();

        if (!$student) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Aucun étudiant trouvé avec ce matricule.');
        }

        return $student;
    }
}
