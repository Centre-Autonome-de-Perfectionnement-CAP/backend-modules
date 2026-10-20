<?php

namespace App\Modules\Stages\Http\Middleware;

use App\Modules\Stages\Services\InternAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Routes de l'espace stagiaire : jeton de session stagiaire obligatoire. */
class AuthenticateIntern
{
    public function __construct(private readonly InternAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $account = $this->auth->resolve($request->header(InternAuthService::HEADER));
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Votre session a expiré. Reconnectez-vous.',
                'error_code' => 'STAGE_INTERN_SESSION',
            ], 401);
        }
        $request->attributes->set('intern', $account);

        return $next($request);
    }
}
