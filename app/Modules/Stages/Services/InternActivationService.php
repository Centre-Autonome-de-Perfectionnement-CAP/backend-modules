<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Activation de l'espace stagiaire, le jour du début :
 *  - à l'heure d'arrivée + 2 h, un lien part sur WhatsApp (et par email) ;
 *  - le stagiaire confirme sa présence (position) et choisit son mot de
 *    passe : son compte est créé, la date réelle de prise de service est
 *    fixée et l'arrivée du jour est enregistrée ;
 *  - sans activation : rappel chaque jour ; au bout de 3 jours, désistement
 *    (place libérée, responsable informé).
 */
class InternActivationService
{
    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly BinomeService $binomes,
        private readonly StageSettingsService $settings,
        private readonly AttendanceService $attendance,
        private readonly StageRequestService $requests,
    ) {}

    /** @return array{sent: int, reminded: int, withdrawn: int} */
    public function tick(?CarbonImmutable $now = null): array
    {
        $now   = ($now ?? CarbonImmutable::now())->setTimezone(BusinessHours::TZ);
        $today = $now->startOfDay();
        $delay = (int) $this->settings->get('activation_delay_hours');
        $limit = (int) $this->settings->get('desistement_days');
        $stats = ['sent' => 0, 'reminded' => 0, 'withdrawn' => 0];

        $pending = StageRequest::where('status', StageStatus::ACCEPTED)
            ->whereNull('activated_at')->whereNull('withdrawn_at')
            ->where('date_debut', '<=', $today->toDateString())
            ->orderBy('id')->limit(200)->get();

        foreach ($pending as $req) {
            try {
                $start  = CarbonImmutable::parse($req->date_debut->toDateString(), BusinessHours::TZ);
                $sendAt = $this->arrivalTime($req, $today)->addHours($delay);

                if ($now->lessThan($sendAt)) {
                    continue; // pas encore l'heure aujourd'hui
                }
                if ($req->activation_sent_at === null) {
                    $this->sendLink($req, false);
                    $stats['sent']++;
                    continue;
                }
                $daysSinceStart = $start->diffInDays($today);
                if ($daysSinceStart >= $limit) {
                    $this->withdraw($req);
                    $stats['withdrawn']++;
                    continue;
                }
                $lastSent = CarbonImmutable::instance($req->activation_sent_at)->setTimezone(BusinessHours::TZ)->startOfDay();
                if ($lastSent->lessThan($today)) {
                    $this->sendLink($req, true);
                    $stats['reminded']++;
                }
            } catch (\Throwable $e) {
                Log::error('[Stages] Activation : envoi impossible', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    public function findByToken(string $token): ?StageRequest
    {
        $req = StageRequest::where('activation_token_hash', hash('sha256', $token))->first();

        return $req && $req->status === StageStatus::ACCEPTED && !$req->activated_at && !$req->withdrawn_at ? $req : null;
    }

    /**
     * Confirme la présence et crée le compte.
     *
     * @param array{lat: float, lng: float, accuracy: float} $position
     */
    public function activate(string $token, array $position, string $password): StageInternAccount
    {
        $req = $this->findByToken($token);
        if (!$req) {
            throw new BusinessException("Ce lien n'est plus valable. Si votre compte est déjà activé, connectez-vous ; sinon contactez le responsable de division.", 'STAGE_ACTIVATION_INVALID', 410);
        }
        $now   = CarbonImmutable::now(BusinessHours::TZ);
        $today = $now->startOfDay();
        if ($today->isWeekend()) {
            throw new BusinessException('La confirmation de présence se fait un jour ouvré, sur votre lieu de stage.', 'STAGE_ACTIVATION_WEEKEND', 422);
        }

        $account = DB::transaction(function () use ($req, $position, $password, $today) {
            $locked = StageRequest::whereKey($req->id)->lockForUpdate()->first();
            if ($locked->activated_at) {
                throw new BusinessException('Votre compte est déjà activé. Connectez-vous.', 'STAGE_ACTIVATION_DONE', 409);
            }

            $locked->forceFill([
                'date_debut_reelle'     => $today->toDateString(),
                'activated_at'          => now(),
                'activation_token_hash' => null,
            ])->save();

            // Présence du premier jour (refusée hors zone : rien n'est créé).
            $this->attendance->punch($locked, 'arrivee', $position, 'activation');

            $account = StageInternAccount::create(['stage_request_id' => $locked->id, 'password' => $password]);

            StageHistory::create([
                'stage_request_id' => $locked->id, 'action' => 'activated',
                'from_status' => $locked->status, 'to_status' => $locked->status, 'actor' => 'demandeur',
                'comment' => 'Présence confirmée - prise de service le ' . $today->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            ]);

            return $account;
        });

        $req->refresh();
        $planned = $req->date_debut->toDateString();
        $real    = $req->date_debut_reelle->toDateString();
        $fields  = ['Référence' => $req->reference, 'Stagiaire' => $req->fullName(), 'Prise de service' => $this->requests->frDate($real)];

        if ($real !== $planned) {
            $newEnd = $this->shiftedEnd($req);
            $fields += ['Début prévu' => $this->requests->frDate($planned), 'Fin proposée' => $this->requests->frDate($newEnd)];
            $this->notifier->toFollowers($req, 'Prise de service décalée', [
                "Le stagiaire a pris service avec un décalage. Pour garder la même durée, la fin du stage passerait au {$this->requests->frDate($newEnd)}.",
                'Ouvrez le dossier pour confirmer la nouvelle date de fin (une note rectificative sera émise) ou l\'ajuster.',
            ], $fields, 'stage-decalage');
        } else {
            $this->notifier->toFollowers($req, 'Stagiaire arrivé', ['Le stagiaire a confirmé sa présence et activé son espace.'], $fields, 'stage-arrivee');
        }

        $this->notifier->toDemandeur(
            $req, 'Bienvenue au CAP-EPAC',
            [
                'Votre présence est confirmée et votre espace stagiaire est activé.',
                'À votre première connexion, lisez et acceptez le règlement, puis enregistrez votre empreinte : elle vous servira à pointer chaque jour.',
            ],
            ['Identifiant' => $req->reference],
            ctaLabel: 'Ouvrir mon espace stagiaire',
            ctaUrl: $this->notifier->spaceUrl(),
            context: 'stage-bienvenue',
        );

        return $account;
    }

    /** Fin décalée d'autant de jours que la prise de service (même durée). */
    public function shiftedEnd(StageRequest $req): string
    {
        if (!$req->date_debut_reelle || !$req->date_fin) {
            return (string) $req->date_fin?->toDateString();
        }
        $shift = CarbonImmutable::parse($req->date_debut->toDateString())->diffInDays(CarbonImmutable::parse($req->date_debut_reelle->toDateString()), false);

        return CarbonImmutable::parse($req->date_fin->toDateString())->addDays((int) $shift)->toDateString();
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function sendLink(StageRequest $req, bool $reminder): void
    {
        $token = Str::random(48);
        $req->forceFill([
            'activation_token_hash' => hash('sha256', $token),
            'activation_sent_at'    => now(),
            'activation_reminders'  => $reminder ? $req->activation_reminders + 1 : 0,
        ])->save();

        $limit = (int) $this->settings->get('desistement_days');
        $this->notifier->toDemandeur(
            $req,
            $reminder ? 'Rappel : confirmez votre présence' : 'Confirmez votre présence',
            [
                $reminder
                    ? 'Vous n\'avez pas encore confirmé votre présence au CAP-EPAC.'
                    : 'Votre stage commence aujourd\'hui. Confirmez votre présence depuis votre lieu de stage : votre espace stagiaire sera activé.',
                'Ouvrez le lien sur votre téléphone, autorisez la localisation et choisissez votre mot de passe. '
                . "Sans confirmation dans les {$limit} jours suivant la date de début, votre stage sera annulé.",
            ],
            ['Référence' => $req->reference],
            ctaLabel: 'Confirmer ma présence',
            ctaUrl: $this->notifier->spaceUrl('/activer?token=' . $token),
            context: $reminder ? 'stage-activation-rappel' : 'stage-activation',
        );
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $reminder ? 'activation_reminder' : 'activation_sent',
            'from_status' => $req->status, 'to_status' => $req->status, 'actor' => 'systeme',
        ]);
    }

    private function withdraw(StageRequest $req): void
    {
        $updated = StageRequest::whereKey($req->id)->where('status', StageStatus::ACCEPTED)->whereNull('activated_at')
            ->update(['status' => StageStatus::WITHDRAWN, 'withdrawn_at' => now(), 'activation_token_hash' => null, 'updated_at' => now()]);
        if (!$updated) {
            return;
        }
        $req->refresh();
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => 'withdrawn',
            'from_status' => StageStatus::ACCEPTED, 'to_status' => StageStatus::WITHDRAWN, 'actor' => 'systeme',
            'comment' => 'Présence jamais confirmée',
        ]);

        $this->notifier->toDemandeur($req, 'Stage annulé', [
            "Vous n'avez pas confirmé votre présence dans le délai prévu : votre stage a été annulé et la place libérée.",
            'Si c\'est une erreur, contactez rapidement le CAP-EPAC.',
        ], ['Référence' => $req->reference], context: 'stage-desistement');
        $this->binomes->release($req, 'Désistement (présence jamais confirmée)');
        $this->notifier->toFollowers($req, 'Désistement d\'un stagiaire', [
            'Le stagiaire n\'a jamais confirmé sa présence : le stage a été annulé automatiquement et la place est libérée.',
        ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName(), 'Début prévu' => $this->requests->frDate($req->date_debut)], 'stage-desistement');
    }

    private function arrivalTime(StageRequest $req, CarbonImmutable $day): CarbonImmutable
    {
        $h = $req->horaires['arrivee'] ?? '08:00';
        [$hh, $mm] = array_map('intval', explode(':', $h) + [0, 0]);

        return $day->setTime($hh, $mm);
    }
}
