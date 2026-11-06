<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageNote;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Fin anticipée d'un stage :
 *  - désistement du stagiaire (depuis son espace, immédiat) ;
 *  - désistement constaté (le stagiaire est parti sans le signaler) ;
 *  - révocation (décision d'un superviseur ou du responsable de division).
 *
 * Le stage quitte le statut « acceptée » : pointage, rapports, rappels et
 * attestation s'arrêtent d'eux-mêmes. Une note (désistement ou révocation)
 * est émise et envoyée au stagiaire. Annulable pendant 48 h (erreur), par
 * un superviseur du stagiaire ou le responsable de division.
 */
class StageEndService
{
    public const UNDO_HOURS = 48;

    /** Motifs de révocation (note et historique). */
    public const REVOKE_MOTIFS = [
        'absences'  => 'Absences répétées',
        'reglement' => 'Manquement au règlement intérieur',
        'autre'     => 'Autre motif',
    ];

    private const DISK = 'local';

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageRequestService $requests,
        private readonly BinomeService $binomes,
        private readonly StagePdfService $pdf,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // DÉCISIONS
    // ════════════════════════════════════════════════════════════════════

    /** Le stagiaire met fin à son stage (depuis son espace). */
    public function desist(StageRequest $req, ?string $motif): StageRequest
    {
        return $this->end($req, StageStatus::DESISTED, null, $motif, null);
    }

    /** Le stagiaire est parti sans le signaler : désistement constaté. */
    public function recordDesist(StageRequest $req, User $by, ?string $motif): StageRequest
    {
        $this->assertFollower($req, $by);

        return $this->end($req, StageStatus::DESIST_FOUND, $by, $motif, null);
    }

    public function revoke(StageRequest $req, User $by, string $motifType, string $motif): StageRequest
    {
        $this->assertFollower($req, $by);
        if (!isset(self::REVOKE_MOTIFS[$motifType])) {
            throw new BusinessException('Choisissez le motif de la révocation.', 'STAGE_END_MOTIF', 422);
        }

        return $this->end($req, StageStatus::REVOKED, $by, $motif, $motifType);
    }

    /** Annulation (erreur) dans les 48 h : le stage reprend comme avant. */
    public function undo(StageRequest $req, User $by): StageRequest
    {
        $this->assertFollower($req, $by);

        $done = DB::transaction(function () use ($req, $by) {
            $stage = StageRequest::whereKey($req->id)->lockForUpdate()->first();
            $end   = $this->lastEnd($stage);
            if (!$end || !in_array($stage->status, StageStatus::ENDED, true)) {
                throw new BusinessException('Ce stage n\'est pas clos.', 'STAGE_END_NONE', 409);
            }
            if (!$this->undoable($end)) {
                throw new BusinessException('Le délai de ' . self::UNDO_HOURS . ' h pour annuler est dépassé.', 'STAGE_END_LATE', 409);
            }
            $meta = $end->meta ?? [];
            $from = $stage->status;
            $stage->forceFill([
                'status'   => StageStatus::ACCEPTED,
                'date_fin' => $meta['date_fin_prevue'] ?? $stage->date_fin?->toDateString(),
            ])->save();

            if (!empty($meta['note_id']) && ($note = StageNote::find($meta['note_id']))) {
                $note->forceFill(['snapshot' => ($note->snapshot ?? []) + ['annulee_at' => now()->toIso8601String()]])->save();
            }
            StageHistory::create([
                'stage_request_id' => $stage->id, 'action' => 'stage_end_undone',
                'from_status' => $from, 'to_status' => StageStatus::ACCEPTED, 'actor' => 'staff', 'user_id' => $by->id,
                'comment' => StageStatus::label($from) . ' annulé(e)', 'meta' => ['end_history_id' => $end->id],
            ]);

            return $stage;
        });

        $this->notifier->toDemandeur($done, 'Votre stage reprend', [
            'La fin de votre stage a été annulée : votre stage continue normalement.',
            'Vous pouvez de nouveau vous connecter à votre espace stagiaire et pointer.',
        ], ['Référence' => $done->reference, 'Fin prévue' => $this->requests->frDate($done->date_fin)],
            ctaLabel: 'Ouvrir mon espace stagiaire', ctaUrl: $this->notifier->spaceUrl(), context: 'stage-fin-annulee', official: true);
        $this->notifier->toFollowers($done, 'Fin de stage annulée', [
            'La fin de stage enregistrée pour ce stagiaire a été annulée : le stage reprend.',
        ], ['Référence' => $done->reference, 'Stagiaire' => $done->fullName()], 'stage-fin-annulee');

        return $done;
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    /** Ce que le responsable / le superviseur peut faire, et la fin en cours. */
    public function state(StageRequest $req, ?User $viewer): array
    {
        $canAct = $viewer && $this->isFollower($req, $viewer);
        $end    = in_array($req->status, StageStatus::ENDED, true) ? $this->lastEnd($req) : null;
        $meta   = $end?->meta ?? [];

        return [
            'can_end'  => $canAct && $req->status === StageStatus::ACCEPTED,
            'can_undo' => $canAct && $end && $this->undoable($end),
            'motifs'   => self::REVOKE_MOTIFS,
            'current'  => $end ? [
                'type'        => $req->status,
                'type_label'  => StageStatus::label($req->status),
                'motif_type'  => $meta['motif_type'] ?? null,
                'motif'       => $meta['motif'] ?? null,
                'last_day'    => $meta['dernier_jour'] ?? null,
                'at'          => $end->created_at?->toIso8601String(),
                'undo_until'  => $end->created_at?->copy()->addHours(self::UNDO_HOURS)->toIso8601String(),
                'note_id'     => $meta['note_id'] ?? null,
            ] : null,
        ];
    }

    /** Superviseur attribué au stagiaire, ou responsable de division. */
    public function isFollower(StageRequest $req, User $user): bool
    {
        return StageAccess::isLead($user)
            || (StageAccess::isSupervisor($user)
                && DB::table('stage_supervisions')->where('stage_request_id', $req->id)->where('user_id', $user->id)->exists());
    }

    // ════════════════════════════════════════════════════════════════════
    // INTERNE
    // ════════════════════════════════════════════════════════════════════

    private function end(StageRequest $req, string $status, ?User $by, ?string $motif, ?string $motifType): StageRequest
    {
        $motif = $motif !== null ? trim(preg_replace('/\s+/u', ' ', $motif)) : null;
        $motif = $motif === '' ? null : $motif;
        if ($status === StageStatus::REVOKED && !$motif) {
            throw new BusinessException('Précisez le motif de la révocation.', 'STAGE_END_MOTIF', 422);
        }

        [$stage, $note] = DB::transaction(function () use ($req, $status, $by, $motif, $motifType) {
            $stage = StageRequest::whereKey($req->id)->lockForUpdate()->first();
            if ($stage->status !== StageStatus::ACCEPTED) {
                throw new BusinessException('Ce stage n\'est pas en cours : il ne peut pas être clos.', 'STAGE_END_STATUS', 409);
            }
            if ($status === StageStatus::DESISTED && !$stage->activated_at) {
                throw new BusinessException('Votre stage n\'a pas commencé.', 'STAGE_END_STATUS', 409);
            }

            $today   = BusinessHours::today();
            $lastDay = DB::table('stage_attendances')->where('stage_request_id', $stage->id)->max('day');
            $absent  = $lastDay ? $this->workingDays(CarbonImmutable::parse($lastDay)->addDay(), $today) : null;
            $planned = $stage->date_fin?->toDateString();
            $start   = ($stage->date_debut_reelle ?? $stage->date_debut)?->toDateString();
            // Période réellement effectuée : jusqu'au dernier jour de présence.
            $effectiveEnd = $lastDay && (!$start || $lastDay >= $start) ? $lastDay : $planned;

            $from = $stage->status;
            $stage->forceFill(['status' => $status, 'date_fin' => $effectiveEnd])->save();

            // Attestation en préparation : sans objet.
            DB::table('stage_attestations')->where('stage_request_id', $stage->id)
                ->whereIn('status', AttestationService::OPEN)
                ->update(['status' => 'annulee', 'cancelled_by' => $by?->id, 'cancelled_at' => now(),
                    'cancel_reason' => StageStatus::label($status), 'updated_at' => now()]);

            $note = StageNote::create([
                'stage_request_id' => $stage->id,
                'number'   => StageNote::nextNumber($today->format('y')),
                'kind'     => $status === StageStatus::REVOKED ? 'revocation' : 'desistement',
                'version'  => 0, // hors de la série initiale / rectificatives
                'snapshot' => [
                    'reference'     => $stage->reference,
                    'civilite'      => $stage->civilite,
                    'nom'           => $stage->nom,
                    'prenoms'       => $stage->prenoms,
                    'etablissement' => $stage->etablissement_origine,
                    'formation'     => $stage->filiere_niveau,
                    'service'       => $stage->service,
                    'date_debut'    => $start,
                    'date_fin_prevue' => $planned,
                    'dernier_jour'  => $lastDay,
                    'date_effet'    => $today->toDateString(),
                    'type'          => $status,
                    'motif_type'    => $motifType,
                    'motif'         => $motif,
                    'jours_absence' => $absent,
                ],
                'motif'       => $motif,
                'issued_by'   => $by?->id,
                'issued_at'   => now(),
                'recorded_at' => now(),
            ]);

            StageHistory::create([
                'stage_request_id' => $stage->id, 'action' => 'stage_ended',
                'from_status' => $from, 'to_status' => $status,
                'actor' => $by ? 'staff' : 'demandeur', 'user_id' => $by?->id,
                'comment' => trim(($motifType ? self::REVOKE_MOTIFS[$motifType] . ($motif ? ' : ' : '') : '') . ($motif ?? '')) ?: null,
                'meta' => [
                    'motif_type' => $motifType, 'motif' => $motif, 'dernier_jour' => $lastDay,
                    'date_fin_prevue' => $planned, 'note_id' => $note->id,
                ],
            ]);

            return [$stage, $note];
        });

        $this->binomes->release($stage, StageStatus::label($status));
        $this->notify($stage, $note, $by);

        return $stage;
    }

    private function notify(StageRequest $req, StageNote $note, ?User $by): void
    {
        $s      = $note->snapshot;
        $file   = $this->noteFile($note);
        $fields = [
            'Référence'          => $req->reference,
            'Note n°'            => $note->number,
            'Dernier jour de présence' => $s['dernier_jour'] ? $this->requests->frDate($s['dernier_jour']) : 'Aucun pointage',
        ];

        if ($req->status === StageStatus::REVOKED) {
            $this->notifier->toDemandeur($req, 'Révocation de votre stage', [
                'Votre stage au CAP-EPAC prend fin par décision de révocation. Votre espace stagiaire est désactivé.',
                'Aucune attestation de stage ne sera délivrée. Vous trouverez la note de révocation ci-jointe.',
            ], $fields, highlight: $this->motifText($s), highlightTitle: 'Motif', context: 'stage-revocation', file: $file);
        } else {
            $paragraphs = $req->status === StageStatus::DESISTED
                ? ['Vous avez mis fin à votre stage au CAP-EPAC. Votre espace stagiaire est désactivé.']
                : ['Votre absence prolongée a été constatée : votre stage au CAP-EPAC est clos pour désistement. Votre espace stagiaire est désactivé.'];
            $paragraphs[] = 'S\'il s\'agit d\'une erreur, contactez votre superviseur dans les ' . self::UNDO_HOURS . ' h. Vous trouverez la note de désistement ci-jointe.';
            $this->notifier->toDemandeur($req, 'Fin de votre stage (désistement)', $paragraphs, $fields,
                highlight: $s['motif'] ?? null, highlightTitle: 'Motif', context: 'stage-desistement', file: $file);
        }

        $who = $by ? trim($by->first_name . ' ' . $by->last_name) : 'le stagiaire lui-même';
        $this->notifier->toFollowers($req, StageStatus::label($req->status), [
            'Le stage est clos (' . mb_strtolower(StageStatus::label($req->status)) . ", enregistré par {$who}). Pointage, rapports et rappels sont arrêtés ; la place est libérée.",
            'En cas d\'erreur, la fin peut être annulée depuis le dossier pendant ' . self::UNDO_HOURS . ' h.',
        ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName(), 'Motif' => $this->motifText($s)], 'stage-fin');
    }

    private function motifText(array $s): ?string
    {
        $type = isset($s['motif_type']) ? (self::REVOKE_MOTIFS[$s['motif_type']] ?? null) : null;

        return trim(($type ? $type . (!empty($s['motif']) ? ' : ' : '') : '') . ($s['motif'] ?? '')) ?: null;
    }

    /** PDF de la note, rangé dans le dossier du stagiaire ; null si la génération échoue. */
    private function noteFile(StageNote $note): ?array
    {
        try {
            $pdf  = $this->pdf->endNote($note);
            $path = "stages/{$note->snapshot['reference']}/notes/{$note->kind}-" . preg_replace('/[^A-Za-z0-9-]/', '-', $note->number) . '.pdf';
            Storage::disk(self::DISK)->put($path, $pdf['content']);
            $note->forceFill(['pdf_path' => $path])->save();

            return ['disk' => self::DISK, 'path' => $path, 'name' => $pdf['name'], 'caption' => $pdf['name']];
        } catch (\Throwable $e) {
            Log::error('[Stages] PDF de la note de fin de stage non généré', ['note' => $note->number, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function lastEnd(StageRequest $req): ?StageHistory
    {
        $end = StageHistory::where('stage_request_id', $req->id)->where('action', 'stage_ended')->latest('id')->first();
        if (!$end) {
            return null;
        }
        $undone = StageHistory::where('stage_request_id', $req->id)->where('action', 'stage_end_undone')
            ->where('id', '>', $end->id)->exists();

        return $undone ? null : $end;
    }

    private function undoable(StageHistory $end): bool
    {
        return $end->created_at && $end->created_at->copy()->addHours(self::UNDO_HOURS)->isFuture();
    }

    private function assertFollower(StageRequest $req, User $user): void
    {
        if (!$this->isFollower($req, $user)) {
            throw new BusinessException('Seuls les superviseurs de ce stagiaire et le responsable de division peuvent le faire.', 'STAGE_END_FORBIDDEN', 403);
        }
    }

    /** Jours ouvrés (lundi - vendredi) de $from à $to inclus. */
    private function workingDays(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $n = 0;
        for ($d = $from->startOfDay(); $d->lte($to); $d = $d->addDay()) {
            $n += $d->isWeekend() ? 0 : 1;
        }

        return $n;
    }
}
