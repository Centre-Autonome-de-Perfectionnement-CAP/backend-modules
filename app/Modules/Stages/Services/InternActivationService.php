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

/**
 * Activation de l'espace stagiaire, le jour du début :
 *  - à l'heure d'arrivée + 2 h, un lien part sur WhatsApp (et par email) ;
 *  - le stagiaire confirme sa présence (position) et choisit son mot de
 *    passe : son compte est créé, la date réelle de prise de service est
 *    fixée et l'arrivée du jour est enregistrée ;
 *  - sans activation : rappel chaque jour ouvré ; au bout de 3 jours ouvrés,
 *    désistement (place libérée, responsable informé). Jamais le week-end, et
 *    jamais pour un stagiaire qui a essayé de confirmer sans y parvenir
 *    (téléphone qui refuse l'empreinte…) : ses superviseurs sont alertés et
 *    décident. Un stage annulé par erreur peut être rétabli (reinstate()).
 */
class InternActivationService
{
    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly BinomeService $binomes,
        private readonly StageSettingsService $settings,
        private readonly AttendanceService $attendance,
        private readonly StageRequestService $requests,
        private readonly InternWebAuthnService $devices,
        private readonly InternCodeService $codes,
    ) {}

    /** @return array{sent: int, reminded: int, withdrawn: int} */
    public function tick(?CarbonImmutable $now = null): array
    {
        $now   = ($now ?? CarbonImmutable::now())->setTimezone(BusinessHours::TZ);
        $today = $now->startOfDay();
        if ($today->isWeekend()) {
            return ['sent' => 0, 'reminded' => 0, 'withdrawn' => 0]; // ni lien, ni rappel, ni annulation le week-end
        }
        $delay = (int) $this->settings->get('activation_delay_hours');
        $limit = (int) $this->settings->get('desistement_days');
        $stats = ['sent' => 0, 'reminded' => 0, 'withdrawn' => 0];

        $pending = StageRequest::where('status', StageStatus::ACCEPTED)
            ->whereNull('activated_at')->whereNull('withdrawn_at')
            ->where('date_debut', '<=', $today->toDateString())
            ->orderBy('id')->limit(200)->get();

        foreach ($pending as $req) {
            try {
                $start  = $this->deadlineStart($req);
                $sendAt = $this->arrivalTime($req, $today)->addHours($delay);

                if ($now->lessThan($sendAt)) {
                    continue; // pas encore l'heure aujourd'hui
                }
                if ($req->activation_sent_at === null) {
                    $this->sendLink($req, false);
                    $stats['sent']++;
                    continue;
                }
                // Jours ouvrés écoulés depuis le début (le jour du début non compté).
                if ($this->workingDaysBetween($start, $today) >= $limit) {
                    if ($this->triedToConfirm($req)) {
                        $this->alertStuck($req);
                    } else {
                        $this->withdraw($req);
                        $stats['withdrawn']++;
                        continue;
                    }
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
    /** Défi d'enregistrement du téléphone de stage, avant la confirmation de présence. */
    public function deviceOptions(string $token, string $deviceHash): array
    {
        $req = $this->openableRequest($token);
        $this->noteAttempt($req);

        return $this->devices->activationOptions($req, $token, $deviceHash);
    }

    /** Mode code (téléphone sans empreinte) : code de confirmation de présence. */
    public function sendCode(string $token, string $deviceHash): array
    {
        $req = $this->openableRequest($token);
        $this->noteAttempt($req);
        $this->devices->assertDeviceFree($deviceHash, null);

        return $this->codes->send($this->codeScope($token), $req, $deviceHash, 'confirmer votre présence');
    }

    private function codeScope(string $token): string
    {
        return 'act:' . hash('sha256', $token);
    }

    /**
     * Rétablit un stage annulé faute de confirmation de présence : le
     * stagiaire reçoit un nouveau lien et dispose à nouveau du délai prévu.
     */
    public function reinstate(StageRequest $req, \App\Models\User $by): StageRequest
    {
        if ($req->status !== StageStatus::WITHDRAWN) {
            throw new BusinessException('Ce stage n\'a pas été annulé faute de confirmation de présence.', 'STAGE_NOT_WITHDRAWN', 409);
        }
        if ($req->date_fin && $req->date_fin->toDateString() < BusinessHours::today()->toDateString()) {
            throw new BusinessException('La période de stage est terminée : il n\'est plus possible de le rétablir.', 'STAGE_PERIOD_OVER', 409);
        }
        $updated = StageRequest::whereKey($req->id)->where('status', StageStatus::WITHDRAWN)->update([
            'status' => StageStatus::ACCEPTED, 'withdrawn_at' => null, 'activation_sent_at' => null, 'updated_at' => now(),
        ]);
        if (!$updated) {
            throw new BusinessException('Ce stage vient d\'être modifié. Actualisez la page.', 'STAGE_CONFLICT', 409);
        }
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => 'reinstated',
            'from_status' => StageStatus::WITHDRAWN, 'to_status' => StageStatus::ACCEPTED, 'actor' => 'staff', 'user_id' => $by->id,
            'comment' => 'Stage rétabli (annulé faute de confirmation de présence)',
        ]);
        $req->refresh();
        $this->sendLink($req, false);

        return $req;
    }

    /**
     * Premier jour, sur le lieu de stage et sur SON téléphone : règlement
     * accepté, téléphone de stage enregistré (empreinte), mot de passe choisi
     * et présence confirmée, en une seule fois. Rien n'est créé si une étape
     * échoue (hors zone, empreinte refusée, téléphone déjà pris…).
     *
     * @param array{clientDataJSON: string, attestationObject: string} $webauthn
     */
    public function activate(
        string $token, array $position, string $password, int $reglementVersion,
        ?array $webauthn, ?string $deviceLabel, string $deviceHash, ?string $code = null,
    ): StageInternAccount {
        $req = $this->openableRequest($token);
        if ($reglementVersion !== (int) $this->settings->get('reglement_version')) {
            throw new BusinessException('Le règlement a été mis à jour. Relisez-le puis recommencez.', 'STAGE_REGLEMENT_OUTDATED', 409);
        }
        // Empreinte, ou code à usage unique pour un téléphone qui ne la gère pas.
        if ($webauthn !== null) {
            $credential = $this->devices->verifyActivation($token, $webauthn, $deviceHash);
        } else {
            $this->codes->check($this->codeScope($token), (string) $code, $deviceHash);
            $this->devices->assertDeviceFree($deviceHash, null);
            $credential = null;
        }
        $today = CarbonImmutable::now(BusinessHours::TZ)->startOfDay();

        $account = DB::transaction(function () use ($req, $position, $password, $today, $reglementVersion, $credential, $deviceLabel, $deviceHash) {
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
            $account->forceFill(['reglement_version' => $reglementVersion, 'reglement_accepted_at' => now()])->save();
            $credential
                ? $this->devices->store($account->id, $credential, $deviceLabel, $deviceHash)
                : $this->devices->storeCode($account->id, $deviceLabel, $deviceHash);

            StageHistory::create([
                'stage_request_id' => $locked->id, 'action' => 'activated',
                'from_status' => $locked->status, 'to_status' => $locked->status, 'actor' => 'demandeur',
                'comment' => 'Présence confirmée - prise de service le ' . $today->locale('fr')->isoFormat('dddd D MMMM YYYY')
                    . " ; règlement (version {$reglementVersion}) accepté et téléphone de stage enregistré"
                    . ($credential ? '' : ' (confirmation par code : le téléphone ne gère pas l\'empreinte)'),
            ]);

            return $account;
        });

        if (!$credential) {
            $this->codes->forget($this->codeScope($token));
        }
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
                'Le téléphone utilisé aujourd\'hui est votre téléphone de stage : c\'est le seul qui ouvre votre espace et qui pointe, '
                    . ($credential ? 'avec votre empreinte.' : 'avec un code que vous demandez à chaque pointage.') . ' Pour en changer, adressez-vous à votre superviseur.',
            ],
            ['Identifiant' => $req->reference],
            ctaLabel: 'Ouvrir mon espace stagiaire',
            ctaUrl: $this->notifier->spaceUrl(),
            context: 'stage-bienvenue',
        );

        return $account;
    }

    /** Lien valable, compte pas encore activé, jour ouvré. */
    private function openableRequest(string $token): StageRequest
    {
        $req = $this->findByToken($token);
        if (!$req) {
            throw new BusinessException("Ce lien n'est plus valable. Si votre compte est déjà activé, connectez-vous ; sinon contactez le responsable de division.", 'STAGE_ACTIVATION_INVALID', 410);
        }
        if (CarbonImmutable::now(BusinessHours::TZ)->isWeekend()) {
            throw new BusinessException('La confirmation de présence se fait un jour ouvré, sur votre lieu de stage.', 'STAGE_ACTIVATION_WEEKEND', 422);
        }

        return $req;
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
        $token = \App\Modules\Core\Support\LinkToken::make();
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
                '*Ouvrez le lien sur VOTRE propre téléphone* : il deviendra votre téléphone de stage, le seul qui ouvrira votre espace et pointera. '
                . 'Lisez le règlement, autorisez la localisation, enregistrez votre empreinte et choisissez votre mot de passe. '
                . "Sans confirmation dans les {$limit} jours ouvrés suivant la date de début, votre stage sera annulé.",
            ],
            ['Référence' => $req->reference],
            ctaLabel: 'Confirmer ma présence',
            ctaUrl: $this->notifier->spaceUrl('/activer/' . $token),
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
        ], ['Référence' => $req->reference], context: 'stage-desistement', official: true);
        $this->binomes->release($req, 'Désistement (présence jamais confirmée)');
        $this->notifier->toFollowers($req, 'Désistement d\'un stagiaire', [
            'Le stagiaire n\'a jamais confirmé sa présence : le stage a été annulé automatiquement et la place est libérée.',
        ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName(), 'Début prévu' => $this->requests->frDate($req->date_debut)], 'stage-desistement');
    }

    /** Point de départ du délai : la date de début, ou le jour où le stage a été rétabli. */
    private function deadlineStart(StageRequest $req): CarbonImmutable
    {
        $start = CarbonImmutable::parse($req->date_debut->toDateString(), BusinessHours::TZ);
        $back = StageHistory::where('stage_request_id', $req->id)->where('action', 'reinstated')->latest('id')->value('created_at');

        return $back ? max($start, CarbonImmutable::parse($back)->setTimezone(BusinessHours::TZ)->startOfDay()) : $start;
    }

    /** Le stagiaire a ouvert l'enregistrement de son téléphone (une trace par jour). */
    private function noteAttempt(StageRequest $req): void
    {
        $today = BusinessHours::today();
        $exists = StageHistory::where('stage_request_id', $req->id)->where('action', 'activation_attempt')
            ->where('created_at', '>=', $today->startOfDay()->utc())->exists();
        if (!$exists) {
            StageHistory::create([
                'stage_request_id' => $req->id, 'action' => 'activation_attempt', 'from_status' => $req->status,
                'to_status' => $req->status, 'actor' => 'demandeur', 'comment' => 'Tentative de confirmation de présence',
            ]);
        }
    }

    private function triedToConfirm(StageRequest $req): bool
    {
        return StageHistory::where('stage_request_id', $req->id)->where('action', 'activation_attempt')->exists();
    }

    /**
     * Délai dépassé alors que le stagiaire a essayé de confirmer : pas
     * d'annulation automatique ; ses superviseurs sont alertés (une fois par jour).
     */
    private function alertStuck(StageRequest $req): void
    {
        $today = BusinessHours::today();
        if (StageHistory::where('stage_request_id', $req->id)->where('action', 'activation_stuck')
            ->where('created_at', '>=', $today->startOfDay()->utc())->exists()) {
            return;
        }
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => 'activation_stuck', 'from_status' => $req->status,
            'to_status' => $req->status, 'actor' => 'systeme', 'comment' => 'Présence non confirmée malgré des tentatives',
        ]);
        $this->notifier->toFollowers($req, 'Stagiaire bloqué à la confirmation de présence', [
            'Le délai de confirmation est dépassé, mais le stagiaire a essayé de confirmer sa présence sans y parvenir (souvent un téléphone qui refuse l\'empreinte). Son stage n\'a pas été annulé.',
            'Voyez avec lui : un autre téléphone, ou constatez son désistement depuis son dossier s\'il ne vient pas.',
        ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName(), 'Début prévu' => $this->requests->frDate($req->date_debut)],
            'stage-activation', $this->notifier->staffUrl($req));
    }

    /** Jours ouvrés (lundi - vendredi) après $from, jusqu'à $to inclus. */
    private function workingDaysBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $n = 0;
        for ($d = $from->startOfDay()->addDay(); $d->lte($to); $d = $d->addDay()) {
            $n += $d->isWeekend() ? 0 : 1;
        }

        return $n;
    }

    private function arrivalTime(StageRequest $req, CarbonImmutable $day): CarbonImmutable
    {
        $h = $req->horaires['arrivee'] ?? '08:00';
        [$hh, $mm] = array_map('intval', explode(':', $h) + [0, 0]);

        return $day->setTime($hh, $mm);
    }
}
