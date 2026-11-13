<?php

namespace App\Modules\Inscription\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Demandes\WorkflowConstants;
use App\Modules\WhatsApp\Models\WaMessageLog;
use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Messages WhatsApp groupés aux étudiants (module Inscription).
 *
 *  - seuls les étudiants cochés et les numéros ajoutés à la main reçoivent
 *    le message ; le numéro d'un étudiant est lu en base, jamais envoyé par
 *    le navigateur ;
 *  - un même numéro ne reçoit qu'une fois ;
 *  - envoi cadencé en arrière-plan (diffusion de masse du Notifier) : le
 *    message d'abord, puis chaque fichier ;
 *  - fichiers joints éphémères : effacés quelques jours après l'envoi ;
 *  - historique sans table dédiée : chaque envoi est journalisé par le
 *    module WhatsApp avec le contexte « diffusion:{id}:{auteur}:{total} ».
 */
class WhatsAppBroadcastService
{
    public const MAX_RECIPIENTS = 500;
    public const MAX_MESSAGE    = 2000;
    public const MAX_FILES      = 3;
    public const MAX_FILE_KB    = 5120;
    public const FILE_MIMES     = 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx';

    private const ROLES     = ['admin', 'responsable-division', 'secretaire'];
    private const MODULE    = 'Inscription';
    private const PREFIX    = 'diffusion:';
    private const DISK      = 'local';
    private const DIR       = 'diffusions';
    private const FILE_DAYS = 3;
    /** Cadence du Notifier : ~5 s par message WhatsApp. */
    private const SECONDS_PER_MESSAGE = 5;

    public function __construct(
        private readonly Notifier $notifier,
        private readonly WhatsAppBridgeClient $bridge,
    ) {}

    /** Administrateur, Responsable de division ou secrétariat. */
    public function canSend(?User $user): bool
    {
        return $user !== null
            && in_array(WorkflowConstants::canonicalRole($user->roles->first()?->slug), self::ROLES, true);
    }

    /**
     * @param int[]          $studentIds identifiants des dossiers cochés (pending_students.id)
     * @param UploadedFile[] $files
     * @return array{recipients: int, invalid_numbers: string[], without_phone: string[], minutes: int}
     */
    public function send(User $by, array $studentIds, string $rawNumbers, string $message, array $files): array
    {
        $message = trim(str_replace("\r\n", "\n", $message));
        if ($message === '') {
            throw new BusinessException('Écrivez le message à envoyer.', 'BROADCAST_EMPTY', 422);
        }

        // Étudiants cochés : numéro lu en base
        $phones = [];
        $withoutPhone = [];
        $rows = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->whereIn('pending_students.id', array_unique(array_map('intval', $studentIds)))
            ->where('pending_students.status', '!=', 'pending')
            ->get(['personal_information.last_name', 'personal_information.first_names', 'personal_information.contacts']);
        foreach ($rows as $r) {
            $phone = $this->normalize($this->notifier->phoneFromContacts($r->contacts));
            if ($phone) {
                $phones[$phone] = true;
            } else {
                $withoutPhone[] = trim($r->last_name . ' ' . $r->first_names);
            }
        }

        // Numéros ajoutés à la main : un par ligne (virgule ou point-virgule acceptés)
        $invalid = [];
        foreach ($this->splitNumbers($rawNumbers) as $raw) {
            $phone = $this->normalize($raw);
            if ($phone) {
                $phones[$phone] = true;
            } else {
                $invalid[] = $raw;
            }
        }

        $total = count($phones);
        if ($total === 0) {
            throw new BusinessException('Aucun destinataire avec un numéro WhatsApp valide.', 'BROADCAST_NO_RECIPIENT', 422);
        }
        if ($total > self::MAX_RECIPIENTS) {
            throw new BusinessException('Au plus ' . self::MAX_RECIPIENTS . ' destinataires par envoi : faites-en plusieurs.', 'BROADCAST_TOO_MANY', 422);
        }

        $id = Str::lower((string) Str::ulid());
        $context = self::PREFIX . "{$id}:{$by->id}:{$total}";

        $stored = [];
        foreach (array_values($files) as $i => $file) {
            $original = $file->getClientOriginalName();
            $stored[] = [
                'path' => $file->storeAs(self::DIR . "/{$id}", $this->safeName($original, $i), self::DISK),
                // Nom affiché au destinataire : celui du fichier choisi, sans caractères de chemin.
                'name' => Str::limit(trim(preg_replace('#[\\\\/\x00-\x1F]+#', ' ', $original)), 120, '') ?: 'document',
            ];
        }

        foreach (array_keys($phones) as $phone) {
            if (!$stored) {
                $this->notifier->whatsapp($phone, $message, $context, self::MODULE, true);
                continue;
            }
            // Le message d'abord (envoyé à part, avant le premier fichier), puis chaque fichier.
            foreach ($stored as $k => $f) {
                $this->notifier->whatsappFile($phone, self::DISK, $f['path'], $f['name'], $k === 0 ? $message : '', $context, self::MODULE, true);
            }
        }

        return [
            'recipients'      => $total,
            'invalid_numbers' => $invalid,
            'without_phone'   => $withoutPhone,
            'minutes'         => (int) max(1, ceil($total * (1 + count($stored)) * self::SECONDS_PER_MESSAGE / 60)),
        ];
    }

    /**
     * Derniers envois (30 jours) : auteur, date, destinataires, reçus, échecs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(): array
    {
        $groups = WaMessageLog::where('module', self::MODULE)->where('context', 'like', self::PREFIX . '%')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('context, min(created_at) as at')
            ->selectRaw("count(distinct case when status = 'sent' then recipient end) as sent")
            ->selectRaw("count(distinct case when status = 'failed' then recipient end) as failed")
            ->groupBy('context')->orderByDesc('at')->limit(20)->get();

        $authors = User::whereIn('id', $groups->map(fn ($g) => (int) explode(':', $g->context)[2])->unique())
            ->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $texts = WaMessageLog::whereIn('context', $groups->pluck('context'))->whereNull('file_name')
            ->where('message', '!=', '')->orderBy('id')->get(['context', 'message'])->unique('context')->keyBy('context');

        return $groups->map(function ($g) use ($authors, $texts) {
            [, $id, $author, $total] = explode(':', $g->context) + [null, null, 0, 0];
            $u = $authors[(int) $author] ?? null;

            return [
                'id'      => $id,
                'at'      => \Carbon\CarbonImmutable::parse($g->at)->toIso8601String(),
                'by'      => $u ? trim($u->first_name . ' ' . $u->last_name) : null,
                'total'   => (int) $total,
                'sent'    => (int) $g->sent,
                'failed'  => (int) $g->failed,
                'message' => Str::limit((string) ($texts[$g->context]->message ?? ''), 160),
            ];
        })->values()->all();
    }

    /** Fichiers joints : effacés quelques jours après l'envoi. */
    public function purgeFiles(): int
    {
        $disk = Storage::disk(self::DISK);
        $limit = now()->subDays(self::FILE_DAYS)->getTimestamp();
        $n = 0;
        foreach ($disk->directories(self::DIR) as $dir) {
            $files = $disk->files($dir);
            if (!$files || max(array_map(fn ($f) => $disk->lastModified($f), $files)) < $limit) {
                $disk->deleteDirectory($dir);
                $n++;
            }
        }

        return $n;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function normalize(?string $raw): ?string
    {
        return $raw === null || trim($raw) === '' ? null : $this->bridge->normalizePhone($raw);
    }

    /** @return string[] */
    private function splitNumbers(string $raw): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\n,;]+/', $raw) ?: []))));
    }

    private function safeName(string $original, int $i): string
    {
        $ext  = Str::lower(pathinfo($original, PATHINFO_EXTENSION));
        $base = Str::limit(Str::slug(pathinfo($original, PATHINFO_FILENAME)), 60, '') ?: 'document';

        return ($i + 1) . '-' . $base . ($ext !== '' ? '.' . $ext : '');
    }
}
