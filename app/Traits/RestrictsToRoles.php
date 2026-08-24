<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * AJOUT (audit sécurité) — factorise le pattern déjà utilisé dans
 * ContratController::assertAdmin() et AdminUserController (middleware de
 * closure), pour éviter de dupliquer la même logique dans chaque nouveau
 * contrôleur. Toute route utilisant ce trait doit rester en plus derrière
 * `auth:sanctum` (ce trait ne vérifie QUE le rôle, pas l'authentification).
 *
 * ⚠️ Les rôles listés ici doivent exister réellement dans la table `roles`
 * en production — voir le point soulevé sur la migration
 * 2026_08_19_000001_sync_roles_and_users_to_production.php qui crée le rôle
 * 'admin' sans l'assigner à aucun utilisateur.
 */
trait RestrictsToRoles
{
    /**
     * Enregistre le middleware de contrôle de rôle (pattern identique à
     * AdminUserController). À appeler dans le constructeur du contrôleur.
     *
     * @param string[] $allowedRoles
     * @param string[] $only    Si fourni, restreint UNIQUEMENT ces actions
     *                          (les autres restent accessibles à tout
     *                          utilisateur authentifié — ou public, selon
     *                          le reste du middleware du contrôleur).
     * @param string[] $except  Si fourni, restreint TOUTES les actions SAUF
     *                          celles-ci.
     */
    protected function restrictToRoles(array $allowedRoles, array $only = [], array $except = []): void
    {
        $this->middleware('auth:sanctum', array_filter([
            'only'   => $only ?: null,
            'except' => $except ?: null,
        ]));

        $roleMiddleware = $this->middleware(function (Request $request, \Closure $next) use ($allowedRoles) {
            $slug = $request->user()?->roles?->first()?->slug;
            if (!in_array($slug, $allowedRoles, true)) {
                abort(403, 'Accès réservé aux rôles autorisés (' . implode(', ', $allowedRoles) . ').');
            }
            return $next($request);
        });

        if ($only) {
            $roleMiddleware->only($only);
        } elseif ($except) {
            $roleMiddleware->except($except);
        }
    }
}
