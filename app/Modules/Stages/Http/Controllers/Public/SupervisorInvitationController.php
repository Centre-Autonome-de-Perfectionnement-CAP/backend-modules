<?php

namespace App\Modules\Stages\Http\Controllers\Public;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Services\SupervisorInvitationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Page publique d'acceptation d'une invitation de superviseur. */
class SupervisorInvitationController extends Controller
{
    use ApiResponse;

    private const TOKEN_RULE = ['required', 'string', 'size:48', 'alpha_num'];

    public function __construct(private readonly SupervisorInvitationService $service) {}

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => self::TOKEN_RULE]);
        $inv  = $this->service->findValid($data['token']);
        if (!$inv) {
            if ($this->service->wasAccepted($data['token'])) {
                return $this->errorResponse('Votre compte superviseur est déjà créé avec ce lien. Connectez-vous avec votre email et votre mot de passe.', 409, 'STAGE_INVITE_USED');
            }
            return $this->errorResponse("Ce lien d'invitation n'est plus valable. Demandez-en un nouveau au responsable de division.", 410, 'STAGE_INVITE_INVALID');
        }

        return $this->successResponse([
            'phone'      => $this->service->maskPhone($inv->phone),
            'name'       => $inv->name,
            'expires_at' => $inv->expires_at,
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => self::TOKEN_RULE,
            'prenom'   => ['required', 'string', 'min:2', 'max:80'],
            'nom'      => ['required', 'string', 'min:2', 'max:80'],
            'email'    => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);
        $data['prenom'] = trim(strip_tags($data['prenom']));
        $data['nom']    = trim(strip_tags($data['nom']));

        try {
            $user = $this->service->accept($data['token'], $data);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return $this->successResponse(['email' => $user->email], 'Votre compte superviseur est créé. Connectez-vous avec votre email et votre mot de passe.', 201);
    }
}
