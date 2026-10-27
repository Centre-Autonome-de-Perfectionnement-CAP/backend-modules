<?php

namespace App\Modules\Stages\Http\Middleware;

use App\Modules\Stages\Support\StageAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte superviseur (souvent extérieur au CAP) n'a accès qu'au module
 * Stages et à sa session : toute autre route de l'API lui est refusée, même
 * celles qui ne vérifient que l'authentification.
 */
class RestrictSupervisorScope
{
    private const ALLOWED = ['api/stages/*', 'api/auth/me', 'api/auth/logout', 'api/auth/login'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && $request->is('api/*') && !$request->is(...self::ALLOWED)) {
            $user = Auth::guard('sanctum')->user();
            if ($user && StageAccess::isSupervisor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé',
                    'error_code' => 'UNAUTHORIZED',
                ], 403);
            }
        }

        return $next($request);
    }
}
