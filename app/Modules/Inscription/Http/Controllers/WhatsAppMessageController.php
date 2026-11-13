<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\WhatsAppBroadcastService as Broadcast;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Messages WhatsApp groupés : administrateur, Responsables de division, secrétariat. */
class WhatsAppMessageController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly Broadcast $broadcast) {}

    public function send(Request $request): JsonResponse
    {
        abort_unless($this->broadcast->canSend($request->user()), 403, "Vous n'avez pas accès à l'envoi de messages groupés.");

        $data = $request->validate([
            'etudiants'   => ['nullable', 'array', 'max:' . Broadcast::MAX_RECIPIENTS],
            'etudiants.*' => ['integer', 'min:1'],
            'numeros'     => ['nullable', 'string', 'max:20000'],
            'message'     => ['required', 'string', 'max:' . Broadcast::MAX_MESSAGE],
            'fichiers'    => ['nullable', 'array', 'max:' . Broadcast::MAX_FILES],
            'fichiers.*'  => ['file', 'max:' . Broadcast::MAX_FILE_KB, 'mimes:' . Broadcast::FILE_MIMES],
        ], [
            'fichiers.max'     => 'Au plus ' . Broadcast::MAX_FILES . ' fichiers par envoi.',
            'fichiers.*.max'   => 'Chaque fichier doit faire au plus 5 Mo.',
            'fichiers.*.mimes' => 'Fichiers acceptés : PDF, images (JPG, PNG), Word, Excel, PowerPoint.',
            'message.required' => 'Écrivez le message à envoyer.',
            'message.max'      => 'Le message est trop long (2 000 caractères au plus).',
        ]);

        $res = $this->broadcast->send(
            $request->user(),
            $data['etudiants'] ?? [],
            (string) ($data['numeros'] ?? ''),
            $data['message'],
            $request->file('fichiers', []),
        );

        return $this->successResponse($res, "Envoi lancé vers {$res['recipients']} destinataire(s).");
    }

    public function history(Request $request): JsonResponse
    {
        abort_unless($this->broadcast->canSend($request->user()), 403, "Vous n'avez pas accès à l'envoi de messages groupés.");

        return $this->successResponse($this->broadcast->history());
    }
}
