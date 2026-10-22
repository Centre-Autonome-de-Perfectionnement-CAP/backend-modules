<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Services\WhatsAppBridgeClient;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Support\StageAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Invitation des superviseurs : il suffit de leur numéro WhatsApp.
 *
 *  - un lien personnel, à usage unique, valable 72 h, part sur WhatsApp ;
 *  - en l'ouvrant, le superviseur complète son nom, son email et son mot de
 *    passe : son compte (rôle « superviseur ») est créé ;
 *  - un numéro déjà invité reçoit un nouveau lien (l'ancien ne marche plus) ;
 *  - un numéro déjà inscrit comme superviseur est ignoré.
 */
class SupervisorInvitationService
{
    public const TTL_HOURS = 72;
    private const MAX_PER_BATCH = 30;

    public function __construct(
        private readonly Notifier $notifier,
        private readonly WhatsAppBridgeClient $bridge,
    ) {}

    /**
     * @param string[] $rawPhones
     * @return array{invited: string[], already_supervisor: string[], invalid: string[]}
     */
    public function invite(array $rawPhones, ?string $name, User $by): array
    {
        $phones = [];
        $invalid = [];
        foreach ($rawPhones as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') continue;
            $n = $this->normalize($raw);
            if ($n) $phones[$n] = true;
            else $invalid[] = $raw;
        }
        $phones = array_keys($phones);

        if (!$phones) {
            throw new BusinessException(
                $invalid ? 'Aucun numéro valide. Exemple attendu : 01 97 00 00 00.' : 'Indiquez au moins un numéro WhatsApp.',
                'STAGE_INVITE_NO_PHONE', 422,
            );
        }
        if (count($phones) > self::MAX_PER_BATCH) {
            throw new BusinessException('30 numéros au maximum par envoi.', 'STAGE_INVITE_TOO_MANY', 422);
        }

        $existing = $this->supervisorPhones();
        $result = ['invited' => [], 'already_supervisor' => [], 'invalid' => $invalid];

        foreach ($phones as $phone) {
            if (in_array($phone, $existing, true)) {
                $result['already_supervisor'][] = $phone;
                continue;
            }
            $token = Str::random(48);

            DB::transaction(function () use ($phone, $name, $by, $token) {
                // Un numéro ne garde qu'une invitation valable : l'ancienne est annulée.
                DB::table('stage_supervisor_invitations')
                    ->where('phone', $phone)->whereNull('accepted_at')->whereNull('revoked_at')
                    ->update(['revoked_at' => now(), 'updated_at' => now()]);

                DB::table('stage_supervisor_invitations')->insert([
                    'phone'        => $phone,
                    'name'         => $name,
                    'token_hash'   => hash('sha256', $token),
                    'invited_by'   => $by->id,
                    'expires_at'   => now()->addHours(self::TTL_HOURS),
                    'last_sent_at' => now(),
                    'send_count'   => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            });

            $this->sendLink($phone, $name, $token, $by);
            $result['invited'][] = $phone;
        }

        return $result;
    }

    /** Nouveau lien (nouveau délai de 72 h) pour une invitation en attente. */
    public function resend(int $id, User $by): void
    {
        $inv = $this->pending($id);
        $token = Str::random(48);

        DB::table('stage_supervisor_invitations')->where('id', $inv->id)->update([
            'token_hash'   => hash('sha256', $token),
            'expires_at'   => now()->addHours(self::TTL_HOURS),
            'last_sent_at' => now(),
            'send_count'   => $inv->send_count + 1,
            'updated_at'   => now(),
        ]);

        $this->sendLink($inv->phone, $inv->name, $token, $by);
    }

    public function revoke(int $id): void
    {
        $inv = $this->pending($id, allowExpired: true);
        DB::table('stage_supervisor_invitations')->where('id', $inv->id)
            ->update(['revoked_at' => now(), 'updated_at' => now()]);
    }

    public function list(): array
    {
        return DB::table('stage_supervisor_invitations as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.invited_by')
            ->leftJoin('users as a', 'a.id', '=', 'i.accepted_user_id')
            ->orderByDesc('i.id')
            ->limit(200)
            ->get([
                'i.id', 'i.phone', 'i.name', 'i.expires_at', 'i.last_sent_at', 'i.send_count',
                'i.accepted_at', 'i.revoked_at', 'i.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as invited_by_name"),
                DB::raw("TRIM(CONCAT(COALESCE(a.first_name,''),' ',COALESCE(a.last_name,''))) as accepted_name"),
            ])
            ->map(function ($i) {
                $i->status = $i->accepted_at ? 'accepted'
                    : ($i->revoked_at ? 'revoked' : (now()->greaterThan($i->expires_at) ? 'expired' : 'pending'));
                return $i;
            })
            ->all();
    }

    // ── Côté invité (public) ─────────────────────────────────────────────

    public function findValid(string $token): ?object
    {
        $inv = DB::table('stage_supervisor_invitations')->where('token_hash', hash('sha256', $token))->first();

        return ($inv && !$inv->accepted_at && !$inv->revoked_at && now()->lessThan($inv->expires_at)) ? $inv : null;
    }

    /** Crée le compte superviseur ; le lien ne sert qu'une fois. */
    public function accept(string $token, array $data): User
    {
        return DB::transaction(function () use ($token, $data) {
            $inv = DB::table('stage_supervisor_invitations')
                ->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();

            if (!$inv || $inv->accepted_at || $inv->revoked_at || now()->greaterThanOrEqualTo($inv->expires_at)) {
                throw new BusinessException("Ce lien d'invitation n'est plus valable. Demandez-en un nouveau au responsable de division.", 'STAGE_INVITE_INVALID', 410);
            }

            $email = mb_strtolower(trim($data['email']));
            if (DB::table('users')->where('email', $email)->exists()) {
                throw new BusinessException('Cette adresse email est déjà utilisée par un compte. Utilisez-en une autre.', 'STAGE_INVITE_EMAIL_TAKEN', 422);
            }

            $roleId = DB::table('roles')->where('slug', StageAccess::SUPERVISOR_ROLE)->value('id');
            if (!$roleId) {
                throw new BusinessException('Le rôle superviseur est introuvable. Prévenez l\'administrateur.', 'STAGE_ROLE_MISSING', 500);
            }

            $userId = DB::table('users')->insertGetId([
                'uuid'       => (string) Str::uuid(),
                'last_name'  => mb_strtoupper(trim($data['nom'])),
                'first_name' => trim($data['prenom']),
                'email'      => $email,
                'password'   => Hash::make($data['password']),
                'phone'      => $inv->phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_user')->insert(['role_id' => $roleId, 'user_id' => $userId]);

            DB::table('stage_supervisor_invitations')->where('id', $inv->id)->update([
                'accepted_at' => now(), 'accepted_user_id' => $userId, 'updated_at' => now(),
            ]);

            return User::findOrFail($userId);
        });
    }

    public function maskPhone(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);

        return strlen($d) >= 4 ? str_repeat('•', max(0, strlen($d) - 4)) . substr($d, -4) : $phone;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function pending(int $id, bool $allowExpired = false): object
    {
        $inv = DB::table('stage_supervisor_invitations')->where('id', $id)->first();
        if (!$inv) {
            throw new BusinessException('Invitation introuvable.', 'STAGE_INVITE_NOT_FOUND', 404);
        }
        if ($inv->accepted_at) {
            throw new BusinessException('Cette invitation a déjà été acceptée.', 'STAGE_INVITE_ACCEPTED', 409);
        }
        if ($inv->revoked_at && !$allowExpired) {
            throw new BusinessException('Cette invitation a été annulée.', 'STAGE_INVITE_REVOKED', 409);
        }

        return $inv;
    }

    private function sendLink(string $phone, ?string $name, string $token, User $by): void
    {
        $url  = rtrim((string) config('app.url'), '/') . '/services/invitation-superviseur?token=' . $token;
        $from = trim(($by->first_name ?? '') . ' ' . ($by->last_name ?? ''));

        $this->notifier->whatsapp(
            $phone,
            WhatsAppText::compose('Invitation - superviseur de stage', [
                'Bonjour' . ($name ? ' ' . $name : '') . ',',
                ($from ? "{$from} vous invite" : 'Vous êtes invité(e)') . ' à rejoindre la plateforme du CAP-EPAC en tant que *superviseur de stage*.',
                "Créez votre compte en ouvrant ce lien personnel (valable 72 heures, utilisable une seule fois) :\n{$url}",
                '_Si vous n\'attendiez pas ce message, ignorez-le._',
            ]),
            'stage-invitation-superviseur',
            'Stages',
        );
    }

    /** @return string[] numéros normalisés des superviseurs déjà inscrits */
    private function supervisorPhones(): array
    {
        return StageAccess::supervisors()->pluck('phone')->filter()
            ->map(fn ($p) => $this->normalize((string) $p))->filter()->values()->all();
    }

    private function normalize(string $raw): ?string
    {
        $n = $this->bridge->normalizePhone($raw);

        return $n ? '+' . ltrim($n, '+') : null;
    }
}
