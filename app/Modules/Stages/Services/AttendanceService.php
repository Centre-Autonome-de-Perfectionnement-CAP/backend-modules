<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Stages\Models\StageAttendance;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\Geo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pointage et assiduité.
 *
 * Quatre pointages par jour ouvré, dans l'ordre : arrivée → (début de
 * pause → fin de pause) → sortie. La pause est facultative.
 *  - arrivée : ouverte 1 h avant l'heure prévue ; en retard au-delà de
 *    15 min après l'heure prévue ;
 *  - début de pause : ouvert 15 min avant l'heure prévue ;
 *  - fin de pause : en retard au-delà de 15 min après l'heure prévue ;
 *  - sortie : possible à tout moment après l'arrivée ; avant l'heure
 *    prévue moins 15 min, elle est marquée « sortie anticipée ».
 * Heure du serveur uniquement. Aucun pointage le week-end, ni avant le
 * début ni après la fin du stage.
 *
 * Assiduité = temps de présence / temps dû, sur les heures prévues
 * (matinée + après-midi, pause exclue). Une journée est « présente » avec
 * arrivée + sortie ; une demi-journée avec seulement la matinée (arrivée +
 * début de pause) ou l'après-midi (fin de pause + sortie). Les retards
 * (arrivée, retour de pause) et les sorties anticipées au-delà de la
 * tolérance réduisent le temps de présence.
 * Une permission accordée n'est jamais pénalisante : les jours d'absence
 * permise sont retirés du calcul ; un retard ou une sortie anticipée permis
 * retire du temps dû la période couverte (jusqu'à l'heure d'arrivée
 * annoncée, ou à partir de l'heure de sortie annoncée).
 */
class AttendanceService
{
    public const KINDS = ['arrivee', 'pause_debut', 'pause_fin', 'sortie'];
    public const LABELS = [
        'arrivee' => 'Arrivée', 'pause_debut' => 'Départ en pause',
        'pause_fin' => 'Retour de pause', 'sortie' => 'Sortie',
    ];
    public const TOLERANCE_MIN = 15;
    private const ARRIVAL_OPENS_BEFORE_MIN = 60;
    private const PAUSE_OPENS_BEFORE_MIN = 15;
    /** Types de permission qui justifient une journée entière. */
    public const JUSTIFYING_TYPES = ['absence', 'maladie', 'evenement_familial', 'autre'];
    /** Permissions d'une partie de journée (heure annoncée : time_from). */
    private const PARTIAL_TYPES = ['retard', 'sortie_anticipee'];

    public function __construct(private readonly StageSettingsService $settings) {}

    /** Premier jour effectif du stage. */
    public function startDay(StageRequest $req): ?CarbonImmutable
    {
        $d = $req->date_debut_reelle ?? $req->date_debut;

        return $d ? CarbonImmutable::parse($d->toDateString(), BusinessHours::TZ) : null;
    }

    /** État du jour : pointages faits, prochains possibles, message. */
    public function today(StageRequest $req, ?CarbonImmutable $now = null): array
    {
        $now   = ($now ?? CarbonImmutable::now())->setTimezone(BusinessHours::TZ);
        $day   = $now->startOfDay();
        $done  = $this->dayPunches($req, $day);
        $state = $this->allowed($req, $now, $done);

        return [
            'day'       => $day->toDateString(),
            'done'      => collect(self::KINDS)->filter(fn ($k) => isset($done[$k]))->map(fn ($k) => [
                'kind' => $k, 'label' => self::LABELS[$k],
                'at' => $done[$k]->at?->toIso8601String(),
                'late_minutes' => $done[$k]->late_minutes, 'early' => $done[$k]->early,
            ])->values()->all(),
            'next'      => array_map(fn ($k) => ['kind' => $k, 'label' => self::LABELS[$k]], $state['kinds']),
            'message'   => $state['message'],
            'horaires'  => $req->horaires,
        ];
    }

    /**
     * Enregistre un pointage (après vérification de l'empreinte par
     * l'appelant). Refus = exception explicite, aucun enregistrement.
     */
    public function punch(StageRequest $req, string $kind, ?array $position, string $source = 'pointage', ?CarbonImmutable $now = null): StageAttendance
    {
        $now  = ($now ?? CarbonImmutable::now())->setTimezone(BusinessHours::TZ);
        $day  = $now->startOfDay();

        $failReason = null;
        try {
            return $this->record($req, $kind, $position, $source, $now, $day, $failReason);
        } catch (BusinessException $e) {
            // Tentative refusée : seule la raison est gardée (diagnostic), hors transaction.
            if ($failReason !== null && $source === 'pointage') {
                $this->fail($req, $failReason);
            }
            throw $e;
        }
    }

    private function record(StageRequest $req, string $kind, ?array $position, string $source, CarbonImmutable $now, CarbonImmutable $day, ?string &$failReason): StageAttendance
    {
        return DB::transaction(function () use ($req, $kind, $position, $source, $now, $day, &$failReason) {
            // Verrou par stagiaire : deux pointages simultanés ne passent pas.
            StageRequest::whereKey($req->id)->lockForUpdate()->first();
            $done  = $this->dayPunches($req, $day);
            $state = $this->allowed($req, $now, $done);

            if (!in_array($kind, $state['kinds'], true)) {
                $failReason = 'not_allowed';
                throw new BusinessException($state['message'] ?? 'Ce pointage n\'est pas possible maintenant.', 'STAGE_PUNCH_NOT_ALLOWED', 422);
            }

            $geo = ['checked' => false, 'distance' => null];
            if ($position !== null) {
                $geo = Geo::check(
                    $this->settings->get('lieu_position'), (int) $this->settings->get('lieu_rayon'),
                    (float) $position['lat'], (float) $position['lng'], (float) $position['accuracy'],
                );
                if (!$geo['ok']) {
                    $failReason = $geo['reason'];
                    throw new BusinessException($geo['message'], 'STAGE_PUNCH_POSITION', 422);
                }
            }

            [$late, $early] = $this->lateness($req, $kind, $now, $source);

            return StageAttendance::create([
                'stage_request_id' => $req->id,
                'day'              => $day->toDateString(),
                'kind'             => $kind,
                'at'               => $now->utc(),
                'late_minutes'     => $late,
                'early'            => $early,
                'distance_m'       => $geo['distance'],
                'accuracy_m'       => $position ? (int) min(65535, round((float) $position['accuracy'])) : null,
                'position_checked' => (bool) $geo['checked'],
                'source'           => $source,
            ]);
        });
    }

    public function fail(StageRequest $req, string $reason): void
    {
        DB::table('stage_attendance_failures')->insert([
            'stage_request_id' => $req->id, 'reason' => mb_substr($reason, 0, 40), 'created_at' => now(),
        ]);
    }

    /**
     * Assiduité de la période écoulée.
     *
     * @return array{days: array, summary: array}
     */
    public function report(StageRequest $req, ?CarbonImmutable $until = null): array
    {
        $start = $this->startDay($req);
        // Stagiaire arrivé avant le système : les présences ne comptent qu'à
        // partir de son entrée dans le système (rien avant ne peut être pointé).
        if ($start && $req->attendance_from) {
            $start = $start->max(CarbonImmutable::parse($req->attendance_from->toDateString(), BusinessHours::TZ));
        }
        $today = BusinessHours::today();
        $empty = ['days' => [], 'summary' => $this->summary([])];
        if (!$start || !$req->date_fin) {
            return $empty;
        }
        $end = CarbonImmutable::parse($req->date_fin->toDateString(), BusinessHours::TZ);
        $end = $until ? $until->min($end) : $today->min($end);
        if ($end->lessThan($start)) {
            return $empty;
        }

        $punches = StageAttendance::where('stage_request_id', $req->id)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->get()->groupBy(fn ($p) => $p->day->toDateString());
        [$justified, $partial] = $this->permissions($req, $start, $end);

        $days = [];
        $t = ['worked' => 0, 'present' => 0, 'half' => 0, 'absent' => 0, 'justified' => 0, 'due' => 0, 'done' => 0,
            'late' => 0, 'late_ok' => 0, 'late_min' => 0, 'early' => 0, 'early_ok' => 0, 'early_min' => 0];
        for ($d = $start; $d->lessThanOrEqualTo($end); $d = $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            $key  = $d->toDateString();
            $p    = ($punches[$key] ?? collect())->keyBy('kind');
            $h    = $this->hours($req, $d);
            // La journée en cours ne compte qu'une fois l'heure de sortie passée.
            $isToday = $key === $today->toDateString() && CarbonImmutable::now(BusinessHours::TZ)->lessThan($h['sortie']);
            $value = $this->dayValue($p);
            $time  = $this->dayTime($p, $h, $partial[$key] ?? []);

            if (isset($justified[$key]) && $value < 1) {
                $status = 'justified';
                $t['justified']++;
            } elseif ($value === 1.0) {
                $status = 'present';
            } elseif ($value === 0.5) {
                $status = 'half';
            } elseif ($isToday) {
                $status = 'in_progress';
            } else {
                $status = 'absent';
            }

            if ($status !== 'justified' && !($isToday && $value < 1)) {
                $t['worked']++;
                $t['present'] += $value === 1.0 ? 1 : 0;
                $t['half']    += $value === 0.5 ? 1 : 0;
                $t['absent']  += $value === 0.0 ? 1 : 0;
                $t['due']     += $time['due'];
                $t['done']    += $time['present'];
            }
            foreach (['late', 'early'] as $k) {
                foreach ($time[$k] as $item) {
                    $item['justified'] ? $t["{$k}_ok"]++ : $t[$k]++;
                    $t["{$k}_min"] += $item['justified'] ? 0 : $item['minutes'];
                }
            }

            $days[] = [
                'day'    => $key,
                'status' => $status,
                'justification' => $justified[$key] ?? null,
                'justification_label' => isset($justified[$key]) ? (InternRequestService::TYPES[$justified[$key]] ?? null) : null,
                'retards'  => $time['late'],
                'sortie_anticipee' => $time['early'][0] ?? null,
                'permissions' => array_values(array_map(fn ($perm) => $perm['type'], $partial[$key] ?? [])),
                'minutes_dues' => $time['due'], 'minutes_presence' => $time['present'],
                'punches' => collect(self::KINDS)->mapWithKeys(fn ($k) => [$k => isset($p[$k]) ? [
                    'at' => $p[$k]->at?->toIso8601String(), 'late_minutes' => $p[$k]->late_minutes,
                    'early' => $p[$k]->early, 'distance_m' => $p[$k]->distance_m,
                ] : null])->all(),
            ];
        }

        return ['days' => array_reverse($days), 'summary' => $this->summary($t)];
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** @param array<string, int> $t totaux de report() */
    private function summary(array $t): array
    {
        $n = fn (string $k) => (int) ($t[$k] ?? 0);

        return [
            'jours_ouvres'  => $n('worked'),
            'presents'      => $n('present'),
            'demi_journees' => $n('half'),
            'absences'      => $n('absent'),
            'justifies'     => $n('justified'),
            // Retards (arrivée, retour de pause) et sorties anticipées :
            // non justifiés (comptés) et couverts par une permission.
            'retards'                   => $n('late'),
            'minutes_retard'            => $n('late_min'),
            'retards_justifies'         => $n('late_ok'),
            'sorties_anticipees'        => $n('early'),
            'minutes_sortie_anticipee'  => $n('early_min'),
            'sorties_justifiees'        => $n('early_ok'),
            'heures_dues'     => round($n('due') / 60, 1),
            'heures_presence' => round($n('done') / 60, 1),
            'taux'            => $n('due') > 0 ? round($n('done') / $n('due') * 100, 1) : null,
        ];
    }

    /**
     * Temps dû et temps de présence d'une journée (minutes), sur les heures
     * prévues ; retards et sortie anticipée relevés.
     *
     * @param  array<string, array{type: string, time: ?string}>  $perms permissions partielles du jour
     * @return array{due: int, present: int, late: array, early: array}
     */
    private function dayTime($p, array $h, array $perms): array
    {
        $min = fn (CarbonImmutable $x) => $x->hour * 60 + $x->minute;
        $at  = fn (string $k) => isset($p[$k]) && $p[$k]->at ? $min(CarbonImmutable::instance($p[$k]->at)->setTimezone(BusinessHours::TZ)) : null;
        [$arr, $pd, $pf, $so] = [$min($h['arrivee']), $min($h['pause_debut']), $min($h['pause_fin']), $min($h['sortie'])];
        $work = [[$arr, $pd], [$pf, $so]];

        // Pointages ramenés à l'heure prévue dans la tolérance.
        $tol = self::TOLERANCE_MIN;
        $in  = fn (?int $v, int $ref) => $v !== null && $v > $ref + $tol ? $v : ($v === null ? null : min($v, $ref));
        $out = fn (?int $v, int $ref) => $v !== null && $v < $ref - $tol ? $v : ($v === null ? null : max($v, $ref));
        // Premier jour (activation) : jamais en retard.
        $a = isset($p['arrivee']) && $p['arrivee']->source === 'activation' ? $arr : $in($at('arrivee'), $arr);
        $b = $out($at('pause_debut'), $pd);
        $c = $in($at('pause_fin'), $pf);
        $e = $out($at('sortie'), $so);

        $present = [];
        if ($a !== null && $e !== null) {
            $present = $b !== null && $c !== null ? [[$a, $b], [$c, $e]] : [[$a, $e]];
        } elseif ($a !== null && $b !== null) {
            $present = [[$a, $b]];
        } elseif ($c !== null && $e !== null) {
            $present = [[$c, $e]];
        }

        // Période couverte par une permission (neutre) : jusqu'à l'heure
        // d'arrivée annoncée, ou à partir de l'heure de sortie annoncée.
        $covered = [];
        $hm = fn (?string $v) => $v ? (int) substr($v, 0, 2) * 60 + (int) substr($v, 3, 2) : null;
        $lateUntil = $hm($perms['retard']['time'] ?? null);
        $earlyFrom = $hm($perms['sortie_anticipee']['time'] ?? null);
        if ($lateUntil !== null) {
            $covered[] = [$arr, $lateUntil + $tol];
        }
        if ($earlyFrom !== null) {
            $covered[] = [$earlyFrom - $tol, $so];
        }

        $due  = $this->span($this->minus($work, $covered));
        $done = $this->span($this->minus($this->intersect($present, $work), $covered));

        // Retards et sortie anticipée. Avec une permission, seul le dépassement
        // de l'heure annoncée compte.
        $late = [];
        foreach ([['arrivee', $a, $arr, $lateUntil], ['pause_fin', $c, $pf, null]] as [$kind, $v, $ref, $allowed]) {
            if ($v !== null && $v > $ref) {
                $ok = $allowed !== null && $v <= $allowed + $tol;
                $late[] = ['pointage' => $kind, 'minutes' => $allowed !== null && !$ok ? $v - $allowed : $v - $ref, 'justified' => $ok];
            }
        }
        $early = [];
        if ($e !== null && $e < $so) {
            $ok = $earlyFrom !== null && $e >= $earlyFrom - $tol;
            $early[] = ['minutes' => $earlyFrom !== null && !$ok ? $earlyFrom - $e : $so - $e, 'justified' => $ok];
        }

        return ['due' => $due, 'present' => min($done, $due), 'late' => $late, 'early' => $early];
    }

    /** @param list<array{0:int,1:int}> $ranges */
    private function span(array $ranges): int
    {
        return array_sum(array_map(fn ($r) => max(0, $r[1] - $r[0]), $ranges));
    }

    private function intersect(array $a, array $b): array
    {
        $out = [];
        foreach ($a as [$s1, $e1]) {
            foreach ($b as [$s2, $e2]) {
                if (min($e1, $e2) > max($s1, $s2)) {
                    $out[] = [max($s1, $s2), min($e1, $e2)];
                }
            }
        }

        return $out;
    }

    private function minus(array $ranges, array $cut): array
    {
        foreach ($cut as [$cs, $ce]) {
            $next = [];
            foreach ($ranges as [$s, $e]) {
                if ($ce <= $s || $cs >= $e) {
                    $next[] = [$s, $e];
                    continue;
                }
                if ($cs > $s) {
                    $next[] = [$s, $cs];
                }
                if ($ce < $e) {
                    $next[] = [$ce, $e];
                }
            }
            $ranges = $next;
        }

        return $ranges;
    }

    private function dayValue($p): float
    {
        if (isset($p['arrivee']) && isset($p['sortie'])) {
            return 1.0;
        }
        if ((isset($p['arrivee']) && isset($p['pause_debut'])) || (isset($p['pause_fin']) && isset($p['sortie']))) {
            return 0.5;
        }

        return 0.0;
    }

    /**
     * Permissions accordées sur la période : jours entiers justifiés, et
     * permissions d'une partie de journée (retard, sortie anticipée).
     *
     * @return array{0: array<string, string>, 1: array<string, array<string, array{type: string, time: ?string}>>}
     */
    private function permissions(StageRequest $req, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $full = $partial = [];
        $perms = DB::table('stage_intern_requests')
            ->where('stage_request_id', $req->id)->where('kind', 'permission')->where('status', 'approved')
            ->whereIn('type', [...self::JUSTIFYING_TYPES, ...self::PARTIAL_TYPES])
            ->where('date_from', '<=', $end->toDateString())->where('date_to', '>=', $start->toDateString())
            ->get(['type', 'date_from', 'date_to', 'time_from']);
        foreach ($perms as $perm) {
            for ($d = CarbonImmutable::parse($perm->date_from); $d->lessThanOrEqualTo(CarbonImmutable::parse($perm->date_to)); $d = $d->addDay()) {
                if (in_array($perm->type, self::PARTIAL_TYPES, true)) {
                    $partial[$d->toDateString()][$perm->type] = ['type' => $perm->type, 'time' => $perm->time_from];
                } else {
                    $full[$d->toDateString()] = $perm->type;
                }
            }
        }

        return [$full, $partial];
    }

    /** @return array<string, StageAttendance> */
    private function dayPunches(StageRequest $req, CarbonImmutable $day): array
    {
        return StageAttendance::where('stage_request_id', $req->id)->where('day', $day->toDateString())
            ->get()->keyBy('kind')->all();
    }

    /** @return array{kinds: string[], message: ?string} */
    private function allowed(StageRequest $req, CarbonImmutable $now, array $done): array
    {
        $start = $this->startDay($req);
        $day   = $now->startOfDay();

        if (!$start || !$req->date_fin) {
            return ['kinds' => [], 'message' => 'Votre stage n\'a pas encore de dates.'];
        }
        if ($day->lessThan($start)) {
            return ['kinds' => [], 'message' => 'Votre stage commence le ' . $start->locale('fr')->isoFormat('dddd D MMMM') . '.'];
        }
        if ($day->toDateString() > $req->date_fin->toDateString()) {
            return ['kinds' => [], 'message' => 'Votre stage est terminé.'];
        }
        if ($day->isWeekend()) {
            return ['kinds' => [], 'message' => 'Pas de pointage le week-end.'];
        }
        if (isset($done['sortie'])) {
            return ['kinds' => [], 'message' => 'Journée terminée. À demain !'];
        }

        $h = $this->hours($req, $day);
        if (!isset($done['arrivee'])) {
            $opens = $h['arrivee']->subMinutes(self::ARRIVAL_OPENS_BEFORE_MIN);
            return $now->lessThan($opens)
                ? ['kinds' => [], 'message' => 'Le pointage d\'arrivée ouvre à ' . $opens->format('H\hi') . '.']
                : ['kinds' => ['arrivee'], 'message' => null];
        }

        $kinds = [];
        if (!isset($done['pause_debut']) && !isset($done['pause_fin'])
            && $now->greaterThanOrEqualTo($h['pause_debut']->subMinutes(self::PAUSE_OPENS_BEFORE_MIN))) {
            $kinds[] = 'pause_debut';
        }
        if (isset($done['pause_debut']) && !isset($done['pause_fin'])) {
            $kinds[] = 'pause_fin';
        }
        $kinds[] = 'sortie';

        return ['kinds' => $kinds, 'message' => null];
    }

    /** @return array{0: int, 1: bool} minutes de retard, sortie anticipée */
    private function lateness(StageRequest $req, string $kind, CarbonImmutable $now, string $source): array
    {
        if ($source === 'activation') {
            return [0, false]; // premier jour : jamais en retard
        }
        $h = $this->hours($req, $now->startOfDay());
        $minutes = fn (CarbonImmutable $ref) => (int) floor(($now->getTimestamp() - $ref->getTimestamp()) / 60);

        return match ($kind) {
            'arrivee'   => [($m = $minutes($h['arrivee'])) > self::TOLERANCE_MIN ? $m : 0, false],
            'pause_fin' => [($m = $minutes($h['pause_fin'])) > self::TOLERANCE_MIN ? $m : 0, false],
            'sortie'    => [0, $now->lessThan($h['sortie']->subMinutes(self::TOLERANCE_MIN))],
            default     => [0, false],
        };
    }

    /** @return array<string, CarbonImmutable> */
    private function hours(StageRequest $req, CarbonImmutable $day): array
    {
        $h = $req->horaires ?: $this->settings->get('default_horaires');
        $at = function (string $hm) use ($day) {
            [$hh, $mm] = array_map('intval', explode(':', $hm) + [0, 0]);
            return $day->setTime($hh, $mm);
        };

        return [
            'arrivee'     => $at($h['arrivee'] ?? '08:00'),
            'pause_debut' => $at($h['pause_debut'] ?? '13:00'),
            'pause_fin'   => $at($h['pause_fin'] ?? '15:00'),
            'sortie'      => $at($h['sortie'] ?? '18:00'),
        ];
    }
}
