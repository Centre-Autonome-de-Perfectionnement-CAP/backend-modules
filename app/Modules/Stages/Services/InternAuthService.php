<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sessions de l'espace stagiaire. Totalement séparées des comptes du
 * personnel : un jeton stagiaire n'ouvre jamais une route du progiciel
 * (il n'est pas un jeton Sanctum), et inversement.
 */
class InternAuthService
{
    public const HEADER = 'X-Stagiaire-Token';
    private const SESSION_DAYS = 30;
    private const RESET_TTL_MIN = 10;

    public function __construct(private readonly Notifier $notifier) {}

    public function open(StageInternAccount $account, Request $request): string
    {
        $token = 'stg_' . Str::random(60);
        DB::table('stage_intern_sessions')->insert([
            'account_id'   => $account->id,
            'token_hash'   => hash('sha256', $token),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 255),
            'ip'           => $request->ip(),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays(self::SESSION_DAYS),
            'created_at'   => now(),
        ]);
        $account->forceFill(['last_login_at' => now()])->save();

        return $token;
    }

    /** Compte correspondant au jeton (session prolongée à chaque usage). */
    public function resolve(?string $token): ?StageInternAccount
    {
        if (!$token || !str_starts_with($token, 'stg_')) {
            return null;
        }
        $session = DB::table('stage_intern_sessions')
            ->where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        if (!$session) {
            return null;
        }
        if (!$session->last_used_at || now()->diffInMinutes($session->last_used_at, true) >= 10) {
            DB::table('stage_intern_sessions')->where('id', $session->id)
                ->update(['last_used_at' => now(), 'expires_at' => now()->addDays(self::SESSION_DAYS)]);
        }

        $account = StageInternAccount::with('stage')->find($session->account_id);

        return $account && $account->stage && $account->stage->status === StageStatus::ACCEPTED ? $account : null;
    }

    public function close(?string $token): void
    {
        if ($token) {
            DB::table('stage_intern_sessions')->where('token_hash', hash('sha256', $token))->delete();
        }
    }

    public function closeAll(StageInternAccount $account): void
    {
        DB::table('stage_intern_sessions')->where('account_id', $account->id)->delete();
    }

    /** Connexion par référence ou email + mot de passe. */
    public function login(string $identifier, string $password): StageInternAccount
    {
        $account = $this->findAccount($identifier);
        if (!$account || !Hash::check($password, $account->password)) {
            throw new BusinessException('Identifiant ou mot de passe incorrect.', 'STAGE_LOGIN_FAILED', 422);
        }
        if ($account->stage->status !== StageStatus::ACCEPTED) {
            throw new BusinessException('Ce stage est clos : l\'espace stagiaire n\'est plus accessible.', 'STAGE_CLOSED', 403);
        }

        return $account;
    }

    /** Mot de passe oublié : code à 6 chiffres sur le WhatsApp (et l'email) du stagiaire. */
    public function sendResetCode(string $identifier): void
    {
        $account = $this->findAccount($identifier);
        if (!$account) {
            return; // réponse identique : on ne révèle pas si le compte existe
        }
        $key = 'stage-reset:' . $account->id;
        $existing = Cache::get($key);
        if ($existing && now()->timestamp - $existing['sent_at'] < 60) {
            return;
        }
        $code = (string) random_int(100000, 999999);
        Cache::put($key, ['hash' => hash('sha256', $code), 'attempts' => 0, 'sent_at' => now()->timestamp], now()->addMinutes(self::RESET_TTL_MIN));

        $req = $account->stage;
        $this->notifier->whatsapp($req->whatsapp, WhatsAppText::compose('Mot de passe oublié', [
            'Votre code pour choisir un nouveau mot de passe de votre espace stagiaire :',
            "*{$code}*",
            '_Valable ' . self::RESET_TTL_MIN . ' minutes. Ne le communiquez à personne._',
        ]), "stage-reset:{$req->reference}", 'Stages');
        $this->notifier->email($req->email, 'Code de vérification — espace stagiaire', 'core::emails.otp-verification', [
            'code' => $code, 'email' => $req->email, 'ttlMinutes' => self::RESET_TTL_MIN,
        ]);
    }

    public function resetPassword(string $identifier, string $code, string $password): StageInternAccount
    {
        $account = $this->findAccount($identifier);
        $key     = $account ? 'stage-reset:' . $account->id : null;
        $stored  = $key ? Cache::get($key) : null;

        if (!$stored || $stored['attempts'] >= 5) {
            throw new BusinessException('Code expiré. Demandez un nouveau code.', 'STAGE_RESET_EXPIRED', 422);
        }
        if (!hash_equals($stored['hash'], hash('sha256', trim($code)))) {
            $stored['attempts']++;
            Cache::put($key, $stored, now()->addMinutes(self::RESET_TTL_MIN));
            throw new BusinessException('Code incorrect.', 'STAGE_RESET_BAD_CODE', 422);
        }

        Cache::forget($key);
        $account->password = $password;
        $account->save();
        $this->closeAll($account);

        return $account;
    }

    private function findAccount(string $identifier): ?StageInternAccount
    {
        $identifier = trim($identifier);
        $query = StageRequest::query();
        if (preg_match('/^STG-[A-Za-z0-9]{6}$/i', $identifier)) {
            $query->where('reference', strtoupper($identifier));
        } else {
            $query->where('email', mb_strtolower($identifier))->where('status', StageStatus::ACCEPTED);
        }
        $req = $query->orderByDesc('id')->first();

        return $req ? StageInternAccount::with('stage')->where('stage_request_id', $req->id)->first() : null;
    }
}
