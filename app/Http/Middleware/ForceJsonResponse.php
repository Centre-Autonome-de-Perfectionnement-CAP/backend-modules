<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Toute route de l'API répond en JSON, même si le client n'envoie pas
 * « Accept: application/json ».
 *
 * Bug corrigé : sans cet en-tête (cas de plusieurs appels du site vitrine,
 * dont le formulaire de contact), une erreur de validation était transformée
 * par Laravel en REDIRECTION vers la page précédente. Le navigateur recevait
 * alors une page HTML au lieu du message d'erreur : l'utilisateur voyait une
 * erreur générique (« impossible de joindre le serveur ») sans savoir quoi
 * corriger.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
