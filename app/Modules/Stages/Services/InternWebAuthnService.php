<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

/**
 * Téléphone de stage du stagiaire (WebAuthn + identifiant d'appareil) :
 *  - enregistré le premier jour, au moment de confirmer sa présence : le
 *    téléphone crée une paire de clés déverrouillée par l'empreinte (ou le
 *    visage / le code du téléphone) ; seule la clé PUBLIQUE est conservée,
 *    avec l'identifiant de l'appareil (haché) ;
 *  - c'est le seul appareil qui ouvre l'espace du stagiaire et qui pointe ;
 *  - un téléphone ne sert qu'à un seul stagiaire en cours de stage ;
 *  - changement de téléphone : réinitialisation par le responsable ou un
 *    superviseur (tracée), puis nouvel enregistrement.
 */
class InternWebAuthnService
{
    private const CHALLENGE_TTL_SEC = 180;

    public function rpId(): string
    {
        $host = parse_url((string) config('app.frontend_url', config('app.url')), PHP_URL_HOST);

        return $host ?: 'localhost';
    }

    private function lib(): WebAuthn
    {
        // Attestation « none » : on ne vérifie pas le modèle de l'appareil,
        // seulement la possession de la clé (aucun certificat requis).
        return new WebAuthn('CAP-EPAC', $this->rpId(), ['none', 'packed', 'android-key', 'android-safetynet', 'apple', 'tpm', 'fido-u2f'], true);
    }

    /** Téléphone de stage actif du compte (null si aucun). */
    public function device(int $accountId): ?object
    {
        return DB::table('stage_webauthn_credentials')->where('account_id', $accountId)->whereNull('revoked_at')->first();
    }

    public function hasCredential(StageInternAccount $account): bool
    {
        return $this->device($account->id) !== null;
    }

    /** Enregistrement depuis l'espace (après une réinitialisation). */
    public function registrationOptions(StageInternAccount $account, string $deviceHash): array
    {
        if ($this->hasCredential($account)) {
            throw new BusinessException('Un téléphone de stage est déjà enregistré pour votre compte. Pour en changer, demandez la réinitialisation à votre superviseur.', 'STAGE_WEBAUTHN_EXISTS', 409);
        }
        $this->assertDeviceFree($deviceHash, $account->id);

        return $this->createArgs((string) $account->id, $account->stage, $this->key('reg', $account->id));
    }

    /** Enregistrement le premier jour (lien d'activation, compte pas encore créé). */
    public function activationOptions(StageRequest $req, string $activationToken, string $deviceHash): array
    {
        $this->assertDeviceFree($deviceHash, null);

        return $this->createArgs('stg-' . $req->id, $req, $this->key('act', hash('sha256', $activationToken)));
    }

    public function register(StageInternAccount $account, array $payload, ?string $label, string $deviceHash): void
    {
        $data = $this->verifyCreation($this->key('reg', $account->id), $payload);
        if ($this->hasCredential($account)) {
            throw new BusinessException('Un téléphone de stage est déjà enregistré pour votre compte.', 'STAGE_WEBAUTHN_EXISTS', 409);
        }
        $this->store($account->id, $data, $label, $deviceHash);
    }

    /**
     * Vérifie l'enregistrement fait pendant l'activation. À enregistrer
     * ensuite avec store(), dans la même transaction que la création du compte.
     */
    public function verifyActivation(string $activationToken, array $payload, string $deviceHash): object
    {
        $this->assertDeviceFree($deviceHash, null);

        return $this->verifyCreation($this->key('act', hash('sha256', $activationToken)), $payload);
    }

    public function store(int $accountId, object $data, ?string $label, string $deviceHash): void
    {
        $this->assertDeviceFree($deviceHash, $accountId);
        $credentialId = $this->b64url($data->credentialId);
        if (DB::table('stage_webauthn_credentials')->where('credential_id', $credentialId)->whereNull('revoked_at')->exists()) {
            throw new BusinessException('Cette empreinte est déjà utilisée par un autre compte.', 'STAGE_WEBAUTHN_TAKEN', 409);
        }
        // Même clé ré-enregistrée après une réinitialisation : l'ancienne ligne révoquée cède la place.
        DB::table('stage_webauthn_credentials')->where('credential_id', $credentialId)->whereNotNull('revoked_at')->delete();

        DB::table('stage_webauthn_credentials')->insert([
            'account_id'   => $accountId,
            'credential_id'=> $credentialId,
            'public_key'   => $data->credentialPublicKey,
            'sign_count'   => (int) ($data->signatureCounter ?? 0),
            'device_label' => $label ? mb_substr(strip_tags($label), 0, 120) : null,
            'device_hash'  => $deviceHash,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Un téléphone ne sert qu'à un seul stagiaire en cours de stage : il ne
     * peut pas pointer pour deux comptes.
     */
    public function assertDeviceFree(string $deviceHash, ?int $accountId): void
    {
        $taken = DB::table('stage_webauthn_credentials as c')
            ->join('stage_intern_accounts as a', 'a.id', '=', 'c.account_id')
            ->join('stage_requests as r', 'r.id', '=', 'a.stage_request_id')
            ->where('c.device_hash', $deviceHash)->whereNull('c.revoked_at')
            ->where('r.status', StageStatus::ACCEPTED)
            ->when($accountId, fn ($q) => $q->where('c.account_id', '!=', $accountId))
            ->exists();
        if ($taken) {
            throw new BusinessException("Ce téléphone est déjà le téléphone de stage d'un autre stagiaire. Chaque stagiaire s'enregistre et pointe avec son propre téléphone.", 'STAGE_DEVICE_TAKEN', 409);
        }
    }

    /** Défi de pointage (lié à la session). */
    public function assertionOptions(StageInternAccount $account, string $sessionKey): array
    {
        $ids = DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->whereNull('revoked_at')
            ->pluck('credential_id')->map(fn ($id) => $this->unb64url($id))->all();
        if (!$ids) {
            throw new BusinessException('Enregistrez d\'abord votre téléphone de stage pour pouvoir pointer.', 'STAGE_WEBAUTHN_MISSING', 409);
        }
        $lib  = $this->lib();
        $args = $lib->getGetArgs($ids, 60, false, false, false, true, true, 'required');
        Cache::put($this->key('get', $sessionKey), base64_encode($lib->getChallenge()->getBinaryString()), self::CHALLENGE_TTL_SEC);

        return json_decode(json_encode($args), true);
    }

    /** Vérifie la signature ; lève une exception explicite sinon. */
    public function verify(StageInternAccount $account, string $sessionKey, array $payload, string $deviceHash): void
    {
        $challenge = base64_decode((string) Cache::pull($this->key('get', $sessionKey)), true);
        if (!$challenge) {
            throw new BusinessException('Délai dépassé. Recommencez votre pointage.', 'STAGE_WEBAUTHN_TIMEOUT', 422);
        }
        $credId = (string) ($payload['id'] ?? '');
        $cred = DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->whereNull('revoked_at')
            ->where('credential_id', $this->b64url($this->unb64url($credId)))->first();
        if (!$cred || ($cred->device_hash && !hash_equals($cred->device_hash, $deviceHash))) {
            throw new BusinessException('Ce téléphone n\'est pas votre téléphone de stage.', 'STAGE_WEBAUTHN_UNKNOWN_DEVICE', 422);
        }

        try {
            $lib = $this->lib();
            $lib->processGet(
                $this->bin($payload['clientDataJSON'] ?? ''),
                $this->bin($payload['authenticatorData'] ?? ''),
                $this->bin($payload['signature'] ?? ''),
                $cred->public_key,
                $challenge,
                (int) $cred->sign_count ?: null,
                true,
                true,
            );
        } catch (WebAuthnException $e) {
            Log::info('[Stages] Vérification WebAuthn refusée', ['account' => $account->id, 'error' => $e->getMessage()]);
            throw new BusinessException("Empreinte non reconnue. Réessayez.", 'STAGE_WEBAUTHN_FAILED', 422);
        }

        DB::table('stage_webauthn_credentials')->where('id', $cred->id)->update([
            'sign_count'   => (int) ($lib->getSignatureCounter() ?? $cred->sign_count),
            // Empreinte enregistrée avant le téléphone de stage : liée à ce téléphone dès qu'elle est prouvée.
            'device_hash'  => $cred->device_hash ?: $deviceHash,
            'last_used_at' => now(),
            'updated_at'   => now(),
        ]);
    }

    public function revokeAll(int $accountId, ?int $byUserId): int
    {
        return DB::table('stage_webauthn_credentials')->where('account_id', $accountId)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_by' => $byUserId, 'updated_at' => now()]);
    }

    private function createArgs(string $userId, StageRequest $req, string $cacheKey): array
    {
        $lib  = $this->lib();
        $args = $lib->getCreateArgs(
            $userId,
            $req->reference,
            trim($req->prenoms . ' ' . mb_strtoupper($req->nom)),
            120,
            false,
            'required',
            false, // authentificateur intégré au téléphone uniquement
        );
        // Encodé : le cache « database » n'accepte pas le binaire brut.
        Cache::put($cacheKey, base64_encode($lib->getChallenge()->getBinaryString()), self::CHALLENGE_TTL_SEC);

        return json_decode(json_encode($args), true);
    }

    private function verifyCreation(string $cacheKey, array $payload): object
    {
        $challenge = base64_decode((string) Cache::pull($cacheKey), true);
        if (!$challenge) {
            throw new BusinessException('Délai dépassé. Recommencez l\'enregistrement de votre téléphone.', 'STAGE_WEBAUTHN_TIMEOUT', 422);
        }

        try {
            return $this->lib()->processCreate(
                $this->bin($payload['clientDataJSON'] ?? ''),
                $this->bin($payload['attestationObject'] ?? ''),
                $challenge,
                true,   // vérification de l'utilisateur (empreinte / code) exigée
                true,
                false,
            );
        } catch (WebAuthnException $e) {
            Log::info('[Stages] Enregistrement WebAuthn refusé', ['error' => $e->getMessage()]);
            throw new BusinessException("Le téléphone n'a pas pu être enregistré. Réessayez ; si le problème continue, vérifiez qu'un verrouillage (empreinte, visage ou code) est activé sur votre téléphone.", 'STAGE_WEBAUTHN_FAILED', 422);
        }
    }

    private function key(string $kind, string|int $id): string
    {
        return "stage-webauthn:{$kind}:{$id}";
    }

    /** Données reçues du navigateur en base64url. */
    private function bin(string $b64url): string
    {
        $raw = $this->unb64url($b64url);
        if ($raw === '') {
            throw new BusinessException('Réponse de l\'empreinte incomplète. Réessayez.', 'STAGE_WEBAUTHN_PAYLOAD', 422);
        }

        return $raw;
    }

    private function unb64url(string $s): string
    {
        $s = strtr(trim($s), '-_', '+/');
        $decoded = base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4), true);

        return $decoded === false ? '' : $decoded;
    }

    private function b64url(string|ByteBuffer $bin): string
    {
        $raw = $bin instanceof ByteBuffer ? $bin->getBinaryString() : $bin;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
