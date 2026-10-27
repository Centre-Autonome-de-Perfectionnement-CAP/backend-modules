<?php

namespace App\Modules\Stages\Services;

use App\Models\User;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageInternRequest;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord et suivi global des stagiaires.
 *
 *  - responsable : tout le module ;
 *  - superviseur : ses stagiaires (les chiffres des demandes restent globaux).
 */
class DashboardService
{
    /** En dessous de ce nombre de jours ouvrés, l'assiduité n'est pas encore significative. */
    private const MIN_DAYS_FOR_RATE = 5;
    private const ENDING_SOON_DAYS = 14;

    /** Codes d'attention d'un stagiaire, du plus urgent au moins urgent. */
    public const CODES = ['non_active', 'assiduite', 'fin_proche', 'rapports', 'sans_superviseur'];

    public function __construct(
        private readonly SupervisionService $supervision,
        private readonly AttendanceService $attendance,
        private readonly ReportService $reports,
        private readonly StageSettingsService $settings,
    ) {}

    public function overview(User $viewer): array
    {
        $lead  = StageAccess::isLead($viewer);
        $today = BusinessHours::today();
        $t     = $today->toDateString();
        $seuil = (int) $this->settings->get('seuil_assiduite');

        $accepted = fn () => StageRequest::where('status', StageStatus::ACCEPTED);
        $mineIds  = DB::table('stage_supervisions')->where('user_id', $viewer->id)->pluck('stage_request_id');
        $scoped   = fn () => $lead ? $accepted() : $accepted()->whereIn('id', $mineIds);

        // Stagiaires en cours (périmètre du lecteur), avec leur suivi.
        $current = $this->rows($scoped()->where('date_debut', '<=', $t)->where('date_fin', '>=', $t)->orderBy('date_fin')->get(), $viewer);

        // Une ligne par stagiaire, avec ses codes d'attention (du plus urgent au moins urgent).
        $alerts = $current->filter(fn ($r) => $r['codes'] !== [])
            ->sortBy(fn ($r) => [array_search($r['codes'][0]['code'], self::CODES, true), $r['nom_complet']])
            ->map(fn ($r) => ['stage_id' => $r['id'], 'reference' => $r['reference'], 'nom' => $r['nom_complet'], 'codes' => $r['codes']])
            ->values()->all();

        $rated = $current->filter(fn ($r) => $r['taux'] !== null && $r['jours_ouvres'] >= self::MIN_DAYS_FOR_RATE);
        $statusCounts = StageRequest::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');
        $reportScope = StageReport::whereIn('status', ['submitted', 'supervisor_approved', 'supervisor_rejected']);
        $requestScope = StageInternRequest::where('status', 'pending');
        if (!$lead) {
            $reportScope->whereIn('stage_request_id', $mineIds);
            $requestScope->whereIn('stage_request_id', $mineIds);
        }

        return [
            'lead'          => $lead,
            'today'         => $t,
            'seuil'         => $seuil,
            'requests_open' => (bool) $this->settings->get('requests_open'),
            'demandes'      => [
                'a_traiter'  => (int) (($statusCounts[StageStatus::SUBMITTED] ?? 0) + ($statusCounts[StageStatus::COUNTER_PROPOSED] ?? 0)),
                'proposition'=> (int) ($statusCounts[StageStatus::PROPOSAL_SENT] ?? 0),
                'acceptees'  => (int) ($statusCounts[StageStatus::ACCEPTED] ?? 0),
                'non_retenues' => (int) (($statusCounts[StageStatus::REJECTED] ?? 0) + ($statusCounts[StageStatus::EXPIRED] ?? 0) + ($statusCounts[StageStatus::WITHDRAWN] ?? 0)),
            ],
            'stagiaires'    => [
                'en_cours'   => $current->count(),
                'a_venir'    => $scoped()->where('date_debut', '>', $t)->count(),
                'fin_proche' => $current->where('fin_proche', true)->count(),
                'termines_annee' => $scoped()->where('date_fin', '<', $t)->where('date_fin', '>=', $today->startOfYear()->toDateString())->count(),
                'sans_superviseur' => $lead ? $accepted()->where('date_fin', '>=', $t)->whereNotIn('id', DB::table('stage_supervisions')->select('stage_request_id'))->count() : null,
            ],
            'a_faire'       => [
                'rapports'      => $reportScope->count(),
                'demandes_stagiaires' => $requestScope->count(),
                'attestations'  => $lead
                    ? StageAttestation::where('status', 'a_valider')->count()
                    : null,
            ],
            'assiduite'     => [
                'moyenne'    => $rated->isEmpty() ? null : round($rated->avg('taux'), 1),
                'sous_seuil' => $rated->filter(fn ($r) => $r['taux'] < $seuil)->count(),
                'mesures'    => $rated->count(),
            ],
            'alertes'       => array_slice($alerts, 0, 40),
            'alertes_total' => count($alerts),
            'mensuel'       => $lead ? $this->monthly($today) : [],
        ];
    }

    /** Suivi global : une ligne par stagiaire, avec assiduité, rapports et attestation. */
    public function follow(string $scope, ?string $q, User $viewer): Collection
    {
        $base = $this->supervision->interns($scope, $q, $viewer);
        if ($base->isEmpty()) {
            return collect();
        }
        $models = StageRequest::whereIn('id', $base->pluck('id'))->get()->keyBy('id');

        return $this->rows($base->pluck('id')->map(fn ($id) => $models[$id])->filter()->values(), $viewer);
    }

    /** Lignes de suivi pour une liste de stages. */
    public function rows(Collection $stages, User $viewer): Collection
    {
        if ($stages->isEmpty()) {
            return collect();
        }
        $base = $this->supervision->decorate($stages, $viewer)->keyBy('id');
        $att = StageAttestation::whereIn('stage_request_id', $stages->pluck('id'))->where('status', '!=', 'annulee')
            ->orderByDesc('id')->get()->unique('stage_request_id')->keyBy('stage_request_id');
        $soon  = BusinessHours::today()->addDays(self::ENDING_SOON_DAYS)->toDateString();
        $t     = BusinessHours::today()->toDateString();
        $seuil = (int) $this->settings->get('seuil_assiduite');

        return $stages->map(function (StageRequest $req) use ($base, $att, $soon, $t, $seuil) {
            $summary = $req->activated_at ? $this->attendance->report($req)['summary'] : null;
            $reports = $req->activated_at ? $this->reports->overview($req)['stats'] : null;
            $a = $att[$req->id] ?? null;

            $row = $base[$req->id] + [
                'activated'    => (bool) $req->activated_at,
                'date_debut_reelle' => $req->date_debut_reelle?->toDateString(),
                'fin_proche'   => $req->date_fin && $req->date_fin->toDateString() >= $t && $req->date_fin->toDateString() <= $soon,
                'taux'         => $summary['taux'] ?? null,
                'jours_ouvres' => $summary['jours_ouvres'] ?? 0,
                'absences'     => $summary['absences'] ?? 0,
                'retards'      => $summary['retards'] ?? 0,
                'sorties_anticipees' => $summary['sorties_anticipees'] ?? 0,
                'justifies'    => $summary['justifies'] ?? 0,
                'rapports'     => [
                    'deposited' => $reports['deposited'] ?? 0,
                    'validated' => $reports['validated'] ?? 0,
                    'missing'   => $reports['missing'] ?? 0,
                    'final_validated' => (bool) ($reports['final_validated'] ?? false),
                ],
                'attestation'  => $a ? [
                    'id' => $a->id, 'status' => $a->status,
                    'label' => AttestationService::STATUS_LABELS[$a->status] ?? $a->status, 'numero' => $a->numero,
                ] : null,
            ];

            return $row + ['codes' => $this->codes($row, $seuil)];
        })->values();
    }

    /**
     * Ce qui demande l'attention sur un stagiaire (liste, tableau de bord) :
     * une seule règle pour tout le module.
     *
     * @return list<array{code: string, detail: string}>
     */
    private function codes(array $r, int $seuil): array
    {
        $found = [];
        if ($r['phase'] === 'current') {
            if (!$r['activated']) {
                $found['non_active'] = 'Présence jamais confirmée (espace non activé).';
            } else {
                if ($r['taux'] !== null && $r['jours_ouvres'] >= self::MIN_DAYS_FOR_RATE && $r['taux'] < $seuil) {
                    $found['assiduite'] = "Assiduité de {$r['taux']} % (minimum {$seuil} %).";
                }
                if ($r['fin_proche'] && !$r['rapports']['final_validated']) {
                    $found['fin_proche'] = 'Fin le ' . $this->fr($r['date_fin']) . ' : rapport final pas encore validé.';
                }
                if ($r['rapports']['missing'] > 0) {
                    $found['rapports'] = $r['rapports']['missing'] . ' rapport(s) non déposé(s).';
                }
            }
        }
        if ($r['phase'] !== 'finished' && count($r['supervisors']) === 0) {
            $found['sans_superviseur'] = 'Aucun superviseur.';
        }

        return array_map(fn ($code) => ['code' => $code, 'detail' => $found[$code]],
            array_values(array_filter(self::CODES, fn ($c) => isset($found[$c]))));
    }

    /** Demandes reçues et acceptées, 12 derniers mois. */
    private function monthly(CarbonImmutable $today): array
    {
        $from = $today->startOfMonth()->subMonths(11);
        $received = StageRequest::where('created_at', '>=', $from->utc())->get(['created_at', 'accepted_at', 'status']);
        $out = [];
        for ($m = $from; $m->lessThanOrEqualTo($today); $m = $m->addMonth()) {
            $key = $m->format('Y-m');
            $out[$key] = ['mois' => $key, 'recues' => 0, 'acceptees' => 0];
        }
        foreach ($received as $r) {
            $k = CarbonImmutable::parse($r->created_at)->setTimezone(BusinessHours::TZ)->format('Y-m');
            if (isset($out[$k])) {
                $out[$k]['recues']++;
                $out[$k]['acceptees'] += $r->status === StageStatus::ACCEPTED ? 1 : 0;
            }
        }

        return array_values($out);
    }

    private function fr(?string $date): string
    {
        return $date ? CarbonImmutable::parse($date)->locale('fr')->isoFormat('D MMMM') : '-';
    }
}
