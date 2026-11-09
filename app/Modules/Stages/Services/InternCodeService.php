<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use Illuminate\Support\Facades\Cache;

/**
 * Codes de confirmation du « mode code » : téléphone qui ne sait pas
 * utiliser l'empreinte. Le stagiaire demande un code à chaque fois
 * (activation, enregistrement du téléphone, chaque pointage) :
 *  - 6 chiffres, envoyés sur son WhatsApp et par email ;
 *  - valable 5 minutes, une seule fois : il expire dès que l'action a réussi ;
 *  - lié au téléphone qui l'a demandé (un code ne sert que sur ce téléphone) ;
 *  - 3 erreurs annulent le code ; un code par minute au plus, 30 par jour.
 */
class InternCodeService
{
    public const TTL_MIN = 5;
    public const COOLDOWN_SEC = 60;
    private const MAX_ATTEMPTS = 3;
    private const DAILY_MAX = 30;

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Envoie un nouveau code (l'ancien ne vaut plus).
     *
     * @param  string  $scope  « act:… » (activation) ou « acct:… » (compte)
     * @return array{expires_minutes: int, retry_in: int, to: string}
     */
    public function send(string $scope, StageRequest $req, string $deviceHash, string $purpose): array
    {
        $key = $this->key($scope);
        $existing = Cache::get($key);
        if ($existing && ($wait = self::COOLDOWN_SEC - (now()->timestamp - $existing['sent_at'])) > 0) {
            throw new BusinessException("Un code vient de vous être envoyé. Patientez {$wait} s avant d'en demander un autre.", 'STAGE_CODE_WAIT', 429);
        }
        $day = 'stage-code-day:' . $req->id . ':' . BusinessHours::today()->toDateString();
        Cache::add($day, 0, now()->addDay());
        if (Cache::increment($day) > self::DAILY_MAX) {
            throw new BusinessException('Trop de codes demandés aujourd\'hui. Prévenez votre superviseur.', 'STAGE_CODE_LIMIT', 429);
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($key, [
            'hash' => hash('sha256', $code), 'device' => $deviceHash, 'attempts' => 0, 'sent_at' => now()->timestamp,
        ], now()->addMinutes(self::TTL_MIN));

        // Envoyé à toute heure et sur les deux canaux : le stagiaire l'attend.
        $this->notifier->whatsapp($req->whatsapp, WhatsAppText::compose('Code de confirmation', [
            "Votre code pour {$purpose} :",
            "*{$code}*",
            '_Valable ' . self::TTL_MIN . ' minutes, une seule fois. Ne le communiquez à personne : il ne sert que sur votre téléphone de stage._',
        ]), "stage-code:{$req->reference}", 'Stages');
        $this->notifier->email($req->email, 'Code de confirmation - espace stagiaire', 'core::emails.otp-verification', [
            'code' => $code, 'email' => $req->email, 'ttlMinutes' => self::TTL_MIN,
        ]);

        return [
            'expires_minutes' => self::TTL_MIN,
            'retry_in'        => self::COOLDOWN_SEC,
            'to'              => $this->hasEmail($req) ? 'votre WhatsApp et votre email' : 'votre WhatsApp',
        ];
    }

    /**
     * Vérifie le code (exception explicite sinon). Il reste valable tant que
     * l'action n'a pas réussi (position refusée…) : l'appelant l'annule
     * ensuite avec forget(), dès que la présence est confirmée.
     */
    public function check(string $scope, string $code, string $deviceHash): void
    {
        $key = $this->key($scope);
        $stored = Cache::get($key);
        if (!$stored) {
            throw new BusinessException('Code expiré ou déjà utilisé. Demandez un nouveau code.', 'STAGE_CODE_EXPIRED', 422);
        }
        if (!hash_equals($stored['device'], $deviceHash)) {
            throw new BusinessException('Ce code a été demandé depuis un autre téléphone. Demandez un code depuis votre téléphone de stage.', 'STAGE_CODE_DEVICE', 422);
        }
        if (!hash_equals($stored['hash'], hash('sha256', trim($code)))) {
            $stored['attempts']++;
            if ($stored['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($key);
                throw new BusinessException('Code incorrect. Après 3 erreurs, le code est annulé : demandez-en un nouveau.', 'STAGE_CODE_EXPIRED', 422);
            }
            $left = self::MAX_ATTEMPTS - $stored['attempts'];
            Cache::put($key, $stored, now()->addSeconds(max(1, self::TTL_MIN * 60 - (now()->timestamp - $stored['sent_at']))));
            throw new BusinessException("Code incorrect. Encore {$left} essai" . ($left > 1 ? 's' : '') . '.', 'STAGE_CODE_BAD', 422);
        }
    }

    /** Le code a servi : il ne vaut plus. */
    public function forget(string $scope): void
    {
        Cache::forget($this->key($scope));
    }

    private function key(string $scope): string
    {
        return 'stage-code:' . $scope;
    }

    private function hasEmail(StageRequest $req): bool
    {
        return (bool) filter_var((string) $req->email, FILTER_VALIDATE_EMAIL);
    }
}
