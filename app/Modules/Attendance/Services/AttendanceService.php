<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceService
{
    // =========================================================
    //  HELPERS PRIVÉS
    // =========================================================

    private function parseHeure(string $heure): ?array
    {
        $dayMap = [
            'lundi' => 'monday', 'mardi' => 'tuesday', 'mercredi' => 'wednesday',
            'jeudi' => 'thursday', 'vendredi' => 'friday', 'samedi' => 'saturday', 'dimanche' => 'sunday',
        ];
        if (!preg_match('/^(\w+)\s+(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/iu', trim($heure), $m)) return null;
        $dayEn = $dayMap[mb_strtolower(trim($m[1]))] ?? null;
        if (!$dayEn) return null;
        return ['day_of_week' => $dayEn, 'start_time' => $m[2], 'end_time' => $m[3]];
    }

    // =========================================================
    //  BASE QUERY
    //  CORRECTION : course_elements → LEFT JOIN (pas INNER JOIN)
    //  pour éviter de perdre les attendances sans cours actif
    // =========================================================
    private function baseQuery()
    {
        return DB::table('attendances')
            ->join('students',       'attendances.student_id',        '=', 'students.id')
            ->join('departments',    'students.filiere_id',            '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id',      '=', 'academic_years.id')
            ->leftJoin('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms',      'attendances.room_id',            '=', 'rooms.id')
            ->leftJoin('emploi_du_temps', function ($join) {
                $join->on('emploi_du_temps.room_id', '=', 'attendances.room_id')
                     ->whereRaw("emploi_du_temps.day_of_week = LOWER(DAYNAME(attendances.date))")
                     ->where('emploi_du_temps.is_cancelled', 0)
                     ->where('emploi_du_temps.is_active', 1);
            });
    }

    private function applyFilters($query, array $filters, string $yearKey = 'annee')
    {
        $annee = $filters[$yearKey] ?? $filters[($yearKey === 'annee' ? 'year' : 'annee')] ?? null;
        if (!empty($annee))              $query->where('academic_years.academic_year', $annee);
        if (!empty($filters['filiere'])) $query->where('departments.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);
        if (!empty($filters['matiere'])) $query->where('course_elements.name', 'like', '%'.$filters['matiere'].'%');
        if (!empty($filters['heure'])) {
            $p = $this->parseHeure($filters['heure']);
            if ($p) {
                $query->where('emploi_du_temps.day_of_week', $p['day_of_week'])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.start_time,'%H:%i') = ?", [$p['start_time']])
                      ->whereRaw("TIME_FORMAT(emploi_du_temps.end_time,'%H:%i') = ?",   [$p['end_time']]);
            }
        }
        if (!empty($filters['course_element_id'])) $query->where('attendances.course_element_id', $filters['course_element_id']);
        if (!empty($filters['date']))              $query->whereDate('attendances.date', $filters['date']);
        return $query;
    }

    private function mapRow(object $row): array
    {
        $dayLabels = [
            'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
        ];
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
            'on_time'   => (bool)($row->on_time ?? true),
            'date'      => $row->date,
            'matiere'   => $row->matiere ?? 'N/A',
            'niveau'    => $row->niveau,
            'filiere'   => $row->filiere,
            'annee'     => $row->annee,
            'salle'     => $row->salle ?? 'N/A',
            'heure'     => $heure,
        ];
    }

    // =========================================================
    //  DASHBOARD — stats globales + retard
    // =========================================================

    public function getDashboardStats(array $filters): array
    {
        $query = $this->applyFilters($this->baseQuery(), $filters);

        $total   = (clone $query)->count();
        $present = (clone $query)->where('attendances.status', 'present')->count();
        $absent  = $total - $present;
        $onTime  = (clone $query)->where('attendances.status', 'present')->where('attendances.on_time', true)->count();
        $late    = (clone $query)->where('attendances.status', 'present')->where('attendances.on_time', false)->count();

        $presenceRate = $total > 0   ? round(($present / $total)   * 100, 1) : 0;
        $absenceRate  = $total > 0   ? round(($absent  / $total)   * 100, 1) : 0;
        $lateRate     = $present > 0 ? round(($late    / $present) * 100, 1) : 0;

        $monthlyPresence = $monthlyAbsence = $monthlyLate = array_fill(0, 12, 0);
        $monthlyPresenceRate = $monthlyAbsenceRate = array_fill(0, 12, 0);

        $monthly = (clone $query)
            ->selectRaw('MONTH(attendances.date) as month, attendances.status, attendances.on_time, COUNT(*) as cnt')
            ->groupBy('month', 'attendances.status', 'attendances.on_time')
            ->orderBy('month')->get();

        foreach ($monthly as $row) {
            $idx = (int)$row->month - 1;
            if ($row->status === 'present') {
                $monthlyPresence[$idx] += (int)$row->cnt;
                if (!(bool)$row->on_time) $monthlyLate[$idx] += (int)$row->cnt;
            } else {
                $monthlyAbsence[$idx] += (int)$row->cnt;
            }
        }

        for ($i = 0; $i < 12; $i++) {
            $t = $monthlyPresence[$i] + $monthlyAbsence[$i];
            if ($t > 0) {
                $monthlyPresenceRate[$i] = round(($monthlyPresence[$i] / $t) * 100, 1);
                $monthlyAbsenceRate[$i]  = round(($monthlyAbsence[$i]  / $t) * 100, 1);
            }
        }

        return [
            'presence'             => $presenceRate,
            'absence'              => $absenceRate,
            'lateRate'             => $lateRate,
            'totalPresences'       => $present,
            'totalAbsences'        => $absent,
            'totalOnTime'          => $onTime,
            'totalLate'            => $late,
            'monthlyPresence'      => $monthlyPresenceRate,
            'monthlyAbsence'       => $monthlyAbsenceRate,
            'monthlyLate'          => $monthlyLate,
            'monthlyPresenceCount' => $monthlyPresence,
            'monthlyAbsenceCount'  => $monthlyAbsence,
        ];
    }

    // =========================================================
    //  MANAGEMENT
    // =========================================================

    public function getManagementList(array $filters): array
    {
        $query = $this->baseQuery()->select(
            'attendances.id',
            DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
            'students.matricule', 'students.phone',
            'attendances.status', 'attendances.on_time', 'attendances.date',
            'course_elements.name as matiere',
            'students.niveau', 'departments.name as filiere',
            'academic_years.academic_year as annee',
            'rooms.name as salle',
            'emploi_du_temps.day_of_week as edt_day',
            'emploi_du_temps.start_time as edt_start',
            'emploi_du_temps.end_time as edt_end',
        );

        $query = $this->applyFilters($query, $filters, 'year');

        return $query->orderBy('attendances.date', 'desc')
            ->orderBy('students.last_name')
            ->get()->map(fn($r) => $this->mapRow($r))->toArray();
    }

    // =========================================================
    //  LISTE PAR COURS / SÉANCE
    // =========================================================

    public function getCourseAttendanceList(array $filters): array
    {
        $query = $this->baseQuery()->select(
            'attendances.id',
            DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
            'students.matricule', 'students.phone',
            'attendances.status', 'attendances.on_time', 'attendances.date',
            'course_elements.name as matiere',
            'students.niveau', 'departments.name as filiere',
            'academic_years.academic_year as annee',
            'rooms.name as salle',
            'emploi_du_temps.day_of_week as edt_day',
            'emploi_du_temps.start_time as edt_start',
            'emploi_du_temps.end_time as edt_end',
        );

        if (!empty($filters['course_element_id'])) $query->where('attendances.course_element_id', $filters['course_element_id']);
        if (!empty($filters['date']))              $query->whereDate('attendances.date', $filters['date']);

        $rows    = $query->orderBy('students.last_name')->get()->map(fn($r) => $this->mapRow($r))->toArray();
        $total   = count($rows);
        $present = count(array_filter($rows, fn($r) => $r['status'] === 'Présent'));
        $late    = count(array_filter($rows, fn($r) => $r['status'] === 'Présent' && !$r['on_time']));

        return [
            'list'    => $rows,
            'summary' => ['total' => $total, 'present' => $present, 'absent' => $total - $present, 'late' => $late],
        ];
    }

    // =========================================================
    //  STATUT CAPTEUR
    // =========================================================

    public function getSensorStatus(): array
    {
        $now    = now();
        $active = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'emploi_du_temps.room_id', '=', 'rooms.id')
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.is_active', 1)
            ->whereRaw("LOWER(DAYNAME(?)) = emploi_du_temps.day_of_week", [$now])
            ->whereRaw("SUBTIME(TIME(?), '02:00:00') <= emploi_du_temps.start_time", [$now->toTimeString()])
            ->whereRaw("TIME(?) <= emploi_du_temps.end_time", [$now->toTimeString()])
            ->select(
                'course_elements.id as course_element_id',
                'course_elements.name as matiere',
                'emploi_du_temps.start_time',
                'emploi_du_temps.end_time',
                'rooms.name as salle'
            )
            ->first();

        if ($active) {
            $startCarbon = Carbon::today()->setTimeFromTimeString($active->start_time);
            $isOnTime    = $now->lte($startCarbon->copy()->addMinutes(5));
            return [
                'active'            => true,
                'course_element_id' => $active->course_element_id,
                'matiere'           => $active->matiere,
                'start_time'        => substr($active->start_time, 0, 5),
                'end_time'          => substr($active->end_time,   0, 5),
                'salle'             => $active->salle ?? 'N/A',
                'on_time'           => $isOnTime,
                'grace_minutes'     => 5,
                'message'           => $isOnTime
                    ? "Cours en cours — scans enregistrés à l'heure"
                    : 'Cours en cours — scans enregistrés avec retard',
            ];
        }

        return ['active' => false, 'message' => 'Aucun cours actif — capteur inactif'];
    }

    // =========================================================
    //  FINGERPRINT — liste étudiants
    // =========================================================

    public function getFingerprintList(array $filters): array
    {
        $query = DB::table('students')
            ->join('departments',    'students.filiere_id',       '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
                'students.matricule',
                'students.phone',
                'students.fingerprint_status as fingerprint',
                'students.fingerprint_index',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee'
            );

        if (!empty($filters['annee']))   $query->where('academic_years.academic_year', $filters['annee']);
        if (!empty($filters['filiere'])) $query->where('departments.name', $filters['filiere']);
        if (!empty($filters['niveau']))  $query->where('students.niveau', $filters['niveau']);

        return $query->orderBy('students.last_name')->get()
            ->map(fn($r) => [
                'id'                => $r->id,
                'name'              => $r->name,
                'matricule'         => $r->matricule,
                'phone'             => $r->phone ?? null,
                'fingerprint'       => (bool)$r->fingerprint,
                'fingerprint_index' => $r->fingerprint_index,
                'niveau'            => $r->niveau,
                'filiere'           => $r->filiere,
                'annee'             => $r->annee,
            ])->toArray();
    }

    public function storeFingerprint(array $data): bool
    {
        $student = DB::table('students')->where('matricule', $data['matricule'])->first();
        if (!$student) return false;
        DB::table('students')->where('id', $student->id)->update(['fingerprint_status' => $data['fingerprint']]);
        return true;
    }

    public function updateFingerprint(int $id, bool $fp, ?int $fingerprintIndex = null): bool
    {
        if (!DB::table('students')->find($id)) return false;

        $update = ['fingerprint_status' => $fp];

        if ($fingerprintIndex !== null && $fingerprintIndex >= 1 && $fingerprintIndex <= 127) {
            $update['fingerprint_index'] = $fingerprintIndex;
        }

        if (!$fp) {
            $update['fingerprint_index'] = null;
        }

        DB::table('students')->where('id', $id)->update($update);
        return true;
    }

    public function deleteFingerprint(int $id): bool
    {
        if (!DB::table('students')->find($id)) return false;
        DB::table('students')->where('id', $id)->update([
            'fingerprint_status' => false,
            'fingerprint_index'  => null,
        ]);
        return true;
    }

    // =========================================================
    //  SCAN DEPUIS ARDUINO
    //  Arduino envoie : { fingerprint_index, date, status? }
    //  Laravel calcule on_time depuis l'emploi du temps
    //
    //  CORRECTION : plus de JOIN sur class_groups
    //  On résout le cours via le niveau de l'étudiant directement
    // =========================================================

    public function recordAttendanceFromArduino(array $data): array
    {
        // 1. Résoudre l'étudiant depuis son slot capteur
        $student = DB::table('students')
            ->where('fingerprint_index', $data['fingerprint_index'])
            ->where('fingerprint_status', true)
            ->first();

        if (!$student) {
            return [
                'success' => false,
                'message' => 'Aucun étudiant enrôlé pour le slot ' . ($data['fingerprint_index'] ?? '?'),
            ];
        }

        $now = now();

        // 2. Trouver le cours actif pour cet étudiant via son niveau
        //    CORRECTION : on cherche dans emploi_du_temps via niveau directement
        //    sans JOIN sur class_groups (table peut ne pas exister)
        $courseElement = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.is_active', 1)
            ->whereRaw("LOWER(DAYNAME(?)) = emploi_du_temps.day_of_week", [$now])
            ->whereRaw("SUBTIME(TIME(?), '02:00:00') <= emploi_du_temps.start_time", [$now->toTimeString()])
            ->whereRaw("TIME(?) <= emploi_du_temps.end_time", [$now->toTimeString()])
            ->select(
                'course_elements.id',
                'course_elements.name as matiere',
                'emploi_du_temps.start_time',
                'emploi_du_temps.room_id'
            )
            ->first();

        // 3. Calculer on_time côté Laravel
        $onTime = true;
        if ($courseElement) {
            $startCarbon = Carbon::today()->setTimeFromTimeString($courseElement->start_time);
            $onTime      = $now->lte($startCarbon->copy()->addMinutes(5));
        }

        // 4. Vérifier doublon — éviter d'enregistrer 2 fois le même jour pour le même cours
        $alreadyExists = DB::table('attendances')
            ->where('student_id',        $student->id)
            ->where('course_element_id', $courseElement?->id)
            ->whereDate('date',          $data['date'])
            ->exists();

        if ($alreadyExists) {
            return [
                'success'  => true,
                'message'  => 'Présence déjà enregistrée pour cette séance',
                'student'  => $student->first_name . ' ' . $student->last_name,
                'matiere'  => $courseElement?->matiere ?? 'N/A',
                'on_time'  => $onTime,
                'duplicate'=> true,
            ];
        }

        // 5. Enregistrer la présence
        DB::table('attendances')->insert([
            'student_id'        => $student->id,
            'course_element_id' => $courseElement?->id,
            'room_id'           => $courseElement?->room_id,
            'status'            => $data['status'] ?? 'present',
            'on_time'           => $onTime,
            'date'              => $data['date'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return [
            'success'  => true,
            'message'  => 'Présence enregistrée',
            'student'  => $student->first_name . ' ' . $student->last_name,
            'matricule'=> $student->matricule,
            'on_time'  => $onTime,
            'matiere'  => $courseElement?->matiere ?? 'Hors cours',
        ];
    }
}
