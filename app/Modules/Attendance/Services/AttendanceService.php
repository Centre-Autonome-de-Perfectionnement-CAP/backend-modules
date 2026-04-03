<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;

class AttendanceService
{
    // =============================================
    // REQUÊTE DE BASE — avec rooms
    // =============================================
    private function baseQuery()
    {
        return DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'attendances.room_id', '=', 'rooms.id'); // ✅ leftJoin car room_id nullable
    }

    // =============================================
    // APPLIQUER LES FILTRES
    // =============================================
    private function applyFilters($query, array $filters, string $yearKey = 'annee')
    {
        if (!empty($filters[$yearKey])) {
            $query->where('academic_years.academic_year', $filters[$yearKey]);
        }
        if (!empty($filters['filiere'])) {
            $query->where('filieres.name', $filters['filiere']);
        }
        if (!empty($filters['niveau'])) {
            $query->where('students.niveau', $filters['niveau']);
        }
        if (!empty($filters['matiere'])) {
            $query->where('course_elements.name', 'like', '%' . $filters['matiere'] . '%');
        }
        if (!empty($filters['salle'])) {          // ✅ filtre salle
            $query->where('rooms.name', $filters['salle']);
        }

        return $query;
    }

    // =============================================
    // DASHBOARD STATS
    // =============================================
    public function getDashboardStats(array $filters): array
    {
        $query = $this->applyFilters($this->baseQuery(), $filters);

        $total   = $query->count();
        $present = (clone $query)->where('attendances.status', 'present')->count();
        $absent  = (clone $query)->where('attendances.status', 'absent')->count();

        $presence = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $absence  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

        $monthlyPresence = array_fill(0, 12, 0);
        $monthlyAbsence  = array_fill(0, 12, 0);

        $monthly = (clone $query)
            ->selectRaw('MONTH(attendances.date) as month, attendances.status, COUNT(*) as total')
            ->groupBy('month', 'attendances.status')
            ->get();

        foreach ($monthly as $row) {
            $index = (int) $row->month - 1;
            if ($row->status === 'present') {
                $monthlyPresence[$index] = (int) $row->total;
            } else {
                $monthlyAbsence[$index] = (int) $row->total;
            }
        }

        return [
            'presence'        => $presence,
            'absence'         => $absence,
            'totalPresences'  => $present,
            'totalAbsences'   => $absent,
            'monthlyPresence' => $monthlyPresence,
            'monthlyAbsence'  => $monthlyAbsence,
        ];
    }

    // =============================================
    // MANAGEMENT LIST — avec salle
    // =============================================
    public function getManagementList(array $filters): array
    {
        $query = $this->baseQuery()
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'attendances.status',
                'attendances.date',
                'course_elements.name as matiere',
                'students.niveau',
                'filieres.name as filiere',
                'academic_years.academic_year as annee',
                'rooms.name as salle',             // ✅ nouveau
                'rooms.code as salle_code',        // ✅ nouveau
            );

        $query = $this->applyFilters($query, $filters, 'year');

        if (!empty($filters['matiere'])) {
            $query->where('course_elements.name', 'like', '%' . $filters['matiere'] . '%');
        }

        return $query
            ->orderBy('students.last_name')
            ->get()
            ->map(fn($row) => [
                'id'         => $row->id,
                'name'       => $row->name,
                'matricule'  => $row->matricule,
                'status'     => $row->status === 'present' ? 'Présent' : 'Absent',
                'date'       => $row->date,
                'matiere'    => $row->matiere,
                'niveau'     => $row->niveau,
                'filiere'    => $row->filiere,
                'annee'      => $row->annee,
                'salle'      => $row->salle ?? 'N/A',     // ✅ nouveau
                'salle_code' => $row->salle_code ?? 'N/A', // ✅ nouveau
            ])
            ->toArray();
    }

    // =============================================
    // FINGERPRINT LIST
    // =============================================
    public function getFingerprintList(array $filters): array
    {
        $query = DB::table('students')
            ->join('filieres', 'students.filiere_id', '=', 'filieres.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.fingerprint_status as fingerprint',
                'students.niveau',
                'filieres.name as filiere',
                'academic_years.academic_year as annee'
            );

        if (!empty($filters['annee'])) {
            $query->where('academic_years.academic_year', $filters['annee']);
        }
        if (!empty($filters['filiere'])) {
            $query->where('filieres.name', $filters['filiere']);
        }
        if (!empty($filters['niveau'])) {
            $query->where('students.niveau', $filters['niveau']);
        }

        return $query
            ->orderBy('students.last_name')
            ->get()
            ->map(fn($row) => [
                'id'          => $row->id,
                'name'        => $row->name,
                'matricule'   => $row->matricule,
                'fingerprint' => (bool) $row->fingerprint,
                'niveau'      => $row->niveau,
                'filiere'     => $row->filiere,
                'annee'       => $row->annee,
            ])
            ->toArray();
    }

    // =============================================
    // STORE FINGERPRINT
    // =============================================
    public function storeFingerprint(array $data): bool
    {
        $student = DB::table('students')
            ->where('matricule', $data['matricule'])
            ->first();

        if (!$student) return false;

        DB::table('students')
            ->where('id', $student->id)
            ->update(['fingerprint_status' => $data['fingerprint']]);

        return true;
    }

    // =============================================
    // UPDATE FINGERPRINT
    // =============================================
    public function updateFingerprint(int $id, bool $fingerprint): bool
    {
        $student = DB::table('students')->find($id);
        if (!$student) return false;

        DB::table('students')
            ->where('id', $id)
            ->update(['fingerprint_status' => $fingerprint]);

        return true;
    }

    // =============================================
    // DELETE FINGERPRINT
    // =============================================
    public function deleteFingerprint(int $id): bool
    {
        $student = DB::table('students')->find($id);
        if (!$student) return false;

        DB::table('students')
            ->where('id', $id)
            ->update(['fingerprint_status' => false]);

        return true;
    }

    // =============================================
    // RECORD ATTENDANCE
    // =============================================
    public function recordAttendance(array $data): object
    {
        $id = DB::table('attendances')->insertGetId([
            'student_id'        => $data['student_id'],
            'course_element_id' => $data['course_element_id'],
            'status'            => $data['status'],
            'date'              => $data['date'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return DB::table('attendances')->find($id);
    }
}
