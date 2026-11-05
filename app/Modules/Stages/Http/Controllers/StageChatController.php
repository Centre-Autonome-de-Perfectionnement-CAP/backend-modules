<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Services\StageChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $data = $request->validate(['body' => ['required', 'string', 'max:' . (StageChatService::MAX_LENGTH + 500)]],
            ['body.required' => 'Écrivez un message.']);

        return $this->guard(fn () => $this->chat->post($channel, $this->who($request), $data['body']));
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
