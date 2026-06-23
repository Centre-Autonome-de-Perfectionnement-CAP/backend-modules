<?php

namespace App\Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceService
{
    // =========================================================================
   // =========================================================================
    //  CONSTANTES CAP-EPAC
    // =========================================================================

    // Grâce pour "à l'heure" : 15 minutes après le début
    const GRACE_ON_TIME_MINUTES  = 15;
    // Fin du "retard léger" : 30 minutes après le début
    const GRACE_LATE_LIGHT_MINUTES = 30;
    // Durée minimale de présence : 70% du cours (240 min × 70% = 168 min)
    const MIN_PRESENCE_MINUTES   = 168; 
    // Ouverture du pointage : 60 min avant le cours
    const OPEN_BEFORE_MINUTES    = 60;
    // Fermeture du pointage : 15 min après la fin
    const CLOSE_AFTER_MINUTES    = 15;

    // =========================================================================
    //  HELPERS PRIVÉS
    // =========================================================================

    private function parseHeure(string $heure): ?array
    {
        $dayMap = [
            'lundi' => 'monday', 'mardi' => 'tuesday', 'mercredi' => 'wednesday',
            'jeudi' => 'thursday', 'vendredi' => 'friday',
            'samedi' => 'saturday', 'dimanche' => 'sunday',
        ];
        if (!preg_match('/^(\w+)\s+(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/iu', trim($heure), $m)) return null;
        $dayEn = $dayMap[mb_strtolower(trim($m[1]))] ?? null;
        if (!$dayEn) return null;
        return ['day_of_week' => $dayEn, 'start_time' => $m[2], 'end_time' => $m[3]];
    }

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
                     // SÉCURITÉ MATIÈRE : Association stricte pour éviter les conflits matin/après-midi dans la même salle
                     ->on('emploi_du_temps.course_element_id', '=', 'attendances.course_element_id')
                     ->whereRaw("emploi_du_temps.day_of_week = LOWER(DAYNAME(attendances.date))")
                     ->where('emploi_du_temps.is_cancelled', 0)
                     ->where('emploi_du_temps.is_active', 1);
            });
    }

    private function applyFilters($query, array $filters)
    {
        $annee = $filters['annee'] ?? $filters['year'] ?? null;
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

        $statusLabel = match(true) {
            $row->status === 'absent' => 'Absent',
            $row->status === 'present' && ($row->late_type ?? null) !== null => 'Retard',
            default => 'Présent',
        };

        return [
            'id'            => $row->id,
            'name'          => $row->name,
            'matricule'     => $row->matricule,
            'phone'         => $row->phone ?? null,
            'status'        => $row->status,
            'status_label'  => $statusLabel,
            'on_time'       => (bool)($row->on_time ?? true),
            'late_type'     => $row->late_type ?? null,
            'first_entry'   => $row->first_entry ?? null,
            'last_exit'     => $row->last_exit   ?? null,
            'total_minutes' => (int)($row->total_minutes ?? 0),
            'date'          => $row->date,
            'matiere'       => $row->matiere ?? 'N/A',
            'niveau'        => $row->niveau,
            'filiere'       => $row->filiere,
            'annee'         => $row->annee,
            'salle'         => $row->salle ?? 'N/A',
            'heure'         => $heure,
        ];
    }

    // =========================================================================
    //  DASHBOARD
    // =========================================================================

    public function getDashboardStats(array $filters): array
    {
        // On s'assure de l'unicité de chaque fiche d'absence/présence
        $query = $this->applyFilters($this->baseQuery(), $filters)->groupBy('attendances.id');

        $total      = DB::table(DB::raw("({$query->toSql()}) as sub"))->mergeBindings($query)->count();
        $present    = (clone $query)->where('attendances.status', 'present');
        $totalPresences = DB::table(DB::raw("({$present->toSql()}) as sub"))->mergeBindings($present)->count();
        
        $absent     = $total - $totalPresences;
        
        $onTimeQuery = (clone $query)->where('attendances.status', 'present')->whereNull('attendances.late_type');
        $onTime      = DB::table(DB::raw("({$onTimeQuery->toSql()}) as sub"))->mergeBindings($onTimeQuery)->count();
        
        $lateQuery   = (clone $query)->where('attendances.status', 'present')->whereNotNull('attendances.late_type');
        $late        = DB::table(DB::raw("({$lateQuery->toSql()}) as sub"))->mergeBindings($lateQuery)->count();

        $presenceRate = $total > 0 ? round(($totalPresences / $total) * 100, 1) : 0;
        $absenceRate  = $total > 0 ? round(($absent  / $total) * 100, 1) : 0;
        $lateRate     = $total > 0 ? round(($late    / $total) * 100, 1) : 0;

        $monthlyPresence = $monthlyAbsence = $monthlyLate = array_fill(0, 12, 0);

        $monthly = $this->applyFilters($this->baseQuery(), $filters)
            ->selectRaw('MONTH(attendances.date) as month, attendances.status, attendances.late_type, COUNT(DISTINCT attendances.id) as cnt')
            ->groupBy('month', 'attendances.status', 'attendances.late_type')
            ->get();

        foreach ($monthly as $row) {
            $idx = (int)$row->month - 1;
            if ($row->status === 'present') {
                $monthlyPresence[$idx] += (int)$row->cnt;
                if ($row->late_type !== null) $monthlyLate[$idx] += (int)$row->cnt;
            } else {
                $monthlyAbsence[$idx] += (int)$row->cnt;
            }
        }

        $monthlyPresenceRate = $monthlyAbsenceRate = $monthlyLateRate = array_fill(0, 12, 0);
        for ($i = 0; $i < 12; $i++) {
            $t = $monthlyPresence[$i] + $monthlyAbsence[$i];
            if ($t > 0) {
                $monthlyPresenceRate[$i] = round(($monthlyPresence[$i] / $t) * 100, 1);
                $monthlyAbsenceRate[$i]  = round(($monthlyAbsence[$i]  / $t) * 100, 1);
                $monthlyLateRate[$i]     = round(($monthlyLate[$i]     / $t) * 100, 1);
            }
        }

        return [
            'presence'       => $presenceRate,
            'absence'        => $absenceRate,
            'lateRate'       => $lateRate,
            'totalPresences' => $totalPresences,
            'totalAbsences'  => $absent,
            'totalOnTime'    => $onTime,
            'totalLate'      => $late,
            'monthlyPresence'=> $monthlyPresenceRate,
            'monthlyAbsence' => $monthlyAbsenceRate,
            'monthlyLate'    => $monthlyLateRate,
        ];
    }

    // =========================================================================
    //  MANAGEMENT
    // =========================================================================

    public function getManagementList(array $filters): array
    {
        $query = $this->baseQuery()->select(
            'attendances.id',
            DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
            'students.matricule', 'students.phone',
            'students.fingerprint_index',
            'students.filiere_id',
            'attendances.status', 'attendances.on_time', 'attendances.late_type',
            'attendances.first_entry', 'attendances.last_exit', 'attendances.total_minutes',
            'attendances.date',
            'course_elements.name as matiere',
            'students.niveau', 'departments.name as filiere',
            'academic_years.academic_year as annee',
            'rooms.name as salle',
            'emploi_du_temps.day_of_week as edt_day',
            'emploi_du_temps.start_time as edt_start',
            'emploi_du_temps.end_time as edt_end'
        );

        $query = $this->applyFilters($query, $filters);

        // ANTI-DOUBLON : Regroupement par ID unique d'attendance
        return $query->groupBy('attendances.id')
            ->orderBy('attendances.date', 'desc')
            ->orderBy('students.last_name')
            ->get()->map(fn($r) => $this->mapRow($r))->toArray();
    }

    // =========================================================================
    //  LISTE PAR COURS / SÉANCE
    // =========================================================================

    public function getCourseAttendanceList(array $filters): array
    {
        $query = $this->baseQuery()->select(
            'attendances.id',
            DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
            'students.matricule', 'students.phone',
            'students.fingerprint_index',
            'students.filiere_id',
            'attendances.status', 'attendances.on_time', 'attendances.late_type',
            'attendances.first_entry', 'attendances.last_exit', 'attendances.total_minutes',
            'attendances.date',
            'course_elements.name as matiere',
            'students.niveau', 'departments.name as filiere',
            'academic_years.academic_year as annee',
            'rooms.name as salle',
            'emploi_du_temps.day_of_week as edt_day',
            'emploi_du_temps.start_time as edt_start',
            'emploi_du_temps.end_time as edt_end'
        );

        if (!empty($filters['course_element_id'])) $query->where('attendances.course_element_id', $filters['course_element_id']);
        if (!empty($filters['date']))              $query->whereDate('attendances.date', $filters['date']);

        // ANTI-DOUBLON : Regroupement par ID unique d'attendance
        $rows    = $query->groupBy('attendances.id')->orderBy('students.last_name')->get()->map(fn($r) => $this->mapRow($r))->toArray();
        $total   = count($rows);
        $present = count(array_filter($rows, fn($r) => $r['status'] === 'present'));

        return [
            'list'    => $rows,
            'summary' => [
                'total'   => $total,
                'present' => $present,
                'absent'  => $total - $present,
                'late'    => count(array_filter($rows, fn($r) => $r['status'] === 'present' && $r['late_type'] !== null)),
            ],
        ];
    }

    // =========================================================================
    //  SÉANCES DISPONIBLES
    // =========================================================================

    public function sessions(array $filters): array
    {
        $query = DB::table('attendances')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->where('attendances.course_element_id', $filters['course_element_id'])
            ->selectRaw('
                DATE(attendances.date) as date,
                course_elements.name as matiere,
                COUNT(DISTINCT attendances.id) as total,
                SUM(attendances.status = "present") as presents,
                SUM(attendances.status = "absent") as absents,
                SUM(attendances.late_type IS NOT NULL) as retards
            ')
            ->groupByRaw('DATE(attendances.date), course_elements.name')
            ->orderByDesc('date')
            ->get();

        return $query->map(fn($r) => [
            'date'     => $r->date,
            'matiere'  => $r->matiere,
            'total'    => (int)$r->total,
            'presents' => (int)$r->presents,
            'absents'  => (int)$r->absents,
            'retards'  => (int)$r->retards,
            'label'    => Carbon::parse($r->date)->translatedFormat('l d F Y')
                          . " — {$r->presents}/{$r->total} présents",
        ])->toArray();
    }

    // =========================================================================
    //  STATUT CAPTEUR
    // =========================================================================

    public function getSensorStatus(): array
    {
        $now    = now();
        $active = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'emploi_du_temps.room_id', '=', 'rooms.id')
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.is_active', 1)
            ->whereRaw("LOWER(DAYNAME(?)) = emploi_du_temps.day_of_week", [$now])
            ->whereRaw("TIME(?) >= SUBTIME(emploi_du_temps.start_time, '00:15:00')", [$now->toTimeString()])
            ->whereRaw("TIME(?) <= ADDTIME(emploi_du_temps.end_time, '00:15:00')", [$now->toTimeString()])
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
            $nowTime     = $now->copy();

            $minutesLate = $nowTime->diffInMinutes($startCarbon, false);
            $minutesLate = abs($minutesLate) * ($nowTime->gt($startCarbon) ? 1 : -1);

            $isOnTime = $minutesLate <= self::GRACE_ON_TIME_MINUTES;
            $isLate   = !$isOnTime;

            return [
                'active'            => true,
                'course_element_id' => $active->course_element_id,
                'matiere'           => $active->matiere,
                'start_time'        => substr($active->start_time, 0, 5),
                'end_time'          => substr($active->end_time, 0, 5),
                'salle'             => $active->salle ?? 'N/A',
                'on_time'           => $isOnTime,
                'is_late'           => $isLate,
                'message'           => $isOnTime
                    ? "Cours en cours — scans à l'heure"
                    : 'Cours en cours — retards en cours',
            ];
        }

        return ['active' => false, 'message' => 'Aucun cours actif — capteur inactif'];
    }

    // =========================================================================
    //  FINGERPRINT
    // =========================================================================

    public function getFingerprintList(array $filters): array
    {
        $query = DB::table('students')
            ->join('departments',    'students.filiere_id',       '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->select(
                'students.id',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as name"),
                'students.matricule', 'students.phone',
                'students.fingerprint_status as fingerprint',
                'students.fingerprint_index',
                'students.niveau', 'departments.name as filiere',
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

    public function updateFingerprint(int $id, bool $fp, ?int $fingerprintIndex = null): bool
    {
        if (!DB::table('students')->find($id)) return false;
        $update = ['fingerprint_status' => $fp];
        if ($fingerprintIndex !== null) $update['fingerprint_index'] = $fingerprintIndex;
        if (!$fp) $update['fingerprint_index'] = null;
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

    public function storeFingerprint(array $data): bool
    {
        $student = DB::table('students')->where('matricule', $data['matricule'])->first();
        if (!$student) return false;
        DB::table('students')->where('id', $student->id)->update(['fingerprint_status' => $data['fingerprint']]);
        return true;
    }

    // =========================================================================
    //  SCAN DEPUIS ARDUINO
    // =========================================================================

    public function recordAttendanceFromArduino(array $data): array
    {
        $fingerprintIndex = $data['fingerprint_index'];
        $date             = $data['date'];
        $scanTimeStr      = $data['time'];

        $student = DB::table('students')
            ->where('fingerprint_index', $fingerprintIndex)
            ->where('fingerprint_status', true)
            ->first();

        if (!$student) {
            return ['success' => false, 'message' => 'Aucun étudiant enrôlé pour le slot '.$fingerprintIndex];
        }

        $now = Carbon::parse($date.' '.$scanTimeStr);

        $course = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->leftJoin('rooms', 'emploi_du_temps.room_id', '=', 'rooms.id')
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.is_active', 1)
            ->whereRaw("LOWER(DAYNAME(?)) = emploi_du_temps.day_of_week", [$now])
            ->whereRaw("TIME(?) >= SUBTIME(emploi_du_temps.start_time, '00:15:00')", [$now->toTimeString()])
            ->whereRaw("TIME(?) <= ADDTIME(emploi_du_temps.end_time, '00:15:00')", [$now->toTimeString()])
            ->select(
                'course_elements.id as course_element_id',
                'course_elements.name as matiere',
                'emploi_du_temps.start_time',
                'emploi_du_temps.end_time',
                'emploi_du_temps.room_id',
            )
            ->first();

        if (!$course) {
            return [
                'success'  => false,
                'message'  => 'Aucun cours actif — pointage refusé (hors plage horaire)',
                'student'  => $student->first_name.' '.$student->last_name,
                'scan_time'=> $scanTimeStr,
            ];
        }

        $firstScanOfCourse = !DB::table('attendance_scans')
            ->where('course_element_id', $course->course_element_id)
            ->where('date', $date)
            ->exists();

        if ($firstScanOfCourse) {
            $classmateIds = DB::table('students')
                ->where('filiere_id',       $student->filiere_id)
                ->where('academic_year_id', $student->academic_year_id)
                ->where('id', '!=', $student->id)
                ->pluck('id');

            $now_ts = now();
            $absentRows = [];
            foreach ($classmateIds as $classmateId) {
                $alreadyExists = DB::table('attendances')
                    ->where('student_id',        $classmateId)
                    ->where('course_element_id', $course->course_element_id)
                    ->whereDate('date',           $date)
                    ->exists();

                if (!$alreadyExists) {
                    $absentRows[] = [
                        'student_id'        => $classmateId,
                        'course_element_id' => $course->course_element_id,
                        'room_id'           => $course->room_id,
                        'date'              => $date,
                        'status'            => 'absent',
                        'on_time'           => 0,
                        'late_type'         => null,
                        'first_entry'       => null,
                        'last_exit'         => null,
                        'total_minutes'     => 0,
                        'fingerprint_index' => null,
                        'created_at'        => $now_ts,
                        'updated_at'        => $now_ts,
                    ];
                }
            }

            if (!empty($absentRows)) {
                foreach (array_chunk($absentRows, 100) as $chunk) {
                    DB::table('attendances')->insert($chunk);
                }
            }
        }

        $lastScan = DB::table('attendance_scans')
            ->where('student_id', $student->id)
            ->where('date', $date)
            ->where('course_element_id', $course->course_element_id)
            ->orderByDesc('scan_time')
            ->first();

        $scanType = 'entry'; 
        if ($lastScan) {
            $scanType = $lastScan->scan_type === 'entry' ? 'exit' : 'entry';
        }

        DB::table('attendance_scans')->insert([
            'student_id'        => $student->id,
            'course_element_id' => $course->course_element_id,
            'room_id'           => $course->room_id,
            'date'              => $date,
            'scan_time'         => $scanTimeStr,
            'scan_type'         => $scanType,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $session = $this->computeSession($student->id, $course, $date);

        $existing = DB::table('attendances')
            ->where('student_id', $student->id)
            ->where('course_element_id', $course->course_element_id)
            ->whereDate('date', $date)
            ->first();

        $attendanceData = [
            'student_id'        => $student->id,
            'course_element_id' => $course->course_element_id,
            'room_id'           => $course->room_id,
            'date'              => $date,
            'status'            => $session['status'],
            'on_time'           => $session['on_time'],
            'late_type'         => $session['late_type'],
            'first_entry'       => $session['first_entry'],
            'last_exit'         => $session['last_exit'],
            'total_minutes'     => $session['total_minutes'],
            'updated_at'        => now(),
        ];

        if ($existing) {
            DB::table('attendances')->where('id', $existing->id)->update($attendanceData);
        } else {
            DB::table('attendances')->insert(array_merge($attendanceData, [
                'fingerprint_index' => $fingerprintIndex,
                'created_at'        => now(),
            ]));
        }

        return [
            'success'       => true,
            'student'       => $student->first_name.' '.$student->last_name,
            'matricule'     => $student->matricule,
            'scan_type'     => $scanType,
            'scan_time'     => $scanTimeStr,
            'matiere'       => $course->matiere,
            'status'        => $session['status'],
            'late_type'     => $session['late_type'],
            'total_minutes' => $session['total_minutes'],
            'message'       => $this->buildScanMessage($scanType, $session, $student),
        ];
    }

    // =========================================================================
    //  CALCUL DE SÉANCE
    // =========================================================================

    private function computeSession(int $studentId, object $course, string $date): array
    {
        $scans = DB::table('attendance_scans')
            ->where('student_id', $studentId)
            ->where('course_element_id', $course->course_element_id)
            ->where('date', $date)
            ->orderBy('scan_time')
            ->get();

        if ($scans->isEmpty()) {
            return [
                'status' => 'absent', 'on_time' => false,
                'late_type' => null, 'first_entry' => null,
                'last_exit' => null, 'total_minutes' => 0,
            ];
        }

        $firstEntry  = null;
        $lastExit    = null;
        $totalMinutes= 0;
        $pendingEntry= null;

        foreach ($scans as $scan) {
            if ($scan->scan_type === 'entry') {
                $pendingEntry = Carbon::parse($date.' '.$scan->scan_time);
                if (!$firstEntry) $firstEntry = $scan->scan_time;
            } elseif ($scan->scan_type === 'exit' && $pendingEntry) {
                $exit = Carbon::parse($date.' '.$scan->scan_time);
                $totalMinutes += $pendingEntry->diffInMinutes($exit);
                $lastExit     = $scan->scan_time;
                $pendingEntry = null;
            }
        }

        $courseStart = Carbon::parse($date.' '.$course->start_time);
        $lateType = null;
        $onTime   = false;
        $status   = 'absent';

        if ($firstEntry) {
            $firstEntryCarbon = Carbon::parse($date.' '.$firstEntry);
            $minutesAfterStart = $courseStart->diffInMinutes($firstEntryCarbon, false);

            if ($minutesAfterStart <= self::GRACE_ON_TIME_MINUTES) {
                $lateType = null;
                $onTime   = true;
            } else {
                $lateType = 'retard';
                $onTime   = false;
            }

            if ($totalMinutes >= self::MIN_PRESENCE_MINUTES) {
                $status = 'present';
            } else {
                $status   = 'absent';
                $lateType = null;
                $onTime   = false;
            }
        }

        return [
            'status'        => $status,
            'on_time'       => $onTime,
            'late_type'     => $lateType,
            'first_entry'   => $firstEntry,
            'last_exit'     => $lastExit,
            'total_minutes' => $totalMinutes,
        ];
    }

    // =========================================================================
    //  CLÔTURE AUTOMATIQUE
    // =========================================================================
    
    public function autoCloseFinishedCourses(): array
    {
        $now  = Carbon::now();
        $date = $now->format('Y-m-d');
        $dayEn = strtolower($now->englishDayOfWeek);

        $coursTermines = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->join('departments',     'emploi_du_temps.department_id',     '=', 'departments.id')
            ->where('emploi_du_temps.is_active',    1)
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.day_of_week',  $dayEn)
            ->whereRaw("TIME(?) > ADDTIME(emploi_du_temps.end_time, '00:15:00')", [$now->toTimeString()])
            ->where(function($q) use ($date) {
                $q->whereNull('emploi_du_temps.recurrence_start_date')
                  ->orWhere('emploi_du_temps.recurrence_start_date', '<=', $date);
            })
            ->where(function($q) use ($date) {
                $q->whereNull('emploi_du_temps.recurrence_end_date')
                  ->orWhere('emploi_du_temps.recurrence_end_date', '>=', $date);
            })
            ->select(
                'emploi_du_temps.course_element_id',
                'emploi_du_temps.department_id',
                'emploi_du_temps.room_id',
                'course_elements.name as matiere',
                'departments.abbreviation as filiere',
            )
            ->get();

        $totalAbsents = 0;

        foreach ($coursTermines as $cours) {
            $yearId = DB::table('academic_years')->orderByDesc('id')->value('id');

            $students = DB::table('students')
                ->where('filiere_id',       $cours->department_id)
                ->where('academic_year_id', $yearId)
                ->pluck('id');

            $now_ts = now();
            foreach ($students as $studentId) {
                $existing = DB::table('attendances')
                    ->where('student_id',        $studentId)
                    ->where('course_element_id', $cours->course_element_id)
                    ->whereDate('date',           $date)
                    ->first();

                if (!$existing) {
                    DB::table('attendances')->insert([
                        'student_id'        => $studentId,
                        'course_element_id' => $cours->course_element_id,
                        'room_id'           => $cours->room_id,
                        'date'              => $date,
                        'status'            => 'absent',
                        'on_time'           => 0,
                        'late_type'         => null,
                        'first_entry'       => null,
                        'last_exit'         => null,
                        'total_minutes'     => 0,
                        'created_at'        => $now_ts,
                        'updated_at'        => $now_ts,
                    ]);
                    $totalAbsents++;
                }
            }
        }

        return ['closed' => count($coursTermines), 'absents_marques' => $totalAbsents];
    }

    // =========================================================================
    //  PROFIL ÉTUDIANT
    // =========================================================================
    
    public function getStudentProfile(int $studentId): array
    {
        $student = DB::table('students')
            ->join('departments',    'students.filiere_id',       '=', 'departments.id')
            ->join('academic_years', 'students.academic_year_id', '=', 'academic_years.id')
            ->where('students.id', $studentId)
            ->select(
                'students.id',
                'students.first_name',
                'students.last_name',
                'students.matricule',
                'students.phone',
                'students.fingerprint_status',
                'students.fingerprint_index',
                'students.niveau',
                'departments.name as filiere',
                'academic_years.academic_year as annee',
            )
            ->first();

        if (!$student) return ['success' => false, 'message' => 'Étudiant introuvable'];

        $history = DB::table('attendances')
            ->join('course_elements', 'attendances.course_element_id', '=', 'course_elements.id')
            ->where('attendances.student_id', $studentId)
            ->orderByDesc('attendances.date')
            ->orderByDesc('attendances.first_entry')
            ->select(
                'attendances.date',
                'attendances.status',
                'attendances.on_time',
                'attendances.late_type',
                'attendances.first_entry',
                'attendances.last_exit',
                'attendances.total_minutes',
                'course_elements.name as matiere',
            )
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();

        return [
            'success' => true,
            'data'    => [
                'student' => (array)$student,
                'history' => $history,
            ],
        ];
    }

    // =========================================================================
    //  CLÔTURER UN COURS MANUELLEMENT
    // =========================================================================
    
    public function closeCourseSession(int $courseElementId, string $date): array
    {
        $course = DB::table('course_elements')->where('id', $courseElementId)->first();
        if (!$course) return ['success' => false, 'message' => 'Cours introuvable'];

        $edt = DB::table('emploi_du_temps')
            ->where('course_element_id', $courseElementId)
            ->where('is_active', 1)
            ->first();
        if (!$edt) return ['success' => false, 'message' => 'Emploi du temps introuvable'];

        $yearId = DB::table('academic_years')->orderByDesc('id')->value('id');

        $students = DB::table('students')
            ->where('filiere_id',       $edt->department_id)
            ->where('academic_year_id', $yearId)
            ->pluck('id');

        $absentsMarques  = 0;
        $dejaPresentCount = 0;
        $now_ts = now();

        foreach ($students as $studentId) {
            $existing = DB::table('attendances')
                ->where('student_id',        $studentId)
                ->where('course_element_id', $courseElementId)
                ->whereDate('date',           $date)
                ->first();

            if (!$existing) {
                DB::table('attendances')->insert([
                    'student_id'        => $studentId,
                    'course_element_id' => $courseElementId,
                    'room_id'           => $edt->room_id,
                    'date'              => $date,
                    'status'            => 'absent',
                    'on_time'           => 0,
                    'late_type'         => null,
                    'first_entry'       => null,
                    'last_exit'         => null,
                    'total_minutes'     => 0,
                    'created_at'        => $now_ts,
                    'updated_at'        => $now_ts,
                ]);
                $absentsMarques++;
            } elseif ($existing->status === 'present') {
                $dejaPresentCount++;
            }
        }

        return [
            'success'         => true,
            'message'         => "Cours clôturé — {$absentsMarques} absent(s) marqué(s)",
            'absents_marques' => $absentsMarques,
            'deja_presents'   => $dejaPresentCount,
            'total_etudiants' => $students->count(),
            'cours'           => $course->name,
            'date'            => $date,
        ];
    }

    private function buildScanMessage(string $scanType, array $session, object $student): string
    {
        $name = $student->first_name.' '.$student->last_name;

        if ($scanType === 'entry') {
            return match($session['late_type']) {
                'retard' => "Entrée — en retard — {$name}",
                default  => "Entrée — à l'heure — {$name}",
            };
        }

        $mins = $session['total_minutes'];
        return "Sortie — {$mins} min — {$name}";
    }
}