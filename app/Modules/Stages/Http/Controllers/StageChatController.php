<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Services\StageChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Messagerie de stage, pour le personnel (progiciel) et pour les stagiaires
 * (espace stagiaire) : mêmes actions, seule l'identité change.
 */
class StageChatController extends Controller
{
    use ApiResponse;

    /** Clé d'une discussion dans l'adresse. */
    public const CHANNEL = 'general|groupe-[0-9]+';

    public function __construct(private readonly StageChatService $chat) {}

    public function index(Request $request): JsonResponse
    {
        return $this->guard(fn () => $this->chat->overview($this->who($request)));
    }

    public function show(Request $request, string $channel): JsonResponse
    {
        $data = $request->validate([
            'after'  => ['nullable', 'integer', 'min:1'],
            'before' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->guard(fn () => $this->chat->messages($channel, $this->who($request), $data['after'] ?? null, $data['before'] ?? null));
    }

    public function store(Request $request, string $channel): JsonResponse
    {
        $data = $request->validate([
            'body'     => ['nullable', 'string', 'max:' . (StageChatService::MAX_LENGTH + 500)],
            'reply_to' => ['nullable', 'integer', 'min:1'],
            'files'    => ['nullable', 'array', 'max:' . StageChatService::MAX_FILES],
            'files.*'  => ['file', 'max:' . StageChatService::MAX_FILE_KB,
                'mimes:' . implode(',', StageChatService::FILE_TYPES), 'mimetypes:' . implode(',', StageChatService::FILE_MIMES)],
        ], [
            'files.max'         => 'Trois fichiers au plus par message.',
            'files.*.max'       => 'Un fichier dépasse 5 Mo.',
            'files.*.mimes'     => 'Fichiers acceptés : PDF, Word, ZIP et images.',
            'files.*.mimetypes' => 'Fichiers acceptés : PDF, Word, ZIP et images.',
        ]);

        return $this->guard(fn () => $this->chat->post($channel, $this->who($request), (string) ($data['body'] ?? ''),
            $request->file('files', []), $data['reply_to'] ?? null));
    }

    /** Pièce jointe : aperçu dans la page pour une image, téléchargement sinon. */
    public function file(Request $request, string $channel, int $file): JsonResponse|StreamedResponse
    {
        if (!$this->chat->ready()) {
            return $this->errorResponse('La messagerie sera bientôt disponible.', 503, 'STAGE_CHAT_NOT_READY');
        }
        try {
            $f = $this->chat->file($channel, $this->who($request), $file);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
        $inline = str_starts_with($f->mime, 'image/');

        return $this->chat->disk()->response($f->path, $f->name, [
            'Content-Type' => $f->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }

    /** Personnel connecté, ou stagiaire (session de l'espace stagiaire). */
    private function who(Request $request): array
    {
        $intern = $request->attributes->get('intern');

        return $intern instanceof StageInternAccount
            ? StageChatService::intern($intern->stage)
            : StageChatService::staff($request->user());
    }

    private function guard(callable $fn): JsonResponse
    {
        if (!$this->chat->ready()) {
            return $this->errorResponse('La messagerie sera bientôt disponible.', 503, 'STAGE_CHAT_NOT_READY');
        }
        try {
            return $this->successResponse($fn());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}
