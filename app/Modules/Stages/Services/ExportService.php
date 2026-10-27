<?php

namespace App\Modules\Stages\Services;

use App\Models\User;
use App\Modules\Stages\Exports\StageSheet;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exports Excel du module Stages. Le fichier est renvoyé en JSON (base64),
 * comme les pièces : jamais intercepté par un gestionnaire de téléchargement.
 */
class ExportService
{
    private const MAX_ROWS = 5000;

    private const PHASES = ['current' => 'En cours', 'upcoming' => 'À venir', 'finished' => 'Terminé'];
    private const DAY_STATUS = [
        'present' => 'Présent', 'half' => 'Demi-journée', 'absent' => 'Absent',
        'justified' => 'Justifié', 'in_progress' => 'En cours',
    ];

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AttendanceService $attendance,
        private readonly StageRequestService $requests,
    ) {}

    /** Demandes de stage (dossiers complets). */
    public function demandes(string $status): array
    {
        $q = StageRequest::query()->orderByDesc('created_at');
        if ($status === 'to_handle') {
            $q->whereIn('status', [StageStatus::SUBMITTED, StageStatus::COUNTER_PROPOSED]);
        } elseif ($status !== 'all') {
            $q->where('status', $status);
        }

        $rows = $q->limit(self::MAX_ROWS)->get()->map(fn (StageRequest $r) => [
            $r->reference, StageStatus::label($r->status), $r->civilite, mb_strtoupper($r->nom), $r->prenoms,
            $this->d($r->date_naissance), $r->lieu_naissance, $r->nationalite, $r->etablissement_origine, $r->filiere_niveau,
            $this->requests->dureeLabel($r->duree_souhaitee), $this->d($r->date_debut_souhaitee), $r->theme_souhaite,
            $r->email, $r->whatsapp, $this->dt($r->created_at),
            $r->service, $this->d($r->date_debut), $this->d($r->date_fin),
            $this->dt($r->accepted_at ?? $r->rejected_at ?? $r->expired_at), $r->rejection_reason,
        ])->all();

        return $this->file('Demandes de stage', 'Demandes de stage', $this->scopeLabel($status), [
            'Référence', 'Statut', 'Civilité', 'Nom', 'Prénoms', 'Né(e) le', 'À', 'Nationalité', 'Établissement', 'Formation',
            'Durée souhaitée', 'Début souhaité', 'Thème souhaité', 'Email', 'WhatsApp', 'Déposée le',
            'Service', 'Début', 'Fin', 'Décision le', 'Motif du rejet',
        ], $rows, 'demandes-de-stage');
    }

    /** Suivi des stagiaires (assiduité, rapports, attestation). */
    public function stagiaires(string $scope, ?string $q, User $viewer): array
    {
        $rows = $this->dashboard->follow($scope, $q, $viewer)->map(fn ($r) => [
            $r['reference'], $r['nom_complet'], $r['etablissement'], $r['formation'], $r['service'],
            $this->d($r['date_debut_reelle'] ?? $r['date_debut']), $this->d($r['date_fin']), self::PHASES[$r['phase']] ?? $r['phase'],
            collect($r['supervisors'])->pluck('name')->implode(', '), $r['team']['name'] ?? '',
            $r['activated'] ? 'Oui' : 'Non',
            $r['taux'], $r['jours_ouvres'], $r['absences'], $r['retards'], $r['justifies'],
            $r['rapports']['deposited'], $r['rapports']['validated'], $r['rapports']['missing'],
            $r['rapports']['final_validated'] ? 'Validé' : 'Non validé',
            $r['attestation']['label'] ?? '', $r['attestation']['numero'] ?? '',
            collect($r['codes'])->pluck('detail')->implode(' '),
            $r['whatsapp'], $r['email'],
        ])->all();

        $scopes = [
            'active' => 'En cours et à venir', 'current' => 'En cours', 'upcoming' => 'À venir', 'finished' => 'Terminés',
            'all' => 'Tous', 'mine' => 'Mes stagiaires', 'unsupervised' => 'Sans superviseur (en cours et à venir)',
        ];

        return $this->file('Suivi des stagiaires', 'Suivi des stagiaires', ($scopes[$scope] ?? $scope) . ($q ? " - recherche « {$q} »" : ''), [
            'Référence', 'Stagiaire', 'Établissement', 'Formation', 'Service', 'Début', 'Fin', 'Phase',
            'Superviseur(s)', 'Binôme / groupe', 'Espace activé',
            'Assiduité (%)', 'Jours ouvrés', 'Absences', 'Retards', 'Justifiés',
            'Rapports déposés', 'Rapports validés', 'Rapports manquants', 'Rapport final',
            'Attestation', 'N° attestation', 'À surveiller', 'WhatsApp', 'Email',
        ], $rows, 'suivi-stagiaires');
    }

    /** Présences jour par jour d'un stagiaire. */
    public function presences(StageRequest $req): array
    {
        $report = $this->attendance->report($req);
        $time = fn ($p) => $p ? CarbonImmutable::parse($p['at'])->setTimezone(BusinessHours::TZ)->format('H:i') : '';
        $rows = collect($report['days'])->reverse()->map(fn ($d) => [
            $this->d($d['day']), self::DAY_STATUS[$d['status']] ?? $d['status'],
            $time($d['punches']['arrivee'] ?? null), $time($d['punches']['pause_debut'] ?? null),
            $time($d['punches']['pause_fin'] ?? null), $time($d['punches']['sortie'] ?? null),
            collect($d['punches'])->filter()->sum('late_minutes') ?: '',
            $d['justification'] ? (InternRequestService::TYPES[$d['justification']] ?? $d['justification']) : '',
        ])->values()->all();
        $s = $report['summary'];

        return $this->file('Présences', 'Présences - ' . trim($req->civilite . ' ' . $req->fullName()) . " ({$req->reference})",
            'Assiduité : ' . ($s['taux'] !== null ? "{$s['taux']} %" : '—') . " · {$s['jours_ouvres']} jour(s) ouvré(s), {$s['absences']} absence(s), {$s['retards']} retard(s), {$s['justifies']} justifié(s)",
            ['Jour', 'État', 'Arrivée', 'Départ en pause', 'Retour de pause', 'Sortie', 'Retard (min)', 'Justification'],
            $rows, 'presences-' . $req->reference);
    }

    /** Registre des attestations. */
    public function attestations(string $status, bool $secretariat): array
    {
        $q = StageAttestation::with('stage')->orderByDesc('id');
        $allowed = $secretariat ? ['a_imprimer', 'imprimee', 'remise'] : array_keys(AttestationService::STATUS_LABELS);
        $q->whereIn('status', $status === 'all' ? $allowed : (array_values(array_intersect([$status], $allowed)) ?: ['__none__']));
        $list  = $q->limit(self::MAX_ROWS)->get();
        $names = User::whereIn('id', $list->pluck('validated_by')->merge($list->pluck('printed_by'))->filter()->unique())
            ->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)]);

        $rows = $list->map(fn (StageAttestation $a) => [
            $a->numero ?? '', trim($a->stage->civilite . ' ' . $a->stage->fullName()), $a->stage->reference,
            AttestationService::KIND_LABELS[$a->kind] ?? $a->kind, AttestationService::STATUS_LABELS[$a->status] ?? $a->status,
            $this->d(($a->content ?? [])['date_debut'] ?? $a->stage->date_debut_reelle ?? $a->stage->date_debut),
            $this->d(($a->content ?? [])['date_fin'] ?? $a->stage->date_fin),
            $this->dt($a->validated_at), $names[$a->validated_by] ?? '',
            $this->dt($a->printed_at), $names[$a->printed_by] ?? '',
            $this->d($a->delivered_on), $a->scan_path ? 'Oui' : 'Non', $a->motif ?? '', $a->cancel_reason ?? '',
        ])->all();

        return $this->file('Attestations', 'Registre des attestations de stage', $status === 'all' ? 'Toutes' : (AttestationService::STATUS_LABELS[$status] ?? $status), [
            'N°', 'Stagiaire', 'Référence', 'Type', 'Statut', 'Début', 'Fin',
            'Validée le', 'Validée par', 'Imprimée le', 'Imprimée par', 'Remise le', 'Scan', 'Motif', "Motif d'annulation",
        ], $rows, 'attestations-de-stage');
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function file(string $sheet, string $title, string $subtitle, array $headings, array $rows, string $slug): array
    {
        $now = CarbonImmutable::now(BusinessHours::TZ);
        $content = Excel::raw(new StageSheet($sheet, "CAP-EPAC - {$title}",
            $subtitle . ' · ' . count($rows) . ' ligne(s) · extrait le ' . $now->format('d/m/Y à H:i'), $headings, $rows), ExcelFormat::XLSX);

        return [
            'name'    => preg_replace('/[^A-Za-z0-9._-]+/', '-', $slug) . '-' . $now->format('Y-m-d') . '.xlsx',
            'mime'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'content' => base64_encode($content),
        ];
    }

    private function scopeLabel(string $status): string
    {
        return match ($status) {
            'all' => 'Toutes les demandes',
            'to_handle' => 'Demandes à traiter',
            default => StageStatus::label($status),
        };
    }

    private function d(mixed $date): string
    {
        return $date ? CarbonImmutable::parse($date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date)->format('d/m/Y') : '';
    }

    private function dt(mixed $date): string
    {
        return $date ? CarbonImmutable::parse($date)->setTimezone(BusinessHours::TZ)->format('d/m/Y H:i') : '';
    }
}
