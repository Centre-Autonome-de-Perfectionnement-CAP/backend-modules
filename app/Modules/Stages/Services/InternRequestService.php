<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageInternRequest;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demandes du stagiaire : permissions (absence, retard, sortie anticipée,
 * maladie, événement familial, autre), prolongation, raccourcissement.
 *
 *  - une permission peut être demandée à l'avance, ou après coup dans un
 *    délai de 3 jours ouvrés (réglable) ;
 *  - n'importe quel superviseur ou le responsable la valide ;
 *  - une permission accordée justifie les jours concernés ;
 *  - une prolongation / un raccourcissement accordé modifie la date de fin
 *    et émet une note rectificative.
 */
class InternRequestService
{
    public const TYPES = [
        'absence'            => 'Absence',
        'retard'             => 'Retard prévu',
        'sortie_anticipee'   => 'Sortie anticipée',
        'maladie'            => 'Maladie',
        'evenement_familial' => 'Événement familial',
        'autre'              => 'Autre',
    ];
    public const KIND_LABELS = [
        'permission' => 'Permission', 'prolongation' => 'Prolongation', 'raccourcissement' => 'Raccourcissement',
    ];

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageSettingsService $settings,
        private readonly StageRequestService $requests,
    ) {}

    public function create(StageRequest $req, array $data, ?UploadedFile $file): StageInternRequest
    {
        $kind  = $data['kind'];
        $today = BusinessHours::today();
        $row   = ['stage_request_id' => $req->id, 'kind' => $kind, 'motif' => $data['motif'], 'status' => 'pending'];

        if ($kind === 'permission') {
            $from = CarbonImmutable::parse($data['date_from']);
            $to   = CarbonImmutable::parse($data['date_to'] ?? $data['date_from']);
            if ($to->lessThan($from)) {
                throw new BusinessException('La date de fin doit être après la date de début.', 'STAGE_PERM_DATES', 422);
            }
            $earliest = $this->workingDaysBefore($today, (int) $this->settings->get('permission_delay_days'));
            if ($from->lessThan($earliest)) {
                throw new BusinessException('Une permission peut être demandée au plus ' . $this->settings->get('permission_delay_days') . ' jours ouvrés après coup.', 'STAGE_PERM_TOO_LATE', 422);
            }
            $start = $req->date_debut_reelle ?? $req->date_debut;
            if ($from->toDateString() < $start->toDateString() || $to->toDateString() > $req->date_fin->toDateString()) {
                throw new BusinessException('Les dates doivent être comprises dans la période de votre stage.', 'STAGE_PERM_OUT_OF_STAGE', 422);
            }
            if (in_array($data['type'], ['retard', 'sortie_anticipee'], true)) {
                if (!$from->equalTo($to)) {
                    throw new BusinessException('Un retard ou une sortie anticipée concerne un seul jour.', 'STAGE_PERM_ONE_DAY', 422);
                }
                if (empty($data['time_from'])) {
                    throw new BusinessException('Indiquez l\'heure.', 'STAGE_PERM_TIME', 422);
                }
            }
            $row += [
                'type' => $data['type'], 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString(),
                'time_from' => $data['time_from'] ?? null, 'time_to' => $data['time_to'] ?? null,
            ];
        } else {
            $new = CarbonImmutable::parse($data['new_date_fin']);
            $end = CarbonImmutable::parse($req->date_fin->toDateString());
            if ($kind === 'prolongation' && !$new->greaterThan($end)) {
                throw new BusinessException('Pour une prolongation, la nouvelle date de fin doit être après la date actuelle (' . $this->requests->frDate($end) . ').', 'STAGE_EXT_DATE', 422);
            }
            if ($kind === 'raccourcissement' && (!$new->lessThan($end) || $new->lessThan($today))) {
                throw new BusinessException('Pour un raccourcissement, la nouvelle date de fin doit être avant la date actuelle et pas dans le passé.', 'STAGE_SHORT_DATE', 422);
            }
            if (StageInternRequest::where('stage_request_id', $req->id)->whereIn('kind', ['prolongation', 'raccourcissement'])->where('status', 'pending')->exists()) {
                throw new BusinessException('Une demande de changement de date de fin est déjà en attente.', 'STAGE_EXT_PENDING', 409);
            }
            $row['new_date_fin'] = $new->toDateString();
        }

        if ($file) {
            $row['file_path'] = $file->storeAs(
                "stages/{$req->reference}/demandes",
                now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . strtolower($file->guessExtension() ?: 'bin'),
                StageRequestService::DISK,
            ) ?: null;
        }

        $item = StageInternRequest::create($row);
        $this->log($req, 'intern_request', null, self::KIND_LABELS[$kind] . ($kind === 'permission' ? ' - ' . self::TYPES[$row['type']] : ''));

        $this->notifier->toStaff(
            $this->recipients($req),
            $kind === 'permission' ? 'Demande de permission' : 'Demande de ' . mb_strtolower(self::KIND_LABELS[$kind]),
            ['Un stagiaire vous adresse une demande à traiter.'],
            ['Stagiaire' => $req->fullName()] + $this->fields($item) + ['Motif' => $item->motif],
            "stage-demande-stagiaire:{$req->reference}",
            rtrim((string) config('app.url'), '/') . '/services/stages/demandes-stagiaires',
            $req->reference,
        );

        return $item;
    }

    public function cancel(StageRequest $req, int $id): void
    {
        $item = StageInternRequest::where('stage_request_id', $req->id)->where('id', $id)->first();
        if (!$item || $item->status !== 'pending') {
            throw new BusinessException('Seule une demande en attente peut être annulée.', 'STAGE_IREQ_NOT_PENDING', 409);
        }
        $item->update(['status' => 'cancelled']);
    }

    public function decide(int $id, string $decision, ?string $comment, User $by): StageInternRequest
    {
        $item = DB::transaction(function () use ($id, $decision, $comment, $by) {
            $item = StageInternRequest::with('stage')->lockForUpdate()->find($id);
            if (!$item) {
                throw new BusinessException('Demande introuvable.', 'STAGE_IREQ_NOT_FOUND', 404);
            }
            if ($item->status !== 'pending') {
                throw new BusinessException('Cette demande a déjà été traitée.', 'STAGE_IREQ_DONE', 409);
            }
            if ($decision === 'rejected' && !$comment) {
                throw new BusinessException('Indiquez le motif du refus.', 'STAGE_IREQ_COMMENT', 422);
            }
            $item->update([
                'status' => $decision, 'decided_by' => $by->id, 'decided_at' => now(), 'decision_comment' => $comment,
            ]);

            return $item;
        });

        $req = $item->stage;
        $label = self::KIND_LABELS[$item->kind] . ($item->kind === 'permission' ? ' - ' . self::TYPES[$item->type] : '');
        $this->log($req, $decision === 'approved' ? 'intern_request_approved' : 'intern_request_rejected', $by, $label . ($comment ? " : {$comment}" : ''));

        // Changement de date de fin accordé : note rectificative automatique.
        if ($decision === 'approved' && $item->kind !== 'permission') {
            $terms = $this->requests->currentTerms($req);
            $terms['date_fin'] = $item->new_date_fin->toDateString();
            $this->requests->rectify($req, $terms, self::KIND_LABELS[$item->kind] . ' demandée par le stagiaire : ' . $item->motif, $by);
        }

        $this->notifier->toDemandeur(
            $req->fresh(),
            $decision === 'approved' ? 'Demande acceptée' : 'Demande refusée',
            [$decision === 'approved' ? 'Votre demande a été acceptée.' : 'Votre demande n\'a pas été acceptée.'],
            ['Demande' => $label] + $this->fields($item),
            highlight: $comment,
            highlightTitle: 'Commentaire',
            context: 'stage-demande-decision',
        );

        if (!StageAccess::isLead($by)) {
            $who = trim(($by->first_name ?? '') . ' ' . ($by->last_name ?? ''));
            $this->notifier->toManagers($req, 'Action d\'un superviseur', [
                "{$who} (superviseur) a " . ($decision === 'approved' ? 'accepté' : 'refusé') . " une demande du stagiaire.",
            ], ['Demande' => $label, 'Stagiaire' => $req->fullName()], 'stage-superviseur');
        }

        return $item->fresh();
    }

    public function present(StageInternRequest $i, bool $withStage = false): array
    {
        return [
            'id'           => $i->id,
            'kind'         => $i->kind,
            'kind_label'   => self::KIND_LABELS[$i->kind] ?? $i->kind,
            'type'         => $i->type,
            'type_label'   => $i->type ? (self::TYPES[$i->type] ?? $i->type) : null,
            'date_from'    => $i->date_from?->toDateString(),
            'date_to'      => $i->date_to?->toDateString(),
            'time_from'    => $i->time_from,
            'time_to'      => $i->time_to,
            'new_date_fin' => $i->new_date_fin?->toDateString(),
            'motif'        => $i->motif,
            'has_file'     => (bool) $i->file_path,
            'status'       => $i->status,
            'decision_comment' => $i->decision_comment,
            'decided_at'   => $i->decided_at?->toIso8601String(),
            'created_at'   => $i->created_at?->toIso8601String(),
        ] + ($withStage ? ['stage' => [
            'id' => $i->stage->id, 'reference' => $i->stage->reference,
            'nom_complet' => trim($i->stage->civilite . ' ' . $i->stage->fullName()),
            'date_fin' => $i->stage->date_fin?->toDateString(),
        ]] : []);
    }

    public function filePath(StageInternRequest $i): ?string
    {
        return $i->file_path && Storage::disk(StageRequestService::DISK)->exists($i->file_path) ? $i->file_path : null;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function fields(StageInternRequest $i): array
    {
        if ($i->kind !== 'permission') {
            return ['Nouvelle fin' => $this->requests->frDate($i->new_date_fin)];
        }
        $period = $i->date_from->equalTo($i->date_to)
            ? $this->requests->frDate($i->date_from)
            : 'du ' . $this->requests->frDate($i->date_from) . ' au ' . $this->requests->frDate($i->date_to);

        return [
            'Type'   => self::TYPES[$i->type] ?? $i->type,
            'Date'   => $period,
            'Heure'  => $i->time_from ? str_replace(':', 'h', $i->time_from) . ($i->time_to ? ' - ' . str_replace(':', 'h', $i->time_to) : '') : null,
        ];
    }

    /** Superviseurs du stagiaire + responsables de division. */
    private function recipients(StageRequest $req)
    {
        $sup = DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.stage_request_id', $req->id)->whereNull('u.deleted_at')
            ->get(['u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name']);

        return $sup->concat(\App\Modules\Stages\Support\StageAccess::managers());
    }

    private function workingDaysBefore(CarbonImmutable $from, int $n): CarbonImmutable
    {
        $d = $from;
        while ($n > 0) {
            $d = $d->subDay();
            if (!$d->isWeekend()) {
                $n--;
            }
        }

        return $d;
    }

    private function log(StageRequest $req, string $action, ?User $by, ?string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action,
            'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => $by ? 'staff' : 'demandeur', 'user_id' => $by?->id, 'comment' => $comment,
        ]);
    }
}
