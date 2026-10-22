<?php

use Illuminate\Support\Facades\Route;

// ========== FICHIERS DE STOCKAGE (avant les catch-all) ==========
//
// AJOUT (Benoite) - sert les fichiers de storage/app/public/ directement.
// N'entre jamais en collision avec le symlink standard public/storage
// (segment d'URL différent : "stockage", pas "storage").
//
// À VÉRIFIER : confirmer qu'un lien symbolique public/storage existe déjà
// (php artisan storage:link) ou non. Si non, cette route est le seul accès
// aux fichiers stockés et doit rester. Si oui, elle fait doublon - inoffensif
// mais à nettoyer plus tard si confirmé inutile.
//
// CORRECTIF SÉCURITÉ (audit 13/10/2026) :
//   1. Path traversal : le chemin était concaténé tel quel → /stockage/../../.env
//      (ou équivalent encodé) permettait de lire n'importe quel fichier du
//      serveur. Le chemin résolu (realpath) doit désormais rester DANS
//      storage/app/public.
//   2. Les dossiers des demandes d'attestation (demandes/…) contiennent des
//      pièces personnelles (acte de naissance, quittances…). Ils ne sont plus
//      servis publiquement : le progiciel y accède via la route authentifiée
//      /api/attestations/document-requests/{id}/files/... .
Route::get('/stockage/{path}', function (string $path) {
    $root     = realpath(storage_path('app/public'));
    $fullPath = $root ? realpath($root . DIRECTORY_SEPARATOR . $path) : false;

    $inside = $fullPath !== false
        && str_starts_with($fullPath, $root . DIRECTORY_SEPARATOR)
        && is_file($fullPath);

    $relative = $inside ? str_replace('\\', '/', substr($fullPath, strlen($root) + 1)) : '';

    if (!$inside || str_starts_with($relative, 'demandes/')) {
        abort(404, 'Fichier non trouvé.');
    }

    return response()->file($fullPath, ['X-Content-Type-Options' => 'nosniff']);
})->where('path', '.*');

// ========== APPLICATIONS FRONTEND ==========
//
// Structure réelle actuelle (vérifiée) :
//   public/app-cap/index.html          → site vitrine (racine)
//   public/app-cap-frontend/index.html → progiciel admin (préfixe /services)

// Route pour l'application principale app-cap (site vitrine à la racine)
Route::get('/', function () {
    return file_get_contents(public_path('app-cap/index.html'));
});

// Route pour app-cap-frontend (progiciel admin) - exclure les fichiers statiques
Route::get('/services/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route catch-all pour app-cap (doit être en dernier) - exclure les fichiers
// statiques, les routes API et le stockage
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!api/)(?!services/)(?!stockage/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
