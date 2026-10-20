<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Stages\Models\StageInternAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

/**
 * Empreinte du stagiaire (WebAuthn) :
 *  - enregistrement : le téléphone crée une paire de clés, déverrouillée
 *    par l'empreinte (ou le visage / le code du téléphone, au choix du
 *    téléphone) ; seule la clé PUBLIQUE est conservée ici ;
 *  - pointage : le téléphone signe un défi à usage unique ; la signature
 *    est vérifiée avec la clé publique enregistrée.
 * Un seul appareil actif par stagiaire ; changement d'appareil = réinitialisation
 * par le responsable ou un superviseur (tracée).
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

    public function hasCredential(StageInternAccount $account): bool
    {
        return DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->whereNull('revoked_at')->exists();
    }

    public function registrationOptions(StageInternAccount $account): array
    {
        if ($this->hasCredential($account)) {
            throw new BusinessException('Une empreinte est déjà enregistrée pour votre compte. Pour changer de téléphone, demandez la réinitialisation à votre superviseur.', 'STAGE_WEBAUTHN_EXISTS', 409);
        }
        $req  = $account->stage;
        $lib  = $this->lib();
        $args = $lib->getCreateArgs(
            (string) $account->id,
            $req->reference,
            trim($req->prenoms . ' ' . mb_strtoupper($req->nom)),
            120,
            false,
            'required',
            false, // authentificateur intégré au téléphone uniquement
        );
        // Encodé : le cache « database » n'accepte pas le binaire brut.
        Cache::put($this->key('reg', $account->id), base64_encode($lib->getChallenge()->getBinaryString()), self::CHALLENGE_TTL_SEC);

        return json_decode(json_encode($args), true);
    }

    public function register(StageInternAccount $account, array $payload, ?string $label): void
    {
        $challenge = base64_decode((string) Cache::pull($this->key('reg', $account->id)), true);
        if (!$challenge) {
            throw new BusinessException('Délai dépassé. Recommencez l\'enregistrement de votre empreinte.', 'STAGE_WEBAUTHN_TIMEOUT', 422);
        }
        if ($this->hasCredential($account)) {
            throw new BusinessException('Une empreinte est déjà enregistrée pour votre compte.', 'STAGE_WEBAUTHN_EXISTS', 409);
        }

        try {
            $data = $this->lib()->processCreate(
                $this->bin($payload['clientDataJSON'] ?? ''),
                $this->bin($payload['attestationObject'] ?? ''),
                $challenge,
                true,   // vérification de l'utilisateur (empreinte / code) exigée
                true,
                false,
            );
        } catch (WebAuthnException $e) {
            Log::info('[Stages] Enregistrement WebAuthn refusé', ['account' => $account->id, 'error' => $e->getMessage()]);
            throw new BusinessException("L'empreinte n'a pas pu être enregistrée. Réessayez ; si le problème continue, vérifiez qu'un verrouillage par empreinte est activé sur votre téléphone.", 'STAGE_WEBAUTHN_FAILED', 422);
        }

        $credentialId = $this->b64url($data->credentialId);
        if (DB::table('stage_webauthn_credentials')->where('credential_id', $credentialId)->whereNull('revoked_at')->exists()) {
            throw new BusinessException('Cette empreinte est déjà utilisée par un autre compte.', 'STAGE_WEBAUTHN_TAKEN', 409);
        }
        // Même clé ré-enregistrée après une réinitialisation : l'ancienne ligne révoquée cède la place.
        DB::table('stage_webauthn_credentials')->where('credential_id', $credentialId)->whereNotNull('revoked_at')->delete();

        DB::table('stage_webauthn_credentials')->insert([
            'account_id'   => $account->id,
            'credential_id'=> $credentialId,
            'public_key'   => $data->credentialPublicKey,
            'sign_count'   => (int) ($data->signatureCounter ?? 0),
            'device_label' => $label ? mb_substr(strip_tags($label), 0, 120) : null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /** Défi de pointage (lié à la session). */
    public function assertionOptions(StageInternAccount $account, string $sessionKey): array
    {
        $ids = DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->whereNull('revoked_at')
            ->pluck('credential_id')->map(fn ($id) => $this->unb64url($id))->all();
        if (!$ids) {
            throw new BusinessException('Enregistrez d\'abord votre empreinte pour pouvoir pointer.', 'STAGE_WEBAUTHN_MISSING', 409);
        }
        $lib  = $this->lib();
        $args = $lib->getGetArgs($ids, 60, false, false, false, true, true, 'required');
        Cache::put($this->key('get', $sessionKey), base64_encode($lib->getChallenge()->getBinaryString()), self::CHALLENGE_TTL_SEC);

        return json_decode(json_encode($args), true);
    }

    /** Vérifie la signature ; lève une exception explicite sinon. */
    public function verify(StageInternAccount $account, string $sessionKey, array $payload): void
    {
        $challenge = base64_decode((string) Cache::pull($this->key('get', $sessionKey)), true);
        if (!$challenge) {
            throw new BusinessException('Délai dépassé. Recommencez votre pointage.', 'STAGE_WEBAUTHN_TIMEOUT', 422);
        }
        $credId = (string) ($payload['id'] ?? '');
        $cred = DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->whereNull('revoked_at')
            ->where('credential_id', $this->b64url($this->unb64url($credId)))->first();
        if (!$cred) {
            throw new BusinessException('Ce téléphone n\'est pas celui enregistré pour votre compte.', 'STAGE_WEBAUTHN_UNKNOWN_DEVICE', 422);
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
            'sign_count' => (int) ($lib->getSignatureCounter() ?? $cred->sign_count),
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function revokeAll(int $accountId, ?int $byUserId): int
    {
        return DB::table('stage_webauthn_credentials')->where('account_id', $accountId)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_by' => $byUserId, 'updated_at' => now()]);
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
