<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;

class AttendanceService
{
    // =============================================
    // PARSING DU LABEL HEURE
    // Format : "Lundi 08:00 - 12:00"
    // =============================================
    private function parseHeure(string $heure): ?array
    {
        $dayMap = [
            'lundi'     => 'monday',
            'mardi'     => 'tuesday',
            'mercredi'  => 'wednesday',
            'jeudi'     => 'thursday',
            'vendredi'  => 'friday',
            'samedi'    => 'saturday',
            'dimanche'  => 'sunday',
        ];

        if (!preg_match('/^(\w+)\s+(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/iu', trim($heure), $m)) {
            return null;
        }

        $dayFr = mb_strtolower(trim($m[1]));
        $dayEn = $dayMap[$dayFr] ?? null;

        if (!$dayEn) return null;

        return [
            'day_of_week' => $dayEn,
            'start_time'  => $m[2],
            'end_time'    => $m[3],
        ];
    }

    // =============================================
    // REQUÊTE DE BASE
    // =============================================
    private function baseQuery()
    {
        return DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'attendances.room_id', '=', 'rooms.id')
            ->leftJoin('emploi_du_temps', function ($join) {
                $join->on('emploi_du_temps.room_id', '=', 'attendances.room_id')
                     ->whereRaw("emploi_du_temps.day_of_week = LOWER(DAYNAME(attendances.date))")
                     ->where('emploi_du_temps.is_cancelled', 0)
                     ->where('emploi_du_temps.is_active', 1);
            });
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
            $query->where('departments.name', $filters['filiere']);
        }
        if (!empty($filters['niveau'])) {
            $query->where('students.niveau', $filters['niveau']);
        }
        if (!empty($filters['matiere'])) {
            $query->where('course_elements.name', 'like', '%' . $filters['matiere'] . '%');
        }
        if (!empty($filters['heure'])) {
            $parsed = $this->parseHeure($filters['heure']);
            if ($parsed) {
                $query
                    ->where('emploi_du_temps.day_of_week', $parsed['day_of_week'])
                    ->whereRaw("TIME_FORMAT(emploi_du_temps.start_time,'%H:%i') = ?", [$parsed['start_time']])
                    ->whereRaw("TIME_FORMAT(emploi_du_temps.end_time,'%H:%i') = ?",   [$parsed['end_time']]);
            }
        }

        return $query;
    }

    // =============================================
    // DASHBOARD STATS
    // Calcule les taux de présence/absence par mois
    // pour alimenter les 2 graphes correctement
    // =============================================
    public function getDashboardStats(array $filters): array
    {
        $query = $this->applyFilters($this->baseQuery(), $filters);

        // ── Totaux globaux ──
        $total   = (clone $query)->count();
        $present = (clone $query)->where('attendances.status', 'present')->count();
        $absent  = (clone $query)->where('attendances.status', 'absent')->count();

        $presenceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $absenceRate  = $total > 0 ? round(($absent  / $total) * 100, 1) : 0;

        // ── Stats par mois ──
        // On calcule pour chaque mois :
        //   - nb présents du mois
        //   - nb absents du mois
        //   - taux présence du mois = présents / (présents + absents) * 100

        $monthlyPresence     = array_fill(0, 12, 0); // nombre brut
        $monthlyAbsence      = array_fill(0, 12, 0);
        $monthlyPresenceRate = array_fill(0, 12, 0); // taux en %
        $monthlyAbsenceRate  = array_fill(0, 12, 0);

        $monthly = (clone $query)
            ->selectRaw('MONTH(attendances.date) as month, attendances.status, COUNT(*) as cnt')
            ->groupBy('month', 'attendances.status')
            ->orderBy('month')
            ->get();

        // Remplir les tableaux bruts
        foreach ($monthly as $row) {
            $idx = (int) $row->month - 1; // 0=Jan, 11=Déc
            if ($row->status === 'present') {
                $monthlyPresence[$idx] = (int) $row->cnt;
            } else {
                $monthlyAbsence[$idx] = (int) $row->cnt;
            }
        }

        // Calculer les taux par mois
        for ($i = 0; $i < 12; $i++) {
            $monthTotal = $monthlyPresence[$i] + $monthlyAbsence[$i];
            if ($monthTotal > 0) {
                $monthlyPresenceRate[$i] = round(($monthlyPresence[$i] / $monthTotal) * 100, 1);
                $monthlyAbsenceRate[$i]  = round(($monthlyAbsence[$i]  / $monthTotal) * 100, 1);
            }
        }

        return [
            // Taux globaux pour les cards KPI et le doughnut
            'presence'       => $presenceRate,
            'absence'        => $absenceRate,
            'totalPresences' => $present,
            'totalAbsences'  => $absent,
            // Taux mensuels (%) pour le graphe en barres — Jan à Déc
            'monthlyPresence' => $monthlyPresenceRate,
            'monthlyAbsence'  => $monthlyAbsenceRate,
            // Données brutes optionnelles
            'monthlyPresenceCount' => $monthlyPresence,
            'monthlyAbsenceCount'  => $monthlyAbsence,
        ];
    }

    // =============================================
    // MANAGEMENT LIST
    // =============================================
    public function getManagementList(array $filters): array
    {
        $dayLabels = [
            'monday' => 'Lundi', 'tuesday' => 'Mardi',
            'wednesday' => 'Mercredi', 'thursday' => 'Jeudi',
            'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
        ];

        $query = $this->baseQuery()->select(
            'students.id',
            DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
            'students.matricule',
            'students.phone',
            'attendances.status',
            'attendances.date',
            'course_elements.name as matiere',
            'students.niveau',
            'departments.name as filiere',
            'academic_years.academic_year as annee',
            'rooms.name as salle',
            'emploi_du_temps.day_of_week as edt_day',
            'emploi_du_temps.start_time as edt_start',
            'emploi_du_temps.end_time as edt_end',
        );

        $query = $this->applyFilters($query, $filters, 'year');

        return $query
            ->orderBy('attendances.date', 'desc')
            ->orderBy('students.last_name')
            ->get()
            ->map(function ($row) use ($dayLabels) {
                $heure = null;
                if (!empty($row->edt_day)) {
                    $day   = $dayLabels[$row->edt_day] ?? ucfirst($row->edt_day);
                    $start = substr($row->edt_start ?? '', 0, 5);
                    $end   = substr($row->edt_end   ?? '', 0, 5);
                    $heure = "{$day} {$start} - {$end}";
                }
                return [
                    'id'        => $row->id,
                    'name'      => $row->name,
                    'matricule' => $row->matricule,
                    'phone'     => $row->phone ?? null,
                    'status'    => $row->status === 'present' ? 'Présent' : 'Absent',
                    'date'      => $row->date,
                    'matiere'   => $row->matiere,
                    'niveau'    => $row->niveau,
                    'filiere'   => $row->filiere,
                    'annee'     => $row->annee,
                    'salle'     => $row->salle ?? 'N/A',
                    'heure'     => $heure,
                ];
            })
            ->toArray();
    }

    // =============================================
    // FINGERPRINT LIST
    // =============================================
    public function getFingerprintList(array $filters): array
    {
        $query = DB::table('students')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'students.fingerprint_status as fingerprint',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee'
            );

        if (!empty($filters['annee']))   $query->where('academic_years.academic_year', $filters['annee']);
        if (!empty($filters['filiere'])) $query->where('departments.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);

        return $query->orderBy('students.last_name')->get()
            ->map(fn($row) => [
                'id'          => $row->id,
                'name'        => $row->name,
                'matricule'   => $row->matricule,
                'phone'       => $row->phone ?? null,
                'fingerprint' => (bool) $row->fingerprint,
                'niveau'      => $row->niveau,
                'filiere'     => $row->filiere,
                'annee'       => $row->annee,
            ])
            ->toArray();
    }

    public function storeFingerprint(array $data): bool
    {
        $student = DB::table('students')->where('matricule', $data['matricule'])->first();
        if (!$student) return false;
        DB::table('students')->where('id', $student->id)->update(['fingerprint_status' => $data['fingerprint']]);
        return true;
    }

    public function updateFingerprint(int $id, bool $fingerprint): bool
    {
        if (!DB::table('students')->find($id)) return false;
        DB::table('students')->where('id', $id)->update(['fingerprint_status' => $fingerprint]);
        return true;
    }

    public function deleteFingerprint(int $id): bool
    {
        if (!DB::table('students')->find($id)) return false;
        DB::table('students')->where('id', $id)->update(['fingerprint_status' => false]);
        return true;
    }

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
